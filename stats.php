<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Listings</title>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Card 1 - Total Clients -->
                <div class="stat-card gradient-1 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Total Clients</p>
                            <div class="counter" data-target="2847">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Active clients in system</p>
                    </div>
                </div>

                <!-- Card 2 - Total Defendants -->
                <div class="stat-card gradient-2 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Total Defendants</p>
                            <div class="counter" data-target="1563">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-user-slash text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Registered defendants</p>
                    </div>
                </div>

                <!-- Card 3 - Total Case Types -->
                <div class="stat-card gradient-3 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Total Case Types</p>
                            <div class="counter" data-target="18">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-file-alt text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Available case categories</p>
                    </div>
                </div>

                <!-- Card 4 - Total Court Types -->
                <div class="stat-card gradient-4 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Court</p>
                            <div class="counter" data-target="12">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-balance-scale text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Court jurisdictions</p>
                    </div>
                </div>

                <!-- Card 5 - Total Parvi ferge due -->
                <div class="stat-card gradient-5 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Total Parvi / Steps Due</p>
                            <div class="counter" data-target="342">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-calendar-alt text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Scheduled hearings</p>
                    </div>
                </div>

                <!-- Card 6 - Total Notices Sent -->
                <div class="stat-card gradient-6 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Total Notices Sent</p>
                            <div class="counter" data-target="5234">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-envelope text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Notifications issued</p>
                    </div>
                </div>

                <!-- Card 7 - Cases due for filing -->
                <div class="stat-card gradient-7 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Cases due for filing</p>
                            <div class="counter" data-target="7">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-tasks text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Cases pending filing</p>
                    </div>
                </div>

                <!-- Card 8 - Case Position stage -->
                <div class="stat-card gradient-8 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Case Position / stage</p>
                            <div class="counter" data-target="9">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-sitemap text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Position categories</p>
                    </div>
                </div>

                <!-- Card 9 - Parvi Stages -->
                <div class="stat-card gradient-9 rounded-xl p-6 sm:p-8 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-white/80 text-sm sm:text-base mb-2">Parvi Stages 4/stage</p>
                            <div class="counter" data-target="5">0</div>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-layer-group text-2xl"></i>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xs sm:text-sm text-white/70">Hearing progress levels</p>
                    </div>
                </div>
            </div>

            <!-- Footer Stats -->
            <div class="mt-12 sm:mt-16 bg-white/50 backdrop-blur-md rounded-xl p-6 sm:p-8 border border-gray-300">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-gray-600 text-sm mb-2">Last Updated</p>
                        <p class="text-gray-800 font-semibold" id="updateTime">Just now</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-sm mb-2">Total Records</p>
                        <p class="text-gray-800 font-semibold">10,028</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-sm mb-2">System Status</p>
                        <p class="text-green-600 font-semibold flex items-center justify-center"><span
                                class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>Active</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-sm mb-2">Database</p>
                        <p class="text-gray-800 font-semibold">Connected</p>
                    </div>
                </div>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <script src="./assets/script.js"></script>
</body>

</html>
