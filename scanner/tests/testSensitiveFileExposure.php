<?php

set_time_limit(0);
date_default_timezone_set('Asia/Riyadh');

function testSensitiveFileExposure($urlToCheck, $testId) {

    connectToDb($db);
    updateStatus($db, "Testing $urlToCheck for Sensitive File Exposure...", $testId);

    $log = new Logger();
    $log->lfile('logs/eventlogs');
    $log->lwrite("Starting Sensitive File Exposure test function on $urlToCheck");

    $pathsToCheck = array(
        '/.git/HEAD' => 'ref:',
        '/.env' => '=',
        '/backup.sql' => 'TABLE',
        '/wp-config.php.bak' => '<?php',
        '/.DS_Store' => 'Bud1' 
    );

    $baseUrl = rtrim($urlToCheck, '/');

    foreach ($pathsToCheck as $path => $signature) {
        $testUrl = $baseUrl . $path;
        
        $http = new http_class;
        $http->timeout=0;
        $http->data_timeout=0;
        $http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
        $http->follow_redirect=1;
        $http->redirection_limit=5;
        $http->setTestId($testId);

        $error=$http->GetRequestArguments($testUrl, $arguments);
        $error=$http->Open($arguments);

        if($error=="") {
            $log->lwrite("Sending HTTP request to $testUrl");
            $error=$http->SendRequest($arguments);

            if($error=="") {
                $headers=array();
                $error=$http->ReadReplyHeaders($headers);
                if($error=="") {
                    $responseCode = $http->response_status;
                    // Only proceed if it returns exactly a 2xx class response
                    if(intval($responseCode) >= 200 && intval($responseCode) < 300) {
                        $error = $http->ReadWholeReplyBody($body);
                        
                        // Verify the content signature matches (avoids false-positive 200 OK custom 404 pages)
                        if (strlen($error) == 0 && stripos($body, $signature) !== false) {
                            $log->lwrite("Sensitive file exposure found: $testUrl");
                            
                            $query = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'fileexposure' AND method = 'get' AND url = '$testUrl' AND attack_str = '$testUrl'";
                            $result = $db->query($query);
                            if($result && $result->num_rows == 0) {
                                insertTestResult($db, $testId, 'fileexposure', 'get', $testUrl, $testUrl);
                            }
                        }
                    }
                }
            }
            $http->Close();
        }
    }
}
?>
