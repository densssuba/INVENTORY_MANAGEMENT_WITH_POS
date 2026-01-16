<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$cashierName = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $_SESSION['username'];

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'role_based_authentication';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$productsByCategory = [];
$productPrices = [];
$result = $conn->query("SELECT product_name, category, price FROM add_product");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $category = strtoupper($row['category']); 
        if (!isset($productsByCategory[$category])) {
            $productsByCategory[$category] = [];
        }
        $productsByCategory[$category][] = $row['product_name'];
        $productPrices[$row['product_name']] = (float)$row['price'];
    }
    $result->free();
}
$conn->close();

$productsByCategoryJson = json_encode($productsByCategory);
$productPricesJson = json_encode($productPrices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>POS Transaction</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: white;
  padding: 40px 20px 0;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
      -ms-flex-direction: column;
          flex-direction: column;
  -webkit-box-align: center;
      -ms-flex-align: center;
          align-items: center;
  margin: 0;
  background-image: url("deans01.jpg");
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    background-attachment: fixed;
}

.header-controls {
    width: 95%; 
    max-width: 1300px; 
    display: -webkit-box; 
    display: -ms-flexbox; 
    display: flex; 
    -webkit-box-pack: start; 
        -ms-flex-pack: start; 
            justify-content: flex-start; 
    margin-bottom: 15px; 
}
.back-btn {
    display: inline-block;
    background: black;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    font-weight: bold;
    -webkit-transition: 0.2s ease;
    -o-transition: 0.2s ease;
    transition: 0.2s ease;
   
}
.back-btn:hover {
  background: #b3b3b3;
}

form {
  background: whitesmoke;
  padding: 40px 25px;
  border-radius: 12px;
  -webkit-box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
          box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
  width: 95%;
  max-width: 1300px;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -ms-flex-wrap: wrap;
      flex-wrap: wrap;
  gap: 20px;
  -webkit-box-pack: justify;
      -ms-flex-pack: justify;
          justify-content: space-between;
}

.add-product-btn {
  background: black;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 10px 16px;
  font-weight: bold;
  cursor: pointer;
  margin-bottom: 15px;
  float: left;
}


form::after {
  content: "";
  display: block;
  clear: both;
}

.form-group {
  -webkit-box-flex: 1;
      -ms-flex: 1 1 calc(33.33% - 20px);
          flex: 1 1 calc(33.33% - 20px);
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-align: center;
      -ms-flex-align: center;
          align-items: center;
  gap: 10px;
}


label {
  width: 120px;
  font-weight: bold;
  text-align: right;
}


input,
select {
  -webkit-box-flex: 1;
      -ms-flex: 1;
          flex: 1;
  padding: 10px;
  border-radius: 10px;
  font-size: 14px;
  border: 1px solid #ccc;
}


.products-table {
  width: 100%;
  margin-bottom: 20px;
  border-collapse: collapse;
}

.products-table th,
.products-table td {
  padding: 10px;
  border-bottom: 1px solid #ccc;
  text-align: left;
}

.products-table th {
  background: #f3f3f3;
}

.remove-product {
  background: black;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 5px 10px;
  cursor: pointer;
}


.form-footer {
  -webkit-box-flex: 1;
      -ms-flex: 1 1 100%;
          flex: 1 1 100%;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-pack: center;
      -ms-flex-pack: center;
          justify-content: center;
  gap: 15px;
  margin-top: 30px;
  -ms-flex-wrap: wrap;
      flex-wrap: wrap;
}

.form-footer button {
  width: 250px;
  padding: 14px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  background-color: black;
  color: #fff;
  -webkit-transition: 0.2s;
  -o-transition: 0.2s;
  transition: 0.2s;
}

.form-footer button:hover {
  background-color: #5f6aa3;
}

@media (max-width: 768px) {
  body {
    padding: 10px;
  }

  form {
    padding: 25px 20px;
    gap: 15px;
  }

  .form-group {
    -webkit-box-flex: 1;
        -ms-flex: 1 1 100%;
            flex: 1 1 100%;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
        -ms-flex-direction: column;
            flex-direction: column;
    -webkit-box-align: start;
        -ms-flex-align: start;
            align-items: flex-start;
  }

  label {
    width: 100%;
    text-align: left;
    font-size: 14px;
  }

  input,
  select {
    width: 100%;
    font-size: 16px;
  }

  .products-table th,
  .products-table td {
    font-size: 14px;
    padding: 8px;
  }

  .form-footer button,
  .add-product-btn,
  .back-btn {
    width: 100%;
    text-align: center;
    padding: 12px;
  }
}


@media (max-width: 480px) {
  form {
    padding: 20px 15px;
  }

  input,
  select {
    padding: 12px;
    font-size: 16px;
  }

  .products-table {
    font-size: 12px;
  }
}


@media print {
  button,
  .add-product-btn,
  .remove-product,
  .back-btn {
    display: none !important;
  }
}
</style>
</head>
<body>

<div class="header-controls">
    <a href="index.php" class="back-btn">Back</a>
