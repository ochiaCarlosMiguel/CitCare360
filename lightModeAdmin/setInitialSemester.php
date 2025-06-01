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
    // Get semester from POST request
    $semester = $_POST['semester'] ?? '';

    // Validate semester
    if (!in_array($semester, ['1', '2'])) {
        throw new Exception('Invalid semester value');
    }

    // Check if semester setting exists
    $checkQuery = "SELECT COUNT(*) as count FROM settings WHERE setting_name = 'current_semester'";
    $result = $conn->query($checkQuery);
    $row = $result->fetch_assoc();
    $exists = $row['count'] > 0;

    if ($exists) {
        // Update existing semester
        $query = "UPDATE settings SET value = ? WHERE setting_name = 'current_semester'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $semester);
        $stmt->execute();
    } else {
        // Insert new semester
        $query = "INSERT INTO settings (setting_name, value) VALUES ('current_semester', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $semester);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Semester updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error setting semester: ' . $e->getMessage()]);
}

$conn->close();
?> 