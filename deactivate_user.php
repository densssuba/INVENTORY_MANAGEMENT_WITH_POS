<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: accounts.php?error=Invalid user ID");
    exit();
}

$user_id = intval($_GET['id']);

if ($user_id == $_SESSION['user_id']) {
    header("Location: accounts.php?error=You cannot deactivate your own account");
    exit();
}

try {
    
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    
    if ($user && $user['role'] === 'admin') {
        header("Location: accounts.php?error=You cannot deactivate another admin account");
        exit();
    }

    $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$user_id]);

    header("Location: accounts.php?success=User account deactivated successfully");
    exit();

} catch (PDOException $e) {
    header("Location: accounts.php?error=Failed to deactivate user: " . urlencode($e->getMessage()));
    exit();
}
?>
