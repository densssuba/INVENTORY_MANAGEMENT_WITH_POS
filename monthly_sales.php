<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'role_based_authentication';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$currentMonth = $_GET['month'] ?? '';
$currentYear = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT created_at, product_name, quantity, price, total_price, payment_method, cashier_name, customer_name, cash_received FROM sales_transactions WHERE 1";

if (!empty($currentMonth)) {
    $sql .= " AND MONTH(created_at) = " . (int)$currentMonth;
}
if (!empty($currentYear)) {
    $sql .= " AND YEAR(created_at) = " . (int)$currentYear;
}
if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);
    $sql .= " AND (product_name LIKE '%$safeSearch%' OR transaction_id LIKE '%$safeSearch%')";
}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);

$totalPrice = 0;
$salesData = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $salesData[] = $row;
        $totalPrice += $row['total_price'];
    }
}

$sales_per_month = array_fill(1, 12, 0);
$year = !empty($currentYear) ? (int)$currentYear : date('Y');
$resultMonthSales = $conn->query("SELECT MONTH(created_at) as month, SUM(total_price) as total FROM sales_transactions WHERE YEAR(created_at) = $year GROUP BY MONTH(created_at)");
if ($resultMonthSales) {
    while ($row = $resultMonthSales->fetch_assoc()) {
        $sales_per_month[(int)$row['month']] = (float)$row['total'];
    }
}

$selectedMonth = (int)$currentMonth;
$monthSales = $sales_per_month[$selectedMonth] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Sales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="monthly.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <a href="sales.php" class="sidebar-icon" title="Back to Dashboard">
            <i class="fa-solid fa-backward"></i>
        </a>
    </div>

    <div class="main-container">
        <div class="header">
            <div class="title">Inventory</div>
        </div>

        <div class="content">
            <div class="tabs">
                <div class="tab active">DAILY SALES</div>
            </div>

            <div class="summary">
                <div class="stats">
                    <div class="stat">
                        <div class="stat-icon products-icon"><i class="fas fa-box"></i></div>
                        Total Transactions: <span><?= count($salesData); ?></span>
                    </div>
                    <div class="stat">
                        <div class="stat-icon sales-icon"><i class="fas fa-coins"></i></div>
                        Total Sales: <span>₱<?= number_format($totalPrice, 2); ?></span>
                    </div>
                    <?php if ($selectedMonth): ?>
                        <div class="stat">
                            Total Sales for <?= date('F', mktime(0,0,0,$selectedMonth,10)) ?>: <span>₱<?= number_format($monthSales, 2); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="filters">
                    <form id="filterForm" method="GET">
                        <select id="monthFilter" name="month" class="filter-select">
                            <option value="">Month</option>
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                $value = str_pad($m, 2, "0", STR_PAD_LEFT);
                                $selected = ($currentMonth == $value) ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>" . date("F", mktime(0, 0, 0, $m, 10)) . "</option>";
                            }
                            ?>
                        </select>

                        <select id="yearFilter" name="year" class="filter-select">
                            <option value="">Year</option>
                            <?php
                            for ($y = 2025; $y <= 2030; $y++) {
                                $selected = ($currentYear == $y) ? 'selected' : '';
                                echo "<option value=\"$y\" $selected>$y</option>";
                            }
                            ?>
                        </select>

                        <input type="text" name="search" placeholder="Search product or ID..." value="<?= htmlspecialchars($search); ?>" />
                        <button type="submit">Search</button>
                    </form>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Cashier</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($salesData) > 0): ?>
                        <?php foreach ($salesData as $row): ?>
                            <tr>
                                <td><?= date('F d, Y - h:i A', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['quantity']) ?></td>
                                <td>₱<?= number_format($row['price'], 2) ?></td>
                                <td>₱<?= number_format($row['total_price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['cashier_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['payment_method'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">No sales found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('monthFilter').addEventListener('change', () => document.getElementById('filterForm').submit());
        document.getElementById('yearFilter').addEventListener('change', () => document.getElementById('filterForm').submit());
    </script>
</body>
</html>
