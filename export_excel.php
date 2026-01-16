<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'role_based_authentication';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$type     = isset($_GET['type']) ? $_GET['type'] : 'inventory';
$search   = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

// ============================
// EXPORT INVENTORY
// ============================
if ($type === 'inventory') {

    $sql = "SELECT * FROM add_product WHERE 1";

    if (!empty($search)) {
        $sql .= " AND (product_name LIKE '%$search%' 
                OR code_id LIKE '%$search%' 
                OR category LIKE '%$search%')";
    }

    if (!empty($category)) {
        $sql .= " AND category = '$category'";
    }

    $result = $conn->query($sql);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=inventory_export.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "Date Added\tProduct Name\tCategory\tProduct ID\tUnit\tStock\tPrice\n";

    while ($row = $result->fetch_assoc()) {
        echo $row['date_added'] . "\t" .
            $row['product_name'] . "\t" .
            $row['category'] . "\t" .
            $row['code_id'] . "\t" .
            $row['unit'] . "\t" .
            $row['Out_stock'] . "\t" .
            $row['price'] . "\n";
    }

    exit;
}



// ============================
// EXPORT SALES REPORT
// ============================
if ($type === 'sales') {

    $sql = "SELECT * FROM sale_transaction WHERE 1";

    if (!empty($search)) {
        $sql .= " AND (product_name LIKE '%$search%' 
                OR product_code LIKE '%$search%' 
                OR cashier LIKE '%$search%')";
    }

    if (!empty($category)) {
        $sql .= " AND category = '$category'";
    }

    $result = $conn->query($sql);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=sales_report.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "Product Name\tProduct Code\tCategory\tQty\tPrice\tTotal\tCashier\tDate Sold\n";

    while ($row = $result->fetch_assoc()) {
        echo $row['product_name'] . "\t" .
            $row['product_code'] . "\t" .
            $row['category'] . "\t" .
            $row['quantity'] . "\t" .
            $row['price'] . "\t" .
            $row['total'] . "\t" .
            $row['cashier'] . "\t" .
            $row['date_sold'] . "\n";
    }

    exit;
}

?>
