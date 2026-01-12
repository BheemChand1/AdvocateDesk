<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_payment') {
        $case_id = mysqli_real_escape_string($conn, $_POST['case_id']);
        $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);

        $update_sql = "UPDATE case_accounts SET payment_status = '$payment_status' WHERE case_id = $case_id";
        if (mysqli_query($conn, $update_sql)) {
            $message = "Payment status updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating payment status: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Fetch processing accounts data
$processing_sql = "
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
WHERE ca.payment_status = 'processing'
GROUP BY ca.id, ca.case_id, ca.bill_number, ca.bill_date, ca.payment_status, c.unique_case_id, c.case_type, cl.name
ORDER BY ca.updated_at DESC
";

$processing_result = mysqli_query($conn, $processing_sql);
$processing_accounts = [];
while ($row = mysqli_fetch_assoc($processing_result)) {
    $processing_accounts[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Fees - Case Accounts</title>
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
                        <i class="fas fa-hourglass-start text-blue-600 mr-3"></i>Processing Fees
                    </h1>
                    <p class="text-gray-600">View and manage fees in processing status</p>
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

                <!-- Message Alert -->
                <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <!-- Processing Fees Table -->
                <?php if (!empty($processing_accounts)): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-hourglass-start text-blue-600 mr-2"></i>Processing Fees List
                        </h2>
                        <p class="text-gray-600 text-sm mt-1">Total Processing Cases: <strong><?php echo count($processing_accounts); ?></strong></p>
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
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Update Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($processing_accounts as $account): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($account['unique_case_id']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['client_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['case_type']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['bill_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['bill_date'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 font-semibold text-blue-600">₹<?php echo number_format($account['total_fee_amount'], 2); ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="update_payment">
                                                <input type="hidden" name="case_id" value="<?php echo $account['case_id']; ?>">
                                                <select name="payment_status" class="px-2 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500" onchange="this.form.submit()">
                                                    <option value="">Select Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="processing" selected>Processing</option>
                                                    <option value="completed">Completed</option>
                                                </select>
                                            </form>
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
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-inbox text-blue-600 mr-2"></i>No Processing Fees
                        </h2>
                    </div>
                    <div class="p-12 text-center">
                        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600 text-lg">No fees in processing status.</p>
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
