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

$testId = isset($_GET['testId']) ? (int)$_GET['testId'] : 0;

if (!$testId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid testId parameter']);
    exit;
}

// Fetch test data making sure it belongs to the username
$stmt = $db->prepare("SELECT status, scan_finished, numUrlsFound, num_requests_sent, url FROM tests WHERE id = ? AND username = ?");
$stmt->bind_param("is", $testId, $username);
$stmt->execute();
$testRes = $stmt->get_result();

if ($testRes->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Test not found or unauthorized']);
    exit;
}

$test = $testRes->fetch_object();
$stmt->close();

// Fetch vulnerability count
$vulnStmt = $db->prepare("SELECT COUNT(*) as vuln_count FROM test_results WHERE test_id = ?");
$vulnStmt->bind_param("i", $testId);
$vulnStmt->execute();
$vulnRes = $vulnStmt->get_result();
$vulnCount = $vulnRes->fetch_object()->vuln_count ?? 0;
$vulnStmt->close();

echo json_encode([
    'testId' => $testId,
    'url' => $test->url,
    'statusText' => $test->status,
    'isFinished' => (bool)$test->scan_finished,
    'urlsFound' => (int)$test->numUrlsFound,
    'requestsSent' => (int)$test->num_requests_sent,
    'vulnerabilitiesFound' => (int)$vulnCount
]);
