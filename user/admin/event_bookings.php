<?php
include '../../config.php';

// Handle booking status update
if (isset($_POST['update_booking_status'])) {
    $booking_id = intval($_POST['event_booking_id']);
    $status = $_POST['status'];
    $notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);

    $update_query = "UPDATE event_booking_tbl SET status = '$status', admin_notes = '$notes' WHERE event_booking_id = $booking_id";
    if (mysqli_query($conn, $update_query)) {
        echo json_encode(['status' => 'success', 'message' => 'Booking updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update booking']);
    }
    exit;
}

// Handle booking deletion
if (isset($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM event_booking_tbl WHERE event_booking_id = $booking_id");
    header("Location: event_bookings.php");
    exit;
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$status_filter = '';
if ($filter == 'pending') $status_filter = "AND eb.status = 'Pending'";
elseif ($filter == 'approved') $status_filter = "AND eb.status = 'Approved'";
elseif ($filter == 'rejected') $status_filter = "AND eb.status = 'Rejected'";

// Fetch bookings with event and reservation details
$query = "SELECT eb.*, et.event_name, et.event_date, et.event_time, et.event_end_time,
                 rt.tracking_number, rt.guest_name, rt.guest_email, rt.guest_phone
          FROM event_booking_tbl eb
          LEFT JOIN event_tbl et ON eb.event_id = et.event_id
          LEFT JOIN reservation_tbl rt ON eb.tracking_number = rt.tracking_number
          WHERE 1=1 $status_filter
          ORDER BY eb.booked_date DESC";

$bookings_result = mysqli_query($conn, $query);
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Event Booking Requests</h1>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="mb-4">
                        <a href="event_bookings.php?filter=all" class="btn <?= ($filter == 'all') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">
                            <i class="fas fa-list"></i> All
                        </a>
                        <a href="event_bookings.php?filter=pending" class="btn <?= ($filter == 'pending') ? 'btn-warning' : 'btn-outline-warning'; ?> btn-sm">
                            <i class="fas fa-clock"></i> Pending
                        </a>
                        <a href="event_bookings.php?filter=approved" class="btn <?= ($filter == 'approved') ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                            <i class="fas fa-check"></i> Approved
                        </a>
                        <a href="event_bookings.php?filter=rejected" class="btn <?= ($filter == 'rejected') ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm">
                            <i class="fas fa-times"></i> Rejected
                        </a>
                    </div>

                    <!-- Bookings Table -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Guest Name</th>
                                            <th>Event</th>
                                            <th>Event Date</th>
                                            <th>Time</th>
                                            <th>Guests</th>
                                            <th>Booking Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        while ($booking = mysqli_fetch_assoc($bookings_result)) {
                                            $status_badge = 'badge-secondary';
                                            if ($booking['status'] == 'Pending') $status_badge = 'badge-warning';
                                            elseif ($booking['status'] == 'Approved') $status_badge = 'badge-success';
                                            elseif ($booking['status'] == 'Rejected') $status_badge = 'badge-danger';
                                        ?>
                                            <tr>
                                                <td>#<?= $booking['event_booking_id'] ?></td>
                                                <td><strong><?= htmlspecialchars($booking['guest_name']) ?></strong></td>
                                                <td><?= htmlspecialchars($booking['event_name'] ?? 'N/A') ?></td>
                                                <td><?= date('M d, Y', strtotime($booking['event_date'])) ?></td>
                                                <td>
                                                    <?= date('h:i A', strtotime($booking['event_time'])) ?>
                                                    <?php if (!empty($booking['event_end_time'])) { 
                                                        echo "- " . date('h:i A', strtotime($booking['event_end_time'])); 
                                                    } ?>
                                                </td>
                                                <td><span class="badge badge-info"><?= $booking['number_of_guests'] ?></span></td>
                                                <td><?= date('M d, Y h:i A', strtotime($booking['booked_date'])) ?></td>
                                                <td><span class="badge <?= $status_badge ?>"><?= $booking['status'] ?></span></td>
                                                <td>
                                                    <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewBooking<?= $booking['event_booking_id'] ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <a href="event_bookings.php?delete=<?= $booking['event_booking_id'] ?>" onclick="return confirm('Delete this booking?');" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>

                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewBooking<?= $booking['event_booking_id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title">Event Booking Details</h5>
                                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <h6 class="font-weight-bold text-primary mb-3">Guest Information</h6>
                                                                    <p><strong>Name:</strong> <?= htmlspecialchars($booking['guest_name']) ?></p>
                                                                    <p><strong>Email:</strong> <?= htmlspecialchars($booking['guest_email']) ?></p>
                                                                    <p><strong>Phone:</strong> <?= htmlspecialchars($booking['guest_phone']) ?></p>
                                                                    <p><strong>Reservation:</strong> #<?= htmlspecialchars($booking['tracking_number']) ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6 class="font-weight-bold text-primary mb-3">Event Information</h6>
                                                                    <p><strong>Event:</strong> <?= htmlspecialchars($booking['event_name']) ?></p>
                                                                    <p><strong>Date:</strong> <?= date('F d, Y', strtotime($booking['event_date'])) ?></p>
                                                                    <p><strong>Time:</strong> <?= date('h:i A', strtotime($booking['event_time'])) ?> 
                                                                        <?php if (!empty($booking['event_end_time'])) { 
                                                                            echo "- " . date('h:i A', strtotime($booking['event_end_time'])); 
                                                                        } ?>
                                                                    </p>
                                                                    <p><strong>Guests:</strong> <span class="badge badge-info"><?= $booking['number_of_guests'] ?></span></p>
                                                                </div>
                                                            </div>

                                                            <?php if (!empty($booking['special_requests'])) { ?>
                                                                <hr>
                                                                <h6 class="font-weight-bold">Special Requests</h6>
                                                                <p class="text-muted"><?= htmlspecialchars($booking['special_requests']) ?></p>
                                                            <?php } ?>

                                                            <hr>
                                                            <form class="booking-update-form" data-booking-id="<?= $booking['event_booking_id'] ?>">
                                                                <div class="form-group">
                                                                    <label class="font-weight-bold">Status</label>
                                                                    <select name="status" class="form-control" required>
                                                                        <option value="Pending" <?= ($booking['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                                                        <option value="Approved" <?= ($booking['status'] == 'Approved') ? 'selected' : '' ?>>Approved</option>
                                                                        <option value="Rejected" <?= ($booking['status'] == 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                                                                        <option value="Cancelled" <?= ($booking['status'] == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="font-weight-bold">Admin Notes</label>
                                                                    <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add notes for reference..."><?= htmlspecialchars($booking['admin_notes'] ?? '') ?></textarea>
                                                                </div>
                                                            </form>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            <button type="button" class="btn btn-primary" onclick="saveEventBooking(<?= $booking['event_booking_id'] ?>)">Save Changes</button>
                                                        </div>
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
        function saveEventBooking(bookingId) {
            const form = document.querySelector(`form[data-booking-id="${bookingId}"]`);
            const formData = new FormData(form);
            formData.append('event_booking_id', bookingId);
            formData.append('update_booking_status', '1');

            fetch('event_bookings.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Success!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    </script>
</body>

</html>
