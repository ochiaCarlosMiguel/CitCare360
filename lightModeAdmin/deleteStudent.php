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

// Get the JSON data from the request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['student_number'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Student number is required']);
    exit();
}

$studentNumber = $data['student_number'];
$status = $data['status'];

try {
    // Start transaction
    $conn->begin_transaction();

    // If student is registered, delete from users table first
    if ($status === 'Registered') {
        $deleteUserQuery = "DELETE FROM users WHERE student_number = ?";
        $stmt = $conn->prepare($deleteUserQuery);
        $stmt->bind_param("s", $studentNumber);
        $stmt->execute();
    }

    // Delete from cit_students table
    $deleteStudentQuery = "DELETE FROM cit_students WHERE student_number = ?";
    $stmt = $conn->prepare($deleteStudentQuery);
    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error deleting student: ' . $e->getMessage()]);
}
?> 