<?php
session_start();
include '../connection/connection.php';

header('Content-Type: application/json');

if (!isset($_GET['counselor']) || !isset($_GET['date'])) {
    echo json_encode(['error' => 'Missing parameters', 'blocked_times' => []]);
    exit;
}

$counselor = $_GET['counselor'];
$date = $_GET['date'];

try {
    // First, get the counselor's ID
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE name = ? AND user_role = 'Counselor'");
    $stmt->bind_param("s", $counselor);
    $stmt->execute();
    $result = $stmt->get_result();
    $counselorData = $result->fetch_assoc();

    if (!$counselorData) {
        echo json_encode(['error' => 'Counselor not found', 'blocked_times' => []]);
        exit;
    }

    $counselorId = $counselorData['id'];

    // Get blocked times from counselor_blocked_times table
    $stmt = $conn->prepare("SELECT TIME_FORMAT(blocked_time, '%H:%i') as formatted_time 
                           FROM counselor_blocked_times 
                           WHERE counselor_id = ? AND blocked_date = ?");
    $stmt->bind_param("is", $counselorId, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $blockedTimes = [];
    while ($row = $result->fetch_assoc()) {
        $blockedTimes[] = $row['formatted_time'];
    }

    // Also check existing appointments
    $stmt = $conn->prepare("SELECT TIME_FORMAT(preferred_time, '%H:%i') as formatted_time 
                           FROM counseling_appointments 
                           WHERE preferred_counselor = ? 
                           AND preferred_date = ? 
                           AND status != 'cancelled'");
    $stmt->bind_param("ss", $counselor, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $blockedTimes[] = $row['formatted_time'];
    }

    echo json_encode([
        'blocked_times' => array_unique($blockedTimes),
        'counselor_id' => $counselorId
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'blocked_times' => []]);
}
