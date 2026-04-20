<?php
// Session Token Validation Helper
// Include this at the top of protected pages after session_start()

function validate_session_token($conn) {
    // Check if user is logged in
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        return false;
    }
    
    // Check if session token exists
    if (!isset($_SESSION['session_token']) || empty($_SESSION['session_token'])) {
        return false;
    }
    
    // Verify session token against database
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    if ($user_id <= 0) {
        return false;
    }
    
    $verify_sql = "SELECT session_token FROM admin_users WHERE id = ? AND role = 'user' LIMIT 1";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "i", $user_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);
    $user_record = mysqli_fetch_assoc($verify_result);
    
    // If session_token is NULL in database, it means user was logged out from all devices
    if (!$user_record || $user_record['session_token'] === null) {
        // Invalidate session
        session_destroy();
        header("Location: login.php?error=Your session has been invalidated. Please login again.");
        exit();
    }
    
    // Verify token matches
    if ($user_record['session_token'] !== $_SESSION['session_token']) {
        // Token mismatch - user logged in from another device
        session_destroy();
        header("Location: login.php?error=Your session has been invalidated from another location.");
        exit();
    }
    
    return true;
}
?>
