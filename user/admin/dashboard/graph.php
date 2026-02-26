<?php


include '../../config.php';
//reservation
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$months = [
    1 => 'Jan',
    2 => 'Feb',
    3 => 'Mar',
    4 => 'Apr',
    5 => 'May',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Aug',
    9 => 'Sep',
    10 => 'Oct',
    11 => 'Nov',
    12 => 'Dec'
];
$reservation_counts = array_fill(1, 12, 0);
$sql = "SELECT MONTH(created_at) AS month, COUNT(*) AS total
        FROM reservation_tbl
        WHERE YEAR(created_at) = ?
        GROUP BY MONTH(created_at)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reservation_counts[intval($row['month'])] = intval($row['total']);
}
$labels = json_encode(array_values($months));
$data = json_encode(array_values($reservation_counts));


//income
$year_income = isset($_GET['year_income']) ? intval($_GET['year_income']) : date('Y');
$months = [
    1 => 'Jan',
    2 => 'Feb',
    3 => 'Mar',
    4 => 'Apr',
    5 => 'May',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Aug',
    9 => 'Sep',
    10 => 'Oct',
    11 => 'Nov',
    12 => 'Dec'
];
$monthly_income = array_fill(1, 12, 0);
$sql_income = "
    SELECT MONTH(created_at) AS month, SUM(amount) AS total
    FROM reservation_payments_tbl
    WHERE YEAR(created_at) = ?
    GROUP BY MONTH(created_at)
";
$stmt = $conn->prepare($sql_income);
$stmt->bind_param("i", $year_income);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $monthly_income[intval($row['month'])] = floatval($row['total']);
}
$labels_income = json_encode(array_values($months));
$data_income = json_encode(array_values($monthly_income));


//room
$room_query = "
    SELECT r.room_name, COUNT(res.reservation_id) AS total_reserved
    FROM rooms_tbl r
    LEFT JOIN reservation_tbl res
        ON r.room_id = res.room_id
    GROUP BY r.room_id
    ORDER BY total_reserved DESC
";
$result_room = mysqli_query($conn, $room_query);
$room_names = [];
$reservation_counts = [];
while ($row_name = mysqli_fetch_assoc($result_room)) {
    $room_names[] = $row_name['room_name'];
    $reservation_counts[] = intval($row_name['total_reserved']);
}

$labels_room = json_encode($room_names);
$data_room = json_encode($reservation_counts);

//service

$sql_service = "
    SELECT s.service_name, COUNT(rs.service_id) AS total
    FROM services_tbl s
    LEFT JOIN reservation_services_tbl rs ON s.service_id = rs.service_id
    GROUP BY s.service_id
    ORDER BY total DESC
";
$result_service = mysqli_query($conn, $sql_service);

$services = [];
$counts = [];

while ($row = mysqli_fetch_assoc($result_service)) {
    $services[] = $row['service_name'];
    $counts[] = intval($row['total']);
}

$labels_service = json_encode($services);
$data_service = json_encode($counts);


