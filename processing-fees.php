<?php
// Suppress output errors to prevent "headers already sent" issues
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

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
$result = @mysqli_query($conn, $check_column_query);

if ($result && mysqli_num_rows($result) === 0) {
    // Add completed_date column if it doesn't exist
    @mysqli_query($conn, "ALTER TABLE case_position_updates ADD COLUMN completed_date DATE");
}

// Handle form submissions
$message = '';
$message_type = '';

// Get search parameter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_payment') {
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);
        $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
        $completed_date = ($payment_status === 'completed') ? date('Y-m-d') : null;

        if ($completed_date) {
            $update_sql = "UPDATE case_position_updates SET payment_status = '$payment_status', completed_date = '$completed_date' WHERE id = $update_id";
        } else {
            $update_sql = "UPDATE case_position_updates SET payment_status = '$payment_status' WHERE id = $update_id";
        }
        
        if (mysqli_query($conn, $update_sql)) {
            $message = "Payment status updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating payment status: " . mysqli_error($conn);
            $message_type = "error";
        }
    } elseif ($_POST['action'] === 'edit_fee') {
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);
        $bill_number = mysqli_real_escape_string($conn, $_POST['bill_number']);
        $bill_date = mysqli_real_escape_string($conn, $_POST['bill_date']);
        $fee_amount = floatval($_POST['fee_amount']);

        $update_sql = "UPDATE case_position_updates SET bill_number = '$bill_number', bill_date = '$bill_date', fee_amount = $fee_amount WHERE id = $update_id";
        if (mysqli_query($conn, $update_sql)) {
            $message = "Fee details updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating fee details: " . mysqli_error($conn);
            $message_type = "error";
        }
    } elseif ($_POST['action'] === 'delete_fee') {
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);

        $delete_sql = "DELETE FROM case_position_updates WHERE id = $update_id";
        if (mysqli_query($conn, $delete_sql)) {
            $message = "Fee record deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting fee record: " . mysqli_error($conn);
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

// Fetch processing fees (show only fees with processing payment status from case_position_updates)
$processing_sql = "
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
WHERE cpu.payment_status = 'processing' AND cpu.fee_amount > 0";

// Apply search filter
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $processing_sql .= " AND (
        c.cnr_number LIKE '" . $search_term . "'
        OR c.unique_case_id LIKE '" . $search_term . "'
        OR cl.name LIKE '" . $search_term . "'
        OR COALESCE(ni.case_no, cr.case_no, cc.case_no, ep.case_no, ao.case_no) LIKE '" . $search_term . "'
        OR cp.name LIKE '" . $search_term . "'
    )";
}

$processing_sql .= "
GROUP BY cpu.id
ORDER BY cpu.update_date DESC
";

// Get total count - use subquery to count grouped results
$grouped_query = explode('LIMIT', $processing_sql)[0]; // Remove LIMIT/OFFSET
$count_query = 'SELECT COUNT(*) as total FROM (' . $grouped_query . ') as grouped_subquery';
$count_result = @mysqli_query($conn, $count_query);
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
$processing_sql .= " LIMIT " . intval($entries_per_page) . " OFFSET " . intval($offset);

