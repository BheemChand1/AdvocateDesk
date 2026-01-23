<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'includes/connection.php';

// Get POST data
$case_id = isset($_POST['case_id']) ? intval($_POST['case_id']) : 0;
$priority_status = isset($_POST['priority_status']) ? intval($_POST['priority_status']) : 0;
$remark = isset($_POST['remark']) ? mysqli_real_escape_string($conn, $_POST['remark']) : '';

if ($case_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid case ID']);
    exit();
}

// Update case priority and remark
$query = "UPDATE cases 
          SET priority_status = $priority_status,
              remark = '$remark',
              updated_at = NOW()
          WHERE id = $case_id";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        'success' => true,
        'message' => 'Priority and remark updated successfully!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating case: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
