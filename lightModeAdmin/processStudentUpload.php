<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
include('../connection/connection.php');

// Get current month to determine semester
// $currentMonth = date('n');
// if ($currentMonth >= 8 && $currentMonth <= 12) {
//     $semester = '1st Semester';
// } elseif ($currentMonth >= 1 && $currentMonth <= 5) {
//     $semester = '2nd Semester';
// } else {
//     $semester = 'Midyear (optional)';
// }

// Get form data
$studentNo = $_POST['studentNo'] ?? '';
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$middleName = $_POST['middleInitial'] ?? '';
$email = $_POST['email'] ?? '';
$yearLevel = $_POST['yearLevel'] ?? '';
$departmentId = $_POST['department'] ?? '';

// Validate required fields
if (empty($studentNo) || empty($firstName) || empty($lastName) || empty($email) || empty($yearLevel) || empty($departmentId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Validate year level
if (!in_array($yearLevel, ['1', '2', '3', '4'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid year level']);
    exit();
}

try {
    // Check if student number already exists
    $checkQuery = "SELECT id FROM cit_students WHERE student_number = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $studentNo);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Student number already exists']);
        exit();
    }

    // Check if email already exists
    $checkEmailQuery = "SELECT id FROM cit_students WHERE email = ?";
    $checkEmailStmt = $conn->prepare($checkEmailQuery);
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();

    if ($checkEmailResult->num_rows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Email address already exists']);
        exit();
    }

    // Check if department exists
    $checkDeptQuery = "SELECT id FROM departments WHERE id = ?";
    $checkDeptStmt = $conn->prepare($checkDeptQuery);
    $checkDeptStmt->bind_param("i", $departmentId);
    $checkDeptStmt->execute();
    $checkDeptResult = $checkDeptStmt->get_result();

    if ($checkDeptResult->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid department']);
        exit();
    }

    // Insert new student
    $insertQuery = "INSERT INTO cit_students (student_number, first_name, last_name, middle_name, email, year_level, department_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("sssssis", $studentNo, $firstName, $lastName, $middleName, $email, $yearLevel, $departmentId);
    
    if ($insertStmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Student added successfully']);
    } else {
        throw new Exception($insertStmt->error);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error adding student: ' . $e->getMessage()]);
}

$conn->close();
?>
