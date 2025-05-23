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
          <li><a href="index.php"><span>Home</span></a></li>
          <li><a class="active" href="about.php"><span>About</span></a></li>
          <li><a href="crawler.php"><span>Crawler</span></a></li>
          <li><a href="scanner.php"><span>Scanner</span></a></li>
          <li><a href="history.php"><span>Scan History</span></a></li>
        </ul>
      </div>
      <!--Menu END-->
    </div>
  </div>
  <!--Header END-->

  <!--SubPage Toprow Begin-->
  <div id="toprowsub">
    <div class="center">
      <h2>About</h2>
    </div>
  </div>
  <!--Toprow END-->

  <!--SubPage MiddleRow Begin-->
  <div id="midrow">
  <div class="center">
    <div class="about-content-wrapper">
      
      <section class="about-section card">
        <h3>Project Background</h3>
        <p>
            This site has been developed by the <strong>Web Application Vulnerability Scanner Group</strong> as a final project for the Cyber Security major at Bisha University's College of Computers and Information Technology.
        </p>
        <p>
            Supervised by: <em>Dr. Muhammad Ayub Muhammad Khan</em>.
        </p>
      </section>

      <hr class="section-divider"> 
      
      <section class="about-section card">
        <h3>Purpose & Goals</h3>
        <p>
            The Web Application Vulnerability Scanner (WAVSS) seeks to address the challenge of securing modern web applications by providing an <strong>automated solution to detect common web vulnerabilities</strong>.
        </p>
        <p>
            Manual security testing can be time-consuming and prone to human error. WAVSS aims to overcome this by efficiently scanning websites, testing for a wide range of flaws, and generating detailed reports. This empowers website owners to understand risks and remediate vulnerabilities effectively.
        </p>
      </section>

      <hr class="section-divider"> 

      <section class="about-section card">
        <h3>Research Contribution</h3>
        <p>
            Our research aims to bridge the gap between the increasing complexity of web applications and the need for <strong>simplified yet effective vulnerability scanning tools</strong>.
        </p>
        <p>
            By developing a robust and user-friendly system, this project contributes to the web security field by providing a valuable tool for developers, company managers, and security professionals to proactively manage and mitigate web application vulnerabilities.
        </p>
      </section>

    </div>
  </div>
</div>
  <!--MiddleRow END-->

  <!--Footer Begin-->
  <div id="footer">
    <div class="foot"> <span>Welcome</span> for <a href="#">WAVSS</a> Web Application Vulnerability Scanner System <a href="#">2025</a> </div>
  </div>
  <!--Footer END-->
</body>

</html>