<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get total client count
$total_clients_result = $conn->query("SELECT COUNT(*) as total FROM clients");
$total_clients = $total_clients_result->fetch_assoc()['total'];

$success_message = '';
if (isset($_GET['deleted']) && $_GET['deleted'] == 'success') {
    $success_message = "Client deleted successfully!";
}

// Fetch all clients
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE name LIKE ? OR email LIKE ? OR mobile LIKE ? OR address LIKE ? ORDER BY client_id DESC");
    $search_param = "%$search%";
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM clients ORDER BY client_id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Clients - Case Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="./assets/style.css">
</head>

<body class="bg-gradient-to-br from-gray-200 via-gray-100 to-slate-200 min-h-screen">
    <?php include './includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <?php include './includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <!-- Page Header -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-users text-blue-500 mr-2"></i>View Clients
                        <span class="text-sm font-normal text-gray-600">(Total: <?php echo $total_clients; ?>)</span>
                    </h1>
                </div>
                <a href="create-client.php" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Add New Client
                </a>
            </div>

            <?php if ($success_message): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-6">
                <form method="GET" action="" class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, mobile, email, or address..." 
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    <button type="submit" class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="view-clients.php" class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition text-center">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Clients Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto p-4">
                    <table id="clientsTable" class="w-full display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Father's Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Address</th>
                                <th>GST</th>
                                <th>PAN</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Use the search-filtered result
                            if ($result && $result->num_rows > 0): 
                                while($client = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $client['client_id']; ?></td>
                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['father_name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                                    <td><?php echo htmlspecialchars($client['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($client['address'], 0, 50)) . (strlen($client['address']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($client['gst_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($client['pan_number'] ?? '-'); ?></td>
                                    <td>
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="client-details.php?id=<?php echo $client['client_id']; ?>" class="text-blue-600 hover:text-blue-800" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-client.php?id=<?php echo $client['client_id']; ?>" class="text-green-600 hover:text-green-800" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $client['client_id']; ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            endif; 
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden divide-y divide-gray-200">
                    <?php 
                    // Reset result pointer for mobile view
                    if ($result && $result->num_rows > 0) {
                        $result->data_seek(0);
                        while($client = $result->fetch_assoc()): 
                    ?>
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded mb-2">ID: <?php echo $client['client_id']; ?></span>
                                <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($client['name']); ?></h3>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-user-tie w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Father:</strong> <?php echo htmlspecialchars($client['father_name']); ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-envelope w-5 text-blue-500"></i>
                                <span class="ml-2"><?php echo htmlspecialchars($client['email']); ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-phone w-5 text-blue-500"></i>
                                <span class="ml-2"><?php echo htmlspecialchars($client['mobile']); ?></span>
                            </div>
                            <div class="flex items-start text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt w-5 text-blue-500 mt-1"></i>
                                <span class="ml-2"><?php echo htmlspecialchars($client['address']); ?></span>
                            </div>
                            <?php if ($client['gst_number']): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-file-invoice w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>GST:</strong> <?php echo htmlspecialchars($client['gst_number']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($client['pan_number']): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-id-card w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>PAN:</strong> <?php echo htmlspecialchars($client['pan_number']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center justify-end space-x-4 pt-3 border-t border-gray-200">
                            <a href="client-details.php?id=<?php echo $client['client_id']; ?>" class="flex items-center px-3 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                <i class="fas fa-eye mr-2"></i>View
                            </a>
                            <a href="edit-client.php?id=<?php echo $client['client_id']; ?>" class="flex items-center px-3 py-2 text-green-600 hover:bg-green-50 rounded-lg transition">
                                <i class="fas fa-edit mr-2"></i>Edit
                            </a>
                            <button onclick="confirmDelete(<?php echo $client['client_id']; ?>)" class="flex items-center px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    } else {
                        echo '<div class="p-8 text-center text-gray-500"><i class="fas fa-inbox text-4xl mb-3"></i><p>No clients found</p></div>';
                    }
                    ?>
                </div>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#clientsTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "No entries to show",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "zeroRecords": "No matching records found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
        });
        
        function confirmDelete(clientId) {
            if (confirm('Are you sure you want to delete this client? This action cannot be undone.')) {
                window.location.href = 'delete-client.php?id=' + clientId;
            }
        }
    </script>
</body>

</html>
