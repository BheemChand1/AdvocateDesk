<?php
// Generate password hash for 'admin123'
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br><br>";

echo "Copy this SQL and run it in phpMyAdmin:<br><br>";
echo "INSERT INTO admin_users (full_name, username, password, role, status) <br>";
echo "VALUES ('Administrator', 'admin', '" . $hash . "', 'admin', 'active');";
?>
