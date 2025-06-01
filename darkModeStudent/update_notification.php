<?php
session_start();
include '../connection/connection.php'; // Include your database connection

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    exit;
}

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);
$incident_id = $data['incident_id'] ?? null; // Incident ID for incident_history
$counseling_id = $data['counseling_id'] ?? null; // Counseling ID for counseling_history

// Log the incident ID for debugging
error_log("Updating notification for incident_id: " . $incident_id);
error_log("Updating notification for counseling_id: " . $counseling_id);

// Initialize response array
$response = ['status' => 'error'];

// Update the notification status in the database
if ($incident_id) {
    $update_query = "UPDATE incident_history SET is_read = '1' WHERE incident_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $incident_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $response['status'] = 'success';
    }
} elseif ($counseling_id) {
    $update_query = "UPDATE counseling_history SET is_read = '1' WHERE counseling_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $counseling_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $response['status'] = 'success';
    }
}

$stmt->close();
$conn->close();

// Return the response as JSON
echo json_encode($response);
?>
