<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get case ID from URL
$case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$case_id) {
    header("Location: view-cases.php");
    exit();
}

$message = '';
$message_type = '';

// Fetch main case data
$query = "SELECT c.*, cl.name as customer_name FROM cases c 
          LEFT JOIN clients cl ON c.client_id = cl.client_id
          WHERE c.id = ? AND c.case_type = 'CONSUMER_CIVIL'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);

if (!$case) {
    header("Location: view-cases.php");
    exit();
}

// Fetch CONSUMER_CIVIL specific details
$details_query = "SELECT * FROM case_consumer_civil_details WHERE case_id = ?";
$stmt = mysqli_prepare($conn, $details_query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case_details = mysqli_fetch_assoc($result);

// Fetch case stages
$stages_query = "SELECT * FROM case_stages ORDER BY display_order ASC";
$stages_result = mysqli_query($conn, $stages_query);
$stages = [];
while ($row = mysqli_fetch_assoc($stages_result)) {
    $stages[] = $row;
}
// Fetch case parties
$parties_query = "SELECT * FROM case_parties WHERE case_id = ? ORDER BY is_primary DESC, id ASC";
$stmt = mysqli_prepare($conn, $parties_query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case_parties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $case_parties[] = $row;
}
// Fetch case parties
$parties_query = "SELECT * FROM case_parties WHERE case_id = ? ORDER BY is_primary DESC, id ASC";
$stmt = mysqli_prepare($conn, $parties_query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case_parties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $case_parties[] = $row;
}

// Fetch case fee grid
$fee_query = "SELECT * FROM case_fee_grid WHERE case_id = ?";
$stmt = mysqli_prepare($conn, $fee_query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case_fees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $case_fees[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update main cases table
    $cnr_number = mysqli_real_escape_string($conn, $_POST['cnr_number'] ?? '');
    $loan_number = mysqli_real_escape_string($conn, $_POST['loan_number'] ?? '');
    $product = mysqli_real_escape_string($conn, $_POST['product'] ?? '');
    $branch_name = mysqli_real_escape_string($conn, $_POST['branch_name'] ?? '');
    $region = mysqli_real_escape_string($conn, $_POST['region'] ?? '');
    $complainant_authorised_person = mysqli_real_escape_string($conn, $_POST['complainant_authorised_person'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'pending');
    $case_stage_id = (!empty($_POST['case_stage_id'])) ? intval($_POST['case_stage_id']) : NULL;

    $update_sql = "UPDATE cases SET cnr_number = '$cnr_number', loan_number = '$loan_number', 
                  product = '$product', branch_name = '$branch_name', region = '$region', 
                  complainant_authorised_person = '$complainant_authorised_person', 
                  status = '$status'" . ($case_stage_id !== NULL ? ", case_stage_id = $case_stage_id" : "") . 
                  " WHERE id = $case_id";

    if (mysqli_query($conn, $update_sql)) {
        // Update CONSUMER_CIVIL specific details
        $case_type_specific = mysqli_real_escape_string($conn, $_POST['case_type_specific'] ?? '');
        $complainant_name = mysqli_real_escape_string($conn, $_POST['complainant_name'] ?? '');
        $complainant_address = mysqli_real_escape_string($conn, $_POST['complainant_address'] ?? '');
        $defendant_name = mysqli_real_escape_string($conn, $_POST['defendant_name'] ?? '');
        $defendant_address = mysqli_real_escape_string($conn, $_POST['defendant_address'] ?? '');
        $filing_location = mysqli_real_escape_string($conn, $_POST['filing_location'] ?? '');
        $court_name = mysqli_real_escape_string($conn, $_POST['court_name'] ?? '');
        $case_no = mysqli_real_escape_string($conn, $_POST['case_no'] ?? '');
        $court_no = mysqli_real_escape_string($conn, $_POST['court_no'] ?? '');
        $advocate = mysqli_real_escape_string($conn, $_POST['advocate'] ?? '');
        $poa = mysqli_real_escape_string($conn, $_POST['poa'] ?? '');
        $case_filling_date = mysqli_real_escape_string($conn, $_POST['case_filling_date'] ?? '');
        $legal_notice_date = mysqli_real_escape_string($conn, $_POST['legal_notice_date'] ?? '');
        $case_vs_law_act = mysqli_real_escape_string($conn, $_POST['case_vs_law_act'] ?? '');
        $swt_value = mysqli_real_escape_string($conn, $_POST['swt_value'] ?? '');

        if ($case_details) {
            // Update existing details
            $details_sql = "UPDATE case_consumer_civil_details SET case_type_specific = '$case_type_specific',
                           complainant_name = '$complainant_name', complainant_address = '$complainant_address',
                           defendant_name = '$defendant_name', defendant_address = '$defendant_address',
                           filing_location = '$filing_location', court_name = '$court_name', case_no = '$case_no',
                           court_no = '$court_no', advocate = '$advocate', poa = '$poa',
                           case_filling_date = '$case_filling_date', legal_notice_date = '$legal_notice_date',
                           case_vs_law_act = '$case_vs_law_act', swt_value = '$swt_value'
                           WHERE case_id = $case_id";
        } else {
            // Insert new details
            $details_sql = "INSERT INTO case_consumer_civil_details (case_id, case_type_specific, complainant_name,
                           complainant_address, defendant_name, defendant_address, filing_location, court_name,
                           case_no, court_no, advocate, poa, case_filling_date, legal_notice_date,
                           case_vs_law_act, swt_value)
                           VALUES ($case_id, '$case_type_specific', '$complainant_name', '$complainant_address',
                           '$defendant_name', '$defendant_address', '$filing_location', '$court_name',
                           '$case_no', '$court_no', '$advocate', '$poa', '$case_filling_date',
                           '$legal_notice_date', '$case_vs_law_act', '$swt_value')";
        }

        if (mysqli_query($conn, $details_sql)) {
            // Update parties
            // Only update if we have valid party data
            $has_parties = false;
            
            // Check if we have any party data to process
            $complainant_name = mysqli_real_escape_string($conn, $_POST['complainant_name'] ?? '');
            $defendant_name = mysqli_real_escape_string($conn, $_POST['defendant_name'] ?? '');
            $additional_defendant_names = $_POST['additional_defendant_name'] ?? [];
            
            // Count non-empty additional defendants
            $additional_defendant_count = 0;
            if (is_array($additional_defendant_names)) {
                foreach ($additional_defendant_names as $name) {
                    if (!empty($name)) {
                        $additional_defendant_count++;
                    }
                }
            }
            
            // Only delete and recreate if we have parties to save
            if (!empty($complainant_name) || !empty($defendant_name) || $additional_defendant_count > 0) {
                $has_parties = true;
            }
            
            if ($has_parties) {
                // Delete existing parties only if we're replacing them
                mysqli_query($conn, "DELETE FROM case_parties WHERE case_id = $case_id");
                
                // Process Complainant
                if (!empty($complainant_name)) {
                    $complainant_name_safe = mysqli_real_escape_string($conn, $complainant_name);
                    $complainant_address_safe = mysqli_real_escape_string($conn, $_POST['complainant_address'] ?? '');
                    $stmt_party = mysqli_prepare($conn, "
                        INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $is_primary = 1;
                    $party_type = 'complainant';
                    mysqli_stmt_bind_param($stmt_party, "isssi", $case_id, $party_type, $complainant_name_safe, $complainant_address_safe, $is_primary);
                    mysqli_stmt_execute($stmt_party);
                    mysqli_stmt_close($stmt_party);
                }
                
                // Process Additional Complainants
                $additional_complainant_names = $_POST['additional_complainant_name'] ?? [];
                $additional_complainant_addresses = $_POST['additional_complainant_address'] ?? [];
                if (is_array($additional_complainant_names)) {
                    foreach ($additional_complainant_names as $index => $add_name) {
                        if (!empty($add_name)) {
                            $add_name_safe = mysqli_real_escape_string($conn, $add_name);
                            $add_address_safe = mysqli_real_escape_string($conn, $additional_complainant_addresses[$index] ?? '');
                            $stmt_party = mysqli_prepare($conn, "
                                INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $is_primary = 0;
                            $party_type = 'complainant';
                            mysqli_stmt_bind_param($stmt_party, "isssi", $case_id, $party_type, $add_name_safe, $add_address_safe, $is_primary);
                            mysqli_stmt_execute($stmt_party);
                            mysqli_stmt_close($stmt_party);
                        }
                    }
                }
                
                // Process Defendant
                if (!empty($defendant_name)) {
                    $defendant_name_safe = mysqli_real_escape_string($conn, $defendant_name);
                    $defendant_address_safe = mysqli_real_escape_string($conn, $_POST['defendant_address'] ?? '');
                    $stmt_party = mysqli_prepare($conn, "
                        INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $is_primary = 1;
                    $party_type = 'defendant';
                    mysqli_stmt_bind_param($stmt_party, "isssi", $case_id, $party_type, $defendant_name_safe, $defendant_address_safe, $is_primary);
                    mysqli_stmt_execute($stmt_party);
                    mysqli_stmt_close($stmt_party);
                }
                
                // Process Additional Defendants
                $additional_defendant_addresses = $_POST['additional_defendant_address'] ?? [];
                if (is_array($additional_defendant_names)) {
                    foreach ($additional_defendant_names as $index => $add_name) {
                        if (!empty($add_name)) {
                            $add_name_safe = mysqli_real_escape_string($conn, $add_name);
                            $add_address_safe = mysqli_real_escape_string($conn, $additional_defendant_addresses[$index] ?? '');
                            $stmt_party = mysqli_prepare($conn, "
                                INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $is_primary = 0;
                            $party_type = 'defendant';
                            mysqli_stmt_bind_param($stmt_party, "isssi", $case_id, $party_type, $add_name_safe, $add_address_safe, $is_primary);
                            mysqli_stmt_execute($stmt_party);
                            mysqli_stmt_close($stmt_party);
                        }
                    }
                }
            }
            
            // Update fee grid
            // Delete existing fees
            mysqli_query($conn, "DELETE FROM case_fee_grid WHERE case_id = $case_id");
            
            // Insert new fees
            if (!empty($_POST['fee_grid_name']) && is_array($_POST['fee_grid_name'])) {
                $fee_names = $_POST['fee_grid_name'];
                $fee_amounts = $_POST['fee_grid_amount'] ?? [];
                
                for ($i = 0; $i < count($fee_names); $i++) {
                    $fee_name = mysqli_real_escape_string($conn, $fee_names[$i]);
                    $fee_amount = !empty($fee_amounts[$i]) ? floatval($fee_amounts[$i]) : 0;
                    
                    if (!empty($fee_name)) {
                        $fee_sql = "INSERT INTO case_fee_grid (case_id, fee_name, fee_amount) VALUES ($case_id, '$fee_name', $fee_amount)";
                        mysqli_query($conn, $fee_sql);
                    }
                }
            }

            $message = "Case details updated successfully!";
            $message_type = "success";
            
            // Refresh case data
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $case_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $case = mysqli_fetch_assoc($result);

            // Refresh case details
            $stmt = mysqli_prepare($conn, $details_query);
            mysqli_stmt_bind_param($stmt, "i", $case_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $case_details = mysqli_fetch_assoc($result);

            // Refresh fees
            $stmt = mysqli_prepare($conn, $fee_query);
            mysqli_stmt_bind_param($stmt, "i", $case_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $case_fees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $case_fees[] = $row;
            }
        } else {
            $message = "Error updating case details: " . mysqli_error($conn);
            $message_type = "error";
        }
    } else {
        $message = "Error updating case: " . mysqli_error($conn);
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Consumer Civil Case - Case Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/style.css">
</head>

<body class="bg-gradient-to-br from-gray-200 via-gray-100 to-slate-200 min-h-screen">
    <?php include './includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <?php include './includes/navbar.php'; ?>

        <main class="px-4 sm:px-6 lg:px-8 py-4">
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between">
                <a href="case-details-consumer-civil.php?id=<?php echo $case_id; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-edit text-green-500 mr-2"></i>Edit Consumer/Civil Case
                </h1>
            </div>

            <!-- Message Alert -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST">

                    <!-- Case Type Display -->
                    <div class="mb-6 pb-4 border-b-2 border-green-200 bg-green-50 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-balance-scale text-green-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Case Category:</span>
                                <h2 class="text-lg font-bold text-gray-800">CONSUMER/CIVIL/REVENUE/RERA/FAMILY COURT</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Client Information (Read-only) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-user text-blue-500 mr-2"></i>Client Information
                        </h2>
                        <div class="bg-green-50 border border-green-300 rounded-lg p-3">
                            <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($case['customer_name']); ?></p>
                            <p class="text-xs text-gray-600">Case ID: <?php echo htmlspecialchars($case['unique_case_id']); ?></p>
                        </div>
                    </div>

                    <!-- CASE DETAILS Section -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-green-600 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-file-alt mr-2"></i>CASE DETAILS
                        </h2>
                        <div class="border-2 border-green-600 rounded-b-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">CNR NUMBER</label>
                                    <input type="text" name="cnr_number" value="<?php echo htmlspecialchars($case['cnr_number'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter CNR number">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Loan Number</label>
                                    <input type="text" name="loan_number" value="<?php echo htmlspecialchars($case['loan_number'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter loan number">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Product</label>
                                    <input type="text" name="product" value="<?php echo htmlspecialchars($case['product'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter product">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Branch Name</label>
                                    <input type="text" name="branch_name" value="<?php echo htmlspecialchars($case['branch_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter branch name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1 bg-red-600 text-white px-2 py-1 rounded">Case Type (drop down)</label>
                                    <select name="case_type_specific" class="w-full px-3 py-2 border-2 border-red-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white">
                                        <option value="">Select case type</option>
                                        <option value="DIST_CONS" <?php echo ($case_details['case_type_specific'] ?? '') === 'DIST_CONS' ? 'selected' : ''; ?>>DIST. CONS.</option>
                                        <option value="ORIGINAL_SUIT" <?php echo ($case_details['case_type_specific'] ?? '') === 'ORIGINAL_SUIT' ? 'selected' : ''; ?>>ORIGINAL SUIT</option>
                                        <option value="STATE_CONS" <?php echo ($case_details['case_type_specific'] ?? '') === 'STATE_CONS' ? 'selected' : ''; ?>>STATE-CONS.</option>
                                        <option value="RC" <?php echo ($case_details['case_type_specific'] ?? '') === 'RC' ? 'selected' : ''; ?>>RC</option>
                                        <option value="OA" <?php echo ($case_details['case_type_specific'] ?? '') === 'OA' ? 'selected' : ''; ?>>OA</option>
                                        <option value="SA" <?php echo ($case_details['case_type_specific'] ?? '') === 'SA' ? 'selected' : ''; ?>>SA</option>
                                        <option value="DIVORCE" <?php echo ($case_details['case_type_specific'] ?? '') === 'DIVORCE' ? 'selected' : ''; ?>>Divorce</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Region</label>
                                    <input type="text" name="region" value="<?php echo htmlspecialchars($case['region'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter region">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant/Authorised person</label>
                                    <input type="text" name="complainant_authorised_person" value="<?php echo htmlspecialchars($case['complainant_authorised_person'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter complainant/authorised person">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Filing_Location</label>
                                    <input type="text" name="filing_location" value="<?php echo htmlspecialchars($case_details['filing_location'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing location">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Court_Name</label>
                                    <input type="text" name="court_name" value="<?php echo htmlspecialchars($case_details['court_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Case_No</label>
                                    <input type="text" name="case_no" value="<?php echo htmlspecialchars($case_details['case_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter case number">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Court_No</label>
                                    <input type="text" name="court_no" value="<?php echo htmlspecialchars($case_details['court_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court number">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Advocate</label>
                                    <input type="text" name="advocate" value="<?php echo htmlspecialchars($case_details['advocate'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter advocate name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Poa</label>
                                    <input type="text" name="poa" value="<?php echo htmlspecialchars($case_details['poa'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter POA">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Case Filling Date</label>
                                    <input type="date" name="case_filling_date" value="<?php echo htmlspecialchars($case_details['case_filling_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Legal Notice Sent/Received Date</label>
                                    <input type="date" name="legal_notice_date" value="<?php echo htmlspecialchars($case_details['legal_notice_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Case v/s with Relevant Law/Act</label>
                                    <input type="text" name="case_vs_law_act" value="<?php echo htmlspecialchars($case_details['case_vs_law_act'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter case v/s with relevant law/act">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">SWT Value</label>
                                    <input type="text" name="swt_value" value="<?php echo htmlspecialchars($case_details['swt_value'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter SWT value">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Complainant Section with Party Management -->
                    <?php
                    $primary_complainant = null;
                    $additional_complainants = [];
                    $primary_defendant = null;
                    $additional_defendants = [];
                    
                    foreach ($case_parties as $party) {
                        if ($party['party_type'] === 'complainant') {
                            if ($party['is_primary']) {
                                $primary_complainant = $party;
                            } else {
                                $additional_complainants[] = $party;
                            }
                        } elseif ($party['party_type'] === 'defendant') {
                            if ($party['is_primary']) {
                                $primary_defendant = $party;
                            } else {
                                $additional_defendants[] = $party;
                            }
                        }
                    }
                    ?>
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-blue-500 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-user-shield mr-2"></i>Complainant
                        </h2>
                        <div class="border-2 border-blue-500 rounded-b-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant</label>
                                    <input type="text" name="complainant_name" value="<?php echo htmlspecialchars($primary_complainant['name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant ADDRESS</label>
                                    <input type="text" name="complainant_address" value="<?php echo htmlspecialchars($primary_complainant['address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant address">
                                </div>
                            </div>
                            
                            <!-- Additional Complainants -->
                            <div id="additionalComplainants" class="mt-4">
                                <?php foreach ($additional_complainants as $complainant): ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-3 bg-white rounded border border-blue-200">
                                    <div>
                                        <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Name</label>
                                        <input type="text" name="additional_complainant_name[]" value="<?php echo htmlspecialchars($complainant['name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant name">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Address</label>
                                        <input type="text" name="additional_complainant_address[]" value="<?php echo htmlspecialchars($complainant['address']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant address">
                                    </div>
                                    <div class="md:col-span-2">
                                        <button type="button" class="remove-complainant px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                            <i class="fas fa-trash mr-2"></i>Remove
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" id="addMoreComplainant" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                <i class="fas fa-plus mr-2"></i>Add More Complainant
                            </button>
                        </div>
                    </div>

                    <!-- Defendant Section with Party Management -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-blue-500 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-users mr-2"></i>Defendant
                        </h2>
                        <div class="border-2 border-blue-500 rounded-b-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">DEFENDANT</label>
                                    <input type="text" name="defendant_name" value="<?php echo htmlspecialchars($primary_defendant['name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter defendant name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">DEFENDANT ADDRESS</label>
                                    <input type="text" name="defendant_address" value="<?php echo htmlspecialchars($primary_defendant['address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter defendant address">
                                </div>
                            </div>
                            
                            <!-- Additional Defendants -->
                            <div id="additionalDefendants" class="mt-4">
                                <?php foreach ($additional_defendants as $defendant): ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-3 bg-white rounded border border-blue-200">
                                    <div>
                                        <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Name</label>
                                        <input type="text" name="additional_defendant_name[]" value="<?php echo htmlspecialchars($defendant['name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter defendant name">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Address</label>
                                        <input type="text" name="additional_defendant_address[]" value="<?php echo htmlspecialchars($defendant['address']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter defendant address">
                                    </div>
                                    <div class="md:col-span-2">
                                        <button type="button" class="remove-defendant px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                            <i class="fas fa-trash mr-2"></i>Remove
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" id="addMoreDefendant" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                <i class="fas fa-plus mr-2"></i>Add More Defendant
                            </button>
                        </div>
                    </div>

                    <!-- Case Status -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-tasks text-blue-500 mr-2"></i>Case Status
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="pending" <?php echo ($case['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo ($case['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="closed" <?php echo ($case['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case Stage</label>
                                <select name="case_stage_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select a stage</option>
                                    <?php foreach ($stages as $stage): ?>
                                    <option value="<?php echo $stage['id']; ?>" <?php echo $case['case_stage_id'] == $stage['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($stage['stage_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Grid -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Fee Grid
                        </h2>
                        <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-4">
                            <div class="overflow-x-auto">
                                <table class="w-full" id="feeGridTable">
                                    <thead>
                                        <tr class="bg-yellow-400 text-gray-900">
                                            <th class="px-4 py-2 text-left font-bold">FEE GRID</th>
                                            <th class="px-4 py-2 text-right font-bold">AMOUNT</th>
                                            <th class="px-4 py-2 w-20"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="feeGridBody">
                                        <?php if (!empty($case_fees)): ?>
                                            <?php foreach ($case_fees as $fee): ?>
                                            <tr class="border-b border-yellow-300">
                                                <td class="px-4 py-2">
                                                    <input type="text" name="fee_grid_name[]" value="<?php echo htmlspecialchars($fee['fee_name']); ?>" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                </td>
                                                <td class="px-4 py-2">
                                                    <input type="number" name="fee_grid_amount[]" value="<?php echo htmlspecialchars($fee['fee_amount']); ?>" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                </td>
                                                <td class="px-4 py-2 text-center">
                                                    <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="border-b border-yellow-300">
                                                <td class="px-4 py-2">
                                                    <input type="text" name="fee_grid_name[]" value="Filing/appearance" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                </td>
                                                <td class="px-4 py-2">
                                                    <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                </td>
                                                <td class="px-4 py-2 text-center">
                                                    <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                <button type="button" id="addMoreFee" class="px-4 py-2 bg-yellow-500 text-gray-900 font-bold rounded-lg hover:bg-yellow-600 transition">
                                    <i class="fas fa-plus mr-2"></i>Add more option
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-6 border-t border-gray-200">
                        <a href="case-details-consumer-civil.php?id=<?php echo $case_id; ?>"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="reset"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                            <i class="fas fa-save mr-2"></i>Update Case
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add More Complainant Functionality
            document.getElementById('addMoreComplainant').addEventListener('click', function(e) {
                e.preventDefault();
                const container = document.getElementById('additionalComplainants');
                const newRow = document.createElement('div');
                newRow.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-3 bg-white rounded border border-blue-200';
                newRow.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Name</label>
                        <input type="text" name="additional_complainant_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Address</label>
                        <input type="text" name="additional_complainant_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant address">
                    </div>
                    <div class="md:col-span-2">
                        <button type="button" class="remove-complainant px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                            <i class="fas fa-trash mr-2"></i>Remove
                        </button>
                    </div>
                `;
                container.appendChild(newRow);
                
                // Add remove functionality
                newRow.querySelector('.remove-complainant').addEventListener('click', function(e) {
                    e.preventDefault();
                    newRow.remove();
                });
            });

            // Add More Defendant Functionality
            document.getElementById('addMoreDefendant').addEventListener('click', function(e) {
                e.preventDefault();
                const container = document.getElementById('additionalDefendants');
                const newRow = document.createElement('div');
                newRow.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-3 bg-white rounded border border-blue-200';
                newRow.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Name</label>
                        <input type="text" name="additional_defendant_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter defendant name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Address</label>
                        <input type="text" name="additional_defendant_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter defendant address">
                    </div>
                    <div class="md:col-span-2">
                        <button type="button" class="remove-defendant px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                            <i class="fas fa-trash mr-2"></i>Remove
                        </button>
                    </div>
                `;
                container.appendChild(newRow);
                
                // Add remove functionality
                newRow.querySelector('.remove-defendant').addEventListener('click', function(e) {
                    e.preventDefault();
                    newRow.remove();
                });
            });

            // Add remove functionality to existing complainant rows
            document.querySelectorAll('.remove-complainant').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.closest('div').remove();
                });
            });

            // Add remove functionality to existing defendant rows
            document.querySelectorAll('.remove-defendant').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.closest('div').remove();
                });
            });

            // Add More Fee Grid Row Functionality
            document.getElementById('addMoreFee').addEventListener('click', function() {
                const tbody = document.getElementById('feeGridBody');
                const newRow = document.createElement('tr');
                newRow.className = 'border-b border-yellow-300';
                newRow.innerHTML = `
                    <td class="px-4 py-2">
                        <input type="text" name="fee_grid_name[]" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter fee name">
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-2 text-center">
                        <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(newRow);

                // Add remove functionality
                newRow.querySelector('.remove-fee').addEventListener('click', function(e) {
                    e.preventDefault();
                    newRow.remove();
                });
            });

            // Add remove functionality to existing fee grid rows
            document.querySelectorAll('.remove-fee').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.closest('tr').remove();
                });
            });
        });
    </script>

</html>
