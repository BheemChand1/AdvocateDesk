<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../includes/connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid user ID");
    exit();
}

$user_id = intval($_GET['id']);

// Prevent editing administrator account
if ($user_id == $_SESSION['admin_id']) {
    header("Location: index.php?error=Cannot edit administrator account");
    exit();
}

// Get user data
$query = "SELECT * FROM admin_users WHERE id = ? AND id != ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $_SESSION['admin_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php?error=User not found");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate inputs
    if (empty($full_name) || empty($username)) {
        $error = "Full name and username are required";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if username already exists (excluding current user)
        $check_sql = "SELECT id FROM admin_users WHERE username = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $username, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Username already exists";
        } else {
            // Update user
            if (!empty($password)) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE admin_users SET full_name = ?, username = ?, password = ?, status = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "ssssi", $full_name, $username, $hashed_password, $status, $user_id);
            } else {
                // Update without changing password
                $update_sql = "UPDATE admin_users SET full_name = ?, username = ?, status = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sssi", $full_name, $username, $status, $user_id);
            }
            
            if (mysqli_stmt_execute($update_stmt)) {
                header("Location: index.php?success=User updated successfully");
                exit();
            } else {
                $error = "Error updating user: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-edit text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Edit User</h1>
                        <p class="text-sm text-gray-600">Update user information</p>
                    </div>
                </div>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-user mr-2 text-blue-500"></i>Full Name
                        </label>
                        <input type="text" id="full_name" name="full_name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter full name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>

                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-at mr-2 text-blue-500"></i>Username
                        </label>
                        <input type="text" id="username" name="username" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter username" value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-lock mr-2 text-blue-500"></i>New Password
                        </label>
                        <input type="password" id="password" name="password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Leave blank to keep current password">
                        <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-lock mr-2 text-blue-500"></i>Confirm New Password
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Confirm new password">
                    </div>

                    <!-- Role (Read-only) -->
                    <div>
                        <label for="role" class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-user-tag mr-2 text-blue-500"></i>Role
                        </label>
                        <input type="text" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">Role cannot be changed</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-toggle-on mr-2 text-blue-500"></i>Status
                        </label>
                        <select id="status" name="status" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="active" <?php echo ($user['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($user['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- User Info -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Created:</span>
                            <span class="font-semibold ml-2"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Last Login:</span>
                            <span class="font-semibold ml-2"><?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-4 mt-8">
                    <a href="index.php" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition">
                        <i class="fas fa-save mr-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>
