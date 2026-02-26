<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC ---

if (isset($_POST['update_room'])) {
    $room_id = $_POST['room_id'];
    $room_type_id = $_POST['room_type_id'];
    $price = $_POST['price'];
    $max_guest = $_POST['max_guest'];
    $quantity = $_POST['quantity'];
    $room_name = $_POST['room_name'];
    $room_description = $_POST['room_description'];

    // If quantity is updated, available rooms should also be updated to match the new quantity.
    // NOTE: This logic assumes all rooms are currently vacant if quantity increases. 
    // If you need more complex availability tracking, you must calculate (old_available + new_quantity - old_quantity).
    $available = $quantity;

    $image_sql = "";
    $image_name = null;
    $params = [$room_type_id, $price, $max_guest, $quantity, $room_name, $room_description, $available];
    $types = "iidissi";

    if (!empty($_FILES['image']['name'])) {
        $image_name = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];
        // Ensure this folder path is correct relative to the script execution
        $folder = "uploads/" . $image_name;
        move_uploaded_file($tmp_name, $folder);
        $image_sql = ", image = ?";

        // Prepend the new image path and update types string for binding
        array_splice($params, 6, 0, [$folder]);
        $types .= "s";
    }

    // Add room_id to the end of the parameters array
    $params[] = $room_id;
    $types .= "i";

    $sql = "UPDATE rooms_tbl SET 
                room_type_id = ?, 
                price = ?, 
                max_guest = ?, 
                quantity = ?, 
                room_name = ?, 
                room_description = ?, 
                available = ? 
                $image_sql
            WHERE room_id = ?";

    $stmt = $conn->prepare($sql);

    // Dynamic binding using call_user_func_array
    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

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
                    title: "The Room details were successfully Updated!"
                });
            });
        </script>';
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}


