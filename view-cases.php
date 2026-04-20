<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$priority_filter = isset($_GET['priority']) ? trim($_GET['priority']) : '';

// Pagination settings
$entries_per_page_raw = isset($_GET['entries_per_page']) ? trim($_GET['entries_per_page']) : '25';
$allowed_entries = ['10', '25', '50', '100', 'all'];
if (!in_array($entries_per_page_raw, $allowed_entries, true)) {
    $entries_per_page_raw = '25';
}

$show_all_rows = ($entries_per_page_raw === 'all');
$entries_per_page = $show_all_rows ? 0 : intval($entries_per_page_raw);

$current_page = $show_all_rows ? 1 : (isset($_GET['page']) ? intval($_GET['page']) : 1);
if ($current_page < 1) {
    $current_page = 1;
}

$where_conditions = [];

// Fetch cases from database with search
$query = "SELECT DISTINCT
    c.id,
    c.unique_case_id,
    c.case_type,
    c.loan_number,
    c.status,
    c.priority_status,
    c.remark,
    c.cnr_number,
    cl.name as customer_name,
    cl.mobile,
    cl.email,
    COALESCE(
        ni.filing_date,
        cr.filing_date,
        cc.case_filling_date,
        ep.date_of_filing,
        ao.filing_date
    ) as filing_date,
    (SELECT GROUP_CONCAT(DISTINCT CASE 
        WHEN party_type IN ('accused', 'defendant') THEN name 
    END SEPARATOR ', ') 
    FROM case_parties WHERE case_id = c.id) as accused_opposite_party
FROM cases c
LEFT JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
WHERE 1=1";

// Add status filter
if (!empty($status_filter)) {
    $where_conditions[] = "c.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Add priority filter
if ($priority_filter !== '' && ($priority_filter === '0' || $priority_filter === '1')) {
    $where_conditions[] = "c.priority_status = " . intval($priority_filter);
}

// Add search filter
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $where_conditions[] = "(c.loan_number LIKE '" . $search_term . "'
               OR c.unique_case_id LIKE '" . $search_term . "'
               OR cl.name LIKE '" . $search_term . "'
               OR cp.name LIKE '" . $search_term . "'
               OR c.cnr_number LIKE '" . $search_term . "')";
}

if (!empty($where_conditions)) {
    $query .= ' AND ' . implode(' AND ', $where_conditions);
}

// Total count for pagination
$count_query = "SELECT COUNT(DISTINCT c.id) as total_cases
FROM cases c
LEFT JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
WHERE 1=1";

if (!empty($where_conditions)) {
    $count_query .= ' AND ' . implode(' AND ', $where_conditions);
}

$count_result = mysqli_query($conn, $count_query);
$total_cases = 0;
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_cases = intval($count_row['total_cases'] ?? 0);
}

$total_pages = $show_all_rows ? 1 : ($total_cases > 0 ? (int) ceil($total_cases / $entries_per_page) : 1);
if ($current_page > $total_pages) {
    $current_page = $total_pages;
}

$offset = $show_all_rows ? 0 : (($current_page - 1) * $entries_per_page);

$query .= " ORDER BY c.created_at DESC";
if (!$show_all_rows) {
    $query .= " LIMIT " . intval($entries_per_page) . " OFFSET " . intval($offset);
}

$result = mysqli_query($conn, $query);

$cases = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cases[] = $row;
    }
}

$start_entry = $total_cases > 0 ? ($offset + 1) : 0;
$end_entry = $show_all_rows ? $total_cases : min($offset + $entries_per_page, $total_cases);

$query_params = [];
if ($search_query !== '') $query_params['search'] = $search_query;
if ($status_filter !== '') $query_params['status'] = $status_filter;
if ($priority_filter !== '') $query_params['priority'] = $priority_filter;
$query_params['entries_per_page'] = $entries_per_page_raw;

