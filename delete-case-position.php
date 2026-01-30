<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

require_once 'includes/connection.php';

// Get POST data
$position_id = isset($_POST['position_id']) ? intval($_POST['position_id']) : 0;
$case_id = isset($_POST['case_id']) ? intval($_POST['case_id']) : 0;

if (!$position_id || !$case_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Verify that the position belongs to the case
$verify_query = "SELECT id FROM case_position_updates WHERE id = ? AND case_id = ?";
$stmt = mysqli_prepare($conn, $verify_query);
mysqli_stmt_bind_param($stmt, "ii", $position_id, $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Position not found or does not belong to this case']);
    exit();
}

// Delete the position update
$delete_query = "DELETE FROM case_position_updates WHERE id = ?";
$stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($stmt, "i", $position_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Stage deleted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting stage: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
