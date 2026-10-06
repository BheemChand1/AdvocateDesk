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
    } elseif ($_POST['action'] === 'edit_fee') {
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);
        $fee_amount = floatval($_POST['fee_amount']);

        $update_sql = "UPDATE case_position_updates SET fee_amount = $fee_amount WHERE id = $update_id";
        if (mysqli_query($conn, $update_sql)) {
            $message = "Fee amount updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating fee amount: " . mysqli_error($conn);
            $message_type = "error";
        }
    } elseif ($_POST['action'] === 'delete_fee') {
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);

        $delete_sql = "DELETE FROM case_position_updates WHERE id = $update_id";
        if (mysqli_query($conn, $delete_sql)) {
            $message = "Pending case deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting pending case: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// Pagination settings
$entries_per_page_param = isset($_GET['entries_per_page']) ? $_GET['entries_per_page'] : '25';
if ($entries_per_page_param === 'all') {
    $entries_per_page = 999999; // Large number to show all
} else {
    $entries_per_page = intval($entries_per_page_param);
}
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;

// Fetch pending cases with pending payment status from case_position_updates
$pending_cases_sql = "
SELECT 
    c.id,
    c.unique_case_id,
    c.case_type,
    c.cnr_number,
    c.loan_number,
    COALESCE(
        ni.case_no,
        cr.case_no,
        cc.case_no,
        ep.case_no,
        ao.case_no
    ) as case_no,
    cl.name as client_name,
    (SELECT GROUP_CONCAT(DISTINCT CASE 
        WHEN party_type IN ('complainant', 'decree_holder', 'plaintiff') THEN name 
    END ORDER BY is_primary DESC SEPARATOR ', ') 
    FROM case_parties WHERE case_id = c.id) as plaintiff_parties,
    (SELECT GROUP_CONCAT(DISTINCT CASE 
        WHEN party_type IN ('accused', 'defendant') THEN name 
    END ORDER BY is_primary DESC SEPARATOR ', ') 
    FROM case_parties WHERE case_id = c.id) as defendant_parties,
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
        OR c.loan_number LIKE '" . $search_term . "'
        OR cl.name LIKE '" . $search_term . "'
        OR COALESCE(ni.case_no, cr.case_no, cc.case_no, ep.case_no, ao.case_no) LIKE '" . $search_term . "'
        OR cp.name LIKE '" . $search_term . "'
    )";
}

$pending_cases_sql .= "
GROUP BY c.id, cpu.id
ORDER BY c.created_at DESC, cpu.update_date DESC, cpu.position
";

// Get total count - use subquery to count grouped results
$grouped_query = explode('LIMIT', $pending_cases_sql)[0]; // Remove LIMIT/OFFSET
$count_query = 'SELECT COUNT(*) as total FROM (' . $grouped_query . ') as grouped_subquery';
$count_result = mysqli_query($conn, $count_query);
$total_cases = 0;
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_cases = $count_row['total'];
}

