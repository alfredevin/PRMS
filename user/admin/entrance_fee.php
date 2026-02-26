<?php
include '../../config.php';

// Handle Update Amount
if (isset($_POST['update_fee'])) {
    $id = $_POST['fee_id'];
    $amount = $_POST['amount'];

    $update_query = "UPDATE entrance_fee_tbl SET entrance_fee_amount = ? WHERE entrance_fee_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("di", $amount, $id);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: "success",
                    title: "Amount updated successfully!",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            });
        </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: "error",
                    title: "Failed to update amount: ' . $stmt->error . '",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        </script>';
    }
}
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

                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Entrance Fee Management</h1>
                    </div>

                    <div class="row justify-content-center">

                        <div class="col-lg-6 col-md-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-primary text-white">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-peso-sign"></i> Current Entrance Fee</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Fee Description</th>
                                                    <th class="text-center">Current Amount (₱)</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM entrance_fee_tbl";
                                                $result = mysqli_query($conn, $sql);
                                                while ($fee = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td class="text-center font-weight-bold text-dark">General Entrance Fee</td>
                                                        <td class="text-center display-6 font-weight-bold text-success">
                                                            ₱<?= number_format($fee['entrance_fee_amount'], 2) ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <button class="btn btn-warning" data-toggle="modal" data-target="#edit<?= $fee['entrance_fee_id'] ?>">
                                                                <i class="fas fa-edit"></i> Edit Amount
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- Edit Modal -->
                                                    <div class="modal fade" id="edit<?= $fee['entrance_fee_id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning text-white">
                                                                    <h5 class="modal-title">Update Entrance Fee</h5>
                                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="fee_id" value="<?= $fee['entrance_fee_id'] ?>">

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">New Entrance Fee Amount (₱) <span class="text-danger">*</span></label>
                                                                            <input type="number" step="0.01" class="form-control form-control-lg" name="amount" value="<?= $fee['entrance_fee_amount'] ?>" required>
                                                                            <small class="text-muted">This applies to all general entrances.</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                        <button type="submit" name="update_fee" class="btn btn-warning">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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