<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get client ID from URL
$client_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($client_id <= 0) {
    header("Location: view-clients.php");
    exit();
}

// Fetch client data
$client_query = "SELECT * FROM clients WHERE client_id = ?";
$client_stmt = mysqli_prepare($conn, $client_query);
mysqli_stmt_bind_param($client_stmt, "i", $client_id);
mysqli_stmt_execute($client_stmt);
$client_result = mysqli_stmt_get_result($client_stmt);

if (!$client_result || mysqli_num_rows($client_result) === 0) {
    header("Location: view-clients.php");
    exit();
}

$client = mysqli_fetch_assoc($client_result);

// Fetch cases for this client
$cases_query = "SELECT 
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
WHERE c.client_id = ?
ORDER BY c.created_at DESC";

$cases_stmt = mysqli_prepare($conn, $cases_query);
mysqli_stmt_bind_param($cases_stmt, "i", $client_id);
mysqli_stmt_execute($cases_stmt);
$cases_result = mysqli_stmt_get_result($cases_stmt);

$cases = [];
if ($cases_result) {
    while ($row = mysqli_fetch_assoc($cases_result)) {
        $cases[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - Case Management</title>
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
            <!-- Back Button and Header -->
            <div class="mb-8">
                <a href="view-clients.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Clients
                </a>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-user-circle text-blue-500 mr-3"></i>Client Details
                </h1>
                <p class="text-gray-600">View client information and manage cases</p>
            </div>

            <!-- Client Information Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 mb-6">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>Client Information
                    </h2>
                    <a href="edit-client.php?id=<?php echo $client['client_id']; ?>" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                        <i class="fas fa-edit mr-2"></i>Edit Client
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Client ID -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-hashtag text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Client ID</p>
                            <p class="text-lg font-semibold text-gray-800">#<?php echo str_pad($client['client_id'], 4, '0', STR_PAD_LEFT); ?></p>
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Full Name</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['name']); ?></p>
                        </div>
                    </div>

                    <!-- Father's Name -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user-tie text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Father's Name</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['father_name'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- Mobile Number -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-phone text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Mobile Number</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['mobile']); ?></p>
                            <a href="tel:<?php echo $client['mobile']; ?>" class="text-sm text-blue-600 hover:text-blue-800">
                                <i class="fas fa-phone-alt mr-1"></i>Call Now
                            </a>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-envelope text-pink-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Email</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- PAN Number -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-id-card text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">PAN Number</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['pan_number'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- GST Number -->
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-file-invoice text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">GST Number</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['gst_number'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="flex items-start md:col-span-2">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Address</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($client['address'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cases Section -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-briefcase text-blue-500 mr-2"></i>Cases (<?php echo count($cases); ?>)
                    </h2>
                </div>

                <?php if (count($cases) > 0): ?>
                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Case ID</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Loan No.</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Case Type</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                    <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($cases as $case): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm font-bold text-purple-600"><?php echo htmlspecialchars($case['unique_case_id'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-blue-600"><?php echo htmlspecialchars($case['loan_number'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                                            <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $case['case_type'] ?? '-'))); ?>
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
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden divide-y divide-gray-200">
                        <?php foreach ($cases as $case): ?>
                        <div class="p-4 hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-block px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">
                                            <?php echo htmlspecialchars($case['unique_case_id'] ?? '-'); ?>
                                        </span>
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                            <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $case['case_type'] ?? '-'))); ?>
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
                                    <p class="text-sm text-blue-600 font-semibold">Loan #<?php echo htmlspecialchars($case['loan_number'] ?? '-'); ?></p>
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
                    </div>
                <?php else: ?>
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Cases Found</h3>
                        <p class="text-gray-600 mb-6">No cases registered for this client yet.</p>
                        <a href="create-case.php?client_id=<?php echo $client['client_id']; ?>" class="inline-flex items-center px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            <i class="fas fa-plus-circle mr-2"></i>Create New Case
                        </a>
                    </div>
                <?php endif; ?>
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
