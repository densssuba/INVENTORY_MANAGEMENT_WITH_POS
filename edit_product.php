<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('❌ Access denied. Only admin can edit products.'); window.location.href='inventory.php';</script>";
    exit();
}

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'role_based_authentication';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid product ID.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['product_name'] ?? '');
    $code       = trim($_POST['code_id'] ?? '');
    $category   = trim($_POST['category'] ?? '');
    $unit       = trim($_POST['unit'] ?? '');
    $Out_stock  = intval($_POST['Out_stock'] ?? 0);
    $price      = floatval($_POST['price'] ?? 0);

    $fetchOld = $conn->prepare("SELECT price, Out_stock FROM add_product WHERE id = ?");
    $fetchOld->bind_param("i", $id);
    $fetchOld->execute();
    $oldResult = $fetchOld->get_result()->fetch_assoc();
    
    $old_price = $oldResult['price'] ?? 0;
    $old_stock = $oldResult['Out_stock'] ?? 0;
    $fetchOld->close();

   
    $stmt = $conn->prepare("UPDATE add_product 
                            SET product_name=?, code_id=?, category=?, price=?, unit=?, Out_stock=? 
                            WHERE id=?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssdsii", $name, $code, $category, $price, $unit, $Out_stock, $id);

    if ($stmt->execute()) {
        $admin_user = $_SESSION['username'];
        $action_type = 'Edit';
        
        $changes = [];
        if ($old_price != $price) {
            $changes[] = "Price: ₱" . number_format($old_price, 2) . " ➔ ₱" . number_format($price, 2);
        }
        if ($old_stock != $Out_stock) {
            $changes[] = "Stock: $old_stock ➔ $Out_stock";
        }

        if (!empty($changes)) {
            $log_message = "Updated $name (" . implode(", ", $changes) . ")";
        } else {
            $log_message = "Updated product details for: $name";
        }

        $log_stmt = $conn->prepare("INSERT INTO activity_log (username, action_type, message) VALUES (?, ?, ?)");
        if ($log_stmt) {
            $log_stmt->bind_param("sss", $admin_user, $action_type, $log_message);
            $log_stmt->execute();
            $log_stmt->close();
        }

        echo "<script>alert('✅ Product updated successfully.'); window.location.href='inventory.php';</script>";
        exit;
    } else {
        echo "Error updating product: " . $stmt->error;
    }
    $stmt->close();
} 
else {
    $stmt = $conn->prepare("SELECT * FROM add_product WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        die("Product not found.");
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; background-color: whitesmoke; font-family: Arial, sans-serif; }
    body { display: flex; justify-content: center; align-items: center; padding: 40px; }
    form { 
        display: flex; flex-wrap: wrap; gap: 20px; background-color:white; 
        padding: 30px 40px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        width: 100%; max-width: 1200px; justify-content: space-between; 
    }
    form h2 { flex: 1 1 100%; text-align: center; margin-bottom: 20px; color: black; }
    .form-group { flex: 1 1 calc(33.33% - 20px); display: flex; flex-direction: column; min-width: 200px; }
    label { margin-bottom: 5px; font-weight: bold; color: black; }
    input { padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    input:focus { border-color: #007bff; outline: none; }
    .button-group { flex: 1 1 100%; display: flex; gap: 15px; margin-top: 10px; }
    button { flex: 1; padding: 12px; background-color: black; border: none; border-radius: 5px; color: white; font-size: 16px; font-weight: bold; cursor: pointer; }
    button:hover { background-color: #CD5656; }
    .cancel-btn { flex: 1; padding: 12px; background-color: #333; text-align: center; border-radius: 5px; color: white; font-size: 16px; font-weight: bold; text-decoration: none; display: flex; justify-content: center; align-items: center; }
    .cancel-btn:hover { background-color: #999; }
  </style>
</head>
<body>
<form method="POST">
    <h2>Edit Product</h2>
    
    <div class="form-group">
        <label>Product Name:</label>
        <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
    </div>

    <div class="form-group">
        <label>Category:</label>
        <input type="text" name="category" value="<?= htmlspecialchars($product['category']) ?>" required>
    </div>

    <div class="form-group">
        <label>Product ID:</label>
        <input type="text" name="code_id" value="<?= htmlspecialchars($product['code_id']) ?>" required>
    </div>

    <div class="form-group">
        <label>Unit:</label>
        <input type="text" name="unit" value="<?= htmlspecialchars($product['unit']) ?>" required>
    </div>

    <div class="form-group">
        <label>Price:</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
    </div>
    
    <div class="form-group">
        <label>Stock:</label>
        <input type="number" name="Out_stock" value="<?= htmlspecialchars($product['Out_stock']) ?>" required>
    </div>

    <div class="button-group">
        <button type="submit">Update</button>
        <a href="inventory.php" class="cancel-btn">Cancel</a>
    </div>
</form>
</body>
</html>