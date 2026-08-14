<?php

set_time_limit(0);
date_default_timezone_set('Asia/Riyadh');

function testSecurityHeaders($urlToCheck, $testId) {

    connectToDb($db);
    updateStatus($db, "Testing $urlToCheck for Security Headers...", $testId);

    $log = new Logger();
    $log->lfile('logs/eventlogs');

    $log->lwrite("Starting Security Headers test function on $urlToCheck");

    $http = new http_class;
    $http->timeout=0;
    $http->data_timeout=0;
    $http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
    $http->follow_redirect=1;
    $http->redirection_limit=5;
    $http->setTestId($testId);

    $error=$http->GetRequestArguments($urlToCheck,$arguments);
                        
    $error=$http->Open($arguments);

    $log->lwrite("URL to be requested is: $urlToCheck");

    if($error=="") {
        $log->lwrite("Sending HTTP request to $urlToCheck");
        $error=$http->SendRequest($arguments);
        
        if($error=="") {
            $headers=array();
            $error=$http->ReadReplyHeaders($headers);
            if($error=="") {           
                $requiredHeaders = array(
                    'content-security-policy',
                    'x-frame-options',
                    'x-content-type-options'
                );
                
                // Only require HSTS over HTTPS
                if (stripos($urlToCheck, 'https://') === 0) {
                    $requiredHeaders[] = 'strict-transport-security';
                }

                foreach($requiredHeaders as $header) {
                    if(!isset($headers[$header])) {
                        $log->lwrite("Security header missing: $header on $urlToCheck");
                        
                        $query = "SELECT * FROM test_results WHERE test_id = $testId AND type = 'secheaders' AND method = 'get' AND url = '$urlToCheck' AND attack_str = 'Missing $header'";
                        $result = $db->query($query);
                        if($result && $result->num_rows == 0) {
                            insertTestResult($db, $testId, 'secheaders', 'get', $urlToCheck, "Missing $header");
                        }
                    }
                }
            }
        }
        $http->Close();
    }
}
?>
