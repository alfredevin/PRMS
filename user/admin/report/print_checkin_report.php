<?php
include '../../../config.php';

// Get Filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Fetch Data
$sql = "SELECT r.*, rm.room_name, rt.room_type_name
        FROM reservation_tbl r
        JOIN rooms_tbl rm ON r.room_id = rm.room_id
        JOIN room_type_tbl rt ON rm.room_type_id = rt.room_type_id
        WHERE DATE(r.check_in) BETWEEN '$from_date' AND '$to_date' 
        AND r.status IN (3, 4) 
        ORDER BY r.check_in ASC";
$result = mysqli_query($conn, $sql);

$current_date = date('F d, Y');
$total_guests = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Manifest Report</title>
    <style>
        @media print {
            @page {
                margin: 10mm;
                size: A4 landscape;
                /* Landscape usually better for guest lists */
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
            margin: 0 auto;
            padding: 10px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px double #36b9cc;
            /* Info Blue */
        }

        .header img {
            width: 70px;
            height: auto;
            margin-bottom: 5px;
        }

        .header h2 {
            margin: 0;
            color: #36b9cc;
            text-transform: uppercase;
            font-size: 16pt;
        }

        .header p {
            margin: 2px 0;
            font-size: 9pt;
        }

        /* Report Details */
        .report-info {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            background-color: #f8f9fc;
            padding: 10px;
            border: 1px solid #e3e6f0;
            border-radius: 5px;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #36b9cc;
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

        /* Signatures */
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .sig-box {
            width: 30%;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        .print-btn {
            background-color: #36b9cc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 10pt;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .print-btn:hover {
            background-color: #2c9faf;
        }
    </style>
</head>

<body>

    <div class="container">
        <div style="text-align: center;" class="no-print">
            <button onclick="window.print()" class="print-btn">🖨️ Print Official Guest Manifest</button>
        </div>

        <div class="header">
            <img src="../../uploads/solo_logo.jpg" alt="Resort Logo" onerror="this.style.display='none'"><br>
            <h2>BEACHFRONT RESORT</h2>
            <p>Polo, Santa Cruz Marinduque</p>
            <p><strong>OFFICIAL GUEST MANIFEST REPORT</strong></p>
        </div>

        <div class="report-info">
            <div>
                <strong>Report Date:</strong> <?= $current_date ?><br>
                <strong>Submited To:</strong> Tourism Department / LGU
            </div>
            <div style="text-align: right;">
                <strong>Period Covered:</strong><br>
                <?= date("F d, Y", strtotime($from_date)) ?> — <?= date("F d, Y", strtotime($to_date)) ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="12%">Check-In</th>
                    <th width="12%">Check-Out</th>
                    <th width="25%">Guest Name</th>
                    <th width="10%">Age/Sex</th>
                    <th width="15%">Origin/Address</th>
                    <th width="15%">Room No.</th>
                    <th width="5%" class="text-center">Pax</th>
                    <th width="6%" class="text-center">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $total_guests += $row['guests'];
                        $checkIn = date("m/d/Y", strtotime($row['check_in']));
                        $checkOut = date("m/d/Y", strtotime($row['check_out']));

                        // Placeholder for data not in main table, usually required by Tourism
                        $origin = "N/A";
                        $demographics = "N/A";
                        $status = ($row['status'] == 4) ? "Out" : "In-House";

                        echo "<tr>
                                <td>{$checkIn}</td>
                                <td>{$checkOut}</td>
                                <td style='font-weight:bold;'>{$row['guest_name']}</td>
                                <td>{$demographics}</td>
                                <td>{$origin}</td>
                                <td>{$row['room_name']}</td>
                                <td class='text-center'>{$row['guests']}</td>
                                <td class='text-center'>{$status}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center; padding: 20px; font-style:italic;'>No guest arrivals recorded for this period.</td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right" style="font-weight:bold; background-color:#e6fcff;">TOTAL NUMBER OF GUESTS:</td>
                    <td class="text-center" style="font-weight:bold; background-color:#e6fcff; font-size:11pt;"><?= $total_guests ?></td>
                    <td style="background-color:#e6fcff;"></td>
                </tr>
            </tfoot>
        </table>

        <br>
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Prepared By</strong><br>
                <span style="font-size:9pt;">Resort Administrator</span>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Noted By</strong><br>
                <span style="font-size:9pt;">Tourism Officer</span>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Approved By</strong><br>
                <span style="font-size:9pt;">Resort Manager</span>
            </div>
        </div>

        <div class="footer">
            <p>This report serves as an official record for Tourism Statistics and LGU compliance.</p>
            <p>System Generated Report - BeachFront Resort Management System</p>
        </div>
    </div>

</body>

</html>