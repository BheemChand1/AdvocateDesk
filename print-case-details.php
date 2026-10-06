<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

$case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$case_id) {
    header("Location: view-cases.php");
    exit();
}

$caseQuery = "SELECT c.*, cl.name as customer_name, cl.father_name, cl.email, cl.mobile, cl.address,
    COALESCE(ni.filing_date, cr.filing_date, cc.case_filling_date, ep.date_of_filing, ao.filing_date) as filing_date,
    COALESCE(ni.case_no, cr.case_no, cc.case_no, ep.case_no, ao.case_no) as case_no,
    COALESCE(ni.court_name, cr.court_name, cc.court_name, ep.court_no, ao.court_no) as court_name,
    latest.update_date as latest_position_date,
    latest.position as latest_position,
    latest.remarks as latest_remark,
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
    SELECT case_id, update_date, position, remarks
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
WHERE c.id = ?";

$stmt = mysqli_prepare($conn, $caseQuery);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);

if (!$case) {
    header("Location: view-cases.php");
    exit();
}

$case_type = strtolower(trim($case['case_type'] ?? ''));

$detailTable = '';
switch ($case_type) {
    case 'ni_passa': $detailTable = 'case_ni_passa_details'; break;
    case 'criminal': $detailTable = 'case_criminal_details'; break;
    case 'consumer_civil': $detailTable = 'case_consumer_civil_details'; break;
    case 'ep_arbitration': $detailTable = 'case_ep_arbitration_details'; break;
    case 'arbitration_other': $detailTable = 'case_arbitration_other_details'; break;
}

$case_details = [];
if ($detailTable !== '') {
    $detailQuery = "SELECT * FROM {$detailTable} WHERE case_id = ?";
    $stmt = mysqli_prepare($conn, $detailQuery);
    mysqli_stmt_bind_param($stmt, "i", $case_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $case_details = mysqli_fetch_assoc($result) ?: [];
}

$query = "SELECT * FROM case_parties WHERE case_id = ? ORDER BY is_primary DESC, id ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$parties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $parties[] = $row;
}

$query = "SELECT * FROM case_fee_grid WHERE case_id = ? ORDER BY id ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$fee_grid = [];
while ($row = mysqli_fetch_assoc($result)) {
    $fee_grid[] = $row;
}

$query = "SELECT * FROM case_position_updates WHERE case_id = ? ORDER BY update_date DESC, created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$position_updates = [];
while ($row = mysqli_fetch_assoc($result)) {
    $position_updates[] = $row;
}

