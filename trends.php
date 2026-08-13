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

if (!connectToDb($db)) {
    die("Database connection failed.");
}

// Fetch all unique URLs scanned by the user for the dropdown
$stmt = $db->prepare("SELECT DISTINCT url FROM tests WHERE username = ? AND type = 'scan' AND scan_finished = 1 ORDER BY url ASC");
$stmt->bind_param("s", $username);
$stmt->execute();
$urlRes = $stmt->get_result();
$scannedUrls = [];
while ($row = $urlRes->fetch_object()) {
    if (!empty($row->url)) {
        $scannedUrls[] = $row->url;
    }
}
$stmt->close();

$selectedUrl = '';
$trendsData = [];

if (isset($_GET['url']) && in_array($_GET['url'], $scannedUrls)) {
    $selectedUrl = $_GET['url'];
    
    // Fetch tests for this URL
    $stmt = $db->prepare("
        SELECT t.id, t.start_timestamp, t.url, 
               COUNT(r.id) AS total_vulns,
               SUM(CASE WHEN r.type = 'rxss' THEN 1 ELSE 0 END) AS rxss,
               SUM(CASE WHEN r.type = 'sxss' THEN 1 ELSE 0 END) AS sxss,
               SUM(CASE WHEN r.type = 'sqli' THEN 1 ELSE 0 END) AS sqli,
               SUM(CASE WHEN r.type = 'basqli' THEN 1 ELSE 0 END) AS basqli,
               SUM(CASE WHEN r.type = 'autoc' THEN 1 ELSE 0 END) AS autoc,
               SUM(CASE WHEN r.type = 'idor' THEN 1 ELSE 0 END) AS idor,
               SUM(CASE WHEN r.type = 'dirlist' THEN 1 ELSE 0 END) AS dirlist,
               SUM(CASE WHEN r.type = 'bannerdis' THEN 1 ELSE 0 END) AS bannerdis,
               SUM(CASE WHEN r.type = 'sslcert' THEN 1 ELSE 0 END) AS sslcert,
               SUM(CASE WHEN r.type = 'unredir' THEN 1 ELSE 0 END) AS unredir
        FROM tests t
        LEFT JOIN test_results r ON t.id = r.test_id
        WHERE t.username = ? AND t.url = ? AND t.type = 'scan' AND t.scan_finished = 1
        GROUP BY t.id, t.start_timestamp, t.url
        ORDER BY t.start_timestamp ASC
    ");
    $stmt->bind_param("ss", $username, $selectedUrl);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        // Format timestamp for display
        $row['date'] = date('Y-m-d H:i', $row['start_timestamp']);
        $trendsData[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'WAVSS - Vulnerability Trends';
require_once($currentDir . 'templates/header.php');
?>

<div class="container my-5 flex-grow-1">
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <h2 class="h5 mb-0">Vulnerability Trends</h2>
    </div>
    <div class="card-body p-4">
      <p>Select a URL you have previously scanned to view historical vulnerability trends.</p>
      
      <form method="get" class="row g-3 align-items-center">
        <div class="col-md-8">
          <label class="visually-hidden" for="urlSelect">URL</label>
          <select name="url" id="urlSelect" class="form-select" required>
            <option value="" disabled <?php echo empty($selectedUrl) ? 'selected' : ''; ?>>Choose a URL...</option>
            <?php foreach ($scannedUrls as $url): ?>
              <option value="<?php echo htmlspecialchars($url); ?>" <?php echo ($selectedUrl === $url) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($url); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary w-100">View Trends</button>
        </div>
      </form>
    </div>
  </div>

  <?php if (!empty($selectedUrl) && count($trendsData) > 0): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-secondary text-white">
        <h2 class="h5 mb-0">Trend Chart: <?php echo htmlspecialchars($selectedUrl); ?></h2>
      </div>
      <div class="card-body p-4">
        <canvas id="trendChart" style="max-height: 400px;"></canvas>
      </div>
    </div>
    
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <h2 class="h5 mb-0">Historical Scan Data</h2>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0 text-center align-middle">
            <thead class="table-dark">
              <tr>
                <th>Date</th>
                <th>Total Vulns</th>
                <th>XSS (R/S)</th>
                <th>SQLi (Std/BA)</th>
                <th>IDOR</th>
                <th>Misc</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($trendsData as $row): ?>
                <tr>
                  <td><?php echo $row['date']; ?></td>
                  <td class="fw-bold text-<?php echo $row['total_vulns'] > 0 ? 'danger' : 'success'; ?>">
                    <?php echo $row['total_vulns']; ?>
                  </td>
                  <td><?php echo $row['rxss'] + $row['sxss']; ?></td>
                  <td><?php echo $row['sqli'] + $row['basqli']; ?></td>
                  <td><?php echo $row['idor']; ?></td>
                  <td>
                    <?php 
                      echo ($row['autoc'] + $row['dirlist'] + $row['bannerdis'] + $row['sslcert'] + $row['unredir']); 
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Load Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const rawData = <?php echo json_encode($trendsData); ?>;
      
      const labels = rawData.map(r => r.date);
      const totalVulns = rawData.map(r => r.total_vulns);
      
      // Breakdown datasets
      const xssData = rawData.map(r => parseInt(r.rxss) + parseInt(r.sxss));
      const sqliData = rawData.map(r => parseInt(r.sqli) + parseInt(r.basqli));
      const idorData = rawData.map(r => parseInt(r.idor));
      const miscData = rawData.map(r => parseInt(r.autoc) + parseInt(r.dirlist) + parseInt(r.bannerdis) + parseInt(r.sslcert) + parseInt(r.unredir));

      const ctx = document.getElementById('trendChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Total Vulnerabilities',
              data: totalVulns,
              borderColor: 'rgba(220, 53, 69, 1)', // Bootstrap Danger
              backgroundColor: 'rgba(220, 53, 69, 0.1)',
              borderWidth: 3,
              fill: true,
              tension: 0.1
            },
            {
              label: 'XSS',
              data: xssData,
              borderColor: 'rgba(253, 126, 20, 1)', // Bootstrap Orange
              borderWidth: 2,
              borderDash: [5, 5],
              fill: false,
              tension: 0.1
            },
            {
              label: 'SQLi',
              data: sqliData,
              borderColor: 'rgba(111, 66, 193, 1)', // Bootstrap Purple
              borderWidth: 2,
              borderDash: [5, 5],
              fill: false,
              tension: 0.1
            },
            {
              label: 'IDOR & Misc',
              data: miscData.map((v, i) => v + idorData[i]),
              borderColor: 'rgba(13, 110, 253, 1)', // Bootstrap Primary
              borderWidth: 2,
              borderDash: [5, 5],
              fill: false,
              tension: 0.1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          },
          plugins: {
            legend: { position: 'top' },
            tooltip: {
              mode: 'index',
              intersect: false,
            }
          },
          interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
          }
        }
      });
    </script>
  <?php elseif (!empty($selectedUrl) && count($trendsData) === 0): ?>
    <div class="alert alert-warning">No finished scan data found for this URL.</div>
  <?php endif; ?>
</div>

<?php require_once($currentDir . 'templates/footer.php'); ?>
