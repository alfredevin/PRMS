<?php
include '../../config.php';
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
                            <h6 class="m-0 font-weight-bold text-primary">All Equipment Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="text-center">
                                        <tr>
                                            <th>Equipment Name</th>
                                            <th>Available</th>
                                            <th>Borrowed</th>
                                            <th>Damaged</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Query para makuha lahat ng equipment at i-calculate ang bawat status
                                        $sql = "
                                            SELECT 
                                                e.equipment_id,
                                                e.equipment_name,
                                                e.equipment_quantity AS available_qty,
                                                COALESCE(SUM(CASE WHEN log.action_type = 'Borrowed' THEN log.quantity END), 0) AS borrowed_qty,
                                                COALESCE(SUM(CASE WHEN log.action_type = 'Damaged' THEN log.quantity END), 0) AS damaged_qty
                                            FROM equipment_tbl e
                                            LEFT JOIN equipment_log_tbl log ON e.equipment_id = log.equipment_id
                                            GROUP BY e.equipment_id, e.equipment_name, e.equipment_quantity
                                            ORDER BY e.equipment_name ASC
                                        ";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                        ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['equipment_name']) ?></td>
                                                    <td class="text-center text-success"><?= htmlspecialchars($row['available_qty']) ?></td>
                                                    <td class="text-center text-warning"><?= htmlspecialchars($row['borrowed_qty']) ?></td>
                                                    <td class="text-center text-danger"><?= htmlspecialchars($row['damaged_qty']) ?></td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="4" class="text-center">No data found.</td></tr>';
                                        }
                                        ?>
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
</body>
</html>
