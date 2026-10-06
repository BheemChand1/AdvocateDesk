<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get filter options
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$priority_filter = isset($_GET['priority']) ? trim($_GET['priority']) : '';

// Build the query to fetch cases
$query = "SELECT DISTINCT
    c.id,
    c.unique_case_id,
    c.case_type,
    c.loan_number,
    c.status,
    c.priority_status,
    c.priority_status_second,
    c.remark,
    c.cnr_number,
    cl.name as customer_name,
    cl.mobile,
    cl.email,
    COALESCE(
        ni.filing_date,
        cr.filing_date,
        cc.case_filling_date,
        ep.date_of_filing,
        ao.filing_date
    ) as filing_date,
    (SELECT GROUP_CONCAT(DISTINCT CASE 
        WHEN party_type IN ('accused', 'defendant') THEN name 
    END SEPARATOR ', ') 
    FROM case_parties WHERE case_id = c.id) as accused_opposite_party
FROM cases c
LEFT JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
WHERE 1=1";

if (!empty($status_filter)) {
    $query .= " AND c.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if ($priority_filter !== '') {
    if ($priority_filter === '1' || $priority_filter === 'first') {
        $query .= " AND c.priority_status = 1";
    } elseif ($priority_filter === '2' || $priority_filter === 'second') {
        $query .= " AND c.priority_status_second = 1";
    } elseif ($priority_filter === 'both') {
        $query .= " AND c.priority_status = 1 AND c.priority_status_second = 1";
    } elseif ($priority_filter === 'any') {
        $query .= " AND (c.priority_status = 1 OR c.priority_status_second = 1)";
    } elseif ($priority_filter === '0' || $priority_filter === 'none') {
        $query .= " AND c.priority_status = 0 AND c.priority_status_second = 0";
    }
}

if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $query .= " AND (c.loan_number LIKE '" . $search_term . "'
               OR c.unique_case_id LIKE '" . $search_term . "'
               OR cl.name LIKE '" . $search_term . "'
               OR cp.name LIKE '" . $search_term . "'
               OR c.cnr_number LIKE '" . $search_term . "')";
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";

$result = mysqli_query($conn, $query);
$cases = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cases[] = $row;
    }
}

if (empty($cases)) {
    die("No cases found to export.");
}

$filename = 'View_Cases_' . date('d-m-Y_H-i-s') . '.csv';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [
    'Case ID',
    'CNR No.',
    'Filing Date',
    'Customer Name',
    'Accused/Opposite Party',
    'Mobile',
    'Email',
    'Loan No.',
    'Case Type',
    'Priority Status',
    'Status'
]);

foreach ($cases as $case) {
    $priority_text = 'Not Priority';
    if ($case['priority_status'] == 1 && $case['priority_status_second'] == 1) {
        $priority_text = '1st & 2nd Priority';
    } elseif ($case['priority_status'] == 1) {
        $priority_text = '1st Priority';
    } elseif ($case['priority_status_second'] == 1) {
        $priority_text = '2nd Priority';
    }

    fputcsv($output, [
        $case['unique_case_id'] ?? 'N/A',
        $case['cnr_number'] ?? 'N/A',
        $case['filing_date'] ? date('d M, Y', strtotime($case['filing_date'])) : 'N/A',
        $case['customer_name'] ?? 'N/A',
        $case['accused_opposite_party'] ?? 'N/A',
        $case['mobile'] ?? 'N/A',
        $case['email'] ?? 'N/A',
        $case['loan_number'] ?? 'N/A',
        $case['case_type'] ?? 'N/A',
        $priority_text,
        ucfirst($case['status'] ?? 'Pending')
    ]);
}

fclose($output);
exit();