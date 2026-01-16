<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Ensure case_position_updates has completed_date column
$check_column_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_NAME='case_position_updates' AND COLUMN_NAME='completed_date'";
$result = mysqli_query($conn, $check_column_query);

if (mysqli_num_rows($result) === 0) {
    // Add completed_date column if it doesn't exist
    mysqli_query($conn, "ALTER TABLE case_position_updates ADD COLUMN completed_date DATE");
}

// Fetch completed fees (show only fees with completed payment status from case_position_updates)
$completed_sql = "
SELECT 
    cpu.id as update_id,
    cpu.case_id,
    cpu.bill_number,
    cpu.bill_date,
    cpu.payment_status,
    cpu.completed_date,
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
    cpu.update_date
FROM case_position_updates cpu
JOIN cases c ON cpu.case_id = c.id
JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
WHERE cpu.payment_status = 'completed' AND cpu.fee_amount > 0
GROUP BY cpu.id
ORDER BY cpu.completed_date DESC, cpu.update_date DESC
";

$completed_result = mysqli_query($conn, $completed_sql);
$completed_accounts = [];
while ($row = mysqli_fetch_assoc($completed_result)) {
    $completed_accounts[] = $row;
}

// Handle edit_fee and delete_fee actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'edit_fee') {
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);
        $bill_number = mysqli_real_escape_string($conn, $_POST['bill_number']);
        $bill_date = mysqli_real_escape_string($conn, $_POST['bill_date']);
        $fee_amount = floatval($_POST['fee_amount']);
        
        $update_sql = "UPDATE case_position_updates SET bill_number = '$bill_number', bill_date = '$bill_date', fee_amount = $fee_amount WHERE id = $update_id";
        
        if (mysqli_query($conn, $update_sql)) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=Fee updated successfully");
            exit();
        } else {
            $error = "Error updating fee: " . mysqli_error($conn);
        }
    } elseif ($_POST['action'] === 'delete_fee') {
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);
        
        $delete_sql = "DELETE FROM case_position_updates WHERE id = $update_id";
        
        if (mysqli_query($conn, $delete_sql)) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=Fee deleted successfully");
            exit();
        } else {
            $error = "Error deleting fee: " . mysqli_error($conn);
        }
    }
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
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>Completed Fees List
                            </h2>
                            <p class="text-gray-600 text-sm mt-1">Total Completed Cases: <strong><?php echo count($completed_accounts); ?></strong></p>
                        </div>
                        <div>
                            <label class="text-gray-700 text-sm font-semibold mr-2">Show Entries:</label>
                            <select id="entriesPerPage" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
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
                            <table class="w-full" id="completedTable">
                                <thead>
                                    <tr class="bg-gray-100 border-b-2 border-gray-300">
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case No.</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">CNR No.</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Client Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Accused/Opposite Party</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case Type</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Bill Number</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Bill Date</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fee Amount</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Completed Date</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="completedTableBody">
                                    <?php foreach ($completed_accounts as $account): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($account['unique_case_id']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['case_no'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['cnr_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['client_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['accused_opposite_party'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['case_type']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['bill_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['bill_date'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 font-semibold text-green-600">₹<?php echo number_format($account['fee_amount'], 2); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 font-semibold text-green-600">
                                            <?php echo $account['completed_date'] ? date('d M, Y', strtotime($account['completed_date'])) : 'N/A'; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                <i class="fas fa-check-circle mr-1"></i>Completed
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode([
                                                'update_id' => $account['update_id'],
                                                'bill_number' => $account['bill_number'] ?? '',
                                                'bill_date' => $account['bill_date'] ?? '',
                                                'fee_amount' => $account['fee_amount']
                                            ])); ?>)" class="px-3 py-1 bg-blue-500 text-white text-xs rounded-lg hover:bg-blue-600 transition mr-2">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this fee? This action cannot be undone.');">
                                                <input type="hidden" name="action" value="delete_fee">
                                                <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($account['update_id']); ?>">
                                                <button type="submit" class="px-3 py-1 bg-red-500 text-white text-xs rounded-lg hover:bg-red-600 transition">
                                                    <i class="fas fa-trash mr-1"></i>Delete
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
                            Showing <span id="startEntry">1</span> to <span id="endEntry">25</span> of <span id="totalEntries"><?php echo count($completed_accounts); ?></span> entries
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

    <!-- Edit Fee Modal -->
    <div id="editFeeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target === this) closeEditModal();">
        <div class="bg-white rounded-lg shadow-lg w-96 p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Edit Fee Details</h2>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_fee">
                <input type="hidden" name="update_id" id="editUpdateId">
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Bill Number</label>
                    <input type="text" id="editBillNumber" name="bill_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Bill Date</label>
                    <input type="date" id="editBillDate" name="bill_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Fee Amount</label>
                    <input type="number" id="editFeeAmount" name="fee_amount" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition">
                        Update Fee
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include './includes/footer.php'; ?>

    <script src="./assets/script.js"></script>
    
    <script>
        function openEditModal(data) {
            document.getElementById('editUpdateId').value = data.update_id;
            document.getElementById('editBillNumber').value = data.bill_number;
            document.getElementById('editBillDate').value = data.bill_date;
            document.getElementById('editFeeAmount').value = data.fee_amount;
            document.getElementById('editFeeModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editFeeModal').classList.add('hidden');
        }
    </script>
    
    <script>
        let currentPage = 1;
        let entriesPerPage = 25;

        // Initialize pagination on page load
        window.addEventListener('load', function() {
            setupPagination();
        });

        // Handle entries per page change
        document.getElementById('entriesPerPage').addEventListener('change', function() {
            entriesPerPage = this.value === 'all' ? document.querySelectorAll('#completedTableBody tr').length : parseInt(this.value);
            currentPage = 1;
            updatePagination();
        });

        function setupPagination() {
            const totalRows = document.querySelectorAll('#completedTableBody tr').length;
            document.getElementById('totalEntries').textContent = totalRows;
            updatePagination();
        }

        function updatePagination() {
            const rows = document.querySelectorAll('#completedTableBody tr');
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / entriesPerPage);

            // Update row visibility
            rows.forEach((row, index) => {
                const pageNum = Math.ceil((index + 1) / entriesPerPage);
                row.style.display = pageNum === currentPage ? '' : 'none';
            });

            // Update pagination info
            const startEntry = currentPage === 1 ? 1 : (currentPage - 1) * entriesPerPage + 1;
            const endEntry = Math.min(currentPage * entriesPerPage, totalRows);
            document.getElementById('startEntry').textContent = totalRows === 0 ? 0 : startEntry;
            document.getElementById('endEntry').textContent = endEntry;

            // Update button states
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = currentPage === totalPages;

            // Update page numbers
            updatePageNumbers(totalPages);
        }

        function updatePageNumbers(totalPages) {
            const pageNumbersContainer = document.getElementById('pageNumbers');
            pageNumbersContainer.innerHTML = '';

            if (totalPages <= 1) return;

            // Show first page
            pageNumbersContainer.appendChild(createPageButton(1));

            // Show pages around current page
            const startPage = Math.max(2, currentPage - 1);
            const endPage = Math.min(totalPages - 1, currentPage + 1);

            // Add ellipsis if needed
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'px-2 py-2 text-gray-600';
                ellipsis.textContent = '...';
                pageNumbersContainer.appendChild(ellipsis);
            }

            // Add middle pages
            for (let i = startPage; i <= endPage; i++) {
                pageNumbersContainer.appendChild(createPageButton(i));
            }

            // Add ellipsis if needed
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'px-2 py-2 text-gray-600';
                ellipsis.textContent = '...';
                pageNumbersContainer.appendChild(ellipsis);
            }

            // Show last page
            if (totalPages > 1) {
                pageNumbersContainer.appendChild(createPageButton(totalPages));
            }
        }

        function createPageButton(pageNum) {
            const button = document.createElement('button');
            button.className = `px-3 py-2 rounded-lg transition ${
                currentPage === pageNum 
                    ? 'bg-green-500 text-white' 
                    : 'border border-gray-300 text-gray-600 hover:bg-gray-50'
            }`;
            button.textContent = pageNum;
            button.onclick = function() {
                currentPage = pageNum;
                updatePagination();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
            return button;
        }

        function nextPage() {
            const rows = document.querySelectorAll('#completedTableBody tr');
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / entriesPerPage);
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
