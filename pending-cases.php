<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Ensure case_position_updates has bill columns
$check_bill_column = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_NAME='case_position_updates' AND COLUMN_NAME='bill_number'";
$result = mysqli_query($conn, $check_bill_column);

if (mysqli_num_rows($result) === 0) {
    // Add bill columns if they don't exist
    mysqli_query($conn, "ALTER TABLE case_position_updates ADD COLUMN bill_number VARCHAR(100)");
    mysqli_query($conn, "ALTER TABLE case_position_updates ADD COLUMN bill_date DATE");
}

// Create case_accounts table if it doesn't exist (for backward compatibility)
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

// Get search parameter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_bill') {
        $case_id = mysqli_real_escape_string($conn, $_POST['case_id']);
        $bill_number = mysqli_real_escape_string($conn, $_POST['bill_number']);
        $bill_date = mysqli_real_escape_string($conn, $_POST['bill_date']);
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);

        // Update the case_position_updates record with bill details and change status to processing
        $update_sql = "UPDATE case_position_updates 
                       SET bill_number = '$bill_number', bill_date = '$bill_date', payment_status = 'processing' 
                       WHERE id = $update_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $message = "Bill details added and status changed to Processing!";
            $message_type = "success";
        } else {
            $message = "Error updating bill details: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Fetch pending cases with pending payment status from case_position_updates
$pending_cases_sql = "
SELECT 
    c.id,
    c.unique_case_id,
    c.case_type,
    c.cnr_number,
    COALESCE(
        ni.case_no,
        cr.case_no,
        cc.case_no,
        ep.case_no,
        ao.case_no
    ) as case_no,
    cl.name as client_name,
    GROUP_CONCAT(DISTINCT CASE 
        WHEN cp.party_type IN ('accused', 'defendant') THEN cp.name 
    END SEPARATOR ', ') as accused_opposite_party,
    cpu.fee_amount,
    cpu.position as fee_name,
    cpu.payment_status,
    cpu.id as update_id,
    cpu.update_date,
    cpu.bill_number,
    cpu.bill_date
FROM cases c
JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
LEFT JOIN case_position_updates cpu ON c.id = cpu.case_id AND cpu.payment_status = 'pending'
WHERE cpu.id IS NOT NULL AND cpu.fee_amount > 0";

// Apply search filter
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $pending_cases_sql .= " AND (
        c.cnr_number LIKE '" . $search_term . "'
        OR c.unique_case_id LIKE '" . $search_term . "'
        OR cl.name LIKE '" . $search_term . "'
        OR COALESCE(ni.case_no, cr.case_no, cc.case_no, ep.case_no, ao.case_no) LIKE '" . $search_term . "'
        OR cp.name LIKE '" . $search_term . "'
    )";
}

$pending_cases_sql .= "
GROUP BY c.id, cpu.id
ORDER BY c.created_at DESC, cpu.update_date DESC, cpu.position
";

$pending_cases_result = mysqli_query($conn, $pending_cases_sql);
$pending_cases = [];
while ($row = mysqli_fetch_assoc($pending_cases_result)) {
    $pending_cases[] = $row;
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
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>Add Bill Details
                            </h2>
                            <p class="text-gray-600 text-sm mt-1">Total Pending Cases: <strong><?php echo count($pending_cases); ?></strong></p>
                        </div>
                        <div>
                            <label class="text-gray-700 text-sm font-semibold mr-2">Show Entries:</label>
                            <select id="entriesPerPage" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="all">Show All</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-6">
                        <!-- Search Filter -->
                        <div class="mb-6 flex gap-3 items-center flex-wrap">
                            <form method="GET" class="w-full flex gap-3 items-center">
                                <div class="flex-1 min-w-[250px]">
                                    <input 
                                        type="text" 
                                        name="search" 
                                        placeholder="Search by Case ID, Case No., CNR No., Client Name, or Opposite Party..." 
                                        value="<?php echo htmlspecialchars($search_query); ?>"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                    >
                                </div>
                                <button 
                                    type="submit" 
                                    class="px-6 py-2 bg-yellow-500 text-white rounded-lg font-medium hover:bg-yellow-600 transition whitespace-nowrap"
                                >
                                    <i class="fas fa-search mr-2"></i>Search
                                </button>
                                <?php if (!empty($search_query)): ?>
                                <a 
                                    href="pending-cases.php" 
                                    class="px-6 py-2 bg-gray-400 text-white rounded-lg font-medium hover:bg-gray-500 transition whitespace-nowrap"
                                >
                                    <i class="fas fa-times mr-2"></i>Clear
                                </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <!-- Export and Print Buttons -->
                        <div class="mb-6 flex gap-3 flex-wrap">
                            <a 
                                href="export-pending-cases.php<?php echo !empty($search_query) ? '?search=' . urlencode($search_query) : ''; ?>" 
                                class="px-6 py-2 bg-green-500 text-white rounded-lg font-medium hover:bg-green-600 transition flex items-center gap-2"
                            >
                                <i class="fas fa-file-excel"></i>Export to Excel
                            </a>
                            <button 
                                onclick="printTable('pendingTable')" 
                                class="px-6 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition flex items-center gap-2"
                            >
                                <i class="fas fa-print"></i>Print
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full" id="pendingTable">
                                <thead>
                                    <tr class="bg-gray-100 border-b-2 border-gray-300">
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case No.</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">CNR No.</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Client Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Accused/Opposite Party</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case Type</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fee Amount</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Bill Number</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Bill Date</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingTableBody">
                                    <?php foreach ($pending_cases as $case): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($case['unique_case_id']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($case['case_no'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($case['cnr_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($case['client_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($case['accused_opposite_party'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($case['case_type']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 font-semibold text-blue-600">₹<?php echo number_format($case['fee_amount'], 2); ?></td>
                                        <td class="px-4 py-3">
                                            <form method="POST" class="flex gap-2 items-center">
                                                <input type="hidden" name="action" value="add_bill">
                                                <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                                <input type="hidden" name="update_id" value="<?php echo $case['update_id']; ?>">
                                                <input type="text" name="bill_number" placeholder="Bill No." class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-yellow-500" required>
                                        </td>
                                        <td class="px-4 py-3">
                                                <input type="date" name="bill_date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-yellow-500" required>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm font-medium hover:bg-yellow-700 transition">
                                                    <i class="fas fa-save mr-1"></i>Add
                                                </button>
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
                            Showing <span id="startEntry">1</span> to <span id="endEntry">25</span> of <span id="totalEntries"><?php echo count($pending_cases); ?></span> entries
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
    <script>
        let currentPage = 1;
        let entriesPerPage = 25;
        let allRows = [];
        
        window.addEventListener('load', function() {
            allRows = Array.from(document.querySelectorAll('#pendingTableBody tr'));
            
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
                ? 'px-3 py-2 bg-yellow-500 text-white rounded-lg font-semibold'
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

        // Print function
        function printTable(tableId) {
            const table = document.getElementById(tableId);
            const printWindow = window.open('', '', 'height=800,width=1000');
            printWindow.document.write('<html><head><title>Pending Cases</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('table { border-collapse: collapse; width: 100%; }');
            printWindow.document.write('th, td { border: 1px solid #000; padding: 8px; text-align: left; }');
            printWindow.document.write('th { background-color: #f3f4f6; font-weight: bold; }');
            printWindow.document.write('body { font-family: Arial, sans-serif; }');
            printWindow.document.write('</style></head><body>');
            printWindow.document.write('<h2>Pending Cases Report</h2>');
            printWindow.document.write(table.outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>

</html>
