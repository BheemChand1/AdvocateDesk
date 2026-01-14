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

$notice_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$notice_id) {
    echo json_encode(['success' => false, 'message' => 'Notice ID not provided']);
    exit();
}

// Fetch notice details
$query = "SELECT n.*, c.name as client_name 
          FROM notices n
          LEFT JOIN clients c ON n.client_id = c.client_id
          WHERE n.id = $notice_id";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Notice not found']);
    exit();
}

$notice = mysqli_fetch_assoc($result);

// Fetch remarks history
$remarks_query = "SELECT remark, created_at, created_by 
                 FROM notice_remarks 
                 WHERE notice_id = $notice_id 
                 ORDER BY created_at DESC";

$remarks_result = mysqli_query($conn, $remarks_query);
$remarks = [];

while ($remark = mysqli_fetch_assoc($remarks_result)) {
    $remarks[] = $remark;
}

// Return JSON response
echo json_encode([
    'success' => true,
    'notice' => $notice,
    'remarks' => $remarks
]);
?>
