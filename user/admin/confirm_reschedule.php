<?php
include '../../config.php';

if (isset($_POST['tracking'])) {
    $tracking = mysqli_real_escape_string($conn, $_POST['tracking']);

    // 1️⃣ Get the new check-in/out from resched_tbl
    $getResched = "SELECT new_check_in, new_check_out FROM resched_tbl WHERE tracking_number = '$tracking' LIMIT 1";
    $result = mysqli_query($conn, $getResched);
    $reschedData = mysqli_fetch_assoc($result);

    if ($reschedData) {
        $newCheckIn = $reschedData['new_check_in'];
        $newCheckOut = $reschedData['new_check_out'];

        // 2️⃣ Update reservation_tbl: set status=3 and update check_in/check_out
        $updateReservation = "
            UPDATE reservation_tbl 
            SET 
                status = 2,
                check_in = '$newCheckIn',
                check_out = '$newCheckOut'
            WHERE tracking_number = '$tracking'
        ";
        $res1 = mysqli_query($conn, $updateReservation);

        // 3️⃣ Update resched_tbl: set status=1 (confirmed)
        $updateResched = "
            UPDATE resched_tbl 
            SET status = 1 
            WHERE tracking_number = '$tracking'
        ";
        $res2 = mysqli_query($conn, $updateResched);

        // 4️⃣ Return result
        if ($res1 && $res2) {
            echo 'success';
        } else {
            echo 'error';
        }
    } else {
        echo 'no_resched_data'; // In case there’s no matching reschedule record
    }
}
?>
