<?php
include '../../config.php';

// Set header to return JSON for AJAX
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $reservation_id = mysqli_real_escape_string($conn, $_POST['reservation_id']);
    $new_checkout = mysqli_real_escape_string($conn, $_POST['new_checkout']);

    // 1. Get current reservation details
    // Kailangan natin ang old checkout date, current total price, at room rate para sa computation
    $sql = "SELECT r.check_out, r.total_price, r.total_nights, rm.price as room_rate 
            FROM reservation_tbl r
            JOIN rooms_tbl rm ON r.room_id = rm.room_id
            WHERE r.reservation_id = '$reservation_id'";

    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Reservation record not found.']);
        exit;
    }

    // Variables for calculation
    $old_checkout_str = $row['check_out'];
    $room_rate = floatval($row['room_rate']);
    $current_total = floatval($row['total_price']);
    $current_nights = intval($row['total_nights']);

    // 2. Calculate Date Difference
    $d1 = new DateTime($old_checkout_str); // Old Date
    $d2 = new DateTime($new_checkout);     // New Date

    // Kunin ang difference in days. 
    // %r%a gives signed integer (positive kung extension, negative kung early checkout)
    $interval = $d1->diff($d2);
    $diff_days = (int)$interval->format('%r%a');

    if ($diff_days == 0) {
        echo json_encode(['status' => 'error', 'message' => 'No changes made. The date is the same.']);
        exit;
    }

    // 3. Calculate New Values
    $adjustment_amount = $diff_days * $room_rate;
    $new_total_price = $current_total + $adjustment_amount;
    $new_total_nights = $current_nights + $diff_days;

    // Basic Validation: Bawal maging negative or zero ang nights/price
    if ($new_total_nights <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid duration. Booking cannot have 0 or negative nights.']);
        exit;
    }

    // 4. Update Database
    $update_sql = "UPDATE reservation_tbl 
                   SET check_out = '$new_checkout', 
                       total_price = '$new_total_price',
                       total_nights = '$new_total_nights'
                   WHERE reservation_id = '$reservation_id'";

    if (mysqli_query($conn, $update_sql)) {
        $action = $diff_days > 0 ? "extended" : "shortened";
        $days_count = abs($diff_days);

        echo json_encode([
            'status' => 'success',
            'message' => "Reservation successfully $action by $days_count night(s). Balance updated."
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
