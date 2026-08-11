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
