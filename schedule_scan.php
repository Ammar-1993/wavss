<?php
session_start();
require_once(__DIR__ . '/csrf.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $url = trim($_POST['url']);
        $frequency = (int)$_POST['frequency_hours'];
        
        if (!empty($url) && in_array($frequency, [24, 168])) {
            $parsedHost = parse_url($url, PHP_URL_HOST) ?: parse_url("http://" . $url, PHP_URL_HOST);
            $isLocal = in_array(strtolower($parsedHost), ['localhost', '127.0.0.1', '::1', '[::1]', 'dvwa']);
            
            if (!$isLocal) {
                // Verify ownership explicitly via DB
                $verifyQuery = "SELECT id FROM domain_verifications WHERE username = ? AND domain = ? AND verified = 1";
                $verifyStmt = $db->prepare($verifyQuery);
                $verifyStmt->bind_param('ss', $username, $parsedHost);
                $verifyStmt->execute();
                if ($verifyStmt->get_result()->num_rows == 0) {
                    $msg = "Error: You must prove ownership of the domain " . htmlspecialchars($parsedHost) . " before scheduling a scan for it.";
                    $msgType = 'danger';
                }
                $verifyStmt->close();
            }
            
            if (empty($msg)) {
                $stmt = $db->prepare("INSERT INTO scheduled_scans (username, url, frequency_hours, next_run_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))");
                $stmt->bind_param("ssii", $username, $url, $frequency, $frequency);
                if ($stmt->execute()) {
                    $msg = "Scheduled scan created successfully.";
                    $msgType = 'success';
                } else {
                    $msg = "Failed to create scheduled scan.";
                    $msgType = 'danger';
                }
                $stmt->close();
            }
        } else {
            $msg = "Invalid input parameters.";
            $msgType = 'danger';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'toggle') {
        $scanId = (int)$_POST['scan_id'];
        $active = (int)$_POST['active_status'];
        $stmt = $db->prepare("UPDATE scheduled_scans SET active = ? WHERE id = ? AND username = ?");
        $stmt->bind_param("iis", $active, $scanId, $username);
        $stmt->execute();
        $stmt->close();
        $msg = "Scan status updated.";
        $msgType = 'success';
    }
}

$pageTitle = 'WAVSS - Scheduled Scans';
require_once($currentDir . 'templates/header.php');
?>

<div class="container my-5 flex-grow-1">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Create a Scheduled Scan</h2>
        </div>
        <div class="card-body p-4">
          <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="create">
            <div class="row g-3">
              <div class="col-md-8">
                <label for="url" class="form-label">URL to Scan</label>
                <input type="text" class="form-control" name="url" id="url" placeholder="http://example.com" required>
              </div>
              <div class="col-md-4">
                <label for="frequency_hours" class="form-label">Frequency</label>
                <select name="frequency_hours" id="frequency_hours" class="form-select" required>
                  <option value="24">Daily (every 24h)</option>
                  <option value="168">Weekly (every 168h)</option>
                </select>
              </div>
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary">Schedule Scan</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
          <h2 class="h5 mb-0">Your Scheduled Scans</h2>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">URL</th>
                  <th>Frequency</th>
                  <th>Next Run</th>
                  <th>Status</th>
                  <th class="pe-4 text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $stmt = $db->prepare("SELECT id, url, frequency_hours, next_run_at, active FROM scheduled_scans WHERE username = ? ORDER BY created_at DESC");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_object()) {
                        echo "<tr>";
                        echo "<td class='ps-4'>" . htmlspecialchars($row->url) . "</td>";
                        echo "<td>Every " . $row->frequency_hours . "h</td>";
                        echo "<td>" . $row->next_run_at . "</td>";
                        echo "<td>";
                        if ($row->active) {
                            echo "<span class='badge bg-success'>Active</span>";
                        } else {
                            echo "<span class='badge bg-secondary'>Paused</span>";
                        }
                        echo "</td>";
                        echo "<td class='pe-4 text-end'>";
                        echo "<form method='post' class='d-inline'>";
                        echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars(csrf_token()) . "'>";
                        echo "<input type='hidden' name='action' value='toggle'>";
                        echo "<input type='hidden' name='scan_id' value='" . $row->id . "'>";
                        if ($row->active) {
                            echo "<input type='hidden' name='active_status' value='0'>";
                            echo "<button type='submit' class='btn btn-sm btn-outline-warning'>Pause</button>";
                        } else {
                            echo "<input type='hidden' name='active_status' value='1'>";
                            echo "<button type='submit' class='btn btn-sm btn-outline-success'>Resume</button>";
                        }
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center text-muted py-4'>No scheduled scans found.</td></tr>";
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