</div>
<form action="save_transaction.php" method="POST" id="posForm">

    <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
      <button type="button" class="add-product-btn" onclick="addProductRow()">Add Product</button>
      
      <select id="productCategory" onchange="updateProductList()" style="padding:10px; border-radius:8px; margin-bottom:15px">
        <option value="">Select Category</option>
        <option value="PORK">PORK</option>
        <option value="CHICKEN">CHICKEN</option>
        <option value="BEEF">BEEF</option>
        <option value="OTHERS">OTHERS</option>
      </select>
    </div>

    <table class="products-table" id="productsTable">
      <thead>
        <tr>
          <th>Product</th>
          <th>Quantity</th>
          <th>Price/Unit</th>
          <th>Remove</th>
        </tr>
      </thead>
      <tbody id="productsTbody">
        <tr>
          <td>
            <input type="text" name="product_name[]" list="productList" placeholder="Type or select product" required>
            <datalist id="productList"></datalist>
          </td>
          <td><input type="number" name="quantity[]" class="qty" required></td>
          <td><input type="number" step="0.00" name="price[]" class="price" required></td>
          <td><button type="button" class="remove-product" onclick="removeProductRow(this)">Remove</button></td>
        </tr>
      </tbody>
    </table>  

    <div class="form-group">
      <label for="cashier_name">Cashier:</label>
      <input type="text" name="cashier_name" id="cashier_name" value="<?= htmlspecialchars($cashierName) ?>" readonly required>
    </div>
    <div class="form-group">
      <label for="customer_name">Customer:</label>
      <input type="text" name="customer_name" id="customer_name" required>
    </div>
    <div class="form-group">
      <label for="payment_method">Payment:</label>
      <select name="payment_method" id="payment_method" required>
        <option value="Cash">Cash</option>
      </select>
    </div>
    <div class="form-group">
      <label for="total">Total:</label>
      <input type="text" id="total" name="total_display" readonly>
    </div>
    <div class="form-group">
      <label for="cash">Cash Received:</label>
      <input type="number" step="0.01" id="cash" name="cash_received">
    </div>
    <div class="form-group">
      <label for="change">Change:</label>
      <input type="text" id="change" name="change_display" readonly>
    </div>

    <input type="hidden" name="total_price" id="hidden_total">
    <input type="hidden" name="change_amount" id="hidden_change">

    <div class="form-footer">
      <button type="submit">Submit & Print Receipt</button>
      <button type="reset" style="background: black;">Clear</button>
    </div>
</form>

<script>
const productsByCategory = <?php echo $productsByCategoryJson; ?>;
const productPrices = <?php echo $productPricesJson; ?>;

function updateProductList() {
  const category = document.getElementById("productCategory").value;
  const options = productsByCategory[category] || [];
  const datalist = document.getElementById("productList");
  datalist.innerHTML = options.map(opt => `<option value="${opt}">`).join("");
}

function addProductRow() {
  const tbody = document.getElementById('productsTbody');
  const row = document.createElement('tr');
  row.innerHTML = `
    <td><input type="text" name="product_name[]" list="productList" placeholder="Type or select product" required></td>
    <td><input type="number" name="quantity[]" class="qty" required></td>
    <td><input type="number" step="0.01" name="price[]" class="price" required></td>
    <td><button type="button" class="remove-product" onclick="removeProductRow(this)">Remove</button></td>
  `;
  tbody.appendChild(row);
  addRowListeners(row);
  calculateTotalAndChange();
}

function removeProductRow(btn) {
  const row = btn.closest('tr');
  row.parentNode.removeChild(row);
  calculateTotalAndChange();
}

function getNumbersFromInputs(selector) {
  return Array.from(document.querySelectorAll(selector)).map(input => parseFloat(input.value) || 0);
}

function calculateTotalAndChange() {
  const qtyArr = getNumbersFromInputs('.qty');
  const priceArr = getNumbersFromInputs('.price');
  let sum = 0;

  for (let i = 0; i < qtyArr.length; i++) {
    sum += qtyArr[i] * priceArr[i];
  }

  document.getElementById('total').value = sum.toFixed(2);
  document.getElementById('hidden_total').value = sum.toFixed(2);

  const cashVal = parseFloat(document.getElementById('cash').value) || 0;
  const change = document.getElementById('change');
  const hiddenChange = document.getElementById('hidden_change');
  const submitBtn = document.querySelector("button[type='submit']");

  if (cashVal < sum) {
    change.value = "Insufficient";
    hiddenChange.value = 0;
    submitBtn.disabled = true;
    submitBtn.style.opacity = 0.5;
    submitBtn.style.cursor = "not-allowed";
  } else {
    const changeVal = cashVal - sum;
    change.value = changeVal.toFixed(2);
    hiddenChange.value = change.value;
    submitBtn.disabled = false;
    submitBtn.style.opacity = 1;
    submitBtn.style.cursor = "pointer";
  }
}

function addRowListeners(row) {
  const inputs = row.querySelectorAll('.qty, .price');
  inputs.forEach(input => input.addEventListener('input', calculateTotalAndChange));

  const productInput = row.querySelector("input[name='product_name[]']");
  productInput.addEventListener('input', function() {
    const priceInput = row.querySelector('.price');
    if (productPrices[this.value] !== undefined) {
      priceInput.value = productPrices[this.value];
      calculateTotalAndChange();
    }
  });
}

document.querySelectorAll('.qty, .price').forEach(input => input.addEventListener('input', calculateTotalAndChange));
document.querySelectorAll("input[name='product_name[]']").forEach(input => {
  input.addEventListener('input', function() {
    const row = input.closest('tr');
    const priceInput = row.querySelector('.price');
    if (productPrices[this.value] !== undefined) {
      priceInput.value = productPrices[this.value];
      calculateTotalAndChange();
    }
  });
});

document.getElementById('cash').addEventListener('input', calculateTotalAndChange);

document.getElementById("posForm").addEventListener("reset", function() {
  setTimeout(() => {
    document.getElementById('change').value = "";
    document.getElementById('total').value = "";
    document.getElementById('hidden_total').value = "";
    document.getElementById('hidden_change').value = "";

    const tbody = document.getElementById('productsTbody');
    while (tbody.rows.length > 1) {
      tbody.deleteRow(1);
    }
    calculateTotalAndChange();
  }, 10);
});

calculateTotalAndChange();
</script>


</body>
</html>
