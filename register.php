<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {


    $username  = trim($_POST['username']);
    $password  = trim($_POST['password']);
    $birthdate = trim($_POST['birthdate']);
    $phone     = trim($_POST['phone']);
    $gender    = $_POST['gender'];
    $role      = $_POST['role'];

    if (!preg_match('/^[0-9]+$/', $phone)) {
        $register_error = "❌ Phone number must contain numbers only.";
    } else {

        try {
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $register_error = "❌ Username already exists.";
            } else {

               
                $phoneCheck = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                $phoneCheck->execute([$phone]);

                if ($phoneCheck->rowCount() > 0) {
                    $register_error = "❌ Phone number is already used.";
                } 
                else {

                    
                    if (!in_array($gender, ['male', 'female'])) {
                        $register_error = "❌ Invalid gender.";
                    }
                    
                    elseif (!in_array($role, ['user', 'admin'])) {
                        $register_error = "❌ Invalid role.";
                    } 
                    else {

                      
                        if ($role === 'admin') {
                            $adminCheck = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
                            if ($adminCheck->rowCount() > 0) {
                                $register_error = "❌ Only one admin account is allowed.";
                            } 
                        }

                        if (!isset($register_error)) {

                            
                            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                          
                            $stmt = $pdo->prepare("
                                INSERT INTO users (username, password, birthdate, phone, gender, role)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");

                            $stmt->execute([$username, $hashedPassword, $birthdate, $phone, $gender, $role]);

                            $register_success = "✅ Registration successful. You can now log in.";
                        }
                    }
                }
            }

        } catch (PDOException $e) {
            $register_error = "❌ Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Account</title>
    <link rel="stylesheet" type="text/css" href="register.css">
</head>
<body>

<h1>Register Account</h1>

<?php 
if (isset($register_error)) echo "<p style='color:red;'>$register_error</p>"; 
if (isset($register_success)) echo "<p style='color:green;'>$register_success</p>"; 
?>

<form method="POST" action="">
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
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
        <input type="tel" id="phone" name="phone" maxlength="11" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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

    <div class="form-footer">
        <button type="submit" name="register">Register</button>
        <button type="button" onclick="window.location.href='login.php'">Back to Login</button>
    </div>

</form>

</body>
</html>
