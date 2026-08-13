<?php
header('Content-Type: application/json');

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../scanner/functions/databaseFunctions.php');

$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing or invalid Authorization header']);
    exit;
}

$apiKey = $matches[1];

if (!connectToDb($db)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stmt = $db->prepare("SELECT username FROM api_keys WHERE api_key = ?");
$stmt->bind_param("s", $apiKey);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$user = $res->fetch_object();
$username = $user->username;
$stmt->close();

// Update last used
$updateStmt = $db->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE api_key = ?");
$updateStmt->bind_param("s", $apiKey);
$updateStmt->execute();
$updateStmt->close();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing url in JSON request body']);
    exit;
}

$urlToScan = trim($input['url']);

// Use the newly extracted validation and insertion function
$scanInit = initializeNewScan($db, $username, $urlToScan);

if (!$scanInit['success']) {
    http_response_code(400);
    echo json_encode(['error' => $scanInit['error']]);
    exit;
}

$testId = $scanInit['testId'];
$testCases = 'rxss sxss sqli basqli autoc idor dirlist bannerdis sslcert unredir emailpdf crawlurl ';

// Trigger the backend scan processor asynchronously via HTTP so the API request doesn't block for minutes
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/scanner/begin_scan.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'specifiedUrl' => $urlToScan,
    'testId' => $testId,
    'username' => $username,
    'email' => '',
    'testCases' => $testCases
]));
// Set a 1-second timeout to abandon the request and close connection, letting the PHP script run in background
curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
@curl_exec($ch);
curl_close($ch);

http_response_code(201);
echo json_encode([
    'message' => 'Scan initiated successfully',
    'testId' => $testId
]);
