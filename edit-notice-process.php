<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $notice_id = isset($_POST['notice_id']) ? intval($_POST['notice_id']) : null;
    $notice_date = isset($_POST['notice_date']) ? mysqli_real_escape_string($conn, trim($_POST['notice_date'])) : null;
    $section = isset($_POST['section']) ? mysqli_real_escape_string($conn, trim($_POST['section'])) : null;
    $act = isset($_POST['act']) ? mysqli_real_escape_string($conn, trim($_POST['act'])) : null;
    $post_date = isset($_POST['post_date']) && !empty($_POST['post_date']) ? mysqli_real_escape_string($conn, trim($_POST['post_date'])) : null;
    $days_reminder = isset($_POST['days_reminder']) ? intval($_POST['days_reminder']) : 15;
    $input_data = isset($_POST['input_data']) ? mysqli_real_escape_string($conn, trim($_POST['input_data'])) : '';
    $addressee = isset($_POST['addressee']) ? mysqli_real_escape_string($conn, trim($_POST['addressee'])) : null;

    // Validation
    if (!$notice_id || !$notice_date || !$section || !$act || !$days_reminder || !$addressee) {
        header("Location: edit-notice.php?id=$notice_id&error=" . urlencode("All required fields must be filled"));
        exit();
    }

    // Verify notice exists
    $notice_check = mysqli_query($conn, "SELECT id, post_date FROM notices WHERE id = $notice_id");
    if (!$notice_check || mysqli_num_rows($notice_check) === 0) {
        header("Location: view-notices.php?error=" . urlencode("Notice not found"));
        exit();
    }

    $notice_row = mysqli_fetch_assoc($notice_check);
    
    // If post_date is not provided, use the existing one
    if (!$post_date) {
        $post_date = $notice_row['post_date'];
    }

    // Calculate due date (post_date + days_reminder)
    $due_date = date('Y-m-d', strtotime($post_date . ' + ' . $days_reminder . ' days'));

    // Update notice
    $update_query = "UPDATE notices 
                     SET notice_date = '$notice_date', 
                         section = '$section', 
                         act = '$act', 
                         post_date = '$post_date', 
                         days_reminder = $days_reminder, 
                         due_date = '$due_date', 
                         input_data = '$input_data', 
                         addressee = '$addressee', 
                         updated_at = NOW() 
                     WHERE id = $notice_id";

    if (mysqli_query($conn, $update_query)) {
        header("Location: view-notices.php?success=" . urlencode("Notice updated successfully"));
        exit();
    } else {
        header("Location: edit-notice.php?id=$notice_id&error=" . urlencode("Error updating notice: " . mysqli_error($conn)));
        exit();
    }
} else {
    // If not POST request, redirect back
    header("Location: view-notices.php");
    exit();
}
?>
