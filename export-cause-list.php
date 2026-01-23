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
    c.product,
    c.branch_name,
    c.region,
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
    (SELECT COALESCE(SUM(fee_amount), 0) FROM case_position_updates WHERE case_id = c.id) as balance_fees,
    
    -- NI/PASSA Details - ALL COLUMNS
    ni.accused_authorised_person,
    ni.cheque_no,
    ni.cheque_date,
    ni.total_no_of_chq,
    ni.cheque_amount,
    ni.filing_amount,
    ni.bank_name_address,
    ni.cheque_holder_name,
    ni.cheque_status,
    ni.bounce_date,
    ni.bounce_reason,
    ni.notice_date,
    ni.notice_sent_date,
    ni.filing_location as ni_filing_location,
    ni.court_no as ni_court_no,
    ni.section as ni_section,
    ni.act as ni_act,
    ni.poa_date as ni_poa_date,
    ni.last_date_update,
    ni.current_stage as ni_current_stage,
    ni.remarks as ni_remarks,
    
    -- Criminal Details - ALL COLUMNS
    cr.case_type_specific as cr_case_type_specific,
    cr.section as cr_section,
    cr.act as cr_act,
    cr.police_station_with_district,
    cr.crime_no_fir_no,
    cr.fir_date,
    cr.charge_sheet_date,
    cr.notice_date as cr_notice_date,
    cr.poa_date as cr_poa_date,
    cr.filing_location as cr_filing_location,
    cr.court_no as cr_court_no,
    cr.remarks as cr_remarks,
    
    -- Consumer/Civil Details - ALL COLUMNS
    cc.case_type_specific as cc_case_type_specific,
    cc.case_filling_date,
    cc.legal_notice_date,
    cc.case_vs_law_act,
    cc.swt_value,
    cc.filing_location as cc_filing_location,
    cc.court_no as cc_court_no,
    cc.advocate as cc_advocate,
    cc.poa as cc_poa,
    cc.remarks as cc_remarks,
    
    -- EP/Arbitration Details - ALL COLUMNS
    ep.filing_location as ep_filing_location,
    ep.court_no as ep_court_no,
    ep.advocate as ep_advocate,
    ep.poa as ep_poa,
    ep.date_of_filing,
    ep.customer_office_address,
    ep.award_date,
    ep.arbitrator_name,
    ep.arbitrator_address,
    ep.arbitration_case_no,
    ep.interest_start_date,
    ep.interest_end_date,
    ep.total_days,
    ep.award_amount,
    ep.rate_of_interest,
    ep.interest_amount,
    ep.cost,
    ep.recovery_amount,
    ep.claim_amount,
    ep.vehicle_1_classification,
    ep.vehicle_2_asset_description,
    ep.vehicle_3_registration_number,
    ep.vehicle_4_engine_no,
    ep.vehicle_5_chasis_no,
    ep.immoveable_property_detail_1,
    ep.immoveable_property_detail_2,
    ep.immoveable_property_detail_3,
    ep.remarks_feedback_trails as ep_remarks_feedback_trails,
    
    -- Arbitration Other Details - ALL COLUMNS
    ao.customer_name as ao_customer_name,
    ao.filing_amount as ao_filing_amount,
    ao.filing_location as ao_filing_location,
    ao.court_no as ao_court_no,
    ao.advocate as ao_advocate,
    ao.poa as ao_poa,
    ao.remarks_feedback_trails as ao_remarks_feedback_trails
    
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

// Group cases by case type
$casesByType = [];
foreach ($cases as $case) {
    $caseType = $case['case_type'];
    if (!isset($casesByType[$caseType])) {
        $casesByType[$caseType] = [];
    }
    $casesByType[$caseType][] = $case;
}

// Common columns for all case types
$commonHeaders = [
    'Case ID',
    'Previous Date',
    'Case No',
    'Court Name',
    'Customer Name',
    'Mobile',
    'Accused/Opposite Party',
    'CNR Number',
    'Fixed Date',
    'Latest Stage',
    'Total Fee',
    'Balance Fee',
    'Priority Status',
    'Product',
    'Branch',
    'Region',
    'Location',
    'Remark',
    'Case Status'
];