function build_pagination_url($page_num, $params) {
    $params['page'] = $page_num;
    return 'view-cases.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Cases - Case Management</title>
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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-briefcase text-blue-500 mr-3"></i><?php 
                    if ($status_filter === 'active') {
                        echo 'Active Cases';
                    } elseif ($status_filter === 'closed') {
                        echo 'Closed Cases';
                    } else {
                        echo 'All Cases';
                    }
                    ?>
                </h1>
                <p class="text-gray-600">
                    <?php 
                    if ($status_filter === 'active') {
                        echo 'Currently active cases in the system';
                    } elseif ($status_filter === 'closed') {
                        echo 'Completed and closed cases';
                    } else {
                        echo 'View and manage all cases in the system';
                    }
                    ?>
                </p>
            </div>

            <!-- Action Bar -->
            <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Search Bar -->
                    <form method="GET" class="w-full sm:flex-1">
                        <div class="flex gap-2">
                            <div class="flex-1 relative">
                                <input type="text" name="search" placeholder="Search by loan number, case ID, or customer name..."
                                    value="<?php echo htmlspecialchars($search_query); ?>"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <select name="priority" class="px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Priority</option>
                                <option value="1" <?php echo $priority_filter === '1' ? 'selected' : ''; ?>>Priority Cases</option>
                                <option value="0" <?php echo $priority_filter === '0' ? 'selected' : ''; ?>>Non-Priority</option>
                            </select>
                            <select name="entries_per_page" class="px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="10" <?php echo $entries_per_page_raw === '10' ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $entries_per_page_raw === '25' ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $entries_per_page_raw === '50' ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $entries_per_page_raw === '100' ? 'selected' : ''; ?>>100</option>
                                <option value="all" <?php echo $entries_per_page_raw === 'all' ? 'selected' : ''; ?>>All</option>
                            </select>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <input type="hidden" name="page" value="1">
                            <button type="submit" class="px-4 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                            <?php if (!empty($search_query) || !empty($status_filter) || $priority_filter !== ''): ?>
                                <a href="view-cases.php" class="px-4 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                                    <i class="fas fa-times mr-2"></i>Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <!-- Add Case Button -->
                    <a href="create-case.php"
                        class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg text-center">
                        <i class="fas fa-plus mr-2"></i>Add New Case
                    </a>
                </div>
            </div>

            <!-- Cases Table/List -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Case ID</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold whitespace-nowrap">CNR No.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold whitespace-nowrap">Filing Date</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Customer Name</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Accused/Opposite Party</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold whitespace-nowrap">Mobile</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Email</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold whitespace-nowrap">Loan No.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold whitespace-nowrap">Case Type</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold whitespace-nowrap">Priority</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($cases)): ?>
                            <tr>
                                <td colspan="12" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                    <p>No cases found. <a href="create-case.php" class="text-blue-600 hover:underline">Create your first case</a></p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($cases as $case): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-bold text-purple-600"><?php echo htmlspecialchars($case['unique_case_id'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-700 whitespace-nowrap"><?php echo htmlspecialchars($case['cnr_number'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap"><?php echo $case['filing_date'] ? date('d-m-Y', strtotime($case['filing_date'])) : 'N/A'; ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($case['customer_name'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($case['accused_opposite_party'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($case['mobile'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($case['email'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-blue-600 whitespace-nowrap"><?php echo htmlspecialchars($case['loan_number'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold whitespace-nowrap">
                                        <?php echo htmlspecialchars($case['case_type'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap">
                                    <?php if (intval($case['priority_status'] ?? 0) === 1): ?>
                                        <div class="flex flex-col gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                                <i class="fas fa-star mr-1 text-yellow-500"></i>Priority
                                            </span>
                                            <button onclick="togglePriority(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id'], ENT_QUOTES, 'UTF-8'); ?>', 0, <?php echo htmlspecialchars(json_encode($case['remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)" class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs font-semibold bg-white text-red-600 border border-red-200 hover:bg-red-50 transition">
                                                <i class="fas fa-times mr-1"></i>Remove
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button onclick="togglePriority(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id'], ENT_QUOTES, 'UTF-8'); ?>', 1, '')" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                                            <i class="far fa-star mr-1"></i>Mark
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 <?php 
                                        $status = strtolower($case['status'] ?? 'pending');
                                        echo $status == 'active' ? 'bg-green-100 text-green-800' : 
                                             ($status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                             ($status == 'closed' ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800')); 
                                    ?> rounded-full text-xs font-semibold whitespace-nowrap">
                                        <?php echo htmlspecialchars(ucfirst($case['status'] ?? 'Pending')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-3">
                                        <a href="case-details.php?id=<?php echo $case['id']; ?>" class="text-blue-600 hover:text-blue-800 transition" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
                                        <?php if ($case['status'] !== 'closed'): ?>
                                        <button onclick="closeCase(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id']); ?>')" class="text-orange-600 hover:text-orange-800 transition" title="Close Case">
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button onclick="deleteCase(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id']); ?>')" class="text-red-600 hover:text-red-800 transition" title="Delete">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden divide-y divide-gray-200">
                    <?php if (empty($cases)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                        <p>No cases found. <a href="create-case.php" class="text-blue-600 hover:underline">Create your first case</a></p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($cases as $case): ?>
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-block px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">
                                        <?php echo htmlspecialchars($case['unique_case_id'] ?? '-'); ?>
                                    </span>
                                    <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                        <?php echo htmlspecialchars($case['case_type'] ?? '-'); ?>
                                    </span>
                                    <?php if (intval($case['priority_status'] ?? 0) === 1): ?>
                                    <button onclick="togglePriority(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id'], ENT_QUOTES, 'UTF-8'); ?>', 0, <?php echo htmlspecialchars(json_encode($case['remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)" class="inline-block px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full hover:bg-red-200 transition">
                                        <i class="fas fa-star text-yellow-500 mr-1"></i>Remove Priority
                                    </button>
                                    <?php else: ?>
                                    <button onclick="togglePriority(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id'], ENT_QUOTES, 'UTF-8'); ?>', 1, '')" class="inline-block px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full hover:bg-gray-200 transition">
                                        <i class="far fa-star mr-1"></i>Mark Priority
                                    </button>
                                    <?php endif; ?>
                                    <span class="inline-block px-2 py-1 <?php 
                                        $status = strtolower($case['status'] ?? 'pending');
                                        echo $status == 'active' ? 'bg-green-100 text-green-800' : 
                                             ($status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                             ($status == 'closed' ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800')); 
                                    ?> rounded-full text-xs font-semibold">
                                        <?php echo htmlspecialchars(ucfirst($case['status'] ?? 'Pending')); ?>
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($case['customer_name'] ?? '-'); ?></h3>
                                <p class="text-sm text-blue-600 font-semibold">Loan #<?php echo htmlspecialchars($case['loan_number'] ?? '-'); ?></p>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-barcode w-5 text-purple-500"></i>
                                <span class="ml-2"><strong>CNR No.:</strong> <?php echo htmlspecialchars($case['cnr_number'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-calendar w-5 text-purple-500"></i>
                                <span class="ml-2"><strong>Filing Date:</strong> <?php echo $case['filing_date'] ? date('d-m-Y', strtotime($case['filing_date'])) : 'N/A'; ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-gavel w-5 text-purple-500"></i>
                                <span class="ml-2"><strong>Opposite Party:</strong> <?php echo htmlspecialchars($case['accused_opposite_party'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-phone w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Mobile:</strong> <?php echo htmlspecialchars($case['mobile'] ?? '-'); ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-envelope w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Email:</strong> <?php echo htmlspecialchars($case['email'] ?? '-'); ?></span>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-4 pt-3 border-t border-gray-200">
                            <a href="case-details.php?id=<?php echo $case['id']; ?>" class="flex items-center px-3 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                <i class="fas fa-eye mr-2"></i>View
                            </a>
                            <?php if ($case['status'] !== 'closed'): ?>
                            <button onclick="closeCase(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id']); ?>')" class="flex items-center px-3 py-2 text-orange-600 hover:bg-orange-50 rounded-lg transition">
                                <i class="fas fa-times-circle mr-2"></i>Close
                            </button>
                            <?php endif; ?>
                            <button onclick="deleteCase(<?php echo $case['id']; ?>, '<?php echo htmlspecialchars($case['unique_case_id']); ?>')" class="flex items-center px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-600">Showing <?php echo $start_entry; ?> to <?php echo $end_entry; ?> of <?php echo $total_cases; ?> entries</p>
                <div class="flex items-center space-x-2">
                    <a href="<?php echo build_pagination_url(max(1, $current_page - 1), $query_params); ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition <?php echo $current_page === 1 ? 'pointer-events-none opacity-50' : ''; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>

                    <?php
                    $start_page = max(1, $current_page - 1);
                    $end_page = min($total_pages, $current_page + 1);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="<?php echo build_pagination_url($i, $query_params); ?>" class="px-4 py-2 rounded-lg <?php echo $i === $current_page ? 'bg-blue-500 text-white' : 'border border-gray-300 text-gray-600 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <a href="<?php echo build_pagination_url(min($total_pages, $current_page + 1), $query_params); ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition <?php echo $current_page >= $total_pages ? 'pointer-events-none opacity-50' : ''; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
    <script>
        function closeCase(caseId, caseIdDisplay) {
            if (!confirm(`Are you sure you want to close case ${caseIdDisplay}? This action will mark the case as closed.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('case_id', caseId);

            fetch('close-case.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Case closed successfully');
                    location.reload();
                } else {
                    alert('Error closing case: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while closing the case');
            });
        }

        function deleteCase(caseId, caseIdDisplay) {
            if (!confirm(`Are you sure you want to delete case ${caseIdDisplay}? This action cannot be undone.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('case_id', caseId);

            fetch('delete-case.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Case deleted successfully');
                    location.reload();
                } else {
                    alert('Error deleting case: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the case');
            });
        }

        function togglePriority(caseId, caseIdDisplay, priorityStatus, remark) {
            const actionText = priorityStatus === 1 ? 'mark this case as priority' : 'remove this case from priority';
            if (!confirm(`Are you sure you want to ${actionText} for case ${caseIdDisplay}?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('case_id', caseId);
            formData.append('priority_status', priorityStatus);
            formData.append('remark', remark || '');

            fetch('update-case-priority.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Priority updated successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Error updating priority');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating priority');
            });
        }
    </script>
</body>

</html>