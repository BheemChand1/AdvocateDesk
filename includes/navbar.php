<!-- Mobile Menu Toggle -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars text-xl"></i>
</button>

<!-- Header -->
<header class="bg-white/80 backdrop-blur-md border-b border-gray-300 sticky top-0 z-50">
    <div class="px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <button class="lg:hidden p-2 rounded-lg hover:bg-gray-200 transition" id="sidebarToggle">
                    <i class="fas fa-bars text-gray-800"></i>
                </button>
                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center lg:hidden">
                    <i class="fas fa-gavel text-white"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 hidden sm:block">Admin Panel</h1>
                <h1 class="text-xl font-bold text-gray-800 sm:hidden">Admin Panel</h1>
            </div>
            <div class="flex items-center space-x-4">
                <button class="p-2 rounded-lg hover:bg-gray-600/50 transition" id="refreshBtn" title="Refresh">
                    <i class="fas fa-sync-alt text-gray-600 hover:text-gray-800"></i>
                </button>
                <div class="hidden md:flex items-center space-x-3 px-3 py-2 rounded-lg bg-white/10 backdrop-blur-sm border border-gray-300">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div class="hidden lg:block">
                        <div class="text-sm font-semibold text-gray-800">Admin User</div>
                        <div class="text-xs text-gray-600">Administrator</div>
                    </div>
                </div>
                <button class="p-2 rounded-lg hover:bg-gray-600/50 transition" title="Logout">
                    <i class="fas fa-sign-out-alt text-gray-600 hover:text-gray-800"></i>
                </button>
            </div>
        </div>
    </div>
</header>
