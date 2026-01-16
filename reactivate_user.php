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

try {
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$user_id]);

    header("Location: accounts.php?success=User account reactivated successfully");
    exit();
} catch (PDOException $e) {
    header("Location: accounts.php?error=Failed to reactivate user: " . urlencode($e->getMessage()));
    exit();
}
?>
