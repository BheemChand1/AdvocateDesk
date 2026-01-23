<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch clients
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE name LIKE ? OR email LIKE ? OR mobile LIKE ? OR address LIKE ? ORDER BY client_id DESC");
    $search_param = "%$search%";
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM clients ORDER BY client_id DESC");
}

$clients = [];
if ($result && $result->num_rows > 0) {
    while($client = $result->fetch_assoc()) {
        $clients[] = $client;
    }
}

// Generate Excel file using CSV format (can be opened in Excel)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Clients_List_' . date('d-m-Y_H-i-s') . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Set BOM for UTF-8 to display special characters correctly in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add header row
$headers = [
    'Client ID',
    'Full Name',
    'Father\'s Name',
    'Email',
    'Mobile',
    'Address',
    'GST Number',
    'PAN Number'
];

fputcsv($output, $headers);

// Add data rows
foreach ($clients as $client) {
    $row = [
        $client['client_id'],
        $client['name'] ?? '',
        $client['father_name'] ?? '',
        $client['email'] ?? '',
        $client['mobile'] ?? '',
        $client['address'] ?? '',
        $client['gst_number'] ?? '',
        $client['pan_number'] ?? ''
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit();
