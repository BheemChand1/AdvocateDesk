<?php
session_start();
require_once 'includes/connection.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data and sanitize
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=Please fill in all fields");
        exit();
    }
    
    // Query to check user (only role='user')
    $sql = "SELECT * FROM admin_users WHERE username = ? AND role = 'user' AND status = 'active' LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_logged_in'] = true;
            
            // Update last login
            $update_sql = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
            mysqli_stmt_execute($update_stmt);
            
            // Handle Remember Me
            if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
                // Set cookie for 30 days
                $cookie_value = base64_encode($user['id'] . ':' . $user['username']);
                setcookie('remember_user', $cookie_value, time() + (86400 * 30), "/"); // 30 days
            } else {
                // Clear cookie if remember me is not checked
                if (isset($_COOKIE['remember_user'])) {
                    setcookie('remember_user', '', time() - 3600, "/");
                }
            }
            
            // Redirect to user dashboard
            header("Location: index.php");
            exit();
            
        } else {
            header("Location: login.php?error=Invalid username or password");
            exit();
        }
    } else {
        header("Location: login.php?error=Invalid username or password");
        exit();
    }
    
} else {
    // If not POST request, redirect to login page
    header("Location: login.php");
    exit();
}
?>
