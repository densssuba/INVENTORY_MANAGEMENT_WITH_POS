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

$allowed_roles = ['admin', 'user'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo "<script>alert('❌ Access denied.'); window.location.href = 'index.php';</script>";
    exit();
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';


$sql = "SELECT * FROM add_product WHERE 1";

if (!empty($search)) {
    $sql .= " AND (product_name LIKE '%$search%' OR code_id LIKE '%$search%' OR category LIKE '%$search%')";
}

if (!empty($category)) {
    $sql .= " AND category = '$category'";
}

$result = $conn->query($sql);

$lowStockProducts = [];
$allRows = [];

while ($row = $result->fetch_assoc()) {
    $allRows[] = $row;

    if ($row['Out_stock'] < 5) {
        $lowStockProducts[] = [
            'product' => $row['product_name'],
            'username' => !empty($row['last_update_by']) ? $row['last_update_by'] : $_SESSION['username'],
            'remaining' => $row['Out_stock']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory Management System</title>
<link rel="stylesheet" href="product.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .search-input { padding: 8px; width: 200px; border-radius: 10px; border: 1px solid #ccc; }
    .search-btn { padding: 8px 12px; border: none; background-color: black; color: white; cursor: pointer; }
    .category-select { padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
    .notification-dropdown { display: none; position: absolute; background: #fff; border: 1px solid #ccc; width: 300px; max-height: 400px; overflow-y: auto; padding: 10px; z-index: 100; }
    .notification-dropdown.show { display: block; }
</style>
</head>
<body>

<div class="sidebar">
    <a href="index.php" class="sidebar-icon" title="Back to Dashboard"><i class="fa-solid fa-backward"></i></a>
    <a href="monthly_products.php" class="sidebar-icon" title="Products"><i class="far fa-calendar-alt"></i></a>
</div>

<div class="main-container">
    <div class="header">
        <div class="title">Inventory</div>

        <div class="notification-wrapper" style="position:relative;">
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
                <?php if (count($lowStockProducts) > 0): ?>
                    <span class="notification-badge"><?= count($lowStockProducts) ?></span>
                <?php endif; ?>
            </button>

            <div id="notification-dropdown" class="notification-dropdown">
                <div class="notification-title">⚠️ Low Stock Alerts</div>
                <?php if (!empty($lowStockProducts)): ?>
                    <ul class="low-stock-list" style="list-style:none; padding:0;">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <li style="padding:5px 0; border-bottom:1px solid #eee;">
                                <span><?= htmlspecialchars($product['product']) ?></span><br>
                                <small>By: <?= htmlspecialchars($product['username']) ?> | Left: <?= htmlspecialchars($product['remaining']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No low stock products.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content">

        <div class="tabs">
            <a class="tab active">PRODUCT</a>
        </div>

        <div class="search-bar">
            <form method="GET" id="searchForm">
                <input type="text" class="search-input" name="search" placeholder="Search..."
                    value="<?= htmlspecialchars($search) ?>"
                    onkeyup="document.getElementById('searchForm').submit();">

                <select name="category" class="category-select" onchange="document.getElementById('searchForm').submit();">
                    <option value="">All Categories</option>
                    <option value="Pork" <?= ($category == "Pork") ? "selected" : "" ?>>Pork</option>
                    <option value="Chicken" <?= ($category == "Chicken") ? "selected" : "" ?>>Chicken</option>
                    <option value="Beef" <?= ($category == "Beef") ? "selected" : "" ?>>Beef</option>
                    <option value="Others" <?= ($category == "Others") ? "selected" : "" ?>>Others</option>
                </select>

                <button class="search-btn"><i class="fas fa-search"></i></button>
            </form>
        </div>

        
    <div class="summary">
    <div class="stats">
        <div class="stat">Total <span class="stat-value total"><?= count($allRows) ?></span></div>
    </div>

    <div class="button-group">
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <button class="add-btn" onclick="location.href='add_product.php'">
            <i class="fas fa-plus"></i> ADD PRODUCT
        </button>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <button class="add-btn export-btn" onclick="location.href='export_excel.php?search=<?= $search ?>&category=<?= $category ?>'">
            <i class="fas fa-file-excel"></i> EXPORT EXCEL
        </button>
        <?php endif; ?>
    </div>
</div>
  
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Product ID</th>
                    <th>Unit</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($allRows)): ?>
                    <?php foreach ($allRows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['date_added']) ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['code_id']) ?></td>
                            <td><?= htmlspecialchars($row['unit']) ?></td>
                            <td><?= htmlspecialchars($row['Out_stock']) ?></td>
                            <td><?= htmlspecialchars($row['price']) ?></td>
                            <td>
                                <a href="view_product.php?id=<?= $row['id'] ?>" class="action-btn view-btn" title="View"><i class="fas fa-eye"></i></a>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <a href="edit_product.php?id=<?= $row['id'] ?>" class="action-btn edit-btn" title="Edit"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>
<script>

    const notificationBtn = document.querySelector('.notification-btn');
    const dropdown = document.getElementById('notification-dropdown');

    if (notificationBtn && dropdown) {
        notificationBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', function (event) {
            if (!dropdown.contains(event.target) && !notificationBtn.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    }
</script>

</body>
</html>
