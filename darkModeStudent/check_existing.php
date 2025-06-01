<?php
include '../connection/connection.php'; // Include the connection file

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Prepare statements to check for existing values
$emailCheck = $conn->prepare("SELECT email FROM users WHERE email = ?");
$emailCheck->bind_param("s", $data['email']);
$emailCheck->execute();
$emailCheck->store_result();

$phoneCheck = $conn->prepare("SELECT phone_number FROM users WHERE phone_number = ?");
$phoneCheck->bind_param("s", $data['phone_number']);
$phoneCheck->execute();
$phoneCheck->store_result();

$studentCheck = $conn->prepare("SELECT student_number FROM users WHERE student_number = ?");
$studentCheck->bind_param("s", $data['student_number']);
$studentCheck->execute();
$studentCheck->store_result();

$userCheck = $conn->prepare("SELECT user_name FROM users WHERE user_name = ?");
$userCheck->bind_param("s", $data['user_name']);
$userCheck->execute();
$userCheck->store_result();

// Prepare the response
$response = [
    'emailExists' => $emailCheck->num_rows > 0,
    'phoneExists' => $phoneCheck->num_rows > 0,
    'studentExists' => $studentCheck->num_rows > 0,
    'userExists' => $userCheck->num_rows > 0
];

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);

// Close the prepared statements
$emailCheck->close();
$phoneCheck->close();
$studentCheck->close();
$userCheck->close();
$conn->close();
?>
