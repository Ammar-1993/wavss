<?php
session_start();
require_once(__DIR__ . '/csrf.php');
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');

// Registration Logic
$displayForm = true;

if (isset($_SESSION['username'])) {
  $message = 'You are currently logged in. You must be logged out to create an account';
  $displayForm = false;
} else {
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
      die('CSRF token validation failed');
    }
    // Assign POST values
    $username = isset($_POST['regusername']) ? $_POST['regusername'] : '';
    $password = isset($_POST['regpassword']) ? $_POST['regpassword'] : '';
    $confirmPassword = isset($_POST['regpassword2']) ? $_POST['regpassword2'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';

    // Validate input fields
    if (empty($username) || empty($password) || empty($confirmPassword) || empty($email)) {
      $message = 'All input fields must be filled in.';
    } elseif (strlen($password) < 8) {
      $message = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
      $message = 'Password must contain both letters and numbers.';
    } elseif ($password != $confirmPassword) {
      $message = 'Password confirmation does not match.';
    } elseif (!ctype_alnum($username) || !ctype_alnum($password)) {
      $message = 'Username and password must be alphanumeric.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $message = 'Invalid email address.';
    } else {
      if (connectToDb($db)) {
        // Check if the username exists
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
          $message = 'Username already exists. Please choose a different username.';
        } else {
          // Check if the email exists
          $query = "SELECT * FROM users WHERE email = ?";
          $stmt->prepare($query);
          $stmt->bind_param('s', $email);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows > 0) {
            $message = 'An account with this email already exists. Please try another email.';
          } else {
            // Insert new user into database
            $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
            $query = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
            $stmt->prepare($query);
            $stmt->bind_param('sss', $username, $hashedPassword, $email);

            if ($stmt->execute()) {
              $message = 'Congratulations! You have successfully registered. Please log in to enjoy our features.';
              $displayForm = false;
            } else {
              $message = 'There was a problem with registration. Please try again later.';
            }
          }
        }
        $stmt->close();
        $db->close();
      } else {
        $message = 'Database connection error. Please contact the administrator.';
      }
    }
  }
}
$pageTitle = 'WAVSS - Register';
require_once($currentDir . 'templates/header.php');
?>
  <!--SubPage Toprow Begin-->
  <div id="toprowsub">
    <div class="center">
      <h2>Sign Up</h2>
    </div>
  </div>
  <!--Toprow END-->
  <!--SubPage MiddleRow Begin-->
  <div id="midrow">
    <div class="center">
      <div class="textbox2">
        <?php
        if (isset($message)) {
          echo "<p>$message</p>";
        }

        if ($displayForm) {
          require_once('register_form.html'); // Display the form
        }
        ?>
      </div>
    </div>
  </div>
  <!--MiddleRow END-->

<?php require_once($currentDir . 'templates/footer.php'); ?>
