<?php
session_start();
include('../connection/connection.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$selected_date = $_POST['selectedDate'];
$blocked_times = $_POST['blocked_times'] ?? [];

try {
    // Begin transaction
    $conn->begin_transaction();

    // First, delete existing blocked times for this admin and date
    $stmt = $conn->prepare("DELETE FROM admin_blocked_times WHERE admin_id = ? AND blocked_date = ?");
    $stmt->bind_param("is", $admin_id, $selected_date);
    $stmt->execute();

    // Insert new blocked times
    if (!empty($blocked_times)) {
        $stmt = $conn->prepare("INSERT INTO admin_blocked_times (admin_id, blocked_date, blocked_time) VALUES (?, ?, ?)");
        
        foreach ($blocked_times as $time) {
            $stmt->bind_param("iss", $admin_id, $selected_date, $time);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
