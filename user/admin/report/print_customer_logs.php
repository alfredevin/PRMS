<?php
include '../../../config.php';

// Get Filters from URL
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

// Fetch Data
$sql = "SELECT * FROM customer_logs_tbl 
        WHERE DATE(login) BETWEEN '$from_date' AND '$to_date' 
        ORDER BY login DESC";
$result = mysqli_query($conn, $sql);

$current_date = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Activity Logs Report</title>
    <style>
        @media print {
            @page {
                margin: 10mm;
                /* Set margin for print */
                size: A4;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12pt;
            color: #333;
            background-color: white;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #004085;
            padding-bottom: 10px;
        }

        .header img {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            color: #004085;
            /* Tourism Blue */
            text-transform: uppercase;
            font-size: 18pt;
        }

        .header p {
            margin: 5px 0;
            font-size: 10pt;
        }

        /* Report Info */
        .report-info {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 11pt;
        }

        .report-info strong {
            color: #004085;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 10pt;
        }

        th {
            background-color: #f8f9fc;
            color: #004085;
            font-weight: bold;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9pt;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .print-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print {
            background-color: #004085;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11pt;
            text-decoration: none;
            display: inline-block;
        }

        .btn-print:hover {
            background-color: #002752;
        }
    </style>
</head>

<body> <!-- Auto print removed to allow preview -->

    <div class="container">
        <!-- Print Button (Hidden in Print) -->
        <div class="print-btn-container no-print">
            <button onclick="window.print()" class="btn-print">🖨️ Print this Report</button>
        </div>

        <div class="header">
            <!-- Update Logo Path as needed -->
            <img src="../../uploads/solo_logo.jpg" alt="Resort Logo" onerror="this.style.display='none'">
            <h2>BEACHFRONT RESORT</h2>
            <p>Polo, Santa Cruz Marinduque</p>
            <p>Official Customer Activity Log</p>
        </div>

        <div class="report-info">
            <div>
                <strong>Report Type:</strong> Customer Activity Logs<br>
                <strong>Date Generated:</strong> <?= $current_date ?>
            </div>
            <div style="text-align: right;">
                <strong>From:</strong> <?= date("M d, Y", strtotime($from_date)) ?><br>
                <strong>To:</strong> <?= date("M d, Y", strtotime($to_date)) ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="25%">Reservation / Guest ID</th>
                    <th width="35%">Login Timestamp</th>
                    <th width="35%">Logout Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    $i = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $login = date("F d, Y h:i A", strtotime($row['login']));
                        $logout = $row['logout'] ? date("F d, Y h:i A", strtotime($row['logout'])) : '<span style="color:green; font-style:italic;">Currently Active</span>';

                        echo "<tr>
                                <td style='text-align:center;'>{$i}</td>
                                <td style='font-weight:bold;'>{$row['reservation_id']}</td>
                                <td>{$login}</td>
                                <td>{$logout}</td>
                              </tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center; padding: 20px; color: #777;'>No logs found for the selected date range.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="footer">
            <p>Generated by BeachFront Resort Management System</p>
            <p>This document is for official use only.</p>
        </div>
    </div>

</body>

</html>

<button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i> Print Report</button>

<a href="print_customer_logs.php?from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" target="_blank" class="btn btn-info btn-sm shadow-sm">
    <i class="fas fa-print fa-sm text-white-50"></i> Print Official Report
</a>