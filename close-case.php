<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'includes/connection.php';

// Get case ID from POST
if (!isset($_POST['case_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Case ID is required']);
    exit();
}

$case_id = intval($_POST['case_id']);

// Verify case exists
$verify_query = "SELECT id, unique_case_id FROM cases WHERE id = ?";
$stmt = mysqli_prepare($conn, $verify_query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Case not found']);
    exit();
}

$case = mysqli_fetch_assoc($result);

// Update case status to closed
$update_query = "UPDATE cases SET status = 'closed' WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "i", $case_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Case closed successfully',
        'case_id' => $case['unique_case_id']
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error closing case: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
