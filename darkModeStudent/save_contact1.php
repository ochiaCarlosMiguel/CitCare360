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

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the input data from the form
    $relationship = $_POST['relationship1'];
    $last_name = $_POST['lastname1'];
    $first_name = $_POST['firstname1'];
    $middle_name = $_POST['middlename1'];
    $age = $_POST['age1'];
    $telephone_number = $_POST['telephone1'];
    $contact_number = $_POST['contactnumber1'];
    $email = $_POST['email1'];
    $complete_address = $_POST['completeaddress1'];

    // Prepare the SQL statement to insert data into contact_persons table
    $insert_query = "INSERT INTO contact_persons (user_id, relationship, last_name, first_name, middle_name, age, telephone_number, contact_number, email, complete_address) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("isssssssss", $user_id, $relationship, $last_name, $first_name, $middle_name, $age, $telephone_number, $contact_number, $email, $complete_address);

    // Execute the statement and check for success
    if ($insert_stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save contact.']);
    }

    // Close the statement
    $insert_stmt->close();
    exit; // Stop further execution
}
?>
