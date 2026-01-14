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

// Fetch cases from database with search
$query = "SELECT DISTINCT
    c.id,
    c.unique_case_id,
    c.case_type,
    c.loan_number,
    c.status,
    cl.name as customer_name,
    cl.mobile,
    cl.email
FROM cases c
LEFT JOIN clients cl ON c.client_id = cl.client_id
LEFT JOIN case_parties cp ON c.id = cp.case_id
WHERE 1=1";

// Add status filter
if (!empty($status_filter)) {
    $query .= " AND c.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Add search filter
if (!empty($search_query)) {
    $search_term = '%' . $search_query . '%';
    $query .= " AND (c.loan_number LIKE ? 
               OR c.unique_case_id LIKE ? 
               OR cl.name LIKE ?
               OR cp.name LIKE ?
               OR c.cnr_number LIKE ?)";
}

$query .= " ORDER BY c.created_at DESC";

if (!empty($search_query)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssss", $search_term, $search_term, $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

$cases = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cases[] = $row;
    }
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
                    <form method="GET" class="w-full sm:flex-1 sm:max-w-96">
                        <div class="flex gap-2">
                            <div class="flex-1 relative">
                                <input type="text" name="search" placeholder="Search by loan number, case ID, or customer name..."
                                    value="<?php echo htmlspecialchars($search_query); ?>"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <button type="submit" class="px-4 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                            <?php if (!empty($search_query) || !empty($status_filter)): ?>
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
                                <th class="px-6 py-4 text-left text-sm font-semibold">Customer Name</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Mobile</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Email</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Loan No.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Case Type</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($cases)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                    <p>No cases found. <a href="create-case.php" class="text-blue-600 hover:underline">Create your first case</a></p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($cases as $case): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-bold text-purple-600"><?php echo htmlspecialchars($case['unique_case_id'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($case['customer_name'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($case['mobile'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($case['email'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-blue-600"><?php echo htmlspecialchars($case['loan_number'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                                        <?php echo htmlspecialchars($case['case_type'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 <?php 
                                        $status = strtolower($case['status'] ?? 'pending');
                                        echo $status == 'active' ? 'bg-green-100 text-green-800' : 
                                             ($status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                             ($status == 'closed' ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800')); 
                                    ?> rounded-full text-xs font-semibold">
                                        <?php echo htmlspecialchars(ucfirst($case['status'] ?? 'Pending')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-3">
                                        <a href="case-details.php?id=<?php echo $case['id']; ?>" class="text-blue-600 hover:text-blue-800 transition" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
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
                <p class="text-sm text-gray-600">Showing 1 to <?php echo count($cases); ?> of <?php echo count($cases); ?> entries</p>
                <div class="flex items-center space-x-2">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="px-4 py-2 bg-blue-500 text-white rounded-lg">1</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50" disabled>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
    <script>
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
    </script>
</body>

</html>
