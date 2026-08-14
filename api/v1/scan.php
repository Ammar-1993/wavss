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
$hashedApiKey = hash('sha256', $apiKey);

if (!connectToDb($db)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stmt = $db->prepare("SELECT username FROM api_keys WHERE api_key = ?");
$stmt->bind_param("s", $hashedApiKey);
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
$updateStmt->bind_param("s", $hashedApiKey);
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

// Queue the scan job in the database
$email = '';
$stmt = $db->prepare("INSERT INTO jobs (test_id, url, username, email, test_cases) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $testId, $urlToScan, $username, $email, $testCases);
$stmt->execute();
$stmt->close();

http_response_code(201);
echo json_encode([
    'message' => 'Scan initiated successfully',
    'testId' => $testId
]);
