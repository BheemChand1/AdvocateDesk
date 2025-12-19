<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get case ID from URL
$case_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Sample case data (will be replaced with database query later)
$cases = [
    1 => [
        'id' => 1,
        'loan_number' => '1310746',
        'customer_name' => 'SUNIL KUMAR',
        'product' => 'PL',
        'branch_name' => 'DEHRADUN',
        'cheque_no' => '000446',
        'cheque_date' => '16.02.2018',
        'cheque_amount' => '35784',
        'bank_name_address' => 'HDFC, Rajpur rd',
        'bounce_date' => '16.02.2018',
        'bounce_reason' => 'Fund Insufficient',
        'status' => 'Pending',
        'father_name' => 'RAM LAL',
        'cheque_holder_name' => 'RAM LAL',
        'off_address' => 'SABJI MANDI, COLONY, KEDARPURAM, DEHRADUN-248002',
        'resi_address' => 'OLD DALANWALI, DEHRADUN-248002',
        'created_at' => '2025-12-19'
    ],
    2 => [
        'id' => 2,
        'loan_number' => '2444175',
        'customer_name' => 'SUNIL KUMAR',
        'product' => 'PL',
        'branch_name' => 'DEHRADUN',
        'cheque_no' => '133465',
        'cheque_date' => '16.02.2018',
        'cheque_amount' => '36033',
        'bank_name_address' => 'Central Bank, Dharasun Branch',
        'bounce_date' => '17.02.2018',
        'bounce_reason' => 'Fund Insufficient',
        'status' => 'Active',
        'father_name' => 'Smt Kumar Smit Kulwari Lata',
        'cheque_holder_name' => 'Smt Kumar Smit Kulwari Lata',
        'off_address' => 'SABJI MANDI, COLONY, KEDARPURAM, DEHRADUN-248002',
        'resi_address' => '131165 OLD DALANWALI, DEHRADUN-248002',
        'created_at' => '2025-12-19'
    ]
];

$case = isset($cases[$case_id]) ? $cases[$case_id] : $cases[1];

