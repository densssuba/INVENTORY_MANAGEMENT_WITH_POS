<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'];
$message = '';
$message_type = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $pdo->prepare('SELECT password FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        $dbPassword = $user['password'];
        $passwordCorrect = false;

        if (password_verify($current_password, $dbPassword)) {
            $passwordCorrect = true;
        } 
        elseif ($dbPassword === md5($current_password)) {
            $passwordCorrect = true;

            $newHash = password_hash($current_password, PASSWORD_DEFAULT);
            $updateOld = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $updateOld->execute([$newHash, $username]);
        }

        if ($passwordCorrect) {

            
            if ($new_password === $confirm_password) {
                
                if (strlen($new_password) < 6) {
                    $message = "New password must be at least 6 characters long.";
                    $message_type = 'error';
                } else {
                    $newHash = password_hash($new_password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE username = ?');
                    $stmt->execute([$newHash, $username]);

                    $message = "Password successfully changed!";
                    $message_type = 'success';
                }

            } else {
                $message = "New password and confirm password do not match.";
                $message_type = 'error';
            }
        } else {
            $message = "Current password is incorrect.";
            $message_type = 'error';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="password.css">
</head>

<body>

    <div class="container">

        <a href="index.php" class="back-btn">Back</a>
        <h1>Change Password</h1>
        <p>Logged in as: <?php echo htmlspecialchars($username); ?></p>

        <?php if ($message) : ?>
            <p class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <div>
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>
            </div>
            <div>
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>
            <div>
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit">Change Password</button>
        </form>
    </div>
</body>

</html>