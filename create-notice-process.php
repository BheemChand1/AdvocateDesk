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
    // Validate required fields
    $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : null;
    $notice_date = isset($_POST['notice_date']) ? mysqli_real_escape_string($conn, trim($_POST['notice_date'])) : null;
    $section = isset($_POST['section']) ? mysqli_real_escape_string($conn, trim($_POST['section'])) : null;
    $act = isset($_POST['act']) ? mysqli_real_escape_string($conn, trim($_POST['act'])) : null;
    $post_date = isset($_POST['post_date']) && !empty($_POST['post_date']) ? mysqli_real_escape_string($conn, trim($_POST['post_date'])) : date('Y-m-d');
    $days_reminder = isset($_POST['days_reminder']) ? intval($_POST['days_reminder']) : 15;
    $input_data = isset($_POST['input_data']) ? mysqli_real_escape_string($conn, trim($_POST['input_data'])) : '';
    $addressee = isset($_POST['addressee']) ? mysqli_real_escape_string($conn, trim($_POST['addressee'])) : null;

    // Validation
    if (!$client_id || !$notice_date || !$section || !$act || !$addressee) {
        header("Location: create-notice.php?error=" . urlencode("All required fields must be filled"));
        exit();
    }

    // Verify client exists
    $client_check = mysqli_query($conn, "SELECT client_id FROM clients WHERE client_id = $client_id");
    if (!$client_check || mysqli_num_rows($client_check) === 0) {
        header("Location: create-notice.php?error=" . urlencode("Selected client does not exist"));
        exit();
    }

    // Calculate due date (post_date + days_reminder)
    $due_date = date('Y-m-d', strtotime($post_date . ' + ' . $days_reminder . ' days'));

    // Generate unique notice ID: NOTICE-YYYYMMDD-XXXXX
    $date_part = date('Ymd');
    $random_part = strtoupper(substr(uniqid(), -5));
    $unique_notice_id = "NOTICE-$date_part-$random_part";

    // Insert notice
    $insert_query = "INSERT INTO notices (unique_notice_id, client_id, notice_date, section, act, post_date, days_reminder, due_date, input_data, addressee, status, created_by, created_at, updated_at) 
                     VALUES ('$unique_notice_id', $client_id, '$notice_date', '$section', '$act', '$post_date', $days_reminder, '$due_date', '$input_data', '$addressee', 'open', '{$_SESSION['user_username']}', NOW(), NOW())";

    if (mysqli_query($conn, $insert_query)) {
        $notice_id = mysqli_insert_id($conn);

        // Create initial remark
        $remark_query = "INSERT INTO notice_remarks (notice_id, remark, created_by, created_at) 
                        VALUES ($notice_id, 'Notice created', '{$_SESSION['user_username']}', NOW())";

        if (mysqli_query($conn, $remark_query)) {
            header("Location: view-notices.php?success=" . urlencode("Notice created successfully with ID: $unique_notice_id"));
            exit();
        } else {
            // Delete the notice if remark insertion fails
            mysqli_query($conn, "DELETE FROM notices WHERE id = $notice_id");
            header("Location: create-notice.php?error=" . urlencode("Error creating notice: " . mysqli_error($conn)));
            exit();
        }
    } else {
        header("Location: create-notice.php?error=" . urlencode("Error creating notice: " . mysqli_error($conn)));
        exit();
    }
} else {
    // If not POST request, redirect back
    header("Location: create-notice.php");
    exit();
}
?>