// Sample stages data (will be replaced with database query later)
$stages = [
    ['id' => 1, 'stage_name' => 'Case Registered', 'stage_date' => '2025-12-19', 'description' => 'Case registered in the system', 'status' => 'Completed'],
    ['id' => 2, 'stage_name' => 'Legal Notice Sent', 'stage_date' => '2025-12-20', 'description' => 'Legal notice sent to the customer via registered post', 'status' => 'Completed'],
    ['id' => 3, 'stage_name' => 'Reply Awaited', 'stage_date' => '2026-01-05', 'description' => 'Waiting for customer response to legal notice', 'status' => 'In Progress'],
    ['id' => 4, 'stage_name' => 'Court Filing', 'stage_date' => '2026-01-15', 'description' => 'Case to be filed in court', 'status' => 'Pending']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - Case Management</title>
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
                            <i class="fas fa-briefcase text-blue-500 mr-3"></i>Case Details
                        </h1>
                        <p class="text-gray-600">Complete information about the case</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-4 py-2 <?php 
                            echo $case['status'] == 'Active' ? 'bg-green-100 text-green-800' : 
                                 ($case['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                 ($case['status'] == 'Closed' ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800')); 
                        ?> rounded-lg text-sm font-bold">
                            <i class="fas fa-flag mr-2"></i><?php echo $case['status']; ?>
                        </span>
                        <a href="edit-case.php?id=<?php echo $case['id']; ?>" 
                            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                            <i class="fas fa-edit mr-2"></i>Edit Case
                        </a>
                    </div>
                </div>
            </div>

            <!-- Loan & Customer Information -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-user-circle text-blue-500 mr-2"></i>Loan & Customer Information
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
                            <p class="text-lg font-bold text-gray-800"><?php echo $case['loan_number']; ?></p>
                        </div>
                    </div>

                    <!-- Customer Name -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user text-purple-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Customer Name</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['customer_name']); ?></p>
                        </div>
                    </div>

                    <!-- Father's Name -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user-tie text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Father's Name</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['father_name']); ?></p>
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
                                    <?php echo $case['product']; ?>
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
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['branch_name']); ?></p>
                        </div>
                    </div>

                    <!-- Created Date -->
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

            <!-- Cheque Information -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-money-check text-blue-500 mr-2"></i>Cheque Information
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Cheque Number -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-receipt text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Cheque Number</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo $case['cheque_no']; ?></p>
                        </div>
                    </div>

                    <!-- Cheque Date -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-calendar text-purple-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Cheque Date</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo $case['cheque_date']; ?></p>
                        </div>
                    </div>

                    <!-- Cheque Amount -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-rupee-sign text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Cheque Amount</p>
                            <p class="text-lg font-bold text-green-600">₹<?php echo number_format($case['cheque_amount']); ?></p>
                        </div>
                    </div>

                    <!-- Cheque Holder Name -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user-check text-orange-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Cheque Holder Name</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['cheque_holder_name']); ?></p>
                        </div>
                    </div>

                    <!-- Bank Name & Address -->
                    <div class="flex items-start md:col-span-2">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-university text-indigo-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Bank Name & Address</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['bank_name_address']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bounce Information -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Bounce Information
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Bounce Date -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-calendar-times text-red-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Bounce Date</p>
                            <p class="text-lg font-bold text-red-600"><?php echo $case['bounce_date']; ?></p>
                        </div>
                    </div>

                    <!-- Bounce Reason -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-comment-slash text-red-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Bounce Reason</p>
                            <p class="text-lg font-bold text-red-600"><?php echo htmlspecialchars($case['bounce_reason']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Case Stages -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-tasks text-blue-500 mr-2"></i>Case Stages
                    </h2>
                    <button onclick="document.getElementById('addStageModal').classList.remove('hidden')" 
                        class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                        <i class="fas fa-plus mr-2"></i>Add Stage
                    </button>
                </div>

                <?php if (count($stages) > 0): ?>
                    <!-- Stages Timeline -->
                    <div class="relative">
                        <!-- Vertical Line -->
                        <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-300 hidden sm:block"></div>
                        
                        <div class="space-y-6">
                            <?php foreach ($stages as $index => $stage): ?>
                                <div class="relative flex items-start gap-4">
                                    <!-- Timeline Dot -->
                                    <div class="hidden sm:flex w-12 h-12 rounded-full <?php 
                                        echo $stage['status'] == 'Completed' ? 'bg-green-500' : 
                                             ($stage['status'] == 'In Progress' ? 'bg-blue-500' : 'bg-gray-300'); 
                                    ?> flex-shrink-0 items-center justify-center z-10">
                                        <i class="fas <?php 
                                            echo $stage['status'] == 'Completed' ? 'fa-check' : 
                                                 ($stage['status'] == 'In Progress' ? 'fa-spinner' : 'fa-clock'); 
                                        ?> text-white text-lg"></i>
                                    </div>
                                    
                                    <!-- Stage Content -->
                                    <div class="flex-1 bg-gray-50 rounded-lg p-4 hover:shadow-md transition">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                            <h3 class="text-lg font-bold text-gray-800"><?php echo $stage['stage_name']; ?></h3>
                                            <div class="flex items-center gap-3">
                                                <span class="text-sm text-gray-600">
                                                    <i class="fas fa-calendar mr-1"></i><?php echo date('d M Y', strtotime($stage['stage_date'])); ?>
                                                </span>
                                                <span class="px-3 py-1 <?php 
                                                    echo $stage['status'] == 'Completed' ? 'bg-green-100 text-green-800' : 
                                                         ($stage['status'] == 'In Progress' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700'); 
                                                ?> rounded-full text-xs font-semibold">
                                                    <?php echo $stage['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-gray-600 text-sm"><?php echo $stage['description']; ?></p>
                                        <div class="flex items-center gap-2 mt-3">
                                            <button class="text-blue-600 hover:text-blue-800 text-sm" title="Edit Stage">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <button class="text-red-600 hover:text-red-800 text-sm" title="Delete Stage">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-tasks text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 text-lg mb-4">No stages added yet</p>
                        <button onclick="document.getElementById('addStageModal').classList.remove('hidden')" 
                            class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>Add First Stage
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Address Information -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>Address Information
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Office Address -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-briefcase text-blue-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">Office Address</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['off_address']); ?></p>
                        </div>
                    </div>

                    <!-- Residential Address -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-home text-green-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">Residential Address</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($case['resi_address']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-4">
                <button class="w-full sm:w-auto px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition shadow-lg">
                    <i class="fas fa-file-pdf mr-2"></i>Generate PDF
                </button>
                <button class="w-full sm:w-auto px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition shadow-lg">
                    <i class="fas fa-print mr-2"></i>Print Details
                </button>
                <button class="w-full sm:w-auto px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg transition shadow-lg">
                    <i class="fas fa-trash mr-2"></i>Delete Case
                </button>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <!-- Add Stage Modal -->
    <div id="addStageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-4 flex items-center justify-between">
                <h3 class="text-2xl font-bold">
                    <i class="fas fa-plus-circle mr-2"></i>Add New Stage
                </h3>
                <button onclick="document.getElementById('addStageModal').classList.add('hidden')" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form class="p-6">
                <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                
                <div class="space-y-6">
                    <!-- Stage Name -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-tag mr-2 text-blue-500"></i>Stage Name <span class="text-red-500">*</span>
                        </label>
                        <select name="stage_name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select stage</option>
                            <option value="Case Registered">Case Registered</option>
                            <option value="Legal Notice Sent">Legal Notice Sent</option>
                            <option value="Reply Received">Reply Received</option>
                            <option value="Reply Awaited">Reply Awaited</option>
                            <option value="Court Filing">Court Filing</option>
                            <option value="First Hearing">First Hearing</option>
                            <option value="Evidence Submission">Evidence Submission</option>
                            <option value="Final Hearing">Final Hearing</option>
                            <option value="Judgment">Judgment</option>
                            <option value="Case Closed">Case Closed</option>
                            <option value="Settlement">Settlement</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Stage Date -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-calendar mr-2 text-blue-500"></i>Stage Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="stage_date" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-flag mr-2 text-blue-500"></i>Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select status</option>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-align-left mr-2 text-blue-500"></i>Description
                        </label>
                        <textarea name="description" rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                            placeholder="Enter stage description or notes"></textarea>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <button type="button" onclick="document.getElementById('addStageModal').classList.add('hidden')"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                        <i class="fas fa-save mr-2"></i>Add Stage
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
