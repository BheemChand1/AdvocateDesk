<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="flex items-center justify-center">
            <img src="./assets/mps-logo.png" alt="Case Management Logo" class="w-50 h-40">
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

        <a href="cause-list.php" class="sidebar-item">
            <i class="fas fa-calendar-alt"></i>
            <span>Cause List</span>
        </a>

        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item has-dropdown">
                <i class="fas fa-envelope-open-text"></i>
                <span>Notices</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="submenu">
                <a href="create-notice.php" class="submenu-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Notice</span>
                </a>
                <a href="view-notices.php" class="submenu-item">
                    <i class="fas fa-list"></i>
                    <span>View Notices</span>
                </a>
            </div>
        </div>

        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item has-dropdown">
                <i class="fas fa-wallet"></i>
                <span>Accounts</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="submenu">
                <a href="pending-cases.php" class="submenu-item">
                    <i class="fas fa-hourglass-half"></i>
                    <span>Pending Cases</span>
                </a>
                <a href="processing-fees.php" class="submenu-item">
                    <i class="fas fa-clock"></i>
                    <span>Processing Fees</span>
                </a>
                <a href="completed-fees.php" class="submenu-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Completed Fees</span>
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
