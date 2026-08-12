<?php
// tests/E2E/target.php
// A dummy vulnerable script used ONLY for E2E testing of the WAVSS scanner.
$db = new mysqli('db', 'root', '12345678', 'wavssv3_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

if (isset($_GET['username'])) {
    $username = $_GET['username'];
    $res = $db->query("SELECT * FROM users WHERE username = '$username'") or die($db->error);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo "Found user: " . $row['username'];
    }
}
?>
<form action="target.php" method="GET">
    <input type="text" name="username" />
    <input type="submit" value="Submit" />
</form>
