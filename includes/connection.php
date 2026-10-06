<?php
// Database configuration
$host = 'localhost';        // Database host (usually 'localhost' for XAMPP)
$username = 'mpslegal';         // Database username (default 'root' for XAMPP)
$password = '~xwOCXT%WGr;';             // Database password (default empty for XAMPP)
$database = 'mpslegaldb';   // Database name

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8 for proper character encoding
mysqli_set_charset($conn, "utf8");

// Optional: Uncomment below to see success message
// echo "Database connected successfully!";
