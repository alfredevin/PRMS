<?php
// Prevent PHP errors from breaking JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

include '../../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './../../mailer/src/Exception.php';
require './../../mailer/src/PHPMailer.php';
require './../../mailer/src/SMTP.php';

$response = ["status" => "error", "message" => "Unknown error"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- 1. COLLECT AND SANITIZE DATA ---
    $room_id = $_POST['room_id'] ?? null;
    $guest_name = $_POST['guest_name'] ?? '';
    $guest_email = $_POST['guest_email'] ?? '';
    $guest_phone = $_POST['guest_phone'] ?? '';
    $check_in = $_POST['checkin'] ?? '';
    $check_out = $_POST['checkout'] ?? '';
    $guests = (int) ($_POST['guests'] ?? 0);
    $total_nights = (int) ($_POST['totalNights'] ?? 0);

    // GENDER BREAKDOWN DATA
    $total_male = (int) ($_POST['total_male'] ?? 0);
    $total_female = (int) ($_POST['total_female'] ?? 0);

    // PAYMENT VALUES
    $total_price = $_POST['total_payments'] ?? 0;
    $payment_option = $_POST['payment_option'] ?? 'full';
    $final_payable = $_POST['final_payable'] ?? $total_price;

    $tracking_number = 'RES' . time() . rand(100, 999);

    // --- 2. FETCH ROOM INFO ---
    $room_id_safe = mysqli_real_escape_string($conn, $room_id);
    $room_sql = "SELECT r.*, t.room_type_name FROM rooms_tbl r
                 INNER JOIN room_type_tbl t ON r.room_type_id = t.room_type_id 
                 WHERE r.room_id = '$room_id_safe'";
    $room_result = mysqli_query($conn, $room_sql);
    $room_data = mysqli_fetch_assoc($room_result);
    $room_name = $room_data['room_type_name'] ?? 'Unknown';

    if ($guests > 0) {
        // --- 3. INSERT MAIN RESERVATION (Including Gender Summary) ---
        $insert_sql = "INSERT INTO reservation_tbl 
            (room_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_nights, total_price, tracking_number, status, total_male, total_female) 
            VALUES (
                '$room_id_safe',
                '" . mysqli_real_escape_string($conn, $guest_name) . "',
                '" . mysqli_real_escape_string($conn, $guest_email) . "',
                '" . mysqli_real_escape_string($conn, $guest_phone) . "',
                '" . mysqli_real_escape_string($conn, $check_in) . "',
                '" . mysqli_real_escape_string($conn, $check_out) . "',
                '$guests', '$total_nights', '" . mysqli_real_escape_string($conn, $total_price) . "', '$tracking_number', '2', '$total_male', '$total_female'
            )";

        if (mysqli_query($conn, $insert_sql)) {
            $reservation_id = mysqli_insert_id($conn);

            // --- 4. INSERT INDIVIDUAL GUEST DETAILS (Prepared Statement) ---
            $guest_stmt = $conn->prepare("INSERT INTO reservation_guests_tbl (reservation_id, age, category, gender, pwd) VALUES (?, ?, ?, ?, ?)");
            $pwd_default = 'No';
            $primary_guest_gender = $_POST['inputGender'] ?? 'Male';

            // Process Adults
            $adult_ages = $_POST['adult_ages'] ?? [];
            $adult_genders = $_POST['adult_genders'] ?? [];
            foreach ($adult_ages as $i => $age) {
                // The very first adult (index 0) gets the gender from the radio button
                $gender = ($i === 0) ? $primary_guest_gender : ($adult_genders[$i] ?? 'Male');
                $cat = 'Adult';
                $age_int = (int) $age;
                $guest_stmt->bind_param("iisss", $reservation_id, $age_int, $cat, $gender, $pwd_default);
                $guest_stmt->execute();
            }

            // Process Children
            $child_ages = $_POST['child_ages'] ?? [];
            $child_genders = $_POST['child_genders'] ?? [];
            foreach ($child_ages as $i => $age) {
                $gender = $child_genders[$i] ?? 'Male';
                $cat = 'Child';
                $age_int = (int) $age;
                $guest_stmt->bind_param("iisss", $reservation_id, $age_int, $cat, $gender, $pwd_default);
                $guest_stmt->execute();
            }

            // Process Seniors
            $senior_ages = $_POST['senior_ages'] ?? [];
            $senior_genders = $_POST['senior_genders'] ?? [];
            foreach ($senior_ages as $i => $age) {
                $gender = $senior_genders[$i] ?? 'Male';
                $cat = 'Senior';
                $age_int = (int) $age;
                $guest_stmt->bind_param("iisss", $reservation_id, $age_int, $cat, $gender, $pwd_default);
                $guest_stmt->execute();
            }
            $guest_stmt->close();

            // --- 5. SERVICES, BOATS, AND RENTALS ---
            if (!empty($_POST['services'])) {
                foreach ($_POST['services'] as $sid) {
                    mysqli_query($conn, "INSERT INTO reservation_services_tbl (reservation_id, tracking_number, service_id) VALUES ('$reservation_id','$tracking_number','" . (int) $sid . "')");
                }
            }

            if (!empty($_POST['boat_rentals'])) {
                foreach ($_POST['boat_rentals'] as $boat) {
                    $parts = explode(':', $boat);
                    $bid = (int) $parts[0];
                    $isl = (int) $parts[1];
                    $amt = (float) $parts[2];
                    mysqli_query($conn, "INSERT INTO reservation_boat_rentals_tbl (reservation_id, tracking_number, rental_id, include_island, amount) VALUES ('$reservation_id', '$tracking_number', '$bid', '$isl', '$amt')");
                }
            }

            if (!empty($_POST['rentals'])) {
                foreach ($_POST['rentals'] as $rid) {
                    mysqli_query($conn, "INSERT INTO reservation_rentals_tbl (reservation_id, tracking_number, rental_id) VALUES ('$reservation_id','$tracking_number','" . (int) $rid . "')");
                }
            }

            // --- 6. INSERT PAYMENT ---
            if (!empty($_POST['payment_type'])) {
                $pay_type = mysqli_real_escape_string($conn, $_POST['payment_type']);
                $ref_num = mysqli_real_escape_string($conn, $_POST['reference_number'] ?? 'WALK-IN');
                $pay_id = (is_numeric($pay_type)) ? (int) $pay_type : 99; // 99 for Cash/Manual

                mysqli_query($conn, "INSERT INTO reservation_payments_tbl (reservation_id, tracking_number, payment_type, reference_number, amount, payment_option) VALUES ('$reservation_id', '$tracking_number', '$pay_id', '$ref_num', '" . mysqli_real_escape_string($conn, $final_payable) . "', '$payment_option')");
            }

            // --- 7. EMAIL SENDING ---
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
                $mail->Subject = 'Walk-in Reservation Confirmed';

                $paid_status = $payment_option === 'full' ? 'FULL PAYMENT' : '50% DOWNPAYMENT';
                $balance = $total_price - $final_payable;

                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
                        <div style='background: #4e73df; padding: 20px; text-align: center; color: white;'>
                            <h2 style='margin: 0;'>Reservation Confirmed</h2>
                            <p>$paid_status Received</p>
                        </div>
                        <div style='padding: 20px;'>
                            <p>Hi <strong>$guest_name</strong>, your walk-in booking is successful.</p>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr style='background: #f8f9fc;'><td style='padding: 8px;'>Tracking ID</td><td style='color: #4e73df;'><strong>$tracking_number</strong></td></tr>
                                <tr><td style='padding: 8px;'>Room</td><td>$room_name</td></tr>
                                <tr style='background: #f8f9fc;'><td style='padding: 8px;'>Check-in</td><td>$check_in</td></tr>
                                <tr><td style='padding: 8px;'>Check-out</td><td>$check_out</td></tr>
                                <tr style='background: #f8f9fc;'><td style='padding: 8px;'>Guests</td><td>$guests ($total_male Male, $total_female Female)</td></tr>
                                <tr><td style='padding: 8px;'>Total Cost</td><td>₱" . number_format($total_price, 2) . "</td></tr>
                                <tr style='background: #f8f9fc;'><td style='padding: 8px;'>Amount Paid</td><td>₱" . number_format($final_payable, 2) . "</td></tr>
                                <tr><td style='padding: 8px;'>Balance</td><td style='color: red;'>₱" . number_format($balance, 2) . "</td></tr>
                            </table>
                            <p style='margin-top: 20px;'>Thank you for choosing Beach Front Resort!</p>
                        </div>
                    </div>";

                $mail->send();
                $response = ["status" => "success", "message" => "Walk-in Booking Successful!", "tracking_number" => $tracking_number];
            } catch (Exception $e) {
                $response = ["status" => "success", "message" => "Booking saved, but email notification failed."];
            }
        } else {
            $response = ["status" => "error", "message" => "Database Error: " . mysqli_error($conn)];
        }
    }
}

echo json_encode($response);
exit;