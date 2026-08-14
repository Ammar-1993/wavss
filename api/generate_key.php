<?php
session_start();
require_once(__DIR__ . '/../csrf.php');
$currentDir = '../';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');

if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$msg = '';
$newKey = '';

if (!connectToDb($db)) {
    die("Database connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $newKey = bin2hex(random_bytes(20));
    $hashedKey = hash('sha256', $newKey);
    $prefix = substr($newKey, 0, 8);
    
    $stmt = $db->prepare("INSERT INTO api_keys (username, api_key, prefix, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $username, $hashedKey, $prefix);
    if ($stmt->execute()) {
        $msg = "New API key generated successfully. Please copy it now, as it will not be shown again.";
    } else {
        $msg = "Failed to generate API key. Please try again.";
        $newKey = '';
    }
    $stmt->close();
}

$pageTitle = 'WAVSS - Generate API Key';
require_once($currentDir . 'templates/header.php');
?>

<div class="container my-5 flex-grow-1">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">API Key Management</h2>
        </div>
        <div class="card-body p-4">
          <?php if ($msg): ?>
            <div class="alert alert-<?php echo $newKey ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($msg); ?></div>
            <?php if ($newKey): ?>
              <div class="mb-4">
                <label class="form-label fw-bold">Your New API Key</label>
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" value="<?php echo htmlspecialchars($newKey); ?>" readonly onclick="this.select();">
                </div>
                <small class="text-danger mt-2 d-block">Make sure to copy your personal access token now. You won't be able to see it again!</small>
              </div>
            <?php endif; ?>
          <?php endif; ?>

          <h3 class="h5 mb-3">Generate a New API Key</h3>
          <p>API keys allow you to interact with the WAVSS REST API programmatically. Generating a new key will not invalidate your old ones, but you are responsible for keeping them secure.</p>
          
          <form method="post" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <button type="submit" name="generate" value="1" class="btn btn-primary">Generate New API Key</button>
          </form>
          
          <hr class="my-4">
          
          <h3 class="h5 mb-3">Your Active Keys</h3>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>Key Prefix</th>
                  <th>Created At</th>
                  <th>Last Used</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $stmt = $db->prepare("SELECT api_key, prefix, created_at, last_used_at FROM api_keys WHERE username = ? ORDER BY created_at DESC");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_object()) {
                        $prefix = htmlspecialchars($row->prefix) . '...';
                        $created = $row->created_at;
                        $lastUsed = $row->last_used_at ? $row->last_used_at : 'Never';
                        echo "<tr>";
                        echo "<td><code class='bg-light px-2 py-1 rounded'>$prefix</code></td>";
                        echo "<td>$created</td>";
                        echo "<td>$lastUsed</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center text-muted'>You have no active API keys.</td></tr>";
                }
                $stmt->close();
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once($currentDir . 'templates/footer.php'); ?>
