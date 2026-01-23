<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get filter options
$case_type_filter = $_GET['case_type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

// Query
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

if (!empty($search_query)) {
    $search = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $query .= " AND (
        c.loan_number LIKE '$search' OR
        c.unique_case_id LIKE '$search' OR
        cl.name LIKE '$search' OR
        c.cnr_number LIKE '$search'
    )";
}

if ($case_type_filter) {
    $query .= " AND c.case_type = '" . mysqli_real_escape_string($conn, $case_type_filter) . "'";
}

if ($status_filter) {
    $query .= " AND c.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if ($from_date) {
    $query .= " AND COALESCE(latest.update_date, filing_date) >= '" . mysqli_real_escape_string($conn, $from_date) . "'";
}

if ($to_date) {
    $query .= " AND COALESCE(latest.update_date, filing_date) <= '" . mysqli_real_escape_string($conn, $to_date) . "'";
}

if ($priority_filter !== '') {
    $query .= " AND c.priority_status = " . intval($priority_filter);
}

$query .= " GROUP BY c.id
ORDER BY COALESCE(latest.update_date, COALESCE(ni.filing_date, cr.filing_date, cc.case_filling_date, ep.date_of_filing, ao.filing_date)) DESC";

$result = mysqli_query($conn, $query);
$cases = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cases[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Print - Cause List</title>

<style>
@page {
    margin: 10mm;
}

body {
    font-family: Arial, sans-serif;
    background: white;
    margin: 0;
    padding: 0;
}

.print-container {
    width: 100%;
}

.print-header {
    text-align: center;
    border-bottom: 2px solid #000;
    margin-bottom: 8px;
    padding-bottom: 5px;
}

.print-header h1 {
    font-size: 20px;
    margin: 0;
}

.print-header p {
    font-size: 11px;
    margin: 2px 0 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

thead {
    display: table-header-group;
}

th, td {
    border: 1px solid #ccc;
    padding: 6px;
    text-align: left;
}

th {
    background: #f0f0f0;
}

tr {
    page-break-inside: avoid;
}

.text-blue { color: #2563eb; font-weight: bold; }
.text-green { color: #16a34a; font-weight: bold; }
.text-orange { color: #ea580c; font-weight: bold; }

@media print {
    button { display: none; }
}
</style>
</head>

<body>
<div class="print-container">

<div class="print-header">
    <h1>Cause List</h1>
    <p>Generated on <?= date('d M, Y H:i'); ?></p>
</div>

<?php if ($cases): ?>
<table>
<thead>
<tr>
    <th>Case ID</th>
    <th>Previous Date</th>
    <th>Case No</th>
    <th>Court</th>
    <th>Customer</th>
    <th>Opposite Party</th>
    <th>CNR</th>
    <th>Fixed Date</th>
    <th>Type</th>
    <th>Stage</th>
    <th>Total</th>
    <th>Balance</th>
</tr>
</thead>
<tbody>
<?php foreach ($cases as $c): ?>
<tr>
<td class="text-blue"><?= htmlspecialchars($c['unique_case_id']) ?></td>
<td><?= $c['previous_position_date'] ? date('d M Y', strtotime($c['previous_position_date'])) : 'N/A' ?></td>
<td><?= htmlspecialchars($c['case_no'] ?? 'N/A') ?></td>
<td><?= htmlspecialchars($c['court_name'] ?? 'N/A') ?></td>
<td><?= htmlspecialchars($c['customer_name']) ?><br><small><?= $c['mobile'] ?></small></td>
<td><?= htmlspecialchars($c['accused_opposite_party'] ?? 'N/A') ?></td>
<td><?= htmlspecialchars($c['cnr_number'] ?? 'N/A') ?></td>
<td><?= date('d M Y', strtotime($c['latest_position_date'] ?? $c['filing_date'])) ?></td>
<td><?= ucwords(str_replace('-', ' ', $c['case_type'])) ?></td>
<td><?= $c['latest_position'] ?? 'No Update' ?></td>
<td class="text-green">₹<?= number_format($c['total_fees'],2) ?></td>
<td class="<?= $c['balance_fees'] > 0 ? 'text-orange' : '' ?>">₹<?= number_format($c['balance_fees'],2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p style="text-align:center;margin-top:15px;">
Total Cases: <?= count($cases) ?><br>
<button onclick="window.print()">Print</button>
</p>

<?php else: ?>
<p style="text-align:center;">No cases found</p>
<?php endif; ?>

</div>
</body>
</html>
