<?php
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../scanner/functions/databaseFunctions.php');

$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing or invalid Authorization header']);
    exit;
}

$apiKey = $matches[1];

if (!connectToDb($db)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stmt = $db->prepare("SELECT username FROM api_keys WHERE api_key = ?");
$stmt->bind_param("s", $apiKey);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}
$user = $res->fetch_object();
$username = $user->username;
$stmt->close();

$testId = isset($_GET['testId']) ? (int)$_GET['testId'] : 0;

if (!$testId) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing or invalid testId parameter']);
    exit;
}

// Fetch test data making sure it belongs to the username
$stmt = $db->prepare("SELECT scan_finished FROM tests WHERE id = ? AND username = ?");
$stmt->bind_param("is", $testId, $username);
$stmt->execute();
$testRes = $stmt->get_result();

if ($testRes->num_rows === 0) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Test not found or unauthorized']);
    exit;
}

$test = $testRes->fetch_object();
$stmt->close();

if (!$test->scan_finished) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Report is not available yet because the scan is not finished.']);
    exit;
}

$reportPath = __DIR__ . '/../../scanner/reports/Test_' . $testId . '.pdf';

if (!file_exists($reportPath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Report file not found.']);
    exit;
}

// Serve the PDF binary
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="WAVSS_Report_Test_' . $testId . '.pdf"');
header('Content-Length: ' . filesize($reportPath));
header('Cache-Control: no-cache, must-revalidate');
readfile($reportPath);
exit;
