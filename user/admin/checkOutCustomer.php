<?php
include '../../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './../../mailer/src/Exception.php';
require './../../mailer/src/PHPMailer.php';
require './../../mailer/src/SMTP.php';

if (isset($_POST['reservation_id']) && isset($_POST['status'])) {
    $id = intval($_POST['reservation_id']);
    $status = intval($_POST['status']);

    // ---------------------------------------------------------
    // 1. ISANG QUERY LANG PARA SA LAHAT NG DETALYE
    // ---------------------------------------------------------
    // Pinalitan ko ito para makuha agad ang pangalan, email, at room name
    // ADJUST COLUMN NAMES HERE IF NEEDED (e.g., firstname vs guest_name)
    $selectQuery = "SELECT 
                        res.room_id, 
                        res.guest_name,   
                        res.guest_email, 
                        res.total_price, 
                        r.room_name 
                    FROM reservation_tbl res
                    JOIN rooms_tbl r ON res.room_id = r.room_id
                    WHERE res.reservation_id = ?";

    $selectStmt = $conn->prepare($selectQuery);
    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();
    $selectStmt->bind_result($room_id, $fname,   $email, $amount, $r_name);

    // Kunin ang data
    if ($selectStmt->fetch()) {
        // I-save sa variables para gamitin mamaya
        $guest_name = $fname;
        $guest_email = $email;
        $amount_paid = $amount;
        $room_name = $r_name;

        $selectStmt->close(); // Isara ang statement bago magsimula ng bago

        // Simulan ang Transaction
        $conn->begin_transaction();

        try {
            // ---------------------------------------------------------
            // 2. RETURN EQUIPMENT LOGIC
            // ---------------------------------------------------------

            // a) Kunin ang mga active na hiniram
            $loan_sql = "SELECT equipment_id, quantity_loaned 
                         FROM reservation_equipment_tbl 
                         WHERE reservation_id = ? AND loan_status = 'ACTIVE'";
            $loan_stmt = $conn->prepare($loan_sql);
            $loan_stmt->bind_param("i", $id);
            $loan_stmt->execute();
            $loan_result = $loan_stmt->get_result();

            $equipment_to_return = [];
            while ($loan_row = $loan_result->fetch_assoc()) {
                $equipment_to_return[] = $loan_row;
            }
            $loan_stmt->close();

            // b) Ibalik sa inventory
            if (!empty($equipment_to_return)) {
                $update_eq_stmt = $conn->prepare("UPDATE equipment_tbl SET equipment_quantity = equipment_quantity + ? WHERE equipment_id = ?");
                $update_loan_status = $conn->prepare("UPDATE reservation_equipment_tbl SET loan_status = 'RETURNED', return_date = NOW() WHERE reservation_id = ? AND loan_status = 'ACTIVE'");

                foreach ($equipment_to_return as $item) {
                    // Update Inventory Count
                    $update_eq_stmt->bind_param("ii", $item['quantity_loaned'], $item['equipment_id']);
                    $update_eq_stmt->execute();
                }
                $update_eq_stmt->close();

                // Update Status to RETURNED
                $update_loan_status->bind_param("i", $id);
                $update_loan_status->execute();
                $update_loan_status->close();
            }

            // ---------------------------------------------------------
            // 3. CHECKOUT UPDATES
            // ---------------------------------------------------------

            // Update Reservation Status
            $updateReservation = $conn->prepare("UPDATE reservation_tbl SET status = ? WHERE reservation_id = ?");
            $updateReservation->bind_param("ii", $status, $id);
            $updateReservation->execute();
            $updateReservation->close();

            // Update Customer Logs (Logout)
            $currentDatetime = date("Y-m-d H:i:s");
            $updateLog = $conn->prepare("UPDATE customer_logs_tbl SET logout = ? WHERE reservation_id = ?");
            $updateLog->bind_param("si", $currentDatetime, $id);
            $updateLog->execute();
            $updateLog->close();

            // Update Room Availability (+1)
            $updateRoom = $conn->prepare("UPDATE rooms_tbl SET available = available + 1 WHERE room_id = ?");
            $updateRoom->bind_param("i", $room_id);
            $updateRoom->execute();
            $updateRoom->close();

            // I-commit ang lahat ng changes sa database
            $conn->commit();

            // ---------------------------------------------------------
            // 4. SEND EMAIL
            // ---------------------------------------------------------
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'beachfrontresort149@gmail.com';
                $mail->Password = 'pseidlyetewnfcof'; // App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('beachfrontresort149@gmail.com', 'Beach Front Resort');
                $mail->addAddress($guest_email, $guest_name);
                $mail->isHTML(true);
                $mail->Subject = 'Thank You for Staying with Us! - Official Receipt';

                // GENERATE RATING LINK
                $encrypted_id = base64_encode($id);
                // **PALITAN MO ITO NG ACTUAL PATH MO**
                $rating_link = "http://localhost/santa_cruz/prms/rating.php?ref=" . $encrypted_id;

                $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                    <div style='background: linear-gradient(135deg, #4e73df, #224abe); padding: 30px; text-align: center; color: #fff;'>
                        <h1 style='margin: 0; font-size: 28px;'>Checkout Successful</h1>
                        <p style='margin-top: 10px; font-size: 16px;'>We hope you had a wonderful stay!</p>
                    </div>

                    <div style='padding: 30px;'>
                        <p>Hi <strong>$guest_name</strong>,</p>
                        <p>Thank you for choosing Beach Front Resort. Your checkout has been processed successfully.</p>

                        <div style='background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                            <h3 style='margin-top: 0; color: #4e73df;'>Trip Summary</h3>
                            <p style='margin: 5px 0;'><strong>Room:</strong> $room_name</p>
                            <p style='margin: 5px 0;'><strong>Total Amount Paid:</strong> ₱" . number_format($amount_paid, 2) . "</p>
                            <p style='margin: 5px 0;'><strong>Date Checked Out:</strong> " . date("F j, Y") . "</p>
                        </div>

                        <p>We are constantly trying to improve our services. Would you mind rating your experience with us?</p>

                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='$rating_link' style='background-color: #f6c23e; color: #fff; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 50px; font-size: 18px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: inline-block;'>
                                ⭐ Rate Your Experience
                            </a>
                        </div>

                        <p style='font-size: 12px; color: #777; text-align: center;'>
                            If the button doesn't work, copy this link: <br> <a href='$rating_link'>$rating_link</a>
                        </p>
                    </div>
                    
                    <div style='background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
                        &copy; " . date("Y") . " Beach Front Resort. All rights reserved.
                    </div>
                </div>";

                $mail->send();
                echo "success";
            } catch (Exception $e) {
                // Email failed, pero success pa rin ang checkout sa database
                echo "success_no_email";
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "error: " . $e->getMessage();
        }
    } else {
        $selectStmt->close();
        echo "no_reservation_found";
    }
}
