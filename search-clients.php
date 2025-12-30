<?php
session_start();
require_once 'includes/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get search query
$searchQuery = $_GET['q'] ?? '';

if (empty($searchQuery)) {
    echo json_encode([]);
    exit();
}

// Search clients by name, email, or mobile
$searchTerm = "%{$searchQuery}%";
$stmt = mysqli_prepare($conn, "
    SELECT client_id, name, email, mobile, father_name, address 
    FROM clients 
    WHERE name LIKE ? 
       OR email LIKE ? 
       OR mobile LIKE ?
       OR father_name LIKE ?
    ORDER BY name ASC 
    LIMIT 10
");

mysqli_stmt_bind_param($stmt, "ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$clients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $clients[] = $row;
}

mysqli_stmt_close($stmt);

header('Content-Type: application/json');
echo json_encode($clients);
?>
