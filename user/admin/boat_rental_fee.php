<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC ---

// 1. Handle Add Boat Rental Fee
if (isset($_POST['add_rental'])) {
    $destination = $_POST['destination'];
    $min_guest   = $_POST['min_guest'];
    $max_guest   = $_POST['max_guest'];
    $amount      = $_POST['amount'];
    $island_hopping_amount = $_POST['island_hopping_amount'] ?? 0;

    // VALIDATION: Check if Max is lower than Min
    if ($max_guest < $min_guest) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Guest Count!",
                    text: "Maximum guests cannot be lower than Minimum guests.",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            });
        </script>';
    } else {
        // Check for duplicates
        $check_query = "SELECT * FROM boat_rental_fee_tbl WHERE destination = ? AND amount = ? AND min_guest= ? AND max_guest=?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("dsii", $destination, $amount, $min_guest, $max_guest);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        icon: "error",
                        title: "Destination and Price/Guest combo already exists!",
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                });
            </script>';
        } else {
            $insert_query = "INSERT INTO boat_rental_fee_tbl (destination, min_guest, max_guest, amount, island_hopping_amount) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("siidd", $destination, $min_guest, $max_guest, $amount, $island_hopping_amount);

            if ($stmt->execute()) {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function () {
                        Swal.fire({
                            icon: "success",
                            title: "Boat rental fee added successfully!",
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
                            title: "Failed to add rental fee: ' . $stmt->error . '",
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true
                        });
                    });
                </script>';
            }
        }
    }
}

// 2. Handle Update Boat Rental Fee
if (isset($_POST['update_rental'])) {
    $id = $_POST['rental_id'];
    $destination = $_POST['destination'];
    $min_guest   = $_POST['min_guest'];
    $max_guest   = $_POST['max_guest'];
    $amount      = $_POST['amount'];
    $island_hopping_amount = $_POST['island_hopping_amount'] ?? 0;

    // VALIDATION: Check if Max is lower than Min
    if ($max_guest < $min_guest) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Guest Count!",
                    text: "Maximum guests cannot be lower than Minimum guests.",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            });
        </script>';
    } else {
        $update_query = "UPDATE boat_rental_fee_tbl 
                         SET destination = ?, min_guest = ?, max_guest = ?, amount = ?, island_hopping_amount = ?
                         WHERE rental_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("siiddi", $destination, $min_guest, $max_guest, $amount, $island_hopping_amount, $id);

        if ($stmt->execute()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        icon: "success",
                        title: "Boat rental fee updated successfully!",
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
                        title: "Failed to update rental fee!",
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                });
            </script>';
        }
    }
}
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

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Boat Rental Management</h1>
                    </div>

                    <div class="row">

                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-success text-white">
                                    <h6 class="m-0 font-weight-bold">Add New Boat Trip Fee</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST" autocomplete="off">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Destination (Back & Forth)</label>
                                            <input type="text" name="destination" class="form-control" placeholder="E.g., Island A" required>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group mb-3 col-6">
                                                <label class="font-weight-bold small">Minimum Guests</label>
                                                <input type="number" name="min_guest" id="add_min" class="form-control" min="1" value="1" required
                                                    oninput="
                                                           // Get Min value
                                                           let minVal = parseInt(this.value);
                                                           // Get Max input element
                                                           let maxInput = document.getElementById('add_max');
                                                           
                                                           // Set the 'min' attribute of Max input so user can't click lower
                                                           maxInput.min = minVal;
                                                           
                                                           // If current Max value is lower than new Min, automatically adjust Max
                                                           if(parseInt(maxInput.value) < minVal) {
                                                               maxInput.value = minVal;
                                                           }
                                                       ">
                                            </div>
                                            <div class="form-group mb-3 col-6">
                                                <label class="font-weight-bold small">Maximum Guests</label>
                                                <input type="number" name="max_guest" id="add_max" class="form-control" min="1" value="10" required>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Base Boat Rental Amount (₱)</label>
                                            <input type="number" name="amount" class="form-control" step="0.01" placeholder="E.g., 2500.00" required>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold small">Island Hopping Add-on (₱)</label>
                                            <input type="number" name="island_hopping_amount" class="form-control" step="0.01" placeholder="Optional (E.g., 500.00)">
                                        </div>
                                        <button type="submit" name="add_rental" class="btn btn-success btn-block">
                                            <i class="fas fa-plus-circle"></i> Add Rental Fee
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Boat Rental Price List</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Destination</th>
                                                    <th>Guests</th>
                                                    <th>Base Amount</th>
                                                    <th>Hopping Add-on</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM boat_rental_fee_tbl ORDER BY destination ASC";
                                                $result = mysqli_query($conn, $sql);
                                                while ($rental = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($rental['destination']) ?></td>
                                                        <td><?= $rental['min_guest'] . ' - ' . $rental['max_guest'] ?></td>
                                                        <td>₱<?= number_format($rental['amount'], 2) ?></td>
                                                        <td>₱<?= number_format($rental['island_hopping_amount'], 2) ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $rental['rental_id'] ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <div class="modal fade" id="edit<?= $rental['rental_id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning text-white">
                                                                    <h5 class="modal-title">Update Fee for: <?= htmlspecialchars($rental['destination']) ?></h5>
                                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="rental_id" value="<?= $rental['rental_id'] ?>">

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Destination</label>
                                                                            <input type="text" name="destination" class="form-control" value="<?= htmlspecialchars($rental['destination']) ?>" required>
                                                                        </div>

                                                                        <div class="form-row">
                                                                            <div class="form-group mb-3 col-6">
                                                                                <label class="font-weight-bold small">Min Guests</label>
                                                                                <input type="number" name="min_guest" class="form-control"
                                                                                    value="<?= $rental['min_guest'] ?>" min="1" required
                                                                                    oninput="
                                                                                           // Logic for Edit Modal
                                                                                           let minVal = parseInt(this.value);
                                                                                           let maxInput = document.getElementById('edit_max_<?= $rental['rental_id'] ?>');
                                                                                           
                                                                                           // Set Min attribute
                                                                                           maxInput.min = minVal;
                                                                                           
                                                                                           // Adjust value if invalid
                                                                                           if(parseInt(maxInput.value) < minVal) {
                                                                                               maxInput.value = minVal;
                                                                                           }
                                                                                       ">
                                                                            </div>
                                                                            <div class="form-group mb-3 col-6">
                                                                                <label class="font-weight-bold small">Max Guests</label>
                                                                                <input type="number" name="max_guest" id="edit_max_<?= $rental['rental_id'] ?>"
                                                                                    class="form-control" value="<?= $rental['max_guest'] ?>"
                                                                                    min="<?= $rental['min_guest'] ?>" required>
                                                                            </div>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Base Amount (₱)</label>
                                                                            <input type="number" name="amount" class="form-control" value="<?= $rental['amount'] ?>" step="0.01" required>
                                                                        </div>
                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Island Hopping Add-on (₱)</label>
                                                                            <input type="number" name="island_hopping_amount" class="form-control" value="<?= $rental['island_hopping_amount'] ?>" step="0.01">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer mt-2">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                        <button type="submit" name="update_rental" class="btn btn-warning">Save Changes</button>
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