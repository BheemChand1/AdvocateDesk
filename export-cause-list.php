<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get filter options
$case_type_filter = isset($_GET['case_type']) ? $_GET['case_type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';

// Build the query to fetch cases with filing dates and latest position updates
$query = "SELECT 
    c.id,
    c.unique_case_id,
    c.case_type,
    c.loan_number,
    c.cnr_number,
    c.status,
    c.location,
    c.priority_status,
    c.remark,
    cl.name as customer_name,
    cl.mobile,
    COALESCE(
        ni.filing_date,
        cr.filing_date,
        cc.case_filling_date,
        ep.date_of_filing,
        ao.filing_date
    ) as filing_date,
    COALESCE(
        ni.case_no,
        cr.case_no,
        cc.case_no,
        ep.case_no,
        ao.case_no
    ) as case_no,
    COALESCE(
        ni.court_name,
        cr.court_name,
        cc.court_name,
        '',
        ''
    ) as court_name,
    (SELECT GROUP_CONCAT(DISTINCT CASE 
        WHEN party_type IN ('accused', 'defendant') THEN name 
    END SEPARATOR ', ') 
    FROM case_parties WHERE case_id = c.id) as accused_opposite_party,
    latest.update_date as latest_position_date,
    latest.position as latest_position,
    previous.update_date as previous_position_date,
    previous.position as previous_position,
    (SELECT COALESCE(SUM(fee_amount), 0) FROM case_fee_grid WHERE case_id = c.id) as total_fees,
    (SELECT COALESCE(SUM(fee_amount), 0) FROM case_fee_grid WHERE case_id = c.id) - 
    (SELECT COALESCE(SUM(fee_amount), 0) FROM case_position_updates WHERE case_id = c.id) as balance_fees
FROM cases c
LEFT JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
LEFT JOIN (
    SELECT case_id, update_date, position
    FROM case_position_updates
    WHERE (case_id, update_date) IN (
        SELECT case_id, MAX(update_date)
        FROM case_position_updates
        GROUP BY case_id
    )
) latest ON c.id = latest.case_id
LEFT JOIN (
    SELECT case_id, update_date, position
    FROM case_position_updates
    WHERE (case_id, update_date) IN (
        SELECT case_id, MAX(update_date)
        FROM case_position_updates
        WHERE update_date < (
            SELECT MAX(update_date)
            FROM case_position_updates cp2
            WHERE cp2.case_id = case_position_updates.case_id
        )
        GROUP BY case_id
    )
) previous ON c.id = previous.case_id
WHERE c.status != 'closed'";

// Apply search filter
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $query .= " AND (c.loan_number LIKE '" . $search_term . "'
               OR c.unique_case_id LIKE '" . $search_term . "'
               OR cl.name LIKE '" . $search_term . "'
               OR c.cnr_number LIKE '" . $search_term . "')";
}

// Apply filters
if (!empty($case_type_filter)) {
    $query .= " AND c.case_type = '" . mysqli_real_escape_string($conn, $case_type_filter) . "'";
}

if (!empty($status_filter)) {
    $query .= " AND c.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Apply date range filter on Fixed Date (latest_position_date or filing_date)
if (!empty($from_date)) {
    $from_date_safe = mysqli_real_escape_string($conn, $from_date);
    $query .= " AND COALESCE(latest.update_date, COALESCE(ni.filing_date, cr.filing_date, cc.case_filling_date, ep.date_of_filing, ao.filing_date)) >= '" . $from_date_safe . "'";
}

if (!empty($to_date)) {
    $to_date_safe = mysqli_real_escape_string($conn, $to_date);
    $query .= " AND COALESCE(latest.update_date, COALESCE(ni.filing_date, cr.filing_date, cc.case_filling_date, ep.date_of_filing, ao.filing_date)) <= '" . $to_date_safe . "'";
}

// Apply priority filter
if ($priority_filter !== '') {
    $priority_filter_int = intval($priority_filter);
    $query .= " AND c.priority_status = $priority_filter_int";
}

// Add GROUP BY clause to properly aggregate data
$query .= " GROUP BY c.id";

// Order by latest position update date if exists, otherwise by filing date (most recent first)
$query .= " ORDER BY COALESCE(latest.update_date, COALESCE(
    ni.filing_date,
    cr.filing_date,
    cc.case_filling_date,
    ep.date_of_filing,
    ao.filing_date
)) DESC, c.created_at DESC";

$result = mysqli_query($conn, $query);

$cases = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cases[] = $row;
    }
}

// Generate Excel file using CSV format (can be opened in Excel)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Cause_List_' . date('d-m-Y_H-i-s') . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Set BOM for UTF-8 to display special characters correctly in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add header row
$headers = [
    'Case ID',
    'Previous Date',
    'Case No',
    'Court Name',
    'Customer Name',
    'Mobile',
    'Accused/Opposite Party',
    'CNR Number',
    'Fixed Date',
    'Case Type',
    'Latest Stage',
    'Total Fee',
    'Balance Fee',
    'Priority Status',
    'Remark',
    'Case Status'
];

fputcsv($output, $headers);

// Add data rows
foreach ($cases as $case) {
    // Show previous position date if exists, otherwise show filing date
    $prev_date = $case['previous_position_date'] ?: $case['filing_date'];
    $prev_date_formatted = $prev_date ? date('d M, Y', strtotime($prev_date)) : 'N/A';
    
    // Display date (latest position date or filing date)
    $display_date = $case['latest_position_date'] ?: $case['filing_date'];
    $display_date_formatted = $display_date ? date('d M, Y', strtotime($display_date)) : 'Not Filed';
    
    $row = [
        $case['unique_case_id'] ?? 'N/A',
        $prev_date_formatted,
        $case['case_no'] ?? 'N/A',
        $case['court_name'] ?? 'N/A',
        $case['customer_name'] ?? 'N/A',
        $case['mobile'] ?? 'N/A',
        $case['accused_opposite_party'] ?? 'N/A',
        $case['cnr_number'] ?? 'N/A',
        $display_date_formatted,
        ucwords(str_replace('-', ' ', $case['case_type'])),
        $case['latest_position'] ?? 'No Updates',
        '₹' . number_format($case['total_fees'], 2),
        '₹' . number_format($case['balance_fees'], 2),
        $case['priority_status'] == 1 ? 'Priority' : 'Not Priority',
        $case['remark'] ?? '',
        ucfirst($case['status'])
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit();
