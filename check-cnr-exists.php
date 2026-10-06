<?php
session_start();
require_once 'includes/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Get CNR number from request
$cnr_number = isset($_GET['cnr']) ? trim($_GET['cnr']) : '';

if (empty($cnr_number)) {
    echo json_encode(['exists' => false]);
    exit();
}

// Check if CNR already exists in the database
$stmt = mysqli_prepare($conn, "SELECT id, unique_case_id FROM cases WHERE cnr_number = ?");
mysqli_stmt_bind_param($stmt, "s", $cnr_number);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'exists' => true,
        'case_id' => $row['id'],
        'unique_case_id' => $row['unique_case_id'],
        'message' => 'This CNR number already exists in the system.'
    ]);
} else {
    echo json_encode(['exists' => false]);
}

mysqli_stmt_close($stmt);
?>
