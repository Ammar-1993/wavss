<?php

session_start();
require_once(__DIR__ . '/csrf.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');
$pageTitle = 'WAVSS - Scan History';
require_once($currentDir . 'templates/header.php');
?>
<div class="container my-5 flex-grow-1">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h2 class="h5 mb-0">Scan History</h2>
    </div>
    <div class="card-body p-4 table-responsive">
      <?php require_once($currentDir . 'scanner/scan_history.php'); ?>
    </div>
  </div>
</div>

<?php require_once($currentDir . 'templates/footer.php'); ?>