<?php
/**
 * WAVSS Scheduled Scan Runner
 * 
 * This script is intended to be executed via a cron job on the host system or inside the container.
 * 
 * Example crontab entry (run hourly at the top of the hour):
 * 0 * * * * /usr/local/bin/php /var/www/html/scripts/run_scheduled_scans.php >> /var/www/html/logs/cron.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../scanner/functions/databaseFunctions.php');

if (!connectToDb($db)) {
    die("Database connection failed.\n");
}

$query = "SELECT id, username, url, frequency_hours FROM scheduled_scans WHERE active = 1 AND next_run_at <= NOW()";
$res = $db->query($query);

if (!$res || $res->num_rows === 0) {
    echo "No scheduled scans to run at this time.\n";
    exit;
}

echo "Found " . $res->num_rows . " scans to execute.\n";

$testCases = 'rxss sxss sqli basqli autoc idor dirlist bannerdis sslcert unredir emailpdf crawlurl ';

while ($row = $res->fetch_object()) {
    echo "Processing scan for URL: {$row->url} (User: {$row->username})\n";
    
    // Initialize scan (skip concurrency check because it's a cron)
    $scanInit = initializeNewScan($db, $row->username, $row->url, true);
    
    if ($scanInit['success']) {
        $testId = $scanInit['testId'];
        echo "Scan initiated successfully with testId {$testId}\n";
        
        // Trigger the backend scan processor asynchronously
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/scanner/begin_scan.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'specifiedUrl' => $row->url,
            'testId' => $testId,
            'username' => $row->username,
            'email' => '',
            'testCases' => $testCases
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
        @curl_exec($ch);
        curl_close($ch);
    } else {
        echo "Failed to initialize scan: " . $scanInit['error'] . "\n";
    }
    
    // Update next_run_at to NOW() + frequency_hours
    $updateStmt = $db->prepare("UPDATE scheduled_scans SET next_run_at = DATE_ADD(NOW(), INTERVAL ? HOUR) WHERE id = ?");
    $updateStmt->bind_param("ii", $row->frequency_hours, $row->id);
    $updateStmt->execute();
    $updateStmt->close();
}

echo "Finished processing all scheduled scans.\n";
