<?php
// Prevent PHP errors from breaking JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include './../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './../mailer/src/Exception.php';
require './../mailer/src/PHPMailer.php';
require './../mailer/src/SMTP.php';

// Default response
$response = ["status" => "error", "message" => "Unknown error"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect POST data safely
    $room_id       = $_POST['room_id'] ?? null;
    $guest_name    = $_POST['guest_name'] ?? '';
    $guest_email   = $_POST['guest_email'] ?? '';
    $guest_phone   = $_POST['guest_phone'] ?? '';
    $check_in      = $_POST['checkin'] ?? '';
    $check_out     = $_POST['checkout'] ?? '';
    $guests        = (int)($_POST['guests'] ?? 0);
    $total_nights  = (int)($_POST['totalNights'] ?? 0);
    $total_price   = isset($_POST['total_payments']) ? floatval(str_replace([',', '₱'], '', $_POST['total_payments'])) : 0; // Clean currency string
    $tracking_number = 'RES' . time() . rand(100, 999);

    // Check room info
    $room_sql = "SELECT * FROM rooms_tbl
        INNER JOIN room_type_tbl ON room_type_tbl.room_type_id = rooms_tbl.room_type_id 
        WHERE room_id = '" . mysqli_real_escape_string($conn, $room_id) . "'";

    $room_result = mysqli_query($conn, $room_sql);
    if (!$room_result) {
        echo json_encode(["status" => "error", "message" => "DB Error (room): " . mysqli_error($conn)]);
        exit;
    }
    $room = mysqli_fetch_assoc($room_result);
    $room_name = $room['room_type_name'] ?? 'Unknown';

    // Check availability
    $check_sql = "SELECT available FROM rooms_tbl WHERE room_id = '" . mysqli_real_escape_string($conn, $room_id) . "'";
    $check_result = mysqli_query($conn, $check_sql);
    if (!$check_result) {
        echo json_encode(["status" => "error", "message" => "DB Error (availability): " . mysqli_error($conn)]);
        exit;
    }
    $room_avail = mysqli_fetch_assoc($check_result);

    if ($guests > 0 ) {
        // Insert reservation
        $insert_sql = "INSERT INTO reservation_tbl 
            (room_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_nights, total_price, tracking_number) 
            VALUES (
                '" . mysqli_real_escape_string($conn, $room_id) . "',
                '" . mysqli_real_escape_string($conn, $guest_name) . "',
                '" . mysqli_real_escape_string($conn, $guest_email) . "',
                '" . mysqli_real_escape_string($conn, $guest_phone) . "',
                '" . mysqli_real_escape_string($conn, $check_in) . "',
                '" . mysqli_real_escape_string($conn, $check_out) . "',
                '$guests',
                '$total_nights',
                '$total_price',
                '$tracking_number'
            )";

        if (mysqli_query($conn, $insert_sql)) {
            $reservation_id = mysqli_insert_id($conn);

            // --- UPDATED GUEST LOGIC ---
            $adult_count = isset($_POST['adults']) ? (int)$_POST['adults'] : 1;
            $child_ages  = isset($_POST['child_ages']) ? $_POST['child_ages'] : []; 

            $guest_stmt = $conn->prepare("INSERT INTO reservation_guests_tbl (reservation_id, age, category, pwd) VALUES (?, ?, ?, ?)");

            // 1. INSERT ADULTS
            for ($i = 0; $i < $adult_count; $i++) {
                $age = 0;          
                $category = 'Adult';
                $pwd = 'No';       
                
                $guest_stmt->bind_param("iiss", $reservation_id, $age, $category, $pwd);
                $guest_stmt->execute();
            }

            // 2. INSERT CHILDREN
            if (!empty($child_ages) && is_array($child_ages)) {
                foreach ($child_ages as $age_val) {
                    $age = (int)$age_val; 
                    $category = 'Child';
                    $pwd = 'No';       
                    
                    $guest_stmt->bind_param("iiss", $reservation_id, $age, $category, $pwd);
                    $guest_stmt->execute();
                }
            }
            $guest_stmt->close();

            // Event Bookings
            if (!empty($_POST['events']) && is_array($_POST['events'])) {
                foreach ($_POST['events'] as $event_id) {
                    $event_id = (int)$event_id;
                    $number_of_guests = $guests;
                    $event_sql = "INSERT INTO event_booking_tbl 
                        (tracking_number, event_id, number_of_guests, status) 
                        VALUES ('$tracking_number', '$event_id', '$number_of_guests', 'Pending')";
                    mysqli_query($conn, $event_sql);
                }
            }

            // Services
            if (!empty($_POST['services']) && is_array($_POST['services'])) {
                foreach ($_POST['services'] as $service_id) {
                    $service_id = (int)$service_id;
                    $service_sql = "INSERT INTO reservation_services_tbl (reservation_id, tracking_number, service_id) 
                                    VALUES ('$reservation_id','$tracking_number','$service_id')";
                    mysqli_query($conn, $service_sql);
                }
            }

            // Boat Rentals
            if (!empty($_POST['boat_rentals']) && is_array($_POST['boat_rentals'])) {
                foreach ($_POST['boat_rentals'] as $boat) {
                    $parts = explode(':', $boat);
                    $boat_id      = (int)$parts[0];
                    $include_island = (isset($parts[1]) && $parts[1] == 1) ? 1 : 0;
                    $amount         = isset($parts[2]) ? (float)$parts[2] : 0;

                    $boat_sql = "INSERT INTO reservation_boat_rentals_tbl 
                        (reservation_id, tracking_number, rental_id, include_island, amount)
                        VALUES ('$reservation_id', '$tracking_number', '$boat_id', '$include_island', '$amount')";
                    mysqli_query($conn, $boat_sql);
                }
            }

            // Rentals
            if (!empty($_POST['rentals']) && is_array($_POST['rentals'])) {
                foreach ($_POST['rentals'] as $rental_id) {
                    $rental_id = (int)$rental_id;
                    $rental_sql = "INSERT INTO reservation_rentals_tbl (reservation_id, tracking_number, rental_id) 
                                   VALUES ('$reservation_id','$tracking_number','$rental_id')";
                    mysqli_query($conn, $rental_sql);
                }
            }

            // Payment
            if (!empty($_POST['payment_type']) && !empty($_POST['reference_number'])) {
                $payment_type     = (int)$_POST['payment_type'];
                $reference_number = mysqli_real_escape_string($conn, $_POST['reference_number']);
                $payment_option   = mysqli_real_escape_string($conn, $_POST['payment_option'] ?? 'full'); // Get payment option
                $amount_paid      = isset($_POST['final_payable']) ? (float)$_POST['final_payable'] : $total_price; // Get actual amount paid
                $screenshot       = null;

                if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
                    $screenshot = basename($_FILES['payment_screenshot']['name']);
                    $target_dir = __DIR__ . "./uploads/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $target_file = $target_dir . $screenshot;
                    move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $target_file);
                }

                // Insert with payment_option
                $payment_sql = "INSERT INTO reservation_payments_tbl 
                    (reservation_id, tracking_number, payment_type, reference_number, proof_image, amount, payment_option) 
                    VALUES ('$reservation_id','$tracking_number','$payment_type','$reference_number','$screenshot','$amount_paid', '$payment_option')";
                mysqli_query($conn, $payment_sql);
            }

            // Note: Room availability update logic is usually done after admin confirmation, 
            // but if you want to deduct immediately, uncomment the line below:
            // mysqli_query($conn, "UPDATE rooms_tbl SET available = available - 1 WHERE room_id = '" . mysqli_real_escape_string($conn, $room_id) . "'");

            // Fetch services for email
            $services_list = "";
            $services_sql = "SELECT s.service_name 
                 FROM reservation_services_tbl rs
                 INNER JOIN services_tbl s ON s.service_id = rs.service_id
                 WHERE rs.reservation_id = '$reservation_id'";
            $services_result = mysqli_query($conn, $services_sql);
            if ($services_result && mysqli_num_rows($services_result) > 0) {
                $services_list .= "<ul>";
                while ($row = mysqli_fetch_assoc($services_result)) {
                    $services_list .= "<li>✔️ " . htmlspecialchars($row['service_name']) . "</li>";
                }
                $services_list .= "</ul>";
            } else {
                $services_list = "<p><em>No additional services availed</em></p>";
            }

            // Fetch rentals for email
            $rentals_list = "";
            $rentals_sql = "SELECT r.rental_name 
                FROM reservation_rentals_tbl rr
                INNER JOIN rentals_tbl r ON r.rental_id = rr.rental_id
                WHERE rr.reservation_id = '$reservation_id'";
            $rentals_result = mysqli_query($conn, $rentals_sql);
            if ($rentals_result && mysqli_num_rows($rentals_result) > 0) {
                $rentals_list .= "<ul>";
                while ($row = mysqli_fetch_assoc($rentals_result)) {
                    $rentals_list .= "<li>🛠️ " . htmlspecialchars($row['rental_name']) . "</li>";
                }
                $rentals_list .= "</ul>";
            } else {
                $rentals_list = "<p><em>No rentals availed</em></p>";
            }

            // Determine payment status text for email
            $payment_status_text = ($payment_option == 'downpayment') ? '50% Downpayment' : 'Full Payment';
            $balance_amount = $total_price - $amount_paid;
            $balance_text = ($balance_amount > 0) ? "<tr><td style='padding: 10px;'><span>⚠️ <strong>Balance Due (Upon Arrival)</strong></span></td><td style='padding: 10px; color: #dc3545;'>₱" . number_format($balance_amount, 2) . "</td></tr>" : "";


            // Send confirmation email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'beachfrontresort149@gmail.com';
                $mail->Password = 'pseidlyetewnfcof';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('beachfrontresort149@gmail.com', 'Beach Front Resort');
                $mail->addAddress($guest_email, $guest_name);
                $mail->isHTML(true);
                $mail->Subject = 'Reservation Confirmation';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                        <div style='background: linear-gradient(135deg, #28a745, #20c997); padding: 20px; text-align: center; color: #fff;'>
                            <h2 style='margin: 0; font-size: 24px;'>✅ Reservation Pending Confirmation</h2>
                            <p>Your payment proof has been received.</p>
                        </div>

                        <div style='padding: 20px;'>
                            <p>Hi <strong>$guest_name</strong>,</p>
                            <p>Thank you for booking with us! Your reservation request has been received.</p>

                            <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                                <tr style='background-color: #f2f2f2;'>
                                    <td style='padding: 10px;'><span>🆔 <strong>Tracking Number</strong></span></td>
                                    <td style='padding: 10px; color: #28a745;'><strong>$tracking_number</strong></td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px;'><span>🏨 <strong>Room</strong></span></td>
                                    <td style='padding: 10px;'>$room_name</td>
                                </tr>
                                <tr style='background-color: #f2f2f2;'>
                                    <td style='padding: 10px;'><span>📅 <strong>Check-in</strong></span></td>
                                    <td style='padding: 10px;'>$check_in</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px;'><span>📅 <strong>Check-out</strong></span></td>
                                    <td style='padding: 10px;'>$check_out</td>
                                </tr>
                                <tr style='background-color: #f2f2f2;'>
                                    <td style='padding: 10px;'><span>💳 <strong>Payment Option</strong></span></td>
                                    <td style='padding: 10px;'>$payment_status_text</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px;'><span>💰 <strong>Amount Paid</strong></span></td>
                                    <td style='padding: 10px;'>₱" . number_format($amount_paid, 2) . "</td>
                                </tr>
                                $balance_text
                            </table>

                            <h3 style='margin-top: 30px; color: #20c997;'>🛎️ Services Availed</h3>
                            $services_list

                            <h3 style='margin-top: 20px; color: #20c997;'>🔧 Rentals Availed</h3>
                            $rentals_list

                            <p style='margin-top: 20px;'>📍 Please present this tracking number upon arrival.</p>
                            <p>💌 If you have any questions, feel free to contact us.</p>

                            <p style='margin-top: 30px;'>Thank you!<br><strong>Beach Front Resort</strong></p>
                        </div>
                    </div>
                    ";

                $mail->send();

                $response = ["status" => "success", "message" => "Reservation successful. Check your email.", "tracking_number" => $tracking_number];
            } catch (Exception $e) {
                $response = ["status" => "error", "message" => "Reservation saved but email failed: " . $mail->ErrorInfo];
            }
        } else {
            $response = ["status" => "error", "message" => "Failed to insert reservation: " . mysqli_error($conn)];
        }
    } else {
        $response = ["status" => "error", "message" => "Invalid number of guests or room not available."];
    }
}

// Always return JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    $response = ["status" => "error", "message" => "JSON encoding failed: " . json_last_error_msg()];
}
echo json_encode($response);
exit; 