$processing_result = @mysqli_query($conn, $processing_sql);
$processing_accounts = [];
if ($processing_result) {
    while ($row = mysqli_fetch_assoc($processing_result)) {
        $processing_accounts[] = $row;
    }
}
ob_end_clean();
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
                            <p class="text-gray-600 text-sm mt-1">Total Processing Cases: <strong><?php echo $total_cases; ?></strong></p>
                        </div>
                        <div>
                            <label class="text-gray-700 text-sm font-semibold mr-2">Show Entries:</label>
                            <select id="entriesPerPage" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="changeEntriesPerPage(this.value)">
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
                                        placeholder="Search by Case ID, Case No., CNR No., Client Name, or Opposite Party..." 
                                        value="<?php echo htmlspecialchars($search_query); ?>"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                </div>
                                <button 
                                    type="submit" 
                                    class="px-6 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition whitespace-nowrap"
                                >
                                    <i class="fas fa-search mr-2"></i>Search
                                </button>
                                <?php if (!empty($search_query)): ?>
                                <a 
                                    href="processing-fees.php" 
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
                                href="export-processing-fees.php<?php echo !empty($search_query) ? '?search=' . urlencode($search_query) : ''; ?>" 
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
                            <table class="w-full" id="processingTable">
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
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Update Status</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="processingTableBody">
                                    <?php foreach ($processing_accounts as $account): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($account['unique_case_id']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['case_no'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['cnr_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['client_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <?php 
                                            $plaintiffs = htmlspecialchars($account['plaintiff_parties'] ?? '');
                                            $defendants = htmlspecialchars($account['defendant_parties'] ?? '');
                                            
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
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['case_type']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['bill_number'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($account['bill_date'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 font-semibold text-blue-600">₹<?php echo number_format($account['fee_amount'], 2); ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="update_payment">
                                                <input type="hidden" name="update_id" value="<?php echo $account['update_id']; ?>">
                                                <select name="payment_status" class="px-2 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500" onchange="this.form.submit()">
                                                    <option value="">Select Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="processing" selected>Processing</option>
                                                    <option value="completed">Completed</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex gap-2 justify-center">
                                                <button onclick="openEditModal(<?php echo $account['update_id']; ?>, '<?php echo htmlspecialchars($account['bill_number'] ?? ''); ?>', '<?php echo htmlspecialchars($account['bill_date'] ?? ''); ?>', <?php echo $account['fee_amount']; ?>)" 
                                                        class="px-3 py-2 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this fee record?');">
                                                    <input type="hidden" name="action" value="delete_fee">
                                                    <input type="hidden" name="update_id" value="<?php echo $account['update_id']; ?>">
                                                    <button type="submit" class="px-3 py-2 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition">
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
                            
                            function build_pagination_url_fees($page_num, $params) {
                                $params['page'] = $page_num;
                                return 'processing-fees.php?' . http_build_query($params);
                            }
                            ?>
                            <a href="<?php echo build_pagination_url_fees($current_page - 1, $query_params); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition <?php echo $current_page === 1 ? 'disabled:opacity-50 disabled:cursor-not-allowed pointer-events-none opacity-50' : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <div id="pageNumbers" class="flex gap-1">
                                <?php 
                                // Show first page
                                if ($total_pages > 0) {
                                    $btn_class = $current_page === 1 ? 'px-3 py-2 bg-blue-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url_fees(1, $query_params) . '" class="' . $btn_class . '">1</a>';
                                }
                                
                                // Show pages around current page
                                for ($i = max(2, $current_page - 1); $i <= min($total_pages - 1, $current_page + 1); $i++) {
                                    $btn_class = $current_page === $i ? 'px-3 py-2 bg-blue-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url_fees($i, $query_params) . '" class="' . $btn_class . '">' . $i . '</a>';
                                }
                                
                                // Show last page
                                if ($total_pages > 1) {
                                    $btn_class = $current_page === $total_pages ? 'px-3 py-2 bg-blue-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url_fees($total_pages, $query_params) . '" class="' . $btn_class . '">' . $total_pages . '</a>';
                                }
                                ?>
                            </div>
                            <a href="<?php echo build_pagination_url_fees($current_page + 1, $query_params); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition <?php echo $current_page === $total_pages || $total_pages === 0 ? 'disabled:opacity-50 disabled:cursor-not-allowed pointer-events-none opacity-50' : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
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
        // Change entries per page
        function changeEntriesPerPage(value) {
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('entries_per_page', value);
            searchParams.set('page', '1');
            window.location.href = 'processing-fees.php?' + searchParams.toString();
        }
    </script>

    <!-- Edit Fee Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-edit text-blue-500 mr-2"></i>Edit Fee Details
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
                    &times;
                </button>
            </div>
            
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="edit_fee">
                <input type="hidden" name="update_id" id="editUpdateId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Bill Number</label>
                    <input type="text" id="editBillNumber" name="bill_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Bill Date</label>
                    <input type="date" id="editBillDate" name="bill_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Fee Amount</label>
                    <input type="number" id="editFeeAmount" name="fee_amount" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeEditModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(updateId, billNumber, billDate, feeAmount) {
            document.getElementById('editUpdateId').value = updateId;
            document.getElementById('editBillNumber').value = billNumber;
            document.getElementById('editBillDate').value = billDate;
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

        // Print function - open in new window
        function openPrintPage() {
            const searchParams = new URLSearchParams(window.location.search);
            const search = searchParams.get('search') || '';
            
            let queryString = '';
            if (search) {
                queryString = '?search=' + encodeURIComponent(search);
            }
            
            window.open('print-processing-fees.php' + queryString, 'printWindow', 'width=1400,height=900');
        }
    </script>
</body>

</html>