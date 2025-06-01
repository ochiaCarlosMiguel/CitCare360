<?php
session_start();
include '../connection/connection.php'; // Include your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID
    $gender = $_POST['modalGender'];
    $age = $_POST['modalAge'];
    $place_of_birth = $_POST['modalPlaceOfBirth'];
    $civil_status = $_POST['modalCivilStatus'];
    $nationality = $_POST['modalNationality'];
    $religion = $_POST['modalReligion'];
    $height = $_POST['modalHeight'];
    $weight = $_POST['modalWeight'];
    $blood_type = $_POST['modalBloodType'];
    $pwd_with_special_needs = isset($_POST['modalPWD']) ? 1 : 0; // Checkbox value

    // Check for empty fields
    if (empty($gender) || empty($age) || empty($place_of_birth) || empty($civil_status) || 
        empty($nationality) || empty($religion) || empty($height) || empty($weight) || 
        empty($blood_type)) {
        echo "All fields are required.";
        exit;
    }

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO user_additional_info (user_id, gender, age, place_of_birth, civil_status, nationality, religion, height, weight, blood_type, pwd_with_special_needs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssssi", $user_id, $gender, $age, $place_of_birth, $civil_status, $nationality, $religion, $height, $weight, $blood_type, $pwd_with_special_needs);

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
