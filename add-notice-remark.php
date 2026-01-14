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
$remark = isset($_POST['remark']) ? mysqli_real_escape_string($conn, trim($_POST['remark'])) : null;

if (!$notice_id || !$remark) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Verify notice exists
$notice_check = mysqli_query($conn, "SELECT id FROM notices WHERE id = $notice_id");
if (!$notice_check || mysqli_num_rows($notice_check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Notice not found']);
    exit();
}

// Insert remark
$insert_query = "INSERT INTO notice_remarks (notice_id, remark, created_by, created_at) 
                 VALUES ($notice_id, '$remark', '{$_SESSION['user_username']}', NOW())";

if (mysqli_query($conn, $insert_query)) {
    echo json_encode(['success' => true, 'message' => 'Remark added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding remark: ' . mysqli_error($conn)]);
}
?>