// Calculate pagination
$total_pages = ceil($total_cases / $entries_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

$offset = ($current_page - 1) * $entries_per_page;

// Add LIMIT and OFFSET to main query
$pending_cases_sql .= " LIMIT " . intval($entries_per_page) . " OFFSET " . intval($offset);

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
                            <p class="text-gray-600 text-sm mt-1">Total Pending Cases: <strong><?php echo $total_cases; ?></strong></p>
                        </div>
                        <div>
                            <label class="text-gray-700 text-sm font-semibold mr-2">Show Entries:</label>
                            <select id="entriesPerPage" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" onchange="changeEntriesPerPage(this.value)">
                                <option value="10" <?php echo $entries_per_page_param == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $entries_per_page_param == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $entries_per_page_param == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $entries_per_page_param == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="all" <?php echo $entries_per_page_param === 'all' ? 'selected' : ''; ?>>All</option>
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
                                        placeholder="Search by Case ID, Loan, Case No., CNR No., Client Name, or Opposite Party..." 
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
                                onclick="openPrintPage()" 
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
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Loan</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case No.</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">CNR No.</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Client Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Accused/Opposite Party</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Case Type</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fee Name</th>
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
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap"><?php echo htmlspecialchars($case['loan_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap"><?php echo htmlspecialchars($case['case_no'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap"><?php echo htmlspecialchars($case['cnr_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($case['client_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <?php 
                                            $plaintiffs = htmlspecialchars($case['plaintiff_parties'] ?? '');
                                            $defendants = htmlspecialchars($case['defendant_parties'] ?? '');
                                            
                                            if ($plaintiffs && $defendants) {
                                                echo $plaintiffs . ' <span class="font-semibold text-blue-600">vs</span> ' . $defendants;
                                            } elseif ($plaintiffs) {
                                                echo $plaintiffs;
                                            } elseif ($defendants) {
                                                echo $defendants;
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap"><?php echo htmlspecialchars($case['case_type']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap"><?php echo htmlspecialchars($case['fee_name'] ?? 'N/A'); ?></td>
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
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <div class="flex gap-1 justify-center items-center">
                                                <button type="submit" class="p-2 bg-yellow-600 text-white rounded text-xs hover:bg-yellow-700 transition" title="Add Bill">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                                <button type="button" onclick="openEditModal(<?php echo $case['update_id']; ?>, <?php echo $case['fee_amount']; ?>)" class="p-2 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition" title="Edit Fee">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </form>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this pending case?');">
                                                    <input type="hidden" name="action" value="delete_fee">
                                                    <input type="hidden" name="update_id" value="<?php echo $case['update_id']; ?>">
                                                    <button type="submit" class="p-2 bg-red-500 text-white text-xs rounded hover:bg-red-600 transition" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
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
                            Showing <span id="startEntry"><?php echo $total_cases > 0 ? ($offset + 1) : 0; ?></span> to <span id="endEntry"><?php echo min($offset + $entries_per_page, $total_cases); ?></span> of <span id="totalEntries"><?php echo $total_cases; ?></span> entries
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php 
                            // Build query string for pagination links
                            $query_params = [];
                            if (!empty($search_query)) $query_params['search'] = $search_query;
                            $query_params['entries_per_page'] = $entries_per_page;
                            
                            function build_pagination_url($page_num, $params) {
                                $params['page'] = $page_num;
                                return 'pending-cases.php?' . http_build_query($params);
                            }
                            ?>
                            <a href="<?php echo build_pagination_url($current_page - 1, $query_params); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition <?php echo $current_page === 1 ? 'disabled:opacity-50 disabled:cursor-not-allowed pointer-events-none opacity-50' : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <div id="pageNumbers" class="flex gap-1">
                                <?php 
                                // Show first page
                                if ($total_pages > 0) {
                                    $btn_class = $current_page === 1 ? 'px-3 py-2 bg-yellow-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url(1, $query_params) . '" class="' . $btn_class . '">1</a>';
                                }
                                
                                // Show pages around current page
                                for ($i = max(2, $current_page - 1); $i <= min($total_pages - 1, $current_page + 1); $i++) {
                                    $btn_class = $current_page === $i ? 'px-3 py-2 bg-yellow-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url($i, $query_params) . '" class="' . $btn_class . '">' . $i . '</a>';
                                }
                                
                                // Show last page
                                if ($total_pages > 1) {
                                    $btn_class = $current_page === $total_pages ? 'px-3 py-2 bg-yellow-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url($total_pages, $query_params) . '" class="' . $btn_class . '">' . $total_pages . '</a>';
                                }
                                ?>
                            </div>
                            <a href="<?php echo build_pagination_url($current_page + 1, $query_params); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition <?php echo $current_page === $total_pages || $total_pages === 0 ? 'disabled:opacity-50 disabled:cursor-not-allowed pointer-events-none opacity-50' : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
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

    <!-- Edit Fee Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-edit text-yellow-500 mr-2"></i>Edit Fee Amount
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
                    &times;
                </button>
            </div>
            
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="edit_fee">
                <input type="hidden" name="update_id" id="editUpdateId">
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Fee Amount</label>
                    <input type="number" id="editFeeAmount" name="fee_amount" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeEditModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                        <i class="fas fa-save mr-2"></i>Update Amount
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="./assets/script.js"></script>
    <script>
        // Change entries per page
        function changeEntriesPerPage(value) {
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('entries_per_page', value);
            searchParams.set('page', '1');
            window.location.href = 'pending-cases.php?' + searchParams.toString();
        }

        // Print function - open in new window
        function openPrintPage() {
            const searchParams = new URLSearchParams(window.location.search);
            const search = searchParams.get('search') || '';
            
            let queryString = '';
            if (search) {
                queryString = '?search=' + encodeURIComponent(search);
            }
            
            window.open('print-pending-cases.php' + queryString, 'printWindow', 'width=1400,height=900');
        }

        // Edit modal functions
        function openEditModal(updateId, feeAmount) {
            document.getElementById('editUpdateId').value = updateId;
            document.getElementById('editFeeAmount').value = feeAmount;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>

</html>