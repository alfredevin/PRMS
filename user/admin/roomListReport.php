<?php
include '../../config.php';
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

                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Room Inventory Report</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-primary text-white">
                            <h6 class="m-0 font-weight-bold"><i class="fas fa-door-open me-2"></i> List of Rooms & Availability</h6>

                            <!-- Print Button (Target Blank) -->
                            <a href="./report/print_room_list.php" target="_blank" class="btn btn-light btn-sm text-primary font-weight-bold shadow-sm">
                                <i class="fas fa-print fa-sm"></i> Print Official Inventory
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Room Name</th>
                                            <th>Type</th>
                                            <th>Price per Night</th>
                                            <th>Max Capacity</th>
                                            <th class="text-center">Total Units</th>
                                            <th class="text-center">Available Units</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT r.*, t.room_type_name FROM rooms_tbl r 
                                                LEFT JOIN room_type_tbl t ON r.room_type_id = t.room_type_id
                                                ORDER BY r.room_name ASC";
                                        $result = mysqli_query($conn, $sql);

                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $status_badge = ($row['available'] > 0)
                                                    ? '<span class="badge badge-success">Available</span>'
                                                    : '<span class="badge badge-danger">Full / Occupied</span>';

                                                // Highlight low availability
                                                $avail_class = ($row['available'] == 0) ? 'text-danger font-weight-bold' : 'text-success font-weight-bold';

                                                echo "<tr>
                                                        <td class='font-weight-bold text-primary'>{$row['room_name']}</td>
                                                        <td>{$row['room_type_name']}</td>
                                                        <td>₱" . number_format($row['price'], 2) . "</td>
                                                        <td>{$row['max_guest']} Pax</td>
                                                        <td class='text-center'>{$row['quantity']}</td>
                                                        <td class='text-center {$avail_class}'>{$row['available']}</td>
                                                        <td class='text-center'>{$status_badge}</td>
                                                      </tr>";
                                            }
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
</body>

</html>