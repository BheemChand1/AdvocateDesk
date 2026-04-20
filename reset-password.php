<?php
require_once 'includes/connection.php';

$error = '';
$success = '';
$selected_user_id = 0;
$new_username_value = '';

$target_user = null;
$target_user_query = "SELECT id, full_name, username, status FROM admin_users WHERE role = 'user' AND status = 'active' ORDER BY id ASC LIMIT 1";
$target_user_result = mysqli_query($conn, $target_user_query);
if ($target_user_result && mysqli_num_rows($target_user_result) > 0) {
    $target_user = mysqli_fetch_assoc($target_user_result);
    $selected_user_id = (int) $target_user['id'];
    $new_username_value = $target_user['username'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
    $new_username = isset($_POST['new_username']) ? trim($_POST['new_username']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    $logout_all_devices = isset($_POST['logout_all_devices']) ? 1 : 0;

    $new_username_value = $new_username;

    if (!$target_user) {
        $error = 'No active user account found for role user.';
    } elseif ($old_password === '' || $new_username === '' || $new_password === '' || $confirm_password === '') {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirm password do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        // Only allow records where role is user.
        $user_sql = "SELECT id, username, password, status FROM admin_users WHERE id = ? AND role = 'user' LIMIT 1";
        $user_stmt = mysqli_prepare($conn, $user_sql);
        mysqli_stmt_bind_param($user_stmt, 'i', $selected_user_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user = mysqli_fetch_assoc($user_result);

        if (!$user) {
            $error = 'Selected user account was not found.';
        } elseif ($user['status'] !== 'active') {
            $error = 'Selected user account is inactive.';
        } elseif (!password_verify($old_password, $user['password'])) {
            $error = 'Old password is incorrect.';
        } else {
            $username_check_sql = 'SELECT id FROM admin_users WHERE username = ? AND id != ? LIMIT 1';
            $username_check_stmt = mysqli_prepare($conn, $username_check_sql);
            mysqli_stmt_bind_param($username_check_stmt, 'si', $new_username, $selected_user_id);
            mysqli_stmt_execute($username_check_stmt);
            $username_check_result = mysqli_stmt_get_result($username_check_stmt);

            if (mysqli_num_rows($username_check_result) > 0) {
                $error = 'This username is already taken. Please choose another username.';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // If logout from all devices is checked, set session_token to NULL
                $session_token_value = $logout_all_devices ? NULL : null;
                if ($logout_all_devices) {
                    $update_sql = 'UPDATE admin_users SET username = ?, password = ?, session_token = NULL, updated_at = NOW() WHERE id = ? AND role = "user"';
                } else {
                    $update_sql = 'UPDATE admin_users SET username = ?, password = ?, updated_at = NOW() WHERE id = ? AND role = "user"';
                }
                
                $update_stmt = mysqli_prepare($conn, $update_sql);
                if ($logout_all_devices) {
                    mysqli_stmt_bind_param($update_stmt, 'ssi', $new_username, $hashed_password, $selected_user_id);
                } else {
                    mysqli_stmt_bind_param($update_stmt, 'ssi', $new_username, $hashed_password, $selected_user_id);
                }

                if (mysqli_stmt_execute($update_stmt) && mysqli_stmt_affected_rows($update_stmt) >= 0) {
                    if ($logout_all_devices) {
                        $success = 'Username and password updated successfully. All other devices have been logged out.';
                    } else {
                        $success = 'Username and password updated successfully.';
                    }
                    $new_username_value = $new_username;
                } else {
                    $error = 'Unable to update credentials. Please try again.';
                }
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
    <title>User Credential Reset - Case Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-blue-900 via-purple-900 to-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-6 text-white">
                <h1 class="text-2xl font-bold"><i class="fas fa-user-shield mr-2"></i>Reset User Credentials</h1>
                <p class="text-sm text-blue-100 mt-1">Update username and password for accounts with role: user</p>
            </div>

            <div class="p-6 md:p-8">
                <?php if ($error !== ''): ?>
                    <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$target_user): ?>
                    <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg">
                        <i class="fas fa-info-circle mr-2"></i>No active users with role <strong>user</strong> found.
                    </div>
                <?php else: ?>
                    <form method="POST" action="" class="space-y-5">
                        <input type="hidden" name="user_id" value="<?php echo (int) $selected_user_id; ?>">

                        <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm text-gray-700">
                            <i class="fas fa-user mr-2 text-blue-500"></i>
                            Updating account: <span class="font-semibold"><?php echo htmlspecialchars($target_user['full_name']); ?></span>
                        </div>

                        <div>
                            <label for="old_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2 text-blue-500"></i>Old Password
                            </label>
                            <input type="password" id="old_password" name="old_password" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter current password of selected user">
                        </div>

                        <div>
                            <label for="new_username" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-at mr-2 text-blue-500"></i>New Username
                            </label>
                            <input type="text" id="new_username" name="new_username" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter new username"
                                value="<?php echo $new_username_value; ?>">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-key mr-2 text-blue-500"></i>New Password
                                </label>
                                <input type="password" id="new_password" name="new_password" required minlength="6"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Minimum 6 characters">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-check-circle mr-2 text-blue-500"></i>Confirm Password
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Re-enter new password">
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <label class="flex items-start cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                                <input type="checkbox" id="logout_all_devices" name="logout_all_devices" 
                                    class="w-5 h-5 text-red-500 rounded focus:ring-2 focus:ring-red-500 mt-0.5">
                                <div class="ml-3">
                                    <p class="font-semibold text-gray-800">
                                        <i class="fas fa-sign-out-alt mr-2 text-red-500"></i>Logout from all devices
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">Check this to invalidate all active sessions. The user will be logged out from all devices.</p>
                                </div>
                            </label>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                            <p class="font-semibold mb-1"><i class="fas fa-info-circle mr-2"></i>Note</p>
                            <p>This page updates only active accounts where role is set to <strong>user</strong>.</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 justify-end">
                            <a href="login.php" class="px-5 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-center">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Login
                            </a>
                            <button type="submit" class="px-5 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition font-semibold">
                                <i class="fas fa-save mr-2"></i>Update Credentials
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
