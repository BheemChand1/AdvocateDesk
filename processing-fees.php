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
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-hourglass-start text-blue-600 mr-2"></i>Processing Fees List
                            </h2>
                            <p class="text-gray-600 text-sm mt-1">Total Processing Cases: <strong><?php echo count($processing_accounts); ?></strong></p>
                        </div>
                        <div>
                            <label class="text-gray-700 text-sm font-semibold mr-2">Show Entries:</label>
                            <select id="entriesPerPage" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="all">Show All</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full" id="processingTable">
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
                                <tbody id="processingTableBody">
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
                    
                    <!-- Pagination -->
                    <div class="p-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Showing <span id="startEntry">1</span> to <span id="endEntry">25</span> of <span id="totalEntries"><?php echo count($processing_accounts); ?></span> entries
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="previousPage()" id="prevBtn" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div id="pageNumbers" class="flex gap-1">
                                <!-- Page numbers will be added by JavaScript -->
                            </div>
                            <button onclick="nextPage()" id="nextBtn" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50">
                                <i class="fas fa-chevron-right"></i>
                            </button>
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
    <script>
        let currentPage = 1;
        let entriesPerPage = 25;
        let allRows = [];
        
        window.addEventListener('load', function() {
            allRows = Array.from(document.querySelectorAll('#processingTableBody tr'));
            
            const entriesDropdown = document.getElementById('entriesPerPage');
            if (entriesDropdown) {
                entriesDropdown.addEventListener('change', function() {
                    if (this.value === 'all') {
                        entriesPerPage = allRows.length;
                    } else {
                        entriesPerPage = parseInt(this.value);
                    }
                    currentPage = 1;
                    updatePagination();
                });
            }
            
            updatePagination();
        });
        
        function updatePagination() {
            const totalPages = Math.ceil(allRows.length / entriesPerPage);
            
            allRows.forEach(row => row.style.display = 'none');
            
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = startIndex + entriesPerPage;
            
            for (let i = startIndex; i < endIndex && i < allRows.length; i++) {
                allRows[i].style.display = '';
            }
            
            document.getElementById('startEntry').textContent = allRows.length > 0 ? startIndex + 1 : 0;
            document.getElementById('endEntry').textContent = Math.min(endIndex, allRows.length);
            document.getElementById('totalEntries').textContent = allRows.length;
            
            updatePageNumbers(totalPages);
            
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = currentPage === totalPages || totalPages === 0;
        }
        
        function updatePageNumbers(totalPages) {
            const pageNumbersDiv = document.getElementById('pageNumbers');
            pageNumbersDiv.innerHTML = '';
            
            if (totalPages > 0) {
                const btn = createPageButton(1);
                pageNumbersDiv.appendChild(btn);
            }
            
            for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
                const btn = createPageButton(i);
                pageNumbersDiv.appendChild(btn);
            }
            
            if (totalPages > 1) {
                const btn = createPageButton(totalPages);
                pageNumbersDiv.appendChild(btn);
            }
        }
        
        function createPageButton(pageNum) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = pageNum;
            btn.className = pageNum === currentPage 
                ? 'px-3 py-2 bg-blue-500 text-white rounded-lg font-semibold'
                : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
            btn.onclick = () => {
                currentPage = pageNum;
                updatePagination();
            };
            return btn;
        }
        
        function nextPage() {
            const totalPages = Math.ceil(allRows.length / entriesPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                updatePagination();
            }
        }
        
        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                updatePagination();
            }
        }
    </script>
</body>

</html>
