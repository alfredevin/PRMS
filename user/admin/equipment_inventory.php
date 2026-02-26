<?php
include '../../config.php';

if (isset($_POST['update_status'])) {
    $id = $_POST['equipment_id'];
    $status = $_POST['equipment_status'];
    $borrowQty = isset($_POST['borrow_quantity']) ? (int)$_POST['borrow_quantity'] : 0;
    $damagedQty = isset($_POST['damaged_quantity']) ? (int)$_POST['damaged_quantity'] : 0;

    // Get current equipment info
    $res = $conn->query("SELECT equipment_quantity FROM equipment_tbl WHERE equipment_id = $id");
    $row = $res->fetch_assoc();
    $availableQty = $row['equipment_quantity'];

    // Determine the quantity to deduct
    $deductQty = 0;
    $actionType = '';

    if ($status == 'Borrowed') {
        $deductQty = $borrowQty;
        $actionType = 'Borrowed';
    } elseif ($status == 'Damaged') {
        $deductQty = $damagedQty;
        $actionType = 'Damaged';
    }

    // Validation: Check if quantity exceeds available
    if ($deductQty > $availableQty) {
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Invalid Quantity",
                text: "Quantity entered exceeds available stock.",
            });
        </script>';
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // 1️⃣ Insert to equipment_log_tbl
            if ($actionType != '') {
                $logSql = "INSERT INTO equipment_log_tbl (equipment_id, action_type, quantity, date_action)
                           VALUES (?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param("isi", $id, $actionType, $deductQty);
                $logStmt->execute();
                $logStmt->close();
            }

            // 2️⃣ Update equipment_tbl quantity and status
            $newQty = $availableQty - $deductQty;
            $updateSql = "UPDATE equipment_tbl SET  equipment_quantity = ? WHERE equipment_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $newQty, $id);
            $updateStmt->execute();
            $updateStmt->close();

            // Commit transaction
            $conn->commit();

            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
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
        } catch (Exception $e) {
            $conn->rollback();
            echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "Error updating equipment",
                    text: "Something went wrong: ' . $e->getMessage() . '",
                });
            </script>';
        }
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

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Equipment Inventory</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Quantity</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT * FROM equipment_tbl";
                                        $result = mysqli_query($conn, $sql);
                                        while ($row = mysqli_fetch_assoc($result)) {
                                        ?>
                                            <tr>
                                                <td><?= $row['equipment_name'] ?></td>
                                                <td><?= $row['equipment_description'] ?></td>
                                                <td><?= $row['equipment_quantity'] ?></td>
                                              
                                                <td>
                                                    <a class="btn btn-primary btn-sm" href="#update<?= $row['equipment_id'] ?>" data-toggle="modal">Update Status</a>
                                                </td>
                                            </tr>

                                            <!-- Update Status Modal -->
                                            <div class="modal fade" id="update<?= $row['equipment_id'] ?>" tabindex="-1" role="dialog">
                                                <div class="modal-dialog modal-dialog-centered" role="document">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Equipment Status</h5>
                                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="equipment_id" value="<?= $row['equipment_id'] ?>">

                                                                <div class="form-group">
                                                                    <label>Status</label>
                                                                    <select name="equipment_status" class="form-control status-select" data-id="<?= $row['equipment_id'] ?>" required>
                                                                        <option value="Available">Available</option>
                                                                        <option value="Borrowed" >Borrowed</option>
                                                                        <option value="Damaged" >Damaged</option>
                                                                    </select>
                                                                </div>

                                                                <!-- Hidden: Quantity to Borrow -->
                                                                <div class="form-group borrow-quantity" id="borrowQty<?= $row['equipment_id'] ?>" style="display:none;">
                                                                    <label>Quantity to Borrow</label>
                                                                    <input type="number"
                                                                        class="form-control borrow-input"
                                                                        name="borrow_quantity"
                                                                        min="1"
                                                                        max="<?= $row['equipment_quantity'] ?>"
                                                                        placeholder="Enter quantity (max <?= $row['equipment_quantity'] ?>)">
                                                                    <small class="text-muted">Available: <?= $row['equipment_quantity'] ?></small>
                                                                </div>

                                                                <!-- Hidden: Quantity Damaged -->
                                                                <div class="form-group damaged-quantity" id="damagedQty<?= $row['equipment_id'] ?>" style="display:none;">
                                                                    <label>Quantity Damaged</label>
                                                                    <input type="number"
                                                                        class="form-control damaged-input"
                                                                        name="damaged_quantity"
                                                                        min="1"
                                                                        max="<?= $row['equipment_quantity'] ?>"
                                                                        placeholder="Enter quantity (max <?= $row['equipment_quantity'] ?>)">
                                                                    <small class="text-muted">Available: <?= $row['equipment_quantity'] ?></small>
                                                                </div>


                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
            <?php include './../template/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle show/hide of quantity fields
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', function() {
                    const id = this.getAttribute('data-id');
                    const borrowDiv = document.getElementById('borrowQty' + id);
                    const damagedDiv = document.getElementById('damagedQty' + id);

                    // Hide both first
                    borrowDiv.style.display = 'none';
                    damagedDiv.style.display = 'none';

                    // Show based on selected status
                    if (this.value === 'Borrowed') {
                        borrowDiv.style.display = 'block';
                    } else if (this.value === 'Damaged') {
                        damagedDiv.style.display = 'block';
                    }
                });
            });

            // Validation: prevent exceeding available quantity
            function limitCheck(inputClass) {
                document.querySelectorAll(inputClass).forEach(input => {
                    input.addEventListener('input', function() {
                        const max = parseInt(this.max);
                        if (parseInt(this.value) > max) {
                            this.value = max;
                            Swal.fire({
                                icon: 'error',
                                title: 'Quantity exceeds available stock!',
                                text: `You can only select up to ${max}.`,
                                toast: true,
                                position: 'top-end',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    });
                });
            }

            limitCheck('.borrow-input');
            limitCheck('.damaged-input');
        });
    </script>


</body>

</html>