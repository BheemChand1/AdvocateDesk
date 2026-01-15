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

// Fetch main case data with client info
$query = "SELECT c.*, cl.name as customer_name, cl.father_name, cl.email, cl.mobile, cl.address
          FROM cases c
          LEFT JOIN clients cl ON c.client_id = cl.client_id
          WHERE c.id = ? AND c.case_type = 'consumer_civil'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);

if (!$case) {
    header("Location: view-cases.php");
    exit();
}

// Fetch Consumer/Civil case specific details
$query = "SELECT * FROM case_consumer_civil_details WHERE case_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case_details = mysqli_fetch_assoc($result);

// Fetch case parties
$query = "SELECT * FROM case_parties WHERE case_id = ? ORDER BY is_primary DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$parties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $parties[] = $row;
}

// Fetch fee grid
$query = "SELECT * FROM case_fee_grid WHERE case_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$fee_grid = [];
while ($row = mysqli_fetch_assoc($result)) {
    $fee_grid[] = $row;
}

// Fetch case position updates (includes all account/billing info now)
$query = "SELECT * FROM case_position_updates WHERE case_id = ? ORDER BY update_date DESC, created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$position_updates = [];
while ($row = mysqli_fetch_assoc($result)) {
    $position_updates[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer/Civil Case Details - Case Management</title>
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
        <main class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <!-- Back Button and Header -->
            <div class="mb-8">
                <a href="view-cases.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Cases
                </a>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">
                            <i class="fas fa-scale-balanced text-blue-500 mr-3"></i>Consumer/Civil Case Details
                        </h1>
                        <p class="text-gray-600">Complete information about the consumer/civil case</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-4 py-2 <?php 
                            $status = strtolower($case['status'] ?? 'pending');
                            echo $status == 'active' ? 'bg-green-100 text-green-800' : 
                                 ($status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                 ($status == 'closed' ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800')); 
                        ?> rounded-lg text-sm font-bold">
                            <i class="fas fa-flag mr-2"></i><?php echo htmlspecialchars(ucfirst($case['status'])); ?>
                        </span>
                        <a href="edit-case-consumer-civil.php?id=<?php echo $case['id']; ?>" 
                            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                            <i class="fas fa-edit mr-2"></i>Edit Case
                        </a>
                    </div>
                </div>
            </div>

            <!-- Customer & Loan Information -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-user-circle text-blue-500 mr-2"></i>Customer & Loan Information
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Loan Number -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-hashtag text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Loan Number</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['loan_number'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Customer Name -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user text-purple-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Customer Name</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['customer_name'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Father's Name -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user-tie text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Father's Name</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['father_name'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Product -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-box text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Product</p>
                            <p class="text-lg font-bold text-gray-800">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                    <?php echo htmlspecialchars($case['product'] ?? '-'); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Branch Name -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-building text-yellow-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Branch Name</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['branch_name'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Case Created Date -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-calendar-plus text-pink-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Case Created</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo date('d M Y', strtotime($case['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consumer/Civil Case Specific Information -->
            <?php if ($case_details): ?>
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-scale-balanced text-blue-500 mr-2"></i>Consumer/Civil Case Information
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Case Type Specific -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-folder text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Case Type</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['case_type_specific'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Filing Date -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-calendar text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Filing Date</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo $case_details['case_filling_date'] ? date('d M Y', strtotime($case_details['case_filling_date'])) : '-'; ?></p>
                        </div>
                    </div>

                    <!-- Legal Notice Date -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-calendar text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Legal Notice Date</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo $case_details['legal_notice_date'] ? date('d M Y', strtotime($case_details['legal_notice_date'])) : '-'; ?></p>
                        </div>
                    </div>

                    <!-- Case vs Law/Act -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-gavel text-orange-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Case vs Law/Act</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['case_vs_law_act'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- SWT Value -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-rupee-sign text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">SWT Value</p>
                            <p class="text-lg font-bold text-green-600">₹<?php echo htmlspecialchars($case_details['swt_value'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Filing Location -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-yellow-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Filing Location</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['filing_location'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Court Name -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-courthouse text-purple-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Court Name</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['court_name'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Case Number -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-hashtag text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Case Number</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['case_no'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Court Number -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-building text-teal-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Court Number</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['court_no'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Advocate -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user-tie text-purple-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Advocate</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['advocate'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- POA -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-file-signature text-pink-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">POA</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['poa'] ?? '-'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                <?php if (!empty($case_details['remarks'])): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-comment text-gray-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">Remarks</p>
                            <p class="text-base text-gray-700"><?php echo nl2br(htmlspecialchars($case_details['remarks'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Case Parties -->
            <?php if (!empty($parties)): ?>
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-users text-blue-500 mr-2"></i>Case Parties
                    </h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($parties as $party): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold uppercase">
                                <?php echo htmlspecialchars($party['party_type']); ?>
                            </span>
                            <?php if ($party['is_primary']): ?>
                            <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Primary</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($party['name']); ?></p>
                        <?php if (!empty($party['address'])): ?>
                        <p class="text-sm text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($party['address'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Fee Grid -->
            <?php if (!empty($fee_grid)): ?>
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-receipt text-blue-500 mr-2"></i>Fee Grid
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fee Name</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php 
                            $total = 0;
                            foreach ($fee_grid as $fee): 
                                $total += $fee['fee_amount'];
                            ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-800"><?php echo htmlspecialchars($fee['fee_name']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-800 text-right">₹<?php echo number_format($fee['fee_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-3 text-sm text-gray-900">Total</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">₹<?php echo number_format($total, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Contact Information -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>Contact Information
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (!empty($case['email'])): ?>
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-envelope text-blue-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">Email</p>
                            <p class="text-base font-semibold text-gray-800"><?php echo htmlspecialchars($case['email']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($case['mobile'])): ?>
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-phone text-green-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">Mobile</p>
                            <p class="text-base font-semibold text-gray-800"><?php echo htmlspecialchars($case['mobile']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($case['address'])): ?>
                    <div class="flex items-start md:col-span-2 lg:col-span-1">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-purple-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">Address</p>
                            <p class="text-base text-gray-700"><?php echo nl2br(htmlspecialchars($case['address'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Case Position Updates / Stages -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-history text-blue-500 mr-2"></i>Case Position Updates & Stages
                    </h2>
                    <p class="text-gray-600 text-sm mt-1">Timeline of all case position updates with fees and billing information</p>
                </div>

                <?php if (!empty($position_updates)): ?>
                    <div class="relative">
                        <!-- Timeline line -->
                        <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-blue-200"></div>
                        
                        <!-- Timeline items -->
                        <div class="space-y-6">
                            <?php foreach ($position_updates as $index => $update): ?>
                                <div class="relative flex items-start gap-4">
                                    <!-- Timeline dot -->
                                    <div class="relative z-10 flex items-center justify-center w-16 h-16 flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full <?php echo $update['is_end_position'] ? 'bg-red-500' : 'bg-blue-500'; ?> flex items-center justify-center shadow-lg">
                                            <i class="fas <?php echo $update['is_end_position'] ? 'fa-flag-checkered' : 'fa-circle'; ?> text-white <?php echo $update['is_end_position'] ? 'text-lg' : 'text-xs'; ?>"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Update card -->
                                    <div class="flex-1 bg-gray-50 rounded-lg p-4 shadow-sm border <?php echo $update['is_end_position'] ? 'border-red-300' : 'border-gray-200'; ?>">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                            <h3 class="text-lg font-bold <?php echo $update['is_end_position'] ? 'text-red-600' : 'text-gray-800'; ?>">
                                                <?php echo htmlspecialchars($update['position']); ?>
                                                <?php if ($update['is_end_position']): ?>
                                                    <span class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">End Position</span>
                                                <?php endif; ?>
                                            </h3>
                                            <span class="text-sm text-gray-600">
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?php echo date('d M, Y', strtotime($update['update_date'])); ?>
                                            </span>
                                        </div>

                                        <!-- Fee and Billing Information -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3 bg-white p-3 rounded border border-gray-200">
                                            <div>
                                                <p class="text-xs text-gray-600 font-semibold">Fee Amount</p>
                                                <p class="text-sm font-bold text-green-600">₹<?php echo number_format($update['fee_amount'] ?? 0, 2); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600 font-semibold">Payment Status</p>
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold inline-block <?php 
                                                    $status = strtolower($update['payment_status'] ?? 'pending');
                                                    echo $status == 'completed' ? 'bg-green-100 text-green-800' : 
                                                         ($status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                         ($status == 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'));
                                                ?>">
                                                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                                                </span>
                                            </div>
                                            <?php if ($update['bill_number']): ?>
                                            <div>
                                                <p class="text-xs text-gray-600 font-semibold">Bill Number</p>
                                                <p class="text-sm font-mono text-blue-600"><?php echo htmlspecialchars($update['bill_number']); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($update['bill_date']): ?>
                                            <div>
                                                <p class="text-xs text-gray-600 font-semibold">Bill Date</p>
                                                <p class="text-sm text-gray-700"><?php echo date('d M, Y', strtotime($update['bill_date'])); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($update['completed_date']): ?>
                                            <div>
                                                <p class="text-xs text-gray-600 font-semibold">Completed Date</p>
                                                <p class="text-sm text-gray-700"><?php echo date('d M, Y', strtotime($update['completed_date'])); ?></p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($update['remarks'])): ?>
                                            <div class="mt-2 text-sm text-gray-700 bg-white p-3 rounded border border-gray-200">
                                                <p class="font-semibold text-gray-600 mb-1"><i class="fas fa-comment-dots mr-1"></i>Remarks:</p>
                                                <p><?php echo nl2br(htmlspecialchars($update['remarks'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-2 text-xs text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>Updated on <?php echo date('d M, Y h:i A', strtotime($update['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-history text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Position Updates</h3>
                        <p class="text-gray-600">No case position updates have been recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Case Accounts Section (Summary of Fee Tracking) -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b-2 border-blue-200">
                    <i class="fas fa-file-invoice-dollar text-blue-500 mr-3"></i>Fee Summary
                </h2>
                
                <?php 
                // Get summary of fees by payment status
                $fee_summary = [
                    'pending' => 0,
                    'processing' => 0,
                    'completed' => 0
                ];
                foreach ($position_updates as $update) {
                    $status = strtolower($update['payment_status'] ?? 'pending');
                    if (isset($fee_summary[$status])) {
                        $fee_summary[$status] += $update['fee_amount'] ?? 0;
                    }
                }
                ?>
                
                <?php if (!empty($position_updates)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Pending Fees</p>
                            <p class="text-2xl font-bold text-yellow-600">₹<?php echo number_format($fee_summary['pending'], 2); ?></p>
                        </div>
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Processing Fees</p>
                            <p class="text-2xl font-bold text-blue-600">₹<?php echo number_format($fee_summary['processing'], 2); ?></p>
                        </div>
                        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Completed Fees</p>
                            <p class="text-2xl font-bold text-green-600">₹<?php echo number_format($fee_summary['completed'], 2); ?></p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-blue-50 border-b-2 border-blue-200">
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Stage</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fee Amount</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Bill Number</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Payment Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($position_updates as $update): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($update['position'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-sm font-bold text-green-600">₹<?php echo number_format($update['fee_amount'] ?? 0, 2); ?></td>
                                    <td class="px-4 py-3 text-sm font-mono text-blue-600"><?php echo htmlspecialchars($update['bill_number'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php 
                                            $status = strtolower($update['payment_status'] ?? 'pending');
                                            echo $status == 'completed' ? 'bg-green-100 text-green-800' : 
                                                 ($status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                 ($status == 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'));
                                        ?>">
                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Fees Recorded</h3>
                        <p class="text-gray-600">No case fees have been recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-4">
                <button class="w-full sm:w-auto px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg transition shadow-lg">
                    <i class="fas fa-trash mr-2"></i>Delete Case
                </button>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
