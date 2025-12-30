<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

$success_message = '';
$error_message = '';
$client = null;

// Get client ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view-clients.php");
    exit();
}

$client_id = intval($_GET['id']);

// Fetch client details
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: view-clients.php");
    exit();
}

$client = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $father_name = trim($_POST['father_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile_number']);
    $address = trim($_POST['address']);
    $gst_number = !empty($_POST['gst_number']) ? trim($_POST['gst_number']) : NULL;
    $pan_number = !empty($_POST['pan_number']) ? trim($_POST['pan_number']) : NULL;
    
    // Validate inputs
    if (empty($name) || empty($father_name) || empty($email) || empty($mobile) || empty($address)) {
        $error_message = "All required fields must be filled!";
    } else {
        // Check if email already exists for other clients
        $check_email = $conn->prepare("SELECT client_id FROM clients WHERE email = ? AND client_id != ?");
        $check_email->bind_param("si", $email, $client_id);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Email already exists!";
        } else {
            // Update client
            $stmt = $conn->prepare("UPDATE clients SET name = ?, father_name = ?, email = ?, mobile = ?, address = ?, gst_number = ?, pan_number = ? WHERE client_id = ?");
            $stmt->bind_param("sssssssi", $name, $father_name, $email, $mobile, $address, $gst_number, $pan_number, $client_id);
            
            if ($stmt->execute()) {
                $success_message = "Client updated successfully!";
                // Refresh client data
                $client['name'] = $name;
                $client['father_name'] = $father_name;
                $client['email'] = $email;
                $client['mobile'] = $mobile;
                $client['address'] = $address;
                $client['gst_number'] = $gst_number;
                $client['pan_number'] = $pan_number;
            } else {
                $error_message = "Error updating client: " . $conn->error;
            }
            $stmt->close();
        }
        $check_email->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client - Case Management</title>
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
            <div class="mb-4">
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-user-edit text-blue-500 mr-2"></i>Edit Client
                </h1>
            </div>

            <?php if ($success_message): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>

            <!-- Edit Client Form -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Full Name -->
                        <div class="md:col-span-2">
                            <label for="full_name" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-user mr-2 text-blue-500"></i>Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="full_name" name="full_name" required
                                value="<?php echo htmlspecialchars($client['name']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Enter client's full name">
                        </div>

                        <!-- Father's Name -->
                        <div class="md:col-span-2">
                            <label for="father_name" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-user-tie mr-2 text-blue-500"></i>Father's Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="father_name" name="father_name" required
                                value="<?php echo htmlspecialchars($client['father_name']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Enter father's name">
                        </div>

                        <!-- Email ID -->
                        <div class="md:col-span-2">
                            <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-envelope mr-2 text-blue-500"></i>Email ID <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" required
                                value="<?php echo htmlspecialchars($client['email']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Enter email address">
                        </div>

                        <!-- Mobile Number -->
                        <div>
                            <label for="mobile_number" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-phone mr-2 text-blue-500"></i>Mobile Number <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" id="mobile_number" name="mobile_number" required pattern="[0-9]{10}"
                                value="<?php echo htmlspecialchars($client['mobile']); ?>"
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
                                placeholder="Enter complete address"><?php echo htmlspecialchars($client['address']); ?></textarea>
                        </div>

                        <!-- GST Number -->
                        <div>
                            <label for="gst_number" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-file-invoice mr-2 text-blue-500"></i>GST Number
                            </label>
                            <input type="text" id="gst_number" name="gst_number"
                                value="<?php echo htmlspecialchars($client['gst_number'] ?? ''); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Enter GST number">
                        </div>

                        <!-- PAN Card Number -->
                        <div>
                            <label for="pan_number" class="block text-gray-700 text-sm font-semibold mb-2">
                                <i class="fas fa-id-card mr-2 text-blue-500"></i>PAN Card Number
                            </label>
                            <input type="text" id="pan_number" name="pan_number"
                                value="<?php echo htmlspecialchars($client['pan_number'] ?? ''); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Enter PAN number" style="text-transform: uppercase;">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-end space-y-3 sm:space-y-0 sm:space-x-4 mt-8 pt-6 border-t border-gray-200">
                        <a href="view-clients.php" class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition text-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-lg">
                            <i class="fas fa-save mr-2"></i>Update Client
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
