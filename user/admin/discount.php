<?php
include '../../config.php';

// Handle Add/Update Discount AND Apply to Rooms
if (isset($_POST['save_discount'])) {

    // 1. Kunin muna ang mga piniling rooms
    $selected_rooms = isset($_POST['room_ids']) ? $_POST['room_ids'] : [];

    // --- VALIDATION: CHECK KUNG MAY PINILI NA ROOM ---
    if (empty($selected_rooms)) {
        // Kapag WALANG pinili, mag-e-error at titigil dito ang code
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() { 
                Swal.fire({
                    icon: 'error', 
                    title: 'No Room Selected!', 
                    text: 'Please select at least one room to apply this promo.',
                    confirmButtonColor: '#d33'
                }); 
            });
        </script>";
    } else {
        // Kapag MERONG pinili, itutuloy ang pag-save

        $id = $_POST['fee_id'] ?? null;
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $amount = floatval($_POST['amount']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // --- 2. SAVE OR UPDATE DISCOUNT TABLE ---
        if ($id) {
            $sql = "UPDATE discount_tbl SET discount_name=?, discount_percent=?, start_date=?, end_date=? WHERE discount_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdssi", $name, $amount, $start_date, $end_date, $id);
            $stmt->execute();
            $discount_id = $id;
        } else {
            $sql = "INSERT INTO discount_tbl (discount_name, discount_percent, start_date, end_date) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdss", $name, $amount, $start_date, $end_date);
            $stmt->execute();
            $discount_id = $conn->insert_id;
        }

        // --- 3. UPDATE ROOMS TABLE ---

        // a) Reset: Remove THIS discount from all rooms first (Set to 0)
        // Ito ay para malinis yung mga dati nang naka-check kung sakaling inuncheck mo sila sa edit
        $reset_sql = "UPDATE rooms_tbl SET discount_id = 0 WHERE discount_id = '$discount_id'";
        mysqli_query($conn, $reset_sql);

        // b) Apply to selected rooms (Overwrite existing discount)
        // Sigurado na tayong hindi empty ito dahil sa validation sa taas
        $room_ids_string = implode(",", array_map('intval', $selected_rooms));

        // Apply the newly created/updated discount ID to the selected rooms
        $apply_sql = "UPDATE rooms_tbl SET discount_id = '$discount_id' WHERE room_id IN ($room_ids_string)";
        mysqli_query($conn, $apply_sql);

        // Success Message
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() { 
                Swal.fire({
                    icon: 'success', 
                    title: 'Promo successfully saved and applied!', 
                    showConfirmButton: false, 
                    timer: 2000
                }).then(() => window.location.href='discount.php'); 
            });
        </script>";
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
                        <h1 class="h3 mb-0 text-gray-800">Promo & Discount Management</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Active and Scheduled Promos</h6>
                            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addDiscountModal">
                                <i class="fas fa-percent"></i> + Add New Promo
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Promo Name</th>
                                            <th>Discount (%)</th>
                                            <th>Validity</th>
                                            <th>Status</th>
                                            <th>Applied To</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT * FROM discount_tbl ORDER BY start_date DESC";
                                        $result = mysqli_query($conn, $sql);
                                        $today = date('Y-m-d');

                                        // Fetch ALL rooms once to pass to modals easily
                                        $all_rooms_res = mysqli_query($conn, "SELECT r.room_id, r.room_name, r.discount_id, t.room_type_name, 
                                            d.discount_name as active_promo, d.end_date as promo_end
                                            FROM rooms_tbl r 
                                            LEFT JOIN room_type_tbl t ON r.room_type_id = t.room_type_id 
                                            LEFT JOIN discount_tbl d ON r.discount_id = d.discount_id
                                            ORDER BY t.room_type_name, r.room_name");
                                        $all_rooms = mysqli_fetch_all($all_rooms_res, MYSQLI_ASSOC);

                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $d_id = $row['discount_id'];
                                            $s_date = date("M d", strtotime($row['start_date']));
                                            $e_date = date("M d, Y", strtotime($row['end_date']));

                                            if ($today < $row['start_date']) $status_badge = '<span class="badge badge-warning"><i class="fas fa-clock"></i> Upcoming</span>';
                                            elseif ($today > $row['end_date']) $status_badge = '<span class="badge badge-secondary"><i class="fas fa-times-circle"></i> Expired</span>';
                                            else $status_badge = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>';

                                            $count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM rooms_tbl WHERE discount_id = '$d_id'"));
                                            $applied_count = $count_res['c'];
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['discount_name']) ?></td>
                                                <td><span class="badge badge-primary"><?= $row['discount_percent'] ?>% OFF</span></td>
                                                <td><small><?= $s_date ?> - <?= $e_date ?></small></td>
                                                <td><?= $status_badge ?></td>
                                                <td><span class="badge badge-info"><?= $applied_count ?> Rooms</span></td>
                                                <td>
                                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $d_id ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Edit Modal (Dynamic ID) -->
                                            <div class="modal fade" id="edit<?= $d_id ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header bg-warning text-white">
                                                                <h5 class="modal-title">Edit Promo: <?= htmlspecialchars($row['discount_name']) ?></h5>
                                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="fee_id" value="<?= $d_id ?>">

                                                                <h6 class="text-warning font-weight-bold mb-3"><i class="fas fa-tags"></i> Promo Details</h6>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6"><label class="small font-weight-bold">Promo Name</label><input type="text" class="form-control" name="name" value="<?= htmlspecialchars($row['discount_name']) ?>" required></div>
                                                                    <div class="col-md-6"><label class="small font-weight-bold">Percent (%)</label><input type="number" class="form-control" name="amount" value="<?= $row['discount_percent'] ?>" step="1" required></div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6"><label class="small font-weight-bold">Start Date</label><input type="date" class="form-control" name="start_date" value="<?= $row['start_date'] ?>" min="<?= date('Y-m-d') ?>" required></div>
                                                                    <div class="col-md-6"><label class="small font-weight-bold">End Date</label><input type="date" class="form-control" name="end_date" value="<?= $row['end_date'] ?>" min="<?= date('Y-m-d') ?>" required></div>
                                                                </div>
                                                                <hr>

                                                                <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-hotel"></i> Apply to Rooms</h6>
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input select-all" type="checkbox" id="selectAllEdit<?= $d_id ?>" onclick="toggleAll(this, '<?= $d_id ?>')">
                                                                    <label class="form-check-label font-weight-bold" for="selectAllEdit<?= $d_id ?>">Select/Deselect All</label>
                                                                </div>

                                                                <div class="room-list-container" style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                                                                    <?php
                                                                    foreach ($all_rooms as $room) {
                                                                        $isDisabled = '';
                                                                        $badge = '';
                                                                        $isChecked = ($room['discount_id'] == $d_id) ? 'checked' : '';

                                                                        // Check Conflict: Has another active promo
                                                                        if ($room['discount_id'] != 0 && $room['discount_id'] != $d_id) {
                                                                            if ($today <= $room['promo_end']) {
                                                                                $isDisabled = 'disabled';
                                                                                $promoEnd = date("M d", strtotime($room['promo_end']));
                                                                                $badge = "<span class='badge badge-danger ml-2' style='font-size: 10px;'>Active: " . htmlspecialchars($room['active_promo']) . " (ends $promoEnd)</span>";
                                                                                $isChecked = ''; // Cannot be checked if disabled
                                                                            }
                                                                        }
                                                                    ?>
                                                                        <div class="form-check border-bottom py-1">
                                                                            <input class="form-check-input room-checkbox-<?= $d_id ?>" type="checkbox" name="room_ids[]" value="<?= $room['room_id'] ?>" id="r<?= $room['room_id'] ?>_<?= $d_id ?>" <?= $isChecked ?> <?= $isDisabled ?>>
                                                                            <label class="form-check-label" for="r<?= $room['room_id'] ?>_<?= $d_id ?>" style="<?= $isDisabled ? 'color:#999; cursor: not-allowed;' : '' ?>">
                                                                                <strong><?= htmlspecialchars($room['room_type_name']) ?></strong> - <?= htmlspecialchars($room['room_name']) ?>
                                                                                <?= $badge ?>
                                                                            </label>
                                                                        </div>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                <button type="submit" name="save_discount" class="btn btn-warning">Save Changes</button>
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

    <div class="modal fade" id="addDiscountModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Create New Promo</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <h6 class="text-success font-weight-bold mb-3"><i class="fas fa-tags"></i> Promo Details</h6>
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="small font-weight-bold">Promo Name</label><input type="text" class="form-control" name="name" placeholder="E.g., CHRISTMAS SALE" required></div>
                            <div class="col-md-6"><label class="small font-weight-bold">Percent (%)</label><input type="number" class="form-control" name="amount" step="1" min="1" max="100" required></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="small font-weight-bold">Start Date</label><input type="date" class="form-control" name="start_date" min="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-md-6"><label class="small font-weight-bold">End Date</label><input type="date" class="form-control" name="end_date" min="<?= date('Y-m-d') ?>" required></div>
                        </div>
                        <hr>

                        <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-hotel"></i> Apply to Rooms</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="selectAllNew" onclick="toggleAll(this, 'new')">
                            <label class="form-check-label font-weight-bold" for="selectAllNew">Select All Available</label>
                            <small class="text-muted ml-2">(Rooms with an active promo cannot be selected.)</small>
                        </div>

                        <div class="room-list-container" style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                            <?php
                            // Reuse the fetched room list for the Add Modal
                            foreach ($all_rooms as $r) {
                                $isDisabled = '';
                                $badge = '';

                                // Logic: If room has discount AND it is not expired
                                if ($r['discount_id'] != 0) {
                                    if ($today <= $r['promo_end']) {
                                        $isDisabled = 'disabled';
                                        $promoEnd = date("M d", strtotime($r['promo_end']));
                                        $badge = "<span class='badge badge-danger ml-2' style='font-size: 10px;'>Active: " . htmlspecialchars($r['active_promo']) . " (ends $promoEnd)</span>";
                                    }
                                }

                                echo '<div class="form-check border-bottom py-1">
                                        <input class="form-check-input room-checkbox-new" type="checkbox" name="room_ids[]" value="' . $r['room_id'] . '" ' . $isDisabled . '>
                                        <label class="form-check-label" style="' . ($isDisabled ? 'color:#999; cursor: not-allowed;' : '') . '">
                                            <strong>' . htmlspecialchars($r['room_type_name']) . '</strong> - ' . htmlspecialchars($r['room_name']) . $badge . '
                                        </label>
                                      </div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="save_discount" class="btn btn-success">Create Promo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Function to toggle all checkboxes within a specific modal/context
        function toggleAll(source, id) {
            // Target checkboxes by their unique class name
            checkboxes = document.getElementsByClassName('room-checkbox-' + id);
            for (var i = 0, n = checkboxes.length; i < n; i++) {
                // Only modify checkboxes that are NOT disabled
                if (!checkboxes[i].disabled) {
                    checkboxes[i].checked = source.checked;
                }
            }
        }
    </script>
</body>

</html>