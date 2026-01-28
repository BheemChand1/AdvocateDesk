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
$position = isset($_POST['position']) ? mysqli_real_escape_string($conn, $_POST['position']) : '';
$fee_amount = isset($_POST['fee_amount']) ? floatval($_POST['fee_amount']) : 0;

if ($case_id == 0 || empty($position)) {
    echo json_encode(['exists' => false]);
    exit();
}

// If fee_amount is 0, allow duplicates (don't check for existing)
if ($fee_amount == 0) {
    echo json_encode([
        'exists' => false,
        'count' => 0,
        'reason' => 'Zero fee stages can be duplicated'
    ]);
    exit();
}

// Check if this stage/position already exists for this case (only for non-zero fees)
$query = "SELECT COUNT(*) as count FROM case_position_updates 
          WHERE case_id = $case_id AND position = '$position'";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

// Return whether the stage exists
echo json_encode([
    'exists' => $row['count'] > 0,
    'count' => $row['count']
]);

mysqli_close($conn);
?>
