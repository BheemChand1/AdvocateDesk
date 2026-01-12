<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';

// Case type display mapping
$case_type_labels = [
    'NI_PASSA' => 'NI/PASSA',
    'CRIMINAL' => 'Criminal',
    'CONSUMER_CIVIL' => 'Consumer/Civil/Revenue/RERA/Family Court/DRT/PLA/Labour',
    'EP_ARBITRATION' => 'EP/Arbitration Executions',
    'ARBITRATION_OTHER' => 'Arbitration Other Than EP'
];

// Create case_accounts table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS case_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    bill_number VARCHAR(100),
    bill_date DATE,
    payment_status ENUM('pending', 'processing', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    UNIQUE KEY unique_case_account (case_id)
);
";

mysqli_query($conn, $create_table_sql);

// Fetch dashboard statistics
$stats = [];

// Total Clients
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM clients");
$row = mysqli_fetch_assoc($result);
$stats['total_clients'] = $row['count'] ?? 0;

// Total Cases
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM cases");
$row = mysqli_fetch_assoc($result);
$stats['total_cases'] = $row['count'] ?? 0;

// Active Cases
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM cases WHERE status = 'active'");
$row = mysqli_fetch_assoc($result);
$stats['active_cases'] = $row['count'] ?? 0;

// Pending Cases
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM cases WHERE status = 'pending'");
$row = mysqli_fetch_assoc($result);
$stats['pending_cases'] = $row['count'] ?? 0;

// Closed Cases
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM cases WHERE status = 'closed'");
$row = mysqli_fetch_assoc($result);
$stats['closed_cases'] = $row['count'] ?? 0;

// Total Case Types
$result = mysqli_query($conn, "SELECT COUNT(DISTINCT case_type) as count FROM cases");
$row = mysqli_fetch_assoc($result);
$stats['case_types'] = $row['count'] ?? 0;

// Get account pending count
$pending_accounts_sql = "
SELECT COUNT(DISTINCT c.id) as total
FROM cases c
LEFT JOIN case_accounts ca ON c.id = ca.case_id
WHERE ca.id IS NULL
";
$pending_result = mysqli_query($conn, $pending_accounts_sql);
$stats['account_pending'] = mysqli_fetch_assoc($pending_result)['total'] ?? 0;

// Get account processing count
$processing_accounts_sql = "
SELECT COUNT(*) as total
FROM case_accounts
WHERE payment_status = 'processing'
";
$processing_result = mysqli_query($conn, $processing_accounts_sql);
$stats['account_processing'] = mysqli_fetch_assoc($processing_result)['total'] ?? 0;

// Cases by Case Type
$result = mysqli_query($conn, "SELECT case_type, COUNT(*) as count FROM cases GROUP BY case_type ORDER BY count DESC");
$case_type_breakdown = [];
while ($row = mysqli_fetch_assoc($result)) {
    $case_type_breakdown[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Case Management</title>
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
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Card 1 - Total Clients -->
                <div class="stat-card gradient-1 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Total Clients</p>
                            <div class="text-3xl sm:text-4xl font-bold"><?php echo $stats['total_clients']; ?></div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Registered clients</p>
                    </div>
                </div>

                <!-- Card 2 - Total Cases -->
                <div class="stat-card gradient-2 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Total Cases</p>
                            <div class="text-3xl sm:text-4xl font-bold"><?php echo $stats['total_cases']; ?></div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-briefcase text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Total cases filed</p>
                    </div>
                </div>

                <!-- Card 3 - Active Cases -->
                <div class="stat-card gradient-3 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Active Cases</p>
                            <div class="text-3xl sm:text-4xl font-bold"><?php echo $stats['active_cases']; ?></div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-hourglass-start text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Currently active</p>
                    </div>
                </div>

                <!-- Card 4 - Pending Cases -->
                <div class="stat-card gradient-4 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Pending Cases</p>
                            <div class="text-3xl sm:text-4xl font-bold"><?php echo $stats['pending_cases']; ?></div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-clock text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Awaiting action</p>
                    </div>
                </div>

                <!-- Card 5 - Closed Cases -->
                <div class="stat-card gradient-5 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Closed Cases</p>
                            <div class="text-3xl sm:text-4xl font-bold"><?php echo $stats['closed_cases']; ?></div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-check-circle text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Completed cases</p>
                    </div>
                </div>

                <!-- Card 6 - Case Types -->
                <div class="stat-card gradient-6 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Case Types</p>
                            <div class="text-3xl sm:text-4xl font-bold"><?php echo $stats['case_types']; ?></div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-file-alt text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Available categories</p>
                    </div>
                </div>

                <!-- Card 7 - Accounts -->
                <div class="stat-card gradient-accounts rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Accounts</p>
                            <div class="flex gap-4">
                                <a href="pending-cases.php" class="hover:scale-110 transition transform cursor-pointer">
                                    <div class="text-2xl font-bold"><?php echo $stats['account_pending']; ?></div>
                                    <p class="text-xs text-white/70">Pending</p>
                                </a>
                                <a href="processing-fees.php" class="hover:scale-110 transition transform cursor-pointer">
                                    <div class="text-2xl font-bold"><?php echo $stats['account_processing']; ?></div>
                                    <p class="text-xs text-white/70">Processing</p>
                                </a>
                            </div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Click on pending or processing to manage</p>
                    </div>
                </div>
            </div>

        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
