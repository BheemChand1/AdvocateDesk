<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Fetch completed accounts data
$completed_sql = "
SELECT 
    ca.id,
    ca.case_id,
    ca.bill_number,
    ca.bill_date,
    ca.payment_status,
    c.unique_case_id,
    c.case_type,
    cl.name as client_name,
    COALESCE(SUM(cfg.fee_amount), 0) as total_fee_amount
FROM case_accounts ca
JOIN cases c ON ca.case_id = c.id
JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_fee_grid cfg ON c.id = cfg.case_id
WHERE ca.payment_status = 'completed'
GROUP BY ca.id, ca.case_id, ca.bill_number, ca.bill_date, ca.payment_status, c.unique_case_id, c.case_type, cl.name
ORDER BY ca.updated_at DESC
";

$completed_result = mysqli_query($conn, $completed_sql);
$completed_accounts = [];
while ($row = mysqli_fetch_assoc($completed_result)) {
    $completed_accounts[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Fees - Case Accounts</title>
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
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>Completed Fees
                    </h1>
                    <p class="text-gray-600">View all completed and paid cases</p>
                </div>

                <!-- Navigation Buttons -->
                <div class="mb-6 flex gap-3 flex-wrap">
                    <a href="case-accounts.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg font-medium hover:bg-gray-600 transition">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="pending-cases.php" class="px-6 py-2 bg-yellow-500 text-white rounded-lg font-medium hover:bg-yellow-600 transition">
                        <i class="fas fa-hourglass-half mr-2"></i>Pending Cases
                    </a>
                    <a href="processing-fees.php" class="px-6 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition">
                        <i class="fas fa-clock mr-2"></i>Processing Fees
                    </a>
                    <a href="completed-fees.php" class="px-6 py-2 bg-green-500 text-white rounded-lg font-medium hover:bg-green-600 transition">
                        <i class="fas fa-check-circle mr-2"></i>Completed Fees
                    </a>
                </div>

                <!-- Completed Fees Table -->
                <?php if (!empty($completed_accounts)): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>Completed Fees List
                        </h2>
                        <p class="text-gray-600 text-sm mt-1">Total Completed Cases: <strong><?php echo count($completed_accounts); ?></strong></p>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-100 border-b-2 border-gray-300">
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Client Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case Type</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Bill Number</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Bill Date</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fee Amount</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_accounts as $account): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($account['unique_case_id']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['client_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['case_type']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['bill_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['bill_date'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 font-semibold text-green-600">₹<?php echo number_format($account['total_fee_amount'], 2); ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                <i class="fas fa-check-circle mr-1"></i>Completed
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-inbox text-green-600 mr-2"></i>No Completed Fees
                        </h2>
                    </div>
                    <div class="p-12 text-center">
                        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600 text-lg">No completed fees yet.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include './includes/footer.php'; ?>

    <script src="./assets/script.js"></script>
</body>

</html>
