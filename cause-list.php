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
    cpu.update_date as latest_position_date,
    cpu.position as latest_position
FROM cases c
LEFT JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_ni_passa_details ni ON c.id = ni.case_id
LEFT JOIN case_criminal_details cr ON c.id = cr.case_id
LEFT JOIN case_consumer_civil_details cc ON c.id = cc.case_id
LEFT JOIN case_ep_arbitration_details ep ON c.id = ep.case_id
LEFT JOIN case_arbitration_other_details ao ON c.id = ao.case_id
LEFT JOIN (
    SELECT case_id, update_date, position
    FROM case_position_updates
    WHERE (case_id, update_date) IN (
        SELECT case_id, MAX(update_date)
        FROM case_position_updates
        GROUP BY case_id
    )
) cpu ON c.id = cpu.case_id
WHERE c.status != 'closed'";

// Apply filters
if (!empty($case_type_filter)) {
    $query .= " AND c.case_type = '" . mysqli_real_escape_string($conn, $case_type_filter) . "'";
}

if (!empty($status_filter)) {
    $query .= " AND c.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Order by latest position update date if exists, otherwise by filing date (most recent first)
$query .= " ORDER BY COALESCE(cpu.update_date, COALESCE(
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

// Get case stages/positions
$stages_query = "SELECT id, stage_name, case_type FROM case_stages ORDER BY case_type, display_order";
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

            <!-- Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Total Cases</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo count($cases); ?></h3>
                        </div>
                        <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-briefcase text-blue-500 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Filed This Month</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2">
                                <?php 
                                $current_month = date('Y-m');
                                $month_count = 0;
                                foreach ($cases as $case) {
                                    if ($case['filing_date'] && date('Y-m', strtotime($case['filing_date'])) == $current_month) {
                                        $month_count++;
                                    }
                                }
                                echo $month_count;
                                ?>
                            </h3>
                        </div>
                        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar-check text-green-500 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Active Cases</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2">
                                <?php 
                                $active_count = 0;
                                foreach ($cases as $case) {
                                    if ($case['status'] == 'active') {
                                        $active_count++;
                                    }
                                }
                                echo $active_count;
                                ?>
                            </h3>
                        </div>
                        <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-hourglass-half text-yellow-500 text-2xl"></i>
                        </div>
                    </div>
                </div>
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
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Case ID
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Latest Update Date
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Customer Name
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Case Type
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Case No
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Court Name
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
                            <tbody class="divide-y divide-gray-200">
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
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                                <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $case['case_type']))); ?>
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
                    <label class="block text-gray-700 text-sm font-bold mb-2">Position/Stage <span class="text-red-500">*</span></label>
                    <select id="position" name="position" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Position</option>
                        <?php foreach ($case_stages as $stage): ?>
                            <option value="<?php echo htmlspecialchars($stage['stage_name']); ?>" 
                                    data-case-type="<?php echo htmlspecialchars($stage['case_type'] ?? ''); ?>">
                                <?php echo htmlspecialchars($stage['stage_name']); ?>
                                <?php if ($stage['case_type']): ?>
                                    (<?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $stage['case_type']))); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
        let currentCaseType = '';
        
        function openUpdateModal(caseId, caseIdDisplay, caseType) {
            currentCaseType = caseType;
            document.getElementById('caseId').value = caseId;
            document.getElementById('caseIdDisplay').value = caseIdDisplay;
            document.getElementById('updateDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('position').value = '';
            document.getElementById('remarks').value = '';
            
            // Filter positions based on case type
            filterPositionsByCaseType();
            
            document.getElementById('updateModal').classList.remove('hidden');
        }
        
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.add('hidden');
        }
        
        function filterPositionsByCaseType() {
            const positionSelect = document.getElementById('position');
            const options = positionSelect.getElementsByTagName('option');
            
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                const optionCaseType = option.getAttribute('data-case-type');
                
                if (option.value === '') {
                    option.style.display = '';
                    continue;
                }
                
                if (optionCaseType === '' || optionCaseType === null || optionCaseType === currentCaseType) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        }
        
        function submitUpdate(isEndCase) {
            const caseId = document.getElementById('caseId').value;
            const updateDate = document.getElementById('updateDate').value;
            const position = document.getElementById('position').value;
            const remarks = document.getElementById('remarks').value;
            
            if (!updateDate || !position) {
                alert('Please fill in all required fields (Date and Position)');
                return;
            }
            
            // Prepare form data
            const formData = new FormData();
            formData.append('case_id', caseId);
            formData.append('update_date', updateDate);
            formData.append('position', position);
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
    </script>
</body>

</html>
