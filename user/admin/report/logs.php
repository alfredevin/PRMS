<?php
include '../../../config.php';
?>


<!DOCTYPE html>
<html>

<head>
    <title>Customer Logs</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            margin: 50px;
        }

        .report-header h2 {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .report-header h3 {
            font-size: 16px;
            margin: 0;
        }

        .report-header h4 {
            font-size: 14px;
            margin: 0;
        }

        .report-header p {
            font-size: 13px;
            margin: 5px 0;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
            font-size: 6px;
        }

        .content {
            text-align: justify;
            font-size: 18px;
            line-height: 1.8;
        }

        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }

        .signatures div {
            width: 40%;
            text-align: center;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
        }

        @media print {
            .noprint {
                display: none;
            }
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: 0;
            pointer-events: none;
        }

        .watermark img {
            width: 700px;
        }
    </style>
</head>

<body>
    <div class="watermark">
        <img src="https://cdn-icons-gif.flaticon.com/8986/8986685.gif" alt="Watermark">
    </div>



    <div class="report-header">
        <table width="100%">
            <tr>
                <td style="text-align: center;">
                    <img src="../uploads/solo_logo.jpg" alt="" width="80">

                    <h2 style="margin: 0;text-transform:uppercase;letter-spacing:1px;">BeachFront Resort</h2>
                    <h4 style="margin: 0;letter-spacing:5px;">Polo, Santa Cruz</h4>
                    <h3 style="margin: 0;letter-spacing:5px;">Province of Marinduque</h3>
                </td>
            </tr>
        </table>

        <hr style="margin: 15px 0; border-top: 2px solid #000;">

    </div>
    <h4 style="text-align: center;text-transform:uppercase;font-weight:bolder;letter-spacing:1px;font-family:'Times New Roman', Times, serif;">Guests Logs </h4>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th></th>
                        <th>Tracking Number</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    $sql = "SELECT * FROM
                                        customer_logs_tbl 
                                        INNER JOIN reservation_tbl ON reservation_tbl.reservation_id = customer_logs_tbl.reservation_id
                                           WHERE status = 4";
                    $result = mysqli_query($conn, $sql);
                    while ($res = mysqli_fetch_assoc($result)) {
                        $login = date("M d, Y H:i:s", strtotime($res['login']));
                        $logout = date("M d, Y H:i:s", strtotime($res['logout']));
                        $createdAt = date("M d, Y h:i A", strtotime($res['created_at']));
                    ?>
                        <tr>
                            <td><?= $counter++; ?></td>
                            <td><?= $res['tracking_number'] ?></td>
                            <td><?= $res['guest_name'] ?></td>
                            <td><?= $res['guest_email'] ?></td>
                            <td><?= $res['guest_phone'] ?></td>
                            <td><?= $login ?></td>
                            <td><?= $logout ?></td>
                        </tr>
                    <?php }  ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        window.print()
    </script>
</body>

</html>