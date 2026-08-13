<?php
// Prevent buffering
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$currentDir = './';
require_once($currentDir . 'functions/databaseFunctions.php');

$testId = isset($_GET['testId']) ? (int)$_GET['testId'] : 0;
if ($testId === 0) {
    echo "event: error\ndata: " . json_encode("Invalid test ID") . "\n\n";
    exit;
}

connectToDb($db);

$lastStatusText = null;
$lastVulnCount = -1;
$maxDuration = 600; // 10 minutes max loop safeguard
$startTime = time();

while (true) {
    if (time() - $startTime > $maxDuration) {
        break;
    }
    
    // Poll status
    $stmt = $db->prepare("SELECT status, start_timestamp, finish_timestamp, numUrlsFound, num_requests_sent, scan_finished FROM tests WHERE id = ?");
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_object();
    $stmt->close();
    
    if (!$row) {
        break;
    }
    
    $finished = $row->scan_finished;
    
    // Update finish time to current time while scan is not finished
    if ($finished == 0) {
        $now = time();
        $stmt2 = $db->prepare("UPDATE tests SET finish_timestamp = ? WHERE id = ?");
        $stmt2->bind_param("ii", $now, $testId);
        $stmt2->execute();
        $stmt2->close();
        $row->finish_timestamp = $now;
    }
    
    // Check vulnerabilities count
    $stmt = $db->prepare("SELECT type, method, url, attack_str FROM test_results WHERE test_id = ?");
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $vulnResult = $stmt->get_result();
    $numVulns = $vulnResult->num_rows;
    
    // Format status output (matching getStatus.php exactly)
    $duration = $row->finish_timestamp - $row->start_timestamp;
    $mins = intval($duration / 60);
    $seconds = $duration % 60;
    $secondsFormatted = str_pad((string)$seconds, 2, "0", STR_PAD_LEFT);
    
    $statusOutput = '<b>Scan Details:</b><br>';
    $statusOutput .= 'Status: ' . $row->status;
    $statusOutput .= "<br><br>No. URLs Found: " . $row->numUrlsFound;
    $statusOutput .= "<br>Time Taken: $mins:$secondsFormatted";
    $statusOutput .= "<br>No. HTTP Requests Sent: " . $row->num_requests_sent;
    $statusOutput .= "<br>No. Vulnerabilities Found: $numVulns";
    
    // Only emit status if it changed since last iteration
    if ($statusOutput !== $lastStatusText) {
        $lastStatusText = $statusOutput;
        echo "event: status\n";
        // JSON encode to safely escape newlines and quotes inside the SSE data frame
        echo "data: " . json_encode($statusOutput) . "\n\n"; 
    }
    
    // Format vulnerabilities output (matching getVulnerabilities.php exactly)
    if ($numVulns !== $lastVulnCount) {
        $lastVulnCount = $numVulns;
        
        if ($numVulns > 0) {
            $vulnOutput = '<b>Vulnerabilites Found:</b>';
            
            while ($vRow = $vulnResult->fetch_object()) {
                $type = $vRow->type;
                $method = strtoupper($vRow->method);
                $url = $vRow->url;
                $info = $vRow->attack_str;
                
                if ($type == 'rxss') {
                    $type = 'Reflected Cross-Site Scripting';
                    $info = 'Query Used: ' . $info;
                } else if ($type == 'sxss') {
                    $type = 'Stored Cross-Site Scripting';
                    $info = 'Query Used: ' . $info;
                } else if ($type == 'sqli') {
                    $type = 'SQL Injection';
                    $info = 'Query Used: ' . $info;
                } else if ($type == 'idor') {
                    $type = '(Potentially Insecure) Direct Object Reference';
                    $info = 'Object Referenced: ' . $info;
                } else if ($type == 'basqli') {
                    $type = 'Broken Authentication using SQL Injection';
                    $info = 'Query Used: ' . $info;
                } else if ($type == 'unredir') {
                    $type = 'Unvalidated Redirects';
                    $info = 'URL Requested: ' . $info;
                } else if ($type == 'dirlist') {
                    $type = 'Directory Listing enabled';
                    $info = 'URL Requested: ' . $info;
                } else if ($type == 'bannerdis') {
                    $type = 'HTTP Banner Disclosure';
                    $info = 'Information Exposed: ' . $info;
                } else if ($type == 'autoc') {
                    $type = 'Autocomplete not disabled on password input field';
                    $info = 'Input Name: ' . $info;
                } else if ($type == 'sslcert') {
                    $type = 'SSL certificate is not trusted';
                    $info = 'URL Requested: ' . $info;
                }
                
                $vulnOutput .= "<p><b>$type</b><br>";
                $vulnOutput .= "$method " . htmlspecialchars($url) . "<br>";
                $vulnOutput .= htmlspecialchars($info) . "</p>";
            }
        } else {
            $vulnOutput = '<b>No Vulnerabilities Found Yet</b>';
        }
        
        echo "event: vulnerability\n";
        echo "data: " . json_encode($vulnOutput) . "\n\n";
    }
    
    $vulnResult->free();
    $stmt->close();
    
    if (ob_get_level() > 0) ob_flush();
    flush();
    
    // Stop stream if scan is finished
    if ($finished == 1) {
        echo "event: done\n";
        echo "data: " . json_encode("finished") . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
        break;
    }
    
    usleep(500000); // 500ms
}

$db->close();
