<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Sample cases data (will be replaced with database query later)
$cases = [
    ['id' => 1, 'loan_number' => '1310746', 'customer_name' => 'SUNIL KUMAR', 'product' => 'PL', 'branch_name' => 'DEHRADUN', 'cheque_no' => '000446', 'cheque_date' => '16.02.2018', 'cheque_amount' => '35784', 'bank_name_address' => 'HDFC, Rajpur rd', 'bounce_date' => '16.02.2018', 'bounce_reason' => 'Fund Insufficient', 'status' => 'Pending', 'father_name' => 'RAM LAL'],
    ['id' => 2, 'loan_number' => '2444175', 'customer_name' => 'SUNIL KUMAR', 'product' => 'PL', 'branch_name' => 'DEHRADUN', 'cheque_no' => '133465', 'cheque_date' => '16.02.2018', 'cheque_amount' => '36033', 'bank_name_address' => 'Central Bank, Dharasun Branch', 'bounce_date' => '17.02.2018', 'bounce_reason' => 'Fund Insufficient', 'status' => 'Active', 'father_name' => 'Smt Kumar Smit Kulwari Lata'],
    ['id' => 3, 'loan_number' => '2444179', 'customer_name' => 'SUNIL KUMAR', 'product' => 'PL', 'branch_name' => 'DEHRADUN', 'cheque_no' => '133465', 'cheque_date' => '16.02.2018', 'cheque_amount' => '36033', 'bank_name_address' => 'Central Bank, Dharasun Branch', 'bounce_date' => '17.02.2018', 'bounce_reason' => 'Fund Insufficient', 'status' => 'Pending', 'father_name' => 'Smt Kumar Smit Kulwari Lata'],
    ['id' => 4, 'loan_number' => '1310747', 'customer_name' => 'PANKAJ CHAVAN', 'product' => 'HL', 'branch_name' => 'MUMBAI', 'cheque_no' => '000448', 'cheque_date' => '20.03.2018', 'cheque_amount' => '45000', 'bank_name_address' => 'ICICI, Andheri', 'bounce_date' => '20.03.2018', 'bounce_reason' => 'Account Closed', 'status' => 'Legal Notice Sent', 'father_name' => 'VIJAY CHAVAN'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Cases - Case Management</title>
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
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-briefcase text-blue-500 mr-3"></i>All Cases
                </h1>
                <p class="text-gray-600">View and manage all cases in the system</p>
            </div>

            <!-- Action Bar -->
            <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Search Bar -->
                    <div class="w-full sm:w-96">
                        <div class="relative">
                            <input type="text" placeholder="Search by loan number, customer name, or cheque no..."
                                class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    <!-- Add Case Button -->
                    <a href="create-case.php"
                        class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg text-center">
                        <i class="fas fa-plus mr-2"></i>Add New Case
                    </a>
                </div>
            </div>

            <!-- Cases Table/List -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Loan No.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Customer Name</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Product</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Branch</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Cheque No.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Cheque Amount</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Bounce Reason</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($cases as $case): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-blue-600"><?php echo $case['loan_number']; ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo $case['customer_name']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                                        <?php echo $case['product']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo $case['branch_name']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo $case['cheque_no']; ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">₹<?php echo number_format($case['cheque_amount']); ?></td>
                                <td class="px-6 py-4 text-sm text-red-600"><?php echo $case['bounce_reason']; ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 <?php 
                                        echo $case['status'] == 'Active' ? 'bg-green-100 text-green-800' : 
                                             ($case['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                             ($case['status'] == 'Closed' ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800')); 
                                    ?> rounded-full text-xs font-semibold">
                                        <?php echo $case['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-3">
                                        <a href="case-details.php?id=<?php echo $case['id']; ?>" class="text-blue-600 hover:text-blue-800 transition" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
                                        <button class="text-green-600 hover:text-green-800 transition" title="Edit">
                                            <i class="fas fa-edit text-lg"></i>
                                        </button>
                                        <button class="text-red-600 hover:text-red-800 transition" title="Delete">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden divide-y divide-gray-200">
                    <?php foreach ($cases as $case): ?>
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                        <?php echo $case['product']; ?>
                                    </span>
                                    <span class="inline-block px-2 py-1 <?php 
                                        echo $case['status'] == 'Active' ? 'bg-green-100 text-green-800' : 
                                             ($case['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                             ($case['status'] == 'Closed' ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800')); 
                                    ?> rounded-full text-xs font-semibold">
                                        <?php echo $case['status']; ?>
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900"><?php echo $case['customer_name']; ?></h3>
                                <p class="text-sm text-blue-600 font-semibold">Loan #<?php echo $case['loan_number']; ?></p>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-building w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Branch:</strong> <?php echo $case['branch_name']; ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-receipt w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Cheque:</strong> <?php echo $case['cheque_no']; ?> - ₹<?php echo number_format($case['cheque_amount']); ?></span>
                            </div>
                            <div class="flex items-start text-sm text-gray-600">
                                <i class="fas fa-exclamation-triangle w-5 text-red-500 mt-1"></i>
                                <span class="ml-2"><strong>Bounce Reason:</strong> <?php echo $case['bounce_reason']; ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-calendar w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Bounce Date:</strong> <?php echo $case['bounce_date']; ?></span>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-4 pt-3 border-t border-gray-200">
                            <a href="case-details.php?id=<?php echo $case['id']; ?>" class="flex items-center px-3 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                <i class="fas fa-eye mr-2"></i>View
                            </a>
                            <button class="flex items-center px-3 py-2 text-green-600 hover:bg-green-50 rounded-lg transition">
                                <i class="fas fa-edit mr-2"></i>Edit
                            </button>
                            <button class="flex items-center px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-600">Showing 1 to <?php echo count($cases); ?> of <?php echo count($cases); ?> entries</p>
                <div class="flex items-center space-x-2">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="px-4 py-2 bg-blue-500 text-white rounded-lg">1</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50" disabled>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
