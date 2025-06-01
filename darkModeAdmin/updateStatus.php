<?php
// Include the database connection file
include '../connection/connection.php';

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);
$incidentId = $data['id'];
$newStatus = $data['status'];

// Prepare the SQL statement
$stmt = $conn->prepare("UPDATE incidents SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $incidentId);

// Execute the statement
if ($stmt->execute()) {
    // Success response
    echo json_encode(['success' => true]);
} else {
    // Error response
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>