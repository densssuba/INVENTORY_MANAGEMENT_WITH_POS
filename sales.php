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


$search    = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $conn->real_escape_string($_GET['from_date']) : '';
$to_date   = isset($_GET['to_date']) ? $conn->real_escape_string($_GET['to_date']) : '';

$sql = "SELECT * FROM sales_transactions WHERE 1";


if (!empty($search)) {
    $sql .= " AND (cashier_name LIKE '%$search%' OR product_name LIKE '%$search%')";
}

// 2. Strict Date Filtering
if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'";
} elseif (!empty($from_date)) {
    $sql .= " AND created_at >= '$from_date 00:00:00' AND created_at <= '$from_date 23:59:59'";
} elseif (!empty($to_date)) {
    $sql .= " AND created_at >= '$to_date 00:00:00' AND created_at <= '$to_date 23:59:59'";
}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);


if (isset($_GET['ajax'])) {
    $html = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['created_at']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['product_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['quantity']) . '</td>';
            $html .= '<td>₱' . number_format($row['price'], 2) . '</td>';
            $html .= '<td>₱' . number_format($row['total_price'], 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['cashier_name'] ?? 'N/A') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['payment_method'] ?? 'N/A') . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="7" style="text-align: center; padding: 20px; font-weight: bold; color: #555;">No transactions found for this specific date/search.</td></tr>';
    }
    
    echo json_encode([
        'html' => $html,
        'count' => $result->num_rows
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Transactions</title>
    <link rel="stylesheet" href="product.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .search-input {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .search-btn {
            padding: 8px 12px;
            border: none;
            background-color: black;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
        }
        .search-btn:hover {
            opacity: 0.8;
        }
        .filter-label {
            font-weight: bold;
            margin-right: 5px;
            font-size: 14px;
        }
        .filter-bar {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
            flex-wrap: wrap; 
            gap: 15px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }

   
        #printHeader {
            display: none;
            text-align: center;
            margin-bottom: 20px;
        }
        #printHeader h2 { margin: 0; }
        #printHeader p { margin: 5px 0 0 0; color: #666; font-size: 14px; }

        
        @media print {
           
            .sidebar, .filter-bar, .tabs, .add-btn, .search-btn {
                display: none !important;
            }

         
            .main-container {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            #printHeader {
                display: block !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 12px !important;
            }
            th, td {
                border: 1px solid #000 !important;
                padding: 5px !important;
            }
            
            body { background-color: white !important; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="index.php" class="sidebar-icon" title="Back to Dashboard">
        <i class="fa-solid fa-backward"></i>
    </a>
    <a href="monthly_sales.php" class="sidebar-icon" title="Monthly Sales">
        <i class="far fa-calendar-alt"></i>
    </a>
</div>

<div class="main-container">
    <div class="header"><div class="title">Sales Transactions</div></div>

    <div class="content">
        <div class="tabs">
            <a class="tab active">Sales History</a>
        </div>

        <div class="filter-bar">
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                
                <input type="text" class="search-input" id="searchInput" name="search" placeholder="Search product or cashier..." 
                       value="<?= htmlspecialchars($search) ?>" oninput="debouncedFilter()">

                <div>
                    <span class="filter-label">From:</span>
                    <input type="date" class="search-input" id="fromDateInput" name="from_date" 
                           value="<?= htmlspecialchars($from_date) ?>" onchange="autoFilter()">
                </div>

                <div>
                    <span class="filter-label">To:</span>
                    <input type="date" class="search-input" id="toDateInput" name="to_date" 
                           value="<?= htmlspecialchars($to_date) ?>" onchange="autoFilter()">
                </div>
                
                <button type="button" class="search-btn" onclick="clearFilters()" style="background-color: #666;">
                    Clear
                </button>
            </div>

            <?php if ($_SESSION['role'] === 'admin'): ?>          
                <button onclick="printReport()" class="search-btn" style="background-color: #d32f2f;">
                    <i class="fas fa-print"></i> Print / Save PDF
                </button>
            <?php endif; ?>
        </div>

        <div id="printHeader">
            <h2 id="printTitle">Sales Transaction Report</h2>
            <p id="printDate"></p>
        </div>

        <div class="summary">
            <div class="stats">
                <div class="stat">
                    Total Transactions: <span class="stat-value total" id="totalTransactions"><?= $result->num_rows ?></span>
                </div>
            </div>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button class="add-btn" onclick="location.href='export_sales.php'" style="background: #0f7b0f;">
                <i class="fas fa-file-excel"></i> EXPORT TO EXCEL
            </button>
            <?php endif; ?>
        </div>

        <table id="salesTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Cashier</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody id="salesTableBody">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                        <td>₱<?= number_format($row['price'], 2) ?></td>
                        <td>₱<?= number_format($row['total_price'], 2) ?></td>
                        <td><?= htmlspecialchars($row['cashier_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['payment_method'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; padding: 20px; font-weight: bold; color: #555;">No transactions found for this specific date/search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>

<script>
let typingTimer;
const doneTypingInterval = 500;

function debouncedFilter() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(autoFilter, doneTypingInterval);
}

function autoFilter() {
    const search = document.getElementById('searchInput').value;
    const fromDate = document.getElementById('fromDateInput').value;
    const toDate = document.getElementById('toDateInput').value;

    const params = new URLSearchParams();
    params.append('search', search);
    params.append('from_date', fromDate);
    params.append('to_date', toDate);
    params.append('ajax', '1');

    const newUrl = window.location.pathname + "?" + params.toString().replace('&ajax=1', '');
    window.history.pushState({path: newUrl}, '', newUrl);

    fetch('?' + params.toString())
        .then(response => response.json())
        .then(data => {
            document.getElementById('salesTableBody').innerHTML = data.html;
            document.getElementById('totalTransactions').innerText = data.count;
        })
        .catch(error => console.error('Error:', error));
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('fromDateInput').value = '';
    document.getElementById('toDateInput').value = '';
    autoFilter(); 
}


function printReport() {
  
    const fromDate = document.getElementById('fromDateInput').value;
    const toDate = document.getElementById('toDateInput').value;
    
    let title = "Sales Transaction Report";
    let subTitle = `Generated on: ${new Date().toLocaleString()}`;

    if (fromDate && toDate) {
        title += ` (${fromDate} to ${toDate})`;
    } else if (fromDate) {
        title += ` (From ${fromDate})`;
    } else if (toDate) {
        title += ` (On ${toDate})`;
    }

   
    document.getElementById('printTitle').innerText = title;
    document.getElementById('printDate').innerText = subTitle;


    window.print();
}
</script>

</body>
</html>
<?php $conn->close(); ?>