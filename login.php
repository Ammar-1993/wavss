<?php

session_start();
require_once(__DIR__ . '/csrf.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');
$pageTitle = 'WAVSS - Login';
require_once($currentDir . 'templates/header.php');
?>
<div class="container my-5 flex-grow-1">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Welcome</h2>
        </div>
        <div class="card-body text-center p-4">
          <p class="lead mb-0 text-danger"><?php
            if (isset($_SESSION['login_error'])) {
                echo htmlspecialchars($_SESSION['login_error']);
                unset($_SESSION['login_error']);
            } elseif (isset($loginMsg)) {
                echo htmlspecialchars($loginMsg);
            }
          ?></p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once($currentDir . 'templates/footer.php'); ?>
