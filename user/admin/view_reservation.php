<?php
include '../../config.php';

// Helper function for status badges
function getStatusBadge($status)
{
    switch ($status) {
        case 1:
            return '<span class="badge badge-warning text-dark"><i class="fas fa-clock me-1"></i> Pending Confirmation</span>';
        case 2:
            return '<span class="badge badge-success"><i class="fas fa-check-circle me-1"></i> Confirmed & Reserved</span>';
        case 3:
            return '<span class="badge badge-info"><i class="fas fa-bed me-1"></i> Checked-In / Stay-in</span>';
        case 4:
            return '<span class="badge badge-primary"><i class="fas fa-sign-out-alt me-1"></i> Checked Out / Completed</span>';
        case 5:
            return '<span class="badge badge-danger"><i class="fas fa-times-circle me-1"></i> Cancelled</span>';
        default:
            return '<span class="badge badge-secondary">Unknown</span>';
    }
}

// Helper function for formatting currency
function formatCurrency($amount)
{
    return '₱' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include './../template/header.php' ?>

<style>
    .card-header-custom {
        background: linear-gradient(45deg, #4e73df, #224abe);
        color: white;
    }

    .info-label {
        font-weight: 600;
        color: #5a5c69;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 1rem;
        color: #2e2f3e;
        font-weight: 500;
    }

    .proof-img {
        cursor: pointer;
        transition: transform 0.2s;
        border: 2px solid #eaecf4;
    }

    .proof-img:hover {
        transform: scale(1.02);
        border-color: #4e73df;
    }

    .total-card {
        background-color: #f8f9fc;
        border-left: 4px solid #1cc88a;
    }

    .section-title {
        border-bottom: 2px solid #e3e6f0;
        padding-bottom: 10px;
        margin-bottom: 20px;
        color: #4e73df;
        font-weight: bold;
    }
</style>

<body id="page-top">
    <div id="wrapper">
        <?php include './../template/sidebar.php' ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './../template/navbar.php'; ?>
                <div class="container-fluid">

                    <?php
                    if (!isset($_GET['tracking'])) {
                        echo "<div class='alert alert-danger'>Invalid request. Tracking number missing.</div>";
                        exit;
                    }

                    $tracking = mysqli_real_escape_string($conn, $_GET['tracking']);

                    // --- MAIN RESERVATION FETCH ---
                    $sql = "SELECT r.*, p.payment_option, rm.room_name, rm.price as room_rate, rm.image as room_image, rt.room_type_name
                            FROM reservation_tbl r
                            JOIN rooms_tbl rm ON r.room_id = rm.room_id
                            INNER JOIN  reservation_payments_tbl p ON r.tracking_number = p.tracking_number
                            JOIN room_type_tbl rt ON rm.room_type_id = rt.room_type_id 
                            WHERE r.tracking_number = '$tracking' LIMIT 1";

                    $result = mysqli_query($conn, $sql);
                    if (mysqli_num_rows($result) == 0) {
                        echo "<div class='alert alert-warning'>Reservation not found.</div>";
                        exit;
                    }

                    $res = mysqli_fetch_assoc($result);
                    $reservation_id = $res['reservation_id'];

                    // Dates
                    $checkIn = date("M d, Y", strtotime($res['check_in']));
                    $checkOut = date("M d, Y", strtotime($res['check_out']));
                    $createdAt = date("M d, Y h:i A", strtotime($res['created_at']));

// --- EVENT BOOKING (if any) ---
$event_booking = null;
$ev_q = mysqli_query($conn, "SELECT eb.*, et.event_name, et.event_date, et.event_time, et.event_end_time
                             FROM event_booking_tbl eb
                             JOIN event_tbl et ON eb.event_id = et.event_id
                             WHERE eb.tracking_number = '$tracking' LIMIT 1");
if (mysqli_num_rows($ev_q) > 0) {
    $event_booking = mysqli_fetch_assoc($ev_q);
}
                    // Get Loan Charges (from reservation_equipment_tbl)
                    $loan_charges_query = mysqli_query($conn, "
                        SELECT 
                            req.quantity_loaned, 
                            req.unit_price, 
                            (req.quantity_loaned * req.unit_price) AS loan_total,
                            eq.equipment_name,
                            req.loan_status
                        FROM reservation_equipment_tbl req
                        JOIN equipment_tbl eq ON req.equipment_id = eq.equipment_id
                        WHERE req.reservation_id = '$reservation_id'
                    ");
                    $loan_charges = mysqli_fetch_all($loan_charges_query, MYSQLI_ASSOC);
                    $total_loan_charge = array_sum(array_column($loan_charges, 'loan_total'));

                    // Get initial payment detail for display
                    $initial_payment_detail_query = mysqli_query($conn, "
                        SELECT p.reference_number, p.proof_image,p.payment_option, p.amount as amount_paid_detail, pt.payment_type_name
                        FROM reservation_tbl r
                        LEFT JOIN reservation_payments_tbl p ON r.tracking_number = p.tracking_number
                        LEFT JOIN payment_type_tbl pt ON pt.payment_type_id = p.payment_type
                        WHERE r.tracking_number = '$tracking'
                        ORDER BY p.created_at ASC LIMIT 1
                    ");
                    $initial_payment_detail = mysqli_fetch_assoc($initial_payment_detail_query);


                    // --- FINAL BALANCE CALCULATION ---
                    $final_total_due = $res['total_price'] + $total_loan_charge;

                    // FIX: If status is 4 (Checked Out), force balance to 0 (Paid)
                    if ($res['status'] == 4) {
                        $final_balance = 0;
                        // Optional: Visually adjust 'total_paid' to match total due if you want it to look balanced in records, 
                        // but keeping actual DB records is usually better for auditing. 
                        // Here we just ensure the 'Balance Due' shows 0.
                    } else {
                        $final_balance = $final_total_due - $total_paid;
                    }
                    ?>

                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Reservation Details</h1>
                        <div>
                            <a href="<?= $_SERVER['HTTP_REFERER'] ?? 'reservation_list.php' ?>"
                                class="btn btn-secondary btn-sm shadow-sm">
                                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
                            </a>

                            <?php if ($res['status'] == 1): ?>
                                <button id="confirmBtn" class="btn btn-success btn-sm shadow-sm">
                                    <i class="fas fa-check fa-sm text-white-50"></i> Confirm Booking
                                </button>
                            <?php endif; ?>

                            <?php if ($res['status'] == 4): ?>
                                <a href="./report/print_reciept?tracking=<?= $tracking ?>" target="_blank"
                                    class="btn btn-primary btn-sm shadow-sm">
                                    <i class="fas fa-print fa-sm text-white-50"></i> Print Receipt
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <!-- LEFT COLUMN: Guest, Room, Inclusions -->
                        <div class="col-lg-8">

                            <!-- Status Card -->
                            <div class="card shadow mb-4 border-left-primary">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span
                                            class="text-xs font-weight-bold text-primary text-uppercase mb-1">Reservation
                                            Status</span>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= getStatusBadge($res['status']) ?></div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tracking
                                            Number</span>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                                            #<?= $res['tracking_number'] ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Guest & Room Details -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i
                                            class="fas fa-user-tag me-2"></i>Guest & Room Info</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-4 text-center">
                                            <img src="<?= $res['room_image'] ?>"
                                                class="img-fluid rounded shadow-sm mb-2"
                                                style="max-height: 150px; width: 100%; object-fit: cover;">
                                            <p class="font-weight-bold text-primary mb-0"><?= $res['room_name'] ?></p>
                                            <small class="text-muted"><?= $res['room_type_name'] ?></small>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <div class="info-label">Guest Name</div>
                                                    <div class="info-value"><?= $res['guest_name'] ?></div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="info-label">Contact</div>
                                                    <div class="info-value"><?= $res['guest_phone'] ?></div>
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <div class="info-label">Email</div>
                                                    <div class="info-value"><?= $res['guest_email'] ?></div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="info-label">Check-In</div>
                                                    <div class="info-value text-success"><i
                                                            class="fas fa-calendar-check me-1"></i> <?= $checkIn ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="info-label">Check-Out</div>
                                                    <div class="info-value text-danger"><i
                                                            class="fas fa-calendar-times me-1"></i> <?= $checkOut ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($event_booking): 
                                            // determine badge color for event status
                                            $evt_badge='badge-secondary';
                                            if ($event_booking['status']=='Pending') $evt_badge='badge-warning';
                                            elseif ($event_booking['status']=='Approved') $evt_badge='badge-success';
                                            elseif ($event_booking['status']=='Rejected') $evt_badge='badge-danger';
                                            elseif ($event_booking['status']=='Cancelled') $evt_badge='badge-dark';
                                        ?>
                                        <h6 class="section-title">Associated Event</h6>
                                        <div class="mb-3">
                                            <div class="info-label">Booking ID</div>
                                            <div class="info-value">#<?= $event_booking['event_booking_id'] ?> <a href="event_bookings.php?filter=all#" target="_blank"><i class="fas fa-external-link-alt fa-sm"></i></a></div>
                                            <div class="info-label">Event Name</div>
                                            <div class="info-value"><?= htmlspecialchars($event_booking['event_name']) ?></div>
                                            <div class="info-label">Date</div>
                                            <div class="info-value"><?= date('M d, Y', strtotime($event_booking['event_date'])) ?></div>
                                            <div class="info-label">Time</div>
                                            <div class="info-value"><?= date('h:i A', strtotime($event_booking['event_time'])) ?><?php if (!empty($event_booking['event_end_time'])) echo ' - '.date('h:i A', strtotime($event_booking['event_end_time'])); ?></div>
                                            <div class="info-label">Guests</div>
                                            <div class="info-value"><span class="badge badge-info"><?= $event_booking['number_of_guests'] ?></span></div>
                                            <div class="info-label">Status</div>
                                            <div class="info-value"><span class="badge <?= $evt_badge ?>"><?= htmlspecialchars($event_booking['status']) ?></span></div>
                                        </div>
                                        <hr>
                                    <?php endif; ?>

                                    <!-- Guest List Table -->
                                    <h6 class="section-title">Guest Breakdown (<?= $res['guests'] ?> Total)</h6>
                                    <div class="table-responsive mb-3">
                                        <?php
                                        $sql_guests = "SELECT * FROM reservation_guests_tbl WHERE reservation_id = '$reservation_id'";
                                        $res_guests = mysqli_query($conn, $sql_guests);

                                        if (mysqli_num_rows($res_guests) > 0) {
                                            echo "<table class='table table-bordered table-sm text-center'>";
                                            echo "<thead class='bg-light text-primary'>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Age</th>
                    <th>Gender</th>
                </tr>
              </thead>
              <tbody>";
                                            $i = 1;
                                            while ($g = mysqli_fetch_assoc($res_guests)) {
                                                $category = empty($g['category']) ? 'Adult' : htmlspecialchars($g['category']);
                                                $age_val = isset($g['age']) ? $g['age'] : 'N/A';
                                                $gender = $g['gender'] ?? 'N/A';

                                                // Set gender badge color
                                                $gender_class = ($gender == 'Male') ? 'badge-primary' : (($gender == 'Female') ? 'badge-danger' : 'badge-secondary');
                                                $gender_icon = ($gender == 'Male') ? 'fa-mars' : (($gender == 'Female') ? 'fa-venus' : 'fa-genderless');

                                                echo "<tr>
                    <td>{$i}</td>
                    <td><span class='badge badge-info'>{$category}</span></td>
                    <td>{$age_val} yrs old</td>
                    <td>
                        <span class='badge {$gender_class}'>
                            <i class='fas {$gender_icon} me-1'></i> {$gender}
                        </span>
                    </td>
                  </tr>";
                                                $i++;
                                            }
                                            echo "</tbody></table>";
                                        } else {
                                            echo "<div class='alert alert-light border text-center text-muted'><small>No individual guest breakdown found.</small></div>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Inclusions (Services & Rentals & LOANS) -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3 bg-info text-white">
                                            <h6 class="m-0 font-weight-bold"><i class="fas fa-concierge-bell me-1"></i>
                                                Services & Rentals</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php
                                            // SERVICES
                                            $sql_services = "SELECT s.service_name, s.service_price 
                                                            FROM reservation_services_tbl rs 
                                                            JOIN services_tbl s ON rs.service_id = s.service_id 
                                                            WHERE rs.tracking_number = '$tracking'";
                                            $res_services = mysqli_query($conn, $sql_services);

                                            // RENTALS
                                            $sql_rentals = "SELECT rnt.rental_name, rnt.rental_price 
                                                            FROM reservation_rentals_tbl rr 
                                                            JOIN rentals_tbl rnt ON rr.rental_id = rnt.rental_id 
                                                            WHERE rr.tracking_number = '$tracking'";
                                            $res_rentals = mysqli_query($conn, $sql_rentals);

                                            if (mysqli_num_rows($res_services) > 0 || mysqli_num_rows($res_rentals) > 0) {
                                                echo "<ul class='list-group list-group-flush'>";
                                                echo "<li class='list-group-item active'>Services:</li>";
                                                while ($srv = mysqli_fetch_assoc($res_services)) {
                                                    echo "<li class='list-group-item d-flex justify-content-between'>{$srv['service_name']} <span class='badge badge-primary'>+" . formatCurrency($srv['service_price']) . "</span></li>";
                                                }
                                                echo "<li class='list-group-item active'>Rentals:</li>";
                                                while ($rnt = mysqli_fetch_assoc($res_rentals)) {
                                                    echo "<li class='list-group-item d-flex justify-content-between'>{$rnt['rental_name']} <span class='badge badge-primary'>+" . formatCurrency($rnt['rental_price']) . "</span></li>";
                                                }
                                                echo "</ul>";
                                            } else {
                                                echo "<p class='text-center text-muted mt-2'>No additional services or rentals availed.</p>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3 bg-primary text-white">
                                            <h6 class="m-0 font-weight-bold"><i class="fas fa-tools me-1"></i> Equipment
                                                Loan History</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($loan_charges)): ?>
                                                <table class="table table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Item</th>
                                                            <th class="text-center">Qty</th>
                                                            <th class="text-center">Charge</th>
                                                            <th class="text-center">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($loan_charges as $loan): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($loan['equipment_name']) ?></td>
                                                                <td class="text-center"><?= $loan['quantity_loaned'] ?></td>
                                                                <td class="text-center text-danger">
                                                                    <?= formatCurrency($loan['loan_total']) ?></td>
                                                                <td class="text-center">
                                                                    <span
                                                                        class="badge badge-<?= $loan['loan_status'] == 'ACTIVE' ? 'warning' : 'success' ?>">
                                                                        <?= strtoupper($loan['loan_status']) ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <p class="text-center text-muted mt-2">No equipment loans recorded.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Payment Info & Totals -->
                        <div class="col-lg-4">

                            <!-- Payment Summary Card -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i
                                            class="fas fa-calculator me-2"></i>Financial Breakdown</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="info-label">Initial Booking Cost</span>
                                        <span
                                            class="info-value text-gray-800"><?= formatCurrency($res['total_price']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="info-label">Total Equipment Loans</span>
                                        <span class="info-value text-danger">+
                                            <?= formatCurrency($total_loan_charge) ?></span>
                                    </div>
                                    <hr class>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="info-label font-weight-bold">GRAND TOTAL DUE</span>
                                        <span
                                            class="h5 font-weight-bold text-primary">₱<?= number_format($final_total_due, 2) ?></span>
                                    </div>

                                    <?php if ($res['status'] == 4): ?>
                                        <!-- Layout for Completed/Checked Out -->
                                        <div class="alert alert-success text-center">
                                            <h4 class="alert-heading font-weight-bold"><i class="fas fa-check-double"></i>
                                                FULLY PAID</h4>
                                            <p class="mb-0">Transaction Completed</p>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <span>Total Paid:</span>
                                                <strong><?= formatCurrency($final_total_due) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <span>Balance:</span>
                                                <strong>₱0.00</strong>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Layout for Active/Pending -->
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="info-label">Total Paid So Far</span>
                                            <span class="h5 font-weight-bold text-success">-
                                                ₱<?= number_format($total_paid, 2) ?></span>
                                        </div>

                                        <div class="alert <?= ($final_balance > 0) ? 'alert-danger' : 'alert-success' ?>">
                                            <div class="d-flex justify-content-between">
                                                <span class="info-label">BALANCE DUE</span>
                                                <span class="h4 mb-0 font-weight-bold">
                                                    <?= formatCurrency(abs($final_balance)) ?>
                                                </span>
                                            </div>
                                            <small class="mt-1 d-block">
                                                (<?= ($final_balance > 0) ? 'AMOUNT OWED BY GUEST' : 'FULLY PAID' ?>)
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Payment Proof Card -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i
                                            class="fas fa-receipt me-2"></i>Payment Details</h6>
                                </div>
                                <div class="card-body">
                                    <!-- <div class="mb-2">
                                        <span class="info-label">Payment Option:</span>
                                        <span class="float-right badge <?= ($res['payment_option'] == 'full') ? 'badge-success' : 'badge-warning' ?>">
                                            <?= ($res['payment_option'] == 'full') ? 'Full Payment' : '50% Downpayment' ?>
                                        </span>
                                    </div> -->
                                    <div class="mb-2">
                                        <span class="info-label">Method:</span>
                                        <span
                                            class="float-right font-weight-bold"><?= $initial_payment_detail['payment_type_name'] ?? 'N/A' ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="info-label">Reference / Note:</span>
                                        <span
                                            class="float-right font-weight-bold"><?= $initial_payment_detail['reference_number'] ?? 'N/A' ?></span>
                                    </div>

                                    <h6 class="section-title mt-3">Proof of Payment</h6>
                                    <div class="text-center">
                                        <?php if (!empty($initial_payment_detail['proof_image'])): ?>
                                            <img src="./../../website/uploads/<?= $initial_payment_detail['proof_image'] ?>"
                                                class="img-fluid rounded shadow-sm proof-img" style="max-height: 200px;"
                                                alt="Proof of Payment" data-toggle="modal" data-target="#proofModal">
                                            <p class="small text-muted mt-2">Click image to enlarge</p>
                                        <?php else: ?>
                                            <div class="alert alert-secondary">No proof uploaded</div>
                                        <?php endif; ?>
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

    <!-- Modal for Image Preview -->
    <div class="modal fade" id="proofModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Proof</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center bg-dark">
                    <?php if (!empty($initial_payment_detail['proof_image'])): ?>
                        <img src="./../../website/uploads/<?= $initial_payment_detail['proof_image'] ?>" class="img-fluid"
                            style="max-height: 80vh;">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php include './../template/script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Note: The AJAX handler for the 'Confirm Booking' button is specific to status 1 reservations.
        // Since this page handles the details view, the action button will dynamically appear based on the status.

        <?php if ($res['status'] == 1): ?>
            $(document).on('click', '#confirmBtn', function () {
                Swal.fire({
                    title: 'Confirm Reservation?',
                    text: "This will update the status to confirmed   and notify the guest.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1cc88a',
                    cancelButtonColor: '#858796',
                    confirmButtonText: '<i class="fas fa-check"></i> Yes, Confirm',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Processing...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading()
                            }
                        });

                        $.ajax({
                            url: 'update_status.php', // Assuming this endpoint handles status updates
                            type: 'POST',
                            data: {
                                reservation_id: <?= $res['reservation_id'] ?>,
                                status: 2 // Status 2 = Confirmed
                            },
                            success: function (response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Confirmed!',
                                    text: 'Reservation status updated successfully.'
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Something went wrong on the server. Please try again.'
                                });
                            }
                        });
                    }
                });
            });
        <?php endif; ?>
    </script>
</body>

</html>