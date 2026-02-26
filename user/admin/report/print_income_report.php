<?php
include '../../../config.php';

// --- UTILITY FUNCTIONS ---
function formatCurrency($amount)
{
    return '₱' . number_format($amount, 2);
}

// Get Filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

// Fetch Data
$sql = "SELECT p.*, pt.payment_type_name, r.guest_name 
        FROM reservation_payments_tbl p
        LEFT JOIN payment_type_tbl pt ON p.payment_type = pt.payment_type_id
        LEFT JOIN reservation_tbl r ON p.tracking_number = r.tracking_number
        WHERE DATE(p.created_at) BETWEEN '$from_date' AND '$to_date'
        ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $sql);

$current_date = date('F d, Y');
$total_income = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Income Report</title>
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
            font-size: 11pt;
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
            border-bottom: 3px double #17a673;
        }

        .header img {
            width: 80px;
            height: auto;
            margin-bottom: 5px;
        }

        .header h2 {
            margin: 0;
            color: #17a673;
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
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #17a673;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Total Row */
        .total-row td {
            background-color: #e8f5e9;
            font-weight: bold;
            font-size: 12pt;
            border-top: 2px solid #17a673;
            color: #000;
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
            font-size: 9pt;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .print-btn {
            background-color: #17a673;
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
            background-color: #138a5f;
        }
    </style>
</head>

<body>

    <div class="container">
        <div style="text-align: center;" class="no-print">
            <button onclick="window.print()" class="print-btn">🖨️ Print Official Income Report</button>
        </div>

        <div class="header">
            <img src="../../uploads/solo_logo.jpg" alt="Resort Logo" onerror="this.style.display='none'"><br>
            <h2>BEACHFRONT RESORT</h2>
            <p>Polo, Santa Cruz Marinduque</p>
            <p><strong>OFFICIAL INCOME STATEMENT</strong></p>
        </div>

        <div class="report-info">
            <div>
                <strong>Report Date:</strong> <?= $current_date ?><br>
                <strong>Generated By:</strong> Administrator
            </div>
            <div style="text-align: right;">
                <strong>Period Covered:</strong><br>
                <?= date("F d, Y", strtotime($from_date)) ?> — <?= date("F d, Y", strtotime($to_date)) ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="15%">Date</th>
                    <th width="25%">Guest Name</th>
                    <th width="20%">Tracking / Ref</th>
                    <th width="15%">Method</th>
                    <th width="15%" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $total_income += $row['amount'];
                        $date = date("M d, Y", strtotime($row['created_at']));
                        $guest = !empty($row['guest_name']) ? $row['guest_name'] : 'Walk-in / N/A';

                        echo "<tr>
                                <td>{$date}</td>
                                <td style='font-weight:500;'>{$guest}</td>
                                <td>
                                    <div style='font-size:9pt; color:#555;'>TR: {$row['tracking_number']}</div>
                                    <div style='font-size:9pt; color:#555;'>RF: {$row['reference_number']}</div>
                                </td>
                                <td>{$row['payment_type_name']}</td>
                                <td class='text-right'>₱" . number_format($row['amount'], 2) . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color:#777; font-style:italic;'>No income records found for the selected dates.</td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-right">TOTAL INCOME GENERATED:</td>
                    <td class="text-right">₱<?= number_format($total_income, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Prepared By</strong><br>
                <span style="font-size:10pt;">(Signature over Printed Name)</span>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Approved By</strong><br>
                <span style="font-size:10pt;">(Manager / Owner)</span>
            </div>
        </div>

        <div class="footer">
            <p>System Generated Report - BeachFront Resort Management System</p>
        </div>
    </div>

</body>

</html>