<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Listings</title>
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
            <!-- Page Header -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-users text-blue-500 mr-3"></i>View Clients
                    </h1>
                    <p class="text-gray-600">Manage all your clients</p>
                </div>
                <a href="create-client.php" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Add New Client
                </a>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" placeholder="Search by name, mobile, or address..." 
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    <button class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Full Name</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Father's Name</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Mobile Number</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Address</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- Sample Data Row 1 -->
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-900">1</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">Ramesh Kumar</td>
                                <td class="px-6 py-4 text-sm text-gray-600">Suresh Kumar</td>
                                <td class="px-6 py-4 text-sm text-gray-600">9876543210</td>
                                <td class="px-6 py-4 text-sm text-gray-600">123 Main Street, Delhi</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-3">
                                        <a href="client-details.php?id=1" class="text-blue-600 hover:text-blue-800 transition" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
                                        <button class="text-green-600 hover:text-green-800 transition" title="Edit">
                                            <i class="fas fa-edit text-lg"></i>
                                        </button>
                                        <button class="text-red-600 hover:text-red-800 transition" title="Delete">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Sample Data Row 2 -->
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-900">2</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">Priya Sharma</td>
                                <td class="px-6 py-4 text-sm text-gray-600">Raj Sharma</td>
                                <td class="px-6 py-4 text-sm text-gray-600">9876543211</td>
                                <td class="px-6 py-4 text-sm text-gray-600">456 Park Avenue, Mumbai</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-3">
                                        <a href="client-details.php?id=2" class="text-blue-600 hover:text-blue-800 transition" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
                                        <button class="text-green-600 hover:text-green-800 transition" title="Edit">
                                            <i class="fas fa-edit text-lg"></i>
                                        </button>
                                        <button class="text-red-600 hover:text-red-800 transition" title="Delete">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Sample Data Row 3 -->
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-900">3</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">Amit Patel</td>
                                <td class="px-6 py-4 text-sm text-gray-600">Vikram Patel</td>
                                <td class="px-6 py-4 text-sm text-gray-600">9876543212</td>
                                <td class="px-6 py-4 text-sm text-gray-600">789 Lake Road, Bangalore</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-3">
                                        <a href="client-details.php?id=3" class="text-blue-600 hover:text-blue-800 transition" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
                                        <button class="text-green-600 hover:text-green-800 transition" title="Edit">
                                            <i class="fas fa-edit text-lg"></i>
                                        </button>
                                        <button class="text-red-600 hover:text-red-800 transition" title="Delete">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden divide-y divide-gray-200">
                    <!-- Card 1 -->
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded mb-2">ID: 1</span>
                                <h3 class="text-lg font-bold text-gray-900">Ramesh Kumar</h3>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-user-tie w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Father:</strong> Suresh Kumar</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-phone w-5 text-blue-500"></i>
                                <span class="ml-2">9876543210</span>
                            </div>
                            <div class="flex items-start text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt w-5 text-blue-500 mt-1"></i>
                                <span class="ml-2">123 Main Street, Delhi</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-4 pt-3 border-t border-gray-200">
                            <a href="client-details.php?id=1" class="flex items-center px-3 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                <i class="fas fa-eye mr-2"></i>View
                            </a>
                            <button class="flex items-center px-3 py-2 text-green-600 hover:bg-green-50 rounded-lg transition">
                                <i class="fas fa-edit mr-2"></i>Edit
                            </button>
                            <button class="flex items-center px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded mb-2">ID: 2</span>
                                <h3 class="text-lg font-bold text-gray-900">Priya Sharma</h3>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-user-tie w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Father:</strong> Raj Sharma</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-phone w-5 text-blue-500"></i>
                                <span class="ml-2">9876543211</span>
                            </div>
                            <div class="flex items-start text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt w-5 text-blue-500 mt-1"></i>
                                <span class="ml-2">456 Park Avenue, Mumbai</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-4 pt-3 border-t border-gray-200">
                            <a href="client-details.php?id=2" class="flex items-center px-3 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                <i class="fas fa-eye mr-2"></i>View
                            </a>
                            <button class="flex items-center px-3 py-2 text-green-600 hover:bg-green-50 rounded-lg transition">
                                <i class="fas fa-edit mr-2"></i>Edit
                            </button>
                            <button class="flex items-center px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded mb-2">ID: 3</span>
                                <h3 class="text-lg font-bold text-gray-900">Amit Patel</h3>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-user-tie w-5 text-blue-500"></i>
                                <span class="ml-2"><strong>Father:</strong> Vikram Patel</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-phone w-5 text-blue-500"></i>
                                <span class="ml-2">9876543212</span>
                            </div>
                            <div class="flex items-start text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt w-5 text-blue-500 mt-1"></i>
                                <span class="ml-2">789 Lake Road, Bangalore</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-4 pt-3 border-t border-gray-200">
                            <a href="client-details.php?id=3" class="flex items-center px-3 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                <i class="fas fa-eye mr-2"></i>View
                            </a>
                            <button class="flex items-center px-3 py-2 text-green-600 hover:bg-green-50 rounded-lg transition">
                                <i class="fas fa-edit mr-2"></i>Edit
                            </button>
                            <button class="flex items-center px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-600">Showing 1 to 3 of 3 entries</p>
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
</body>

</html>
