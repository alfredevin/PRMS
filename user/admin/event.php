<?php
include '../../config.php';

// --- PHP PROCESSING LOGIC ---

if (isset($_POST['add_event'])) {
    $name = $_POST['event_name'];
    $desc = $_POST['description'];
    $date = $_POST['event_date'];
    $time = $_POST['event_time'];
    $end_time = !empty($_POST['event_end_time']) ? $_POST['event_end_time'] : NULL;

    // Check for event name and date conflict
    $check_query = "SELECT * FROM event_tbl WHERE event_name = ? AND event_date = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $name, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
                Toast.fire({
                    icon: "error",
                    title: "Error: Event with that name already scheduled for this date!"
                });
            });
        </script>';
    } else {
        $insert_query = "INSERT INTO event_tbl (event_name, description, event_date, event_time, event_end_time) 
                             VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssss", $name, $desc, $date, $time, $end_time);
        if ($stmt->execute()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                    Toast.fire({
                        icon: "success",
                        title: "Event successfully Saved!"
                    });
                });
            </script>';
        } else {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                    Toast.fire({
                        icon: "error",
                        title: "Failed to save event: ' . $stmt->error . '"
                    });
                });
            </script>';
        }
    }
}

if (isset($_POST['update_event'])) {
    $id   = $_POST['event_id'];
    $name = $_POST['event_name'];
    $desc = $_POST['description'];
    $date = $_POST['event_date'];
    $time = $_POST['event_time'];
    $end_time = !empty($_POST['event_end_time']) ? $_POST['event_end_time'] : NULL;

    // Check for duplicate name/date excluding the current event being edited
    $check_query = "SELECT * FROM event_tbl WHERE event_name = ? AND event_date = ? AND event_id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ssi", $name, $date, $id);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
                Toast.fire({
                    icon: "error",
                    title: "Error: Event conflict detected for that date!"
                });
            });
        </script>';
    } else {
        $update_query = "UPDATE event_tbl 
                         SET event_name = ?, description = ?, event_date = ?, event_time = ?, event_end_time = ?
                         WHERE event_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssi", $name, $desc, $date, $time, $end_time, $id);

        if ($stmt->execute()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                    Toast.fire({
                        icon: "success",
                        title: "Event successfully updated!"
                    });
                });
            </script>';
        } else {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                    });
                    Toast.fire({
                        icon: "error",
                        title: "Failed to Update. Error: ' . $stmt->error . '"
                    });
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

                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Resort Events Schedule</h1>
                    </div>

                    <div class="row">

                        <!-- LEFT COLUMN: Add Event Form -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-success text-white">
                                    <h6 class="m-0 font-weight-bold">Add New Event</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST" autocomplete="off">
                                        <div class="form-group mb-3">
                                            <label for="event_name" class="font-weight-bold small">Event Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="event_name"
                                                placeholder="E.g., BEACH VOLLEYBALL TOURNAMENT" required oninput="this.value = this.value.toUpperCase();">
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group mb-3 col-6">
                                                <label for="event_date" class="font-weight-bold small">Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="event_date_input" name="event_date" min="<?= date('Y-m-d') ?>" required>
                                            </div>
                                            <div class="form-group mb-3 col-6">
                                                <label for="event_time" class="font-weight-bold small">Start Time <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" name="event_time" required>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="event_end_time" class="font-weight-bold small">End Time (Optional)</label>
                                            <input type="time" class="form-control" name="event_end_time" placeholder="When does the event end?">
                                        </div>

                                        <div class="form-group mb-4">
                                            <label for="description" class="font-weight-bold small">Description / Location</label>
                                            <textarea class="form-control" name="description" rows="3" placeholder="Details or specific location (e.g., Near Pool Deck)"></textarea>
                                        </div>

                                        <button type="submit" name="add_event" class="btn btn-success btn-block">
                                            <i class="fas fa-calendar-plus"></i> Schedule Event
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: List of Events -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Upcoming Events List</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Event Name</th>
                                                    <th>Description</th>
                                                    <th>Date</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM event_tbl ORDER BY event_date ASC, event_time ASC";
                                                $result = mysqli_query($conn, $sql);
                                                while ($res = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($res['event_name']) ?></td>
                                                        <td><?= htmlspecialchars($res['description']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($res['event_date'])) ?></td>
                                                        <td><?= date('h:i A', strtotime($res['event_time'])) ?></td>
                                                        <td><?= !empty($res['event_end_time']) ? date('h:i A', strtotime($res['event_end_time'])) : '-' ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#edit<?= $res['event_id'] ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- Edit Modal (Dynamic ID) -->
                                                    <div class="modal fade" id="edit<?= $res['event_id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning text-white">
                                                                    <h5 class="modal-title">Update Event: <?= htmlspecialchars($res['event_name']) ?></h5>
                                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="event_id" value="<?= $res['event_id'] ?>">
                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Event Name</label>
                                                                            <input type="text" name="event_name" class="form-control"
                                                                                value="<?= htmlspecialchars($res['event_name']) ?>" oninput="this.value = this.value.toUpperCase();" required>
                                                                        </div>
                                                                        <div class="form-row">
                                                                            <div class="form-group col-6 mb-3">
                                                                                <label class="font-weight-bold small">Date</label>
                                                                                <input type="date" name="event_date" class="form-control" value="<?= $res['event_date'] ?>" min="<?= date('Y-m-d') ?>" required>
                                                                            </div>
                                                                            <div class="form-group col-6 mb-3">
                                                                                <label class="font-weight-bold small">Start Time</label>
                                                                                <input type="time" name="event_time" class="form-control" value="<?= $res['event_time'] ?>" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">End Time (Optional)</label>
                                                                            <input type="time" name="event_end_time" class="form-control" value="<?= $res['event_end_time'] ?? '' ?>">
                                                                        </div>
                                                                        <div class="form-group mb-3">
                                                                            <label class="font-weight-bold small">Description</label>
                                                                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($res['description']) ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer mt-4">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                        <button type="submit" name="update_event" class="btn btn-warning">Save Changes</button>
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

    <script>
        // Client-side validation to ensure event date is not in the past
        document.addEventListener("DOMContentLoaded", function() {
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('event_date_input');
            if (dateInput) {
                dateInput.setAttribute('min', today);
            }
        });
    </script>
</body>

</html>