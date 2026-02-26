<?php
// CRITICAL: Ensure detailed error reporting is ON for debugging, then turn it OFF in production.
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

include '../../config.php';

$response = ["status" => "error", "message" => "An internal error occurred."];

// Helper function for formatting currency in logs/messages
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collect POST data
    $reservation_id = $_POST['reservation_id'] ?? null;
    $loan_total_cost = (float)($_POST['loan_total_cost'] ?? 0);
    $equipment_loans = $_POST['equipment_loan'] ?? []; // Array of [equipment_id => quantity]

    if (!$reservation_id || $loan_total_cost <= 0) {
        $response = ["status" => "error", "message" => "Invalid reservation ID or zero loan amount."];
        echo json_encode($response);
        exit;
    }
    
    // Start Transaction to ensure all updates succeed or fail together
    mysqli_begin_transaction($conn);

    try {
        // --- A. GET CURRENT TOTAL PRICE ---
        $current_total_query = mysqli_query($conn, 
            "SELECT total_price FROM reservation_tbl WHERE reservation_id = '$reservation_id'"
        );
        
        if (!$current_total_query || mysqli_num_rows($current_total_query) == 0) {
            throw new Exception("Reservation not found.");
        }
        
        $current_price_data = mysqli_fetch_assoc($current_total_query);
        $old_total_price = (float)$current_price_data['total_price'];
        $new_total_price = $old_total_price + $loan_total_cost;
        
        // --- B. UPDATE RESERVATION'S TOTAL PRICE ---
        $update_res_sql = "UPDATE reservation_tbl SET total_price = ? WHERE reservation_id = ?";
        $stmt_update_res = $conn->prepare($update_res_sql);
        
        // Bind types: d (new_total_price), i (reservation_id)
        $stmt_update_res->bind_param("di", $new_total_price, $reservation_id);
        
        if (!$stmt_update_res->execute()) {
             throw new Exception("Failed to update reservation price: " . $stmt_update_res->error);
        }
        $stmt_update_res->close();


        // --- C. RECORD INDIVIDUAL LOAN TRANSACTIONS (Into reservation_equipment_tbl) ---
        $loan_date = date('Y-m-d H:i:s');
        
        // Prepare statements outside the loop
        $stmt_loan = $conn->prepare("INSERT INTO reservation_equipment_tbl 
            (reservation_id, equipment_id, quantity_loaned, unit_price, loan_date) 
            VALUES (?, ?, ?, ?, ?)");
        
        $stmt_inventory = $conn->prepare("UPDATE equipment_tbl SET equipment_quantity = equipment_quantity - ? WHERE equipment_id = ?");


        foreach ($equipment_loans as $equipment_id => $qty) {
            $qty = (int)$qty;
            $equipment_id = (int)$equipment_id;
            
            if ($qty > 0) {
                
                // Fetch unit price from the equipment table (for validation/accuracy)
                $price_query = mysqli_query($conn, "SELECT equipment_price FROM equipment_tbl WHERE equipment_id = '$equipment_id'");
                $unit_price = (float)mysqli_fetch_assoc($price_query)['equipment_price'];
                
                // 1. Insert into reservation_equipment_tbl
                // Bind types: i (res_id), i (eq_id), i (qty), d (price), s (date)
                $stmt_loan->bind_param("iiids", $reservation_id, $equipment_id, $qty, $unit_price, $loan_date);
                
                if (!$stmt_loan->execute()) {
                    throw new Exception("Failed to record loan item: " . $stmt_loan->error);
                }
                
                // 2. DECREMENT INVENTORY (equipment_tbl)
                $stmt_inventory->bind_param("ii", $qty, $equipment_id);
                if (!$stmt_inventory->execute()) {
                    throw new Exception("Failed to update inventory for equipment ID: " . $equipment_id);
                }
            }
        }
        $stmt_loan->close();
        $stmt_inventory->close();


        // --- D. COMMIT TRANSACTION ---
        mysqli_commit($conn);
        $response = ["status" => "success", "message" => "Equipment loan saved! New total balance: " . formatCurrency($new_total_price) . "."];

    } catch (Exception $e) {
        // --- E. ROLLBACK TRANSACTION ON FAILURE ---
        mysqli_rollback($conn);
        $response = ["status" => "error", "message" => "Transaction Failed: " . $e->getMessage()];
    }

} else {
    $response = ["status" => "error", "message" => "Invalid request method."];
}

echo json_encode($response);
exit;