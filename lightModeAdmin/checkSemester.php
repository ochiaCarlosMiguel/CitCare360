<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Include database connection
include('../connection/connection.php');

try {
    // Check if semester is set
    $checkQuery = "SELECT COUNT(*) as count FROM settings WHERE setting_name = 'current_semester'";
    $checkResult = $conn->query($checkQuery);
    $count = $checkResult->fetch_assoc()['count'];

    echo json_encode(['success' => true, 'isSet' => $count > 0]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error checking semester: ' . $e->getMessage()]);
}

$conn->close();
?> 