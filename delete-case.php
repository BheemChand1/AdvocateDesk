<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Get case ID
$case_id = isset($_POST['case_id']) ? intval($_POST['case_id']) : 0;

if ($case_id <= 0) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid case ID']));
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get case type first
    $case_query = "SELECT case_type FROM cases WHERE id = ?";
    $case_stmt = mysqli_prepare($conn, $case_query);
    mysqli_stmt_bind_param($case_stmt, "i", $case_id);
    mysqli_stmt_execute($case_stmt);
    $case_result = mysqli_stmt_get_result($case_stmt);
    
    if (!$case_result || mysqli_num_rows($case_result) === 0) {
        throw new Exception("Case not found");
    }
    
    $case_row = mysqli_fetch_assoc($case_result);
    $case_type = $case_row['case_type'];
    
    // Delete from case_fee_grid
    $delete_fees = "DELETE FROM case_fee_grid WHERE case_id = ?";
    $fees_stmt = mysqli_prepare($conn, $delete_fees);
    mysqli_stmt_bind_param($fees_stmt, "i", $case_id);
    if (!mysqli_stmt_execute($fees_stmt)) {
        throw new Exception("Failed to delete case fees");
    }
    
    // Delete from case_position_updates (position history)
    $delete_positions = "DELETE FROM case_position_updates WHERE case_id = ?";
    $positions_stmt = mysqli_prepare($conn, $delete_positions);
    mysqli_stmt_bind_param($positions_stmt, "i", $case_id);
    if (!mysqli_stmt_execute($positions_stmt)) {
        throw new Exception("Failed to delete case positions");
    }
    
    // Delete from case type specific table
    $details_table = 'case_' . strtolower($case_type) . '_details';
    $delete_details = "DELETE FROM " . mysqli_real_escape_string($conn, $details_table) . " WHERE case_id = ?";
    $details_stmt = mysqli_prepare($conn, $delete_details);
    if ($details_stmt) {
        mysqli_stmt_bind_param($details_stmt, "i", $case_id);
        if (!mysqli_stmt_execute($details_stmt)) {
            throw new Exception("Failed to delete case details");
        }
    }
    
    // Delete from cases table
    $delete_case = "DELETE FROM cases WHERE id = ?";
    $case_delete_stmt = mysqli_prepare($conn, $delete_case);
    mysqli_stmt_bind_param($case_delete_stmt, "i", $case_id);
    if (!mysqli_stmt_execute($case_delete_stmt)) {
        throw new Exception("Failed to delete case");
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Case deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>
