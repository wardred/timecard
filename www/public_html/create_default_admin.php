<?php
// Used to create the default user using PHP's current password hashing
// After this is used it should probably be deleted from the public_html dir.

require 'header.php';
require 'functions.php';
$password=password_hash('whacky_676_Wabbit', PASSWORD_DEFAULT);
$insert_user="INSERT IGNORE INTO users(first_name,username,password,active)
              VALUES('admin','admin','$password',TRUE)";
$insert_role="INSERT IGNORE INTO role_to_user(user_id, role_id, main)
              VALUES((SELECT id FROM users WHERE username='admin'),
                     (SELECT id FROM roles WHERE name='Timecard Admin'),
                      TRUE)";

$stmt = $conn->prepare($insert_user);
$stmt->execute();

$stmt = $conn->prepare($insert_role);
$stmt->execute();
?>

<html><head><title>Insert Default Admin</title></head>
<body>This is used to insert the default admin user and password.</body></html>
