<?php
require 'db_config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}

$id = $_GET['id'];
$message = '';
$message_type = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username     = trim($_POST['username']);
    $birthdate    = $_POST['birthdate'];
    $phone        = $_POST['phone'];
    $gender       = $_POST['gender'];
    $role         = $_POST['role'];
 
    $new_password = trim($_POST['new_password'] ?? ''); 

    $clean_phone = preg_replace('/[^0-9]/', '', $phone); 
    
    if (empty($username) || empty($birthdate) || empty($clean_phone) || empty($gender) || empty($role)) {
        $message = "All general fields must be filled.";
        $message_type = 'error';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = 'error';
    } else {
        try {
            
            $sql = "UPDATE users SET username = ?, birthdate = ?, phone = ?, gender = ?, role = ?";
            $params = [$username, $birthdate, $clean_phone, $gender, $role];

          
            if (!empty($new_password)) {
                
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $sql .= ", password = ?";
                $params[] = $new_password_hash;
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $update = $pdo->prepare($sql);
            
            $success = $update->execute($params);

            if ($success) {
        
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC); 
                
                $password_msg = !empty($new_password) ? " and **Password was reset**" : "";

                $message = "User account updated successfully!" . $password_msg;
                $message_type = 'success';
            } else {
                $message = "Update failed due to an unknown error.";
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = "Update failed: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #f4f6f9; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        form {
            background-color: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2D336B;
            font-weight: 700;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="date"],
        input[type="password"], 
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        input[type="password"]:focus, 
        select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #2D336B;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #007bff;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
        }

        .message.success {
            background-color: #e6ffe6;
            color: #28a745;
            border: 1px solid #28a745;
        }

        .message.error {
            background-color: #ffe6e6;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .back-link {
            display: block;
            margin-bottom: 15px;
            color: #2D336B;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            color: #007bff;
        }
    </style>
</head>
<body>

<form method="POST">
    <a href="accounts.php" class="back-link">Back</a>
    <h2>Edit User Account</h2>
    
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <label>Username</label>
    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

    <label>Birthdate</label>
    <input type="date" name="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>" required>

    <label>Phone</label>
    <input 
        type="text" 
        name="phone" 
        value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
        required
        inputmode="numeric" 
        pattern="\d*"
        oninput="this.value = this.value.replace(/[^0-9]/g, '');"
    >

    <label>Gender</label>
    <select name="gender" required>
        <option value="" disabled <?= empty($user['gender']) ? 'selected' : '' ?>>Select Gender</option>
        <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
    </select>

    <label>Role</label>
    <select name="role" required>
        <option value="" disabled <?= empty($user['role']) ? 'selected' : '' ?>>Select Role</option>
        <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    
    <hr style="margin: 20px 0; border-top: 1px solid #eee;">
    
    <label>New Password (Optional Reset)</label>
    <input 
        type="password" 
        name="new_password" 
        placeholder="Leave blank to keep current password"
        autocomplete="new-password"
        minlength="6"
    >
    <p style="font-size: 0.8em; color: #777; margin-top: -10px; margin-bottom: 15px;">
        Leave blank if you don't want to change the password.
    </p>

    <button type="submit">Save Changes</button>
</form>
