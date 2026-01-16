<?php
require 'db_config.php';


ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_names  = $_POST['product_name'];
    $quantities     = $_POST['quantity'];
    $prices         = $_POST['price']; 
    $payment_method = $_POST['payment_method'];
    $cashier_name   = trim($_POST['cashier_name']);
    $customer_name  = trim($_POST['customer_name']);

    function showErrorAndGoBack($message) {
        echo "<script>alert('$message'); window.history.back();</script>";
        exit;
    }

    $cash_received = isset($_POST['cash_received']) ? (float) $_POST['cash_received'] : 0.00;
    $change_amount = isset($_POST['change_amount']) ? (float) $_POST['change_amount'] : 0.00;

    $stmt = $pdo->prepare("INSERT INTO sales_transactions 
        (product_name, quantity, price, payment_method, cashier_name, customer_name, status, cash_received, change_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $first_id = null;

    $pdo->beginTransaction();

    try {
        foreach ($product_names as $index => $product_name) {
            $p_name         = trim($product_name);
            $p_quantity     = (int) $quantities[$index];
            $p_price        = (float) $prices[$index]; 
            

            $stockQuery = $pdo->prepare("SELECT Out_stock FROM add_product WHERE product_name = ?");
            $stockQuery->execute([$p_name]);
            $product = $stockQuery->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
              
                throw new Exception("❌ Product '$p_name' not found in inventory.");
            }

            $current_out = (int) $product['Out_stock'];

            if ($current_out < $p_quantity) {
           
                throw new Exception("❌ Insufficient Out_stock for '$p_name'. Available: $current_out.");
            }

       
            $new_out_stock = $current_out - $p_quantity;
            $updateStock = $pdo->prepare("UPDATE add_product 
                SET Out_stock = ?, last_update_by = ?, date_added = NOW() 
                WHERE product_name = ?");
            $updateStock->execute([$new_out_stock, $cashier_name, $p_name]);

           
            $stmt->execute([
                $p_name,
                $p_quantity,
                $p_price,
                $payment_method,
                $cashier_name,
                $customer_name,
                $status,
                $cash_received,
                $change_amount
            ]); 

            if ($first_id === null) {
               
                $first_id = $pdo->lastInsertId();
            }
        }
        
     
        $pdo->commit(); 

        if ($first_id) {
            
            header("Location: invoice_receipt.php?id=" . urlencode($first_id));
            exit();
        } else {
            
            showErrorAndGoBack("⚠️ Transaction saved, but invoice ID not found. Please check your database.");
        }

    } catch (Exception $e) {
        
        $pdo->rollBack();
        
        showErrorAndGoBack("⚠️ Transaction failed. " . $e->getMessage());
    }
}

ob_end_flush();
?>