// Case type specific headers
$caseTypeSpecificHeaders = [
    'NI_PASSA' => [
        'Accused Authorised Person',
        'Cheque No',
        'Cheque Date',
        'Total No of Cheques',
        'Cheque Amount',
        'Filing Amount',
        'Bank Name & Address',
        'Cheque Holder Name',
        'Cheque Status',
        'Bounce Date',
        'Bounce Reason',
        'Notice Date',
        'Notice Sent Date',
        'Filing Location',
        'Court No',
        'Section',
        'Act',
        'POA Date',
        'Last Date Update',
        'Current Stage',
        'Remarks'
    ],
    'CRIMINAL' => [
        'Case Type Specific',
        'Section',
        'Act',
        'Police Station with District',
        'Crime/FIR No',
        'FIR Date',
        'Charge Sheet Date',
        'Notice Date',
        'POA Date',
        'Filing Location',
        'Court No',
        'Remarks'
    ],
    'CONSUMER_CIVIL' => [
        'Case Type Specific',
        'Case Filling Date',
        'Legal Notice Date',
        'Case vs Law Act',
        'SWT Value',
        'Filing Location',
        'Court No',
        'Advocate',
        'POA',
        'Remarks'
    ],
    'EP_ARBITRATION' => [
        'Filing Location',
        'Court No',
        'Advocate',
        'POA',
        'Date of Filing',
        'Customer Office Address',
        'Award Date',
        'Arbitrator Name',
        'Arbitrator Address',
        'Arbitration Case No',
        'Interest Start Date',
        'Interest End Date',
        'Total Days',
        'Award Amount',
        'Rate of Interest',
        'Interest Amount',
        'Cost',
        'Recovery Amount',
        'Claim Amount',
        'Vehicle 1 Classification',
        'Vehicle 2 Asset Description',
        'Vehicle 3 Registration Number',
        'Vehicle 4 Engine No',
        'Vehicle 5 Chassis No',
        'Immoveable Property Detail 1',
        'Immoveable Property Detail 2',
        'Immoveable Property Detail 3',
        'Remarks & Feedback Trails'
    ],
    'ARBITRATION_OTHER' => [
        'Customer Name',
        'Filing Amount',
        'Filing Location',
        'Court No',
        'Advocate',
        'POA',
        'Remarks & Feedback Trails'
    ]
];

// Determine which case type(s) to export
$typesToExport = [];
if (!empty($case_type_filter)) {
    // If filter is applied, only export that type
    if (isset($casesByType[$case_type_filter])) {
        $typesToExport[$case_type_filter] = $casesByType[$case_type_filter];
    }
} else {
    // If no filter, export all types
    $typesToExport = $casesByType;
}

// If no cases found, show error
if (empty($typesToExport)) {
    die("No cases found to export. Available case types: " . implode(', ', array_keys($casesByType)));
}

// Determine filename
if (count($typesToExport) === 1) {
    $caseType = array_key_first($typesToExport);
    $filename = 'Cause_List_' . $caseType . '_' . date('d-m-Y_H-i-s') . '.csv';
} else {
    $filename = 'Cause_List_' . date('d-m-Y_H-i-s') . '.csv';
}

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

