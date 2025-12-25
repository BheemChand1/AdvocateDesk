<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get client ID from URL if provided
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Case - Case Management</title>
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
        <main class="px-4 sm:px-6 lg:px-8 py-4">
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between">
                <a href="<?php echo $client_id ? 'client-details.php?id='.$client_id : 'view-clients.php'; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Create New Case
                </h1>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <!-- Case Type Selection -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list-alt text-blue-500 mr-2"></i>Select Case Type <span class="text-red-500">*</span>
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="create-case-ni-passa.php<?php echo $client_id ? '?client_id='.$client_id : ''; ?>" class="flex items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="flex items-center">
                                <i class="fas fa-file-alt text-blue-500 text-2xl mr-3"></i>
                                <span class="text-gray-700 font-medium">NI/PASSA</span>
                            </div>
                        </a>
                        <a href="create-case-ep-arbitration.php<?php echo $client_id ? '?client_id='.$client_id : ''; ?>" class="flex items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="flex items-center">
                                <i class="fas fa-gavel text-blue-500 text-2xl mr-3"></i>
                                <span class="text-gray-700 font-medium">EP/Arbitration Executions</span>
                            </div>
                        </a>
                        <a href="create-case-arbitration-other.php<?php echo $client_id ? '?client_id='.$client_id : ''; ?>" class="flex items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="flex items-center">
                                <i class="fas fa-balance-scale text-blue-500 text-2xl mr-3"></i>
                                <span class="text-gray-700 font-medium">Arbitration Other Than EP</span>
                            </div>
                        </a>
                        <a href="create-case-consumer-civil.php<?php echo $client_id ? '?client_id='.$client_id : ''; ?>" class="flex items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition sm:col-span-2 lg:col-span-1">
                            <div class="flex items-center">
                                <i class="fas fa-user-shield text-blue-500 text-2xl mr-3"></i>
                                <span class="text-gray-700 font-medium">Consumer/Civil/Revenue/RERA/Family Court/DRT/PLA/Labour</span>
                            </div>
                        </a>
                        <a href="create-case-criminal.php<?php echo $client_id ? '?client_id='.$client_id : ''; ?>" class="flex items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-500 text-2xl mr-3"></i>
                                <span class="text-gray-700 font-medium">Criminal</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
