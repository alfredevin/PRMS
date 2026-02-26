<?php
include '../../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Reservation History <span class="badge badge-success"><i class="fas fa-history"></i> Completed Bookings</span></h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Completed Reservation List (Status 4)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <!-- Inalis ang '#' column para maging malinis -->
                                            <th>Tracking Number</th>
                                            <th>Guest Name</th>
                                            <th>Check In Date</th>
                                            <th>Check Out Date</th>
                                            <th>Final Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT r.*, rm.room_name FROM reservation_tbl r
                                                JOIN rooms_tbl rm ON r.room_id = rm.room_id
                                                WHERE r.status = 4 
                                                ORDER BY r.check_out DESC"; // Sort by newest checkout date
                                        $result = mysqli_query($conn, $sql);
                                        while ($res = mysqli_fetch_assoc($result)) {
                                            $checkIn = date("M d, Y", strtotime($res['check_in']));
                                            $checkOut = date("M d, Y", strtotime($res['check_out']));
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($res['tracking_number']) ?></td>
                                                <td><?= htmlspecialchars($res['guest_name']) ?></td>
                                                <td><?= $checkIn ?></td>
                                                <td><?= $checkOut ?></td>
                                                <td>
                                                    <span class="badge badge-primary"><i class="fas fa-check"></i> CHECKED OUT</span>
                                                </td>
                                                <td>
                                                    <a href="view_reservation?tracking=<?= $res['tracking_number'] ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
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
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>

</html>