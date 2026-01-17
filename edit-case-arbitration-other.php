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
          WHERE c.id = ? AND c.case_type = 'ARBITRATION_OTHER'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);

if (!$case) {
    header("Location: view-cases.php");
    exit();
}

// Fetch ARBITRATION_OTHER specific details
$details_query = "SELECT * FROM case_arbitration_other_details WHERE case_id = ?";
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update main cases table
    $cnr_number = mysqli_real_escape_string($conn, $_POST['cnr_number'] ?? '');
    $loan_number = mysqli_real_escape_string($conn, $_POST['loan_number'] ?? '');
    $product = mysqli_real_escape_string($conn, $_POST['product'] ?? '');
    $branch_name = mysqli_real_escape_string($conn, $_POST['branch_name'] ?? '');
    $location = mysqli_real_escape_string($conn, $_POST['location'] ?? '');
    $region = mysqli_real_escape_string($conn, $_POST['region'] ?? '');
    $filing_amount = !empty($_POST['filing_amount']) ? floatval($_POST['filing_amount']) : 0;
    $complainant_authorised_person = mysqli_real_escape_string($conn, $_POST['complainant_authorised_person'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'pending');
    $case_stage_id = (!empty($_POST['case_stage_id'])) ? intval($_POST['case_stage_id']) : NULL;

    $update_sql = "UPDATE cases SET cnr_number = '$cnr_number', loan_number = '$loan_number', 
                  product = '$product', branch_name = '$branch_name', location = '$location', 
                  region = '$region', complainant_authorised_person = '$complainant_authorised_person', 
                  status = '$status'" . ($case_stage_id !== NULL ? ", case_stage_id = $case_stage_id" : "") . 
                  " WHERE id = $case_id";

    if (mysqli_query($conn, $update_sql)) {
        // Update ARBITRATION_OTHER specific details
        $plaintiff_name = mysqli_real_escape_string($conn, $_POST['plaintiff_name'] ?? '');
        $plaintiff_address = mysqli_real_escape_string($conn, $_POST['plaintiff_address'] ?? '');
        $defendant_name = mysqli_real_escape_string($conn, $_POST['defendant_name'] ?? '');
        $defendant_address = mysqli_real_escape_string($conn, $_POST['defendant_address'] ?? '');
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '');
        $filing_date = mysqli_real_escape_string($conn, $_POST['filing_date'] ?? '');
        $filing_location = mysqli_real_escape_string($conn, $_POST['filing_location'] ?? '');
        $case_no = mysqli_real_escape_string($conn, $_POST['case_no'] ?? '');
        $court_no = mysqli_real_escape_string($conn, $_POST['court_no'] ?? '');
        $advocate = mysqli_real_escape_string($conn, $_POST['advocate'] ?? '');
        $poa = mysqli_real_escape_string($conn, $_POST['poa'] ?? '');
        $remarks_feedback_trails = mysqli_real_escape_string($conn, $_POST['remarks_feedback_trails'] ?? '');

        if ($case_details) {
            // Update existing details
            $details_sql = "UPDATE case_arbitration_other_details SET plaintiff_name = '$plaintiff_name',
                           plaintiff_address = '$plaintiff_address', defendant_name = '$defendant_name',
                           defendant_address = '$defendant_address', customer_name = '$customer_name',
                           filing_date = '$filing_date', filing_location = '$filing_location', case_no = '$case_no',
                           court_no = '$court_no', advocate = '$advocate', poa = '$poa',
                           remarks_feedback_trails = '$remarks_feedback_trails'
                           WHERE case_id = $case_id";
        } else {
            // Insert new details
            $details_sql = "INSERT INTO case_arbitration_other_details (case_id, plaintiff_name,
                           plaintiff_address, defendant_name, defendant_address, customer_name,
                           filing_date, filing_location, case_no, court_no, advocate, poa,
                           remarks_feedback_trails)
                           VALUES ($case_id, '$plaintiff_name', '$plaintiff_address', '$defendant_name',
                           '$defendant_address', '$customer_name', '$filing_date', '$filing_location',
                           '$case_no', '$court_no', '$advocate', '$poa', '$remarks_feedback_trails')";
        }

        if (mysqli_query($conn, $details_sql)) {
            // Update case parties
            $has_parties = !empty($_POST['plaintiff_name']) || !empty($_POST['defendant_name']) ||
                          !empty($_POST['additional_plaintiff_name']) || !empty($_POST['additional_defendant_name']);
            
            if ($has_parties) {
                // Delete existing parties if updating
                mysqli_query($conn, "DELETE FROM case_parties WHERE case_id = $case_id");
                
                // Insert plaintiff primary party
                if (!empty($_POST['plaintiff_name'])) {
                    $party_name = mysqli_real_escape_string($conn, $_POST['plaintiff_name']);
                    $party_address = mysqli_real_escape_string($conn, $_POST['plaintiff_address'] ?? '');
                    
                    $stmt = mysqli_prepare($conn, "INSERT INTO case_parties (case_id, party_type, name, address, is_primary) VALUES (?, ?, ?, ?, ?)");
                    $party_type = 'plaintiff';
                    $is_primary = 1;
                    mysqli_stmt_bind_param($stmt, "isssi", $case_id, $party_type, $party_name, $party_address, $is_primary);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                
                // Insert defendant primary party
                if (!empty($_POST['defendant_name'])) {
                    $party_name = mysqli_real_escape_string($conn, $_POST['defendant_name']);
                    $party_address = mysqli_real_escape_string($conn, $_POST['defendant_address'] ?? '');
                    
                    $stmt = mysqli_prepare($conn, "INSERT INTO case_parties (case_id, party_type, name, address, is_primary) VALUES (?, ?, ?, ?, ?)");
                    $party_type = 'defendant';
                    $is_primary = 1;
                    mysqli_stmt_bind_param($stmt, "isssi", $case_id, $party_type, $party_name, $party_address, $is_primary);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                
                // Insert additional plaintiffs
                if (!empty($_POST['additional_plaintiff_name']) && is_array($_POST['additional_plaintiff_name'])) {
                    $plaintiff_names = $_POST['additional_plaintiff_name'];
                    $plaintiff_addresses = $_POST['additional_plaintiff_address'] ?? [];
                    
                    for ($i = 0; $i < count($plaintiff_names); $i++) {
                        if (!empty($plaintiff_names[$i])) {
                            $party_name = mysqli_real_escape_string($conn, $plaintiff_names[$i]);
                            $party_address = mysqli_real_escape_string($conn, $plaintiff_addresses[$i] ?? '');
                            
                            $stmt = mysqli_prepare($conn, "INSERT INTO case_parties (case_id, party_type, name, address, is_primary) VALUES (?, ?, ?, ?, ?)");
                            $party_type = 'plaintiff';
                            $is_primary = 0;
                            mysqli_stmt_bind_param($stmt, "isssi", $case_id, $party_type, $party_name, $party_address, $is_primary);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
                
                // Insert additional defendants
                if (!empty($_POST['additional_defendant_name']) && is_array($_POST['additional_defendant_name'])) {
                    $defendant_names = $_POST['additional_defendant_name'];
                    $defendant_addresses = $_POST['additional_defendant_address'] ?? [];
                    
                    for ($i = 0; $i < count($defendant_names); $i++) {
                        if (!empty($defendant_names[$i])) {
                            $party_name = mysqli_real_escape_string($conn, $defendant_names[$i]);
                            $party_address = mysqli_real_escape_string($conn, $defendant_addresses[$i] ?? '');
                            
                            $stmt = mysqli_prepare($conn, "INSERT INTO case_parties (case_id, party_type, name, address, is_primary) VALUES (?, ?, ?, ?, ?)");
                            $party_type = 'defendant';
                            $is_primary = 0;
                            mysqli_stmt_bind_param($stmt, "isssi", $case_id, $party_type, $party_name, $party_address, $is_primary);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
            }
            
            // Update fee grid
            mysqli_query($conn, "DELETE FROM case_fee_grid WHERE case_id = $case_id");
            
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
    <title>Edit Arbitration Other Case - Case Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/style.css">
</head>

<body class="bg-gradient-to-br from-gray-200 via-gray-100 to-slate-200 min-h-screen">
    <?php include './includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <?php include './includes/navbar.php'; ?>

        <main class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <div class="mb-8">
                <a href="case-details-arbitration-other.php?id=<?php echo $case_id; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Case Details
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-edit text-green-500 mr-3"></i>Edit Arbitration Other Case
                    </h1>
                    <p class="text-gray-600">Update case position and stages for <strong><?php echo htmlspecialchars($case['unique_case_id']); ?></strong> - <?php echo htmlspecialchars($case['customer_name']); ?></p>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6 sm:p-8">
                <form method="POST" action="">
                    <!-- Case Type Display -->
                    <div class="mb-6 bg-gradient-to-r from-purple-50 to-purple-100 border-l-4 border-purple-500 p-4 rounded-lg">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-gavel text-purple-500 mr-2 text-2xl"></i>Arbitration Other Case
                        </h2>
                    </div>

                    <!-- Client Information -->
                    <div class="mb-6 bg-green-50 border border-green-300 rounded-lg p-4">
                        <h3 class="text-lg font-bold text-gray-800 mb-3"><i class="fas fa-user-circle text-green-500 mr-2"></i>Client Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Customer Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" value="<?php echo htmlspecialchars($case['customer_name']); ?>" readonly>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case ID</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" value="<?php echo htmlspecialchars($case['unique_case_id']); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Case Details -->
                    <div class="mb-6 bg-green-50 border-2 border-green-400 rounded-lg p-4">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-file-alt text-green-500 mr-2"></i>Case Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">CNR NUMBER</label>
                                <input type="text" name="cnr_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo htmlspecialchars($case['cnr_number'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Loan Number</label>
                                <input type="text" name="loan_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo htmlspecialchars($case['loan_number'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Product</label>
                                <input type="text" name="product" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo htmlspecialchars($case['product'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Branch Name</label>
                                <input type="text" name="branch_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo htmlspecialchars($case['branch_name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Location</label>
                                <input type="text" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo htmlspecialchars($case['location'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Region</label>
                                <input type="text" name="region" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo htmlspecialchars($case['region'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Amount</label>
                                <input type="number" name="filing_amount" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo htmlspecialchars($case['filing_amount'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Authorised Person</label>
                                <input type="text" name="complainant_authorised_person" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo htmlspecialchars($case['complainant_authorised_person'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Plaintiff and Defendant Information with Party Management -->
                    <?php
                    $primary_plaintiff = null;
                    $additional_plaintiffs = [];
                    $primary_defendant = null;
                    $additional_defendants = [];
                    
                    foreach ($case_parties as $party) {
                        if ($party['party_type'] === 'plaintiff') {
                            if ($party['is_primary']) {
                                $primary_plaintiff = $party;
                            } else {
                                $additional_plaintiffs[] = $party;
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
                    <div class="mb-6 bg-blue-50 border border-blue-300 rounded-lg p-4">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-user text-blue-500 mr-2"></i>Plaintiff Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff Name</label>
                                <input type="text" name="plaintiff_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($primary_plaintiff['name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff Address</label>
                                <input type="text" name="plaintiff_address" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($primary_plaintiff['address'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <!-- Additional Plaintiffs -->
                        <div id="additionalPlaintiffs" class="mt-4">
                            <?php foreach ($additional_plaintiffs as $plaintiff): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-3 bg-white rounded border border-blue-200">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff Name</label>
                                    <input type="text" name="additional_plaintiff_name[]" value="<?php echo htmlspecialchars($plaintiff['name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter plaintiff name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff Address</label>
                                    <input type="text" name="additional_plaintiff_address[]" value="<?php echo htmlspecialchars($plaintiff['address']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter plaintiff address">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" class="remove-plaintiff px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                        <i class="fas fa-trash mr-2"></i>Remove
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" id="addMorePlaintiff" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            <i class="fas fa-plus mr-2"></i>Add More Plaintiff
                        </button>
                    </div>

                    <div class="mb-6 bg-blue-50 border border-blue-300 rounded-lg p-4">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-user-injured text-blue-500 mr-2"></i>Defendant Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Name</label>
                                <input type="text" name="defendant_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($primary_defendant['name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Address</label>
                                <input type="text" name="defendant_address" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($primary_defendant['address'] ?? ''); ?>">
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

                    <!-- Court & Legal Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-gavel text-blue-500 mr-2"></i>Court & Legal Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Date</label>
                                <input type="date" name="filing_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($case_details['filing_date'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Location</label>
                                <input type="text" name="filing_location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($case_details['filing_location'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case No</label>
                                <input type="text" name="case_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($case_details['case_no'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Court No</label>
                                <input type="text" name="court_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($case_details['court_no'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Advocate</label>
                                <input type="text" name="advocate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($case_details['advocate'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">POA</label>
                                <input type="text" name="poa" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($case_details['poa'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Case Status -->
                    <div class="mb-6 bg-yellow-50 border border-yellow-300 rounded-lg p-4">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-tasks text-yellow-600 mr-2"></i>Case Status</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                    <option value="pending" <?php echo ($case['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo ($case['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo ($case['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo ($case['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case Stage</label>
                                <select name="case_stage_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                    <option value="">Select Stage</option>
                                    <?php foreach ($stages as $stage): ?>
                                    <option value="<?php echo $stage['id']; ?>" <?php echo ($case['case_stage_id'] == $stage['id']) ? 'selected' : ''; ?>>
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
                                        <?php if (empty($case_fees)): ?>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Filing/Appearance" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Decided" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Misc" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Certified Copy ( of any order )" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($case_fees as $fee): ?>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="<?php echo htmlspecialchars($fee['fee_name']); ?>" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="<?php echo htmlspecialchars($fee['fee_amount']); ?>" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                            <?php endforeach; ?>
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

                    <!-- Remarks/Feedback/Trails -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-comments text-blue-500 mr-2"></i>Remarks/Feedback/Trails
                        </h2>
                        <div>
                            <textarea name="remarks_feedback_trails" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"><?php echo htmlspecialchars($case_details['remarks_feedback_trails'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-6 border-t border-gray-200">
                        <a href="view-cases.php" class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="reset" class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
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
            // Add More Plaintiff Functionality
            document.getElementById('addMorePlaintiff').addEventListener('click', function(e) {
                e.preventDefault();
                const container = document.getElementById('additionalPlaintiffs');
                const newRow = document.createElement('div');
                newRow.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-3 bg-white rounded border border-blue-200';
                newRow.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff Name</label>
                        <input type="text" name="additional_plaintiff_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter plaintiff name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff Address</label>
                        <input type="text" name="additional_plaintiff_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter plaintiff address">
                    </div>
                    <div class="md:col-span-2">
                        <button type="button" class="remove-plaintiff px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                            <i class="fas fa-trash mr-2"></i>Remove
                        </button>
                    </div>
                `;
                container.appendChild(newRow);
                
                // Add remove functionality
                newRow.querySelector('.remove-plaintiff').addEventListener('click', function(e) {
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

            // Add remove functionality to existing plaintiff rows
            document.querySelectorAll('.remove-plaintiff').forEach(button => {
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

            // Add More Fee Functionality
            document.getElementById('addMoreFee').addEventListener('click', function() {
                const tbody = document.getElementById('feeGridBody');
                const newRow = document.createElement('tr');
                newRow.className = 'border-b border-yellow-300';
                newRow.innerHTML = `
                    <td class="px-4 py-2">
                        <input type="text" name="fee_grid_name[]" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Fee name">
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" name="fee_grid_amount[]" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                    </td>
                    <td class="px-4 py-2 text-center">
                        <button type="button" class="remove-fee text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(newRow);
                
                newRow.querySelector('.remove-fee').addEventListener('click', function(e) {
                    e.preventDefault();
                    newRow.remove();
                });
            });

            // Remove Fee Functionality
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
