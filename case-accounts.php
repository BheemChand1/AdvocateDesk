<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Create case_accounts table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS case_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    bill_number VARCHAR(100),
    bill_date DATE,
    payment_status ENUM('pending', 'processing', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    UNIQUE KEY unique_case_account (case_id)
);
";

mysqli_query($conn, $create_table_sql);

// Fetch counts for each status
$counts_sql = "
SELECT 
    COUNT(CASE WHEN ca.id IS NULL THEN 1 END) as pending_count,
    COUNT(CASE WHEN ca.payment_status = 'processing' THEN 1 END) as processing_count,
    COUNT(CASE WHEN ca.payment_status = 'completed' THEN 1 END) as completed_count
FROM cases c
LEFT JOIN case_accounts ca ON c.id = ca.case_id
";

$counts_result = mysqli_query($conn, $counts_sql);
$counts = mysqli_fetch_assoc($counts_result);

// Get total pending cases
$pending_cases_sql = "
SELECT COUNT(DISTINCT c.id) as total
FROM cases c
LEFT JOIN case_accounts ca ON c.id = ca.case_id
WHERE ca.id IS NULL
";
$pending_result = mysqli_query($conn, $pending_cases_sql);
$pending_count = mysqli_fetch_assoc($pending_result)['total'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Accounts - Case Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/style.css">
</head>

<body class="bg-gradient-to-br from-gray-200 via-gray-100 to-slate-200 min-h-screen">
    <?php include './includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <?php include './includes/navbar.php'; ?>
        
        <div class="content-area">
            <div class="w-full px-4 py-6">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-wallet text-blue-600 mr-3"></i>Case Accounts Management
                    </h1>
                    <p class="text-gray-600 text-lg">Manage case fee grids, bills, and payment status</p>
                </div>

                <!-- Status Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Pending Cases Card -->
                    <a href="pending-cases.php" class="group block">
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg shadow-lg hover:shadow-xl transition overflow-hidden border-2 border-yellow-300 h-full">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-2xl font-bold text-yellow-700">
                                        <i class="fas fa-hourglass-half mr-2"></i>Pending Cases
                                    </h2>
                                    <i class="fas fa-arrow-right text-yellow-600 text-2xl group-hover:translate-x-2 transition"></i>
                                </div>
                                <p class="text-gray-600 mb-4">Add bill date and bill number</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Total Cases:</span>
                                    <span class="text-4xl font-bold text-yellow-600"><?php echo $pending_count; ?></span>
                                </div>
                            </div>
                            <div class="bg-yellow-300 h-1 group-hover:h-2 transition"></div>
                        </div>
                    </a>

                    <!-- Processing Fees Card -->
                    <a href="processing-fees.php" class="group block">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-lg hover:shadow-xl transition overflow-hidden border-2 border-blue-300 h-full">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-2xl font-bold text-blue-700">
                                        <i class="fas fa-hourglass-start mr-2"></i>Processing Fees
                                    </h2>
                                    <i class="fas fa-arrow-right text-blue-600 text-2xl group-hover:translate-x-2 transition"></i>
                                </div>
                                <p class="text-gray-600 mb-4">View and manage fees in progress</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Total Cases:</span>
                                    <span class="text-4xl font-bold text-blue-600"><?php echo $counts['processing_count']; ?></span>
                                </div>
                            </div>
                            <div class="bg-blue-300 h-1 group-hover:h-2 transition"></div>
                        </div>
                    </a>

                    <!-- Completed Fees Card -->
                    <a href="completed-fees.php" class="group block">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow-lg hover:shadow-xl transition overflow-hidden border-2 border-green-300 h-full">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-2xl font-bold text-green-700">
                                        <i class="fas fa-check-circle mr-2"></i>Completed Fees
                                    </h2>
                                    <i class="fas fa-arrow-right text-green-600 text-2xl group-hover:translate-x-2 transition"></i>
                                </div>
                                <p class="text-gray-600 mb-4">View all completed and paid cases</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Total Cases:</span>
                                    <span class="text-4xl font-bold text-green-600"><?php echo $counts['completed_count']; ?></span>
                                </div>
                            </div>
                            <div class="bg-green-300 h-1 group-hover:h-2 transition"></div>
                        </div>
                    </a>
                </div>

                <!-- Info Section -->
                <div class="bg-white rounded-lg shadow-lg p-8 border-l-4 border-blue-600">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>How to Use
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex gap-4">
                            <div class="text-3xl text-yellow-600">
                                <i class="fas fa-1 font-bold"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Pending Cases</h4>
                                <p class="text-gray-600 text-sm">Click here to add bill details (bill number and date) for cases that don't have account information yet.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="text-3xl text-blue-600">
                                <i class="fas fa-2 font-bold"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Processing Fees</h4>
                                <p class="text-gray-600 text-sm">View cases where fees are being processed. Update the status when the payment process changes.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="text-3xl text-green-600">
                                <i class="fas fa-3 font-bold"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Completed Fees</h4>
                                <p class="text-gray-600 text-sm">View all cases with completed and paid fees. This is your final payment records.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include './includes/footer.php'; ?>

    <script src="./assets/script.js"></script>
</body>

</html>
