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

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_bill') {
        $case_id = mysqli_real_escape_string($conn, $_POST['case_id']);
        $bill_number = mysqli_real_escape_string($conn, $_POST['bill_number']);
        $bill_date = mysqli_real_escape_string($conn, $_POST['bill_date']);

        // Check if account already exists
        $check_sql = "SELECT id FROM case_accounts WHERE case_id = $case_id";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $update_sql = "UPDATE case_accounts SET bill_number = '$bill_number', bill_date = '$bill_date', payment_status = 'processing' WHERE case_id = $case_id";
            if (mysqli_query($conn, $update_sql)) {
                $message = "Bill details added and status changed to Processing!";
                $message_type = "success";
            } else {
                $message = "Error updating bill details: " . mysqli_error($conn);
                $message_type = "error";
            }
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO case_accounts (case_id, bill_number, bill_date, payment_status) VALUES ($case_id, '$bill_number', '$bill_date', 'processing')";
            if (mysqli_query($conn, $insert_sql)) {
                $message = "Bill details added and status changed to Processing!";
                $message_type = "success";
            } else {
                $message = "Error adding bill details: " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    }
}

// Fetch pending cases not in case_accounts
$pending_cases_sql = "
SELECT DISTINCT
    c.id,
    c.unique_case_id,
    c.case_type,
    cl.name as client_name
FROM cases c
JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_accounts ca ON c.id = ca.case_id
WHERE ca.id IS NULL
ORDER BY c.created_at DESC
";

$pending_cases_result = mysqli_query($conn, $pending_cases_sql);
$pending_cases = [];
while ($row = mysqli_fetch_assoc($pending_cases_result)) {
    $exists = false;
    foreach ($pending_cases as $pc) {
        if ($pc['id'] === $row['id']) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $pending_cases[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Cases - Case Accounts</title>
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
                        <i class="fas fa-hourglass-half text-yellow-600 mr-3"></i>Pending Cases
                    </h1>
                    <p class="text-gray-600">Add bill details to move cases to processing</p>
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

                <!-- Pending Cases Table -->
                <?php if (count($pending_cases) > 0): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>Add Bill Details
                        </h2>
                        <p class="text-gray-600 text-sm mt-1">Total Pending Cases: <strong><?php echo count($pending_cases); ?></strong></p>
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
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_cases as $case): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($case['unique_case_id']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($case['client_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($case['case_type']); ?></td>
                                        <td class="px-4 py-3">
                                            <form method="POST" class="flex gap-2 items-center">
                                                <input type="hidden" name="action" value="add_bill">
                                                <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                                <input type="text" name="bill_number" placeholder="Bill No." class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500" required>
                                                <input type="date" name="bill_date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500" required>
                                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                                                    <i class="fas fa-save mr-1"></i>Add
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-4 py-3"></td>
                                        <td class="px-4 py-3"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-check-double text-green-600 mr-2"></i>No Pending Cases
                        </h2>
                    </div>
                    <div class="p-12 text-center">
                        <i class="fas fa-check-double text-6xl text-green-300 mb-4"></i>
                        <p class="text-gray-600 text-lg">All cases have bill details! No pending cases.</p>
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
