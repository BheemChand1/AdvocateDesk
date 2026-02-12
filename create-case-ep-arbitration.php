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
    <title>Create EP/Arbitration Case - Case Management</title>
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
                    <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Create EP/Arbitration Case
                </h1>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="create-case-process.php">
                    <input type="hidden" name="case_type" value="EP_ARBITRATION">

                    <!-- Case Type Display -->
                    <div class="mb-6 pb-4 border-b-2 border-blue-200 bg-blue-50 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-gavel text-blue-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Case Type:</span>
                                <h2 class="text-lg font-bold text-gray-800">EP/Arbitration Executions</h2>
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

                    <!-- Basic Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>Basic Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">CNR NUMBER <span class="text-gray-500 font-normal">(16 characters)</span></label>
                                <input type="text" name="cnr_number" id="cnr_number" maxlength="16" pattern="[A-Za-z0-9]{16}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase" placeholder="Enter 16 character CNR number">
                                <div id="cnrExistsMessage" class="hidden mt-1 text-red-600 text-sm font-semibold"></div>
                                <div id="cnrLengthMessage" class="hidden mt-1 text-red-600 text-sm font-semibold"></div>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Loan Number <span class="text-red-500">*</span></label>
                                <input type="text" name="loan_number" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter loan number">
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
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Client Authorised Person</label>
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
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing Location</label>
                                <input type="text" name="filing_location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing location">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case No</label>
                                <input type="text" name="case_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter case number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Court Name</label>
                                <input type="text" name="court_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Advocate</label>
                                <input type="text" name="advocate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter advocate name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">POA</label>
                                <input type="text" name="poa" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter POA">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Date of Filing</label>
                                <input type="date" name="date_of_filing" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Decree Holder/Client Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-user-tie text-blue-500 mr-2"></i>Decree Holder Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Decree Holder <span class="text-red-500">*</span></label>
                                <input type="text" name="decree_holder_client" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter decree holder/client name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Decree Holder Complete ADDRESS Detail <span class="text-red-500">*</span></label>
                                <input type="text" name="decree_holder_address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter complete address">
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" id="addMoreDecreeHolder" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                    <i class="fas fa-plus mr-2"></i>Add More Decree Holder with Address
                                </button>
                                <div id="additionalDecreeHolders" class="mt-4 space-y-3"></div>
                            </div>
                        </div>
                    </div>

                    <!--Defendant Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-users text-blue-500 mr-2"></i>Defendant Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">DEFENDANT <span class="text-red-500">*</span></label>
                                <input type="text" name="customer_name_defendant" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter customer/defendant name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">DEFENDANT ADDRESS <span class="text-red-500">*</span></label>
                                <input type="text" name="customer_address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter customer address">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 text-sm font-semibold mb-1">DEFENDANT OFFICE ADDRESS</label>
                                <input type="text" name="customer_office_address" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter customer office address">
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" id="addMoreAddress" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                    <i class="fas fa-plus mr-2"></i>Add More Defendant With Address
                                </button>
                                <div id="additionalAddresses" class="mt-4 space-y-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Arbitration Details -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-balance-scale text-blue-500 mr-2"></i>Arbitration Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Award Date</label>
                                <input type="date" name="award_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Arbitrator Name</label>
                                <input type="text" name="arbitrator_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter arbitrator name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Arbitrator Address</label>
                                <input type="text" name="arbitrator_address" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter arbitrator address">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Arbitration Case No.</label>
                                <input type="text" name="arbitration_case_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter arbitration case number">
                            </div>
                        </div>
                    </div>

                    <!-- Interest Calculation -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-calculator text-green-500 mr-2"></i>Interest Calculation
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Interest Start Date</label>
                                <input type="date" name="interest_start_date" id="interestStartDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Interest End Date</label>
                                <input type="date" name="interest_end_date" id="interestEndDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Total Days</label>
                                <input type="number" name="total_days" id="totalDays" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Auto calculated">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Award Amount</label>
                                <input type="number" name="award_amount" id="awardAmount" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter award amount">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Rate of Interest %</label>
                                <input type="number" name="rate_of_interest" id="rateOfInterest" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter rate">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Interest Amount (After Calculation)</label>
                                <input type="number" name="interest_amount" id="interestAmount" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Auto calculated">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Cost</label>
                                <input type="number" name="cost" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter cost">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Recovery Amount/Received After Award Date</label>
                                <input type="number" name="recovery_amount" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter recovery amount">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Claim Amount</label>
                                <input type="number" name="claim_amount" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter claim amount">
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle & Asset Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-car text-blue-500 mr-2"></i>Vehicle & Asset Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Vehicle 1 Classification</label>
                                <input type="text" name="vehicle_1_classification" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter vehicle classification">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Vehicle 2 Asset Description</label>
                                <input type="text" name="vehicle_2_asset_description" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter asset description">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Vehicle 3 Registration Number</label>
                                <input type="text" name="vehicle_3_registration_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter registration number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Vehicle 4 Engine No.</label>
                                <input type="text" name="vehicle_4_engine_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter engine number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Vehicle 5 Chasis No.</label>
                                <input type="text" name="vehicle_5_chasis_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter chasis number">
                            </div>
                        </div>
                    </div>

                    <!-- Immoveable Property Details -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-home text-blue-500 mr-2"></i>Immoveable Property Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Immoveable Property Detail 1</label>
                                <textarea name="immoveable_property_detail_1" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter property detail 1"></textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Immoveable Property Detail 2</label>
                                <textarea name="immoveable_property_detail_2" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter property detail 2"></textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Immoveable Property Detail 3</label>
                                <textarea name="immoveable_property_detail_3" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter property detail 3"></textarea>
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
                                                <input type="text" name="fee_grid_name[]" value="Filing" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="0" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="number" name="fee_grid_amount[]" value="0" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="number" name="fee_grid_amount[]" value="0" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="number" name="fee_grid_amount[]" value="0" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
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
            // Add More Decree Holder Functionality
            let decreeHolderCount = 0;
            document.getElementById('addMoreDecreeHolder').addEventListener('click', function() {
                decreeHolderCount++;
                const container = document.getElementById('additionalDecreeHolders');
                const decreeHolderDiv = document.createElement('div');
                decreeHolderDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg relative';
                decreeHolderDiv.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Decree Holder ${decreeHolderCount + 1}</label>
                        <input type="text" name="additional_decree_holder_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter decree holder name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Decree Holder Address ${decreeHolderCount + 1}</label>
                        <input type="text" name="additional_decree_holder_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter decree holder address">
                    </div>
                    <button type="button" class="remove-decree-holder absolute top-2 right-2 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(decreeHolderDiv);

                decreeHolderDiv.querySelector('.remove-decree-holder').addEventListener('click', function() {
                    decreeHolderDiv.remove();
                });
            });

            // Add More Defendant with Address Functionality
            let defendantAddressCount = 0;
            document.getElementById('addMoreAddress').addEventListener('click', function() {
                defendantAddressCount++;
                const container = document.getElementById('additionalAddresses');
                const defendantAddressDiv = document.createElement('div');
                defendantAddressDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg relative';
                defendantAddressDiv.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant ${defendantAddressCount + 1}</label>
                        <input type="text" name="additional_defendant_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter defendant name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Defendant Address ${defendantAddressCount + 1}</label>
                        <input type="text" name="additional_defendant_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter defendant address">
                    </div>
                    <button type="button" class="remove-defendant-address absolute top-2 right-2 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(defendantAddressDiv);

                defendantAddressDiv.querySelector('.remove-defendant-address').addEventListener('click', function() {
                    defendantAddressDiv.remove();
                });
            });

            // Interest Calculation Functions
            const startDateInput = document.getElementById('interestStartDate');
            const endDateInput = document.getElementById('interestEndDate');
            const totalDaysInput = document.getElementById('totalDays');
            const awardAmountInput = document.getElementById('awardAmount');
            const rateOfInterestInput = document.getElementById('rateOfInterest');
            const interestAmountInput = document.getElementById('interestAmount');

            function calculateTotalDays() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                
                if (startDateInput.value && endDateInput.value && endDate >= startDate) {
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    totalDaysInput.value = diffDays;
                    calculateInterest();
                } else {
                    totalDaysInput.value = '';
                }
            }

            function calculateInterest() {
                const principal = parseFloat(awardAmountInput.value) || 0;
                const rate = parseFloat(rateOfInterestInput.value) || 0;
                const days = parseInt(totalDaysInput.value) || 0;

                if (principal > 0 && rate > 0 && days > 0) {
                    // Simple interest calculation: (P × R × T) / 100
                    // Where T is in years (days/365)
                    const interest = (principal * rate * days) / (365 * 100);
                    interestAmountInput.value = interest.toFixed(2);
                } else {
                    interestAmountInput.value = '';
                }
            }

            // Event listeners for calculation
            startDateInput.addEventListener('change', calculateTotalDays);
            endDateInput.addEventListener('change', calculateTotalDays);
            awardAmountInput.addEventListener('input', calculateInterest);
            rateOfInterestInput.addEventListener('input', calculateInterest);

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
                        <input type="number" name="fee_grid_amount[]" value="0" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-2 text-center">
                        <button type="button" class="remove-fee text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(newRow);

                // Add remove functionality
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

        // CNR Number Validation
        let cnrExists = false;
        let cnrInvalid = false;
        const cnrInput = document.getElementById('cnr_number');
        const cnrMessage = document.getElementById('cnrExistsMessage');
        const cnrLengthMessage = document.getElementById('cnrLengthMessage');

        // Allow only alphanumeric characters and convert to uppercase
        if (cnrInput) {
            cnrInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            });
        }

        if (cnrInput) {
            cnrInput.addEventListener('blur', function() {
                const cnr = this.value.trim();
                
                // Check length validation
                if (cnr.length > 0 && cnr.length !== 16) {
                    cnrInvalid = true;
                    cnrInput.classList.add('border-red-500', 'bg-red-50');
                    cnrInput.classList.remove('border-gray-300');
                    cnrLengthMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>CNR number must be exactly 16 characters!';
                    cnrLengthMessage.classList.remove('hidden');
                } else {
                    cnrInvalid = false;
                    cnrLengthMessage.classList.add('hidden');
                }
                
                // Check duplicate only if length is valid
                if (cnr.length === 16) {
                    fetch('check-cnr-exists.php?cnr=' + encodeURIComponent(cnr))
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                cnrExists = true;
                                cnrInput.classList.add('border-red-500', 'bg-red-50');
                                cnrInput.classList.remove('border-gray-300');
                                cnrMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>This CNR number already exists!';
                                cnrMessage.classList.remove('hidden');
                            } else {
                                cnrExists = false;
                                if (!cnrInvalid) {
                                    cnrInput.classList.remove('border-red-500', 'bg-red-50');
                                    cnrInput.classList.add('border-gray-300');
                                }
                                cnrMessage.classList.add('hidden');
                            }
                        })
                        .catch(error => console.error('Error checking CNR:', error));
                } else if (cnr.length === 0) {
                    cnrExists = false;
                    cnrInvalid = false;
                    cnrInput.classList.remove('border-red-500', 'bg-red-50');
                    cnrInput.classList.add('border-gray-300');
                    cnrMessage.classList.add('hidden');
                    cnrLengthMessage.classList.add('hidden');
                }
            });
        }

        // Prevent form submission if CNR exists or invalid
        document.querySelector('form').addEventListener('submit', function(e) {
            const cnr = cnrInput ? cnrInput.value.trim() : '';
            if (cnr.length > 0 && cnr.length !== 16) {
                e.preventDefault();
                alert('CNR number must be exactly 16 characters!');
                cnrInput.focus();
                return;
            }
            if (cnrExists) {
                e.preventDefault();
                alert('Cannot create case: The CNR number already exists in the system. Please use a different CNR number.');
                cnrInput.focus();
            }
        });
    </script>
</body>

</html>
