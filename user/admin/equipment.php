<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC ---

if (isset($_POST['update_equipment'])) {
    $id    = $_POST['equipment_id'];
    $name  = $_POST['equipment_name'];
    $desc  = $_POST['equipment_description'];
    $qty   = $_POST['equipment_quantity'];
    // NEW FIELD: Price
    $price = $_POST['equipment_price'];

    $sql = "UPDATE equipment_tbl 
             SET equipment_name = ?, 
                 equipment_description = ?, 
                 equipment_quantity = ?, 
                 equipment_price = ?
             WHERE equipment_id = ?";
    $stmt = $conn->prepare($sql);
    // Bind types: s (name), s (desc), i (qty), d (price), i (id)
    $stmt->bind_param("ssidi", $name, $desc, $qty, $price, $id);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: "success",
                    title: "Equipment updated successfully!",
                    toast: true,
                    position: "top-end",
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: "error",
                    title: "Failed to update equipment: ' . $stmt->error . '",
                    toast: true,
                    position: "top-end",
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>';
    }
    $stmt->close();
}

if (isset($_POST['add_equipment'])) {
    $name = $_POST['equipment_name'];
    $desc = $_POST['equipment_description'];
    $qty  = $_POST['equipment_quantity'];
    // NEW FIELD: Price
    $price = $_POST['equipment_price'];

    $insert_query = "INSERT INTO equipment_tbl (equipment_name, equipment_description, equipment_quantity, equipment_price) 
                     VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    // Bind types: s (name), s (desc), i (qty), d (price)
    $stmt->bind_param("ssid", $name, $desc, $qty, $price);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: "success",
                    title: "Equipment added successfully!",
                    toast: true,
                    position: "top-end",
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: "error",
                    title: "Failed to add equipment: ' . $stmt->error . '",
                    toast: true,
                    position: "top-end",
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>';
    }
    $stmt->close();
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
                        <h1 class="h3 mb-0 text-gray-800">Internal Equipment Inventory</h1>
                    </div>

                    <div class="row">

                        <!-- LEFT COLUMN: Add Equipment Form -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-success text-white">
                                    <h6 class="m-0 font-weight-bold">Add New Equipment Item</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST" autocomplete="off">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Equipment Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="equipment_name" placeholder="E.g., TOWEL, CHAIR" oninput="this.value = this.value.toUpperCase();" required>
                                            <small class="text-muted">Item used internally or loaned to guests.</small>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Description <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="equipment_description" rows="2" placeholder="Brief details about the item." required></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Total Quantity in Stock <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="equipment_quantity" min="1" required>
                                        </div>
                                        <!-- NEW PRICE FIELD -->
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold small">Replacement Price (₱) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="equipment_price" step="0.01" placeholder="Cost to replace one unit (for damage tracking)" required>
                                        </div>

                                        <button type="submit" name="add_equipment" class="btn btn-success btn-block">
                                            <i class="fas fa-plus"></i> Add Equipment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: List of Equipment -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Current Equipment List</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th><i class="fas fa-tools"></i> Name</th>
                                                    <th>Description</th>
                                                    <th><i class="fas fa-boxes"></i> Quantity</th>
                                                    <th><i class="fas fa-tags"></i> Price (₱)</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM equipment_tbl ORDER BY equipment_name ASC";
                                                $result = mysqli_query($conn, $sql);
                                                while ($res = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($res['equipment_name']) ?></td>
                                                        <td><?= htmlspecialchars(substr($res['equipment_description'], 0, 40)) ?>...</td>
                                                        <td><?= $res['equipment_quantity'] ?></td>
                                                        <td>₱<?= number_format($res['equipment_price'], 2) ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $res['equipment_id'] ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- EDIT MODAL -->
                                                    <div class="modal fade" id="edit<?= $res['equipment_id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST">
                                                                    <div class="modal-header bg-warning text-white">
                                                                        <h5 class="modal-title">Edit Equipment: <?= htmlspecialchars($res['equipment_name']) ?></h5>
                                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="equipment_id" value="<?= $res['equipment_id'] ?>">

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Equipment Name</label>
                                                                            <input type="text" name="equipment_name" value="<?= htmlspecialchars($res['equipment_name']) ?>" class="form-control" required>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Description</label>
                                                                            <textarea name="equipment_description" class="form-control" rows="2" required><?= htmlspecialchars($res['equipment_description']) ?></textarea>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Quantity</label>
                                                                            <input type="number" name="equipment_quantity" value="<?= $res['equipment_quantity'] ?>" class="form-control" required>
                                                                        </div>

                                                                        <!-- UPDATED PRICE FIELD -->
                                                                        <div class="form-group mb-4">
                                                                            <label class="font-weight-bold small">Replacement Price (₱)</label>
                                                                            <input type="number" name="equipment_price" value="<?= $res['equipment_price'] ?>" step="0.01" class="form-control" required>
                                                                        </div>

                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_equipment" class="btn btn-warning">Save Changes</button>
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