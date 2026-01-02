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
          WHERE c.id = ? AND c.case_type = 'arbitration_other'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $case_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);

if (!$case) {
    header("Location: view-cases.php");
    exit();
}

// Fetch Arbitration Other case specific details
$query = "SELECT * FROM case_arbitration_other_details WHERE case_id = ?";
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arbitration (Other) Case Details - Case Management</title>
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
                            <i class="fas fa-handshake text-indigo-500 mr-3"></i>Arbitration (Other) Case Details
                        </h1>
                        <p class="text-gray-600">Complete information about the arbitration case</p>
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
                        <a href="edit-case.php?id=<?php echo $case['id']; ?>" 
                            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                            <i class="fas fa-edit mr-2"></i>Edit Case
                        </a>
                    </div>
                </div>
            </div>

            <!-- Customer & Case Information -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-user-circle text-blue-500 mr-2"></i>Customer & Case Information
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Customer Name -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user text-purple-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Customer Name</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['customer_name'] ?? $case_details['customer_name'] ?? '-'); ?></p>
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

            <!-- Arbitration Case Details -->
            <?php if ($case_details): ?>
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-handshake text-indigo-500 mr-2"></i>Arbitration Case Details
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Filing Date -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-calendar text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Filing Date</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo $case_details['filing_date'] ? date('d M Y', strtotime($case_details['filing_date'])) : '-'; ?></p>
                        </div>
                    </div>

                    <!-- Filing Amount -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-rupee-sign text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Filing Amount</p>
                            <p class="text-lg font-bold text-green-600">₹<?php echo $case_details['filing_amount'] ? number_format($case_details['filing_amount']) : '-'; ?></p>
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

                    <!-- Case Number -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-hashtag text-purple-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Case Number</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['case_no'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Court Number -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-building text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Court Number</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case_details['court_no'] ?? '-'); ?></p>
                        </div>
                    </div>

                    <!-- Advocate -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user-tie text-teal-600 text-lg"></i>
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

                <!-- Remarks & Feedback -->
                <?php if (!empty($case_details['remarks_feedback_trails'])): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-comment text-gray-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">Remarks & Feedback</p>
                            <p class="text-base text-gray-700"><?php echo nl2br(htmlspecialchars($case_details['remarks_feedback_trails'])); ?></p>
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
