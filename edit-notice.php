<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Get notice ID from URL
$notice_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$notice_id) {
    header("Location: view-notices.php?error=" . urlencode("Invalid notice ID"));
    exit();
}

// Fetch notice details
$query = "SELECT n.*, c.name as client_name FROM notices n 
          LEFT JOIN clients c ON n.client_id = c.client_id 
          WHERE n.id = $notice_id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: view-notices.php?error=" . urlencode("Notice not found"));
    exit();
}

$notice = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Notice - Case Management</title>
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
                    <i class="fas fa-edit text-blue-500 mr-2"></i>Edit Notice
                </h1>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="edit-notice-process.php">
                    <input type="hidden" name="notice_id" value="<?php echo $notice_id; ?>">

                    <!-- Notice Type Display -->
                    <div class="mb-6 pb-4 border-b-2 border-blue-200 bg-blue-50 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-envelope-open-text text-blue-500 text-2xl mr-3"></i>
                            <div>
                                <span class="text-sm text-gray-600">Editing:</span>
                                <h2 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($notice['unique_notice_id']); ?></h2>
                                <p class="text-sm text-gray-600">Client: <?php echo htmlspecialchars($notice['client_name']); ?></p>
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
                                <input type="date" name="notice_date" required value="<?php echo htmlspecialchars($notice['notice_date']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Section <span class="text-red-500">*</span></label>
                                <input type="text" name="section" required value="<?php echo htmlspecialchars($notice['section']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter section">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Act <span class="text-red-500">*</span></label>
                                <input type="text" name="act" required value="<?php echo htmlspecialchars($notice['act']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter act">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Post Date</label>
                                <input type="date" name="post_date" value="<?php echo htmlspecialchars($notice['post_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-1">Days Reminder <span class="text-red-500">*</span></label>
                                <select name="days_reminder" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Days</option>
                                    <?php 
                                    for ($i = 1; $i <= 30; $i++) { 
                                        $selected = ($i == $notice['days_reminder']) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i days</option>";
                                    } 
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Display Calculated Due Date -->
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-gray-700">
                                <strong>Current Due Date:</strong> 
                                <span class="text-blue-600 font-semibold"><?php echo date('d M, Y', strtotime($notice['due_date'])); ?></span>
                                <span class="text-gray-500 ml-2">(will be recalculated on save)</span>
                            </p>
                        </div>
                    </div>

                    <!-- Additional Input -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Additional Input</label>
                        <textarea name="input_data" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter any additional information..."><?php echo htmlspecialchars($notice['input_data'] ?? ''); ?></textarea>
                    </div>

                    <!-- Addressee -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Addressee (To Whom Notice is Sent) <span class="text-red-500">*</span></label>
                        <textarea name="addressee" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter the addressee/recipient of the notice..."><?php echo htmlspecialchars($notice['addressee'] ?? ''); ?></textarea>
                    </div>

                    <!-- Notice Metadata -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                            <i class="fas fa-history text-gray-500 mr-2"></i>Notice Metadata
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Status</p>
                                <p class="font-semibold text-gray-800"><?php echo ucfirst(htmlspecialchars($notice['status'])); ?></p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Created</p>
                                <p class="font-semibold text-gray-800"><?php echo date('d M, Y H:i', strtotime($notice['created_at'])); ?></p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Last Updated</p>
                                <p class="font-semibold text-gray-800"><?php echo date('d M, Y H:i', strtotime($notice['updated_at'])); ?></p>
                            </div>
                            <?php if ($notice['closed_date']): ?>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">Closed Date</p>
                                <p class="font-semibold text-gray-800"><?php echo date('d M, Y H:i', strtotime($notice['closed_date'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
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
                            <i class="fas fa-save mr-2"></i>Save Changes
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
