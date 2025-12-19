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
        <main class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <!-- Header -->
            <div class="mb-8">
                <a href="<?php echo $client_id ? 'client-details.php?id='.$client_id : 'view-clients.php'; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-plus-circle text-blue-500 mr-3"></i>Create New Case
                </h1>
                <p class="text-gray-600">Add case details and cheque bounce information</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="create-case-process.php">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">

                    <!-- Loan & Customer Information -->
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-200">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>Loan & Customer Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Loan Number -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-hashtag mr-2 text-blue-500"></i>Loan Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="loan_number" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter loan number">
                            </div>

                            <!-- Customer Name -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-user mr-2 text-blue-500"></i>Customer Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="customer_name" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter customer name">
                            </div>

                            <!-- Product -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-box mr-2 text-blue-500"></i>Product <span class="text-red-500">*</span>
                                </label>
                                <select name="product" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select product</option>
                                    <option value="PL">PL - Personal Loan</option>
                                    <option value="HL">HL - Home Loan</option>
                                    <option value="BL">BL - Business Loan</option>
                                    <option value="AL">AL - Auto Loan</option>
                                    <option value="GL">GL - Gold Loan</option>
                                </select>
                            </div>

                            <!-- Branch Name -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-building mr-2 text-blue-500"></i>Branch Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="branch_name" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter branch name">
                            </div>
                        </div>
                    </div>

                    <!-- Cheque Information -->
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-200">
                            <i class="fas fa-money-check text-blue-500 mr-2"></i>Cheque Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Cheque Number -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-receipt mr-2 text-blue-500"></i>Cheque Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="cheque_no" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter cheque number">
                            </div>

                            <!-- Cheque Date -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-calendar mr-2 text-blue-500"></i>Cheque Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="cheque_date" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Cheque Amount -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-rupee-sign mr-2 text-blue-500"></i>Cheque Amount <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="cheque_amount" required min="0" step="0.01"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter amount">
                            </div>

                            <!-- Bank Name & Address -->
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-university mr-2 text-blue-500"></i>Bank Name & Address <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="bank_name_address" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="e.g., HDFC, Rajpur rd">
                            </div>

                            <!-- Cheque Holder Name -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-user-check mr-2 text-blue-500"></i>Cheque Holder Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="cheque_holder_name" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter cheque holder name">
                            </div>
                        </div>
                    </div>

                    <!-- Bounce Information -->
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-200">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Bounce Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Bounce Date -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-calendar-times mr-2 text-red-500"></i>Bounce Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="bounce_date" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Bounce Reason -->
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-comment-slash mr-2 text-red-500"></i>Bounce Reason <span class="text-red-500">*</span>
                                </label>
                                <select name="bounce_reason" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select bounce reason</option>
                                    <option value="Fund Insufficient">Fund Insufficient</option>
                                    <option value="Account Closed">Account Closed</option>
                                    <option value="Signature Mismatch">Signature Mismatch</option>
                                    <option value="Stop Payment">Stop Payment</option>
                                    <option value="Exceeds Arrangement">Exceeds Arrangement</option>
                                    <option value="Account Frozen">Account Frozen</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-200">
                            <i class="fas fa-address-card text-blue-500 mr-2"></i>Personal Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Father's Name -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-user-tie mr-2 text-blue-500"></i>Father's Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="father_name" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter father's name">
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-flag mr-2 text-blue-500"></i>Status <span class="text-red-500">*</span>
                                </label>
                                <select name="status" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Active">Active</option>
                                    <option value="Closed">Closed</option>
                                    <option value="Legal Notice Sent">Legal Notice Sent</option>
                                    <option value="In Court">In Court</option>
                                </select>
                            </div>

                            <!-- Office Address -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-briefcase mr-2 text-blue-500"></i>Office Address
                                </label>
                                <textarea name="off_address" rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                    placeholder="Enter office address"></textarea>
                            </div>

                            <!-- Residential Address -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-2">
                                    <i class="fas fa-home mr-2 text-blue-500"></i>Residential Address <span class="text-red-500">*</span>
                                </label>
                                <textarea name="resi_address" rows="3" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                    placeholder="Enter residential address"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-6 border-t border-gray-200">
                        <a href="<?php echo $client_id ? 'client-details.php?id='.$client_id : 'view-clients.php'; ?>"
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
</body>

</html>
