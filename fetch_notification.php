<?php
require_once 'db_config.php';

$result = $conn->query("
    SELECT message, created_at 
    FROM admin_notifications 
    ORDER BY id DESC 
    LIMIT 30
");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(array_reverse($data));
