<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC ---

if (isset($_POST['add_rental'])) {
    $name  = $_POST['rental_name'];
    $desc  = $_POST['rental_description'];
    $price = $_POST['rental_price'];
    $hours = $_POST['hours'];
    $imageName = null;
    $targetDir = "uploads/rentals/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    if (!empty($_FILES['rental_image']['name'])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['rental_image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $imageName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['rental_image']['name']);
            $target = $targetDir . $imageName;
            move_uploaded_file($_FILES['rental_image']['tmp_name'], $target);
        }
    }
    $sql = "INSERT INTO rentals_tbl (rental_name, rental_description, rental_price, rental_image,hours) 
             VALUES (?, ?, ?, ?,?)";
    $stmt = $conn->prepare($sql);
    // Bind types: s (name), s (desc), d (price), s (image), i (hours) - assuming hours is an integer
    $stmt->bind_param("ssdsi", $name, $desc, $price, $imageName, $hours);
    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:2000,timerProgressBar:true});
                Toast.fire({icon:"success",title:"The Rental details were Successfully Saved!"});
            });
        </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:3000,timerProgressBar:true});
                Toast.fire({icon:"error",title:"Failed to Save Rental: ' . $stmt->error . '"});
            });
        </script>';
    }
}

if (isset($_POST['update_rental'])) {
    $id    = $_POST['rental_id'];
    $name  = $_POST['rental_name'];
    $desc  = $_POST['rental_description'];
    $price = $_POST['rental_price'];
    $hours = $_POST['hours'];
    $imageName = null;
    $targetDir = "uploads/rentals/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    // Check if a new image was uploaded
    if (!empty($_FILES['rental_image']['name'])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['rental_image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $imageName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['rental_image']['name']);
            $target = $targetDir . $imageName;
            move_uploaded_file($_FILES['rental_image']['tmp_name'], $target);

            // Delete old image if exists
            $oldImgQuery = $conn->query("SELECT rental_image FROM rentals_tbl WHERE rental_id = '$id'");
            if ($oldImgQuery && $oldImgQuery->num_rows > 0) {
                $oldImg = $oldImgQuery->fetch_assoc()['rental_image'];
                if ($oldImg && file_exists($targetDir . $oldImg)) {
                    unlink($targetDir . $oldImg);
                }
            }

            // Update query including new image
            $sql = "UPDATE rentals_tbl 
                        SET rental_name = ?, rental_description = ?, rental_price = ?, rental_image = ?, hours = ?
                      WHERE rental_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdsii", $name, $desc, $price, $imageName, $hours, $id);
        } else {
            // Handle case where uploaded file type is invalid
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:3000,timerProgressBar:true});
                    Toast.fire({icon:"error",title:"Invalid image file type! Update failed."});
                });
            </script>';
            return;
        }
    }

    // Update query excluding image (if $imageName is null)
    if (!$imageName) {
        $sql = "UPDATE rentals_tbl 
                    SET rental_name = ?, rental_description = ?, rental_price = ?, hours = ?
                  WHERE rental_id = ?";
        $stmt = $conn->prepare($sql);
        // Bind types: s (name), s (desc), d (price), i (hours), i (id)
        $stmt->bind_param("ssdi", $name, $desc, $price, $hours, $id);
    }

    // Execute the final prepared statement
    if ($stmt->execute()) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:2000,timerProgressBar:true});
                Toast.fire({icon:"success",title:"The Rental details were Successfully Updated!"});
            });
        </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({toast:true,position:"top-end",showConfirmButton:false,timer:3000,timerProgressBar:true});
                Toast.fire({icon:"error",title:"Failed to Update Rental: ' . $stmt->error . '"});
            });
        </script>';
    }
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
                        <h1 class="h3 mb-0 text-gray-800">Equipment & Gear Rentals</h1>
                    </div>

                    <div class="row">

                        <!-- LEFT COLUMN: Add Rental Form -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-success text-white">
                                    <h6 class="m-0 font-weight-bold">Add New Rental Gear</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST" enctype="multipart/form-data" autocomplete="off">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Rental Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="rental_name" placeholder="E.g., Snorkel Set, Kayak" required>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small">Description <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="rental_description" rows="2" placeholder="Describe the item or usage." required></textarea>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-md-6 mb-3">
                                                <label class="font-weight-bold small">Price (₱) <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="rental_price" step="0.01" placeholder="Price per unit" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="font-weight-bold small">Hours/Duration <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="hours" step="0.5" placeholder="Duration (e.g., 4 or 2.5)" required>
                                            </div>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold small">Image <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" name="rental_image" accept="image/*" required>
                                        </div>
                                        <button type="submit" name="add_rental" class="btn btn-success btn-block">
                                            <i class="fas fa-plus"></i> Add Rental Item
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: List of Rentals -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Current Rental Inventory</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th><i class="fas fa-image"></i> Image</th>
                                                    <th>Rental Name</th>
                                                    <th>Description</th>
                                                    <th><i class="fas fa-peso-sign"></i> Price</th>
                                                    <th><i class="fas fa-clock"></i> Hour/s</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM rentals_tbl ORDER BY rental_name ASC";
                                                $result = mysqli_query($conn, $sql);
                                                while ($res = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td><img src="uploads/rentals/<?= htmlspecialchars($res['rental_image']) ?>" width="60" height="60" style="object-fit: cover;" class="rounded"></td>
                                                        <td><?= htmlspecialchars($res['rental_name']) ?></td>
                                                        <td><?= htmlspecialchars(substr($res['rental_description'], 0, 40)) ?>...</td>
                                                        <td>₱<?= number_format($res['rental_price'], 2) ?></td>
                                                        <td><?= $res['hours'] ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $res['rental_id'] ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- EDIT MODAL -->
                                                    <div class="modal fade" id="edit<?= $res['rental_id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST" enctype="multipart/form-data">
                                                                    <div class="modal-header bg-warning text-white">
                                                                        <h5 class="modal-title">Update Rental: <?= htmlspecialchars($res['rental_name']) ?></h5>
                                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="rental_id" value="<?= $res['rental_id'] ?>">

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Rental Name</label>
                                                                            <input type="text" name="rental_name" value="<?= htmlspecialchars($res['rental_name']) ?>" class="form-control" required>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Description</label>
                                                                            <textarea name="rental_description" class="form-control" rows="2" required><?= htmlspecialchars($res['rental_description']) ?></textarea>
                                                                        </div>

                                                                        <div class="form-row">
                                                                            <div class="form-group col-md-6 mb-3">
                                                                                <label class="font-weight-bold small">Price (₱)</label>
                                                                                <input type="number" step="0.01" name="rental_price" value="<?= $res['rental_price'] ?>" class="form-control" required>
                                                                            </div>
                                                                            <div class="form-group col-md-6 mb-3">
                                                                                <label class="font-weight-bold small">Hours/Duration</label>
                                                                                <input type="number" name="hours" class="form-control" value="<?= $res['hours'] ?>" step="0.5" required>
                                                                            </div>
                                                                        </div>

                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Change Image (Optional)</label><br>
                                                                            <img src="uploads/rentals/<?= htmlspecialchars($res['rental_image']) ?>" width="80" height="60" style="object-fit: cover;" class="rounded mb-2"><br>
                                                                            <input type="file" name="rental_image" class="form-control" accept="image/*">
                                                                            <small class="text-muted">Current Image: <?= htmlspecialchars($res['rental_image']) ?> (Leave blank to keep current)</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_rental" class="btn btn-warning">Save Changes</button>
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