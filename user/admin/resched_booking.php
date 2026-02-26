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
                            <h6 class="m-0 font-weight-bold text-primary">List of Reservation for Reschedule </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Tracking Number</th>
                                            <th>Full Name</th>
                                            <th>Old Check-In</th>
                                            <th>Old Check-Out</th>
                                            <th>New Check-In</th>
                                            <th>New Check-Out</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $counter = 1;

                                        // Join reservation_tbl and reschedule_tbl by tracking_number
                                        $sql = "SELECT 
                                                    r.tracking_number,
                                                    r.guest_name,
                                                    r.guest_email,
                                                    r.guest_phone,
                                                    r.check_in,
                                                    r.check_out,
                                                    r.created_at,
                                                    re.new_check_in,
                                                    re.new_check_out
                                                FROM reservation_tbl r
                                                LEFT JOIN resched_tbl re 
                                                    ON r.tracking_number = re.tracking_number
                                                WHERE r.status = 8";

                                        $result = mysqli_query($conn, $sql);

                                        while ($res = mysqli_fetch_assoc($result)) {
                                            $oldCheckIn = date("M d, Y", strtotime($res['check_in']));
                                            $oldCheckOut = date("M d, Y", strtotime($res['check_out']));
                                            $newCheckIn = $res['new_check_in'] ? date("M d, Y", strtotime($res['new_check_in'])) : '<span class="text-muted">—</span>';
                                            $newCheckOut = $res['new_check_out'] ? date("M d, Y", strtotime($res['new_check_out'])) : '<span class="text-muted">—</span>';
                                            $createdAt = date("M d, Y h:i A", strtotime($res['created_at']));
                                        ?>
                                            <tr class="text-center">
                                                <td><?= $counter++; ?></td>
                                                <td><?= $res['tracking_number'] ?></td>
                                                <td><?= $res['guest_name'] ?></td>
                                                <td><span class="text-muted"><?= $oldCheckIn ?></span></td>
                                                <td><span class="text-muted"><?= $oldCheckOut ?></span></td>
                                                <td><span class="text-success font-weight-bold"><?= $newCheckIn ?></span></td>
                                                <td><span class="text-success font-weight-bold"><?= $newCheckOut ?></span></td>
                                                <td>
                                                    <a href="view_reservation?tracking=<?= $res['tracking_number'] ?>" class="btn btn-primary btn-sm">VIEW DETAILS</a>
                                                    <a href="#"
                                                        class="btn btn-success btn-sm confirm-resched-btn"
                                                        data-tracking="<?= $res['tracking_number'] ?>">
                                                        Confirm Reschedule
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
    <script>
        $(document).ready(function() {
            $('.confirm-resched-btn').on('click', function(e) {
                e.preventDefault();
                const trackingNumber = $(this).data('tracking');

                Swal.fire({
                    title: 'Confirm Reschedule?',
                    text: 'This action will update the reservation and reschedule status.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, confirm it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#28a745',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'confirm_reschedule.php',
                            type: 'POST',
                            data: {
                                tracking: trackingNumber
                            },
                            success: function(response) {
                                if (response.trim() === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Reschedule Confirmed!',
                                        text: 'The reservation and reschedule records have been updated.',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Something went wrong while updating the record.'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to connect to the server.'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>


</body>

</html>