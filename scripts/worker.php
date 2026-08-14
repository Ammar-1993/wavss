<?php

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../scanner/functions/databaseFunctions.php');
require_once(__DIR__ . '/../scanner/begin_scan.php'); // Loads runScan()

echo "Worker started. Listening for pending jobs...\n";

while (true) {
    if (!connectToDb($db)) {
        echo "Database connection failed. Retrying in 5 seconds...\n";
        sleep(5);
        continue;
    }

    // Find the oldest pending job
    $res = $db->query("SELECT * FROM jobs WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1");
    
    if ($res && $res->num_rows > 0) {
        $job = $res->fetch_object();
        
        // Mark as running
        $stmt = $db->prepare("UPDATE jobs SET status = 'running', started_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $job->id);
        $stmt->execute();
        $stmt->close();

        echo "Executing Job ID: {$job->id} (Test ID: {$job->test_id})\n";
        
        try {
            runScan($job->test_id, $job->url, $job->username, $job->email, $job->test_cases);
            
            $stmt = $db->prepare("UPDATE jobs SET status = 'done', finished_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $job->id);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
            $stmt = $db->prepare("UPDATE jobs SET status = 'failed', finished_at = NOW(), error_message = ? WHERE id = ?");
            $stmt->bind_param("si", $errorMsg, $job->id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $db->close();
    sleep(2); // Short idle sleep
}
