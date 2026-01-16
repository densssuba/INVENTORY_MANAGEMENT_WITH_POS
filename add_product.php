<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
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

function generateUniqueCodeId($conn, $category) {
    switch (strtoupper($category)) {
        case 'PORK':
            $prefix = 'P';
            break;
        case 'CHICKEN':
            $prefix = 'C';
            break;
        case 'BEEF':
            $prefix = 'B';
            break;
        case 'OTHERS':
            $prefix = 'O';
            break;
        default:
            return 'UNK-' . substr(str_shuffle("0123456789"), 0, 4); 
    }

    $sql = "SELECT code_id FROM add_product 
            WHERE code_id REGEXP ?
            ORDER BY code_id DESC 
            LIMIT 1";

    $regex_pattern = '^' . $prefix . '[0-9]+$';
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 'ERR'; 
    }
    $stmt->bind_param("s", $regex_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest_id = $result->fetch_assoc();
    $stmt->close();

    $new_number = 1;
    
    if ($latest_id) {
        $last_id_str = $latest_id['code_id'];
        
        $numeric_part = (int)substr($last_id_str, 1);
        
     
        $new_number = $numeric_part + 1;
    }

    $new_id_number_formatted = str_pad($new_number, 3, '0', STR_PAD_LEFT);
    
    return $prefix . $new_id_number_formatted;
}

if (isset($_POST['submit'])) {
    
    $date_added   = trim($_POST['date_added']);
    $product_name = trim($_POST['product_name']);
    $category     = strtoupper(trim($_POST['category'])); 
    
    $code_id = generateUniqueCodeId($conn, $category); 
    
    $price        = floatval($_POST['price']);
    $Out_stock    = intval($_POST['Out_stock']);
    $unit         = trim($_POST['unit']);

    $sql = "INSERT INTO add_product (date_added, product_name, category, code_id, price, Out_stock, created_at, unit)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssssdis", $date_added, $product_name, $category, $code_id, $price, $Out_stock, $unit);

    if ($stmt->execute()) {

        $admin_user = $_SESSION['username'];
    $action_type = 'Add';
    $log_message = "Added new product: " . $product_name . " (Price: ₱" . number_format($price, 2) . ")";

    $log_sql = "INSERT INTO activity_log (username, action_type, message) VALUES (?, ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    if ($log_stmt) {
        $log_stmt->bind_param("sss", $admin_user, $action_type, $log_message);
        $log_stmt->execute();
        $log_stmt->close();
    }
        
        header("Location: inventory.php");
        exit;
    } else {
        echo "Execution failed: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Product</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    body {
        font-family: "Arial", sans-serif;
        background:whitesmoke;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }

    .form-container {
        background: white;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 500px;
    }

    .form-header .title {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        text-align: center;
        margin-bottom: 25px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }

    .form-control, .stock-input, .unit-select {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #A9B5DF;
        transition: 0.3s ease;
        font-size: 15px;
        box-sizing: border-box;
    }

    .form-control:focus, .stock-input:focus, .unit-select:focus {
        border-color: lightgray;
        outline: none;
        box-shadow: 0 0 5px rgba(76,175,80,0.3);
    }

    .stock-input-group {
        display: flex;
        align-items: center;
        border: 1px solid #ccc;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }

    .stock-btn {
        background: black;
        color: white;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
    }

    .stock-btn:hover {
        background: #A9B5DF;
    }

    .stock-input {
        width: 100%;
        text-align: center;
        border: none;
        font-size: 15px;
        outline: none;
        padding: 10px 0;
    }

    .button-group {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 25px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 25px;
        background-color:black;
        color:white;
        font-size: 16px;
        border-radius: 8px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: 0.3s ease;
    }

    .btn:hover {
        background-color: black;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background-color: black;
    }

    .btn-secondary:hover {
        background-color: black;
    }
</style>
</head>

<script>
function changeStock(id, amount) {
    const input = document.getElementById(id);
    let value = parseInt(input.value) || 0;
    value = Math.max(0, value + amount); 
    input.value = value;
}
</script>

<body>
<div class="form-container">
    <div class="form-header">
        <div class="title">Add Product</div>
    </div>

    <form action="add_product.php" method="POST">
        <div class="form-group">
            <label class="form-label">Product Name</label>
            <input type="text" class="form-control" name="product_name" placeholder="Enter product name" required>
        </div>

        <div class="form-group">
            <label class="form-label">Category</label>
            <select class="unit-select" name="category" required>
                <option value="">Select Category</option>
                <option value="PORK">PORK</option>
                <option value="CHICKEN">CHICKEN</option>
                <option value="BEEF">BEEF</option>
                <option value="OTHERS">OTHERS</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Date Added</label>
            <input type="date" class="form-control" name="date_added" required>
        </div>

        <div class="form-group">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" class="form-control" name="price" placeholder="Enter price" required>
        </div>

        <div class="form-group">
            <label class="form-label">Stock</label>
            <div class="stock-input-group">
                <button class="stock-btn" type="button" onclick="changeStock('outStockInput', -1)">
                    <i class="fas fa-minus"></i>
                </button>
                <input type="text" class="stock-input" name="Out_stock" id="outStockInput" value="0">
                <button class="stock-btn" type="button" onclick="changeStock('outStockInput', 1)">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Unit</label>
            <select class="unit-select" name="unit" required>
                <option value="">Select unit</option>
                <option value="pcs">pcs</option>
                <option value="box">box</option>
                <option value="kg">kg</option>
                <option value="pack">pack</option>
            </select>
        </div>

        <div class="button-group">
            <button type="submit" name="submit" class="btn"><i class="fas fa-plus"></i> Add</button>
            <a href="inventory.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
        </div>
    </form>
</div>
</body>
</html>