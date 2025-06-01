<?php
header('Content-Type: application/json');
include '../connection/connection.php';

// Get the JSON data from the request body
$data = json_decode(file_get_contents('php://input'), true);

// Initialize response array
$response = [
    'emailExists' => false,
    'phoneExists' => false,
    'studentExists' => false,
    'studentVerified' => false,
    'validationErrors' => [
        'first_name' => false,
        'last_name' => false,
        'email' => false,
        'student_number' => false,
        'department_id' => false
    ]
];

// Check if student exists in cit_students table
$verifyStudent = $conn->prepare("SELECT first_name, last_name, email, student_number, department_id FROM cit_students WHERE 
    student_number = ? OR 
    email = ?");
$verifyStudent->bind_param("ss", 
    $data['student_number'],
    $data['email']
);
$verifyStudent->execute();
$result = $verifyStudent->get_result();

if ($result->num_rows > 0) {
    $studentData = $result->fetch_assoc();
    
    // Check each field individually
    if (strtolower($studentData['first_name']) !== strtolower($data['first_name'])) {
        $response['validationErrors']['first_name'] = true;
    }
    if (strtolower($studentData['last_name']) !== strtolower($data['last_name'])) {
        $response['validationErrors']['last_name'] = true;
    }
    if (strtolower($studentData['email']) !== strtolower($data['email'])) {
        $response['validationErrors']['email'] = true;
    }
    if ($studentData['student_number'] !== $data['student_number']) {
        $response['validationErrors']['student_number'] = true;
    }
    if ($studentData['department_id'] != $data['department_id']) {
        $response['validationErrors']['department_id'] = true;
    }

    // If all fields match, mark as verified
    if (!in_array(true, $response['validationErrors'])) {
        $response['studentVerified'] = true;
    }
}

// Check for existing email in users table
$checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
$checkEmail->bind_param("s", $data['email']);
$checkEmail->execute();
$checkEmail->store_result();
if ($checkEmail->num_rows > 0) {
    $response['emailExists'] = true;
}

// Check for existing phone number in users table
$checkPhone = $conn->prepare("SELECT phone_number FROM users WHERE phone_number = ?");
$checkPhone->bind_param("s", $data['phone_number']);
$checkPhone->execute();
$checkPhone->store_result();
if ($checkPhone->num_rows > 0) {
    $response['phoneExists'] = true;
}

// Check for existing student number in users table
$checkStudent = $conn->prepare("SELECT student_number FROM users WHERE student_number = ?");
$checkStudent->bind_param("s", $data['student_number']);
$checkStudent->execute();
$checkStudent->store_result();
if ($checkStudent->num_rows > 0) {
    $response['studentExists'] = true;
}

// Close statements
$verifyStudent->close();
$checkEmail->close();
$checkPhone->close();
$checkStudent->close();

// Close connection
$conn->close();

// Return the response
echo json_encode($response);
?> 