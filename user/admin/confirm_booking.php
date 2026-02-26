<?php

// Set error display for debugging, can be turned off (0) in live production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');



include '../../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure these paths are correct relative to your current file location
require './../../mailer/src/Exception.php';
require './../../mailer/src/PHPMailer.php';
require './../../mailer/src/SMTP.php';

// Default response
$response = ["status" => "error", "message" => "Unknown error"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- 1. COLLECT AND SANITIZE DATA ---
    $room_id        = $_POST['room_id'] ?? null;
    $guest_name     = $_POST['guest_name'] ?? '';
    $guest_email    = $_POST['guest_email'] ?? '';
    $guest_phone    = $_POST['guest_phone'] ?? '';
    $check_in       = $_POST['checkin'] ?? '';
    $check_out      = $_POST['checkout'] ?? '';
    $guests         = (int)($_POST['guests'] ?? 0);
    $total_nights   = (int)($_POST['totalNights'] ?? 0);

    // FIX: Get correct payment values from the hidden fields
    $total_price    = $_POST['total_payments'] ?? 0;        // Grand Total
    $payment_option = $_POST['payment_option'] ?? 'full';   // 'full' or 'downpayment'
    $final_payable  = $_POST['final_payable'] ?? $total_price; // Actual amount paid

    $tracking_number = 'RES' . time() . rand(100, 999);

    // --- 2. FETCH ROOM AND AVAILABILITY ---
    $room_sql = "SELECT r.*, t.room_type_name
        FROM rooms_tbl r
        INNER JOIN room_type_tbl t ON r.room_type_id = t.room_type_id 
        WHERE r.room_id = '" . mysqli_real_escape_string($conn, $room_id) . "'";

    $room_result = mysqli_query($conn, $room_sql);
    if (!$room_result) {
        echo json_encode(["status" => "error", "message" => "DB Error (room): " . mysqli_error($conn)]);
        exit;
    }
    $room = mysqli_fetch_assoc($room_result);
    $room_name = $room['room_type_name'] ?? 'Unknown';

    $check_sql = "SELECT available FROM rooms_tbl WHERE room_id = '" . mysqli_real_escape_string($conn, $room_id) . "'";
    $check_result = mysqli_query($conn, $check_sql);
    $room_avail = mysqli_fetch_assoc($check_result);

    if ($guests > 0 && $room_avail) {
        $insert_sql = "INSERT INTO reservation_tbl 
            (room_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_nights, total_price, tracking_number, status) 
            VALUES (
                '" . mysqli_real_escape_string($conn, $room_id) . "',
                '" . mysqli_real_escape_string($conn, $guest_name) . "',
                '" . mysqli_real_escape_string($conn, $guest_email) . "',
                '" . mysqli_real_escape_string($conn, $guest_phone) . "',
                '" . mysqli_real_escape_string($conn, $check_in) . "',
                '" . mysqli_real_escape_string($conn, $check_out) . "',
                '$guests',
                '$total_nights',
                '" . mysqli_real_escape_string($conn, $total_price) . "',
                '$tracking_number',
                '2'
            )";

        if (mysqli_query($conn, $insert_sql)) {
            $reservation_id = mysqli_insert_id($conn);

            // --- 4. INSERT GUEST AGES (Children) ---
            if (!empty($_POST['child_ages']) && is_array($_POST['child_ages'])) {
                $guest_stmt = $conn->prepare("INSERT INTO reservation_guests_tbl (reservation_id, age, category, pwd) VALUES (?, ?, ?, ?)");
                $category_child = 'Child';
                $pwd_default = 'No';

                if ($guest_stmt) {
                    foreach ($_POST['child_ages'] as $age) {
                        $age_int = (int)$age;
                        $guest_stmt->bind_param("iiss", $reservation_id, $age_int, $category_child, $pwd_default);
                        $guest_stmt->execute();
                    }
                    $guest_stmt->close();
                }
            }

            // --- 5. INSERT SERVICES, BOAT, RENTALS ---

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
                    $boat_id        = (int)($parts[0] ?? 0);
                    $include_island = (int)($parts[1] ?? 0);
                    $amount         = (float)($parts[2] ?? 0);

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

            // --- 6. INSERT PAYMENT (FIXED: Using final_payable) ---
            if (!empty($_POST['payment_type'])) {
                $payment_type     = mysqli_real_escape_string($conn, $_POST['payment_type']);
                $reference_number = mysqli_real_escape_string($conn, $_POST['reference_number'] ?? 'N/A');
                $screenshot       = null;

                $payment_type_id = (is_numeric($payment_type)) ? (int)$payment_type : 99;

                $payment_sql = "INSERT INTO reservation_payments_tbl 
                    (reservation_id, tracking_number, payment_type, reference_number, proof_image, amount) 
                    VALUES (
                        '$reservation_id',
                        '$tracking_number',
                        '$payment_type_id',
                        '$reference_number',
                        '$screenshot',
                        '" . mysqli_real_escape_string($conn, $final_payable) . "' 
                    )";
                mysqli_query($conn, $payment_sql);
            }

            // --- 7. EMAIL PREPARATION ---
            // Fetch Services/Rentals lists for email body (logic restored from previous steps)
            $services_list = "";
            $services_sql = "SELECT s.service_name FROM reservation_services_tbl rs INNER JOIN services_tbl s ON s.service_id = rs.service_id WHERE rs.reservation_id = '$reservation_id'";
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

            $rentals_list = "";
            $rentals_sql = "SELECT r.rental_name FROM reservation_rentals_tbl rr INNER JOIN rentals_tbl r ON r.rental_id = rr.rental_id WHERE rr.reservation_id = '$reservation_id'";
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

            // --- 8. SEND EMAIL ---
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

                $paid_status = $payment_option === 'full' ? 'FULL PAYMENT RECEIVED' : 'DOWNPAYMENT RECEIVED';
                $total_remaining = $total_price - $final_payable;

                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                        <div style='background: linear-gradient(135deg, #28a745, #20c997); padding: 20px; text-align: center; color: #fff;'>
                            <h2 style='margin: 0; font-size: 24px;'>✅ Reservation Confirmed!</h2>
                            <p style='margin: 5px 0 0; font-size: 16px;'>($paid_status)</p>
                        </div>
                        <div style='padding: 20px;'>
                            <p>Hi <strong>$guest_name</strong>,</p>
                            <p>Thank you for choosing our service! Your room reservation has been successfully confirmed.</p>
                            <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                                <tr style='background-color: #f2f2f2;'><td style='padding: 10px;'><span>🆔 <strong>Tracking Number</strong></span></td><td style='padding: 10px; color: #28a745;'><strong>$tracking_number</strong></td></tr>
                                <tr><td style='padding: 10px;'><span>🏨 <strong>Room</strong></span></td><td style='padding: 10px;'>$room_name</td></tr>
                                <tr style='background-color: #f2f2f2;'><td style='padding: 10px;'><span>📅 <strong>Check-in</strong></span></td><td style='padding: 10px;'>$check_in</td></tr>
                                <tr><td style='padding: 10px;'><span>📅 <strong>Check-out</strong></span></td><td style='padding: 10px;'>$check_out</td></tr>
                                <tr style='background-color: #f2f2f2;'><td style='padding: 10px;'><span>🛌 <strong>Total Nights</strong></span></td><td style='padding: 10px;'>$total_nights</td></tr>
                                <tr><td style='padding: 10px;'><span>👥 <strong>Guests</strong></span></td><td style='padding: 10px;'>$guests</td></tr>
                                <tr style='background-color: #f2f2f2;'><td style='padding: 10px;'><span>💰 <strong>Grand Total Cost</strong></span></td><td style='padding: 10px;'>₱" . number_format($total_price, 2) . "</td></tr>
                                <tr><td style='padding: 10px;'><span>💳 <strong>Amount Paid Now ($payment_option)</strong></span></td><td style='padding: 10px; color: #28a745;'><strong>₱" . number_format($final_payable, 2) . "</strong></td></tr>
                                <tr style='background-color: #f2f2f2;'><td style='padding: 10px;'><span>💵 <strong>Remaining Balance</strong></span></td><td style='padding: 10px;'>₱" . number_format($total_remaining, 2) . "</td></tr>
                            </table>
                            <h3 style='margin-top: 30px; color: #20c997;'>🛎️ Services Availed</h3>$services_list
                            <h3 style='margin-top: 20px; color: #20c997;'>🔧 Rentals Availed</h3>$rentals_list
                            <p style='margin-top: 20px;'>📍 Please bring this tracking number upon arrival.</p>
                            <p>💌 If you have any questions, feel free to contact our office.</p>
                            <p style='margin-top: 30px;'>Thank you!<br><strong>Beach Front Resort</strong></p>
                        </div>
                        <div style='background-color: #f2f2f2; text-align: center; padding: 10px; font-size: 12px; color: #666;'>This is an automated message. Please do not reply directly to this email.</div>
                    </div>";

                $mail->send();

                $response = ["status" => "success", "message" => "Reservation successful. Check your email.", "tracking_number" => $tracking_number];
            } catch (Exception $e) {
                // If email fails, the database entry is still saved.
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
