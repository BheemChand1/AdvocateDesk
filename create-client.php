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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-user-plus text-blue-500 mr-3"></i>Create New Client
                </h1>
                <p class="text-gray-600">Add a new client to the system</p>
            </div>

            <!-- Create Client Form -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Full Name -->
                        <div class="md:col-span-2">
                            <label for="full_name" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-user mr-2 text-blue-500"></i>Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="full_name" name="full_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Enter client's full name">
                        </div>

                        <!-- Father's Name -->
                        <div class="md:col-span-2">
                            <label for="father_name" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-user-tie mr-2 text-blue-500"></i>Father's Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="father_name" name="father_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Enter father's name">
                        </div>

                        <!-- Email ID -->
                        <div class="md:col-span-2">
                            <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-envelope mr-2 text-blue-500"></i>Email ID <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Enter email address">
                        </div>

                        <!-- Mobile Number -->
                        <div>
                            <label for="mobile_number" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-phone mr-2 text-blue-500"></i>Mobile Number <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" id="mobile_number" name="mobile_number" required pattern="[0-9]{10}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="10 digit mobile number" maxlength="10">
                            <p class="text-xs text-gray-500 mt-1">Enter 10 digit mobile number</p>
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>Address <span class="text-red-500">*</span>
                            </label>
                            <textarea id="address" name="address" required rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                                placeholder="Enter complete address"></textarea>
                        </div>

                        <!-- GST Number -->
                        <div>
                            <label for="gst_number" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-file-invoice mr-2 text-blue-500"></i>GST Number
                            </label>
                            <input type="text" id="gst_number" name="gst_number" pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}" maxlength="15"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="e.g., 22AAAAA0000A1Z5">
                            <p class="text-xs text-gray-500 mt-1">15 character GST number (optional)</p>
                        </div>

                        <!-- PAN Card Number -->
                        <div>
                            <label for="pan_number" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-id-card mr-2 text-blue-500"></i>PAN Card Number
                            </label>
                            <input type="text" id="pan_number" name="pan_number" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" maxlength="10"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="e.g., ABCDE1234F" style="text-transform: uppercase;">
                            <p class="text-xs text-gray-500 mt-1">10 character PAN number (optional)</p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-end space-y-3 sm:space-y-0 sm:space-x-4 mt-8 pt-6 border-t border-gray-200">
                        <a href="view-clients.php" class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="reset" class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                            <i class="fas fa-save mr-2"></i>Create Client
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
