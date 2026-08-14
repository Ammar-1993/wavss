<?php
session_start();
require_once(__DIR__ . '/csrf.php');
require_once(__DIR__ . '/vendor/autoload.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');

use PragmaRX\Google2FA\Google2FA;

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$msg = '';
$msgType = 'info';

if (!connectToDb($db)) {
    die("Database connection failed.");
}

// Check current status
$stmt = $db->prepare("SELECT totp_enabled FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$userRow = $res->fetch_object();
$stmt->close();

$totpEnabled = (bool)$userRow->totp_enabled;

$google2fa = new Google2FA();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code']) && !$totpEnabled) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $secret = $_SESSION['pending_totp_secret'] ?? '';
    $code = trim($_POST['verify_code']);
    
    if (empty($secret)) {
        $msg = "Session expired. Please reload the page and try again.";
        $msgType = 'danger';
    } else {
        $valid = $google2fa->verifyKey($secret, $code);
        if ($valid) {
            $stmt = $db->prepare("UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE username = ?");
            $stmt->bind_param("ss", $secret, $username);
            $stmt->execute();
            $stmt->close();
            
            $totpEnabled = true;
            unset($_SESSION['pending_totp_secret']);
            $msg = "Two-Factor Authentication has been successfully enabled!";
            $msgType = 'success';
        } else {
            $msg = "Invalid code. Please try again.";
            $msgType = 'danger';
        }
    }
}

// If not enabled and not just successfully verified, prep the setup variables
$qrCodeUrl = '';
$secretKey = '';
if (!$totpEnabled) {
    if (empty($_SESSION['pending_totp_secret'])) {
        $_SESSION['pending_totp_secret'] = $google2fa->generateSecretKey();
    }
    $secretKey = $_SESSION['pending_totp_secret'];
    
    // Generate QR Code URL
    $qrUrl = $google2fa->getQRCodeUrl(
        'WAVSS',
        $username,
        $secretKey
    );
    
    // Generate the QR code locally as a base64 PNG data URI
    $builder = new \Endroid\QrCode\Builder\Builder(
        writer: new \Endroid\QrCode\Writer\PngWriter(),
        data: $qrUrl,
        encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
        size: 250,
        margin: 10
    );
    $qrCode = $builder->build();
        
    $qrCodeUrl = $qrCode->getDataUri();
}

$pageTitle = 'WAVSS - Enable 2FA';
require_once($currentDir . 'templates/header.php');
?>

<div class="container my-5 flex-grow-1">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Two-Factor Authentication</h2>
        </div>
        <div class="card-body p-4">
          <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
          <?php endif; ?>

          <?php if ($totpEnabled): ?>
            <div class="text-center">
              <span class="badge bg-success fs-6 mb-3 p-2">2FA is Currently Enabled</span>
              <p>Your account is secured with Two-Factor Authentication.</p>
              <a href="scanner.php" class="btn btn-primary mt-3">Return to Scanner</a>
            </div>
          <?php else: ?>
            <p class="mb-4">Enhance the security of your account by enabling Two-Factor Authentication. Scan the QR code below with an authenticator app (like Google Authenticator or Authy).</p>
            
            <div class="text-center mb-4">
              <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code" class="img-thumbnail">
            </div>
            
            <div class="mb-4 text-center">
              <p class="text-muted mb-1">If you can't scan the QR code, manually enter this secret key into your app:</p>
              <code><?php echo htmlspecialchars($secretKey); ?></code>
            </div>
            
            <hr>
            
            <form method="post" class="mt-4">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
              <div class="mb-3">
                <label for="verify_code" class="form-label">Enter 6-Digit Code</label>
                <input type="text" name="verify_code" id="verify_code" class="form-control text-center mx-auto" style="letter-spacing: 5px; font-size: 1.2rem; max-width: 200px;" maxlength="6" required autocomplete="off">
              </div>
              <div class="text-center">
                <button type="submit" class="btn btn-success">Verify and Enable 2FA</button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once($currentDir . 'templates/footer.php'); ?>
