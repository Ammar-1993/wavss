<?php
$db = new mysqli('db', 'root', '', 'wavssv3_db');

if ($db->connect_error) {
    die("فشل الاتصال: " . $db->connect_error);
}
echo "تم الاتصال بنجاح!";
?>
