<?php

$currentDir = './';
require_once($currentDir . 'functions/databaseFunctions.php');
//require_once('classes/Logger.php');

isset($_POST['testId']) ? $testId = (int)$_POST['testId'] : $testId = 0;

connectToDb($db);

$stmt = $db->prepare("SELECT * FROM tests WHERE id = ?");
$stmt->bind_param("i", $testId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_object();
$stmt->close();
$finished = $row->scan_finished;

//Update finish time to current time while scan is not finished
if($finished == 0)
{
	$now = time();
	$stmt = $db->prepare("UPDATE tests SET finish_timestamp = ? WHERE id = ?");
	$stmt->bind_param("ii", $now, $testId);
	$stmt->execute();
	$stmt->close();
}

$stmt = $db->prepare("SELECT * FROM tests WHERE id = ?");
$stmt->bind_param("i", $testId);
$stmt->execute();
$result = $stmt->get_result();

$row = $result->fetch_object();
$stmt->close();
$status = $row->status;
$startTime = $row->start_timestamp;
$finTime = $row->finish_timestamp;
$count = $row->numUrlsFound;
$numRequests = $row->num_requests_sent;

$duration = $finTime - $startTime;
$mins = intval($duration/60);
$seconds = $duration % 60;
$secondsStr = strval($seconds);
$secondsFormatted = str_pad($secondsStr,2,"0",STR_PAD_LEFT);

$stmt = $db->prepare("SELECT * FROM test_results WHERE test_id = ?");
$stmt->bind_param("i", $testId);
$stmt->execute();
$result = $stmt->get_result();
$numVulns = $result->num_rows;
$stmt->close();

//TODO: Put table here, looks bit messy
echo '<b>Scan Details:</b><br>';
echo 'Status: ' . $status;

echo "<br><br>No. URLs Found: $count";
echo "<br>Time Taken: $mins:$secondsFormatted";
echo "<br>No. HTTP Requests Sent: $numRequests";
echo "<br>No. Vulnerabilities Found: $numVulns";

$result->free();
$db->close();

?>