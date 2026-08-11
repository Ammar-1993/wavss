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
$username = 'e2e_user_' . time();
request('/register.php', [
    'csrf_token' => $csrf,
    'username' => $username,
    'email' => 'e2e@example.com',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'submit' => 'Register'
]);

echo "3. Logging in...\n";
$html = request('/login.php');
preg_match('/name="csrf_token" value="(.*?)"/', $html, $matches);
$csrf = $matches[1];
request('/login.php', [
    'csrf_token' => $csrf,
    'email' => 'e2e@example.com',
    'password' => 'password123',
    'submit' => 'Login'
]);

echo "4. Submitting scan for DVWA...\n";
$html = request('/scanner.php');
preg_match('/name="csrf_token" value="(.*?)"/', $html, $matches);
$csrf = $matches[1];

$res = request('/scanner.php', [
    'csrf_token' => $csrf,
    'urlToScan' => 'http://dvwa/login.php',
    'sqli' => 'sqli',
    'submit' => 'Start Scan'
]);

if (preg_match('/beginScan\("http:\\\/\\\/dvwa\\\/login\.php",(\d+),/', $res, $matches) || 
    preg_match('/beginScan\("http:\/\/dvwa\/login\.php",(\d+),/', $res, $matches)) {
    $testId = $matches[1];
    file_put_contents(__DIR__ . '/last_test_id.txt', $testId);
} else {
    die("Failed to extract testId from scanner page.\n");
}

echo "5. Triggering backend scan process for Test ID $testId...\n";
request('/scanner/begin_scan.php', [
    'specifiedUrl' => 'http://dvwa/login.php',
    'testId' => $testId,
    'username' => $username,
    'email' => 'e2e@example.com',
    'testCases' => ' sqli '
]);

echo "6. Polling for completion...\n";
$maxAttempts = 120; // 4 minutes max wait
$attempt = 0;
while ($attempt < $maxAttempts) {
    $status = request('/scanner/getStatus.php', ['testId' => $testId]);
    echo "Status: " . strip_tags($status) . "\n";
    if (strpos($status, 'Scan Complete') !== false) {
        break;
    }
    sleep(2);
    $attempt++;
}

if ($attempt >= $maxAttempts) {
    die("Scan timed out.\n");
}
echo "Scan finished!\n";
