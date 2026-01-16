<?php
session_start(); 

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'role_based_authentication';

if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$activity_logs = $conn->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 30");
function execute_query($conn, $sql, $types = null, $params = null) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error . " | Query: " . $sql);
        return false;
    }
    if ($params && $types) {
        $bind_names[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }
    if (!$stmt->execute()) {
        error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error . " | Query: " . $sql);
        return false;
    }
    $res = $stmt->get_result();
    $stmt->close();
    return $res;
}

$current_year = date('Y');


$check_sales = $conn->query("SELECT COUNT(*) AS total FROM sales_transactions WHERE YEAR(created_at) = $current_year");
$sales_in_current_year = $check_sales ? $check_sales->fetch_assoc()['total'] : 0;

if ($sales_in_current_year == 0) {
    $last_year = date('Y') - 1;
    $check_last_year = $conn->query("SELECT COUNT(*) AS total FROM sales_transactions WHERE YEAR(created_at) = $last_year");
    $sales_in_last_year = $check_last_year ? $check_last_year->fetch_assoc()['total'] : 0;
    
    if ($sales_in_last_year > 0) {
        $current_year = $last_year;
    }
}

$product_result = $conn->query("SELECT COUNT(*) AS total_products FROM add_product");
$product_count = $product_result->fetch_assoc()['total_products'];

$sales_result = $conn->query("SELECT COUNT(*) AS total_sales FROM sales_transactions WHERE YEAR(created_at) = $current_year");
$sales_count = $sales_result ? $sales_result->fetch_assoc()['total_sales'] : 0;


