<?php
include '../../config.php';

// Default Filter: Today's Check-ins if no date is selected
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

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
                        <h1 class="h3 mb-0 text-gray-800">Checked-In Guests Report</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-info text-white">
                            <h6 class="m-0 font-weight-bold"><i class="fas fa-users me-2"></i> Guest Manifest</h6>

                            <!-- Print Button (Target Blank) -->
                            <a href="./report/print_checkin_report.php?from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" target="_blank" class="btn btn-light btn-sm text-info font-weight-bold shadow-sm">
                                <i class="fas fa-print fa-sm"></i> Print Official Report
                            </a>
                        </div>
                        <div class="card-body">

                            <!-- Filter Form -->
                            <form method="GET" class="mb-4 p-3 bg-light rounded border">
                                <div class="form-row align-items-end">
                                    <div class="col-md-4 mb-2">
                                        <label class="small font-weight-bold text-gray-600">Check-In From:</label>
                                        <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="small font-weight-bold text-gray-600">Check-In To:</label>
                                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button type="submit" class="btn btn-info btn-block">
                                            <i class="fas fa-filter fa-sm"></i> Filter Records
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Check-In Date</th>
                                            <th>Guest Name</th>
                                            <th>Contact No.</th>
                                            <th>Room Details</th>
                                            <th>Total Pax</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Filter based on Check-In Date
                                        $sql = "SELECT r.*, rm.room_name, rm.price as room_rate, rt.room_type_name
                                                FROM reservation_tbl r
                                                JOIN rooms_tbl rm ON r.room_id = rm.room_id
                                                JOIN room_type_tbl rt ON rm.room_type_id = rt.room_type_id
                                                WHERE DATE(r.check_in) BETWEEN '$from_date' AND '$to_date' 
                                                AND r.status IN (3, 4) -- Show Checked-In (3) and Completed (4)
                                                ORDER BY r.check_in DESC";

                                        $result = mysqli_query($conn, $sql);

                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $checkIn = date("M d, Y", strtotime($row['check_in']));
                                                $status_badge = ($row['status'] == 3)
                                                    ? '<span class="badge badge-success">Checked-In</span>'
                                                    : '<span class="badge badge-secondary">Checked-Out</span>';

                                                echo "<tr>
                                                        <td>{$checkIn}</td>
                                                        <td class='font-weight-bold'>{$row['guest_name']}</td>
                                                        <td>{$row['guest_phone']}</td>
                                                        <td>
                                                            {$row['room_name']}<br>
                                                            <small class='text-muted'>{$row['room_type_name']}</small>
                                                        </td>
                                                        <td class='text-center'>{$row['guests']}</td>
                                                        <td class='text-center'>{$status_badge}</td>
                                                      </tr>";
                                            }
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