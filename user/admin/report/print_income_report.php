<?php
include '../../../config.php';

// --- UTILITY FUNCTIONS ---
function formatCurrency($amount)
{
    return '₱' . number_format($amount, 2);
}

// 1. GET FILTERS FROM URL
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

// 2. FETCH SUMMARIZED DAILY DATA + DEMOGRAPHICS (For Tourism standard)
$sql = "SELECT 
            DATE(p.created_at) as trans_date, 
            COUNT(p.payment_id) as trans_count,
            SUM(p.amount) as daily_total_income,
            SUM(r.total_male) as daily_males,
            SUM(r.total_female) as daily_females
        FROM reservation_payments_tbl p
        LEFT JOIN reservation_tbl r ON p.tracking_number = r.tracking_number
        WHERE DATE(p.created_at) BETWEEN '$from_date' AND '$to_date'
        GROUP BY DATE(p.created_at)
        ORDER BY trans_date DESC";

$result = mysqli_query($conn, $sql);

// 3. FETCH TOURIST TYPE SUMMARY (Local vs Foreign)
$tourist_sql = "SELECT tourist_type, COUNT(*) as count 
                FROM reservation_tbl 
                WHERE DATE(check_in) BETWEEN '$from_date' AND '$to_date' 
                GROUP BY tourist_type";
$tourist_res = mysqli_query($conn, $tourist_sql);
$stats = ['Local' => 0, 'Foreign' => 0];
while ($ts = mysqli_fetch_assoc($tourist_res)) {
    $stats[$ts['tourist_type']] = $ts['count'];
}

$current_date = date('F d, Y');
$total_gross_income = 0;
$total_males = 0;
$total_females = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Official Tourism Income Report - Beachfront</title>
    <style>
        @media print {
            @page {
                margin: 10mm;
                size: A4 portrait;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
            color: #222;
            background: #fff;
        }

        .container {
            width: 100%;
            max-width: 850px;
            margin: auto;
            padding: 10px;
        }

        /* Header Section */
        .header {
            text-align: center;
            border-bottom: 2px solid #1cc88a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            color: #1cc88a;
            font-size: 20pt;
        }

        .header p {
            margin: 2px 0;
            color: #555;
        }

        /* Tourism KPI Cards */
        .kpi-wrapper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }

        .kpi-card {
            flex: 1;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            background: #f8f9fc;
        }

        .kpi-card h4 {
            margin: 5px 0;
            font-size: 14pt;
            color: #4e73df;
        }

        .kpi-card small {
            font-weight: bold;
            text-transform: uppercase;
            color: #858796;
            font-size: 7pt;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            padding: 10px;
            font-size: 8pt;
            text-transform: uppercase;
            color: #4e73df;
        }

        td {
            border: 1px solid #e3e6f0;
            padding: 8px;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bg-total {
            background-color: #1cc88a !important;
            color: white !important;
            font-weight: bold;
        }

        /* Signatures */
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .sig-box {
            width: 30%;
            text-align: center;
            font-size: 9pt;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 35px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .print-btn {
            background: #4e73df;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="no-print" style="text-align: right; margin-bottom: 10px;">
            <button onclick="window.print()" class="print-btn">🖨️ Generate Official Printout</button>
        </div>

        <div class="header">
            <h2>BEACHFRONT RESORT</h2>
            <p>Polo, Santa Cruz, Marinduque | Contact: 0912-345-6789</p>
            <p style="font-size: 11pt; font-weight: bold; color: #333; margin-top: 5px;">MONTHLY TOURISM REVENUE &
                STATISTICAL REPORT</p>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 9pt;">
            <div><strong>Date Generated:</strong> <?= $current_date ?></div>
            <div style="text-align: right;"><strong>Period:</strong> <?= date("M d, Y", strtotime($from_date)) ?> -
                <?= date("M d, Y", strtotime($to_date)) ?></div>
        </div>

        <div class="kpi-wrapper">
            <div class="kpi-card">
                <small>Total Revenue</small>
                <h4 id="totalRevDisplay">₱0.00</h4>
            </div>
            <div class="kpi-card">
                <small>Local Tourists</small>
                <h4><?= $stats['Local'] ?></h4>
            </div>
            <div class="kpi-card">
                <small>Foreign Tourists</small>
                <h4><?= $stats['Foreign'] ?></h4>
            </div>
            <div class="kpi-card">
                <small>Avg. Daily Income</small>
                <h4 id="avgIncome">₱0.00</h4>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Daily Transactions</th>
                    <th>Demographics (M/F)</th>
                    <th class="text-right">Daily Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count_days = 0;
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $income = (float) $row['daily_total_income'];
                        $total_gross_income += $income;
                        $total_males += (int) $row['daily_males'];
                        $total_females += (int) $row['daily_females'];
                        $count_days++;

                        echo "<tr>
                            <td style='font-weight:bold;'>" . date("M d, Y", strtotime($row['trans_date'])) . "</td>
                            <td>{$row['trans_count']} Payments</td>
                            <td>
                                <span style='color: #4e73df;'>♂ {$row['daily_males']}</span> | 
                                <span style='color: #e74a3b;'>♀ {$row['daily_females']}</span>
                            </td>
                            <td class='text-right font-weight-bold'>" . formatCurrency($income) . "</td>
                          </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='padding:30px;'>No records found.</td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right bg-total">TOTALS FOR THIS PERIOD:</td>
                    <td class="bg-total">♂ <?= $total_males ?> | ♀ <?= $total_females ?></td>
                    <td class="text-right bg-total" style="font-size: 12pt;"><?= formatCurrency($total_gross_income) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 10px; font-size: 8.5pt; color: #666; font-style: italic;">
            * This summary includes all validated payments and guest demographics recorded within the specified period.
        </div>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line">Prepared By:</div>
                System Administrator / Staff
            </div>
            <div class="sig-box">
                <div class="sig-line">Verified By:</div>
                Operations Manager
            </div>
            <div class="sig-box">
                <div class="sig-line">Approved By:</div>
                Sir Robs / Resort Owner
            </div>
        </div>

        <script>
            document.getElementById('totalRevDisplay').innerText = '<?= formatCurrency($total_gross_income) ?>';
            document.getElementById('avgIncome').innerText = '<?= formatCurrency($total_gross_income / max($count_days, 1)) ?>';
        </script>

        <div
            style="margin-top: 40px; text-align: center; font-size: 8pt; color: #ccc; border-top: 1px solid #eee; padding-top: 5px;">
            Beachfront Resort Management System | Tourism Compliance Report | Confidential
        </div>
    </div>

</body>

</html>