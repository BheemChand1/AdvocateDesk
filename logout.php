<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, "/");
}

// Redirect to login page
header("Location: login.php?success=Logged out successfully");
exit();
?>
