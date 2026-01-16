<?php
session_start();
require_once 'db_config.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();

    
    $stmt = $pdo->prepare("UPDATE users_log SET logout_time = NOW() WHERE user_id = ? AND session_id = ? AND logout_time IS NULL");
    $stmt->execute([$user_id, $session_id]);
}


session_unset();
session_destroy();

header("Location: login.php");
exit();
?>
