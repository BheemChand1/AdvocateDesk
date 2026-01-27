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

// Fetch processing fees (show only fees with processing payment status from case_position_updates)
$processing_sql = "
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
WHERE cpu.payment_status = 'processing' AND cpu.fee_amount > 0";

// Apply search filter
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $processing_sql .= " AND (
        c.cnr_number LIKE '" . $search_term . "'
        OR c.unique_case_id LIKE '" . $search_term . "'
        OR cl.name LIKE '" . $search_term . "'
        OR COALESCE(ni.case_no, cr.case_no, cc.case_no, ep.case_no, ao.case_no) LIKE '" . $search_term . "'
        OR cp.name LIKE '" . $search_term . "'
    )";
}

$processing_sql .= "
GROUP BY cpu.id
ORDER BY cpu.update_date DESC
";

$processing_result = @mysqli_query($conn, $processing_sql);
$processing_accounts = [];
if ($processing_result) {
    while ($row = mysqli_fetch_assoc($processing_result)) {
        $processing_accounts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Print - Processing Fees</title>

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
    margin-bottom: 20px;
    border-bottom: 2px solid #333;
    padding-bottom: 10px;
}

.header h1 {
    margin: 0;
    color: #333;
}

.header p {
    margin: 5px 0;
    color: #666;
    font-size: 12px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 12px;
}

table thead {
    background-color: #f3f4f6;
}

table th {
    border: 1px solid #000;
    padding: 8px;
    text-align: left;
    font-weight: bold;
    color: #333;
}

table td {
    border: 1px solid #ddd;
    padding: 8px;
}

table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tbody tr:hover {
    background-color: #f0f0f0;
}

.amount {
    text-align: right;
    font-weight: bold;
    color: #0066cc;
}

.summary {
    margin-top: 20px;
    padding: 10px;
    background-color: #f3f4f6;
    border-left: 4px solid #0066cc;
}

.summary p {
    margin: 5px 0;
    font-size: 13px;
}

@media print {
    body {
        margin: 0;
        padding: 10mm;
    }
    
    table {
        page-break-inside: avoid;
    }
    
    .no-print {
        display: none;
    }
}

.no-print {
    margin: 20px 0;
}

.no-print button {
    padding: 10px 20px;
    background-color: #0066cc;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.no-print button:hover {
    background-color: #0052a3;
}
</style>
</head>
<body>

<div class="header">
    <h1>Processing Fees Report</h1>
    <p>Generated on: <?php echo date('d-m-Y H:i:s'); ?></p>
    <?php if (!empty($search_query)): ?>
    <p>Search Filter: <?php echo htmlspecialchars($search_query); ?></p>
    <?php endif; ?>
</div>

<div class="no-print">
    <button onclick="window.print()">🖨️ Print</button>
    <button onclick="window.close()">❌ Close</button>
</div>

<?php if (!empty($processing_accounts)): ?>
<table>
    <thead>
        <tr>
            <th>Case ID</th>
            <th>Case No.</th>
            <th>CNR No.</th>
            <th>Client Name</th>
            <th>Accused/Opposite Party</th>
            <th>Case Type</th>
            <th>Bill Number</th>
            <th>Bill Date</th>
            <th>Fee Amount</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total_fees = 0;
        foreach ($processing_accounts as $account): 
            $total_fees += $account['fee_amount'];
        ?>
        <tr>
            <td><?php echo htmlspecialchars($account['unique_case_id']); ?></td>
            <td><?php echo htmlspecialchars($account['case_no'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($account['cnr_number'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($account['client_name']); ?></td>
            <td><?php echo htmlspecialchars($account['accused_opposite_party'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($account['case_type']); ?></td>
            <td><?php echo htmlspecialchars($account['bill_number'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($account['bill_date'] ?? 'N/A'); ?></td>
            <td class="amount">₹<?php echo number_format($account['fee_amount'], 2); ?></td>
            <td><?php echo htmlspecialchars($account['payment_status']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="summary">
    <p><strong>Total Records:</strong> <?php echo count($processing_accounts); ?></p>
    <p><strong>Total Fee Amount:</strong> ₹<?php echo number_format($total_fees, 2); ?></p>
</div>

<?php else: ?>
<div style="text-align: center; padding: 40px; color: #666;">
    <p style="font-size: 16px;">No processing fees found.</p>
</div>
<?php endif; ?>

</body>
</html>
