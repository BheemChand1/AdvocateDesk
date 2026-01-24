<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get filter options
$case_type_filter = isset($_GET['case_type']) ? $_GET['case_type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';

// Pagination settings
$entries_per_page = isset($_GET['entries_per_page']) ? intval($_GET['entries_per_page']) : 25;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;

// Build the query to fetch cases with filing dates and latest position updates
$query = "SELECT 
    c.id,
    c.unique_case_id,
    c.case_type,
    c.loan_number,
    c.cnr_number,
    c.status,
    c.location,
    c.priority_status,
    c.remark,
    cl.name as customer_name,
    cl.mobile,
    COALESCE(
        ni.filing_date,
        cr.filing_date,
        cc.case_filling_date,
        ep.date_of_filing,
        ao.filing_date
    ) as filing_date,
    COALESCE(
        ni.case_no,
        cr.case_no,
        cc.case_no,
        ep.case_no,
        ao.case_no
    ) as case_no,
    COALESCE(
        ni.court_name,
        cr.court_name,
        cc.court_name,
        '',
        ''
    ) as court_name,
    (SELECT GROUP_CONCAT(DISTINCT CASE 
        WHEN party_type IN ('accused', 'defendant') THEN name 
    END SEPARATOR ', ') 
    FROM case_parties WHERE case_id = c.id) as accused_opposite_party,
    latest.update_date as latest_position_date,
    latest.position as latest_position,
    previous.update_date as previous_position_date,
    previous.position as previous_position,
    (SELECT COALESCE(SUM(fee_amount), 0) FROM case_fee_grid WHERE case_id = c.id) as total_fees,
    (SELECT COALESCE(SUM(fee_amount), 0) FROM case_fee_grid WHERE case_id = c.id) - 
    (SELECT COALESCE(SUM(fee_amount), 0) FROM case_position_updates WHERE case_id = c.id) as balance_fees
FROM cases c
LEFT JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
LEFT JOIN (
    SELECT case_id, update_date, position
    FROM case_position_updates
    WHERE (case_id, update_date) IN (
        SELECT case_id, MAX(update_date)
        FROM case_position_updates
        GROUP BY case_id
    )
) latest ON c.id = latest.case_id
LEFT JOIN (
    SELECT case_id, update_date, position
    FROM case_position_updates
    WHERE (case_id, update_date) IN (
        SELECT case_id, MAX(update_date)
        FROM case_position_updates
        WHERE update_date < (
            SELECT MAX(update_date)
            FROM case_position_updates cp2
            WHERE cp2.case_id = case_position_updates.case_id
        )
        GROUP BY case_id
    )
) previous ON c.id = previous.case_id
WHERE c.status != 'closed'";

// Apply search filter
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $query .= " AND (c.loan_number LIKE '" . $search_term . "'
               OR c.unique_case_id LIKE '" . $search_term . "'
               OR cl.name LIKE '" . $search_term . "'
               OR cp.name LIKE '" . $search_term . "'
               OR c.cnr_number LIKE '" . $search_term . "')";
}

// Apply filters
if (!empty($case_type_filter)) {
    $query .= " AND c.case_type = '" . mysqli_real_escape_string($conn, $case_type_filter) . "'";
}

if (!empty($status_filter)) {
    $query .= " AND c.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Apply date range filter on Fixed Date (latest_position_date or filing_date)
if (!empty($from_date)) {
    $from_date_safe = mysqli_real_escape_string($conn, $from_date);
    $query .= " AND COALESCE(latest.update_date, COALESCE(ni.filing_date, cr.filing_date, cc.case_filling_date, ep.date_of_filing, ao.filing_date)) >= '" . $from_date_safe . "'";
}

if (!empty($to_date)) {
    $to_date_safe = mysqli_real_escape_string($conn, $to_date);
    $query .= " AND COALESCE(latest.update_date, COALESCE(ni.filing_date, cr.filing_date, cc.case_filling_date, ep.date_of_filing, ao.filing_date)) <= '" . $to_date_safe . "'";
}

// Apply priority filter
if ($priority_filter !== '') {
    $priority_filter_int = intval($priority_filter);
    $query .= " AND c.priority_status = $priority_filter_int";
}

// Add GROUP BY clause to properly aggregate data
$query .= " GROUP BY c.id";

// Order by latest position update date if exists, otherwise by filing date (most recent first)
$query .= " ORDER BY COALESCE(latest.update_date, COALESCE(
    ni.filing_date,
    cr.filing_date,
    cc.case_filling_date,
    ep.date_of_filing,
    ao.filing_date
)) DESC, c.created_at DESC";

