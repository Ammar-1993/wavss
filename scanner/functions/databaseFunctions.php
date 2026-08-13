<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

//Connects to database. Returns true on success, False on failure.
function connectToDb(&$db)
{
	$db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASSWORD'), getenv('DB_NAME'));
	if (mysqli_connect_errno()) {
		return false;
	}
	return true;
}

//Update status of test in db
//e.g. updateStatus($db, 'Starting scan...', 1234);
//Returns true on success, False on failure.
function updateStatus($db, $newStatus, $testId)
{
	$stmt = $db->prepare("UPDATE tests SET status = ? WHERE id = ?");
	$stmt->bind_param("si", $newStatus, $testId);
	$result = $stmt->execute();
	$stmt->close();
	return $result;
}

function insertTestResult($db, $testId, $type, $method, $url, $attackStr)
{
	$stmt = $db->prepare("INSERT into test_results(test_id, type, method, url, attack_str) VALUES(?,?,?,?,?)");
	$stmt->bind_param("issss", $testId, $type, $method, $url, $attackStr);
	$result = $stmt->execute();
	$stmt->close();
	return $result;
}

//Generates the next test id
//Return the next test id on success. Otherwise returns false.
function generateNextTestId($db)
{
	$query = "SELECT MAX(id) FROM tests";
	$result = $db->query($query);
	if (!$result)
		return $result;

	$row = $result->fetch_array();

	$maxId = $row[0] + 1;
	//$maxId = $row->id;//or else $row->MAX(id)
	return $maxId;
}

//Adds 1 to the current number of HTTP requests sent
//Returns true on success, false on failure
function incrementHttpRequests($db, $testId)
{
	$stmt = $db->prepare("UPDATE tests SET num_requests_sent = (num_requests_sent + 1) WHERE id = ?");
	$stmt->bind_param("i", $testId);
	$result = $stmt->execute();
	$stmt->close();
	return $result;
}

// Initializes a new scan, handling rate limits and domain validation
// Returns ['success' => true, 'testId' => $testId] or ['success' => false, 'error' => 'message']
function initializeNewScan($db, $username, $urlToScan, $skipConcurrencyCheck = false)
{
	$parsedHost = parse_url($urlToScan, PHP_URL_HOST);
	if (!$parsedHost) {
		$parsedHost = parse_url("http://" . $urlToScan, PHP_URL_HOST);
	}
	
	$isLocal = in_array(strtolower($parsedHost), ['localhost', '127.0.0.1', '::1', '[::1]', 'dvwa']);
	
	if (!$isLocal) {
		$verifyQuery = "SELECT id FROM domain_verifications WHERE username = ? AND domain = ? AND verified = 1";
		$verifyStmt = $db->prepare($verifyQuery);
		$verifyStmt->bind_param('ss', $username, $parsedHost);
		$verifyStmt->execute();
		$verifyRes = $verifyStmt->get_result();
		
		if ($verifyRes->num_rows == 0) {
			return ['success' => false, 'error' => "You must prove ownership of the domain " . htmlspecialchars($parsedHost) . " before scanning it."];
		}
	}

	if (!$skipConcurrencyCheck) {
		$activeScanQuery = "SELECT id FROM tests WHERE username = ? AND type = 'scan' AND scan_finished = 0";
		$activeScanStmt = $db->prepare($activeScanQuery);
		$activeScanStmt->bind_param('s', $username);
		$activeScanStmt->execute();
		if ($activeScanStmt->get_result()->num_rows > 0) {
			return ['success' => false, 'error' => "You already have an active scan running. Please wait for it to finish before starting a new one."];
		}
	}

	$recentScanQuery = "SELECT start_timestamp FROM tests WHERE username = ? AND url = ? ORDER BY start_timestamp DESC LIMIT 1";
	$recentScanStmt = $db->prepare($recentScanQuery);
	$recentScanStmt->bind_param('ss', $username, $urlToScan);
	$recentScanStmt->execute();
	$recentScanRes = $recentScanStmt->get_result();
	if ($recentScanRes->num_rows > 0) {
		$row = $recentScanRes->fetch_object();
		$timeSince = time() - $row->start_timestamp;
		if ($timeSince < 300) {
			$remaining = 300 - $timeSince;
			return ['success' => false, 'error' => "You scanned this URL recently. Please wait $remaining seconds before scanning it again."];
		}
	}

	$nextId = generateNextTestId($db);

	if (!$nextId) {
		return ['success' => false, 'error' => "Failed to generate next test ID."];
	}

	$testId = $nextId;
	$now = time();
	$query = "INSERT into tests(id,status,numUrlsFound,type,num_requests_sent,start_timestamp,finish_timestamp,scan_finished,url,username,urls_found) VALUES(?, 'Creating profile for new scan...', 0, 'scan', 0, ?, ?, 0, ?, ?, '')";
	$stmt = $db->prepare($query);
	$stmt->bind_param('iiiss', $nextId, $now, $now, $urlToScan, $username);
	$result = $stmt->execute();
	$stmt->close();
	if (!$result) {
		return ['success' => false, 'error' => "Problem inserting a new test into the database. Please try again."];
	}

	updateStatus($db, 'Pending...', $testId);

	$stmt = $db->prepare("UPDATE tests SET numUrlsFound = 0, duration = 0 WHERE id = ?");
	$stmt->bind_param("i", $testId);
	$stmt->execute();
	$stmt->close();

	return ['success' => true, 'testId' => $testId];
}
