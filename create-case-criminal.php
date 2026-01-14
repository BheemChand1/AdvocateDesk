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
    <title>Create Criminal Case - Case Management</title>
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
                    <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Create Criminal Case
                </h1>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="create-case-process.php">
                    <input type="hidden" name="case_type" value="CRIMINAL">

                    <!-- Case Type Display -->
                    <div class="mb-6 pb-4 border-b-2 border-blue-200 bg-blue-50 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-gavel text-blue-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Case Category:</span>
                                <h2 class="text-lg font-bold text-gray-800">Criminal Case</h2>
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">CNR NUMBER</label>
                                <input type="text" name="cnr_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter CNR number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Loan Number</label>
                                <input type="text" name="loan_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter loan number">
                            </div>
                        </div>
                    </div>

                    <!-- Complainant & Accused Information (Blue) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-blue-500 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-user-shield mr-2"></i>Complainant Information
                        </h2>
                        <div class="border-2 border-blue-500 rounded-b-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Name</label>
                                    <input type="text" name="complainant_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant ADDRESS</label>
                                    <input type="text" name="complainant_address" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant address">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" id="addMoreComplainant" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-plus mr-2"></i>OPTION TO ADD MORE complainant with ADDRESS
                                    </button>
                                    <div id="additionalComplainants" class="mt-4 space-y-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accused Information (Blue) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-blue-500 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-user-times mr-2"></i>Accused Information
                        </h2>
                        <div class="border-2 border-blue-500 rounded-b-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Accused name <span class="text-red-500">*</span></label>
                                    <input type="text" name="accused_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter accused name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Address <span class="text-red-500">*</span></label>
                                    <input type="text" name="accused_address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter accused address">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" id="addMoreAccused" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-plus mr-2"></i>OPTION TO ADD MORE accused with ADDRESS
                                    </button>
                                    <div id="additionalAccused" class="mt-4 space-y-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business & Location Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-building text-blue-500 mr-2"></i>Business & Location Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Product <span class="text-red-500">*</span></label>
                                <input type="text" name="product" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter product">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Branch Name <span class="text-red-500">*</span></label>
                                <input type="text" name="branch_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter branch name">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1 bg-red-600 text-white px-2 py-1 rounded">Case Type (drop down) <span class="text-yellow-300">*</span></label>
                                <select name="case_type_specific" required class="w-full px-3 py-2 border-2 border-red-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white">
                                    <option value="">Select case type</option>
                                    <option value="BAIL_APPLICATION_NO">BAIL APPLICATION NO.</option>
                                    <option value="CBI_CASES">CBI CASES</option>
                                    <option value="COMPLAINT_CASE">COMPLAINT CASE</option>
                                    <option value="CRIMINAL_MISC">CRIMINAL MISC.</option>
                                    <option value="DV_ACT">DV ACT</option>
                                    <option value="FINAL_REPORT">FINAL REPORT</option>
                                    <option value="MUNICIPAL_APPEAL">MUNICIPAL APPEAL</option>
                                    <option value="MOTOR_VEHICLE_APPEAL">MOTOR VEHICLE APPEAL</option>
                                    <option value="SEC_14">Sec 14</option>
                                    <option value="144_BNSS">144 BNSS</option>
                                    <option value="STATE_CASES">STATE CASES</option>
                                    <option value="SUMMARY_TRIAL">SUMMARY TRIAL</option>
                                    <option value="TRAFFIC_CHALLAN">TRAFFIC CHALLAN</option>
                                    <option value="POCSO_ACT">POCSO ACT</option>
                                    <option value="DM">DM</option>
                                    <option value="CRIMINAL_APPEAL">CRIMINAL APPEAL</option>
                                    <option value="CRIMINAL_REVISION">CRIMINAL REVISION</option>
                                    <option value="JUVENILE">JUVENILE</option>
                                    <option value="CRIMINAL_OTHER">CRIMINAL OTHER</option>
                                    <option value="SST">SST</option>
                                    <option value="ST">ST</option>
                                    <option value="NDPS">NDPS</option>
                                    <option value="GANGSTER_ACT">GANGSTER ACT</option>
                                    <option value="GUNDA_ACT">GUNDA ACT</option>
                                    <option value="HUMAN_RIGHTS">HUMAN RIGHTS</option>
                                    <option value="WOMEN_COMMISSION">WOMEN COMMISSION</option>
                                    <option value="POLICE_STATION_MATTER">POLICE STATION MATTER</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Region</label>
                                <input type="text" name="region" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter region">
                            </div>
                        </div>
                    </div>

                    <!-- Notice & Date Information (Yellow Background) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Notice & Date Information
                        </h2>
                        <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Notice_Date</label>
                                    <input type="date" name="notice_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Poa date</label>
                                    <input type="date" name="poa_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Court & Legal Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-gavel text-blue-500 mr-2"></i>Court & Legal Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant authorised person</label>
                                <input type="text" name="complainant_authorised_person" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter authorised person">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing_Date</label>
                                <input type="date" name="filing_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Filing_Location</label>
                                <input type="text" name="filing_location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing location">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Case_No</label>
                                <input type="text" name="case_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter case number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Court_No</label>
                                <input type="text" name="court_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Court_Name</label>
                                <input type="text" name="court_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court name">
                            </div>
                        </div>
                    </div>

                    <!-- Criminal Case Specific Information -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-file-invoice text-blue-500 mr-2"></i>Criminal Case Specific Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Section</label>
                                <input type="text" name="section" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter section">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Act</label>
                                <input type="text" name="act" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter act">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Police station with District</label>
                                <input type="text" name="police_station_with_district" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter police station with district">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Crime No./FIR No.</label>
                                <input type="text" name="crime_no_fir_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter crime/FIR number">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">FIR Date</label>
                                <input type="date" name="fir_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Charge Sheet Date</label>
                                <input type="date" name="charge_sheet_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="NOTICE" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="FILING" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="SUMMON" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="BAILABLE WARRANT" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="NON BAILABLE WARRANT" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="PROCLAMATION" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="PROSECUTION EVIDENCE" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="STATEMENT OF ACCUSED" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="DEFENSE EVIDENCE" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="ARGUMENT" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="JUDGMENT" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                                <input type="text" name="fee_grid_name[]" value="BAIL" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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

                    <!-- Remarks -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-comments text-blue-500 mr-2"></i>Remarks
                        </h2>
                        <div>
                            <textarea name="remarks" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter remarks"></textarea>
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
            // Add More Complainant Functionality
            let complainantCount = 0;
            document.getElementById('addMoreComplainant').addEventListener('click', function() {
                complainantCount++;
                const container = document.getElementById('additionalComplainants');
                const complainantDiv = document.createElement('div');
                complainantDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg bg-white relative';
                complainantDiv.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant name ${complainantCount + 1}</label>
                        <input type="text" name="additional_complainant_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter complainant name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant Address ${complainantCount + 1}</label>
                        <input type="text" name="additional_complainant_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter complainant address">
                    </div>
                    <button type="button" class="remove-complainant absolute top-2 right-2 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(complainantDiv);

                complainantDiv.querySelector('.remove-complainant').addEventListener('click', function() {
                    complainantDiv.remove();
                });
            });

            // Add More Accused Functionality
            let accusedCount = 0;
            document.getElementById('addMoreAccused').addEventListener('click', function() {
                accusedCount++;
                const container = document.getElementById('additionalAccused');
                const accusedDiv = document.createElement('div');
                accusedDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 rounded-lg bg-white relative';
                accusedDiv.innerHTML = `
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Accused name ${accusedCount + 1}</label>
                        <input type="text" name="additional_accused_name[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter accused name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Accused Address ${accusedCount + 1}</label>
                        <input type="text" name="additional_accused_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter accused address">
                    </div>
                    <button type="button" class="remove-accused absolute top-2 right-2 text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(accusedDiv);

                accusedDiv.querySelector('.remove-accused').addEventListener('click', function() {
                    accusedDiv.remove();
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
                        <input type="number" name="fee_grid_amount[]" value="0" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
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
