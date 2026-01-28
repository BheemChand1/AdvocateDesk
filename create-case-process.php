<?php
session_start();
require_once 'includes/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create-case.php");
    exit();
}

// Get common fields
$client_id = $_POST['client_id'] ?? null;
$case_type = $_POST['case_type'] ?? null;

// Validate required fields
if (!$client_id || !$case_type) {
    die("Error: Client ID and Case Type are required");
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Generate unique_case_id
    $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM cases");
    $countRow = mysqli_fetch_assoc($countResult);
    $nextNumber = $countRow['count'] + 1;
    $unique_case_id = "case" . $nextNumber;
    
    // Insert into main cases table
    $stmt = mysqli_prepare($conn, "
        INSERT INTO cases (
            unique_case_id, client_id, case_type, cnr_number, loan_number, product, 
            branch_name, location, region, complainant_authorised_person, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $cnr_number = $_POST['cnr_number'] ?? null;
    $loan_number = $_POST['loan_number'] ?? null;
    $product = $_POST['product'] ?? null;
    $branch_name = $_POST['branch_name'] ?? null;
    $location = $_POST['location'] ?? null;
    $region = $_POST['region'] ?? null;
    $complainant_authorised_person = $_POST['complainant_authorised_person'] ?? null;
    $created_by = $_SESSION['user_id'];
    
    mysqli_stmt_bind_param($stmt, "sissssssssi", 
        $unique_case_id, $client_id, $case_type, $cnr_number, $loan_number, $product,
        $branch_name, $location, $region, $complainant_authorised_person, $created_by
    );
    
    mysqli_stmt_execute($stmt);
    $case_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    // Insert parties based on case type
    insertParties($conn, $case_id, $case_type, $_POST);
    
    // Insert fee grid
    insertFeeGrid($conn, $case_id, $_POST);
    
    // Insert case type specific details
    switch ($case_type) {
        case 'NI_PASSA':
            insertNiPassaDetails($conn, $case_id, $_POST);
            break;
        case 'CRIMINAL':
            insertCriminalDetails($conn, $case_id, $_POST);
            break;
        case 'CONSUMER_CIVIL':
            insertConsumerCivilDetails($conn, $case_id, $_POST);
            break;
        case 'EP_ARBITRATION':
            insertEpArbitrationDetails($conn, $case_id, $_POST);
            break;
        case 'ARBITRATION_OTHER':
            insertArbitrationOtherDetails($conn, $case_id, $_POST);
            break;
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect to case details page
    header("Location: case-details.php?id=" . $case_id . "&success=1");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    die("Error creating case: " . $e->getMessage());
}

// Function to insert parties
function insertParties($conn, $case_id, $case_type, $data) {
    $stmt = mysqli_prepare($conn, "
        INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // Define party mappings based on case type - ONLY include name fields (addresses are derived automatically)
    $party_mappings = [
        'NI_PASSA' => [
            'complainant' => ['complainant_name'],
            'accused' => ['accused_name', 'additional_accused_name']
        ],
        'CRIMINAL' => [
            'complainant' => ['complainant_name', 'additional_complainant_name'],
            'accused' => ['accused_name', 'additional_accused_name']
        ],
        'CONSUMER_CIVIL' => [
            'complainant' => ['complainant_name', 'additional_complainant_name'],
            'defendant' => ['defendant_name', 'additional_defendant_name']
        ],
        'EP_ARBITRATION' => [
            'decree_holder' => ['decree_holder_client', 'additional_decree_holder_name'],
            'defendant' => ['customer_name_defendant', 'additional_defendant_name']
        ],
        'ARBITRATION_OTHER' => [
            'plaintiff' => ['plaintiff_name', 'additional_plaintiff_name'],
            'defendant' => ['defendant_name', 'additional_defendant_name']
        ]
    ];
    
    if (isset($party_mappings[$case_type])) {
        foreach ($party_mappings[$case_type] as $party_type => $fields) {
            $is_primary = true;
            
            foreach ($fields as $field) {
                if (strpos($field, 'additional_') === 0) {
                    // Handle array fields (additional parties)
                    $names = $data[$field] ?? [];
                    $address_field = str_replace('_name', '_address', $field);
                    $addresses = $data[$address_field] ?? [];
                    
                    if (is_array($names)) {
                        foreach ($names as $index => $name) {
                            if (!empty($name)) {
                                $name_safe = mysqli_real_escape_string($conn, $name);
                                $address_safe = mysqli_real_escape_string($conn, $addresses[$index] ?? '');
                                $is_primary_flag = 0;
                                $party_type_local = $party_type;
                                mysqli_stmt_bind_param($stmt, "isssi", $case_id, $party_type_local, $name_safe, $address_safe, $is_primary_flag);
                                mysqli_stmt_execute($stmt);
                            }
                        }
                    }
                } else {
                    // Handle single fields (primary parties)
                    $name = $data[$field] ?? null;
                    if (!empty($name)) {
                        $name_safe = mysqli_real_escape_string($conn, $name);
                        // Derive address field - handle special cases for non-standard field names
                        if ($field === 'decree_holder_client') {
                            $address_field = 'decree_holder_address';
                        } elseif ($field === 'customer_name_defendant') {
                            $address_field = 'customer_address';
                        } else {
                            $address_field = str_replace('_name', '_address', $field);
                        }
                        $address_safe = mysqli_real_escape_string($conn, $data[$address_field] ?? '');
                        $is_primary_flag = $is_primary ? 1 : 0;
                        $party_type_local = $party_type;
                        mysqli_stmt_bind_param($stmt, "isssi", $case_id, $party_type_local, $name_safe, $address_safe, $is_primary_flag);
                        mysqli_stmt_execute($stmt);
                        $is_primary = false; // Only first entry is primary
                    }
                }
            }
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Function to insert fee grid
function insertFeeGrid($conn, $case_id, $data) {
    $fee_names = $data['fee_grid_name'] ?? [];
    $fee_amounts = $data['fee_grid_amount'] ?? [];
    
    if (is_array($fee_names) && is_array($fee_amounts)) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO case_fee_grid (case_id, fee_name, fee_amount)
            VALUES (?, ?, ?)
        ");
        
        foreach ($fee_names as $index => $fee_name) {
            if (!empty($fee_name) && isset($fee_amounts[$index])) {
                $fee_amount = floatval($fee_amounts[$index]);
                mysqli_stmt_bind_param($stmt, "isd", $case_id, $fee_name, $fee_amount);
                mysqli_stmt_execute($stmt);
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Function to insert NI/PASSA specific details
function insertNiPassaDetails($conn, $case_id, $data) {
    // Assign values to variables first (PHP 8+ requirement)
    $accused_authorised_person = $data['accused_authorised_person'] ?? null;
    $cheque_no = $data['cheque_no'] ?? null;
    $cheque_date = $data['cheque_date'] ?? null;
    $total_no_of_chq = $data['total_no_of_chq'] ?? null;
    $cheque_amount = $data['cheque_amount'] ?? null;
    $filing_amount = $data['filing_amount'] ?? null;
    $bank_name_address = $data['bank_name_address'] ?? null;
    $cheque_holder_name = $data['cheque_holder_name'] ?? null;
    $cheque_status = $data['cheque_status'] ?? null;
    $bounce_date = $data['bounce_date'] ?? null;
    $bounce_reason = $data['bounce_reason'] ?? null;
    $notice_date = $data['notice_date'] ?? null;
    $notice_sent_date = $data['notice_sent_date'] ?? null;
    $filing_date = $data['filing_date'] ?? null;
    $filing_location = $data['filing_location'] ?? null;
    $case_no = $data['case_no'] ?? null;
    $court_no = $data['court_no'] ?? null;
    $court_name = $data['court_name'] ?? null;
    $section = $data['section'] ?? null;
    $act = $data['act'] ?? null;
    $poa_date = $data['poa_date'] ?? null;
    $last_date_update = $data['last_date_update'] ?? null;
    $current_stage = $data['current_stage'] ?? null;
    $remarks = $data['remarks'] ?? null;
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO case_ni_passa_details (
            case_id, accused_authorised_person, cheque_no, cheque_date, total_no_of_chq,
            cheque_amount, filing_amount, bank_name_address, cheque_holder_name, cheque_status,
            bounce_date, bounce_reason, notice_date, notice_sent_date, filing_date,
            filing_location, case_no, court_no, court_name, section, act,
            poa_date, last_date_update, current_stage, remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    mysqli_stmt_bind_param($stmt, "isssissssssssssssssssssss",
        $case_id, $accused_authorised_person, $cheque_no, $cheque_date, $total_no_of_chq,
        $cheque_amount, $filing_amount, $bank_name_address, $cheque_holder_name, $cheque_status,
        $bounce_date, $bounce_reason, $notice_date, $notice_sent_date, $filing_date,
        $filing_location, $case_no, $court_no, $court_name, $section, $act,
        $poa_date, $last_date_update, $current_stage, $remarks
    );
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Function to insert Criminal case specific details
function insertCriminalDetails($conn, $case_id, $data) {
    $case_type_specific = $data['case_type_specific'] ?? null;
    $section = $data['section'] ?? null;
    $act = $data['act'] ?? null;
    $police_station_with_district = $data['police_station_with_district'] ?? null;
    $crime_no_fir_no = $data['crime_no_fir_no'] ?? null;
    $fir_date = $data['fir_date'] ?? null;
    $charge_sheet_date = $data['charge_sheet_date'] ?? null;
    $notice_date = $data['notice_date'] ?? null;
    $poa_date = $data['poa_date'] ?? null;
    $filing_date = $data['filing_date'] ?? null;
    $filing_location = $data['filing_location'] ?? null;
    $case_no = $data['case_no'] ?? null;
    $court_no = $data['court_no'] ?? null;
    $court_name = $data['court_name'] ?? null;
    $remarks = $data['remarks'] ?? null;
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO case_criminal_details (
            case_id, case_type_specific, section, act, police_station_with_district,
            crime_no_fir_no, fir_date, charge_sheet_date, notice_date, poa_date,
            filing_date, filing_location, case_no, court_no, court_name, remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    mysqli_stmt_bind_param($stmt, "isssssssssssssss",
        $case_id, $case_type_specific, $section, $act, $police_station_with_district,
        $crime_no_fir_no, $fir_date, $charge_sheet_date, $notice_date, $poa_date,
        $filing_date, $filing_location, $case_no, $court_no, $court_name, $remarks
    );
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Function to insert Consumer/Civil case specific details
function insertConsumerCivilDetails($conn, $case_id, $data) {
    $case_type_specific = $data['case_type_specific'] ?? null;
    $case_filling_date = $data['case_filling_date'] ?? null;
    $legal_notice_date = $data['legal_notice_date'] ?? null;
    $case_vs_law_act = $data['case_vs_law_act'] ?? null;
    $swt_value = $data['swt_value'] ?? null;
    $filing_location = $data['filing_location'] ?? null;
    $court_name = $data['court_name'] ?? null;
    $case_no = $data['case_no'] ?? null;
    $court_no = $data['court_no'] ?? null;
    $advocate = $data['advocate'] ?? null;
    $poa = $data['poa'] ?? null;
    $remarks = $data['remarks'] ?? null;
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO case_consumer_civil_details (
            case_id, case_type_specific, case_filling_date, legal_notice_date,
            case_vs_law_act, swt_value, filing_location, court_name,
            case_no, court_no, advocate, poa, remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    mysqli_stmt_bind_param($stmt, "issssssssssss",
        $case_id, $case_type_specific, $case_filling_date, $legal_notice_date,
        $case_vs_law_act, $swt_value, $filing_location, $court_name,
        $case_no, $court_no, $advocate, $poa, $remarks
    );
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Function to insert EP/Arbitration case specific details
function insertEpArbitrationDetails($conn, $case_id, $data) {
    $filing_location = $data['filing_location'] ?? null;
    $case_no = $data['case_no'] ?? null;
    $court_no = $data['court_no'] ?? null;
    $advocate = $data['advocate'] ?? null;
    $poa = $data['poa'] ?? null;
    $date_of_filing = $data['date_of_filing'] ?? null;
    $customer_office_address = $data['customer_office_address'] ?? null;
    $award_date = $data['award_date'] ?? null;
    $arbitrator_name = $data['arbitrator_name'] ?? null;
    $arbitrator_address = $data['arbitrator_address'] ?? null;
    $arbitration_case_no = $data['arbitration_case_no'] ?? null;
    $interest_start_date = $data['interest_start_date'] ?? null;
    $interest_end_date = $data['interest_end_date'] ?? null;
    $total_days = $data['total_days'] ?? null;
    $award_amount = $data['award_amount'] ?? null;
    $rate_of_interest = $data['rate_of_interest'] ?? null;
    $interest_amount = $data['interest_amount'] ?? null;
    $cost = $data['cost'] ?? null;
    $recovery_amount = $data['recovery_amount'] ?? null;
    $claim_amount = $data['claim_amount'] ?? null;
    $vehicle_1_classification = $data['vehicle_1_classification'] ?? null;
    $vehicle_2_asset_description = $data['vehicle_2_asset_description'] ?? null;
    $vehicle_3_registration_number = $data['vehicle_3_registration_number'] ?? null;
    $vehicle_4_engine_no = $data['vehicle_4_engine_no'] ?? null;
    $vehicle_5_chasis_no = $data['vehicle_5_chasis_no'] ?? null;
    $immoveable_property_detail_1 = $data['immoveable_property_detail_1'] ?? null;
    $immoveable_property_detail_2 = $data['immoveable_property_detail_2'] ?? null;
    $immoveable_property_detail_3 = $data['immoveable_property_detail_3'] ?? null;
    $remarks_feedback_trails = $data['remarks_feedback_trails'] ?? null;
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO case_ep_arbitration_details (
            case_id, filing_location, case_no, court_no, advocate, poa, date_of_filing,
            customer_office_address, award_date, arbitrator_name, arbitrator_address,
            arbitration_case_no, interest_start_date, interest_end_date, total_days,
            award_amount, rate_of_interest, interest_amount, cost, recovery_amount,
            claim_amount, vehicle_1_classification, vehicle_2_asset_description,
            vehicle_3_registration_number, vehicle_4_engine_no, vehicle_5_chasis_no,
            immoveable_property_detail_1, immoveable_property_detail_2,
            immoveable_property_detail_3, remarks_feedback_trails
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Bind parameters: 30 total (i + 13s + i + 6d + 9s)
    mysqli_stmt_bind_param($stmt, "isssssssssssssiddddddsssssssss",
        $case_id, $filing_location, $case_no, $court_no, $advocate, $poa, $date_of_filing,
        $customer_office_address, $award_date, $arbitrator_name, $arbitrator_address,
        $arbitration_case_no, $interest_start_date, $interest_end_date, $total_days,
        $award_amount, $rate_of_interest, $interest_amount, $cost, $recovery_amount,
        $claim_amount, $vehicle_1_classification, $vehicle_2_asset_description,
        $vehicle_3_registration_number, $vehicle_4_engine_no, $vehicle_5_chasis_no,
        $immoveable_property_detail_1, $immoveable_property_detail_2,
        $immoveable_property_detail_3, $remarks_feedback_trails
    );
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Function to insert Arbitration Other case specific details
function insertArbitrationOtherDetails($conn, $case_id, $data) {
    $customer_name = $data['customer_name'] ?? null;
    $filing_amount = $data['filing_amount'] ?? null;
    $filing_date = $data['filing_date'] ?? null;
    $filing_location = $data['filing_location'] ?? null;
    $case_no = $data['case_no'] ?? null;
    $court_no = $data['court_no'] ?? null;
    $advocate = $data['advocate'] ?? null;
    $poa = $data['poa'] ?? null;
    $remarks_feedback_trails = $data['remarks_feedback_trails'] ?? null;
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO case_arbitration_other_details (
            case_id, customer_name, filing_amount, filing_date, filing_location,
            case_no, court_no, advocate, poa, remarks_feedback_trails
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    mysqli_stmt_bind_param($stmt, "isdsssssss",
        $case_id, $customer_name, $filing_amount, $filing_date, $filing_location,
        $case_no, $court_no, $advocate, $poa, $remarks_feedback_trails
    );
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