// Get total count for pagination (before adding LIMIT)
$count_query = "SELECT COUNT(DISTINCT c.id) as total FROM cases c
LEFT JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
LEFT JOIN (
    SELECT case_id, update_date, position
    FROM case_position_updates
    WHERE (case_id, update_date) IN (
        SELECT case_id, MAX(update_date)
        FROM case_position_updates
        GROUP BY case_id
    )
) latest ON c.id = latest.case_id
LEFT JOIN (
    SELECT case_id, update_date, position
    FROM case_position_updates
    WHERE (case_id, update_date) IN (
        SELECT case_id, MAX(update_date)
        FROM case_position_updates
        WHERE update_date < (
            SELECT MAX(update_date)
            FROM case_position_updates cp2
            WHERE cp2.case_id = case_position_updates.case_id
        )
        GROUP BY case_id
    )
) previous ON c.id = previous.case_id
WHERE c.status != 'closed'";

// Apply same filters to count query
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $count_query .= " AND (c.loan_number LIKE '" . $search_term . "'
               OR c.unique_case_id LIKE '" . $search_term . "'
               OR cl.name LIKE '" . $search_term . "'
               OR c.cnr_number LIKE '" . $search_term . "')";
}

if (!empty($case_type_filter)) {
    $count_query .= " AND c.case_type = '" . mysqli_real_escape_string($conn, $case_type_filter) . "'";
}

if (!empty($status_filter)) {
    $count_query .= " AND c.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if (!empty($from_date)) {
    $from_date_safe = mysqli_real_escape_string($conn, $from_date);
    $count_query .= " AND COALESCE(latest.update_date, COALESCE(ni.filing_date, cr.filing_date, cc.case_filling_date, ep.date_of_filing, ao.filing_date)) >= '" . $from_date_safe . "'";
}

if (!empty($to_date)) {
    $to_date_safe = mysqli_real_escape_string($conn, $to_date);
    $count_query .= " AND COALESCE(latest.update_date, COALESCE(ni.filing_date, cr.filing_date, cc.case_filling_date, ep.date_of_filing, ao.filing_date)) <= '" . $to_date_safe . "'";
}

if ($priority_filter !== '') {
    $priority_filter_int = intval($priority_filter);
    $count_query .= " AND c.priority_status = $priority_filter_int";
}

// Execute count query
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
$query .= " LIMIT " . intval($entries_per_page) . " OFFSET " . intval($offset);

$result = mysqli_query($conn, $query);

$cases = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cases[] = $row;
    }
}

// Get unique case types for filter
$case_types_query = "SELECT DISTINCT case_type FROM cases ORDER BY case_type";
$case_types_result = mysqli_query($conn, $case_types_query);
$case_types = [];
if ($case_types_result) {
    while ($row = mysqli_fetch_assoc($case_types_result)) {
        $case_types[] = $row['case_type'];
    }
}

// Get all fee grid items (will be filtered by case_id in JavaScript)
$stages_query = "SELECT cfg.id, cfg.case_id, cfg.fee_name, cfg.fee_amount, c.case_type
                 FROM case_fee_grid cfg
                 JOIN cases c ON cfg.case_id = c.id
                 ORDER BY cfg.case_id, cfg.fee_name";
