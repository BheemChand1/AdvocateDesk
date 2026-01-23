<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get search parameter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch completed fees
$completed_sql = "
SELECT 
    cpu.id as update_id,
    cpu.case_id,
    cpu.bill_number,
    cpu.bill_date,
    cpu.payment_status,
    cpu.completed_date,
    c.unique_case_id,
    c.case_type,
    c.cnr_number,
    COALESCE(
        ni.case_no,
        cr.case_no,
        cc.case_no,
        ep.case_no,
        ao.case_no
    ) as case_no,
    cl.name as client_name,
    GROUP_CONCAT(DISTINCT CASE 
        WHEN cp.party_type IN ('accused', 'defendant') THEN cp.name 
    END SEPARATOR ', ') as accused_opposite_party,
    cpu.fee_amount,
    cpu.position as fee_name,
    cpu.update_date
FROM case_position_updates cpu
JOIN cases c ON cpu.case_id = c.id
JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
WHERE cpu.payment_status = 'completed' AND cpu.fee_amount > 0";

// Apply search filter
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $completed_sql .= " AND (
        c.cnr_number LIKE '" . $search_term . "'
        OR c.unique_case_id LIKE '" . $search_term . "'
        OR cl.name LIKE '" . $search_term . "'
        OR COALESCE(ni.case_no, cr.case_no, cc.case_no, ep.case_no, ao.case_no) LIKE '" . $search_term . "'
        OR cp.name LIKE '" . $search_term . "'
    )";
}

$completed_sql .= "
GROUP BY cpu.id
ORDER BY cpu.completed_date DESC, cpu.update_date DESC
";

$completed_result = mysqli_query($conn, $completed_sql);
$completed_accounts = [];
while ($row = mysqli_fetch_assoc($completed_result)) {
    $completed_accounts[] = $row;
}

// If no cases found
if (empty($completed_accounts)) {
    die("No completed fees found to export.");
}

// Set filename
$filename = 'Completed_Fees_' . date('d-m-Y_H-i-s') . '.csv';

// Generate Excel file using CSV format
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Set BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Define headers
$headers = [
    'Case ID',
    'Case No.',
    'CNR No.',
    'Client Name',
    'Accused/Opposite Party',
    'Case Type',
    'Bill Number',
    'Bill Date',
    'Fee Amount',
    'Fee Name',
    'Completed Date',
    'Update Date',
    'Status'
];

fputcsv($output, $headers);

// Add data rows
foreach ($completed_accounts as $case) {
    $row = [
        $case['unique_case_id'] ?? 'N/A',
        $case['case_no'] ?? 'N/A',
        $case['cnr_number'] ?? 'N/A',
        $case['client_name'] ?? 'N/A',
        $case['accused_opposite_party'] ?? 'N/A',
        $case['case_type'] ?? 'N/A',
        $case['bill_number'] ?? 'N/A',
        $case['bill_date'] ?? 'N/A',
        '₹' . number_format($case['fee_amount'], 2),
        $case['fee_name'] ?? 'N/A',
        $case['completed_date'] ? date('d M, Y', strtotime($case['completed_date'])) : 'N/A',
        $case['update_date'] ? date('d M, Y', strtotime($case['update_date'])) : 'N/A',
        'Completed'
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
