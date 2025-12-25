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
    <title>Create Consumer/Civil Case - Case Management</title>
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
                    <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Create Consumer/Civil Case
                </h1>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="create-case-process.php">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">

                    <!-- Case Type Display -->
                    <div class="mb-6 pb-4 border-b-2 border-blue-200 bg-blue-50 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-balance-scale text-blue-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Case Category:</span>
                                <h2 class="text-lg font-bold text-gray-800">CONSUMER/CIVIL/REVENUE/RERA/FAMILY COURT/DRT/PLA/LABOUR</h2>
                            </div>
                        </div>
                    </div>

                    <!-- CASE DETAILS Section -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-green-600 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-file-alt mr-2"></i>CASE DETAILS
                        </h2>
                        <div class="border-2 border-green-600 rounded-b-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">CNR NUMBER</label>
                                    <input type="text" name="cnr_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter CNR number">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Loan Number <span class="text-red-500">*</span></label>
                                    <input type="text" name="loan_number" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter loan number">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Product <span class="text-red-500">*</span></label>
                                    <select name="product" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select product</option>
                                        <option value="PL">PL - Personal Loan</option>
                                        <option value="HL">HL - Home Loan</option>
                                        <option value="BL">BL - Business Loan</option>
                                        <option value="AL">AL - Auto Loan</option>
                                        <option value="GL">GL - Gold Loan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Branch Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="branch_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter branch name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1 bg-red-600 text-white px-2 py-1 rounded">Case Type (drop down) <span class="text-yellow-300">*</span></label>
                                    <select name="case_type" required class="w-full px-3 py-2 border-2 border-red-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white">
                                        <option value="">Select case type</option>
                                        <option value="DIST_CONS">DIST. CONS.</option>
                                        <option value="ORIGINAL_SUIT">ORIGINAL SUIT</option>
                                        <option value="STATE_CONS">STATE-CONS.</option>
                                        <option value="RC">RC</option>
                                        <option value="OA">OA</option>
                                        <option value="SA">SA</option>
                                        <option value="DIVORCE">Divorce</option>
                                        <option value="MUTUAL_DIVORCE">Mutual Divorce</option>
                                        <option value="CONJUGAL_RIGHTS">conjugal rights</option>
                                        <option value="DLA">DLA</option>
                                        <option value="MDDA">MDDA</option>
                                        <option value="MACT">MACT</option>
                                        <option value="MDDA_COMM">MDDA COMM</option>
                                        <option value="RERA">RERA</option>
                                        <option value="CIVIL_APPEAL">CIVIL APPEAL</option>
                                        <option value="MISC_APPEAL">MISC APPEAL</option>
                                        <option value="CIVIL_REVISION">CIVIL REVISION</option>
                                        <option value="CIVIL_MISC">CIVIL MISC</option>
                                        <option value="TRC">TRC</option>
                                        <option value="SEC_7_FC">SEC 7 FC</option>
                                        <option value="TA">TA</option>
                                        <option value="DLSA">DLSA</option>
                                        <option value="CIVIL_OTHER">CIVIL OTHER</option>
                                        <option value="CLK_ADALT">CLK ADALT</option>
                                        <option value="SCC">SCC</option>
                                        <option value="REVENUE">Revenue</option>
                                        <option value="RESTORATION">Restoration</option>
                                        <option value="RENT">RENT</option>
                                        <option value="CAVEAT">CAVEAT</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Region</label>
                                    <input type="text" name="region" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter region">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant/Authorised person</label>
                                    <input type="text" name="complainant_authorised_person" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter complainant/authorised person">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Filing_Location</label>
                                    <input type="text" name="filing_location" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter filing location">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Court_Name</label>
                                    <input type="text" name="court_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter court name">
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
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Advocate</label>
                                    <input type="text" name="advocate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter advocate name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Poa</label>
                                    <input type="text" name="poa" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter POA">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Complainant Section (Blue) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-blue-500 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-user-shield mr-2"></i>Complainant
                        </h2>
                        <div class="border-2 border-blue-500 rounded-b-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant <span class="text-red-500">*</span></label>
                                    <input type="text" name="complainant" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">Complainant ADDRESS <span class="text-red-500">*</span></label>
                                    <input type="text" name="complainant_address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter complainant address">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" id="addMoreComplainantAddress" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-plus mr-2"></i>OPTION TO ADD MORE ADDRESS
                                    </button>
                                    <div id="additionalComplainantAddresses" class="mt-4 space-y-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Defendant Section (Blue) -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-white bg-blue-500 px-4 py-2 rounded-t-lg">
                            <i class="fas fa-users mr-2"></i>Defendant
                        </h2>
                        <div class="border-2 border-blue-500 rounded-b-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">DEFENDANT <span class="text-red-500">*</span></label>
                                    <input type="text" name="defendant" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter defendant name">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-semibold mb-1">DEFENDANT ADDRESS <span class="text-red-500">*</span></label>
                                    <input type="text" name="defendant_address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" placeholder="Enter defendant address">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" id="addMoreDefendantAddress" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-plus mr-2"></i>OPTION TO ADD MORE ADDRESS
                                    </button>
                                    <div id="additionalDefendantAddresses" class="mt-4 space-y-3"></div>
                                </div>
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
                                                <input type="text" name="fee_grid_name[]" value="Filing/appearance" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="summon/written statement/Reply" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="issues" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="plaintiff evidence" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="defendant evidence" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="arguments" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="judgment" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Misc" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                        <tr class="border-b border-yellow-300">
                                            <td class="px-4 py-2">
                                                <input type="text" name="fee_grid_name[]" value="Certified Copy ( of any order )" class="w-full px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" name="fee_grid_amount[]" value="100" min="0" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2"></td>
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
            // Add More Complainant Address Functionality
            let complainantAddressCount = 0;
            document.getElementById('addMoreComplainantAddress').addEventListener('click', function() {
                complainantAddressCount++;
                const container = document.getElementById('additionalComplainantAddresses');
                const addressDiv = document.createElement('div');
                addressDiv.className = 'flex items-center gap-3 p-3 border border-gray-200 rounded-lg bg-white relative';
                addressDiv.innerHTML = `
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Additional Address ${complainantAddressCount}</label>
                        <input type="text" name="additional_complainant_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter additional complainant address">
                    </div>
                    <button type="button" class="remove-complainant-address text-red-500 hover:text-red-700 mt-5">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                container.appendChild(addressDiv);

                addressDiv.querySelector('.remove-complainant-address').addEventListener('click', function() {
                    addressDiv.remove();
                });
            });

            // Add More Defendant Address Functionality
            let defendantAddressCount = 0;
            document.getElementById('addMoreDefendantAddress').addEventListener('click', function() {
                defendantAddressCount++;
                const container = document.getElementById('additionalDefendantAddresses');
                const addressDiv = document.createElement('div');
                addressDiv.className = 'flex items-center gap-3 p-3 border border-gray-200 rounded-lg bg-white relative';
                addressDiv.innerHTML = `
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-semibold mb-1">Additional Address ${defendantAddressCount}</label>
                        <input type="text" name="additional_defendant_address[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter additional defendant address">
                    </div>
                    <button type="button" class="remove-defendant-address text-red-500 hover:text-red-700 mt-5">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                container.appendChild(addressDiv);

                addressDiv.querySelector('.remove-defendant-address').addEventListener('click', function() {
                    addressDiv.remove();
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
        });
    </script>
</body>

</html>
