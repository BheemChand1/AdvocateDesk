<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once 'includes/connection.php';

// Ensure case_position_updates table has fee_amount and payment_status columns
$check_column_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_NAME='case_position_updates' AND COLUMN_NAME='fee_amount'";
$result = mysqli_query($conn, $check_column_query);

if (mysqli_num_rows($result) === 0) {
    // Add columns if they don't exist
    mysqli_query($conn, "ALTER TABLE case_position_updates ADD COLUMN fee_amount DECIMAL(10,2) DEFAULT 0");
    mysqli_query($conn, "ALTER TABLE case_position_updates ADD COLUMN payment_status ENUM('pending', 'processing', 'completed') DEFAULT 'pending'");
}

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
$fee_amount = isset($_POST['fee_amount']) ? floatval($_POST['fee_amount']) : 0;
$payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : '';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
$is_end = isset($_POST['is_end']) && $_POST['is_end'] == '1';

// Validate required fields
if (empty($case_id) || empty($update_date) || empty($position) || empty($payment_status)) {
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
    
    // Get the fee grid ID for this position (fee_name)
    $fee_id_query = "SELECT id FROM case_fee_grid WHERE case_id = ? AND fee_name = ?";
    $stmt = mysqli_prepare($conn, $fee_id_query);
    mysqli_stmt_bind_param($stmt, "is", $case_id, $position);
    mysqli_stmt_execute($stmt);
    $fee_result = mysqli_stmt_get_result($stmt);
    $fee_data = mysqli_fetch_assoc($fee_result);
    $fee_id = $fee_data ? $fee_data['id'] : null;
    
    // Insert case position update with fee details and payment status
    $insert_query = "INSERT INTO case_position_updates 
                    (case_id, update_date, position, fee_amount, payment_status, remarks, is_end_position, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $insert_query);
    $is_end_int = $is_end ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "issdssii", $case_id, $update_date, $position, $fee_amount, $payment_status, $remarks, $is_end_int, $created_by);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to insert position update: ' . mysqli_error($conn));
    }
    
    // Update the case_stage_id in cases table based on the fee grid entry
    if ($fee_id) {
        $update_case_stage_query = "UPDATE cases SET case_stage_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_case_stage_query);
        mysqli_stmt_bind_param($stmt, "ii", $fee_id, $case_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update case stage');
        }
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
