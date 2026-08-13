<!DOCTYPE html>
<head>
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'WAVSS'; ?></title>
  <meta charset="UTF-8">
  <link rel="shortcut icon" href="images/favicon.gif" />
  <link rel="stylesheet" type="text/css" href="style.css" />
  <link rel="stylesheet" type="text/css" href="custom.css" />
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
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <ul>
          <li><a <?php if($currentPage == 'index.php') echo 'class="active"'; ?> href="index.php"><span>Home</span></a></li>
          <li><a <?php if($currentPage == 'about.php') echo 'class="active"'; ?> href="about.php"><span>About</span></a></li>
          <li><a <?php if($currentPage == 'crawler.php') echo 'class="active"'; ?> href="crawler.php"><span>Crawler</span></a></li>
          <li><a <?php if($currentPage == 'scanner.php') echo 'class="active"'; ?> href="scanner.php"><span>Scanner</span></a></li>
          <li><a <?php if($currentPage == 'history.php') echo 'class="active"'; ?> href="history.php"><span>Scan History</span></a></li>
        </ul>
      </div>
      <!--Menu END-->
    </div>
  </div>
  <!--Header END-->
