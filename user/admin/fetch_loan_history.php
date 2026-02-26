<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

include '../../config.php';

$response = ["status" => "error", "message" => "Invalid request.", "data" => []];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reservation_id'])) {
    $reservation_id = mysqli_real_escape_string($conn, $_GET['reservation_id']);

    $sql = "SELECT 
                req.quantity_loaned, 
                req.unit_price, 
                req.loan_date,
                req.loan_status,
                eq.equipment_name
            FROM reservation_equipment_tbl req
            JOIN equipment_tbl eq ON req.equipment_id = eq.equipment_id
            WHERE req.reservation_id = '$reservation_id'
            ORDER BY req.loan_date DESC";
            
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $loan_history = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $response = ["status" => "success", "message" => "History fetched successfully.", "data" => $loan_history];
    } else {
        $response = ["status" => "error", "message" => "Database query failed: " . mysqli_error($conn)];
    }
}

echo json_encode($response);
?>