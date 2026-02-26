<?php
include '../../config.php';

// ✅ Handle AJAX return request
if (isset($_POST['ajax_return'])) {
    $log_id = $_POST['log_id'];
    $equipment_id = $_POST['equipment_id'];
    $return_qty = (int)$_POST['return_qty'];

    $response = ["status" => "error", "message" => "Unknown error occurred"];

    // Fetch current quantities
    $res = $conn->query("SELECT equipment_quantity FROM equipment_tbl WHERE equipment_id = $equipment_id");
    $row = $res->fetch_assoc();
    $currentQty = $row['equipment_quantity'];

    $logRes = $conn->query("SELECT quantity FROM equipment_log_tbl WHERE id = $log_id");
    $logRow = $logRes->fetch_assoc();
    $borrowedQty = $logRow['quantity'];

    if ($return_qty > $borrowedQty) {
        $response = ["status" => "error", "message" => "You cannot return more than borrowed."];
        echo json_encode($response);
        exit;
    }

    $newQty = $currentQty + $return_qty;
    $remainingBorrowed = $borrowedQty - $return_qty;

    $conn->begin_transaction();
    try {
        // Update equipment quantity
        $updateEq = $conn->prepare("
            UPDATE equipment_tbl 
            SET equipment_quantity = ?
            WHERE equipment_id = ?
        ");
        $updateEq->bind_param("ii", $newQty, $equipment_id);
        $updateEq->execute();
        $updateEq->close();

        // Update log depending on return type
        if ($remainingBorrowed == 0) {
            $updateLog = $conn->prepare("
                UPDATE equipment_log_tbl 
                SET action_type = 'Returned', status_log = 2, date_action = NOW() 
                WHERE id = ?
            ");
            $updateLog->bind_param("i", $log_id);
        } else {
            $updateLog = $conn->prepare("
                UPDATE equipment_log_tbl 
                SET quantity = ?, date_action = NOW() 
                WHERE id = ?
            ");
            $updateLog->bind_param("ii", $remainingBorrowed, $log_id);
        }
        $updateLog->execute();
        $updateLog->close();

        $conn->commit();
        $response = ["status" => "success", "message" => "Equipment returned successfully!"];
    } catch (Exception $e) {
        $conn->rollback();
        $response = ["status" => "error", "message" => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Borrowed Equipment</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Equipment Name</th>
                                            <th>Quantity Borrowed</th>
                                            <th>Date Borrowed</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "
                                            SELECT 
                                                log.id AS log_id,
                                                log.equipment_id,
                                                e.equipment_name,
                                                log.quantity,
                                                log.date_action
                                            FROM equipment_log_tbl log
                                            JOIN equipment_tbl e ON log.equipment_id = e.equipment_id
                                            WHERE log.action_type = 'Borrowed' AND log.status_log = 1
                                            ORDER BY log.date_action DESC
                                        ";
                                        $result = $conn->query($sql);

                                        while ($row = $result->fetch_assoc()) {
                                        ?>
                                            <tr id="row<?= $row['log_id'] ?>">
                                                <td><?= htmlspecialchars($row['equipment_name']) ?></td>
                                                <td><?= htmlspecialchars($row['quantity']) ?></td>
                                                <td><?= htmlspecialchars($row['date_action']) ?></td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn btn-success btn-sm return-btn"
                                                            data-log-id="<?= $row['log_id'] ?>"
                                                            data-equip-id="<?= $row['equipment_id'] ?>"
                                                            data-max="<?= $row['quantity'] ?>">
                                                        <i class="fas fa-undo"></i> Return
                                                    </button>
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

    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".return-btn").forEach(btn => {
            btn.addEventListener("click", function() {
                const log_id = this.getAttribute("data-log-id");
                const equipment_id = this.getAttribute("data-equip-id");
                const maxQty = parseInt(this.getAttribute("data-max"));

                Swal.fire({
                    title: "Return Equipment",
                    html: `
                        <input type="number" id="returnQty" class="swal2-input" min="1" max="${maxQty}" placeholder="Enter quantity to return">
                        <small>Max: ${maxQty}</small>
                    `,
                    showCancelButton: true,
                    confirmButtonText: "Confirm Return",
                    preConfirm: () => {
                        const qty = parseInt(document.getElementById('returnQty').value);
                        if (isNaN(qty) || qty < 1 || qty > maxQty) {
                            Swal.showValidationMessage(`Please enter a valid number between 1 and ${maxQty}`);
                            return false;
                        }
                        return qty;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const qty = result.value;
                        // AJAX call
                        fetch("", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: new URLSearchParams({
                                ajax_return: 1,
                                log_id: log_id,
                                equipment_id: equipment_id,
                                return_qty: qty
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: "success",
                                    title: data.message,
                                    toast: true,
                                    position: "top-end",
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Remove row from table if fully returned
                                if (qty == maxQty) {
                                    document.getElementById("row" + log_id).remove();
                                } else {
                                    // Update remaining qty in table
                                    const row = document.getElementById("row" + log_id);
                                    row.querySelector("td:nth-child(2)").textContent = maxQty - qty;
                                }
                            } else {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: data.message
                                });
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: "error",
                                title: "Request Failed",
                                text: err.message
                            });
                        });
                    }
                });
            });
        });
    });
    </script>
</body>
</html>
