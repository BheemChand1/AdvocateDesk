<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                <i class="fas fa-balance-scale text-white"></i>
            </div>
            <div>
                <h2 class="text-gray-800 font-bold text-base">Case Management</h2>
                <p class="text-gray-600 text-xs">System Panel</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="sidebar-section-title">Main Menu</div>
        <a href="index.php" class="sidebar-item active">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item has-dropdown">
                <i class="fas fa-users"></i>
                <span>Clients</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="submenu">
                <a href="create-client.php" class="submenu-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Client</span>
                </a>
                <a href="view-clients.php" class="submenu-item">
                    <i class="fas fa-list"></i>
                    <span>View Clients</span>
                </a>
            </div>
        </div>
       
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item has-dropdown">
                <i class="fas fa-briefcase"></i>
                <span>Cases</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="submenu">
                <a href="create-case.php" class="submenu-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Case</span>
                </a>
                <a href="view-cases.php" class="submenu-item">
                    <i class="fas fa-list"></i>
                    <span>View Cases</span>
                </a>
            </div>
        </div>

         <!-- <a href="#" class="sidebar-item">
            <i class="fas fa-user-slash"></i>
            <span>Defendants</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-calendar-alt"></i>
            <span>Parvi Dates</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-wallet"></i>
            <span>Account Management</span>
        </a> -->

        <!-- <div class="sidebar-section-title">Management</div>
        <a href="#" class="sidebar-item">
            <i class="fas fa-file-alt"></i>
            <span>Case Types</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-balance-scale"></i>
            <span>Court Types</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-envelope"></i>
            <span>Notices</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-tasks"></i>
            <span>Case Stages</span>
        </a> -->

        <!-- <div class="sidebar-section-title">Reports & Settings</div>
        <a href="#" class="sidebar-item">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a> -->
    </nav>
</aside>
