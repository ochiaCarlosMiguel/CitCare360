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

// Get the user_id from the session
$user_id = $_SESSION['user_id'];

// Check if the form data is set
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $relationship = $_POST['relationship2'];
    $last_name = $_POST['lastname2'];
    $first_name = $_POST['firstname2'];
    $middle_name = $_POST['middlename2'];
    $age = $_POST['age2'];
    $telephone_number = $_POST['telephone2'];
    $contact_number = $_POST['contactnumber2'];
    $email = $_POST['email2'];
    $complete_address = $_POST['completeaddress2'];

    // Validate input data (you can add more validation as needed)
    if (empty($relationship) || empty($last_name) || empty($first_name) || empty($age) || empty($telephone_number) || empty($contact_number) || empty($email) || empty($complete_address)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Prepare the SQL statement to insert data into contact_persons_2 table
    $query = "INSERT INTO contact_persons_2 (user_id, last_name, first_name, middle_name, age, telephone_number, contact_number, relationship, email, complete_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ississssss", $user_id, $last_name, $first_name, $middle_name, $age, $telephone_number, $contact_number, $relationship, $email, $complete_address);

    // Execute the statement and check for success
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save contact person.']);
    }

    // Close the statement
    $stmt->close();
}

// Close the database connection
$conn->close();
?>
