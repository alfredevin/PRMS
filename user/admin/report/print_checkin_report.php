<?php
include '../../../config.php';

// 1. GET FILTERS
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// 2. FETCH DATA
$sql = "SELECT r.*, rm.room_name, rt.room_type_name
        FROM reservation_tbl r
        JOIN rooms_tbl rm ON r.room_id = rm.room_id
        JOIN room_type_tbl rt ON rm.room_type_id = rt.room_type_id
        WHERE DATE(r.check_in) BETWEEN '$from_date' AND '$to_date' 
        AND r.status IN (3, 4) 
        ORDER BY r.check_in ASC";
$result = mysqli_query($conn, $sql);

$current_date = date('F d, Y');

// Stats counters initialization
$total_pax = 0;
$total_males = 0;
$total_females = 0;
$total_local = 0;
$total_foreign = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Official Guest Manifest - Tourism Report</title>
    <style>
        @media print {
            @page {
                margin: 10mm;
                size: A4 landscape;
            }

            body {
                margin: 0;
                padding: 0;
                background-color: white;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* --- COMPACT OFFICIAL HEADER --- */
        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            /* Pinaglalapit ang mga elements */
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            gap: 30px;
            /* Distansya ng logo sa text */
        }

        .header-logo {
            width: 90px;
            height: auto;
        }

        .header-text {
            text-align: center;
            line-height: 1.2;
        }

        .header-text h5 {
            margin: 0;
            font-size: 10pt;
            font-weight: normal;
            text-transform: uppercase;
        }

        .header-text h4 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
        }

        .header-text h2 {
            margin: 5px 0;
            font-size: 18pt;
            color: #1a5276;
            font-weight: 800;
        }

        .header-text p {
            margin: 0;
            font-size: 9pt;
            color: #555;
        }

        /* Stats Section */
        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }

        .stat-card {
            flex: 1;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            background: #fcfcfc;
        }

        .stat-card h3 {
            margin: 0;
            color: #d9534f;
            font-size: 14pt;
        }

        .stat-card small {
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            font-size: 7.5pt;
        }

        /* Table Style */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f2f2f2;
            border: 1px solid #333;
            padding: 10px;
            font-size: 8pt;
            text-transform: uppercase;
            color: #333;
        }

        td {
            border: 1px solid #333;
            padding: 8px;
            font-size: 9pt;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
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
        }

        .sig-line {
            border-top: 1.5px solid #000;
            margin-top: 40px;
            margin-bottom: 5px;
        }

        .print-btn {
            background-color: #1a5276;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        .print-btn:hover {
            background-color: #154360;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print()" class="print-btn">🖨️ PRINT OFFICIAL MANIFEST</button>
        </div>

        <div class="header-container">
            <img src="https://www.caap.gov.ph/wp-content/uploads/2023/09/Bagong-Pilipinas-logo.jpg" class="header-logo"
                alt="LGU Logo">

            <div class="header-text">
                <h5>Republic of the Philippines</h5>
                <h4>Province of Marinduque</h4>
                <h4>Municipality of Santa Cruz</h4>
                <h2>BEACHFRONT RESORT</h2>
                <p>Brgy. Polo, Santa Cruz, Marinduque | Phone: 0912-345-6789</p>
                <p style="font-weight: bold; text-decoration: underline; margin-top: 10px;">GUEST MANIFEST & ARRIVAL
                    REPORT</p>
            </div>

            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTPykbqxRcYVOaHJdCDE7_AMuStfLWfA5FznA&s"
                class="header-logo" alt="Resort Logo">
        </div>

        <div style="margin-bottom: 15px; font-size: 9pt;">
            <strong>Generated:</strong> <?= $current_date ?> |
            <strong>Covered Period:</strong> <?= date("M d, Y", strtotime($from_date)) ?> to
            <?= date("M d, Y", strtotime($to_date)) ?>
        </div>

        <div class="stats-row">
            <div class="stat-card"><small>Total Pax</small>
                <h3 id="s_pax">0</h3>
            </div>
            <div class="stat-card"><small>Males</small>
                <h3 id="s_m">0</h3>
            </div>
            <div class="stat-card"><small>Females</small>
                <h3 id="s_f">0</h3>
            </div>
            <div class="stat-card"><small>Local Tourists</small>
                <h3 id="s_l">0</h3>
            </div>
            <div class="stat-card"><small>Foreign Tourists</small>
                <h3 id="s_fr">0</h3>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Arrival</th>
                    <th>Guest Name</th>
                    <th>Demographics</th>
                    <th>Type</th>
                    <th>Room</th>
                    <th>Pax</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = 1;
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $total_pax += $row['guests'];
                        $total_males += $row['total_male'];
                        $total_females += $row['total_female'];

                        $t_type = $row['tourist_type'] ?? 'Local';
                        if ($t_type == 'Local')
                            $total_local++;
                        else
                            $total_foreign++;
                        ?>
                        <tr class="text-center">
                            <td><?= $count++ ?></td>
                            <td><?= date("m/d/Y", strtotime($row['check_in'])) ?></td>
                            <td class="text-left font-bold" style="text-align: left;"><?= strtoupper($row['guest_name']) ?></td>
                            <td>M: <?= $row['total_male'] ?> | F: <?= $row['total_female'] ?></td>
                            <td><?= $t_type ?></td>
                            <td><?= $row['room_name'] ?></td>
                            <td class="font-bold"><?= $row['guests'] ?></td>
                            <td><small><?= ($row['status'] == 4) ? 'Checked-Out' : 'Active' ?></small></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center py-4'>No records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Prepared By:</strong><br><small>Front Desk / Staff</small>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Noted By:</strong><br><small>Municipal Tourism Officer</small>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Approved By:</strong><br><small>Resort Manager / Owner</small>
            </div>
        </div>

        <div
            style="margin-top: 30px; text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #eee; padding-top: 10px;">
            This report is a system-generated document for Beachfront Resort Tourism Compliance.
        </div>
    </div>

    <script>
        document.getElementById('s_pax').innerText = '<?= $total_pax ?>';
        document.getElementById('s_m').innerText = '<?= $total_males ?>';
        document.getElementById('s_f').innerText = '<?= $total_females ?>';
        document.getElementById('s_l').innerText = '<?= $total_local ?> Groups';
        document.getElementById('s_fr').innerText = '<?= $total_foreign ?> Groups';
    </script>

</body>

</html>