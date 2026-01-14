<?php
require_once 'includes/connection.php';

// Check if addressee column exists
$check_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'notices' AND COLUMN_NAME = 'addressee'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) === 0) {
    // Column doesn't exist, add it
    $alter_query = "ALTER TABLE notices ADD COLUMN addressee TEXT AFTER input_data";
    
    if (mysqli_query($conn, $alter_query)) {
        echo "✓ Successfully added 'addressee' column to notices table";
    } else {
        echo "✗ Error adding column: " . mysqli_error($conn);
    }
} else {
    echo "✓ 'addressee' column already exists in notices table";
}
?>
