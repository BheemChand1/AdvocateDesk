<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once 'includes/connection.php';

// Set response header to JSON
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$case_id = isset($_POST['case_id']) ? intval($_POST['case_id']) : 0;
$update_date = isset($_POST['update_date']) ? trim($_POST['update_date']) : '';
$position = isset($_POST['position']) ? trim($_POST['position']) : '';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
$is_end = isset($_POST['is_end']) && $_POST['is_end'] == '1';

// Validate required fields
if (empty($case_id) || empty($update_date) || empty($position)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $update_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

// Verify case exists
$case_check_query = "SELECT id, status FROM cases WHERE id = ?";
$stmt = mysqli_prepare($conn, $case_check_query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Case not found']);
    exit();
}

$case_data = mysqli_fetch_assoc($result);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get user ID from session
    $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Insert case position update
    $insert_query = "INSERT INTO case_position_updates 
                    (case_id, update_date, position, remarks, is_end_position, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $insert_query);
    $is_end_int = $is_end ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "isssii", $case_id, $update_date, $position, $remarks, $is_end_int, $created_by);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to insert position update');
    }
    
    // If this is an end position, update the case status to inactive/closed
    if ($is_end) {
        $update_case_query = "UPDATE cases SET status = 'closed', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_case_query);
        mysqli_stmt_bind_param($stmt, "i", $case_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update case status');
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    $message = $is_end 
        ? 'Case position updated and case marked as closed successfully!' 
        : 'Case position updated successfully!';
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'is_end' => $is_end
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
