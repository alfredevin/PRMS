<?php
include '../../config.php';

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');
?>
<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php'; ?>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">

                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Monthly Income Report</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-success text-white">
                            <h6 class="m-0 font-weight-bold"><i class="fas fa-money-bill-wave me-2"></i> Sales Summary</h6>

                            <!-- Print Button (Target Blank) -->
                            <a href="./report/print_income_report.php?from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" target="_blank" class="btn btn-light btn-sm text-success font-weight-bold shadow-sm">
                                <i class="fas fa-print fa-sm"></i> Print Official Report
                            </a>
                        </div>
                        <div class="card-body">

                            <!-- Filter Form -->
                            <form method="GET" class="mb-4 p-3 bg-light rounded border">
                                <div class="form-row align-items-end">
                                    <div class="col-md-4 mb-2">
                                        <label class="small font-weight-bold text-gray-600">From Date:</label>
                                        <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="small font-weight-bold text-gray-600">To Date:</label>
                                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button type="submit" class="btn btn-success btn-block">
                                            <i class="fas fa-filter fa-sm"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Payment Date</th>
                                            <th>Tracking No.</th>
                                            <th>Reference No.</th>
                                            <th>Payment Method</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total_income = 0;
                                        $sql = "SELECT p.*, pt.payment_type_name 
                                                FROM reservation_payments_tbl p
                                                LEFT JOIN payment_type_tbl pt ON p.payment_type = pt.payment_type_id
                                                WHERE DATE(p.created_at) BETWEEN '$from_date' AND '$to_date'
                                                ORDER BY p.created_at DESC";
                                        $result = mysqli_query($conn, $sql);

                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $total_income += $row['amount'];
                                                $date = date("M d, Y", strtotime($row['created_at']));
                                                echo "<tr>
                                                        <td>{$date}</td>
                                                        <td><span class='badge badge-secondary'>{$row['tracking_number']}</span></td>
                                                        <td>{$row['reference_number']}</td>
                                                        <td>{$row['payment_type_name']}</td>
                                                        <td class='text-right text-dark'>₱" . number_format($row['amount'], 2) . "</td>
                                                      </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center text-muted py-4'>No transaction records found for this date range.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <td colspan="4" class="text-right font-weight-bold text-uppercase">Total Income:</td>
                                            <td class="text-right font-weight-bold text-success h5">₱<?= number_format($total_income, 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include './../template/footer.php'; ?>
        </div>
    </div>
    <?php include './../template/script.php'; ?>
</body>

</html>