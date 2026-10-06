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
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Print - View Cases</title>

<style>
@page {
    margin: 10mm;
}

body {
    font-family: Arial, sans-serif;
    background: white;
    margin: 0;
    padding: 10mm;
}

.header {
    text-align: center;
    margin-bottom: 18px;
    border-bottom: 2px solid #1f2937;
    padding-bottom: 10px;
}

.header h1 {
    margin: 0;
    color: #111827;
    font-size: 20px;
}

.header p {
    margin: 4px 0 0;
    color: #4b5563;
    font-size: 12px;
}

.summary {
    margin-bottom: 14px;
    font-size: 12px;
    color: #374151;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

th, td {
    border: 1px solid #cbd5e1;
    padding: 6px;
    text-align: left;
    vertical-align: top;
}

th {
    background: #f3f4f6;
    font-weight: bold;
}

tr {
    page-break-inside: avoid;
}

.case-id {
    color: #2563eb;
    font-weight: bold;
}

.priority-1 {
    color: #dc2626;
    font-weight: bold;
}

.priority-2 {
    color: #2563eb;
    font-weight: bold;
}

.controls {
    margin: 16px 0 20px;
}

.controls button {
    padding: 10px 18px;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
}

.print-btn {
    background: #2563eb;
    color: #fff;
}

.close-btn {
    background: #d1d5db;
    color: #111827;
    margin-left: 8px;
}

@media print {
    .controls {
        display: none;
    }
}
</style>
</head>
<body>

<div class="header">
    <h1>View Cases Report</h1>
    <p>Generated on: <?php echo date('d M, Y H:i'); ?></p>
</div>

<div class="summary">
    <?php if (!empty($search_query)): ?>
        <div>Search: <?php echo htmlspecialchars($search_query); ?></div>
    <?php endif; ?>
    <?php if (!empty($status_filter)): ?>
        <div>Status: <?php echo htmlspecialchars(ucfirst($status_filter)); ?></div>
    <?php endif; ?>
    <?php if ($priority_filter !== ''): ?>
        <div>Priority: <?php echo htmlspecialchars($priority_filter); ?></div>
    <?php endif; ?>
</div>

<div class="controls">
    <button class="print-btn" onclick="window.print()">Print</button>
    <button class="close-btn" onclick="window.close()">Close</button>
</div>

<?php if (!empty($cases)): ?>
<table>
    <thead>
        <tr>
            <th>Case ID</th>
            <th>CNR No.</th>
            <th>Filing Date</th>
            <th>Customer Name</th>
            <th>Accused/Opposite Party</th>
            <th>Mobile</th>
            <th>Email</th>
            <th>Loan No.</th>
            <th>Case Type</th>
            <th>Priority</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cases as $case): ?>
            <?php
            $priority_text = 'Not Priority';
            $priority_class = '';
            if ($case['priority_status'] == 1 && $case['priority_status_second'] == 1) {
                $priority_text = '1st & 2nd Priority';
                $priority_class = 'priority-1';
            } elseif ($case['priority_status'] == 1) {
                $priority_text = '1st Priority';
                $priority_class = 'priority-1';
            } elseif ($case['priority_status_second'] == 1) {
                $priority_text = '2nd Priority';
                $priority_class = 'priority-2';
            }
            ?>
            <tr>
                <td class="case-id"><?php echo htmlspecialchars($case['unique_case_id'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($case['cnr_number'] ?? 'N/A'); ?></td>
                <td><?php echo $case['filing_date'] ? date('d M, Y', strtotime($case['filing_date'])) : 'N/A'; ?></td>
                <td><?php echo htmlspecialchars($case['customer_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($case['accused_opposite_party'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($case['mobile'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($case['email'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($case['loan_number'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($case['case_type'] ?? 'N/A'); ?></td>
                <td class="<?php echo $priority_class; ?>"><?php echo htmlspecialchars($priority_text); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($case['status'] ?? 'Pending')); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>No cases found.</p>
<?php endif; ?>

</body>
</html>