<?php

session_start();
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');
?>
<!DOCTYPE html>

<head>
  <title>WAVSS</title>
  <meta charset="UTF-8">
  <link rel="shortcut icon" href="images/favicon.gif" />
  <link rel="stylesheet" type="text/css" href="style.css" />
  <link rel="stylesheet" type="text/css" href="custom.css" />
  <script type="text/javascript" src="jquery-1.6.4.js"></script>
  <!-- <script type="text/javascript" src="js/swfobject/swfobject.js"></script> -->

</head>

<body>
  <!--Header Begin-->
  <div id="header">
    <div class="center">
      <div id="logo"><a href="#">WAVSS</a></div>
      <!--Menu Begin-->
      <div id="menu">
        <?php require_once($currentDir . 'session_control.php'); ?>
      </div>
      <div id="menu">
        <ul>
          <li><a class="active" href="index.php"><span>Home</span></a></li>
          <li><a href="about.php"><span>About</span></a></li>
          <li><a href="crawler.php"><span>Crawler</span></a></li>
          <li><a href="scanner.php"><span>Scanner</span></a></li>
          <li><a href="history.php"><span>Scan History</span></a></li>
        </ul>
      </div>
      <!--Menu END-->
    </div>
  </div>
  <!--Header END-->
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
  <!--Footer Begin-->
  <div id="footer">
    <div class="foot"> <span>Welcome</span> for <a href="#">WAVSS</a> Web Application Vulnerability Scanner System <a href="#">2025</a> </div>
  </div>
  <!--Footer END-->
</body>

</html>