function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function formatDateValue($value, $format = 'd M, Y') { if (empty($value)) return 'N/A'; $timestamp = strtotime($value); return $timestamp ? date($format, $timestamp) : 'N/A'; }
function priorityLabel($case) { if (($case['priority_status'] ?? 0) == 1 && ($case['priority_status_second'] ?? 0) == 1) return '1st & 2nd Priority'; if (($case['priority_status'] ?? 0) == 1) return '1st Priority'; if (($case['priority_status_second'] ?? 0) == 1) return '2nd Priority'; return 'Not Priority'; }
function caseTypeLabel($caseType) { return ucwords(str_replace('_', ' ', $caseType)); }
function paymentBadge($status) { $status = strtolower((string) $status); if ($status === 'completed') return 'bg-green-100 text-green-800'; if ($status === 'processing') return 'bg-blue-100 text-blue-800'; if ($status === 'pending') return 'bg-yellow-100 text-yellow-800'; return 'bg-gray-100 text-gray-800'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Case Details - <?php echo e($case['unique_case_id'] ?? 'Case'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page { size: A4 landscape; margin: 6mm; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; margin: 0; }
        .sheet { max-width: 1120px; margin: 0 auto; padding: 18px; }
        .paper { background: #fff; border-radius: 18px; box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08); overflow: hidden; }
        .hero { background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 52%, #0ea5e9 100%); color: #fff; padding: 28px; }
        .hero-top { display: flex; justify-content: space-between; gap: 20px; align-items: flex-start; }
        .brand { display: flex; gap: 14px; align-items: center; }
        .brand img { width: 62px; height: 62px; object-fit: contain; background: rgba(255,255,255,0.1); border-radius: 14px; padding: 8px; }
        .hero h1 { margin: 0; font-size: 28px; line-height: 1.15; }
        .hero p { margin: 6px 0 0; opacity: 0.9; font-size: 13px; }
        .pill-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        .pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.18); }
        .content { padding: 24px 28px 28px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
        .toolbar { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 18px; }
        .toolbar button, .toolbar a { padding: 10px 16px; border: 0; border-radius: 10px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .print-btn { background: #1d4ed8; color: #fff; }
        .close-btn { background: #e5e7eb; color: #111827; }
        .section { margin-top: 22px; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
        .section-header { padding: 16px 18px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .section-header h2 { margin: 0; font-size: 16px; }
        .section-body { padding: 18px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .info-card { border: 1px solid #e5e7eb; background: #fff; border-radius: 14px; padding: 14px; min-height: 84px; }
        .info-label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; margin-bottom: 6px; }
        .info-value { font-size: 15px; font-weight: 700; color: #111827; line-height: 1.4; }
        .muted { color: #6b7280; font-weight: 500; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .badge { display: inline-flex; align-items: center; padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .party-card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 14px; background: #fff; }
        .party-role { display: inline-block; margin-bottom: 8px; padding: 4px 8px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: 700; }
        .footer-note { padding: 14px 18px 22px; text-align: center; color: #6b7280; font-size: 12px; }
        @media print {
            body { background: #fff; }
            .sheet { padding: 0; max-width: none; }
            .paper { box-shadow: none; border-radius: 0; }
            .toolbar { display: none; }
            .section { break-inside: avoid; margin-top: 10px; }
            .section-header { padding: 10px 12px; }
            .section-header h2 { font-size: 14px; }
            .section-body { padding: 12px; }
            .hero { padding: 18px 20px; }
            .hero h1 { font-size: 22px; }
            .hero p { font-size: 11px; }
            .pill-row { margin-top: 10px; gap: 6px; }
            .pill { padding: 6px 10px; font-size: 10px; }
            .grid-3, .grid-2 { gap: 8px; }
            .info-card { min-height: 0; padding: 10px; border-radius: 10px; }
            .info-label { font-size: 9px; margin-bottom: 4px; }
            .info-value { font-size: 12px; }
            .muted { font-size: 10px; }
            .badge { padding: 4px 8px; font-size: 10px; }
            .footer-note { display: none; }
        }
        @media (max-width: 900px) { .grid-3, .grid-2, .hero-top { grid-template-columns: 1fr; flex-direction: column; } }
    </style>
</head>
<body>
<div class="sheet">
    <div class="toolbar">
        <button type="button" class="print-btn" onclick="window.print()"><i class="fas fa-print mr-2"></i>Print</button>
        <a class="close-btn" href="javascript:window.close()"><i class="fas fa-xmark mr-2"></i>Close</a>
    </div>
    <div class="paper">
        <div class="hero">
            <div class="hero-top">
                <div class="brand">
                    <img src="./assets/mps-logo.png" alt="MPS Legal Logo">
                    <div>
                        <h1>MPS Legal</h1>
                        <p>Client Case Summary</p>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:12px; opacity:.85;">Generated on</div>
                    <div style="font-size:15px; font-weight:700; margin-top:4px;"><?php echo date('d M Y, H:i'); ?></div>
                </div>
            </div>
            <div class="pill-row">
                <span class="pill"><i class="fas fa-hashtag"></i><?php echo e($case['unique_case_id'] ?? 'N/A'); ?></span>
                <span class="pill"><i class="fas fa-scale-balanced"></i><?php echo e(caseTypeLabel($case_type)); ?></span>
                <span class="pill"><i class="fas fa-flag"></i><?php echo e(ucfirst($case['status'] ?? 'Pending')); ?></span>
                <span class="pill"><i class="fas fa-star"></i><?php echo e(priorityLabel($case)); ?></span>
            </div>
        </div>
        <div class="content">
            <div class="section section-print-essential" style="margin-top:0;">
                <div class="section-header"><h2>Case Overview</h2></div>
                <div class="section-body">
                    <div class="grid-3">
                        <div class="info-card"><div class="info-label">Customer</div><div class="info-value"><?php echo e($case['customer_name'] ?? 'N/A'); ?></div><div class="muted"><?php echo e($case['father_name'] ?? ''); ?></div></div>
                        <div class="info-card"><div class="info-label">Loan / CNR</div><div class="info-value"><?php echo e($case['loan_number'] ?? 'N/A'); ?></div><div class="muted"><?php echo e($case['cnr_number'] ?? 'N/A'); ?></div></div>
                        <div class="info-card"><div class="info-label">Case / Court</div><div class="info-value"><?php echo e($case['case_no'] ?? 'N/A'); ?></div><div class="muted"><?php echo e($case['court_name'] ?? 'N/A'); ?></div></div>
                    </div>
                    <div class="grid-3" style="margin-top:14px;">
                        <div class="info-card"><div class="info-label">Filing Date</div><div class="info-value"><?php echo e(formatDateValue($case['filing_date'])); ?></div></div>
                        <div class="info-card"><div class="info-label">Latest Update</div><div class="info-value"><?php echo e(formatDateValue($case['latest_position_date'])); ?></div><div class="muted"><?php echo e($case['latest_position'] ?? 'No Updates'); ?></div></div>
                        <div class="info-card"><div class="info-label">Fees</div><div class="info-value">Total: ₹<?php echo number_format((float) ($case['total_fees'] ?? 0), 2); ?></div><div class="muted">Balance: ₹<?php echo number_format((float) ($case['balance_fees'] ?? 0), 2); ?></div></div>
                    </div>
                </div>
            </div>
            <div class="section section-print-essential">
                <div class="section-header"><h2>Contact Information</h2></div>
                <div class="section-body"><div class="grid-3"><div class="info-card"><div class="info-label">Mobile</div><div class="info-value"><?php echo e($case['mobile'] ?? 'N/A'); ?></div></div><div class="info-card"><div class="info-label">Email</div><div class="info-value"><?php echo e($case['email'] ?? 'N/A'); ?></div></div><div class="info-card"><div class="info-label">Address</div><div class="info-value"><?php echo $case['address'] ? nl2br(e($case['address'])) : 'N/A'; ?></div></div></div></div>
            </div>
            <?php if (!empty($case_details)): ?>
            <div class="section section-print-essential">
                <div class="section-header"><h2><?php echo e(caseTypeLabel($case_type)); ?> Details</h2></div>
                <div class="section-body">
                    <div class="grid-3">
                        <?php if ($case_type === 'ni_passa'): ?>
                            <div class="info-card"><div class="info-label">Cheque No.</div><div class="info-value"><?php echo e($case_details['cheque_no'] ?? 'N/A'); ?></div></div>
                            <div class="info-card"><div class="info-label">Cheque Date</div><div class="info-value"><?php echo e(formatDateValue($case_details['cheque_date'] ?? null)); ?></div></div>
                            <div class="info-card"><div class="info-label">Cheque Amount</div><div class="info-value"><?php echo !empty($case_details['cheque_amount']) ? '₹' . number_format((float) $case_details['cheque_amount'], 2) : 'N/A'; ?></div></div>
                            <div class="info-card"><div class="info-label">Bank</div><div class="info-value"><?php echo e($case_details['bank_name_address'] ?? 'N/A'); ?></div></div>
                            <div class="info-card"><div class="info-label">Bounce Date / Reason</div><div class="info-value"><?php echo e(formatDateValue($case_details['bounce_date'] ?? null)); ?><div class="muted"><?php echo e($case_details['bounce_reason'] ?? ''); ?></div></div></div>
                            <div class="info-card"><div class="info-label">Current Stage</div><div class="info-value"><?php echo e($case_details['current_stage'] ?? 'N/A'); ?></div></div>
                        <?php elseif ($case_type === 'criminal'): ?>
                            <div class="info-card"><div class="info-label">Section / Act</div><div class="info-value"><?php echo e(($case_details['section'] ?? 'N/A') . ' / ' . ($case_details['act'] ?? 'N/A')); ?></div></div>
                            <div class="info-card"><div class="info-label">Police Station</div><div class="info-value"><?php echo e($case_details['police_station_with_district'] ?? 'N/A'); ?></div></div>
                            <div class="info-card"><div class="info-label">Crime / FIR No.</div><div class="info-value"><?php echo e($case_details['crime_no_fir_no'] ?? 'N/A'); ?></div></div>
                            <div class="info-card"><div class="info-label">FIR Date</div><div class="info-value"><?php echo e(formatDateValue($case_details['fir_date'] ?? null)); ?></div></div>
                            <div class="info-card"><div class="info-label">Charge Sheet Date</div><div class="info-value"><?php echo e(formatDateValue($case_details['charge_sheet_date'] ?? null)); ?></div></div>
                            <div class="info-card"><div class="info-label">Case Type Specific</div><div class="info-value"><?php echo e($case_details['case_type_specific'] ?? 'N/A'); ?></div></div>
                        <?php elseif ($case_type === 'consumer_civil'): ?>
                            <div class="info-card"><div class="info-label">Case Type Specific</div><div class="info-value"><?php echo e($case_details['case_type_specific'] ?? 'N/A'); ?></div></div>
                            <div class="info-card"><div class="info-label">Legal Notice Date</div><div class="info-value"><?php echo e(formatDateValue($case_details['legal_notice_date'] ?? null)); ?></div></div>
                            <div class="info-card"><div class="info-label">Case vs Law Act</div><div class="info-value"><?php echo e($case_details['case_vs_law_act'] ?? 'N/A'); ?></div></div>
                            <div class="info-card"><div class="info-label">SWT Value</div><div class="info-value"><?php echo !empty($case_details['swt_value']) ? '₹' . number_format((float) $case_details['swt_value'], 2) : 'N/A'; ?></div></div>
                            <div class="info-card"><div class="info-label">Filing Location / Court No.</div><div class="info-value"><?php echo e(($case_details['filing_location'] ?? 'N/A') . ' / ' . ($case_details['court_no'] ?? 'N/A')); ?></div></div>
                            <div class="info-card"><div class="info-label">Advocate / POA</div><div class="info-value"><?php echo e(($case_details['advocate'] ?? 'N/A') . ' / ' . ($case_details['poa'] ?? 'N/A')); ?></div></div>
                        <?php elseif ($case_type === 'ep_arbitration'): ?>
                            <div class="info-card"><div class="info-label">Date of Filing</div><div class="info-value"><?php echo e(formatDateValue($case_details['date_of_filing'] ?? null)); ?></div></div>
                            <div class="info-card"><div class="info-label">Filing Location / Court No.</div><div class="info-value"><?php echo e(($case_details['filing_location'] ?? 'N/A') . ' / ' . ($case_details['court_no'] ?? 'N/A')); ?></div></div>
                            <div class="info-card"><div class="info-label">Advocate / POA</div><div class="info-value"><?php echo e(($case_details['advocate'] ?? 'N/A') . ' / ' . ($case_details['poa'] ?? 'N/A')); ?></div></div>
                            <div class="info-card"><div class="info-label">Award Date</div><div class="info-value"><?php echo e(formatDateValue($case_details['award_date'] ?? null)); ?></div></div>
                            <div class="info-card"><div class="info-label">Arbitrator</div><div class="info-value"><?php echo e($case_details['arbitrator_name'] ?? 'N/A'); ?><div class="muted"><?php echo e($case_details['arbitrator_address'] ?? ''); ?></div></div></div>
                            <div class="info-card"><div class="info-label">Award / Claim Amount</div><div class="info-value"><?php echo !empty($case_details['award_amount']) ? '₹' . number_format((float) $case_details['award_amount'], 2) : 'N/A'; ?><div class="muted"><?php echo !empty($case_details['claim_amount']) ? 'Claim: ₹' . number_format((float) $case_details['claim_amount'], 2) : ''; ?></div></div></div>
                        <?php elseif ($case_type === 'arbitration_other'): ?>
                            <div class="info-card"><div class="info-label">Filing Amount</div><div class="info-value"><?php echo !empty($case_details['filing_amount']) ? '₹' . number_format((float) $case_details['filing_amount'], 2) : 'N/A'; ?></div></div>
                            <div class="info-card"><div class="info-label">Filing Location / Court No.</div><div class="info-value"><?php echo e(($case_details['filing_location'] ?? 'N/A') . ' / ' . ($case_details['court_no'] ?? 'N/A')); ?></div></div>
                            <div class="info-card"><div class="info-label">Advocate / POA</div><div class="info-value"><?php echo e(($case_details['advocate'] ?? 'N/A') . ' / ' . ($case_details['poa'] ?? 'N/A')); ?></div></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($case_details['remarks']) || !empty($case_details['remarks_feedback_trails'])): ?>
                        <div class="info-card" style="margin-top:14px;"><div class="info-label">Remarks</div><div class="info-value"><?php echo nl2br(e($case_details['remarks'] ?? $case_details['remarks_feedback_trails'])); ?></div></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($parties)): ?>
            <div class="section">
                <div class="section-header"><h2>Case Parties</h2></div>
                <div class="section-body"><div class="grid-2"><?php foreach ($parties as $party): ?><div class="party-card"><div class="party-role"><?php echo e(ucfirst(str_replace('_', ' ', $party['party_type'] ?? 'party'))); ?></div><div style="font-size:16px; font-weight:700; margin-bottom:6px;"><?php echo e($party['name'] ?? 'N/A'); ?></div><?php if (!empty($party['address'])): ?><div class="muted"><?php echo nl2br(e($party['address'])); ?></div><?php endif; ?></div><?php endforeach; ?></div></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($fee_grid)): ?>
            <div class="section">
                <div class="section-header"><h2>Fee Grid</h2></div>
                <div class="section-body table-wrap"><table><thead><tr><th>Stage</th><th>Fee Amount</th></tr></thead><tbody><?php foreach ($fee_grid as $fee): ?><tr><td><?php echo e($fee['fee_name'] ?? 'N/A'); ?></td><td>₹<?php echo number_format((float) ($fee['fee_amount'] ?? 0), 2); ?></td></tr><?php endforeach; ?></tbody></table></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($position_updates)): ?>
            <div class="section">
                <div class="section-header"><h2>Recent Position Updates</h2></div>
                <div class="section-body table-wrap"><table><thead><tr><th>Date</th><th>Stage</th><th>Payment</th><th>Remarks</th></tr></thead><tbody><?php foreach ($position_updates as $update): ?><tr><td><?php echo e(formatDateValue($update['update_date'] ?? null)); ?></td><td><?php echo e($update['position'] ?? 'N/A'); ?></td><td>₹<?php echo number_format((float) ($update['fee_amount'] ?? 0), 2); ?><br><span class="badge <?php echo e(paymentBadge($update['payment_status'] ?? 'pending')); ?>"><?php echo e(ucfirst($update['payment_status'] ?? 'Pending')); ?></span></td><td><?php echo !empty($update['remarks']) ? nl2br(e($update['remarks'])) : 'N/A'; ?></td></tr><?php endforeach; ?></tbody></table></div>
            </div>
            <?php endif; ?>
            <div class="footer-note">Prepared for client sharing. Please verify case details against the source record before filing or submission.</div>
        </div>
    </div>
</div>
</body>
</html>