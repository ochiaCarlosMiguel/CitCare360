<?php
// Start the session
session_start();

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['exists' => false]);
    exit;
}

// Assuming you have a database connection file
include '../connection/connection.php';

// Get the user_id from the request
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null; // Get user_id from the request

if ($user_id) {
    // Prepare the SQL statement to check if the user exists
    $query = "SELECT * FROM contact_persons_2 WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, fetch the data
        $user_data = $result->fetch_assoc();
        echo json_encode([
            'exists' => true,
            'last_name' => $user_data['last_name'],
            'first_name' => $user_data['first_name'],
            'middle_name' => $user_data['middle_name'],
            'age' => $user_data['age'],
            'telephone_number' => $user_data['telephone_number'],
            'contact_number' => $user_data['contact_number'],
            'email' => $user_data['email'],
            'complete_address' => $user_data['complete_address'],
            'relationship' => $user_data['relationship']
        ]);
    } else {
        // User does not exist
        echo json_encode(['exists' => false]);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode(['exists' => false]);
}

// Close the database connection
$conn->close();
?>