$stages_result = mysqli_query($conn, $stages_query);
$case_stages = [];
if ($stages_result) {
    while ($row = mysqli_fetch_assoc($stages_result)) {
        $case_stages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cause List - Case Management</title>
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
            <!-- Header -->
            <div class="flex justify-between items-center mb-8 flex-wrap gap-2">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-calendar-alt text-blue-500 mr-3"></i>Cause List
                </h1>
                <div class="flex gap-2">
                    <button onclick="exportToExcel()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center gap-2">
                        <i class="fas fa-file-excel"></i>Export to Excel
                    </button>
                    <button onclick="openPrintPage()" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium flex items-center gap-2">
                        <i class="fas fa-print"></i>Print
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-filter text-blue-500 mr-2"></i>Filters & Search
                </h3>
                <form method="GET" class="space-y-4">
                    <!-- Search Bar -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Search Cases</label>
                        <div class="flex gap-2">
                            <div class="flex-1 relative">
                                <input type="text" name="search" placeholder="Search by loan number, case ID, customer name, party name, or CNR number..."
                                    value="<?php echo htmlspecialchars($search_query); ?>"
                                    class="w-full pl-12 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition whitespace-nowrap">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                        </div>
                    </div>

                    <!-- Filters Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Case Type Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Case Type</label>
                            <select name="case_type" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Case Types</option>
                                <?php foreach ($case_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" 
                                            <?php echo $case_type_filter == $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $type))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- From Date Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">From Date (Fixed Date)</label>
                            <input type="date" name="from_date" 
                                   value="<?php echo htmlspecialchars($from_date); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- To Date Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">To Date (Fixed Date)</label>
                            <input type="date" name="to_date" 
                                   value="<?php echo htmlspecialchars($to_date); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Priority Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Priority</label>
                            <select name="priority" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Cases</option>
                                <option value="1" <?php echo $priority_filter == '1' ? 'selected' : ''; ?>>Priority Cases</option>
                                <option value="0" <?php echo $priority_filter == '0' ? 'selected' : ''; ?>>Non-Priority Cases</option>
                            </select>
                        </div>
                        
                        <!-- Show Entries Dropdown -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Show Entries</label>
                            <select id="entriesPerPage" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onchange="changeEntriesPerPage(this.value)">
                                <option value="10" <?php echo $entries_per_page == 10 ? 'selected' : ''; ?>>10 entries</option>
                                <option value="25" <?php echo $entries_per_page == 25 ? 'selected' : ''; ?>>25 entries</option>
                                <option value="50" <?php echo $entries_per_page == 50 ? 'selected' : ''; ?>>50 entries</option>
                                <option value="100" <?php echo $entries_per_page == 100 ? 'selected' : ''; ?>>100 entries</option>
                            </select>
                        </div>
                        
                        <!-- Clear Filters -->
                        <?php if (!empty($search_query) || !empty($case_type_filter) || !empty($status_filter) || !empty($from_date) || !empty($to_date) || $priority_filter !== ''): ?>
                            <div class="flex items-end">
                                <a href="cause-list.php" 
                                   class="w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-center font-medium">
                                    <i class="fas fa-times mr-2"></i>Clear All Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Cases Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-list-alt text-blue-500 mr-2"></i>Cases List (Ordered by Latest Update)
                    </h2>
                </div>

                <?php if (count($cases) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full" id="casesTable">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Case ID
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Previous Date
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Case No
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Court Name
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Customer Name
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Accused/Opposite Party
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        CNR Number
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Fixed Date
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Case Type
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Latest Stage
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Fee (Total / Balance)
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Priority
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="casesTableBody">
                                <?php foreach ($cases as $case): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-blue-600">
                                                <?php echo htmlspecialchars($case['unique_case_id'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-900 font-semibold">
                                                <?php 
                                                // Show previous position date if exists, otherwise show filing date
                                                $prev_date = $case['previous_position_date'] ?: $case['filing_date'];
                                                if ($prev_date) {
                                                    echo date('d M, Y', strtotime($prev_date));
                                                    if ($case['previous_position_date']) {
                                                        echo '<br><span class="text-xs text-gray-500"><i class="fas fa-history mr-1"></i>Previous</span>';
                                                    } else {
                                                        echo '<br><span class="text-xs text-gray-500"><i class="fas fa-calendar mr-1"></i>Filed</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-gray-400">N/A</span>';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($case['case_no'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-700">
                                                <?php echo htmlspecialchars($case['court_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($case['customer_name'] ?? 'N/A'); ?>
                                            </span>
                                            <?php if ($case['mobile']): ?>
                                                <br>
                                                <span class="text-xs text-gray-500">
                                                    <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($case['mobile']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($case['accused_opposite_party'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-900 font-semibold">
                                                <?php echo htmlspecialchars($case['cnr_number'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-900 font-semibold">
                                                <?php 
                                                $display_date = $case['latest_position_date'] ?: $case['filing_date'];
                                                if ($display_date) {
                                                    echo date('d M, Y', strtotime($display_date));
                                                    if ($case['latest_position_date']) {
                                                        echo '<br><span class="text-xs text-blue-500"><i class="fas fa-sync-alt mr-1"></i>Updated</span>';
                                                    } else {
                                                        echo '<br><span class="text-xs text-gray-500"><i class="fas fa-calendar mr-1"></i>Filed</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-gray-400">Not Filed</span>';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                                <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $case['case_type']))); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($case['latest_position']): ?>
                                                <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                    <i class="fas fa-tasks mr-1"></i><?php echo htmlspecialchars($case['latest_position']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-sm text-gray-400">No Updates</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <div class="font-semibold text-gray-900">
                                                    Total: <span class="text-green-600">₹<?php echo number_format($case['total_fees'], 2); ?></span>
                                                </div>
                                                <div class="text-gray-700 mt-1">
                                                    Balance: <span class="<?php echo $case['balance_fees'] > 0 ? 'text-orange-600' : 'text-gray-500'; ?> font-semibold">₹<?php echo number_format($case['balance_fees'], 2); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button onclick="openPriorityModal(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id']); ?>', <?php echo $case['priority_status']; ?>, '<?php echo htmlspecialchars($case['remark'] ?? ''); ?>')" 
                                                    class="px-3 py-2 <?php echo $case['priority_status'] == 1 ? 'bg-red-500' : 'bg-gray-400'; ?> text-white text-sm font-medium rounded-lg hover:opacity-80 transition">
                                                <i class="fas fa-star mr-1"></i><?php echo $case['priority_status'] == 1 ? 'Priority' : 'Not Priority'; ?>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status = $case['status'];
                                            $status_colors = [
                                                'active' => 'bg-green-100 text-green-800',
                                                'closed' => 'bg-gray-100 text-gray-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800'
                                            ];
                                            $color_class = $status_colors[$status] ?? 'bg-blue-100 text-blue-800';
                                            ?>
                                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo $color_class; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <a href="case-details.php?id=<?php echo $case['id']; ?>" 
                                                   class="px-3 py-2 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600 transition duration-200 flex items-center gap-1">
                                                    <i class="fas fa-eye"></i>
                                                    View
                                                </a>
                                                <button onclick="openUpdateModal(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id']); ?>', '<?php echo htmlspecialchars($case['case_type']); ?>')" 
                                                        class="px-3 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition duration-200 flex items-center gap-1">
                                                    <i class="fas fa-edit"></i>
                                                    Update
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-6 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Showing <span id="startEntry"><?php echo $total_cases > 0 ? ($offset + 1) : 0; ?></span> to <span id="endEntry"><?php echo min($offset + $entries_per_page, $total_cases); ?></span> of <span id="totalEntries"><?php echo $total_cases; ?></span> entries
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php 
                            // Build query string for pagination links
                            $query_params = [];
                            if (!empty($search_query)) $query_params['search'] = $search_query;
                            if (!empty($case_type_filter)) $query_params['case_type'] = $case_type_filter;
                            if (!empty($status_filter)) $query_params['status'] = $status_filter;
                            if (!empty($from_date)) $query_params['from_date'] = $from_date;
                            if (!empty($to_date)) $query_params['to_date'] = $to_date;
                            if ($priority_filter !== '') $query_params['priority'] = $priority_filter;
                            $query_params['entries_per_page'] = $entries_per_page;
                            
                            function build_pagination_url($page_num, $params) {
                                $params['page'] = $page_num;
                                return 'cause-list.php?' . http_build_query($params);
                            }
                            ?>
                            <a href="<?php echo build_pagination_url($current_page - 1, $query_params); ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition <?php echo $current_page === 1 ? 'disabled:opacity-50 disabled:cursor-not-allowed pointer-events-none opacity-50' : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <div id="pageNumbers" class="flex gap-1">
                                <?php 
                                // Show first page
                                if ($total_pages > 0) {
                                    $btn_class = $current_page === 1 ? 'px-3 py-2 bg-blue-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url(1, $query_params) . '" class="' . $btn_class . '">1</a>';
                                }
                                
                                // Show pages around current page
                                for ($i = max(2, $current_page - 1); $i <= min($total_pages - 1, $current_page + 1); $i++) {
                                    $btn_class = $current_page === $i ? 'px-3 py-2 bg-blue-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url($i, $query_params) . '" class="' . $btn_class . '">' . $i . '</a>';
                                }
                                
                                // Show last page
                                if ($total_pages > 1) {
                                    $btn_class = $current_page === $total_pages ? 'px-3 py-2 bg-blue-500 text-white rounded-lg font-semibold' : 'px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition';
                                    echo '<a href="' . build_pagination_url($total_pages, $query_params) . '" class="' . $btn_class . '">' . $total_pages . '</a>';
                                }
                                ?>
                            </div>
                            <a href="<?php echo build_pagination_url($current_page + 1, $query_params); ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition <?php echo $current_page === $total_pages || $total_pages === 0 ? 'disabled:opacity-50 disabled:cursor-not-allowed pointer-events-none opacity-50' : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Cases Found</h3>
                        <p class="text-gray-600 mb-6">There are no registered cases with filing dates in the system.</p>
                        <a href="create-case.php" class="inline-flex items-center px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            <i class="fas fa-plus-circle mr-2"></i>Create New Case
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <!-- Update Position Modal -->
    <div id="updateModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-edit text-blue-500 mr-2"></i>Update Case Position
                </h3>
                <button onclick="closeUpdateModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
                    &times;
                </button>
            </div>
            
            <form id="updateForm" class="mt-4">
                <input type="hidden" id="caseId" name="case_id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Case ID</label>
                    <input type="text" id="caseIdDisplay" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Update Date <span class="text-red-500">*</span></label>
                    <input type="date" id="updateDate" name="update_date" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Stage <span class="text-red-500">*</span></label>
                    <select id="position" name="position" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Stage</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Fee Amount</label>
                    <input type="text" id="feeAmount" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="4" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Enter any additional remarks or notes..."></textarea>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeUpdateModal()" 
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="button" onclick="submitUpdate(false)" 
                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-save mr-2"></i>Update
                    </button>
                    <button type="button" onclick="submitUpdate(true)" 
                            class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                        <i class="fas fa-flag-checkered mr-2"></i>End Case
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Priority Modal -->
    <div id="priorityModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-star text-yellow-500 mr-2"></i>Priority & Remark
                </h3>
                <button onclick="closePriorityModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
                    &times;
                </button>
            </div>
            
            <form id="priorityForm" class="mt-4">
                <input type="hidden" id="priorityCaseId" name="case_id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Case ID</label>
                    <input type="text" id="priorityCaseIdDisplay" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                </div>
                
                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="priorityStatus" name="priority_status" 
                               class="w-5 h-5 text-red-500 rounded focus:ring-2 focus:ring-red-500 cursor-pointer">
                        <label for="priorityStatus" class="ml-3 text-gray-700 font-semibold cursor-pointer">
                            <i class="fas fa-star text-red-500 mr-2"></i>Mark as Priority
                        </label>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Remark</label>
                    <textarea id="priorityRemark" name="remark" rows="5" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Add any remarks or notes about this case..."></textarea>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closePriorityModal()" 
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="button" onclick="submitPriority()" 
                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-save mr-2"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="./assets/script.js"></script>
    <script>
        // Store all fee data
        const allFeeData = <?php echo json_encode($case_stages); ?>;
        let currentCaseId = '';
        
        // Function to change entries per page
        function changeEntriesPerPage(value) {
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('entries_per_page', value);
            searchParams.set('page', '1'); // Reset to first page
            window.location.href = 'cause-list.php?' + searchParams.toString();
        }
        
        // Function to export to Excel
        function exportToExcel() {
            // Get current filter values from URL
            const searchParams = new URLSearchParams(window.location.search);
            const filters = {
                case_type: searchParams.get('case_type') || '',
                status: searchParams.get('status') || '',
                search: searchParams.get('search') || '',
                from_date: searchParams.get('from_date') || '',
                to_date: searchParams.get('to_date') || '',
                priority: searchParams.get('priority') || ''
            };
            
            // Build query string
            let queryString = '';
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    queryString += (queryString ? '&' : '?') + key + '=' + encodeURIComponent(filters[key]);
                }
            });
            
            // Redirect to export handler
            window.location.href = 'export-cause-list.php' + queryString;
        }
        
        // Function to open print page with filters
        function openPrintPage() {
            // Get current filter values from URL or form
            const searchParams = new URLSearchParams(window.location.search);
            const filters = {
                case_type: searchParams.get('case_type') || '',
                status: searchParams.get('status') || '',
                search: searchParams.get('search') || '',
                from_date: searchParams.get('from_date') || '',
                to_date: searchParams.get('to_date') || '',
                priority: searchParams.get('priority') || ''
            };
            
            // Build query string
            let queryString = '';
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    queryString += (queryString ? '&' : '?') + key + '=' + encodeURIComponent(filters[key]);
                }
            });
            
            // Open print page in new tab
            window.open('print-cause-list.php' + queryString, 'printWindow', 'width=1400,height=900');
        }
        
        function openUpdateModal(caseId, caseIdDisplay, caseType) {
            currentCaseId = caseId;
            document.getElementById('caseId').value = caseId;
            document.getElementById('caseIdDisplay').value = caseIdDisplay;
            document.getElementById('updateDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('position').value = '';
            document.getElementById('feeAmount').value = '';
            document.getElementById('remarks').value = '';
            
            // Load fees for this specific case
            loadFeesForCase(caseId);
            
            document.getElementById('updateModal').classList.remove('hidden');
        }
        
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.add('hidden');
        }
        
        // Priority Modal Functions
        function openPriorityModal(caseId, caseIdDisplay, priorityStatus, remark) {
            document.getElementById('priorityCaseId').value = caseId;
            document.getElementById('priorityCaseIdDisplay').value = caseIdDisplay;
            document.getElementById('priorityStatus').checked = priorityStatus == 1;
            document.getElementById('priorityRemark').value = remark || '';
            document.getElementById('priorityModal').classList.remove('hidden');
        }
        
        function closePriorityModal() {
            document.getElementById('priorityModal').classList.add('hidden');
        }
        
        function submitPriority() {
            const caseId = document.getElementById('priorityCaseId').value;
            const priorityStatus = document.getElementById('priorityStatus').checked ? 1 : 0;
            const remark = document.getElementById('priorityRemark').value;
            
            // Prepare form data
            const formData = new FormData();
            formData.append('case_id', caseId);
            formData.append('priority_status', priorityStatus);
            formData.append('remark', remark);
            
            // Submit via AJAX
            fetch('update-case-priority.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Priority and remark updated successfully!');
                    closePriorityModal();
                    location.reload();
                } else {
                    alert(data.message || 'Error updating priority and remark');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating priority and remark');
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('priorityModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closePriorityModal();
            }
        });
        
        function loadFeesForCase(caseId) {
            const positionSelect = document.getElementById('position');
            positionSelect.innerHTML = '<option value="">Select Stage</option>';
            
            // Filter fees for this specific case
            const caseFees = allFeeData.filter(fee => fee.case_id == caseId);
            
            caseFees.forEach(fee => {
                const option = document.createElement('option');
                option.value = fee.fee_name;
                option.setAttribute('data-fee-id', fee.id);
                option.setAttribute('data-fee-amount', fee.fee_amount);
                option.textContent = `${fee.fee_name} (₹${parseFloat(fee.fee_amount).toFixed(2)})`;
                positionSelect.appendChild(option);
            });
            
            // Add change event listener
            positionSelect.addEventListener('change', function() {
                updateFeeAmount();
            });
        }
        
        function updateFeeAmount() {
            const positionSelect = document.getElementById('position');
            const selectedOption = positionSelect.options[positionSelect.selectedIndex];
            const feeAmount = selectedOption.getAttribute('data-fee-amount') || '0';
            document.getElementById('feeAmount').value = '₹' + parseFloat(feeAmount).toFixed(2);
        }
        
        function submitUpdate(isEndCase) {
            const caseId = document.getElementById('caseId').value;
            const updateDate = document.getElementById('updateDate').value;
            const position = document.getElementById('position').value;
            const feeAmount = document.getElementById('feeAmount').value.replace('₹', '').trim();
            const remarks = document.getElementById('remarks').value;
            
            if (!updateDate || !position) {
                alert('Please fill in all required fields (Date and Stage)');
                return;
            }
            
            // First, check if this stage already exists for this case
            fetch('check-stage-exists.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'case_id=' + caseId + '&position=' + encodeURIComponent(position)
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    alert('⚠️ This stage has already been updated for this case!');
                    return;
                }
                
                // Stage doesn't exist, proceed with update
                // Prepare form data
                const formData = new FormData();
                formData.append('case_id', caseId);
                formData.append('update_date', updateDate);
                formData.append('position', position);
                formData.append('fee_amount', feeAmount);
                formData.append('payment_status', 'pending');
                formData.append('remarks', remarks);
                formData.append('is_end', isEndCase ? '1' : '0');
                
                // Submit via AJAX
                fetch('update-case-position.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Case position updated successfully!');
                        closeUpdateModal();
                        location.reload(); // Reload to see updated status
                    } else {
                        alert(data.message || 'Error updating case position');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the case position');
                });
            })
            .catch(error => {
                console.error('Error checking stage:', error);
                alert('An error occurred while checking the stage');
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('updateModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeUpdateModal();
            }
        });
    </script>
</body>

</html>
