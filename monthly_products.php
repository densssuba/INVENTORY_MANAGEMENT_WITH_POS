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

$sql = "SELECT id, date_added, product_name, code_id,  Out_stock, created_at 
        FROM add_product 
        WHERE 1";

if (!empty($currentMonth)) {
    $sql .= " AND MONTH(created_at) = " . (int)$currentMonth;
}
if (!empty($currentYear)) {
    $sql .= " AND YEAR(created_at) = " . (int)$currentYear;
}
if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);
    $sql .= " AND (product_name LIKE '%$safeSearch%' OR code_id LIKE '%$safeSearch%')";
}

$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Products - Inventory Management</title>
    <link rel="stylesheet" href="monthly.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="sidebar">
    <a href="inventory.php" class="sidebar-icon" title="Back to Inventory">
        <i class="fa-solid fa-backward"></i>
    </a>
</div>

<div class="main-container">
    <div class="header">
        <div class="title">Monthly Products</div>
    </div>

    <div class="content">
        <div class="tabs">
            <div class="tab active">MONTHLY PRODUCT</div>
        </div>

        <div class="summary">
            <div class="stats">
                <div class="stat">
                    <div class="stat-icon products-icon"><i class="fas fa-box"></i></div>
                    Products <span>(<?= $result->num_rows ?>)</span>
                </div>
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

                    <input type="text" name="search" placeholder="Search product or ID..." value="<?= htmlspecialchars($search) ?>" />
                    <button type="submit">Search</button>
                </form>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Product ID</th>
                    <th>stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td></td>
                            <td>
<?php
    $created = $row['created_at'];
    if (!empty($created) && $created !== '0000-00-00 00:00:00' && strtotime($created)) {
        echo htmlspecialchars(date('Y-m-d', strtotime($created)), ENT_QUOTES);
    } else {
        echo 'N/A';
    }
?>
</td>

                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= htmlspecialchars($row['code_id']) ?></td>
                            <td><?= (int)$row['Out_stock'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No products found.</td>
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

<?php $conn->close(); ?>
