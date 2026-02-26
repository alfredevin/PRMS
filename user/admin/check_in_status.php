<?php
include '../../config.php';

if (isset($_POST['reservation_id']) && isset($_POST['status'])) {
    $id = intval($_POST['reservation_id']);
    $status = intval($_POST['status']);
    $selectQuery = "SELECT room_id, tracking_number FROM reservation_tbl WHERE reservation_id = ?";
    $selectStmt = $conn->prepare($selectQuery);
    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();
    $selectStmt->bind_result($room_id, $tracking_number);
    $selectStmt->fetch();
    $selectStmt->close();
    if ($room_id) {
        $query = "UPDATE reservation_tbl SET status = ? WHERE reservation_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $status, $id);
        if ($stmt->execute()) {
            if ($status == 3) {
                $currentDatetime = date("Y-m-d H:i:s");
                $logInsert = $conn->prepare("INSERT INTO customer_logs_tbl (reservation_id, login) VALUES (?, ?)");
                $logInsert->bind_param("is", $id, $currentDatetime);
                $logInsert->execute();
                $logInsert->close();
                $updateDates = $conn->prepare("
                    UPDATE reservation_tbl r
                    JOIN resched_tbl s ON r.tracking_number = s.tracking_number
                    SET r.check_in = s.new_check_in, r.check_out = s.new_check_out
                    WHERE r.reservation_id = ?
                ");
                $updateDates->bind_param("i", $id);
                $updateDates->execute();
                $updateDates->close();

                $updateRoom = $conn->prepare("UPDATE rooms_tbl SET available = available - 1 WHERE room_id = ?");
                $updateRoom->bind_param("i", $room_id);
                $updateRoom->execute();
                $updateRoom->close();
            } elseif ($status == 9) {
                $updateResched = $conn->prepare("UPDATE resched_tbl SET status = 0 WHERE tracking_number = ?");
                $updateResched->bind_param("s", $tracking_number);
                $updateResched->execute();
                $updateResched->close();

                $updateRoom = $conn->prepare("UPDATE rooms_tbl SET available = available + 1 WHERE room_id = ?");
                $updateRoom->bind_param("i", $room_id);
                $updateRoom->execute();
                $updateRoom->close();
            }

            echo "success";
        } else {
            echo "error";
        }

        $stmt->close();
    } else {
        echo "no_room";
    }
}