if (isset($_POST['add_room'])) {
    $room_type_id = $_POST['room_type_id'];
    $price = $_POST['price'];
    $max_guest = $_POST['max_guest'];
    $room_name = $_POST['room_name'];
    $room_description = $_POST['room_description'];
    $quantity = $_POST['quantity'];
    $available = $quantity;

    // Image handling
    $image_name = $_FILES['image']['name'] ?? '';
    $tmp_name = $_FILES['image']['tmp_name'] ?? '';
    $folder = !empty($image_name) ? "uploads/" . $image_name : '';
    if (!empty($tmp_name)) {
        move_uploaded_file($tmp_name, $folder);
    }

    $sql = "INSERT INTO rooms_tbl (room_type_id, price, max_guest, quantity, available, image, room_description, room_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iidissss", $room_type_id, $price, $max_guest, $quantity, $available, $folder, $room_description, $room_name);

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
                    title: "The Room details were successfully Saved!"
                });
            });
        </script>';
    } else {
        echo "Error: " . $stmt->error;
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
                        <h1 class="h3 mb-0 text-gray-800">Room Management</h1>
                    </div>

                    <div class="card shadow mb-4 ml-2">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Room Inventory</h6>
                            <button class="btn btn-success" data-toggle="modal" data-target="#addRoomModal">
                                <i class="fas fa-plus me-2"></i> Add New Room
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Room Type</th>
                                            <th>Room Name</th>
                                            <th>Description</th>
                                            <th>Price (₱)</th>
                                            <th>Max Guests</th>
                                            <th>Total Qty</th>
                                            <th class="bg-success text-white">Available</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT r.*, t.room_type_name FROM rooms_tbl r
                                            LEFT JOIN room_type_tbl t ON r.room_type_id = t.room_type_id";
                                        $result = mysqli_query($conn, $sql);
                                        while ($room = mysqli_fetch_assoc($result)) {
                                        ?>
                                            <tr>
                                                <td>
                                                    <img src="<?= htmlspecialchars($room['image']) ?>" width="80" height="60" style="object-fit: cover;" class="rounded">
                                                </td>
                                                <td><?= htmlspecialchars($room['room_type_name']) ?></td>
                                                <td><?= htmlspecialchars($room['room_name']) ?></td>
                                                <td><?= htmlspecialchars(substr($room['room_description'], 0, 50)) ?>...</td>
                                                <td>₱<?= number_format($room['price'], 2) ?></td>
                                                <td><?= $room['max_guest'] ?></td>
                                                <td><?= $room['quantity'] ?></td>
                                                <td><span class="badge badge-success"><?= $room['available'] ?></span></td>

                                                <td>
                                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editRoom<?= $room['room_id'] ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Edit Room Modal -->
                                            <div class="modal fade" id="editRoom<?= $room['room_id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning text-white">
                                                            <h5 class="modal-title">Edit Room: <?= htmlspecialchars($room['room_name']) ?></h5>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">

                                                                <div class="form-row">
                                                                    <div class="form-group mb-3 col-6">
                                                                        <label class="font-weight-bold small">Room Name</label>
                                                                        <input type="text" name="room_name" value="<?= htmlspecialchars($room['room_name']) ?>" oninput="this.value = this.value.toUpperCase();" class="form-control" placeholder="Enter Room Name" required>
                                                                    </div>
                                                                    <div class="form-group col-md-6">
                                                                        <label class="font-weight-bold small">Room Type</label>
                                                                        <select name="room_type_id" class="form-control" required>
                                                                            <?php
                                                                            $types = mysqli_query($conn, "SELECT * FROM room_type_tbl");
                                                                            while ($type = mysqli_fetch_assoc($types)) {
                                                                                $selected = ($type['room_type_id'] == $room['room_type_id']) ? 'selected' : '';
                                                                                echo "<option value='{$type['room_type_id']}' $selected>" . htmlspecialchars($type['room_type_name']) . "</option>";
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="form-group mb-3">
                                                                    <label class="font-weight-bold small">Room Description</label>
                                                                    <textarea name="room_description" class="form-control" rows="3"><?= htmlspecialchars($room['room_description']) ?></textarea>
                                                                </div>

                                                                <div class="form-row">
                                                                    <div class="form-group col-md-4">
                                                                        <label class="font-weight-bold small">Price (₱)</label>
                                                                        <input type="number" name="price" class="form-control" value="<?= $room['price'] ?>" step="0.01" required>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label class="font-weight-bold small">Max Guests</label>
                                                                        <input type="number" name="max_guest" class="form-control" value="<?= $room['max_guest'] ?>" required>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label class="font-weight-bold small">Quantity (Total Rooms)</label>
                                                                        <input type="number" name="quantity" class="form-control" value="<?= $room['quantity'] ?>" required>
                                                                        <small class="text-muted">Updating this value will reset 'Available' rooms to this quantity.</small>
                                                                    </div>
                                                                </div>

                                                                <div class="form-group">
                                                                    <label class="font-weight-bold small">Room Image (Optional)</label>
                                                                    <input type="file" name="image" class="form-control" accept="image/*">
                                                                    <small class="text-muted">Leave blank to keep the current image. Current image: <a href="<?= htmlspecialchars($room['image']) ?>" target="_blank"><?= basename($room['image']) ?></a></small>
                                                                </div>

                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                <button type="submit" name="update_room" class="btn btn-warning">Save Changes</button>
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

    <!-- Add Room Modal (Moved outside the PHP loop) -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Add New Room</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body row">
                        <!-- Left Column (Name, Type, Description) -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Room Name</label>
                                <input type="text" name="room_name" oninput="this.value = this.value.toUpperCase();" class="form-control" placeholder="E.g., DELUXE A" required>
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Room Type</label>
                                <select name="room_type_id" class="form-control" required>
                                    <option value="">Select Room Type</option>
                                    <?php
                                    $types = mysqli_query($conn, "SELECT * FROM room_type_tbl");
                                    while ($type = mysqli_fetch_assoc($types)) {
                                        echo "<option value='{$type['room_type_id']}'>" . htmlspecialchars($type['room_type_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Room Description</label>
                                <textarea name="room_description" class="form-control" rows="3" placeholder="Brief description of the room and amenities."></textarea>
                            </div>
                        </div>

                        <!-- Right Column (Details) -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Price (₱) <small>(Per Night)</small></label>
                                <input type="number" name="price" class="form-control" step="0.01" placeholder="Enter Price" required>
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Max Guests</label>
                                <input type="number" name="max_guest" class="form-control" placeholder="Max number of guests" required>
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Quantity (Total Rooms)</label>
                                <input type="number" name="quantity" class="form-control" placeholder="Total quantity of this room type" required>
                                <small class="text-muted">This sets the initial 'Available' count.</small>
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Room Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_room" class="btn btn-success">Save Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>