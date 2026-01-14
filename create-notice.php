<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Notice - Case Management</title>
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
                <a href="view-notices.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Create Notice
                </h1>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="create-notice-process.php">

                    <!-- Notice Type Display -->
                    <div class="mb-6 pb-4 border-b-2 border-blue-200 bg-blue-50 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-envelope-open-text text-blue-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Create:</span>
                                <h2 class="text-lg font-bold text-gray-800">Notice</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Client Selection -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-user text-blue-500 mr-2"></i>Select Client
                        </h2>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Client Name <span class="text-red-500">*</span></label>
                                <input type="text" id="clientSearch" placeholder="Search by name, email, or mobile number..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="hidden" name="client_id" id="selectedClientId" required>
                                <div id="clientResults" class="mt-2 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                                <div id="selectedClient" class="mt-2 hidden">
                                    <div class="bg-green-50 border border-green-300 rounded-lg p-3">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800" id="selectedClientName"></p>
                                                <p class="text-xs text-gray-600" id="selectedClientDetails"></p>
                                            </div>
                                            <button type="button" onclick="clearClientSelection()" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notice Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>Notice Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Notice Date <span class="text-red-500">*</span></label>
                                <input type="date" name="notice_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Section <span class="text-red-500">*</span></label>
                                <input type="text" name="section" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter section">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Act <span class="text-red-500">*</span></label>
                                <input type="text" name="act" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter act">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Post Date</label>
                                <input type="date" name="post_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Days Reminder <span class="text-red-500">*</span></label>
                                <select name="days_reminder" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Days</option>
                                    <?php for ($i = 1; $i <= 30; $i++) { echo "<option value='$i'>$i days</option>"; } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Input -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Additional Input</label>
                        <textarea name="input_data" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter any additional information..."></textarea>
                    </div>

                    <!-- Addressee -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Addressee (To Whom Notice is Sent) <span class="text-red-500">*</span></label>
                        <textarea name="addressee" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter the addressee/recipient of the notice..."></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-6 border-t border-gray-200">
                        <a href="view-notices.php"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="reset"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                            <i class="fas fa-save mr-2"></i>Create Notice
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Client Search Functionality
            const clientSearch = document.getElementById('clientSearch');
            const clientResults = document.getElementById('clientResults');
            const selectedClientId = document.getElementById('selectedClientId');
            const selectedClient = document.getElementById('selectedClient');
            const selectedClientName = document.getElementById('selectedClientName');
            const selectedClientDetails = document.getElementById('selectedClientDetails');
            let searchTimeout;

            clientSearch.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    clientResults.classList.add('hidden');
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    fetch('search-clients.php?q=' + encodeURIComponent(query))
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                clientResults.innerHTML = data.map(client => `
                                    <div class="client-item p-3 hover:bg-blue-50 cursor-pointer border-b last:border-b-0" data-id="${client.client_id}" data-name="${client.name}" data-email="${client.email || ''}" data-mobile="${client.mobile || ''}">
                                        <p class="font-semibold text-gray-800">${client.name}</p>
                                        <p class="text-xs text-gray-600">${client.email || ''} ${client.mobile ? '| ' + client.mobile : ''}</p>
                                    </div>
                                `).join('');
                                clientResults.classList.remove('hidden');
                                
                                // Add click events to client items
                                document.querySelectorAll('.client-item').forEach(item => {
                                    item.addEventListener('click', function() {
                                        const id = this.dataset.id;
                                        const name = this.dataset.name;
                                        const email = this.dataset.email;
                                        const mobile = this.dataset.mobile;
                                        
                                        selectedClientId.value = id;
                                        selectedClientName.textContent = name;
                                        selectedClientDetails.textContent = `${email} ${mobile ? '| ' + mobile : ''}`;
                                        
                                        selectedClient.classList.remove('hidden');
                                        clientSearch.value = '';
                                        clientResults.classList.add('hidden');
                                    });
                                });
                            } else {
                                clientResults.innerHTML = '<div class="p-3 text-gray-500 text-sm">No clients found</div>';
                                clientResults.classList.remove('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            clientResults.innerHTML = '<div class="p-3 text-red-500 text-sm">Error searching clients</div>';
                            clientResults.classList.remove('hidden');
                        });
                }, 300);
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!clientSearch.contains(e.target) && !clientResults.contains(e.target)) {
                    clientResults.classList.add('hidden');
                }
            });
        });

        function clearClientSelection() {
            document.getElementById('selectedClientId').value = '';
            document.getElementById('selectedClient').classList.add('hidden');
            document.getElementById('clientSearch').value = '';
        }
    </script>
</body>

</html>