$sales_per_month = array_fill(1, 12, 0);
$result = $conn->query("
    SELECT MONTH(created_at) as month, SUM(total_price) as total 
    FROM sales_transactions 
    WHERE YEAR(created_at) = $current_year 
    GROUP BY MONTH(created_at)
");
while ($row = $result->fetch_assoc()) {
    $sales_per_month[(int)$row['month']] = (float)$row['total'];
}


$sales_per_week = array_fill(1, 52, 0);
$weekly_result = $conn->query("
    SELECT WEEK(created_at, 1) as week, SUM(total_price) as total 
    FROM sales_transactions 
    WHERE YEAR(created_at) = $current_year 
    GROUP BY WEEK(created_at, 1)
");
while ($row = $weekly_result->fetch_assoc()) {
    $sales_per_week[(int)$row['week']] = (float)$row['total'];
}


$weekly_sales_per_month = [];
$weekly_month_result = $conn->query("
    SELECT MONTH(created_at) as month, WEEK(created_at,1) as week, SUM(total_price) as total
    FROM sales_transactions
    WHERE YEAR(created_at) = $current_year
    GROUP BY MONTH(created_at), WEEK(created_at,1)
");
while ($row = $weekly_month_result->fetch_assoc()) {
    $month = (int)$row['month'];
    $week = (int)$row['week'];
    if (!isset($weekly_sales_per_month[$month])) {
        $weekly_sales_per_month[$month] = [];
    }
    $weekly_sales_per_month[$month][$week] = (float)$row['total'];
}


$sales_per_day = [];
$daily_result = $conn->query("
    SELECT DATE(created_at) as day, SUM(total_price) as total 
    FROM sales_transactions 
    WHERE YEAR(created_at) = $current_year 
    GROUP BY DATE(created_at) 
    ORDER BY day ASC
");
while ($row = $daily_result->fetch_assoc()) {
    $sales_per_day[$row['day']] = (float)$row['total'];
}

$top_products_by_month = [];
$top_sales_result = $conn->query("
    SELECT 
        product_name, 
        MONTH(created_at) AS month, 
        SUM(quantity) AS total_qty 
    FROM sales_transactions 
    WHERE YEAR(created_at) = $current_year 
    GROUP BY product_name, MONTH(created_at)
    ORDER BY month, total_qty DESC
");
while ($row = $top_sales_result->fetch_assoc()) {
    $month = (int)$row['month'];
    if (!isset($top_products_by_month[$month])) {
        $top_products_by_month[$month] = [];
    }
    if (count($top_products_by_month[$month]) < 10) {
        $top_products_by_month[$month][] = [
            'label' => $row['product_name'],
            'value' => (int)$row['total_qty']
        ];
    }
}
$yearly_sales = [];
$yearly_result = $conn->query("
    SELECT YEAR(created_at) as year, SUM(total_price) as total 
    FROM sales_transactions 
    GROUP BY YEAR(created_at)
");
while ($row = $yearly_result->fetch_assoc()) {
    $yearly_sales[(int)$row['year']] = (float)$row['total'];
}

$category_list_result = execute_query($conn, "
    SELECT DISTINCT category
    FROM add_product
    WHERE category IS NOT NULL AND category != ''
");
$category_map = []; 
if ($category_list_result) {
    while ($row = $category_list_result->fetch_assoc()) {
        $raw = trim($row['category']);
        if ($raw !== '') {
            $category_map[$raw] = ucfirst(strtolower($raw));
        }
    }
}

$top_grossing_by_category = []; 
foreach ($category_map as $raw_cat => $display_name) {
    $gross_result = execute_query($conn, "
        SELECT st.product_name, SUM(st.total_price) AS total_gross
        FROM sales_transactions st
        JOIN add_product ap ON st.product_name = ap.product_name
        WHERE YEAR(st.created_at) = ? AND ap.category = ?
        GROUP BY st.product_name
        ORDER BY total_gross DESC
        LIMIT 5
    ", 'is', [$current_year, $raw_cat]);

    $top_grossing_by_category[$display_name] = [];
    if ($gross_result) {
        while ($row = $gross_result->fetch_assoc()) {
            $top_grossing_by_category[$display_name][] = [
                'label' => $row['product_name'],
                'value' => (float)$row['total_gross']
            ];
        }
    }
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>DEAN'S FOOD STORE</title>
<link rel="stylesheet" href="styles.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</head>
<body>
<div class="container">
    <div class="navigation">
        <ul>
            <li>
                <a href="#">
                    <img src="deans.png" alt="deans logo" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 1px; margin-top: 10px;">
                    <span class="title">Dean's Food Store</span>
                </a>
            </li>
            <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
            <li><a href="inventory.php"><span class="icon"><ion-icon name="list-outline"></ion-icon></span><span class="title">Products</span></a></li>
            <li><a href="Sales.php"><span class="icon"><ion-icon name="cash-outline"></ion-icon></span><span class="title">Sales</span></a></li>
            <li><a href="invoice.php"><span class="icon"><ion-icon name="cash-outline"></ion-icon></span><span class="title">Invoice</span></a></li>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="add account.php"><span class="icon"><ion-icon name="person-add-outline"></ion-icon></span><span class="title">Add Account</span></a></li>
            <?php endif; ?>

            <li class="dropdown">
                <a>
                    <span class="icon"><ion-icon name="settings-outline"></ion-icon></span>
                    <span class="title">Settings</span>
                </a>
                <div class="dropdown-content">
                    <a href="password.php">Change Password</a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="users_log.php">Accounts log out</a>
                        <a href="accounts.php">Account</a>
                    <?php endif; ?>
                </div>
            </li>

            <li><a href="logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Sign Out</span></a></li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
                <span>Dashboard</span>
                <span id="datetime" style="font-size: 14px; color: black; font-weight: 500;"></span>
            </div>
        </div>
                        
        <div class="cardBox">
            <div class="card">
                <div>
                    <div class="numbers"><?= $product_count ?></div>
                    <div class="cardName">Product</div>
                </div>
                <div class="iconBx"><ion-icon name="restaurant-outline"></ion-icon></div>
            </div>
            <div class="card">
                <div>
                    <div class="numbers"><?= $sales_count ?></div>
                    <div class="cardName">Sales (<?= $current_year ?>)</div>
                </div>
                <div class="iconBx"><ion-icon name="cash-outline"></ion-icon></div>
            </div>
        </div>
<div class="activity-chat-container">
    <div class="activity-header">
        <div class="header-title">
            <ion-icon name="notifications-outline"></ion-icon>
            <span>Recent Admin Activities</span>
        </div>
    </div>
    <div class="activity-body">
        <?php if ($activity_logs && $activity_logs->num_rows > 0): ?>
            <?php while($log = $activity_logs->fetch_assoc()): 
                $badgeClass = (strtolower($log['action_type']) == 'add') ? 'badge-add' : 'badge-edit';
                $icon = (strtolower($log['action_type']) == 'add') ? 'add-circle-outline' : 'create-outline';
            ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $badgeClass; ?>">
                        <ion-icon name="<?php echo $icon; ?>"></ion-icon>
                    </div>
                    <div class="activity-details">
                        <div class="activity-top">
                            <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                            <span class="activity-time"><?php echo date('M d, g:i a', strtotime($log['created_at'])); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($log['message']); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-activity">No recent admin activities recorded.</div>
        <?php endif; ?>
    </div>
</div>
        <div style="margin-top: 30px; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <label for="chartType" style="font-weight: bold;">View:</label>
            <select id="chartType" style="padding: 0.3rem 0.5rem; border-radius: 6px;">
                <option value="monthly">Monthly Sales</option>
                <option value="weekly">Weekly Sales</option>
                <option value="weekly_month">Weekly Sales Per Month</option>
                <option value="daily">Daily Sales</option>
                <option value="yearly">Yearly Sales</option>
                <option value="top">Top Products (Quantity)</option>
            </select>

            <select id="monthFilter" style="padding: 0.3rem 0.5rem; border-radius: 6px; display: none;">
                <?php
                $months = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                ];
                foreach ($months as $num => $name) {
                    echo "<option value='$num'>$name</option>";
                }
                ?>
            </select>
            
            <label for="pieChartCategory" style="font-weight: bold; margin-left: 20px;">Top Grossing:</label>
            <select id="pieChartCategory" style="padding: 0.3rem 0.5rem; border-radius: 6px;">
                <option value="Pork">Pork Products</option>
                <option value="Chicken">Chicken Products</option>
                <option value="Beef">Beef Products</option>
                <option value="Others">Others Products</option>
            </select>
        </div>

        <div class="landscape-container" style="display: flex; gap: 2rem; margin-top: 20px;">
            <section class="chart-container" style="flex: 2; height: 350px;">
                <canvas id="barChart"></canvas>
            </section>
            
            <section class="chart-container" style="flex: 1; height: 350px;">
                <canvas id="pieChart"></canvas>
            </section>
        </div>

        <div class="table-container" style="margin: 20px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);">
            <h3>Top 5 Grossing Products (<?= $current_year ?>)</h3>
            <div style="display: flex; gap: 20px; overflow-x: auto;">
                <?php foreach ($top_grossing_by_category as $category => $products): ?>
                    <div style="min-width: 300px; flex: 1;">
                        <h4 style="color: #11224E; border-bottom: 2px solid #ddd; padding-bottom: 5px; margin-top: 15px;"><?= htmlspecialchars($category) ?></h4>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;">
                            <thead>
                                <tr style="background-color: #f4f4f4;">
                                    <th style="padding: 8px; text-align: left;">Product</th>
                                    <th style="padding: 8px; text-align: right;">Gross (₱)</th> <!-- SIGN CHANGED HERE -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr><td colspan="2" style="padding: 8px; text-align: center; color: #888;">No sales data available.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td style="padding: 8px; border-bottom: 1px solid #eee;"><?= htmlspecialchars($product['label']) ?></td>
                                            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #eee;">₱<?= number_format($product['value'], 2) ?></td> <!-- SIGN CHANGED HERE -->
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="main.js"></script>

<script>
    
    const monthlySales = <?= json_encode(array_values($sales_per_month)) ?>;
    const weeklySales = <?= json_encode(array_values($sales_per_week)) ?>;
    const weeklySalesPerMonth = <?= json_encode($weekly_sales_per_month) ?>;
    const dailyLabels = <?= json_encode(array_keys($sales_per_day)) ?>;
    const dailySales = <?= json_encode(array_values($sales_per_day)) ?>;
    const yearlyLabels = <?= json_encode(array_keys($yearly_sales)) ?>;
    const yearlySales = <?= json_encode(array_values($yearly_sales)) ?>;
    const topProductsByMonth = <?= json_encode($top_products_by_month) ?>;
    const topGrossingByCategory = <?= json_encode($top_grossing_by_category) ?>;

    const COLORS = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
        '#FF9F40', '#E7E9ED', '#A0C3D2', '#FF9999', '#99FF99'
    ];

    const ctxBar = document.getElementById('barChart').getContext('2d');
    let barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{
                label: 'Monthly Sales (<?= $current_year ?>)',
                data: monthlySales,
                backgroundColor: '#11224E',
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, labels: { color: '#020303ff', font: { weight: 'bold' } } },
                tooltip: { enabled: true }
            },
            scales: {
                y: { beginAtZero: true, ticks: { color: 'black', font: { weight: '600' } }, grid: { color: '#cbd5e1' } },
                x: { ticks: { color: '#1e293b', font: { weight: '600' } }, grid: { display: false } }
            }
        }
    });
    
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    let pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                label: 'Gross Revenue',
                data: [],
                backgroundColor: COLORS,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'right', 
                    labels: { font: { size: 12 } }
                },
                title: {
                    display: true,
                    text: 'Top 5 Grossing Products', 
                    font: { size: 14, weight: 'bold' }
                }
            }
        }
    });

    const chartTypeSelect = document.getElementById('chartType');
    const monthFilter = document.getElementById('monthFilter');
    const pieChartCategorySelect = document.getElementById('pieChartCategory');
    

    function updateBarChart(type, labels, data, labelText) {
        barChart.config.type = type; 
        
        barChart.data.labels = labels;
        barChart.data.datasets[0].label = labelText;
        barChart.data.datasets[0].data = data;
        
        barChart.data.datasets[0].backgroundColor = '#11224E'; 
        
        barChart.update();
    }
    
    function updatePieChart(category) {
        const data = topGrossingByCategory[category] || [];
        const labels = data.map(p => p.label);
        const values = data.map(p => p.value);

        pieChart.data.labels = labels;
        pieChart.data.datasets[0].data = values;
        pieChart.options.plugins.title.text = `Top Grossing ${category} Products (<?= $current_year ?>)`;
        pieChart.update();
    }

    function updateTopProductsChart(month) {
        const data = topProductsByMonth[month] || [];
        updateBarChart(
            'bar',
            data.map(p => p.label),
            data.map(p => p.value),
            `Top Products (Quantity) - ${monthFilter.options[monthFilter.selectedIndex].text} (<?= $current_year ?>)`
        );
    }

    function updateWeeklyMonthChart(month) {
        const weekData = weeklySalesPerMonth[month] || {};
        const labels = Object.keys(weekData).map(w => 'Week ' + w);
        const data = Object.values(weekData);
        updateBarChart(
            'bar',
            labels,
            data,
            `Weekly Sales - ${monthFilter.options[monthFilter.selectedIndex].text} (<?= $current_year ?>)`
        );
    }

    chartTypeSelect.addEventListener('change', function () {
        const value = this.value;

        if (value === 'top') {
            monthFilter.style.display = 'inline-block';
            updateTopProductsChart(parseInt(monthFilter.value));
        } else if (value === 'weekly_month') {
            monthFilter.style.display = 'inline-block';
            updateWeeklyMonthChart(parseInt(monthFilter.value));
        } else {
            monthFilter.style.display = 'none';

            if (value === 'monthly') {  
                updateBarChart(
                    'bar',
                    ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                    monthlySales,
                    'Monthly Sales (<?= $current_year ?>)'
                );
            } else if (value === 'weekly') {
                updateBarChart(
                    'bar',
                    Array.from({ length: 52 }, (_, i) => 'Week ' + (i + 1)),
                    weeklySales,
                    'Weekly Sales (<?= $current_year ?>)'
                );
            } else if (value === 'daily') {
                updateBarChart(
                    'bar',
                    dailyLabels,
                    dailySales,
                    'Daily Sales (<?= $current_year ?>)'
                );
            } else if (value === 'yearly') {
                updateBarChart(
                    'line',
                    yearlyLabels,
                    yearlySales,
                    'Yearly Sales'
                );
            }
            
            barChart.update();
        }
    });

    monthFilter.addEventListener('change', function () {
        if (chartTypeSelect.value === 'weekly_month') {
            updateWeeklyMonthChart(parseInt(this.value));
        } else if (chartTypeSelect.value === 'top') {
            updateTopProductsChart(parseInt(this.value));
        }
    });
    
    pieChartCategorySelect.addEventListener('change', function() {
        updatePieChart(this.value);
    });

    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        document.getElementById('datetime').textContent = now.toLocaleString('en-US', options);
    }
    
    setInterval(updateDateTime, 1000);
    updateDateTime();
    
    document.addEventListener('DOMContentLoaded', () => {
        updatePieChart('Pork');
    });
</script>
<?php
if (isset($_GET['success'])) {
    $message = htmlspecialchars($_GET['success']);
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'WELCOME BACK!',
                text: '$message',
                icon: 'success'
            });

            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        });
    </script>
    ";
}
?>


</body>
</html>