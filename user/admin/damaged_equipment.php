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
                            <h6 class="m-0 font-weight-bold text-primary">Damaged Equipment</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Equipment Name</th>
                                            <th>Quantity Damaged</th>
                                            <th>Date Damaged</th>
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
                                            WHERE log.action_type = 'Damaged' AND log.status_log = 1
                                            ORDER BY log.date_action DESC
                                        ";
                                        $result = $conn->query($sql);

                                        while ($row = $result->fetch_assoc()) {
                                        ?>
                                            <tr id="row<?= $row['log_id'] ?>">
                                                <td><?= htmlspecialchars($row['equipment_name']) ?></td>
                                                <td><?= htmlspecialchars($row['quantity']) ?></td>
                                                <td><?= htmlspecialchars($row['date_action']) ?></td>
                                          
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
 
</body>

</html>