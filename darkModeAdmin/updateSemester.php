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
    // Start transaction
    $conn->begin_transaction();

    // Get current semester
    $semesterQuery = "SELECT value FROM settings WHERE setting_name = 'current_semester'";
    $semesterResult = $conn->query($semesterQuery);
    $currentSemester = $semesterResult->fetch_assoc()['value'] ?? '1';

    // Calculate new semester
    $newSemester = $currentSemester == '1' ? '2' : '1';

    // Update semester in settings
    $updateSemesterQuery = "UPDATE settings SET value = ? WHERE setting_name = 'current_semester'";
    $stmt = $conn->prepare($updateSemesterQuery);
    $stmt->bind_param("s", $newSemester);
    $stmt->execute();

    // If changing from Semester 2 to Semester 1, update year levels
    if ($currentSemester == '2') {
        // Update year levels for all students
        $updateYearQuery = "UPDATE cit_students SET year_level = year_level + 1 WHERE year_level < 4";
        $conn->query($updateYearQuery);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Semester updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error updating semester: ' . $e->getMessage()]);
}

$conn->close();
?> 