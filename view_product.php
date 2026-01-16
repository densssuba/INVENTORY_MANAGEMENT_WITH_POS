<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "role_based_authentication";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$product_stmt = $conn->prepare("SELECT * FROM add_product WHERE id = ?");
$product_stmt->bind_param("i", $item_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

$purchase_stmt = $conn->prepare("SELECT quantity FROM purchases WHERE item_id = ?");
$purchase_stmt->bind_param("i", $item_id);
$purchase_stmt->execute();
$purchase_result = $purchase_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Detail</title>
    <link rel="stylesheet" href="view.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="info-container">
        <div class="info-header">
            <div class="title">
                <i class="fas fa-box"></i> Product Details
            </div>
        </div>

        <a href="inventory.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
        
        <div class="info-content">
            <div class="info-grid">
                <div class="info-item"><div class="info-label">date_added:</div><div class="info-value"><?= htmlspecialchars($product['date_added']) ?></div></div>
                <div class="info-item"><div class="info-label">Product Name:</div><div class="info-value"><?= htmlspecialchars($product['product_name']) ?></div></div>
                <div class="info-item"><div class="info-label">Product ID:</div><div class="info-value"><?= htmlspecialchars($product['code_id']) ?></div></div>
                <div class="info-item"><div class="info-label">Unit:</div><div class="info-value"><?= htmlspecialchars($product['unit']) ?></div></div>
                <div class="info-item"><div class="info-label">Stock:</div><div class="info-value"><?= htmlspecialchars($product['Out_stock']) ?></div></div>
            </div>
        </div>
    </div>
</body>
</html>
