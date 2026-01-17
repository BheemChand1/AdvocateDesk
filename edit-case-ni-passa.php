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
          WHERE c.id = ? AND c.case_type = 'NI_PASSA'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);

if (!$case) {
    header("Location: view-cases.php");
    exit();
}

// Fetch NI/PASSA specific details
$details_query = "SELECT * FROM case_ni_passa_details WHERE case_id = ?";
$stmt = mysqli_prepare($conn, $details_query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case_details = mysqli_fetch_assoc($result);

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

// Fetch case stages
$stages_query = "SELECT * FROM case_stages ORDER BY display_order ASC";
$stages_result = mysqli_query($conn, $stages_query);
$stages = [];
while ($row = mysqli_fetch_assoc($stages_result)) {
    $stages[] = $row;
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
    $loan_number = mysqli_real_escape_string($conn, $_POST['loan_number'] ?? '');
    $cnr_number = mysqli_real_escape_string($conn, $_POST['cnr_number'] ?? '');
    $product = mysqli_real_escape_string($conn, $_POST['product'] ?? '');
    $branch_name = mysqli_real_escape_string($conn, $_POST['branch_name'] ?? '');
    $location = mysqli_real_escape_string($conn, $_POST['location'] ?? '');
    $region = mysqli_real_escape_string($conn, $_POST['region'] ?? '');
    $complainant_authorised_person = mysqli_real_escape_string($conn, $_POST['complainant_authorised_person'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'pending');
    $case_stage_id = (!empty($_POST['case_stage_id'])) ? intval($_POST['case_stage_id']) : NULL;

    $update_sql = "UPDATE cases SET loan_number = '$loan_number', cnr_number = '$cnr_number', 
                  product = '$product', branch_name = '$branch_name', location = '$location', 
                  region = '$region', complainant_authorised_person = '$complainant_authorised_person', 
                  status = '$status'" . ($case_stage_id !== NULL ? ", case_stage_id = $case_stage_id" : "") . 
                  " WHERE id = $case_id";

    if (mysqli_query($conn, $update_sql)) {
        // Update NI/PASSA specific details
        $accused_authorised_person = mysqli_real_escape_string($conn, $_POST['accused_authorised_person'] ?? '');
        $cheque_no = mysqli_real_escape_string($conn, $_POST['cheque_no'] ?? '');
        $cheque_date = mysqli_real_escape_string($conn, $_POST['cheque_date'] ?? '');
        $total_no_of_chq = !empty($_POST['total_no_of_chq']) ? intval($_POST['total_no_of_chq']) : 0;
        $cheque_amount = !empty($_POST['cheque_amount']) ? floatval($_POST['cheque_amount']) : 0;
        $filing_amount = !empty($_POST['filing_amount']) ? floatval($_POST['filing_amount']) : 0;
        $bank_name_address = mysqli_real_escape_string($conn, $_POST['bank_name_address'] ?? '');
        $cheque_holder_name = mysqli_real_escape_string($conn, $_POST['cheque_holder_name'] ?? '');
        $cheque_status = mysqli_real_escape_string($conn, $_POST['cheque_status'] ?? '');
        $bounce_date = mysqli_real_escape_string($conn, $_POST['bounce_date'] ?? '');
        $bounce_reason = mysqli_real_escape_string($conn, $_POST['bounce_reason'] ?? '');
        $notice_date = mysqli_real_escape_string($conn, $_POST['notice_date'] ?? '');
        $notice_sent_date = mysqli_real_escape_string($conn, $_POST['notice_sent_date'] ?? '');
        $filing_date = mysqli_real_escape_string($conn, $_POST['filing_date'] ?? '');
        $filing_location = mysqli_real_escape_string($conn, $_POST['filing_location'] ?? '');
        $case_no = mysqli_real_escape_string($conn, $_POST['case_no'] ?? '');
        $court_no = mysqli_real_escape_string($conn, $_POST['court_no'] ?? '');
        $court_name = mysqli_real_escape_string($conn, $_POST['court_name'] ?? '');
        $section = mysqli_real_escape_string($conn, $_POST['section'] ?? '');
        $act = mysqli_real_escape_string($conn, $_POST['act'] ?? '');
        $poa_date = mysqli_real_escape_string($conn, $_POST['poa_date'] ?? '');
        $last_date_update = mysqli_real_escape_string($conn, $_POST['last_date_update'] ?? '');
        $current_stage = mysqli_real_escape_string($conn, $_POST['current_stage'] ?? '');
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');

        if ($case_details) {
            // Update existing details
            $details_sql = "UPDATE case_ni_passa_details SET accused_authorised_person = '$accused_authorised_person',
                           cheque_no = '$cheque_no', cheque_date = '$cheque_date', total_no_of_chq = $total_no_of_chq,
                           cheque_amount = $cheque_amount, filing_amount = $filing_amount, 
                           bank_name_address = '$bank_name_address', cheque_holder_name = '$cheque_holder_name',
                           cheque_status = '$cheque_status', bounce_date = '$bounce_date', 
                           bounce_reason = '$bounce_reason', notice_date = '$notice_date',
                           notice_sent_date = '$notice_sent_date', filing_date = '$filing_date',
                           filing_location = '$filing_location', case_no = '$case_no', court_no = '$court_no',
                           court_name = '$court_name', section = '$section', act = '$act', 
                           poa_date = '$poa_date', last_date_update = '$last_date_update',
                           current_stage = '$current_stage', remarks = '$remarks'
                           WHERE case_id = $case_id";
        } else {
            // Insert new details
            $details_sql = "INSERT INTO case_ni_passa_details (case_id, accused_authorised_person, cheque_no,
                           cheque_date, total_no_of_chq, cheque_amount, filing_amount, bank_name_address,
                           cheque_holder_name, cheque_status, bounce_date, bounce_reason, notice_date,
                           notice_sent_date, filing_date, filing_location, case_no, court_no, court_name,
                           section, act, poa_date, last_date_update, current_stage, remarks)
                           VALUES ($case_id, '$accused_authorised_person', '$cheque_no', '$cheque_date',
                           $total_no_of_chq, $cheque_amount, $filing_amount, '$bank_name_address',
                           '$cheque_holder_name', '$cheque_status', '$bounce_date', '$bounce_reason',
                           '$notice_date', '$notice_sent_date', '$filing_date', '$filing_location',
                           '$case_no', '$court_no', '$court_name', '$section', '$act', '$poa_date',
                           '$last_date_update', '$current_stage', '$remarks')";
        }

        if (mysqli_query($conn, $details_sql)) {
            // Update parties
            // Only update if we have valid party data
            $has_parties = false;
            
            // Check if we have any party data to process
            $complainant_name = mysqli_real_escape_string($conn, $_POST['complainant_name'] ?? '');
            $accused_name = mysqli_real_escape_string($conn, $_POST['accused_name'] ?? '');
            $additional_accused_names = $_POST['additional_accused_name'] ?? [];
            
            // Count non-empty additional accused
            $additional_accused_count = 0;
            if (is_array($additional_accused_names)) {
                foreach ($additional_accused_names as $name) {
                    if (!empty($name)) {
                        $additional_accused_count++;
                    }
                }
            }
            
            // Only delete and recreate if we have parties to save
            if (!empty($complainant_name) || !empty($accused_name) || $additional_accused_count > 0) {
                $has_parties = true;
            }
            
            if ($has_parties) {
                // Delete existing parties only if we're replacing them
                mysqli_query($conn, "DELETE FROM case_parties WHERE case_id = $case_id");
                
                // Process Complainant
                $complainant_address = mysqli_real_escape_string($conn, $_POST['complainant_address'] ?? '');
                if (!empty($complainant_name)) {
                    $stmt_party = mysqli_prepare($conn, "
                        INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $is_primary = 1;
                    $party_type = 'complainant';
                    mysqli_stmt_bind_param($stmt_party, "isssi", $case_id, $party_type, $complainant_name, $complainant_address, $is_primary);
                    mysqli_stmt_execute($stmt_party);
                    mysqli_stmt_close($stmt_party);
                }
                
                // Process Additional Complainants
                $additional_complainant_names = $_POST['additional_complainant_name'] ?? [];
                $additional_complainant_addresses = $_POST['additional_complainant_address'] ?? [];
                if (is_array($additional_complainant_names)) {
                    foreach ($additional_complainant_names as $index => $add_name) {
                        if (!empty($add_name)) {
                            $stmt_party = mysqli_prepare($conn, "
                                INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $add_address = $additional_complainant_addresses[$index] ?? '';
                            $add_name = mysqli_real_escape_string($conn, $add_name);
                            $add_address = mysqli_real_escape_string($conn, $add_address);
                            $is_primary = 0;
                            $party_type = 'complainant';
                            mysqli_stmt_bind_param($stmt_party, "isssi", $case_id, $party_type, $add_name, $add_address, $is_primary);
                            mysqli_stmt_execute($stmt_party);
                            mysqli_stmt_close($stmt_party);
                        }
                    }
                }
                
                // Process Accused
                $accused_address = mysqli_real_escape_string($conn, $_POST['accused_address'] ?? '');
                if (!empty($accused_name)) {
                    $stmt_party = mysqli_prepare($conn, "
                        INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $is_primary = 1;
                    $party_type = 'accused';
                    mysqli_stmt_bind_param($stmt_party, "isssi", $case_id, $party_type, $accused_name, $accused_address, $is_primary);
                    mysqli_stmt_execute($stmt_party);
                    mysqli_stmt_close($stmt_party);
                }
                
                // Process Additional Accused
                $additional_accused_addresses = $_POST['additional_accused_address'] ?? [];
                if (is_array($additional_accused_names)) {
                    foreach ($additional_accused_names as $index => $add_name) {
                        if (!empty($add_name)) {
                            $stmt_party = mysqli_prepare($conn, "
                                INSERT INTO case_parties (case_id, party_type, name, address, is_primary)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $add_address = $additional_accused_addresses[$index] ?? '';
                            $add_name = mysqli_real_escape_string($conn, $add_name);
                            $add_address = mysqli_real_escape_string($conn, $add_address);
                            $is_primary = 0;
                            $party_type = 'accused';
                            mysqli_stmt_bind_param($stmt_party, "isssi", $case_id, $party_type, $add_name, $add_address, $is_primary);
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

            // Refresh parties
            $stmt = mysqli_prepare($conn, $parties_query);
            mysqli_stmt_bind_param($stmt, "i", $case_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $case_parties = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $case_parties[] = $row;
            }

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
    <title>Edit NI/PASSA Case - Case Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/style.css">
</head>

<body class="bg-gradient-to-br from-gray-200 via-gray-100 to-slate-200 min-h-screen">
    <?php include './includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <?php include './includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="px-4 sm:px-6 lg:px-8 py-4">
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between">
                <a href="case-details-ni-passa.php?id=<?php echo $case_id; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-edit text-green-500 mr-2"></i>Edit NI/PASSA Case
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
                    <div class="mb-6 pb-4 border-b-2 border-blue-200 bg-blue-50 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-file-alt text-blue-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Case Type:</span>
                                <h2 class="text-lg font-bold text-gray-800">NI/PASSA</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Client Information (Read-only) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-user text-blue-500 mr-2"></i>Client Information
                        </h2>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="bg-green-50 border border-green-300 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($case['customer_name']); ?></p>
                                        <p class="text-xs text-gray-600">Case ID: <?php echo htmlspecialchars($case['unique_case_id']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>Basic Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Location</label>
                                <input type="text" name="location" value="<?php echo htmlspecialchars($case['location'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter location">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Region</label>
                                <input type="text" name="region" value="<?php echo htmlspecialchars($case['region'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter region">
                            </div>
                        </div>
                    </div>

                    <!-- Complainant & Accused Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-users text-blue-500 mr-2"></i>Complainant & Accused Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php
                            // Get primary and additional complainants
                            $primary_complainant = null;
                            $additional_complainants = [];
                            foreach ($case_parties as $party) {
                                if ($party['party_type'] === 'complainant') {
                                    if ($party['is_primary']) {
                                        $primary_complainant = $party;
                                    } else {
                                        $additional_complainants[] = $party;
                                    }
                                }
                            }
                            ?>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Name</label>
                                <input type="text" name="complainant_name" value="<?php echo htmlspecialchars($primary_complainant['name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter complainant name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Address</label>
                                <input type="text" name="complainant_address" value="<?php echo htmlspecialchars($primary_complainant['address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter complainant address">
                            </div>
                            <?php
                            // Get primary and additional accused
                            $primary_accused = null;
                            $additional_accused = [];
                            foreach ($case_parties as $party) {
                                if ($party['party_type'] === 'accused') {
                                    if ($party['is_primary']) {
                                        $primary_accused = $party;
                                    } else {
                                        $additional_accused[] = $party;
                                    }
                                }
                            }
                            ?>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Name</label>
                                <input type="text" name="accused_name" value="<?php echo htmlspecialchars($primary_accused['name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter accused name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Address</label>
                                <input type="text" name="accused_address" value="<?php echo htmlspecialchars($primary_accused['address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter accused address">
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" id="addMoreAccused" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                    <i class="fas fa-plus mr-2"></i>Add More Accused
                                </button>
                                <div id="additionalAccused" class="mt-4 space-y-3">
                                    <?php foreach ($additional_accused as $accused): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg relative">
                                        <div>
                                            <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Name</label>
                                            <input type="text" name="additional_accused_name[]" value="<?php echo htmlspecialchars($accused['name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter accused name">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Address</label>
                                            <input type="text" name="additional_accused_address[]" value="<?php echo htmlspecialchars($accused['address']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter accused address">
                                        </div>
                                        <button type="button" class="remove-accused absolute top-2 right-2 text-red-500 hover:text-red-700">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Authorised Person</label>
                                <input type="text" name="complainant_authorised_person" value="<?php echo htmlspecialchars($case['complainant_authorised_person'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter authorised person">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Authorised Person</label>
                                <input type="text" name="accused_authorised_person" value="<?php echo htmlspecialchars($case_details['accused_authorised_person'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter authorised person">
                            </div>
                        </div>
                    </div>

                    <!-- Cheque Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-money-check text-blue-500 mr-2"></i>Cheque Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Cheque No</label>
                                <input type="text" name="cheque_no" value="<?php echo htmlspecialchars($case_details['cheque_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter cheque number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Cheque Date</label>
                                <input type="date" name="cheque_date" value="<?php echo htmlspecialchars($case_details['cheque_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Total No Of Cheques</label>
                                <input type="number" name="total_no_of_chq" value="<?php echo htmlspecialchars($case_details['total_no_of_chq'] ?? ''); ?>" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter total cheques">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Cheque Amount</label>
                                <input type="number" name="cheque_amount" value="<?php echo htmlspecialchars($case_details['cheque_amount'] ?? ''); ?>" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter amount">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Amount</label>
                                <input type="number" name="filing_amount" value="<?php echo htmlspecialchars($case_details['filing_amount'] ?? ''); ?>" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing amount">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Bank Name & Address</label>
                                <input type="text" name="bank_name_address" value="<?php echo htmlspecialchars($case_details['bank_name_address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., HDFC, Rajpur rd">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Cheque Holder Name</label>
                                <input type="text" name="cheque_holder_name" value="<?php echo htmlspecialchars($case_details['cheque_holder_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter cheque holder name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Cheque Status</label>
                                <select name="cheque_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select status</option>
                                    <option value="Cleared" <?php echo ($case_details['cheque_status'] ?? '') === 'Cleared' ? 'selected' : ''; ?>>Cleared</option>
                                    <option value="Bounced" <?php echo ($case_details['cheque_status'] ?? '') === 'Bounced' ? 'selected' : ''; ?>>Bounced</option>
                                    <option value="Pending" <?php echo ($case_details['cheque_status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Bounce & Notice Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Bounce & Notice Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Bounce Date</label>
                                <input type="date" name="bounce_date" value="<?php echo htmlspecialchars($case_details['bounce_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Bounce Reason</label>
                                <select name="bounce_reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select bounce reason</option>
                                    <option value="Fund Insufficient" <?php echo ($case_details['bounce_reason'] ?? '') === 'Fund Insufficient' ? 'selected' : ''; ?>>Fund Insufficient</option>
                                    <option value="Account Closed" <?php echo ($case_details['bounce_reason'] ?? '') === 'Account Closed' ? 'selected' : ''; ?>>Account Closed</option>
                                    <option value="Signature Mismatch" <?php echo ($case_details['bounce_reason'] ?? '') === 'Signature Mismatch' ? 'selected' : ''; ?>>Signature Mismatch</option>
                                    <option value="Stop Payment" <?php echo ($case_details['bounce_reason'] ?? '') === 'Stop Payment' ? 'selected' : ''; ?>>Stop Payment</option>
                                    <option value="Exceeds Arrangement" <?php echo ($case_details['bounce_reason'] ?? '') === 'Exceeds Arrangement' ? 'selected' : ''; ?>>Exceeds Arrangement</option>
                                    <option value="Account Frozen" <?php echo ($case_details['bounce_reason'] ?? '') === 'Account Frozen' ? 'selected' : ''; ?>>Account Frozen</option>
                                    <option value="Other" <?php echo ($case_details['bounce_reason'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Notice Date</label>
                                <input type="date" name="notice_date" value="<?php echo htmlspecialchars($case_details['notice_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Notice Sent Date</label>
                                <input type="date" name="notice_sent_date" value="<?php echo htmlspecialchars($case_details['notice_sent_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Legal & Court Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-gavel text-blue-500 mr-2"></i>Legal & Court Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Date</label>
                                <input type="date" name="filing_date" value="<?php echo htmlspecialchars($case_details['filing_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Location</label>
                                <input type="text" name="filing_location" value="<?php echo htmlspecialchars($case_details['filing_location'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing location">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case No</label>
                                <input type="text" name="case_no" value="<?php echo htmlspecialchars($case_details['case_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter case number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Court No</label>
                                <input type="text" name="court_no" value="<?php echo htmlspecialchars($case_details['court_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Court Name</label>
                                <input type="text" name="court_name" value="<?php echo htmlspecialchars($case_details['court_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Section</label>
                                <input type="text" name="section" value="<?php echo htmlspecialchars($case_details['section'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter section">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Act</label>
                                <input type="text" name="act" value="<?php echo htmlspecialchars($case_details['act'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter act">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">POA Date</label>
                                <input type="date" name="poa_date" value="<?php echo htmlspecialchars($case_details['poa_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Last Date Update</label>
                                <input type="date" name="last_date_update" value="<?php echo htmlspecialchars($case_details['last_date_update'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Current Stage</label>
                                <input type="text" name="current_stage" value="<?php echo htmlspecialchars($case_details['current_stage'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter current stage">
                            </div>
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
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Remarks</label>
                                <textarea name="remarks" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter remarks"><?php echo htmlspecialchars($case_details['remarks'] ?? ''); ?></textarea>
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
                                                    <input type="text" name="fee_grid_name[]" value="NOTICE" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                            <tr class="border-b border-yellow-300">
                                                <td class="px-4 py-2">
                                                    <input type="text" name="fee_grid_name[]" value="FILING" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                            <tr class="border-b border-yellow-300">
                                                <td class="px-4 py-2">
                                                    <input type="text" name="fee_grid_name[]" value="SUMMON" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                        <a href="case-details-ni-passa.php?id=<?php echo $case_id; ?>"
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
            // Add More Accused Functionality
            let accusedCount = document.querySelectorAll('#additionalAccused > div').length;
            document.getElementById('addMoreAccused').addEventListener('click', function() {
                accusedCount++;
                const container = document.getElementById('additionalAccused');
                const accusedDiv = document.createElement('div');
                accusedDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg relative';
                accusedDiv.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Name ${accusedCount + 1}</label>
                        <input type="text" name="additional_accused_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter accused name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Address ${accusedCount + 1}</label>
                        <input type="text" name="additional_accused_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter accused address">
                    </div>
                    <button type="button" class="remove-accused absolute top-2 right-2 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(accusedDiv);

                // Add remove functionality
                accusedDiv.querySelector('.remove-accused').addEventListener('click', function() {
                    accusedDiv.remove();
                });
            });

            // Add remove functionality to existing accused rows
            document.querySelectorAll('.remove-accused').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.closest('div[class*="grid"]').remove();
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
</body>

</html>
