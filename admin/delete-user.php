<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../includes/connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid user ID");
    exit();
}

$user_id = intval($_GET['id']);

// Prevent deleting administrator account
if ($user_id == $_SESSION['admin_id']) {
    header("Location: index.php?error=Cannot delete administrator account");
    exit();
}

// Delete user
$delete_sql = "DELETE FROM admin_users WHERE id = ? AND id != ?";
$stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $_SESSION['admin_id']);

if (mysqli_stmt_execute($stmt)) {
    header("Location: index.php?success=User deleted successfully");
    exit();
} else {
    header("Location: index.php?error=Error deleting user");
    exit();
}
?>
