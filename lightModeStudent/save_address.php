<?php
// Start the session
session_start();

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if user_id is not set
    header("Location: ../studentPortal/login.php");
    exit;
}

// Assuming you have a database connection file
include '../connection/connection.php';

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Check if the request method is POST and the required fields are set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['houseNumber'])) {
    $house_number = $_POST['houseNumber'];
    $province = $_POST['province'];
    $municipality = $_POST['municipality'];
    $barangay = $_POST['barangay'];
    $zip_code = $_POST['zipcode'];

    // Insert the address into the database
    $insert_query = "INSERT INTO user_addresses (user_id, house_number, province, municipality, barangay, zip_code) VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("isssss", $user_id, $house_number, $province, $municipality, $barangay, $zip_code);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save address.']);
    }
    $insert_stmt->close();
    exit; // Stop further execution
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
