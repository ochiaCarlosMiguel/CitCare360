<?php
session_start();
include('../connection/connection.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$admin_id = $_SESSION['user_id'];
$date = $_GET['date'];

$stmt = $conn->prepare("SELECT blocked_time FROM admin_blocked_times WHERE admin_id = ? AND blocked_date = ?");
$stmt->bind_param("is", $admin_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$blocked_times = [];
while ($row = $result->fetch_assoc()) {
    $blocked_times[] = $row['blocked_time'];
}

echo json_encode($blocked_times);
