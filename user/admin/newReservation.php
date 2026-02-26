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
                <div class="container-fluid  ">

                    <div class="card shadow mb-4   ml-2">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">List of New Reservation </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Tracking Number</th>
                                            <th>Full Name</th> 
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $counter = 1;
                                        $sql = "SELECT * FROM reservation_tbl WHERE status = 1";
                                        $result = mysqli_query($conn, $sql);
                                        while ($res = mysqli_fetch_assoc($result)) {
                                            $checkIn = date("M d, Y", strtotime($res['check_in']));
                                            $checkOut = date("M d, Y", strtotime($res['check_out']));
                                            $createdAt = date("M d, Y h:i A", strtotime($res['created_at']));
                                        ?>
                                            <tr>
                                                <td><?= $counter++; ?></td>
                                                <td><?= $res['tracking_number'] ?></td>
                                                <td><?= $res['guest_name'] ?></td>
                                                <td><?= $checkIn ?></td>
                                                <td><?= $checkOut ?></td>
                                                <td>
                                                    <a href="view_reservation?tracking=<?= $res['tracking_number'] ?>" class="btn btn-primary btn-sm">VIEW DETAILS</a>
                                                </td>
                                            </tr>
                                        <?php }  ?>
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