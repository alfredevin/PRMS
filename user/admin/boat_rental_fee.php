<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC ---

// 1. Handle Add Boat Rental Fee
if (isset($_POST['add_rental'])) {
    $destination = $_POST['destination'];
    $min_guest   = (int)$_POST['min_guest'];
    $max_guest   = (int)$_POST['max_guest'];
    $amount      = (float)$_POST['amount'];
    $island_hopping_amount = !empty($_POST['island_hopping_amount']) ? (float)$_POST['island_hopping_amount'] : 0;
    $description = $_POST['description'];
    $num_days    = (int)$_POST['num_days'];
    $is_vice_versa = isset($_POST['is_vice_versa']) ? 1 : 0;

    if ($max_guest < $min_guest) {
        echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "error", title: "Invalid Guest Count!", text: "Maximum guests cannot be lower than Minimum guests." }); });</script>';
    } else {
        // FIXED bind_param types: s=string, i=int, d=double
        $insert_query = "INSERT INTO boat_rental_fee_tbl (destination, min_guest, max_guest, amount, island_hopping_amount, description, num_days, is_vice_versa) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("siiddsii", $destination, $min_guest, $max_guest, $amount, $island_hopping_amount, $description, $num_days, $is_vice_versa);

        if ($stmt->execute()) {
            echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "success", title: "Added successfully!", toast: true, position: "top-end", showConfirmButton: false, timer: 2000 }); });</script>';
        }
    }
}

// 2. Handle Update Boat Rental Fee
if (isset($_POST['update_rental'])) {
    $id = (int)$_POST['rental_id'];
    $destination = $_POST['destination'];
    $min_guest   = (int)$_POST['min_guest'];
    $max_guest   = (int)$_POST['max_guest'];
    $amount      = (float)$_POST['amount'];
    $island_hopping_amount = !empty($_POST['island_hopping_amount']) ? (float)$_POST['island_hopping_amount'] : 0;
    $description = $_POST['description'];
    $num_days    = (int)$_POST['num_days'];
    $is_vice_versa = isset($_POST['is_vice_versa']) ? 1 : 0;

    if ($max_guest < $min_guest) {
        echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "error", title: "Invalid Guest Count!", text: "Maximum guests cannot be lower than Minimum guests." }); });</script>';
    } else {
        // FIXED SQL: Correct types for bind_param (siiddsiii)
        $update_query = "UPDATE boat_rental_fee_tbl SET destination=?, min_guest=?, max_guest=?, amount=?, island_hopping_amount=?, description=?, num_days=?, is_vice_versa=? WHERE rental_id=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("siiddsiii", $destination, $min_guest, $max_guest, $amount, $island_hopping_amount, $description, $num_days, $is_vice_versa, $id);

        if ($stmt->execute()) {
            echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire({ icon: "success", title: "Updated successfully!", toast: true, position: "top-end", showConfirmButton: false, timer: 2000 }); });</script>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php'; ?>
<style>
    .badge-vice-versa { background-color: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb; font-size: 0.7rem; }
