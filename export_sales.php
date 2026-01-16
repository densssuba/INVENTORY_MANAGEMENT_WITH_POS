<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'role_based_authentication';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}


$sql = "SELECT * FROM sales_transactions ORDER BY created_at DESC";
$result = $conn->query($sql);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=sales_transactions.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "Date\tProduct\tQuantity\tPrice\tTotal\tCash\tChange\tCashier\tPayment\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo htmlspecialchars($row['created_at']) . "\t" .
             htmlspecialchars($row['product_name']) . "\t" .
             $row['quantity'] . "\t" .
             $row['price'] . "\t" .
             $row['total_price'] . "\t" .
             ($row['cash_received'] ?? 0) . "\t" .
             ($row['change_amount'] ?? 0) . "\t" .
             htmlspecialchars($row['cashier_name'] ?? 'N/A') . "\t" .
             htmlspecialchars($row['payment_method'] ?? 'N/A') . "\n";
    }
}

$conn->close();
exit();
?>
