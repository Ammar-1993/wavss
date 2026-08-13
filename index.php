<?php

session_start();
require_once(__DIR__ . '/csrf.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');
$pageTitle = 'WAVSS - Home';
require_once($currentDir . 'templates/header.php');
?>
  <div class="container my-5 flex-grow-1">
    <div class="row justify-content-center">
      <div class="col-md-10 text-center">
        <div class="mb-5">
          <!-- <img src="images/logicon.png" alt="" class="img-fluid" /> -->
        </div>
        <div class="card shadow-sm">
          <div class="card-body p-5">
            <h1 class="display-5 fw-bold mb-4">WAVSS - The Web Application Vulnerability Scanner System</h1>
            <p class="lead text-muted">
              WAVSS firstly crawls the target website to identify all URLs belonging to the website. It scans each URL for a number of vulnerabilities and emails you a detailed PDF report once the scan is complete.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php require_once($currentDir . 'templates/footer.php'); ?>