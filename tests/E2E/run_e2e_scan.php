<?php
// tests/E2E/run_e2e_scan.php
$baseURL = 'http://127.0.0.1:8084';
$cookieFile = tempnam(sys_get_temp_dir(), 'cookie');

function request($url, $postData = null) {
    global $baseURL, $cookieFile;
    $ch = curl_init($baseURL . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

echo "1. Fetching registration page...\n";
$html = request('/register.php');
preg_match('/name="csrf_token" value="(.*?)"/', $html, $matches);
$csrf = $matches[1];

echo "2. Registering test user...\n";
$username = 'e2euser' . time();
$email = 'e2e_' . time() . '@example.com';
request('/register.php', [
    'csrf_token' => $csrf,
    'regusername' => $username,
    'email' => $email,
    'regpassword' => 'password123',
    'regpassword2' => 'password123',
    'submit' => 'Register'
]);

echo "3. Logging in...\n";
$html = request('/login.php');
preg_match('/name="csrf_token" value="(.*?)"/', $html, $matches);
$csrf = $matches[1];
request('/login.php', [
    'csrf_token' => $csrf,
    'email' => $email,
    'password' => 'password123',
    'submit' => 'Login'
]);

echo "4. Submitting scan for DVWA...\n";
$html = request('/scanner.php');
preg_match('/name="csrf_token" value="(.*?)"/', $html, $matches);
$csrf = $matches[1];

$res = request('/scanner.php', [
    'csrf_token' => $csrf,
    'urlToScan' => 'http://localhost/tests/E2E/target.php',
    'sqli' => 'sqli',
    'basqli' => 'basqli',
    'submit' => 'Start Scan'
]);

if (preg_match('#beginScan\("http:\\\\/\\\\/localhost\\\\/tests\\\\/E2E\\\\/target\.php",\s*(\d+),#', $res, $matches) || 
    preg_match('#beginScan\("http://localhost/tests/E2E/target\.php",\s*(\d+),#', $res, $matches)) {
    $testId = $matches[1];
    file_put_contents(__DIR__ . '/last_test_id.txt', $testId);
} else {
    echo "Failed to extract testId from scanner page.\n";
    exit(1);
}

echo "5. Triggering backend scan process for Test ID $testId...\n";
request('/scanner/begin_scan.php', [
    'specifiedUrl' => 'http://localhost/tests/E2E/target.php',
    'testId' => $testId,
    'username' => $username,
    'email' => $email,
    'testCases' => ' sqli  basqli '
]);

echo "6. Polling for completion...\n";
$maxAttempts = 120; // 4 minutes max wait
$attempt = 0;
while ($attempt < $maxAttempts) {
    $status = request('/scanner/getStatus.php', ['testId' => $testId]);
    echo "Status: " . strip_tags($status) . "\n";
    if (stripos($status, 'Scan is complete') !== false) {
        break;
    }
    sleep(2);
    $attempt++;
}

if ($attempt >= $maxAttempts) {
    echo "Scan timed out.\n";
    exit(1);
}
echo "Scan finished!\n";
