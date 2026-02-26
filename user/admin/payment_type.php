<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC (UNCHANGED) ---

if (isset($_POST['update_payment'])) {
    $id     = $_POST['payment_type_id'];
    $name   = $_POST['payment_type_name'];
    $number = $_POST['payment_type_number'];
    $check_query = "SELECT * FROM payment_type_tbl WHERE payment_type_name = ? AND payment_type_id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: "error",
                    title: "Error: Payment Type name already exists!"
                });
            });
        </script>';
    } else {
        $update_query = "UPDATE payment_type_tbl SET payment_type_name = ?, payment_type_number = ? WHERE payment_type_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $name, $number, $id);

        if ($stmt->execute()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: "success",
                        title: "Payment Type updated successfully!"
                    });
                });
            </script>';
        } else {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: "error",
                        title: "Failed to Update Payment Type: ' . $stmt->error . '"
                    });
                });
            </script>';
        }
    }
}

if (isset($_POST['add_payment'])) {
    $name   = $_POST['payment_type_name'];
    $number = $_POST['payment_type_number'];

    $check_query = "SELECT * FROM payment_type_tbl WHERE payment_type_name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: "error",
                    title: "Error: Payment Type name already exists!"
                });
            });
        </script>';
    } else {
        $insert_query = "INSERT INTO payment_type_tbl (payment_type_name, payment_type_number) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $name, $number);

        if ($stmt->execute()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: "success",
                        title: "Payment Type successfully Saved!"
                    });
                });
            </script>';
        } else {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: "error",
                        title: "Failed to save payment type: ' . $stmt->error . '"
                    });
                });
            </script>';
        }
    }
}

// --- END PHP PROCESSING LOGIC ---
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
                        <h1 class="h3 mb-0 text-gray-800">Payment Type Management</h1>
                    </div>

                    <div class="row">

                        <!-- LEFT COLUMN: Add Payment Type Form -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-success text-white">
                                    <h6 class="m-0 font-weight-bold">Add New Payment Type</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST" autocomplete="off">
                                        <div class="form-group mb-3">
                                            <label for="payment_type_name" class="font-weight-bold small">Type Name (E.g., GCASH, VISA) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="payment_type_name"
                                                placeholder="Enter Payment Type Name" required oninput="this.value = this.value.toUpperCase();">
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="payment_type_number" class="font-weight-bold small">Account Number/Ref <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="payment_type_number"
                                                placeholder="Enter Account Number or Reference" required
                                                maxlength="11"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                                            <small class="text-muted">This number will be displayed to guests for payment.</small>
                                        </div>
                                        <button type="submit" name="add_payment" class="btn btn-success btn-block">
                                            <i class="fas fa-plus"></i> Add Payment Type
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: List of Payment Types -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Existing Payment Methods</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th><i class="fas fa-wallet mr-1"></i> Payment Type Name</th>
                                                    <th><i class="fas fa-hashtag mr-1"></i> Account Number/Ref</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM payment_type_tbl ORDER BY payment_type_name ASC";
                                                $result = mysqli_query($conn, $sql);
                                                while ($res = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($res['payment_type_name']) ?></td>
                                                        <td><?= htmlspecialchars($res['payment_type_number']) ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $res['payment_type_id'] ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- Edit Modal (Dynamic ID) -->
                                                    <div class="modal fade" id="edit<?= $res['payment_type_id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning text-white">
                                                                    <h5 class="modal-title">Update Method: <?= htmlspecialchars($res['payment_type_name']) ?></h5>
                                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="payment_type_id" value="<?= $res['payment_type_id'] ?>">
                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Payment Type Name</label>
                                                                            <input type="text" name="payment_type_name" class="form-control"
                                                                                value="<?= htmlspecialchars($res['payment_type_name']) ?>" oninput="this.value = this.value.toUpperCase();" required>
                                                                        </div>
                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Account Number/Ref</label>
                                                                            <input type="text" name="payment_type_number" class="form-control"
                                                                                value="<?= htmlspecialchars($res['payment_type_number']) ?>" required

                                                                                maxlength="11"
                                                                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                                                                            <small class="text-muted">This number is visible to users during booking.</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer mt-4">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                        <button type="submit" name="update_payment" class="btn btn-warning">Save Changes</button>
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