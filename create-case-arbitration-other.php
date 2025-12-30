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
    <title>Create Arbitration Other Case - Case Management</title>
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
                <a href="create-case.php<?php echo $client_id ? '?client_id='.$client_id : ''; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Create Arbitration Other Case
                </h1>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="create-case-process.php">
                    <input type="hidden" name="case_type" value="ARBITRATION_OTHER">

                    <!-- Case Type Display -->
                    <div class="mb-6 pb-4 border-b-2 border-blue-200 bg-blue-50 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-balance-scale text-blue-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Case Type:</span>
                                <h2 class="text-lg font-bold text-gray-800">Arbitration Other Than EP</h2>
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
                                <label class="block text-gray-700 text-sm font-semibold mb-1">clients/retained for</label>
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

                    <!-- Basic Case Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>Basic Case Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">CNR NUMBER</label>
                                <input type="text" name="cnr_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter CNR number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Loan Number <span class="text-red-500">*</span></label>
                                <input type="text" name="loan_number" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter loan number">
                            </div>
                        </div>
                    </div>

                    <!-- Plaintiff Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-user-shield text-blue-500 mr-2"></i>Plaintiff Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff Name <span class="text-red-500">*</span></label>
                                <input type="text" name="plaintiff_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter plaintiff name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff ADDRESS <span class="text-red-500">*</span></label>
                                <input type="text" name="plaintiff_address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter plaintiff address">
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" id="addMorePlaintiff" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                    <i class="fas fa-plus mr-2"></i>Add More Plaintiff with ADDRESS
                                </button>
                                <div id="additionalPlaintiffs" class="mt-4 space-y-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Defendant Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-users text-blue-500 mr-2"></i>Defendant Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Name <span class="text-red-500">*</span></label>
                                <input type="text" name="defendant_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter defendant name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant ADDRESS <span class="text-red-500">*</span></label>
                                <input type="text" name="defendant_address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter defendant address">
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" id="addMoreDefendant" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                    <i class="fas fa-plus mr-2"></i>Add More Defendant with ADDRESS
                                </button>
                                <div id="additionalDefendants" class="mt-4 space-y-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer & Business Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-briefcase text-blue-500 mr-2"></i>Customer & Business Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Customer Name <span class="text-red-500">*</span></label>
                                <input type="text" name="customer_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter customer name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Product <span class="text-red-500">*</span></label>
                                <input type="text" name="product" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter product">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Branch Name <span class="text-red-500">*</span></label>
                                <input type="text" name="branch_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter branch name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Location</label>
                                <input type="text" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter location">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Region</label>
                                <input type="text" name="region" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter region">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Amount</label>
                                <input type="number" name="filing_amount" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing amount">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Authorised Person</label>
                                <input type="text" name="complainant_authorised_person" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter authorised person">
                            </div>
                        </div>
                    </div>

                    <!-- Court & Legal Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-gavel text-blue-500 mr-2"></i>Court & Legal Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Date</label>
                                <input type="date" name="filing_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Location</label>
                                <input type="text" name="filing_location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing location">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case No</label>
                                <input type="text" name="case_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter case number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Court No</label>
                                <input type="text" name="court_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Advocate</label>
                                <input type="text" name="advocate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter advocate name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Poa</label>
                                <input type="text" name="poa" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter POA">
                            </div>
                        </div>
                    </div>

                    <!-- Fee Grid -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Fee Grid
                        </h2>
                        <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-4">
                            <div class="overflow-x-auto">
                                <table class="w-full" id="feeGridTable">
                                    <thead>
                                        <tr class="bg-yellow-400 text-gray-900">
                                            <th class="px-4 py-2 text-left font-bold">FEE GRID</th>
                                            <th class="px-4 py-2 text-right font-bold">AMOUNT</th>
                                            <th class="px-4 py-2 w-20"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="feeGridBody">
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Filing/Appearance" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Decided" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Misc" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Certified Copy ( of any order )" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                <button type="button" id="addMoreFee" class="px-4 py-2 bg-yellow-500 text-gray-900 font-bold rounded-lg hover:bg-yellow-600 transition">
                                    <i class="fas fa-plus mr-2"></i>Add more option
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks/Feedback/Trails -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-comments text-blue-500 mr-2"></i>Remarks/Feedback/Trails
                        </h2>
                        <div>
                            <textarea name="remarks_feedback_trails" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter remarks, feedback, or trails"></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-6 border-t border-gray-200">
                        <a href="create-case.php<?php echo $client_id ? '?client_id='.$client_id : ''; ?>"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="reset"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                            <i class="fas fa-save mr-2"></i>Create Case
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
            // Add More Plaintiff Functionality
            let plaintiffCount = 0;
            document.getElementById('addMorePlaintiff').addEventListener('click', function() {
                plaintiffCount++;
                const container = document.getElementById('additionalPlaintiffs');
                const plaintiffDiv = document.createElement('div');
                plaintiffDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg relative';
                plaintiffDiv.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff Name ${plaintiffCount + 1}</label>
                        <input type="text" name="additional_plaintiff_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter plaintiff name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Plaintiff ADDRESS ${plaintiffCount + 1}</label>
                        <input type="text" name="additional_plaintiff_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter plaintiff address">
                    </div>
                    <button type="button" class="remove-plaintiff absolute top-2 right-2 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(plaintiffDiv);

                plaintiffDiv.querySelector('.remove-plaintiff').addEventListener('click', function() {
                    plaintiffDiv.remove();
                });
            });

            // Add More Defendant Functionality
            let defendantCount = 0;
            document.getElementById('addMoreDefendant').addEventListener('click', function() {
                defendantCount++;
                const container = document.getElementById('additionalDefendants');
                const defendantDiv = document.createElement('div');
                defendantDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg relative';
                defendantDiv.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Name ${defendantCount + 1}</label>
                        <input type="text" name="additional_defendant_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter defendant name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant ADDRESS ${defendantCount + 1}</label>
                        <input type="text" name="additional_defendant_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter defendant address">
                    </div>
                    <button type="button" class="remove-defendant absolute top-2 right-2 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(defendantDiv);

                defendantDiv.querySelector('.remove-defendant').addEventListener('click', function() {
                    defendantDiv.remove();
                });
            });

            // Add More Fee Grid Row Functionality
            document.getElementById('addMoreFee').addEventListener('click', function() {
                const tbody = document.getElementById('feeGridBody');
                const newRow = document.createElement('tr');
                newRow.className = 'border-b border-yellow-300';
                newRow.innerHTML = `
                    <td class="px-4 py-2">
                        <input type="text" name="fee_grid_name[]" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter fee name">
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-2 text-center">
                        <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(newRow);

                newRow.querySelector('.remove-fee').addEventListener('click', function() {
                    newRow.remove();
                });
            });

            // Add remove functionality to existing fee grid rows
            document.querySelectorAll('.remove-fee').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('tr').remove();
                });
            });

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