</style>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Boat Rental Management</h1>

                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow border-left-success">
                                <div class="card-header py-3 bg-success text-white"><h6 class="m-0 font-weight-bold">Add Trip Details</h6></div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group mb-2">
                                            <label class="font-weight-bold small">Destination</label>
                                            <input type="text" name="destination" class="form-control" placeholder="Island Name" required>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-3">
                                            <input type="checkbox" class="custom-control-input" id="vvAdd" name="is_vice_versa" checked>
                                            <label class="custom-control-label small font-weight-bold" for="vvAdd">Include Vice Versa</label>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-6">
                                                <label class="small">Min Guest</label>
                                                <input type="number" name="min_guest" class="form-control" value="1" required>
                                            </div>
                                            <div class="form-group col-6">
                                                <label class="small">Max Guest</label>
                                                <input type="number" name="max_guest" class="form-control" value="10" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-6">
                                                <label class="small">Days</label>
                                                <input type="number" name="num_days" class="form-control" value="1" required>
                                            </div>
                                            <div class="form-group col-6">
                                                <label class="small">Price (₱)</label>
                                                <input type="number" name="amount" class="form-control" step="0.01" required>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small">Island Hopping Add-on (₱)</label>
                                            <input type="number" name="island_hopping_amount" class="form-control" step="0.01">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small">Description</label>
                                            <textarea name="description" class="form-control" rows="2"></textarea>
                                        </div>
                                        <button type="submit" name="add_rental" class="btn btn-success btn-block">Save Boat Trip</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="card shadow">
                                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Boat Rental Price List</h6></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm small" id="dataTable">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Destination</th>
                                                    <th>Duration</th>
                                                    <th>Capacity</th>
                                                    <th>Base Price</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $result = mysqli_query($conn, "SELECT * FROM boat_rental_fee_tbl ORDER BY rental_id DESC");
                                                // FIXED: Changed $r to $rental to match Modal variables
                                                while ($rental = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars($rental['destination']) ?></strong>
                                                            <?= $rental['is_vice_versa'] ? '<span class="badge badge-vice-versa">Vice Versa</span>' : '' ?>
                                                        </td>
                                                        <td><?= $rental['num_days'] ?> Day/s</td>
                                                        <td><?= $rental['min_guest'] ?>-<?= $rental['max_guest'] ?> pax</td>
                                                        <td>₱<?= number_format($rental['amount'], 2) ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $rental['rental_id'] ?>"><i class="fas fa-edit"></i></button>
                                                        </td>
                                                    </tr>

                                                    <div class="modal fade" id="edit<?= $rental['rental_id'] ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning text-white"><h5 class="modal-title small">Update Trip</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="rental_id" value="<?= $rental['rental_id'] ?>">
                                                                        <div class="form-group mb-2">
                                                                            <label class="small font-weight-bold">Destination</label>
                                                                            <input type="text" name="destination" class="form-control" value="<?= htmlspecialchars($rental['destination']) ?>" required>
                                                                        </div>
                                                                        <div class="custom-control custom-checkbox mb-3">
                                                                            <input type="checkbox" class="custom-control-input" id="vv<?= $rental['rental_id'] ?>" name="is_vice_versa" <?= $rental['is_vice_versa'] ? 'checked' : '' ?>>
                                                                            <label class="custom-control-label small" for="vv<?= $rental['rental_id'] ?>">Include Vice Versa</label>
                                                                        </div>
                                                                        <div class="form-row">
                                                                            <div class="form-group col-6">
                                                                                <label class="small font-weight-bold">Min Guest</label>
                                                                                <input type="number" name="min_guest" class="form-control" value="<?= $rental['min_guest'] ?>" required>
                                                                            </div>
                                                                            <div class="form-group col-6">
                                                                                <label class="small font-weight-bold">Max Guest</label>
                                                                                <input type="number" name="max_guest" class="form-control" value="<?= $rental['max_guest'] ?>" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-row">
                                                                            <div class="form-group col-6">
                                                                                <label class="small font-weight-bold">Days</label>
                                                                                <input type="number" name="num_days" class="form-control" value="<?= $rental['num_days'] ?>" required>
                                                                            </div>
                                                                            <div class="form-group col-6">
                                                                                <label class="small font-weight-bold">Price (₱)</label>
                                                                                <input type="number" name="amount" class="form-control" value="<?= $rental['amount'] ?>" step="0.01" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="small font-weight-bold">Island Hopping Add-on (₱)</label>
                                                                            <input type="number" name="island_hopping_amount" class="form-control" value="<?= $rental['island_hopping_amount'] ?>" step="0.01">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label class="small font-weight-bold">Description</label>
                                                                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($rental['description']) ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer"><button type="submit" name="update_rental" class="btn btn-warning btn-block font-weight-bold">Save Changes</button></div>
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
    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>