<?php
require 'db_config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "No valid transaction ID provided.";
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM sales_transactions WHERE transaction_id = ?");
$stmt->execute([$id]);
$main_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$main_row) {
    echo "Transaction not found.";
    exit;
}

$created_at    = $main_row['created_at'];
$cashier_name  = $main_row['cashier_name'];
$customer_name = $main_row['customer_name'];
$status        = $main_row['status'];
$cash_received = $main_row['cash_received'];
$change_amount = $main_row['change_amount'];

$stmt2 = $pdo->prepare("SELECT * FROM sales_transactions 
    WHERE created_at = ? AND cashier_name = ? AND customer_name = ?");
$stmt2->execute([$created_at, $cashier_name, $customer_name]);
$products = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$grand_total = 0;

foreach ($products as $row) {
    $line_total = $row['quantity'] * $row['price']; 
    $grand_total += $line_total;
}

$formattedDate = date("F j, Y, g:i A", strtotime($created_at));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Receipt #<?= htmlspecialchars($id) ?></title>
    <style>
body {
    font-family: "Courier New", Courier, monospace;
    padding: 0;
    margin: 0;
    background: #fff;
    font-size: 11px;
}

.receipt {
    width: 58mm;              /* change to 80mm if needed */
    margin: 0 auto;
    border: 1px dashed #000;
    background: #fff;
    padding: 8px;
}

.receipt h2 {
    text-align: center;
    margin: 6px 0;
    font-size: 14px;
}

.receipt img {
    display: block;
    margin: 0 auto 6px auto;
    width: 48px;              /* SAFE for thermal printers */
    height: auto;
    border-radius: 0;         /* IMPORTANT */
    object-fit: contain;
}

.line {
    border-top: 1px dashed #000;
    margin: 6px 0;
}

table.products {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}

table.products th,
table.products td {
    padding: 2px 0;
}

table.products th {
    border-bottom: 1px solid #000;
}

.right {
    text-align: right;
}

.back-btn {
     display: none; 
     display: inline-block; 
     padding: 0.45rem 0.9rem; 
     background-color: black; 
     color: #fff; 
     font-size: 0.95rem; 
     font-weight: 500; 
     border-radius: 0.35rem; 
     text-decoration: none; 
     border: none; 
     cursor: pointer; 
     margin-top: 10px; 
}

@media print {
    body {
        background: #fff;
        padding: 0;
        margin: 0;
    }

    .receipt {
        width: 58mm;
        border: none;
        padding: 6px;
    }

    .back-btn {
        display: none !important;
    }
}

    </style>
</head>
<body>
<div class="receipt" id="receipt">
    <img src="deans.png" alt="Store Logo">

    <h2>Dean's Food Store</h2>
    <p style="text-align:center; margin: 0;">SALES RECEIPT</p>
    <p style="text-align:center;">#<?= htmlspecialchars($id) ?></p>

    <p>Date: <?= $formattedDate ?></p>
    <div class="line"></div>

    <table class="products">
        <thead>
            <tr>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td class="right"><?= htmlspecialchars($row['quantity']) ?></td>
                <td class="right">₱<?= number_format($row['price'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="line"></div>
    <p class="total-section"><b>Total:</b> ₱<?= number_format($grand_total, 2) ?></p>

    <p><b>Cash Received:</b> ₱<?= number_format($cash_received, 2) ?></p>
    <p class="total-section"><b>Change:</b> ₱<?= number_format($change_amount, 2) ?></p>

    <div class="line"></div>
    <p>Cashier: <?= htmlspecialchars($cashier_name) ?></p>
    <p>Customer: <?= htmlspecialchars($customer_name) ?></p>

    <div class="footer">
        <p>Thank you for your purchase!</p>
        <a id="backToInvoice" class="back-btn" href="invoice.php?id=<?= htmlspecialchars($id) ?>">Back to invoice</a>
    </div>
</div>

<script>
(function() {
    const backBtn = document.getElementById('backToInvoice');
    function showBackButton() {
        if (backBtn) backBtn.style.display = 'inline-block';
    }
    window.onafterprint = showBackButton;
    window.addEventListener('load', function() {
        try { window.print(); } catch (e) {}
        setTimeout(showBackButton, 1200);
    });
})();
</script>
</body>
</html>
