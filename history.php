<?php

session_start();
require_once(__DIR__ . '/csrf.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');
$pageTitle = 'WAVSS - Scan History';
require_once($currentDir . 'templates/header.php');
?>
  <!--SubPage Toprow Begin-->
  <div id="toprowsub">
    <div class="center">
      <h2>Scan History</h2>
    </div>
  </div>
  <!--Toprow END-->
  <!--SubPage MiddleRow Begin-->
  <div id="midrow">
    <div class="center">
      <div class="textbox2">
        <p><?php require_once($currentDir . 'scanner/scan_history.php'); ?></p>
      </div>
    </div>
  </div>
  <!--MiddleRow END-->

<?php require_once($currentDir . 'templates/footer.php'); ?>