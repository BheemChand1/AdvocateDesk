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
SELECT COUNT(*) as total
FROM case_position_updates
WHERE payment_status = 'pending' AND fee_amount > 0
";
$pending_result = mysqli_query($conn, $pending_accounts_sql);
$stats['account_pending'] = mysqli_fetch_assoc($pending_result)['total'] ?? 0;

// Get account processing count
$processing_accounts_sql = "
SELECT COUNT(*) as total
FROM case_position_updates
WHERE payment_status = 'processing' AND fee_amount > 0
";
$processing_result = mysqli_query($conn, $processing_accounts_sql);
$stats['account_processing'] = mysqli_fetch_assoc($processing_result)['total'] ?? 0;

// Cases by Case Type
$result = mysqli_query($conn, "SELECT case_type, COUNT(*) as count FROM cases GROUP BY case_type ORDER BY count DESC");
$case_type_breakdown = [];
while ($row = mysqli_fetch_assoc($result)) {
    $case_type_breakdown[] = $row;
}

// Priority Cases
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM cases WHERE priority_status = 1");
$row = mysqli_fetch_assoc($result);
$stats['priority_cases'] = $row['count'] ?? 0;

// Get notices due/overdue for dashboard
$notices_query = "SELECT id, unique_notice_id, client_id, section, act, due_date, status,
                 DATEDIFF(due_date, CURDATE()) as days_left
                 FROM notices 
                 WHERE status = 'open' AND DATEDIFF(due_date, CURDATE()) <= 7 AND DATEDIFF(due_date, CURDATE()) >= -30
                 ORDER BY due_date ASC
                 LIMIT 5";
$notices_result = mysqli_query($conn, $notices_query);
$upcoming_notices = [];
while ($row = mysqli_fetch_assoc($notices_result)) {
    $upcoming_notices[] = $row;
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
                <a href="view-clients.php" class="stat-card gradient-1 rounded-xl p-6 sm:p-8 text-white shadow-lg hover:shadow-xl transition cursor-pointer">
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
                </a>

                <!-- Card 2 - Total Cases -->
                <a href="view-cases.php" class="stat-card gradient-2 rounded-xl p-6 sm:p-8 text-white shadow-lg hover:shadow-xl transition cursor-pointer">
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
                </a>

                <!-- Card 3 - Active Cases -->
                <a href="view-cases.php?status=active" class="stat-card gradient-3 rounded-xl p-6 sm:p-8 text-white shadow-lg hover:shadow-xl transition cursor-pointer">
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
                </a>

                <!-- Card 4 - Pending Cases -->
                <!-- <a href="view-cases.php" class="stat-card gradient-4 rounded-xl p-6 sm:p-8 text-white shadow-lg hover:shadow-xl transition cursor-pointer">
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
                </a> -->

                <!-- Card 5 - Closed Cases -->
                <a href="view-cases.php?status=closed" class="stat-card gradient-5 rounded-xl p-6 sm:p-8 text-white shadow-lg hover:shadow-xl transition cursor-pointer">
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
                </a>

                <!-- Card 6 - Case Types -->
                <a href="view-cases.php" class="stat-card gradient-6 rounded-xl p-6 sm:p-8 text-white shadow-lg hover:shadow-xl transition cursor-pointer">
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
                </a>

                <!-- Card 7 - Priority Cases -->
                <a href="cause-list.php?priority=1" class="stat-card gradient-priority rounded-xl p-6 sm:p-8 text-white shadow-lg hover:shadow-xl transition cursor-pointer">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Priority Cases</p>
                            <div class="text-3xl sm:text-4xl font-bold"><?php echo $stats['priority_cases']; ?></div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-star text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">High priority cases</p>
                    </div>
                </a>

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

            <!-- Notices Section -->
            <div class="mt-8 sm:mt-12">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-envelope-open-text text-blue-500 mr-2"></i>Upcoming Notices
                    </h2>
                    <a href="view-notices.php" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">View All</a>
                </div>

                <?php if (count($upcoming_notices) > 0): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Notice ID</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Section / Act</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Due Date</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Days Left</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($upcoming_notices as $notice): 
                                    $days_left = $notice['days_left'];
                                    $is_overdue = $days_left < 0;
                                    $days_color = $is_overdue ? 'text-red-600 font-bold' : ($days_left <= 3 ? 'text-orange-600 font-bold' : 'text-blue-600');
                                    $days_text = $is_overdue ? 'Overdue by ' . abs($days_left) . ' days' : $days_left . ' days left';
                                    $row_color = $is_overdue ? 'bg-red-50 hover:bg-red-100' : ($days_left <= 3 ? 'bg-orange-50 hover:bg-orange-100' : 'hover:bg-gray-50');
                                ?>
                                <tr class="<?php echo $row_color; ?> transition">
                                    <td class="px-4 py-3 text-sm font-mono text-blue-600 font-semibold"><?php echo htmlspecialchars($notice['unique_notice_id']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <div><?php echo htmlspecialchars($notice['section']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($notice['act']); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800"><?php echo date('d M, Y', strtotime($notice['due_date'])); ?></td>
                                    <td class="px-4 py-3 text-sm <?php echo $days_color; ?>"><?php echo $days_text; ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="view-notices.php" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-xs font-semibold hover:bg-blue-200 transition">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-xl shadow-md p-8 text-center">
                    <i class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
                    <p class="text-gray-600">No notices due or overdue in the next 30 days</p>
                    <a href="view-notices.php" class="inline-block mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        View All Notices
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
