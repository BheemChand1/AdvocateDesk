<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get client ID from URL (for now using sample data)
$client_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Sample client data (will be replaced with database query later)
$clients = [
    1 => ['id' => 1, 'full_name' => 'Ramesh Kumar', 'father_name' => 'Suresh Kumar', 'mobile' => '9876543210', 'address' => '123 Main Street, Delhi'],
    2 => ['id' => 2, 'full_name' => 'Priya Sharma', 'father_name' => 'Raj Sharma', 'mobile' => '9876543211', 'address' => '456 Park Avenue, Mumbai'],
    3 => ['id' => 3, 'full_name' => 'Amit Patel', 'father_name' => 'Vikram Patel', 'mobile' => '9876543212', 'address' => '789 Lake Road, Bangalore']
];

$client = isset($clients[$client_id]) ? $clients[$client_id] : $clients[1];

// Sample cases data (will be replaced with database query later)
$cases = [
    ['id' => 1, 'loan_number' => '1310746', 'product' => 'PL', 'branch_name' => 'DEHRADUN', 'cheque_no' => '000446', 'cheque_date' => '16.02.2018', 'cheque_amount' => '35784', 'bank_name_address' => 'HDFC, Rajpur rd', 'bounce_date' => '16.02.2018', 'bounce_reason' => 'Fund Insufficient', 'status' => 'Pending'],
    ['id' => 2, 'loan_number' => '1310747', 'product' => 'HL', 'branch_name' => 'DELHI', 'cheque_no' => '000447', 'cheque_date' => '20.03.2018', 'cheque_amount' => '45000', 'bank_name_address' => 'ICICI, Connaught Place', 'bounce_date' => '20.03.2018', 'bounce_reason' => 'Account Closed', 'status' => 'Active']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - Case Management</title>
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
                <a href="view-clients.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Clients
                </a>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-user-circle text-blue-500 mr-3"></i>Client Details
                </h1>
                <p class="text-gray-600">View client information and manage cases</p>
            </div>

            <!-- Client Information Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>Client Information
                    </h2>
                    <a href="edit-client.php?id=<?php echo $client['id']; ?>" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                        <i class="fas fa-edit mr-2"></i>Edit Client
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Client ID -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-hashtag text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Client ID</p>
                            <p class="text-lg font-semibold text-gray-800">#<?php echo str_pad($client['id'], 4, '0', STR_PAD_LEFT); ?></p>
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Full Name</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['full_name']); ?></p>
                        </div>
                    </div>

                    <!-- Father's Name -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user-tie text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Father's Name</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['father_name']); ?></p>
                        </div>
                    </div>

                    <!-- Mobile Number -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-phone text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Mobile Number</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['mobile']); ?></p>
                            <a href="tel:<?php echo $client['mobile']; ?>" class="text-sm text-blue-600 hover:text-blue-800">
                                <i class="fas fa-phone-alt mr-1"></i>Call Now
                            </a>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="flex items-start md:col-span-2">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Address</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['address']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cases Section -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">
                        <i class="fas fa-briefcase mr-2"></i>Cases (<?php echo count($cases); ?>)
                    </h2>
                    <a href="create-case.php?client_id=<?php echo $client['id']; ?>" 
                        class="px-4 py-2 bg-white text-blue-600 hover:bg-gray-100 rounded-lg transition font-semibold">
                        <i class="fas fa-plus mr-2"></i>Create New Case
                    </a>
                </div>

                <!-- Cases List -->
                <div class="p-6">
                    <?php if (count($cases) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($cases as $case): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex flex-col gap-4">
                                        <div class="flex items-center gap-3">
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                                                Loan #<?php echo $case['loan_number']; ?>
                                            </span>
                                            <span class="px-3 py-1 <?php echo $case['status'] == 'Active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> text-sm font-semibold rounded-full">
                                                <?php echo $case['status']; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <p class="text-gray-500 mb-1">Product</p>
                                                <p class="font-semibold text-gray-800"><?php echo $case['product']; ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 mb-1">Branch Name</p>
                                                <p class="font-semibold text-gray-800"><?php echo $case['branch_name']; ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 mb-1">Cheque No</p>
                                                <p class="font-semibold text-gray-800"><?php echo $case['cheque_no']; ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 mb-1">Cheque Date</p>
                                                <p class="font-semibold text-gray-800"><?php echo $case['cheque_date']; ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 mb-1">Cheque Amount</p>
                                                <p class="font-semibold text-gray-800">₹<?php echo number_format($case['cheque_amount']); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 mb-1">Bank Name & Address</p>
                                                <p class="font-semibold text-gray-800"><?php echo $case['bank_name_address']; ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 mb-1">Bounce Date</p>
                                                <p class="font-semibold text-gray-800"><?php echo $case['bounce_date']; ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 mb-1">Bounce Reason</p>
                                                <p class="font-semibold text-red-600"><?php echo $case['bounce_reason']; ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200">
                                            <button class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                                                <i class="fas fa-eye mr-2"></i>View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg mb-4">No cases found for this client</p>
                            <a href="create-case.php?client_id=<?php echo $client['id']; ?>" 
                                class="inline-block px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                                <i class="fas fa-plus mr-2"></i>Create First Case
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
