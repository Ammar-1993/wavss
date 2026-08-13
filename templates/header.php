<!DOCTYPE html>
<html lang="en">
<head>
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'WAVSS'; ?></title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="images/favicon.gif" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body class="d-flex flex-column min-vh-100">
  <!--Header Begin-->
  <nav id="header" class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
      <a id="logo" class="navbar-brand" href="index.php">WAVSS</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
          <li class="nav-item">
            <a class="nav-link <?php if($currentPage == 'index.php') echo 'active'; ?>" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if($currentPage == 'about.php') echo 'active'; ?>" href="about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if($currentPage == 'crawler.php') echo 'active'; ?>" href="crawler.php">Crawler</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if($currentPage == 'scanner.php') echo 'active'; ?>" href="scanner.php">Scanner</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if($currentPage == 'history.php') echo 'active'; ?>" href="history.php">Scan History</a>
          </li>
        </ul>
        <div class="d-flex align-items-center">
          <?php require_once($currentDir . 'session_control.php'); ?>
        </div>
      </div>
    </div>
  </nav>
  <!--Header END-->
