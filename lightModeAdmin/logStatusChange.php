<?php
include '../connection/connection.php'; // Include your database connection

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Prepare the SQL statement
$query = "INSERT INTO incident_history (incident_id, previous_status, new_status) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $data['incident_id'], $data['previous_status'], $data['new_status']);

// Execute the statement and check for success
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to log status change']);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
