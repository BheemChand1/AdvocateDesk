<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'includes/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$notice_id = isset($_POST['notice_id']) ? intval($_POST['notice_id']) : null;
$close_remark = isset($_POST['close_remark']) ? mysqli_real_escape_string($conn, trim($_POST['close_remark'])) : null;

if (!$notice_id || !$close_remark) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Verify notice exists and is open
$notice_check = mysqli_query($conn, "SELECT id, status FROM notices WHERE id = $notice_id AND status = 'open'");
if (!$notice_check || mysqli_num_rows($notice_check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Notice not found or already closed']);
    exit();
}

// Add closing remark
$remark_query = "INSERT INTO notice_remarks (notice_id, remark, created_by, created_at) 
                 VALUES ($notice_id, '$close_remark', '{$_SESSION['user_username']}', NOW())";

if (!mysqli_query($conn, $remark_query)) {
    echo json_encode(['success' => false, 'message' => 'Error adding closing remark: ' . mysqli_error($conn)]);
    exit();
}

// Update notice to closed status
$update_query = "UPDATE notices SET status = 'closed', closed_date = NOW(), updated_at = NOW() WHERE id = $notice_id";

if (mysqli_query($conn, $update_query)) {
    echo json_encode(['success' => true, 'message' => 'Notice closed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error closing notice: ' . mysqli_error($conn)]);
}
?>
