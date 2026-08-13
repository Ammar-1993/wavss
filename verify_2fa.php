<?php
session_start();
require_once(__DIR__ . '/csrf.php');
require_once(__DIR__ . '/vendor/autoload.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');

use PragmaRX\Google2FA\Google2FA;

if (!isset($_SESSION['pending_2fa_username'])) {
    header('Location: login.php');
    exit;
}

$pendingUsername = $_SESSION['pending_2fa_username'];
$pendingEmail = $_SESSION['pending_2fa_email'] ?? '';
$msg = '';

if (!connectToDb($db)) {
    die("Database connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $code = trim($_POST['verify_code']);
    
    // Fetch the secret for this user
    $stmt = $db->prepare("SELECT totp_secret FROM users WHERE username = ?");
    $stmt->bind_param("s", $pendingUsername);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_object();
    $stmt->close();
    
    if ($row && !empty($row->totp_secret)) {
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($row->totp_secret, $code);
        
        if ($valid) {
            $_SESSION['username'] = $pendingUsername;
            if (!empty($pendingEmail)) {
                $_SESSION['email'] = $pendingEmail;
            }
            unset($_SESSION['pending_2fa_username']);
            unset($_SESSION['pending_2fa_email']);
            header('Location: index.php');
            exit;
        } else {
            $msg = 'Invalid code. Please try again.';
        }
    } else {
        $msg = '2FA is not properly configured for this account.';
    }
}

$pageTitle = 'WAVSS - Verify 2FA';
require_once($currentDir . 'templates/header.php');
?>

<div class="container my-5 flex-grow-1">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Two-Factor Authentication</h2>
        </div>
        <div class="card-body p-4 text-center">
          <?php if ($msg) echo "<div class='alert alert-danger'>$msg</div>"; ?>
          <p class="mb-4">Please enter the 6-digit code from your authenticator app to continue.</p>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="mb-3">
              <input type="text" name="verify_code" class="form-control text-center mx-auto" style="letter-spacing: 5px; font-size: 1.5rem; max-width: 200px;" maxlength="6" required autocomplete="off" autofocus>
            </div>
            <button type="submit" class="btn btn-primary">Verify</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once($currentDir . 'templates/footer.php'); ?>
