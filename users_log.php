<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$sessionExpiration = 480;

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            ul.login_time,
            ul.logout_time,
            TIMESTAMPDIFF(
                MINUTE,
                ul.login_time,
                IFNULL(
                    ul.logout_time,
                    LEAST(
                        NOW(),
                        DATE_ADD(ul.login_time, INTERVAL :duration MINUTE)
                    )
                )
            ) AS session_duration,
            CASE 
                WHEN ul.logout_time IS NULL 
                     AND TIMESTAMPDIFF(MINUTE, ul.login_time, NOW()) > :expire_time 
                     THEN 'Expired'
                WHEN ul.logout_time IS NULL 
                     THEN 'Active'
                ELSE 'Logged Out'
            END AS session_status

        FROM users_log ul       
        JOIN users u ON ul.user_id = u.id
        ORDER BY ul.login_time DESC
    ");

    $stmt->execute([
        'duration'    => $sessionExpiration,
        'expire_time' => $sessionExpiration
    ]);

    $monitoringData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching monitoring data: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User and Admin Monitoring</title>

<style>
    body {
        font-family: "Arial", sans-serif;
        background: white;
        padding: 20px;
        
    }

    h2 {
        color: white;
        text-align: center;
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-radius: 8px;
        overflow: hidden;
        margin: 0 auto;
    }

    th, td {
        padding: 12px 16px;
        text-align: left;
    }

    th {
        background-color: #2D336B;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f1f2f6;
    }

    tr:hover {
        background-color: #dcdde1;
    }

    .back-button {
        display: inline-block;
        margin: 20px 0;
        padding: 10px 20px;
        background: #000000;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        transition: 0.3s;
    }

    .back-button:hover {
        background: #2f3640;
    }

    .status-active {
        color: green;
        font-weight: bold;
    }

    .status-expired {
        color: red;
        font-weight: bold;
    }

    .status-loggedout {
        color: gray;
        font-weight: bold;
    }

    @media (max-width: 768px) {
        table, th, td {
            font-size: 14px;
        }
    }
</style>
</head>

<body>

<a href="index.php" class="back-button">Back</a>

<h2>User and Admin Login Logs</h2>

<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Login Time</th>
            <th>Logout Time</th>
            <th>Session Duration (min)</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($monitoringData): ?>
            <?php foreach ($monitoringData as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>

                    <td><?= htmlspecialchars($row['login_time']) ?></td>

                    <td>
                        <?= $row['logout_time'] 
                            ? htmlspecialchars($row['logout_time']) 
                            : '<em>Not Logged Out</em>' ?>
                    </td>

                    <td><?= $row['session_duration'] ?></td>

                    <td class="status-<?= strtolower(str_replace(' ', '', $row['session_status'])) ?>">
                        <?= $row['session_status'] ?>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align:center;">No monitoring data available.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
