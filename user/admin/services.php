<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC ---

if (isset($_POST['update_service'])) {
    $id    = $_POST['service_id'];
    $name  = $_POST['service_name'];
    $desc  = $_POST['service_description'];
    $price = $_POST['service_price'];
    $status = $_POST['service_status']; // 0 or 1
    $imageName = null;

    if (!empty($_FILES['service_image']['name'])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed_ext)) {
            // Sanitize filename and add timestamp
            $imageName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['service_image']['name']);
            $target = "uploads/" . $imageName;
            if (!is_dir("uploads/")) {
                mkdir("uploads/", 0777, true);
            }
            move_uploaded_file($_FILES['service_image']['tmp_name'], $target);
        }
    }

    if ($imageName) {
        $sql = "UPDATE services_tbl 
                    SET service_name = ?, service_description = ?, service_price = ?, service_image = ?, status = ?
                  WHERE service_id = ?";
        $stmt = $conn->prepare($sql);
        // Bind types: s (name), s (desc), d (price), s (image), i (status), i (id)
        $stmt->bind_param("ssdsii", $name, $desc, $price, $imageName, $status, $id);
    } else {
        $sql = "UPDATE services_tbl 
                    SET service_name = ?, service_description = ?, service_price = ?, status = ?
                  WHERE service_id = ?";
        $stmt = $conn->prepare($sql);
        // Bind types: s (name), s (desc), d (price), i (status), i (id)
        $stmt->bind_param("ssdi", $name, $desc, $price, $status, $id);
    }

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:2000,timerProgressBar:true});
                Toast.fire({icon:"success",title:"The Service details were Successfully Updated!"});
            });
        </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:3000,timerProgressBar:true});
                Toast.fire({icon:"error",title:"The Services details Failed to Update: ' . $stmt->error . '"});
            });
        </script>';
    }
}

if (isset($_POST['add_service'])) {
    $name  = $_POST['service_name'];
    $desc  = $_POST['service_description'];
    $price = $_POST['service_price'];
    $status = 1; // Default to Available (1) when adding
    $imageName = null;

    // Image Upload Logic
    if (!empty($_FILES['service_image']['name'])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed_ext)) {
            $imageName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['service_image']['name']);
            $target = "uploads/" . $imageName;
            if (!is_dir("uploads/")) {
                mkdir("uploads/", 0777, true);
            }
            move_uploaded_file($_FILES['service_image']['tmp_name'], $target);
        }
    }

    // Insert Logic
    $sql = "INSERT INTO services_tbl (service_name, service_description, service_price, service_image, status) 
             VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    // Bind types: s (name), s (desc), d (price), s (image), i (status)
    $stmt->bind_param("ssdsi", $name, $desc, $price, $imageName, $status);

    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:2000,timerProgressBar:true});
                Toast.fire({icon:"success",title:"The Services details were Successfully Saved!"});
            });
        </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:3000,timerProgressBar:true});
                Toast.fire({icon:"error",title:"Failed to Save Service: ' . $stmt->error . '"});
            });
        </script>';
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
                        <h1 class="h3 mb-0 text-gray-800">Additional Services Management</h1>
                    </div>

                    <div class="row">

                        <!-- LEFT COLUMN: Add Service Form -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-success text-white">
                                    <h6 class="m-0 font-weight-bold">Add New Service</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST" enctype="multipart/form-data" autocomplete="off">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Service Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="service_name" placeholder="E.g., MASSAGE, SNORKEL RENTAL" oninput="this.value = this.value.toUpperCase();" required>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Description <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="service_description" rows="3" placeholder="Brief description and duration." required></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Price (₱) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="service_price" step="0.01" placeholder="Enter Price" required>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold small">Image <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" name="service_image" accept="image/*" required>
                                            <small class="text-muted">A clear image helps customers choose.</small>
                                        </div>
                                        <button type="submit" name="add_service" class="btn btn-success btn-block">
                                            <i class="fas fa-plus"></i> Add Service
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: List of Services -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Service Price List</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th><i class="fas fa-check-circle"></i> Status</th>
                                                    <th><i class="fas fa-image"></i> Image</th>
                                                    <th>Service Name</th>
                                                    <th>Description</th>
                                                    <th>Price (₱)</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM services_tbl ORDER BY service_name ASC";
                                                $result = mysqli_query($conn, $sql);
                                                while ($res = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($res['status'] == 1): ?>
                                                                <span class="badge badge-success">Available</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-danger">Not Available</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><img src="uploads/<?= htmlspecialchars($res['service_image']) ?>" width="60" height="60" style="object-fit: cover;" class="rounded"></td>
                                                        <td><?= htmlspecialchars($res['service_name']) ?></td>
                                                        <td><?= htmlspecialchars(substr($res['service_description'], 0, 40)) ?>...</td>
                                                        <td>₱<?= number_format($res['service_price'], 2) ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $res['service_id'] ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- Edit Modal (Dynamic ID) -->
                                                    <div class="modal fade" id="edit<?= $res['service_id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST" enctype="multipart/form-data">
                                                                    <div class="modal-header bg-warning text-white">
                                                                        <h5 class="modal-title">Update Service: <?= htmlspecialchars($res['service_name']) ?></h5>
                                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="service_id" value="<?= $res['service_id'] ?>">

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Service Name</label>
                                                                            <input type="text" name="service_name" value="<?= htmlspecialchars($res['service_name']) ?>" class="form-control" required>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Description</label>
                                                                            <textarea name="service_description" class="form-control" rows="2" required><?= htmlspecialchars($res['service_description']) ?></textarea>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Price (₱)</label>
                                                                            <input type="number" step="0.01" name="service_price" value="<?= $res['service_price'] ?>" class="form-control" required>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Status</label>
                                                                            <select name="service_status" class="form-control" required>
                                                                                <option value="1" <?= $res['status'] == 1 ? 'selected' : '' ?>>Available</option>
                                                                                <option value="0" <?= $res['status'] == 0 ? 'selected' : '' ?>>Not Available</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Change Image (Optional)</label><br>
                                                                            <img src="uploads/<?= htmlspecialchars($res['service_image']) ?>" width="80" height="60" style="object-fit: cover;" class="rounded mb-2"><br>
                                                                            <input type="file" name="service_image" class="form-control" accept="image/*">
                                                                            <small class="text-muted">Current Image: <?= htmlspecialchars($res['service_image']) ?> (Leave blank to keep current)</small>
                                                                        </div>

                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_service" class="btn btn-warning">Save Changes</button>
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