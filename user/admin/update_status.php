<?php
include '../../config.php';

if (isset($_POST['reservation_id']) && isset($_POST['status'])) {
    $id = intval($_POST['reservation_id']);
    $status = intval($_POST['status']);

    $selectQuery = "SELECT room_id FROM reservation_tbl WHERE reservation_id = ?";
    $selectStmt = $conn->prepare($selectQuery);
    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();
    $selectStmt->bind_result($room_id);
    $selectStmt->fetch();
    $selectStmt->close();

    if ($room_id) {
        $query = "UPDATE reservation_tbl SET status = ? WHERE reservation_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $status, $id);

        if ($stmt->execute()) {
            $updateRoom = $conn->prepare("UPDATE rooms_tbl SET available = available - 1 WHERE room_id = ?");
            $updateRoom->bind_param("i", $room_id);
            $updateRoom->execute();
            $updateRoom->close();

            echo "success";
        } else {
            echo "error";
        }

        $stmt->close();
    } else {
        echo "no_room"; 
    }
}
