<?php
include '../../../config.php';

// Fetch Data
$sql = "SELECT r.*, t.room_type_name FROM rooms_tbl r 
        LEFT JOIN room_type_tbl t ON r.room_type_id = t.room_type_id
        ORDER BY t.room_type_name ASC, r.room_name ASC";
$result = mysqli_query($conn, $sql);

$current_date = date('F d, Y');

// Stats Variables
$total_rooms_count = 0;
$total_capacity = 0;
$total_available = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Inventory Report</title>
    <style>
        @media print {
            @page {
                margin: 10mm;
                size: A4 portrait;
            }

            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            color: #333;
            background-color: white;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px double #4e73df;
            /* Primary Blue */
        }

        .header img {
            width: 80px;
            height: auto;
            margin-bottom: 5px;
        }

        .header h2 {
            margin: 0;
            color: #4e73df;
            text-transform: uppercase;
            font-size: 18pt;
        }

        .header p {
            margin: 2px 0;
            font-size: 10pt;
        }

        /* Report Details */
        .report-info {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            background-color: #f8f9fc;
            padding: 15px;
            border: 1px solid #e3e6f0;
            border-radius: 5px;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background-color: #4e73df;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* Summary Box */
        .summary-box {
            margin-top: 20px;
            border: 1px solid #4e73df;
            padding: 15px;
            width: 50%;
            margin-left: auto;
            /* Align right */
            border-radius: 5px;
        }

        /* Signatures */
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .sig-box {
            width: 40%;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 8pt;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .print-btn {
            background-color: #4e73df;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11pt;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .print-btn:hover {
            background-color: #2e59d9;
        }
    </style>
</head>

<body>

    <div class="container">
        <div style="text-align: center;" class="no-print">
            <button onclick="window.print()" class="print-btn">🖨️ Print Official Inventory Report</button>
        </div>

        <div class="header">
            <img src="../../uploads/solo_logo.jpg" alt="Resort Logo" onerror="this.style.display='none'"><br>
            <h2>BEACHFRONT RESORT</h2>
            <p>Polo, Santa Cruz Marinduque</p>
            <p><strong>OFFICIAL ROOM INVENTORY & STATUS REPORT</strong></p>
        </div>

        <div class="report-info">
            <div>
                <strong>Report Date:</strong> <?= $current_date ?><br>
                <strong>Report Type:</strong> Accommodation Inventory
            </div>
            <div style="text-align: right;">
                <strong>Department:</strong> Housekeeping / Front Office
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="25%">Room Name</th>
                    <th width="20%">Type</th>
                    <th width="15%">Capacity (Pax)</th>
                    <th width="15%">Rate (PHP)</th>
                    <th width="10%" class="text-center">Total</th>
                    <th width="15%" class="text-center">Available</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Aggregate Stats
                        $total_rooms_count += $row['quantity'];
                        $total_capacity += ($row['quantity'] * $row['max_guest']);
                        $total_available += $row['available'];

                        echo "<tr>
                                <td style='font-weight:bold;'>{$row['room_name']}</td>
                                <td>{$row['room_type_name']}</td>
                                <td>{$row['max_guest']}</td>
                                <td>₱" . number_format($row['price'], 2) . "</td>
                                <td class='text-center'>{$row['quantity']}</td>
                                <td class='text-center' style='font-weight:bold;'>" . ($row['available'] == 0 ? 'FULL' : $row['available']) . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>No rooms listed in inventory.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="summary-box">
            <h4 style="margin-top:0; margin-bottom:10px; text-align:center; color:#4e73df;">Inventory Summary</h4>
            <table style="width:100%; border:none; margin-bottom:0;">
                <tr>
                    <td style="border:none; padding:5px;">Total Number of Rooms:</td>
                    <td style="border:none; padding:5px; text-align:right; font-weight:bold;"><?= $total_rooms_count ?> Units</td>
                </tr>
                <tr>
                    <td style="border:none; padding:5px;">Total Guest Capacity:</td>
                    <td style="border:none; padding:5px; text-align:right; font-weight:bold;"><?= $total_capacity ?> Pax</td>
                </tr>
                <tr>
                    <td style="border:none; padding:5px;">Currently Available:</td>
                    <td style="border:none; padding:5px; text-align:right; font-weight:bold; color:green;"><?= $total_available ?> Units</td>
                </tr>
            </table>
        </div>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Prepared By</strong><br>
                <span style="font-size:10pt;">Resort Administrator</span>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Verified By</strong><br>
                <span style="font-size:10pt;">General Manager</span>
            </div>
        </div>

        <div class="footer">
            <p>System Generated Report - BeachFront Resort Management System</p>
        </div>
    </div>

</body>

</html>