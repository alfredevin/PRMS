<?php
include '../../config.php';

// Default: Petsa ngayon
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

                    <h1 class="h3 mb-4 text-gray-800">Customer Logs Report</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Activity Logs</h6>
                            <a href="./report/print_customer_logs.php?from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" target="_blank" class="btn btn-secondary btn-sm">
                                <i class="fas fa-print"></i> Print Report
                            </a>
                        </div>
                        <div class="card-body">
                            <!-- Filter Form -->
                            <form method="GET" class="form-inline mb-3">
                                <label class="mr-2">From:</label>
                                <input type="date" name="from_date" class="form-control mr-2" value="<?= $from_date ?>">
                                <label class="mr-2">To:</label>
                                <input type="date" name="to_date" class="form-control mr-2" value="<?= $to_date ?>">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Reservation ID</th>
                                            <th>Login Time</th>
                                            <th>Logout Time</th>
                                            <!-- Dagdagan kung may iba pang columns sa customer_logs_tbl -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Siguraduhin na tama ang table name at columns base sa iyong database
                                        $sql = "SELECT * FROM customer_logs_tbl 
                                                WHERE DATE(login) BETWEEN '$from_date' AND '$to_date' 
                                                ORDER BY login DESC";
                                        $result = mysqli_query($conn, $sql);
                                        $i = 1;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $login = date("M d, Y h:i A", strtotime($row['login']));
                                            $logout = $row['logout'] ? date("M d, Y h:i A", strtotime($row['logout'])) : 'Active';
                                            echo "<tr>
                                                    <td>{$i}</td>
                                                    <td>{$row['reservation_id']}</td>
                                                    <td>{$login}</td>
                                                    <td>{$logout}</td>
                                                  </tr>";
                                            $i++;
                                        }
                                        ?>
                                    </tbody>
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