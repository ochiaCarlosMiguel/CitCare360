<?php
session_start();
require_once '../connection/connection.php';
require '../vendor/autoload.php'; // You'll need to install PhpSpreadsheet via Composer

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

try {
    // Load the Excel file
    $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Remove header row
    array_shift($rows);

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO cit_students 
        (student_number, first_name, last_name, middle_name, email, year_level, department_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Prepare statements for validation checks
    $checkNameStmt = $conn->prepare("SELECT COUNT(*) as count FROM cit_students 
        WHERE first_name = ? AND last_name = ? AND middle_name = ?");
    $checkEmailStmt = $conn->prepare("SELECT COUNT(*) as count FROM cit_students WHERE email = ?");

    foreach ($rows as $row) {
        // Skip empty rows
        if (empty($row[0])) continue;

        try {
            // Validate Year Level
            $yearLevel = intval($row[5]);
            if ($yearLevel < 1 || $yearLevel > 4) {
                throw new Exception("Invalid Year Level: " . $row[5] . ". Must be between 1 and 4.");
            }

            // Check for duplicate names
            $checkNameStmt->bind_param("sss", $row[1], $row[2], $row[3]);
            $checkNameStmt->execute();
            $nameResult = $checkNameStmt->get_result();
            $nameCount = $nameResult->fetch_assoc()['count'];
            
            if ($nameCount > 0) {
                throw new Exception("Duplicate name combination found: " . $row[1] . " " . $row[2] . " " . $row[3]);
            }

            // Check for duplicate email
            $checkEmailStmt->bind_param("s", $row[4]);
            $checkEmailStmt->execute();
            $emailResult = $checkEmailStmt->get_result();
            $emailCount = $emailResult->fetch_assoc()['count'];
            
            if ($emailCount > 0) {
                throw new Exception("Duplicate email found: " . $row[4]);
            }

            // Get department ID from department name
            $deptStmt = $conn->prepare("SELECT id FROM departments WHERE name = ?");
            $deptStmt->bind_param("s", $row[6]); // Department name is now in column G
            $deptStmt->execute();
            $deptResult = $deptStmt->get_result();
            
            if ($deptResult->num_rows === 0) {
                throw new Exception("Department not found: " . $row[6]);
            }
            
            $departmentId = $deptResult->fetch_assoc()['id'];
            
            // Bind parameters for the main insert
            $stmt->bind_param("sssssis", 
                $row[0], // Student Number
                $row[1], // First Name
                $row[2], // Last Name
                $row[3], // Middle Name
                $row[4], // Email
                $yearLevel, // Year Level (validated)
                $departmentId
            );

            if ($stmt->execute()) {
                $successCount++;
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $errorCount++;
            $errors[] = "Row " . ($successCount + $errorCount) . ": " . $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Import completed. Successfully imported $successCount records. Failed: $errorCount",
        'errors' => $errors
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing file: ' . $e->getMessage()
    ]);
}
?> 