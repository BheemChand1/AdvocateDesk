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
    <title>Print Notices - Case Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: white;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background-color: #3b82f6;
            color: white;
        }

        th {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
            font-size: 13px;
        }

        td {
            padding: 10px 12px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tbody tr:hover {
            background-color: #f3f4f6;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }

        .status-open {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-closed {
            background-color: #e5e7eb;
            color: #1f2937;
        }

        .days-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }

        .days-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .days-left {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .notice-id {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #1e40af;
        }

        .section-info {
            font-size: 11px;
        }

        .section-info .act {
            color: #666;
            font-size: 10px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .print-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .print-button:hover {
            background-color: #2563eb;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-button {
                display: none;
            }

            .header {
                page-break-after: avoid;
            }

            table {
                page-break-inside: avoid;
            }

            tbody tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="print-button" onclick="window.print()"><i>🖨️</i> Print This Page</button>

        <div class="header">
            <h1>Notices Report</h1>
            <p>Generated on <?php echo date('d M, Y \a\t H:i A'); ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Notice ID</th>
                    <th style="width: 12%;">Client Name</th>
                    <th style="width: 12%;">Addressee</th>
                    <th style="width: 10%;">Notice Date</th>
                    <th style="width: 12%;">Section / Act</th>
                    <th style="width: 10%;">Due Date</th>
                    <th style="width: 12%;">Days Left</th>
                    <th style="width: 12%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all notices sorted by due_date
                $query = "SELECT n.id, n.unique_notice_id, n.client_id, c.name as client_name, 
                         n.notice_date, n.due_date, n.section, n.act, n.status, n.closed_date, n.addressee
                         FROM notices n
                         LEFT JOIN clients c ON n.client_id = c.client_id
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

                        // Calculate days left
                        $due_date_obj = new DateTime($row['due_date']);
                        $today = new DateTime('today');
                        $days_left = $today->diff($due_date_obj)->days;
                        $is_overdue = $today > $due_date_obj;

                        if ($is_overdue) {
                            $days_text = "Overdue by {$days_left} days";
                            $days_class = "days-overdue";
                        } else {
                            $days_text = "{$days_left} days left";
                            $days_class = "days-left";
                        }

                        $status_class = ($status === 'open') ? 'status-open' : 'status-closed';
                        $status_text = ucfirst($status);

                        echo "
                        <tr>
                            <td><span class='notice-id'>$unique_notice_id</span></td>
                            <td>$client_name</td>
                            <td>$addressee</td>
                            <td>$notice_date</td>
                            <td>
                                <div class='section-info'>
                                    <div><strong>$section</strong></div>
                                    <div class='act'>$act</div>
                                </div>
                            </td>
                            <td><strong>$due_date</strong></td>
                            <td><span class='days-badge $days_class'>$days_text</span></td>
                            <td><span class='status-badge $status_class'>$status_text</span></td>
                        </tr>
                        ";
                    }
                } else {
                    echo "
                    <tr>
                        <td colspan='8' style='text-align: center; padding: 20px;'>
                            No notices found
                        </td>
                    </tr>
                    ";
                }
                ?>
            </tbody>
        </table>

        <div class="footer">
            <p>© <?php echo date('Y'); ?> Case Management System. All rights reserved.</p>
            <p>This is a confidential document. Unauthorized access is prohibited.</p>
        </div>
    </div>
</body>
</html>