// If exporting single case type, use specific columns. If multiple types, use all columns
if (count($typesToExport) === 1) {
    $caseType = array_key_first($typesToExport);
    $casesOfType = $typesToExport[$caseType];
    
    // Build headers: common + case-type-specific
    $headers = $commonHeaders;
    if (isset($caseTypeSpecificHeaders[$caseType])) {
        $headers = array_merge($headers, $caseTypeSpecificHeaders[$caseType]);
    }
    
    fputcsv($output, $headers);
    
    // Add data rows for this case type
    foreach ($casesOfType as $case) {
        $prev_date = $case['previous_position_date'] ?: $case['filing_date'];
        $prev_date_formatted = $prev_date ? date('d M, Y', strtotime($prev_date)) : 'N/A';
        
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
            $case['latest_position'] ?? 'No Updates',
            '₹' . number_format($case['total_fees'], 2),
            '₹' . number_format($case['balance_fees'], 2),
            $case['priority_status'] == 1 ? 'Priority' : 'Not Priority',
            $case['product'] ?? 'N/A',
            $case['branch_name'] ?? 'N/A',
            $case['region'] ?? 'N/A',
            $case['location'] ?? 'N/A',
            $case['remark'] ?? '',
            ucfirst($case['status'])
        ];
        
        // Add case-type-specific columns
        if ($caseType === 'NI_PASSA') {
            $cheque_date_formatted = $case['cheque_date'] ? date('d M, Y', strtotime($case['cheque_date'])) : 'N/A';
            $bounce_date_formatted = $case['bounce_date'] ? date('d M, Y', strtotime($case['bounce_date'])) : 'N/A';
            $notice_date_formatted = $case['notice_date'] ? date('d M, Y', strtotime($case['notice_date'])) : 'N/A';
            $notice_sent_date_formatted = $case['notice_sent_date'] ? date('d M, Y', strtotime($case['notice_sent_date'])) : 'N/A';
            $poa_date_formatted = $case['ni_poa_date'] ? date('d M, Y', strtotime($case['ni_poa_date'])) : 'N/A';
            $last_date_update_formatted = $case['last_date_update'] ? date('d M, Y', strtotime($case['last_date_update'])) : 'N/A';
            
            $row = array_merge($row, [
                $case['accused_authorised_person'] ?? 'N/A',
                $case['cheque_no'] ?? 'N/A',
                $cheque_date_formatted,
                $case['total_no_of_chq'] ?? 'N/A',
                !empty($case['cheque_amount']) ? '₹' . number_format($case['cheque_amount'], 2) : 'N/A',
                !empty($case['filing_amount']) ? '₹' . number_format($case['filing_amount'], 2) : 'N/A',
                $case['bank_name_address'] ?? 'N/A',
                $case['cheque_holder_name'] ?? 'N/A',
                $case['cheque_status'] ?? 'N/A',
                $bounce_date_formatted,
                $case['bounce_reason'] ?? 'N/A',
                $notice_date_formatted,
                $notice_sent_date_formatted,
                $case['ni_filing_location'] ?? 'N/A',
                $case['ni_court_no'] ?? 'N/A',
                $case['ni_section'] ?? 'N/A',
                $case['ni_act'] ?? 'N/A',
                $poa_date_formatted,
                $last_date_update_formatted,
                $case['ni_current_stage'] ?? 'N/A',
                $case['ni_remarks'] ?? ''
            ]);
        } elseif ($caseType === 'CRIMINAL') {
            $fir_date_formatted = $case['fir_date'] ? date('d M, Y', strtotime($case['fir_date'])) : 'N/A';
            $charge_sheet_date_formatted = $case['charge_sheet_date'] ? date('d M, Y', strtotime($case['charge_sheet_date'])) : 'N/A';
            $cr_notice_date_formatted = $case['cr_notice_date'] ? date('d M, Y', strtotime($case['cr_notice_date'])) : 'N/A';
            $cr_poa_date_formatted = $case['cr_poa_date'] ? date('d M, Y', strtotime($case['cr_poa_date'])) : 'N/A';
            
            $row = array_merge($row, [
                $case['cr_case_type_specific'] ?? 'N/A',
                $case['cr_section'] ?? 'N/A',
                $case['cr_act'] ?? 'N/A',
                $case['police_station_with_district'] ?? 'N/A',
                $case['crime_no_fir_no'] ?? 'N/A',
                $fir_date_formatted,
                $charge_sheet_date_formatted,
                $cr_notice_date_formatted,
                $cr_poa_date_formatted,
                $case['cr_filing_location'] ?? 'N/A',
                $case['cr_court_no'] ?? 'N/A',
                $case['cr_remarks'] ?? ''
            ]);
        } elseif ($caseType === 'CONSUMER_CIVIL') {
            $case_filling_date_formatted = $case['case_filling_date'] ? date('d M, Y', strtotime($case['case_filling_date'])) : 'N/A';
            $legal_notice_date_formatted = $case['legal_notice_date'] ? date('d M, Y', strtotime($case['legal_notice_date'])) : 'N/A';
            
            $row = array_merge($row, [
                $case['cc_case_type_specific'] ?? 'N/A',
                $case_filling_date_formatted,
                $legal_notice_date_formatted,
                $case['case_vs_law_act'] ?? 'N/A',
                $case['swt_value'] ?? 'N/A',
                $case['cc_filing_location'] ?? 'N/A',
                $case['cc_court_no'] ?? 'N/A',
                $case['cc_advocate'] ?? 'N/A',
                $case['cc_poa'] ?? 'N/A',
                $case['cc_remarks'] ?? ''
            ]);
        } elseif ($caseType === 'EP_ARBITRATION') {
            $date_of_filing_formatted = $case['date_of_filing'] ? date('d M, Y', strtotime($case['date_of_filing'])) : 'N/A';
            $award_date_formatted = $case['award_date'] ? date('d M, Y', strtotime($case['award_date'])) : 'N/A';
            $interest_start_date_formatted = $case['interest_start_date'] ? date('d M, Y', strtotime($case['interest_start_date'])) : 'N/A';
            $interest_end_date_formatted = $case['interest_end_date'] ? date('d M, Y', strtotime($case['interest_end_date'])) : 'N/A';
            
            $row = array_merge($row, [
                $case['ep_filing_location'] ?? 'N/A',
                $case['ep_court_no'] ?? 'N/A',
                $case['ep_advocate'] ?? 'N/A',
                $case['ep_poa'] ?? 'N/A',
                $date_of_filing_formatted,
                $case['customer_office_address'] ?? 'N/A',
                $award_date_formatted,
                $case['arbitrator_name'] ?? 'N/A',
                $case['arbitrator_address'] ?? 'N/A',
                $case['arbitration_case_no'] ?? 'N/A',
                $interest_start_date_formatted,
                $interest_end_date_formatted,
                $case['total_days'] ?? 'N/A',
                !empty($case['award_amount']) ? '₹' . number_format($case['award_amount'], 2) : 'N/A',
                !empty($case['rate_of_interest']) ? $case['rate_of_interest'] . '%' : 'N/A',
                !empty($case['interest_amount']) ? '₹' . number_format($case['interest_amount'], 2) : 'N/A',
                !empty($case['cost']) ? '₹' . number_format($case['cost'], 2) : 'N/A',
                !empty($case['recovery_amount']) ? '₹' . number_format($case['recovery_amount'], 2) : 'N/A',
                !empty($case['claim_amount']) ? '₹' . number_format($case['claim_amount'], 2) : 'N/A',
                $case['vehicle_1_classification'] ?? 'N/A',
                $case['vehicle_2_asset_description'] ?? 'N/A',
                $case['vehicle_3_registration_number'] ?? 'N/A',
                $case['vehicle_4_engine_no'] ?? 'N/A',
                $case['vehicle_5_chasis_no'] ?? 'N/A',
                $case['immoveable_property_detail_1'] ?? 'N/A',
                $case['immoveable_property_detail_2'] ?? 'N/A',
                $case['immoveable_property_detail_3'] ?? 'N/A',
                $case['ep_remarks_feedback_trails'] ?? ''
            ]);
        } elseif ($caseType === 'ARBITRATION_OTHER') {
            $row = array_merge($row, [
                $case['ao_customer_name'] ?? 'N/A',
                !empty($case['ao_filing_amount']) ? '₹' . number_format($case['ao_filing_amount'], 2) : 'N/A',
                $case['ao_filing_location'] ?? 'N/A',
                $case['ao_court_no'] ?? 'N/A',
                $case['ao_advocate'] ?? 'N/A',
                $case['ao_poa'] ?? 'N/A',
                $case['ao_remarks_feedback_trails'] ?? ''
            ]);
        }
        
        fputcsv($output, $row);
    }
} else {
    // Multiple case types - export all in one file with common columns only
    // Add all case type labels in the header
    $headers = array_merge($commonHeaders, ['Case Type']);
    fputcsv($output, $headers);
    
    // Add all cases
    foreach ($typesToExport as $caseType => $casesOfType) {
        foreach ($casesOfType as $case) {
            $prev_date = $case['previous_position_date'] ?: $case['filing_date'];
            $prev_date_formatted = $prev_date ? date('d M, Y', strtotime($prev_date)) : 'N/A';
            
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
                $case['latest_position'] ?? 'No Updates',
                '₹' . number_format($case['total_fees'], 2),
                '₹' . number_format($case['balance_fees'], 2),
                $case['priority_status'] == 1 ? 'Priority' : 'Not Priority',
                $case['product'] ?? 'N/A',
                $case['branch_name'] ?? 'N/A',
                $case['region'] ?? 'N/A',
                $case['location'] ?? 'N/A',
                $case['remark'] ?? '',
                ucfirst($case['status']),
                ucwords(str_replace('-', ' ', $caseType))
            ];
            
            fputcsv($output, $row);
        }
    }
}

fclose($output);
exit();
