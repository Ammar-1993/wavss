<?php
session_start();
require_once(__DIR__ . '/csrf.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');

function is_safe_public_host($host) {
    $ip = gethostbyname($host);
    if ($ip === $host) {
        return false;
    }
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

if (!isset($_SESSION['username'])) {
    die("You must be logged in to access this page.");
}

$username = $_SESSION['username'];
$msg = '';

if (!connectToDb($db)) {
    die("Database connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain_to_add'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $domain = trim($_POST['domain_to_add']);
    $parsedHost = parse_url($domain, PHP_URL_HOST);
    if (!$parsedHost) {
        $parsedHost = parse_url("http://" . $domain, PHP_URL_HOST);
    }
    $domain = $parsedHost ?: $domain;

    if (!empty($domain)) {
        $stmt = $db->prepare("SELECT id FROM domain_verifications WHERE username = ? AND domain = ?");
        $stmt->bind_param('ss', $username, $domain);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows == 0) {
            $token = bin2hex(random_bytes(16));
            $stmt = $db->prepare("INSERT INTO domain_verifications (username, domain, verification_token, verified, created_at) VALUES (?, ?, ?, 0, NOW())");
            $stmt->bind_param('sss', $username, $domain, $token);
            $stmt->execute();
            $msg = "Domain added. Please verify it.";
        } else {
            $msg = "Domain is already listed.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_domain_id'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $id = (int)$_POST['verify_domain_id'];
    $stmt = $db->prepare("SELECT domain, verification_token FROM domain_verifications WHERE id = ? AND username = ? AND verified = 0");
    $stmt->bind_param('is', $id, $username);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_object();
        $domain = $row->domain;
        $token = $row->verification_token;
        
        $verified = false;
        
        // Option (a): Check DNS TXT
        $records = @dns_get_record($domain, DNS_TXT);
        if ($records) {
            foreach ($records as $record) {
                if (isset($record['txt']) && $record['txt'] === "wavss-verification=$token") {
                    $verified = true;
                    break;
                }
            }
        }
        
        // Option (b): Check HTTP GET
        $ssrf_error = false;
        if (!$verified) {
            if (!is_safe_public_host($domain)) {
                $ssrf_error = true;
            } else {
                $url = "http://" . $domain . "/wavss-verify.txt";
                $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                $content = @file_get_contents($url, false, $ctx);
                if ($content !== false && trim($content) === $token) {
                    $verified = true;
                }
            }
        }
        
        if ($verified) {
            $stmt = $db->prepare("UPDATE domain_verifications SET verified = 1 WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $msg = "Domain <b>" . htmlspecialchars($domain) . "</b> successfully verified!";
        } else {
            if ($ssrf_error) {
                $msg = "<span style='color:red;'>Verification failed: <b>" . htmlspecialchars($domain) . "</b> resolves to an invalid or internal IP address (SSRF protection).</span>";
            } else {
                $msg = "<span style='color:red;'>Verification failed for <b>" . htmlspecialchars($domain) . "</b>. Please ensure the DNS record or file is published correctly and try again.</span>";
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM domain_verifications WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$domains = $stmt->get_result();
$pageTitle = 'WAVSS - Verify Domain Ownership';
require_once($currentDir . 'templates/header.php');
?>
    
    <div id="toprowsub">
        <div class="center">
            <h2>Domain Verification</h2>
        </div>
    </div>
    
    <div id="midrow">
        <div class="center">
            <div class="textbox2" style="padding:20px; background:#fff; border-radius:5px;">
                <?php if ($msg) echo "<p>$msg</p>"; ?>
                
                <h3>Add a New Domain</h3>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="text" name="domain_to_add" placeholder="e.g. example.com" required>
                    <input type="submit" class="button" value="Add Domain">
                </form>
                
                <hr style="margin:20px 0;">
                
                <h3>Your Registered Domains</h3>
                <table width="100%" border="1" cellpadding="5" cellspacing="0" style="text-align:left; border-collapse:collapse;">
                    <tr style="background:#f4f4f4;">
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Verification Actions</th>
                    </tr>
                    <?php while ($row = $domains->fetch_object()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row->domain); ?></td>
                        <td><?php echo $row->verified ? '<strong style="color:green;">Verified</strong>' : '<strong style="color:orange;">Unverified</strong>'; ?></td>
                        <td>
                            <?php if (!$row->verified): ?>
                                <p style="margin-top:0;">To prove ownership, complete one of the following methods:</p>
                                <ul>
                                    <li><strong>DNS:</strong> Create a TXT record for <code><?php echo htmlspecialchars($row->domain); ?></code> containing: <br><code>wavss-verification=<?php echo htmlspecialchars($row->verification_token); ?></code></li>
                                    <li><strong>File:</strong> Upload a file to <code>http://<?php echo htmlspecialchars($row->domain); ?>/wavss-verify.txt</code> containing just: <br><code><?php echo htmlspecialchars($row->verification_token); ?></code></li>
                                </ul>
                                <form method="post" style="margin-top:10px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="verify_domain_id" value="<?php echo $row->id; ?>">
                                    <input type="submit" class="button" value="Verify now">
                                </form>
                            <?php else: ?>
                                <em>No further action required.</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <br>
                <a href="scanner.php" class="button">Back to Scanner</a>
            </div>
        </div>
    </div>
<?php require_once($currentDir . 'templates/footer.php'); ?>
