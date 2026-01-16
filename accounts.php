<?php
require_once 'db_config.php';

$stmt = $pdo->query("SELECT * FROM users ORDER BY role DESC");
$monitoringData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$successMsg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$errorMsg   = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management</title>
<style>
    body { font-family: "Ubuntu", sans-serif; background: #FFF2F2; padding: 20px; }
    h2 { text-align: center; margin-bottom: 20px; }
    .message { width: 100%; max-width: 800px; margin: 10px auto; padding: 10px 15px; border-radius: 6px; text-align: center; font-weight: bold; }
    .success { background-color: #2ecc71; color: white; }
    .error { background-color: #e74c3c; color: white; }
    table { width: 100%; border-collapse: collapse; background: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; margin: 0 auto; }
    th, td { padding: 12px 16px; text-align: left; }
    th { background-color: white; color: black; }
    tr:nth-child(even) { background-color: #A9B5DF; }
    tr:hover { background-color: #dcdde1; }
    .back-button { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background-color: #000000; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background-color 0.3s ease; }
    .back-button:hover { background-color: #2f3640; }
    .action-btn { padding: 6px 12px; border: none; border-radius: 4px; color: white; font-weight: bold; cursor: pointer; text-decoration: none; margin-right: 5px; display: inline-block; }
    .edit-btn { background-color: #3498db; }
    .delete-btn { background-color: #e74c3c; }
    .password-btn { background-color: #f39c12; }
    .admin-row { font-weight: bold; background-color: #f9f0c1 !important; }
</style>
</head>
<body>

<a href="index.php" class="back-button">Back </a>

<h2>User and Admin Accounts</h2>

<?php if ($successMsg): ?>
    <div class="message success"><?= $successMsg ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="message error"><?= $errorMsg ?></div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Birthdate</th>
            <th>Phone</th>
            <th>Gender</th>
            <th>Role</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($monitoringData): ?>
            <?php foreach ($monitoringData as $row): ?>
                <tr class="<?= $row['role'] === 'admin' ? 'admin-row' : '' ?>">
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['birthdate']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['gender']) ?></td>
                    <td><?= htmlspecialchars($row['role']) ?></td>
                    <td>
                        <a href="edit_user.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">Edit</a>
                        
                        <?php if ($row['status'] === 'active'): ?>
                            <a href="deactivate_user.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" 
                            onclick="return confirm('Are you sure you want to deactivate this user?');">Deactivate</a>
                        <?php else: ?>
                            <a href="reactivate_user.php?id=<?= $row['id'] ?>" class="action-btn edit-btn" 
                            onclick="return confirm('Are you sure you want to reactivate this user?');">Reactivate</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No user data available.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
