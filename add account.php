<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location:index.php');
    exit;
}

$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    $username  = trim($_POST['username']);
    $password  = trim($_POST['password']);
    $birthdate = $_POST['birthdate'];
    $phone     = $_POST['phone'];
    $gender    = $_POST['gender'];
    $role      = $_POST['role']; 

    if ($username === '' || $password === '') {
        $register_error = 'Username and password are required.';
    } else {

        try {
            $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $register_error = 'Username already exists.';
            } else {

                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare('
                    INSERT INTO users (username, password, birthdate, phone, gender, role)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$username, $hashed, $birthdate, $phone, $gender, $role]);
                $register_success = '✅ Registration successful.';
            }
        } catch (PDOException $e) {
            $register_error = '❌ Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Account</title>
    <link rel="stylesheet" href="register.css">
</head>

<style>
.btn {
    display: inline-block;
    padding: 12px 25px;
    background-color: #4CAF50;
    color: white;
    font-size: 16px; 
    font-family:"Ubuntu", sans-serif;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.btn:hover {
    background-color: #45a049;
}
.btn-secondary {
    background-color: #888;
}
.btn-secondary:hover {
    background-color: #666;
}
.button-group {
    display: flex;
    justify-content: center; 
    gap: 15px; 
    margin-top: 20px;
}
</style>

<body>
    
<h1>Create New User</h1>

<?php if ($register_error): ?>
    <p style="color:red;"><?= $register_error ?></p>
<?php endif; ?>

<?php if ($register_success): ?>
    <p style="color:green;"><?= $register_success ?></p>
<?php endif; ?>

<form method="POST">

    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required autofocus>
    </div>

    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>

    <div class="form-group">
        <label for="birthdate">Birthdate:</label>
        <input type="date" id="birthdate" name="birthdate" required>
    </div>

    <div class="form-group">
        <label for="phone">Phone:</label>
        <input type="tel" id="phone" name="phone"
               maxlength="11"
               pattern="[0-9]{11}"
               oninput="this.value = this.value.replace(/[^0-9]/g, '');"
               required>
    </div>

    <div class="form-group">
        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
        </select>
    </div>

    <div class="form-group">
        <label for="role">Role:</label>
        <select id="role" name="role" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
    </div>

    <button type="submit" name="register">Register</button>
    <button type="button" onclick="window.location.href='index.php'">Back</button>

</form>

</body>
</html>
