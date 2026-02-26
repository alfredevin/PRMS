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
                            <h6 class="m-0 font-weight-bold text-primary">List of For Cancellation Reservation </h6>
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
                                        $sql = "SELECT * FROM reservation_tbl WHERE status = 7";
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
                                                    <button class="btn btn-danger btn-sm cancel-btn" data-id="<?= $res['reservation_id'] ?>">Cancel</button>

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
    <script>
        $(document).on('click', '.cancel-btn', function() {
            let reservationId = $(this).data('id');

            Swal.fire({
                title: 'Cancel Reservation?',
                text: "Are you sure you want to cancel this reservation?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, Cancel it',
                cancelButtonText: 'No, Keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'check_in_status.php',
                        type: 'POST',
                        data: {
                            reservation_id: reservationId,
                            status: 9
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cancelled!',
                                text: 'Reservation has been cancelled successfully.'
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong. Please try again.'
                            });
                        }
                    });
                }
            });
        });
    </script>

</body>

</html>