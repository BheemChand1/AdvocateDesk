<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Notices - Case Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="./assets/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<body class="bg-gradient-to-br from-gray-200 via-gray-100 to-slate-200 min-h-screen">
    <?php include './includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <?php include './includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="px-4 sm:px-6 lg:px-8 py-4">
            <!-- Header -->
            <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-4 sm:space-y-0">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-envelope-open-text text-blue-500 mr-2"></i>All Notices
                </h1>
                <div class="flex gap-3 flex-wrap">
                    <button onclick="printNotices()" 
                        class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition shadow-md">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <a href="create-notice.php"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition shadow-md">
                        <i class="fas fa-plus mr-2"></i>Create Notice
                    </a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php
            if (isset($_GET['success'])) {
                echo '<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>' . htmlspecialchars($_GET['success']) . '
                </div>';
            }
            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>' . htmlspecialchars($_GET['error']) . '
                </div>';
            }
            ?>

            <!-- Filter Controls -->
            <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Search</label>
                        <input type="text" id="searchInput" placeholder="Client name, notice ID..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Status</label>
                        <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">All Notices</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <!-- Records Per Page -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Show Entries</label>
                        <select id="recordsPerPage" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="10">10 entries</option>
                            <option value="25">25 entries</option>
                            <option value="50">50 entries</option>
                            <option value="100">100 entries</option>
                            <option value="-1">All entries</option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    <div class="flex items-end">
                        <button onclick="clearFilters()" class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition text-sm font-semibold">
                            <i class="fas fa-redo mr-2"></i>Clear Filters
                        </button>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table id="noticesTable" class="w-full display">
                        <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Notice ID</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Client Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Addressee</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Notice Date</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Section / Act</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Due Date</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Days Left</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                            // Fetch all notices sorted by due_date
                            $query = "SELECT n.id, n.unique_notice_id, n.client_id, c.name as client_name, 
                                     n.notice_date, n.due_date, n.section, n.act, n.status, n.closed_date, n.addressee,
                                     COUNT(nr.id) as remarks_count
                                     FROM notices n
                                     LEFT JOIN clients c ON n.client_id = c.client_id
                                     LEFT JOIN notice_remarks nr ON n.id = nr.notice_id
                                     GROUP BY n.id
                                     ORDER BY n.due_date ASC, n.created_at DESC";

                            $result = mysqli_query($conn, $query);

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $notice_id = htmlspecialchars($row['id']);
                                    $unique_notice_id = htmlspecialchars($row['unique_notice_id']);
                                    $client_name = htmlspecialchars($row['client_name'] ?? 'N/A');
                                    $addressee = htmlspecialchars($row['addressee'] ?? 'N/A');
                                    $notice_date = date('d M, Y', strtotime($row['notice_date']));
                                    $due_date = date('d M, Y', strtotime($row['due_date']));
                                    $section = htmlspecialchars($row['section']);
                                    $act = htmlspecialchars($row['act']);
                                    $status = htmlspecialchars($row['status']);
                                    $remarks_count = $row['remarks_count'];
                                    
                                    // Calculate days left
                                    $due_date_obj = new DateTime($row['due_date']);
                                    $today = new DateTime('today');
                                    $days_left = $today->diff($due_date_obj)->days;
                                    $is_overdue = $today > $due_date_obj;
                                    
                                    if ($is_overdue) {
                                        $days_text = "Overdue by {$days_left} days";
                                        $days_badge = "bg-red-100 text-red-600";
                                    } else {
                                        $days_text = "{$days_left} days left";
                                        $days_badge = "bg-blue-100 text-blue-600";
                                    }

                                    // Status badge color
                                    $status_badge = match ($status) {
                                        'open' => 'bg-blue-100 text-blue-800',
                                        'closed' => 'bg-gray-100 text-gray-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };

                                    echo "
                                    <tr class='hover:bg-gray-50 transition' data-status='$status'>
                                        <td class='px-4 py-3 text-sm font-mono text-blue-600 font-semibold'>$unique_notice_id</td>
                                        <td class='px-4 py-3 text-sm font-semibold text-gray-800'>$client_name</td>
                                        <td class='px-4 py-3 text-sm text-gray-700'>$addressee</td>
                                        <td class='px-4 py-3 text-sm text-gray-700'>$notice_date</td>
                                        <td class='px-4 py-3 text-sm text-gray-700'>
                                            <div>$section</div>
                                            <div class='text-xs text-gray-500'>$act</div>
                                        </td>
                                        <td class='px-4 py-3 text-sm text-gray-700 font-semibold'>$due_date</td>
                                        <td class='px-4 py-3 text-sm'>
                                            <span class='px-3 py-1 rounded-full text-xs font-semibold $days_badge'>$days_text</span>
                                        </td>
                                        <td class='px-4 py-3 text-sm'>
                                            <span class='inline-block px-3 py-1 $status_badge rounded-full text-xs font-semibold'>" . ucfirst($status) . "</span>
                                        </td>
                                        <td class='px-4 py-3 text-center'>
                                            <div class='flex items-center justify-center space-x-2'>
                                                <button onclick='viewNoticeDetails($notice_id)' class='p-2 text-blue-500 hover:bg-blue-100 rounded-lg transition' title='View Details'>
                                                    <i class='fas fa-eye'></i>
                                                </button>
                                                <a href='edit-notice.php?id=$notice_id' class='p-2 text-purple-500 hover:bg-purple-100 rounded-lg transition' title='Edit Notice'>
                                                    <i class='fas fa-edit'></i>
                                                </a>
                                                <button onclick='addRemark($notice_id)' class='p-2 text-green-500 hover:bg-green-100 rounded-lg transition' title='Add Remark'>
                                                    <i class='fas fa-comment'></i>
                                                </button>
                                                " . ($status === 'open' ? "
                                                <button onclick='closeNotice($notice_id)' class='p-2 text-orange-500 hover:bg-orange-100 rounded-lg transition' title='Close Notice'>
                                                    <i class='fas fa-check-circle'></i>
                                                </button>
                                                " : "") . "
                                                <button onclick='deleteNotice($notice_id)' class='p-2 text-red-500 hover:bg-red-100 rounded-lg transition' title='Delete'>
                                                    <i class='fas fa-trash'></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "
                                <tr>
                                    <td colspan='9' class='px-4 py-6 text-center text-gray-500'>
                                        <i class='fas fa-inbox text-3xl mb-2'></i>
                                        <p class='mt-2'>No notices found</p>
                                    </td>
                                </tr>
                                ";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <?php include './includes/footer.php'; ?>
    </div>

    <!-- View Notice Details Modal -->
    <div id="viewDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-bold flex items-center">
                    <i class="fas fa-envelope-open-text mr-2"></i>Notice Details
                </h2>
                <button onclick="closeModal('viewDetailsModal')" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="modalContent" class="px-6 py-4">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Update Stage Modal -->
    <div id="updateStageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-bold flex items-center">
                    <i class="fas fa-comment mr-2"></i>Add Remark
                </h2>
                <button onclick="closeModal('updateStageModal')" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <form id="updateStageForm">
                    <input type="hidden" id="noticeIdInput" name="notice_id">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Remark <span class="text-red-500">*</span></label>
                        <textarea name="remark" required rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter remark..."></textarea>
                    </div>

                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeModal('updateStageModal')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition">
                            <i class="fas fa-save mr-2"></i>Save Remark
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Close Notice Modal -->
    <div id="closeNoticeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-bold flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>Close Notice
                </h2>
                <button onclick="closeModal('closeNoticeModal')" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <form id="closeNoticeForm">
                    <input type="hidden" id="closeNoticeIdInput" name="notice_id">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Close Remark <span class="text-red-500">*</span></label>
                        <textarea name="close_remark" required rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Enter reason for closing..."></textarea>
                    </div>

                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeModal('closeNoticeModal')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-lg hover:from-orange-600 hover:to-red-700 transition">
                            <i class="fas fa-check mr-2"></i>Close
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="./assets/script.js"></script>
    <script>
    var baseUrl = window.location.href.substring(0, window.location.href.lastIndexOf("/") + 1);
    var table = null;

    window.viewNoticeDetails = function(noticeId) {
        var modal = document.getElementById("viewDetailsModal");
        var modalContent = document.getElementById("modalContent");
        modalContent.innerHTML = '<p class="text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</p>';
        modal.classList.remove("hidden");

        fetch(baseUrl + "get-notice-details.php?id=" + noticeId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var notice = data.notice;
                    var remarks = data.remarks || [];
                    var html = '<div class="space-y-4"><div><p class="text-sm text-gray-600">Notice ID</p><p class="font-mono text-blue-600 font-semibold">' + notice.unique_notice_id + '</p></div>';
                    html += '<div><p class="text-sm text-gray-600">Client</p><p class="font-semibold text-gray-800">' + notice.client_name + '</p></div>';
                    html += '<div><p class="text-sm text-gray-600">Addressee</p><p class="text-gray-800 whitespace-pre-wrap">' + (notice.addressee || "N/A") + '</p></div>';
                    
                    var daysLeft = Math.ceil((new Date(notice.due_date) - new Date()) / (1000 * 60 * 60 * 24));
                    var isOverdue = daysLeft < 0;
                    var daysText = isOverdue ? "Overdue by " + Math.abs(daysLeft) + " days" : daysLeft + " days";
                    var daysClass = isOverdue ? "text-red-600" : "text-blue-600";
                    
                    html += '<div class="grid grid-cols-2 gap-4"><div><p class="text-sm text-gray-600">Due Date</p><p class="font-semibold text-gray-800">' + new Date(notice.due_date).toLocaleDateString("en-US", {year:"numeric",month:"short",day:"numeric"}) + '</p></div>';
                    html += '<div><p class="text-sm text-gray-600">Days Left</p><p class="font-semibold ' + daysClass + '">' + daysText + '</p></div></div>';
                    
                    html += '<div><p class="text-sm text-gray-600">Status</p><p class="font-semibold text-gray-800">' + (notice.status === "closed" ? "Closed" : "Open") + '</p></div>';
                    
                    html += '<div class="border-t border-gray-300 pt-4"><h3 class="text-lg font-bold text-gray-800 mb-3">Remarks History</h3><div class="space-y-3">';
                    if (remarks.length > 0) {
                        remarks.forEach(function(remark) {
                            html += '<div class="border border-gray-300 rounded-lg p-3 bg-gray-50"><div class="flex items-center justify-between mb-2"><span class="font-semibold text-blue-600">' + (remark.created_by || "User") + '</span><span class="text-xs text-gray-500">' + new Date(remark.created_at).toLocaleDateString("en-US", {year:"numeric",month:"short",day:"numeric"}) + '</span></div><p class="text-sm text-gray-700">' + remark.remark + '</p></div>';
                        });
                    } else {
                        html += '<p class="text-sm text-gray-500">No remarks yet</p>';
                    }
                    html += '</div></div></div>';
                    modalContent.innerHTML = html;
                } else {
                    modalContent.innerHTML = '<p class="text-red-600">Error loading notice details</p>';
                }
            })
            .catch(function(e) { modalContent.innerHTML = '<p class="text-red-600">Error loading notice details</p>'; });
    };

    window.addRemark = function(id) {
        document.getElementById("noticeIdInput").value = id;
        document.getElementById("updateStageModal").classList.remove("hidden");
    };

    window.closeNotice = function(id) {
        document.getElementById("closeNoticeIdInput").value = id;
        document.getElementById("closeNoticeModal").classList.remove("hidden");
    };

    window.closeModal = function(id) {
        document.getElementById(id).classList.add("hidden");
    };

    window.deleteNotice = function(id) {
        if (confirm("Are you sure?")) {
            fetch(baseUrl + "delete-notice.php", {method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:"notice_id=" + id})
                .then(function(r) { return r.json(); })
                .then(function(d) { if (d.success) { alert("Deleted"); location.reload(); } else { alert("Error: " + d.message); } })
                .catch(function(e) { alert("Error"); });
        }
    };

    window.clearFilters = function() {
        document.getElementById("searchInput").value = "";
        document.getElementById("statusFilter").value = "";
        document.getElementById("recordsPerPage").value = "10";
        if (table) { table.search("").page.len(10).draw(); }
    };

    window.printNotices = function() {
        window.open(baseUrl + "print-notices.php", "_blank", "width=1200,height=800");
    };

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof jQuery !== "undefined") {
            jQuery(document).ready(function($) {
                table = $("#noticesTable").DataTable({pageLength:10, lengthMenu:[[10,25,50,100,-1],[10,25,50,100,"All"]], paging:true, searching:true, ordering:true, info:true, autoWidth:false});
                $("#recordsPerPage").on("change", function() { var v = jQuery(this).val(); table.page.len(v == -1 ? -1 : parseInt(v)).draw(); });
                $("#statusFilter").on("change", function() { 
                    var s = document.getElementById("statusFilter").value;
                    $.fn.dataTable.ext.search.pop();
                    if (s) {
                        $.fn.dataTable.ext.search.push(function(st, d, i) {
                            var row = table.row(i).node();
                            return jQuery(row).data("status") === s;
                        });
                    }
                    table.draw();
                });
                $("#searchInput").on("keyup", function() { table.search(jQuery(this).val()).draw(); });
            });
        }

        document.getElementById("updateStageForm").addEventListener("submit", function(e) {
            e.preventDefault();
            fetch(baseUrl + "add-notice-remark.php", {method:"POST", body:new FormData(this)})
                .then(function(r) { return r.json(); })
                .then(function(d) { if (d.success) { alert("Added"); closeModal("updateStageModal"); location.reload(); } else { alert("Error"); } })
                .catch(function(e) { alert("Error"); });
        });

        document.getElementById("closeNoticeForm").addEventListener("submit", function(e) {
            e.preventDefault();
            fetch(baseUrl + "close-notice.php", {method:"POST", body:new FormData(this)})
                .then(function(r) { return r.json(); })
                .then(function(d) { if (d.success) { alert("Closed"); closeModal("closeNoticeModal"); location.reload(); } else { alert("Error"); } })
                .catch(function(e) { alert("Error"); });
        });
    });
    </script>
</body>

</html>