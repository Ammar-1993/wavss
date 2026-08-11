<?php

session_start();
require_once(__DIR__ . '/csrf.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');
$pageTitle = 'WAVSS - Home';
require_once($currentDir . 'templates/header.php');
?>
  <!--Toprow Begin-->
  <div id="toprow">
    <div class="center">
      <div id="cubershadow">
        <!-- <div id="cu3er-container"> <a href="#"> <img src="images/logicon.png" alt="" /> </a> </div> -->
      </div>
    </div>
  </div>
  <!--Toprow END-->

  <!--BottomRow Begin-->
  <div id="bottomrow">
    <div class="textbox">
      <h1>WAVSS - The Web Application Vulnerability Scanner System</h1>
      <div class="about-section">
        <p>WAVSS firstly crawls the target website to identify all URLs belonging to the website. It scans each URL for a number of vulnerabilities and emails you a detailed PDF report once the scan is complete.</p>
      </div>
    </div>
  </div>
  <!--BottomRow END-->
<?php require_once($currentDir . 'templates/footer.php'); ?>