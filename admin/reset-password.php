<?php
require_once '../includes/connection.php';

// First, let's check if the table exists and has data
echo "<h2>Database Connection Test</h2>";
echo "Connected to database: case_management<br><br>";

// Check if table exists
$check_table = "SHOW TABLES LIKE 'admin_users'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "✓ Table 'admin_users' exists<br><br>";
    
    // Get all users
    echo "<h3>Current Users in Database:</h3>";
    $sql = "SELECT id, full_name, username, role, status, created_at FROM admin_users";
    $users = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($users) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Full Name</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th></tr>";
        while ($row = mysqli_fetch_assoc($users)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['full_name'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "⚠ No users found in database!<br><br>";
    }
    
} else {
    echo "✗ Table 'admin_users' does not exist!<br><br>";
    echo "<a href='setup-database.php'>Click here to setup database</a>";
    exit;
}

// Now let's reset the admin password
echo "<hr><h3>Reset Admin Password</h3>";

$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

echo "New Password: <strong>admin123</strong><br>";
echo "Hashed: " . $hashed_password . "<br><br>";

// Check if admin user exists
$check_admin = "SELECT id FROM admin_users WHERE username = 'admin'";
$admin_result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($admin_result) > 0) {
    // Update existing admin
    $update_sql = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "s", $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; color: #155724;'>";
        echo "✓ Admin password has been reset successfully!<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong>";
        echo "</div><br>";
        echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
    } else {
        echo "Error updating password: " . mysqli_error($conn);
    }
} else {
    // Create new admin
    $insert_sql = "INSERT INTO admin_users (full_name, username, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())";
    $stmt = mysqli_prepare($conn, $insert_sql);
    $full_name = "Administrator";
    $username = "admin";
    mysqli_stmt_bind_param($stmt, "sss", $full_name, $username, $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; color: #155724;'>";
        echo "✓ Admin user created successfully!<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong>";
        echo "</div><br>";
        echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
    } else {
        echo "Error creating admin: " . mysqli_error($conn);
    }
}
?>
