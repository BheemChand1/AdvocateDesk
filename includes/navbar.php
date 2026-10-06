<!-- Mobile Menu Toggle -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars text-xl"></i>
</button>

<!-- Header -->
<header class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 sticky top-0 z-50 shadow-sm">
    <div class="px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex items-center justify-between">
            <!-- Left Side: Logo and Title -->
            <div class="flex items-center space-x-4">
                <button class="lg:hidden p-2 rounded-lg hover:bg-gray-200 transition" id="sidebarToggle">
                    <i class="fas fa-bars text-gray-700"></i>
                </button>
                <!-- <img src="./assets/mps-logo.png" alt="MPS Legal Logo" class="w-12 h-12 object-contain"> -->
                <div class="hidden sm:flex flex-col">
                    <h1 class="text-xl font-bold text-gray-800">MPS Legal</h1>
                    <p class="text-xs text-gray-600">Case Management System</p>
                </div>
            </div>
            
            <!-- Right Side: Actions and User Info -->
            <div class="flex items-center space-x-4">
                <button class="p-2 rounded-lg hover:bg-gray-200 transition text-gray-600 hover:text-gray-800" id="refreshBtn" title="Refresh Data">
                    <i class="fas fa-sync-alt text-lg"></i>
                </button>
                
                <div class="hidden md:flex items-center space-x-4 pl-4 border-l border-gray-300">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-md">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div class="hidden lg:block">
                        <div class="text-sm font-semibold text-gray-800"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?></div>
                        <div class="text-xs text-gray-500"><?php echo isset($_SESSION['user_role']) ? ucfirst(htmlspecialchars($_SESSION['user_role'])) : 'Member'; ?></div>
                    </div>
                </div>
                
                <a href="logout.php" class="p-2 rounded-lg hover:bg-red-100 transition text-gray-600 hover:text-red-600" title="Logout">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>
            </div>
        </div>
    </div>
</header>
