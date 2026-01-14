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

if (!$notice_id) {
    echo json_encode(['success' => false, 'message' => 'Notice ID not provided']);
    exit();
}

// Verify notice exists
$notice_check = mysqli_query($conn, "SELECT id FROM notices WHERE id = $notice_id");
if (!$notice_check || mysqli_num_rows($notice_check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Notice not found']);
    exit();
}

// Delete remarks first (due to foreign key constraint)
$delete_remarks = "DELETE FROM notice_remarks WHERE notice_id = $notice_id";
if (!mysqli_query($conn, $delete_remarks)) {
    echo json_encode(['success' => false, 'message' => 'Error deleting remarks: ' . mysqli_error($conn)]);
    exit();
}

// Delete notice
$delete_query = "DELETE FROM notices WHERE id = $notice_id";

if (mysqli_query($conn, $delete_query)) {
    echo json_encode(['success' => true, 'message' => 'Notice deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting notice: ' . mysqli_error($conn)]);
}
?>
