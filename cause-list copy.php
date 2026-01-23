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

// Build the query to fetch cases with filing dates and latest position updates
$query = "SELECT 
    c.id,
    c.unique_case_id,
    c.case_type,
    c.loan_number,
    c.cnr_number,
    c.status,
    c.location,
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
    GROUP_CONCAT(DISTINCT CASE 
        WHEN cp.party_type IN ('accused', 'defendant') THEN cp.name 
    END SEPARATOR ', ') as accused_opposite_party,
    latest.update_date as latest_position_date,
    latest.position as latest_position,
    previous.update_date as previous_position_date,
    previous.position as previous_position
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

// Add GROUP BY clause
$query .= " GROUP BY c.id";

// Order by latest position update date if exists, otherwise by filing date (most recent first)
$query .= " ORDER BY COALESCE(latest.update_date, COALESCE(
    ni.filing_date,
    cr.filing_date,
    cc.case_filling_date,
    ep.date_of_filing,
    ao.filing_date
)) DESC, c.created_at DESC";

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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-calendar-alt text-blue-500 mr-3"></i>Cause List
                </h1>
                <p class="text-gray-600">View all registered cases ordered by filing date</p>
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
                        
                        <!-- Show Entries Dropdown -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Show Entries</label>
                            <select id="entriesPerPage" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="10">10 entries</option>
                                <option value="25" selected>25 entries</option>
                                <option value="50">50 entries</option>
                                <option value="100">100 entries</option>
                                <option value="all">Show All</option>
                            </select>
                        </div>
                        
                        <!-- Clear Filters -->
                        <?php if (!empty($search_query) || !empty($case_type_filter) || !empty($status_filter)): ?>
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
                            Showing <span id="startEntry">1</span> to <span id="endEntry">25</span> of <span id="totalEntries"><?php echo count($cases); ?></span> entries
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="previousPage()" id="prevBtn" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div id="pageNumbers" class="flex gap-1">
                                <!-- Page numbers will be added here by JavaScript -->
                            </div>
                            <button onclick="nextPage()" id="nextBtn" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </button>
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

    <script src="./assets/script.js"></script>
    <script>
        // Store all fee data
        const allFeeData = <?php echo json_encode($case_stages); ?>;
        let currentCaseId = '';
        
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
        }
        
        // Close modal when clicking outside
        document.getElementById('updateModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeUpdateModal();
            }
        });
        
        // Pagination functionality
        let currentPage = 1;
        let entriesPerPage = 25;
        let allRows = [];
        
        // Initialize pagination on page load
        window.addEventListener('load', function() {
            allRows = Array.from(document.querySelectorAll('#casesTableBody tr'));
            
            // Set up entries per page dropdown
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
            
            // Hide all rows
            allRows.forEach(row => row.style.display = 'none');
            
            // Show rows for current page
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = startIndex + entriesPerPage;
            
            for (let i = startIndex; i < endIndex && i < allRows.length; i++) {
                allRows[i].style.display = '';
            }
            
            // Update pagination info
            document.getElementById('startEntry').textContent = allRows.length > 0 ? startIndex + 1 : 0;
            document.getElementById('endEntry').textContent = Math.min(endIndex, allRows.length);
            document.getElementById('totalEntries').textContent = allRows.length;
            
            // Update pagination buttons
            updatePageNumbers(totalPages);
            
            // Enable/disable next and prev buttons
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = currentPage === totalPages || totalPages === 0;
        }
        
        function updatePageNumbers(totalPages) {
            const pageNumbersDiv = document.getElementById('pageNumbers');
            pageNumbersDiv.innerHTML = '';
            
            // Show first page
            if (totalPages > 0) {
                const btn = createPageButton(1);
                pageNumbersDiv.appendChild(btn);
            }
            
            // Show pages around current page
            for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
                const btn = createPageButton(i);
                pageNumbersDiv.appendChild(btn);
            }
            
            // Show last page
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
