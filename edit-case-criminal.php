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
          WHERE c.id = ? AND c.case_type = 'CRIMINAL'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);

if (!$case) {
    header("Location: view-cases.php");
    exit();
}

// Fetch CRIMINAL specific details
$details_query = "SELECT * FROM case_criminal_details WHERE case_id = ?";
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
        // Update CRIMINAL specific details
        $case_type_specific = mysqli_real_escape_string($conn, $_POST['case_type_specific'] ?? '');
        $complainant_name = mysqli_real_escape_string($conn, $_POST['complainant_name'] ?? '');
        $complainant_address = mysqli_real_escape_string($conn, $_POST['complainant_address'] ?? '');
        $accused_name = mysqli_real_escape_string($conn, $_POST['accused_name'] ?? '');
        $accused_address = mysqli_real_escape_string($conn, $_POST['accused_address'] ?? '');
        $notice_date = mysqli_real_escape_string($conn, $_POST['notice_date'] ?? '');
        $poa_date = mysqli_real_escape_string($conn, $_POST['poa_date'] ?? '');
        $filing_date = mysqli_real_escape_string($conn, $_POST['filing_date'] ?? '');
        $filing_location = mysqli_real_escape_string($conn, $_POST['filing_location'] ?? '');
        $case_no = mysqli_real_escape_string($conn, $_POST['case_no'] ?? '');
        $court_no = mysqli_real_escape_string($conn, $_POST['court_no'] ?? '');
        $court_name = mysqli_real_escape_string($conn, $_POST['court_name'] ?? '');
        $section = mysqli_real_escape_string($conn, $_POST['section'] ?? '');
        $act = mysqli_real_escape_string($conn, $_POST['act'] ?? '');
        $police_station_with_district = mysqli_real_escape_string($conn, $_POST['police_station_with_district'] ?? '');
        $crime_no_fir_no = mysqli_real_escape_string($conn, $_POST['crime_no_fir_no'] ?? '');
        $fir_date = mysqli_real_escape_string($conn, $_POST['fir_date'] ?? '');
        $charge_sheet_date = mysqli_real_escape_string($conn, $_POST['charge_sheet_date'] ?? '');

        if ($case_details) {
            // Update existing details
            $details_sql = "UPDATE case_criminal_details SET case_type_specific = '$case_type_specific',
                           complainant_name = '$complainant_name', complainant_address = '$complainant_address',
                           accused_name = '$accused_name', accused_address = '$accused_address',
                           notice_date = '$notice_date', poa_date = '$poa_date', filing_date = '$filing_date',
                           filing_location = '$filing_location', case_no = '$case_no', court_no = '$court_no',
                           court_name = '$court_name', section = '$section', act = '$act',
                           police_station_with_district = '$police_station_with_district',
                           crime_no_fir_no = '$crime_no_fir_no', fir_date = '$fir_date',
                           charge_sheet_date = '$charge_sheet_date'
                           WHERE case_id = $case_id";
        } else {
            // Insert new details
            $details_sql = "INSERT INTO case_criminal_details (case_id, case_type_specific, complainant_name, 
                           complainant_address, accused_name, accused_address, notice_date, poa_date, 
                           filing_date, filing_location, case_no, court_no, court_name, section, act,
                           police_station_with_district, crime_no_fir_no, fir_date, charge_sheet_date)
                           VALUES ($case_id, '$case_type_specific', '$complainant_name', '$complainant_address',
                           '$accused_name', '$accused_address', '$notice_date', '$poa_date', '$filing_date',
                           '$filing_location', '$case_no', '$court_no', '$court_name', '$section', '$act',
                           '$police_station_with_district', '$crime_no_fir_no', '$fir_date', '$charge_sheet_date')";
        }

        if (mysqli_query($conn, $details_sql)) {
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
    <title>Edit Criminal Case - Case Management</title>
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
                <a href="case-details-criminal.php?id=<?php echo $case_id; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-edit text-green-500 mr-2"></i>Edit Criminal Case
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
                            <i class="fas fa-gavel text-blue-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Case Category:</span>
                                <h2 class="text-lg font-bold text-gray-800">Criminal Case</h2>
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">CNR NUMBER</label>
                                <input type="text" name="cnr_number" value="<?php echo htmlspecialchars($case['cnr_number'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter CNR number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Loan Number</label>
                                <input type="text" name="loan_number" value="<?php echo htmlspecialchars($case['loan_number'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter loan number">
                            </div>
                        </div>
                    </div>

                    <!-- Complainant & Accused Information (Blue) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-blue-500 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-user-shield mr-2"></i>Complainant Information
                        </h2>
                        <div class="border-2 border-blue-500 rounded-b-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Name</label>
                                    <input type="text" name="complainant_name" value="<?php echo htmlspecialchars($case_details['complainant_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant ADDRESS</label>
                                    <input type="text" name="complainant_address" value="<?php echo htmlspecialchars($case_details['complainant_address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant address">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accused Information (Blue) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-blue-500 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-user-times mr-2"></i>Accused Information
                        </h2>
                        <div class="border-2 border-blue-500 rounded-b-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Accused name</label>
                                    <input type="text" name="accused_name" value="<?php echo htmlspecialchars($case_details['accused_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter accused name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Address</label>
                                    <input type="text" name="accused_address" value="<?php echo htmlspecialchars($case_details['accused_address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter accused address">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business & Location Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-building text-blue-500 mr-2"></i>Business & Location Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    <option value="BAIL_APPLICATION_NO" <?php echo ($case_details['case_type_specific'] ?? '') === 'BAIL_APPLICATION_NO' ? 'selected' : ''; ?>>BAIL APPLICATION NO.</option>
                                    <option value="CBI_CASES" <?php echo ($case_details['case_type_specific'] ?? '') === 'CBI_CASES' ? 'selected' : ''; ?>>CBI CASES</option>
                                    <option value="COMPLAINT_CASE" <?php echo ($case_details['case_type_specific'] ?? '') === 'COMPLAINT_CASE' ? 'selected' : ''; ?>>COMPLAINT CASE</option>
                                    <option value="CRIMINAL_MISC" <?php echo ($case_details['case_type_specific'] ?? '') === 'CRIMINAL_MISC' ? 'selected' : ''; ?>>CRIMINAL MISC.</option>
                                    <option value="DV_ACT" <?php echo ($case_details['case_type_specific'] ?? '') === 'DV_ACT' ? 'selected' : ''; ?>>DV ACT</option>
                                    <option value="FINAL_REPORT" <?php echo ($case_details['case_type_specific'] ?? '') === 'FINAL_REPORT' ? 'selected' : ''; ?>>FINAL REPORT</option>
                                    <option value="MUNICIPAL_APPEAL" <?php echo ($case_details['case_type_specific'] ?? '') === 'MUNICIPAL_APPEAL' ? 'selected' : ''; ?>>MUNICIPAL APPEAL</option>
                                    <option value="MOTOR_VEHICLE_APPEAL" <?php echo ($case_details['case_type_specific'] ?? '') === 'MOTOR_VEHICLE_APPEAL' ? 'selected' : ''; ?>>MOTOR VEHICLE APPEAL</option>
                                    <option value="SEC_14" <?php echo ($case_details['case_type_specific'] ?? '') === 'SEC_14' ? 'selected' : ''; ?>>Sec 14</option>
                                    <option value="144_BNSS" <?php echo ($case_details['case_type_specific'] ?? '') === '144_BNSS' ? 'selected' : ''; ?>>144 BNSS</option>
                                    <option value="STATE_CASES" <?php echo ($case_details['case_type_specific'] ?? '') === 'STATE_CASES' ? 'selected' : ''; ?>>STATE CASES</option>
                                    <option value="SUMMARY_TRIAL" <?php echo ($case_details['case_type_specific'] ?? '') === 'SUMMARY_TRIAL' ? 'selected' : ''; ?>>SUMMARY TRIAL</option>
                                    <option value="TRAFFIC_CHALLAN" <?php echo ($case_details['case_type_specific'] ?? '') === 'TRAFFIC_CHALLAN' ? 'selected' : ''; ?>>TRAFFIC CHALLAN</option>
                                    <option value="POCSO_ACT" <?php echo ($case_details['case_type_specific'] ?? '') === 'POCSO_ACT' ? 'selected' : ''; ?>>POCSO ACT</option>
                                    <option value="DM" <?php echo ($case_details['case_type_specific'] ?? '') === 'DM' ? 'selected' : ''; ?>>DM</option>
                                    <option value="CRIMINAL_APPEAL" <?php echo ($case_details['case_type_specific'] ?? '') === 'CRIMINAL_APPEAL' ? 'selected' : ''; ?>>CRIMINAL APPEAL</option>
                                    <option value="CRIMINAL_REVISION" <?php echo ($case_details['case_type_specific'] ?? '') === 'CRIMINAL_REVISION' ? 'selected' : ''; ?>>CRIMINAL REVISION</option>
                                    <option value="JUVENILE" <?php echo ($case_details['case_type_specific'] ?? '') === 'JUVENILE' ? 'selected' : ''; ?>>JUVENILE</option>
                                    <option value="CRIMINAL_OTHER" <?php echo ($case_details['case_type_specific'] ?? '') === 'CRIMINAL_OTHER' ? 'selected' : ''; ?>>CRIMINAL OTHER</option>
                                    <option value="SST" <?php echo ($case_details['case_type_specific'] ?? '') === 'SST' ? 'selected' : ''; ?>>SST</option>
                                    <option value="ST" <?php echo ($case_details['case_type_specific'] ?? '') === 'ST' ? 'selected' : ''; ?>>ST</option>
                                    <option value="NDPS" <?php echo ($case_details['case_type_specific'] ?? '') === 'NDPS' ? 'selected' : ''; ?>>NDPS</option>
                                    <option value="GANGSTER_ACT" <?php echo ($case_details['case_type_specific'] ?? '') === 'GANGSTER_ACT' ? 'selected' : ''; ?>>GANGSTER ACT</option>
                                    <option value="GUNDA_ACT" <?php echo ($case_details['case_type_specific'] ?? '') === 'GUNDA_ACT' ? 'selected' : ''; ?>>GUNDA ACT</option>
                                    <option value="HUMAN_RIGHTS" <?php echo ($case_details['case_type_specific'] ?? '') === 'HUMAN_RIGHTS' ? 'selected' : ''; ?>>HUMAN RIGHTS</option>
                                    <option value="WOMEN_COMMISSION" <?php echo ($case_details['case_type_specific'] ?? '') === 'WOMEN_COMMISSION' ? 'selected' : ''; ?>>WOMEN COMMISSION</option>
                                    <option value="POLICE_STATION_MATTER" <?php echo ($case_details['case_type_specific'] ?? '') === 'POLICE_STATION_MATTER' ? 'selected' : ''; ?>>POLICE STATION MATTER</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Region</label>
                                <input type="text" name="region" value="<?php echo htmlspecialchars($case['region'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter region">
                            </div>
                        </div>
                    </div>

                    <!-- Notice & Date Information (Yellow Background) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Notice & Date Information
                        </h2>
                        <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Notice_Date</label>
                                    <input type="date" name="notice_date" value="<?php echo htmlspecialchars($case_details['notice_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Poa date</label>
                                    <input type="date" name="poa_date" value="<?php echo htmlspecialchars($case_details['poa_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Court & Legal Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-gavel text-blue-500 mr-2"></i>Court & Legal Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant authorised person</label>
                                <input type="text" name="complainant_authorised_person" value="<?php echo htmlspecialchars($case['complainant_authorised_person'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter authorised person">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing_Date</label>
                                <input type="date" name="filing_date" value="<?php echo htmlspecialchars($case_details['filing_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing_Location</label>
                                <input type="text" name="filing_location" value="<?php echo htmlspecialchars($case_details['filing_location'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing location">
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
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Court_Name</label>
                                <input type="text" name="court_name" value="<?php echo htmlspecialchars($case_details['court_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court name">
                            </div>
                        </div>
                    </div>

                    <!-- Criminal Case Specific Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-file-invoice text-blue-500 mr-2"></i>Criminal Case Specific Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Section</label>
                                <input type="text" name="section" value="<?php echo htmlspecialchars($case_details['section'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter section">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Act</label>
                                <input type="text" name="act" value="<?php echo htmlspecialchars($case_details['act'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter act">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Police station with District</label>
                                <input type="text" name="police_station_with_district" value="<?php echo htmlspecialchars($case_details['police_station_with_district'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter police station with district">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Crime No./FIR No.</label>
                                <input type="text" name="crime_no_fir_no" value="<?php echo htmlspecialchars($case_details['crime_no_fir_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter crime/FIR number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">FIR Date</label>
                                <input type="date" name="fir_date" value="<?php echo htmlspecialchars($case_details['fir_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Charge Sheet Date</label>
                                <input type="date" name="charge_sheet_date" value="<?php echo htmlspecialchars($case_details['charge_sheet_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
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
                        <a href="case-details-criminal.php?id=<?php echo $case_id; ?>"
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
