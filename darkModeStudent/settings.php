<?php
// Start the session
session_start();

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if user_id is not set
    header("Location: ../studentPortal/login.php");
    exit;
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Assuming you have a database connection file
include '../connection/connection.php';

// Initialize user_profile with a default value
$user_profile = '../image/default.png'; // Default image path

// Fetch the user's first name from the database
$query = "SELECT u.first_name, u.last_name, u.middle_name, u.email, u.phone_number, u.department, u.student_number, 
                 a.gender, a.age, a.place_of_birth, a.civil_status, a.nationality, a.religion, a.height, a.weight, a.blood_type, a.pwd_with_special_needs, u.user_profile
          FROM users u 
          LEFT JOIN user_additional_info a ON u.id = a.user_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $middle_name = $user['middle_name'];
    $email = $user['email'];
    $phone_number = $user['phone_number'];
    $department = $user['department'];
    $student_number = $user['student_number'];
    $gender = $user['gender'];
    $age = $user['age'];
    $place_of_birth = $user['place_of_birth'];
    $civil_status = $user['civil_status'];
    $nationality = $user['nationality'];
    $religion = $user['religion'];
    $height = $user['height'];
    $weight = $user['weight'];
    $blood_type = $user['blood_type'];
    $pwd_with_special_needs = $user['pwd_with_special_needs']; // Change to text field
    $user_profile = '../image/' . $user['user_profile']; // Set user profile image
} else {
    echo "User not found.";
    $first_name = 'Guest'; // Default name
    // Handle the case where the user is not found in the database
    exit;
}

// Fetch the user's address from the database
$address_query = "SELECT house_number, province, municipality, barangay, zip_code FROM user_addresses WHERE user_id = ?";
$address_stmt = $conn->prepare($address_query);
$address_stmt->bind_param("i", $user_id);
$address_stmt->execute();
$address_result = $address_stmt->get_result();
$address = $address_result->fetch_assoc();

if ($address) {
    $house_number = $address['house_number'];
    $province = $address['province'];
    $municipality = $address['municipality'];
    $barangay = $address['barangay'];
    $zip_code = $address['zip_code'];
} else {
    // Default values if no address found
    $house_number = '';
    $province = '';
    $municipality = '';
    $barangay = '';
    $zip_code = '';
}

// Update the unread notifications query to properly count notifications for the current user
$unread_query = "SELECT (
    SELECT COUNT(*) 
    FROM incident_history ih 
    INNER JOIN incidents i ON ih.incident_id = i.id 
    WHERE ih.is_read = '0' AND i.user_id = ?
) + (
    SELECT COUNT(*) 
    FROM counseling_history ch 
    INNER JOIN counseling_appointments ca ON ch.counseling_id = ca.id 
    WHERE ch.is_read = '0' AND ca.user_id = ?
) as total_unread";

$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("ii", $user_id, $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['total_unread'];

// Check if the user has additional information
$check_query = "SELECT * FROM user_additional_info WHERE user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$has_additional_info = $check_result->num_rows > 0; // Check if any rows exist
$check_stmt->close();

// Check if the user has an address
$address_check_query = "SELECT * FROM user_addresses WHERE user_id = ?";
$address_check_stmt = $conn->prepare($address_check_query);
$address_check_stmt->bind_param("i", $user_id);
$address_check_stmt->execute();
$address_check_result = $address_check_stmt->get_result();
$has_address = $address_check_result->num_rows > 0; // Check if any rows exist
$address_check_stmt->close();

// Check if the user has a contact person
$contact_check_query = "SELECT * FROM contact_persons WHERE user_id = ?";
$contact_check_stmt = $conn->prepare($contact_check_query);
$contact_check_stmt->bind_param("i", $user_id);
$contact_check_stmt->execute();
$contact_check_result = $contact_check_stmt->get_result();
$has_contact1 = $contact_check_result->num_rows > 0; // Check if any rows exist
$contact_check_stmt->close();

// Fetch the user's contact person data from the database
$contact_query = "SELECT * FROM contact_persons WHERE user_id = ?";
$contact_stmt = $conn->prepare($contact_query);
$contact_stmt->bind_param("i", $user_id);
$contact_stmt->execute();
$contact_result = $contact_stmt->get_result();
$contact1 = $contact_result->fetch_assoc(); // Fetch the first contact person

// Check if contact1 is set and not null
if ($contact1) {
    $relationship1 = $contact1['relationship'] ?? ''; // Store the relationship value safely
    $contact1_last_name = $contact1['last_name'] ?? ''; // Use null coalescing operator to avoid warnings
    $contact1_first_name = $contact1['first_name'] ?? '';
    $contact1_middle_name = $contact1['middle_name'] ?? '';
    $contact1_age = $contact1['age'] ?? '';
    $contact1_telephone_number = $contact1['telephone_number'] ?? '';
    $contact1_contact_number = $contact1['contact_number'] ?? '';
    $contact1_email = $contact1['email'] ?? '';
    $contact1_complete_address = $contact1['complete_address'] ?? '';
} else {
    // Default values if no data found
    $relationship1 = '';
    $contact1_last_name = '';
    $contact1_first_name = '';
    $contact1_middle_name = '';
    $contact1_age = '';
    $contact1_telephone_number = '';
    $contact1_contact_number = '';
    $contact1_email = '';
    $contact1_complete_address = '';
}

// Check if the user has a Contact Person #2
$contact2_check_query = "SELECT * FROM contact_persons_2 WHERE user_id = ?";
$contact2_check_stmt = $conn->prepare($contact2_check_query);
$contact2_check_stmt->bind_param("i", $user_id);
$contact2_check_stmt->execute();
$contact2_check_result = $contact2_check_stmt->get_result();
$has_contact2 = $contact2_check_result->num_rows > 0; // Check if any rows exist
$contact2_check_stmt->close();

// Fetch the user's Contact Person #2 data from the database
$contact2_query = "SELECT * FROM contact_persons_2 WHERE user_id = ?";
$contact2_stmt = $conn->prepare($contact2_query);
$contact2_stmt->bind_param("i", $user_id);
$contact2_stmt->execute();
$contact2_result = $contact2_stmt->get_result();
$contact2 = $contact2_result->fetch_assoc(); // Fetch the second contact person

// Check if contact2 is set and not null
if ($contact2) {
    $relationship2 = $contact2['relationship'] ?? ''; // Store the relationship value safely
    $contact2_last_name = $contact2['last_name'] ?? ''; // Use the correct field for last name
    $contact2_first_name = $contact2['first_name'] ?? ''; // Use the correct field for first name
    $contact2_middle_name = $contact2['middle_name'] ?? ''; // Use the correct field for middle name
    $contact2_age = $contact2['age'] ?? ''; // Use the correct field for age
    $contact2_telephone_number = $contact2['telephone_number'] ?? ''; // Use the correct field for phone number
    $contact2_contact_number = $contact2['contact_number'] ?? ''; // Use the correct field for contact number
    $contact2_email = $contact2['email'] ?? ''; // Use the correct field for email
    $contact2_complete_address = $contact2['complete_address'] ?? ''; // Use the correct field for address
} else {
    // Default values if no data found
    $relationship2 = '';
    $contact2_last_name = '';
    $contact2_first_name = '';
    $contact2_middle_name = '';
    $contact2_age = '';
    $contact2_telephone_number = '';
    $contact2_contact_number = '';
    $contact2_email = '';
    $contact2_complete_address = '';
}

// Set the current page variable
$currentPage = 'home'; // Set this page as the current page

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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings</title>
  <link rel="icon" type="image/png" href="../favicon.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    * { 
      box-sizing: border-box; 
      scroll-behavior: smooth; 
    }
    body { 
      margin: 0; 
      font-family: 'Poppins', sans-serif; 
      background: url('../image/bg.png') no-repeat center center fixed;
      background-size: cover; 
      color: #2d3436; 
      line-height: 1.6; 
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Dark mode styles */
    body.dark-mode {
      background-color: #1a1a1a;
      color: #ffffff;
    }

    /* Theme switch styles */
    .theme-switch-wrapper {
      display: flex;
      align-items: center;
      margin-left: 0; /* Remove left margin */
    }

    .theme-switch {
      display: inline-block;
      height: 34px;
      position: relative;
      width: 60px;
    }

    .theme-switch input {
      display: none;
    }

    .slider {
      background-color: #ccc;
      bottom: 0;
      cursor: pointer;
      left: 0;
      position: absolute;
      right: 0;
      top: 0;
      transition: .4s;
      border-radius: 34px;
    }

    .slider:before {
      background-color: #fff;
      bottom: 4px;
      content: "";
      height: 26px;
      left: 4px;
      position: absolute;
      transition: .4s;
      width: 26px;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: #2196F3;
    }

    input:checked + .slider:before {
      transform: translateX(26px);
    }

    .slider:after {
      content: '🌙';
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 12px;
    }

    input:checked + .slider:after {
      content: '☀️';
      left: 8px;
      right: auto;
    }

    /* Add smooth transition for all elements */
    * {
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    .overlay {
      position: fixed; /* Cover the entire viewport */
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.9); /* Dark overlay */
      z-index: -1; /* Set z-index to -1 to place it behind other content */
    }
    a { 
      text-decoration: none; 
      color: inherit; 
      transition: color 0.3s; 
    }
    
     /* Navbar Styles */
     .navbar { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      background: #2c3e50; /* Darker navbar background */
      padding: 15px 50px; 
      position: sticky; 
      top: 0; 
      z-index: 1000; 
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3); /* Added shadow for depth */
    }
    .navbar .logo {
      font-size: 36px; /* Increased logo font size */
      font-weight: 700;
      color: #ecf0f1; /* Lighter logo color */
      text-shadow: 2px 2px #09243B;
    }
    .navbar .logo span.cit {
      color: #4F46E5;
    }
    .nav-menu { 
      display: flex; 
      gap: 30px; 
      list-style: none; 
      margin: 0 auto;
    }
    .nav-menu li a { 
      color: #ecf0f1; /* Lighter link color */
      padding: 10px 15px; /* Added padding for better click area */
      border-radius: 4px; 
      transition: background 0.3s, color 0.3s; /* Added color transition */
      font-size: 18px; /* Increased font size for nav links */
    }
    .nav-menu li a:hover { 
      background: #34495e; /* Darker hover background */
      color: #14141F; /* Change text color on hover */
    }
    .nav-menu li a.active {
      background: rgba(244, 162, 97, 0.6); /* Highlight color */
      border-radius: 4px;
    }

    @media (min-width: 1024px) {
      .navbar {
        padding: 15px 30px; /* Adjust padding for larger screens */
      }
      .nav-menu li a {
        font-size: 16px; /* Adjust font size for larger screens */
        padding: 8px 12px; /* Adjust padding for larger screens */
      }
      .login-btn {
        padding: 8px 20px; /* Adjust padding for larger screens */
      }
    }

    @media (min-width: 775px) and (max-width: 1023px) {
      .navbar {
        padding: 10px 20px; /* Adjust padding for medium screens */
      }
      .nav-menu li a {
        font-size: 16px; /* Adjust font size for medium screens */
        padding: 8px 10px; /* Adjust padding for medium screens */
      }
      .login-btn {
        padding: 8px 15px; /* Adjust padding for medium screens */
      }
    }


    .icon-button {
      background: #4F46E5;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      cursor: pointer;
      transition: background 0.3s, transform 0.3s; /* Added transform transition */
    }
    .icon-button:hover {
      background: #3B3A4A; /* Darker background on hover */
      transform: scale(1.1); /* Scale effect on hover */
    }

    @media (max-width: 768px) {
      .navbar {
        display: none; /* Hide the desktop navbar on mobile */
      }
      .mobile-navbar {
        display: flex; /* Show mobile navbar */
        justify-content: space-around; /* Space out buttons */
        background: #34495e; /* Updated background color for better contrast */
        padding: 10px 0; /* Add padding */
        position: fixed; /* Fix to the bottom */
        bottom: 0;
        width: 100%;
        z-index: 1000; /* Ensure it stays above other content */
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.3); /* Add shadow for depth */
        transition: transform 0.3s ease; /* Add transition for smooth animation */
      }
      .mobile-navbar .icon-button {
        background: none;
        color: #ecf0f1;
        font-size: 24px;
        padding: 10px;
        text-align: center;
        border: none;
        border-radius: 0;
      }
      .mobile-navbar .icon-button div {
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      .mobile-navbar .icon-button span {
        font-size: 12px;
        margin-top: 4px;
      }
      .mobile-navbar .icon-button.active {
        color: #f39c12;
      }
      .mobile-navbar .icon-button:hover {
        color: #f39c12;
      }
      .nav-menu { 
        flex-direction: column;
        gap: 10px;
      }
    }

    @media (min-width: 769px) {
      .mobile-navbar {
        display: none;
      }
    }

    .notification {
      margin-right: 0; /* Remove right margin */
    }

    .user-profile {
      position: relative;
      display: flex;
      align-items: center;
      margin-left: auto;
      gap: 20px; /* Add consistent gap between elements */
    }

    .profile-container {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #34495e;
      padding: 12px;
      border-radius: 25px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      border: 1px solid #2c3e50;
    }

    .profile-icon {
      background: none;
      border: none;
      color: #fff;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      top: 100%;
      right: 0;
      background-color: #222232;
      min-width: 160px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      z-index: 1;
      border-radius: 8px;
      overflow: hidden;
    }

    .dropdown-content a {
      color: #fff;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      transition: background 0.3s;
    }

    .dropdown-content a:hover {
      background-color: #3B3A4A;
    }

    .profile-icon img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #fff;
    }


    /* Header styles */
    .mobile-header {
      display: flex;
      align-items: center;
      justify-content: space-between; /* Space between logo and other elements */
      padding: 15px 20px; /* Increased padding for the header */
      background-color: #1c1c1c; /* Dark background color for dark mode */
      position: fixed; /* Fix to the top */
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000; /* Ensure it stays above other content */
      color: #e0e0e0; /* Light text color for better contrast */
    }

    .logo-container {
      display: flex;
      align-items: center; /* Center the logo and text vertically */
    }

    .mobile-header .logo {
      max-height: 60px; /* Increased maximum height for the logo */
      height: auto; /* Maintain aspect ratio */
      width: auto; /* Maintain aspect ratio */
      margin-right: 10px; /* Space between logo and text */
      color: #f4a261; /* Adjust logo color for better visibility in dark mode */
    }

    .text-logo {
      font-size: 24px; /* Font size for the text logo */
      color: #f4a261; /* Actual color for the text logo */
      font-weight: bold; /* Make the text bold */
      line-height: 1; /* Adjust line height for better alignment */
    }

    .mobile-header .logout-button {
      background: none; /* No background for button */
      border: none; /* No border */
      color: #e0e0e0; /* Ensure logout button has light color */
      font-size: 24px; /* Size for the logout icon */
      cursor: pointer; /* Pointer cursor for button */
    }

    .mobile-navbar {
      background: #1c1c1c; /* Dark background color for dark mode */
      color: #e0e0e0; /* Light text color for better contrast */
    }

    .mobile-navbar .icon-button {
      color: #e0e0e0; /* Ensure icon buttons have light color */
    }

    .mobile-navbar .icon-button:hover {
      background: #333; /* Darker hover background color */
    }

    /* Additional styles for dark mode */
    body {
      background-color: #121212; /* Dark background for the entire page */
      color: #e0e0e0; /* Light text color for the entire page */
    }

    /* Adjust other elements as needed for dark mode */
    .footer {
      background: #1c1c1c; /* Dark background color */
      color: #e0e0e0; /* Light text color */
      padding: 20px 10px; /* Padding for the footer */
      text-align: center; /* Center text */
      position: relative; /* Ensure it stays in flow with the content */
      bottom: 0; /* Stick to the bottom of the page */
      width: 100%; /* Full width */
    }

    .footer-content {
      display: flex; /* Use flexbox for layout */
      justify-content: space-between; /* Space out items */
      align-items: center; /* Center items vertically */
      flex-wrap: wrap; /* Allow wrapping on smaller screens */
      margin: 0 auto; /* Center the content */
      max-width: 1200px; /* Limit the width for larger screens */
    }

    .footer-content p {
      margin: 5px 0; /* Margin for paragraphs */
      font-size: 14px; /* Font size for footer text */
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .footer-content {
        flex-direction: column; /* Stack items vertically on mobile */
        align-items: center; /* Center items */
      }

      .footer-content p {
        font-size: 12px; /* Smaller font size on mobile */
      }
    }

    .profile-completion {
      position: fixed; /* Change to fixed positioning */
      left: 20px; /* Distance from the left */
      top: 80px; /* Adjusted distance from the top to avoid overlap with the navbar */
      z-index: 1000; /* Ensure it stays above other content */
    }

    .dropdown-button {
      background-color: #4F46E5; /* Button background color */
      color: white; /* Button text color */
      padding: 10px 15px; /* Button padding */
      border: none; /* No border */
      border-radius: 5px; /* Rounded corners */
      cursor: pointer; /* Pointer cursor on hover */
      transition: background 0.3s; /* Smooth background transition */
    }

    .dropdown-button:hover {
      background-color: #3B3A4A; /* Darker background on hover */
    }

    .dropdown-content {
      display: none; /* Hidden by default */
      position: absolute; /* Positioning relative to the button */
      background-color: #222232; /* Dropdown background color */
      min-width: 160px; /* Minimum width of the dropdown */
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); /* Shadow for depth */
      z-index: 1; /* Ensure it stays above other content */
      border-radius: 5px; /* Rounded corners */
      animation: fadeIn 0.3s; /* Animation for dropdown */
    }

    .dropdown-content a {
      color: #fff; /* Link text color */
      padding: 12px 16px; /* Link padding */
      text-decoration: none; /* No underline */
      display: block; /* Block display for links */
      transition: background 0.3s; /* Smooth background transition */
    }

    .dropdown-content a:hover {
      background-color: #3B3A4A; /* Background color on hover */
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .main-content {
      margin: 20px auto; /* Center the content */
      padding: 20px;
      max-width: 800px; /* Limit the width */
      background-color: #fff; /* White background for the content */
      border-radius: 8px; /* Rounded corners */
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    }

    .tabs {
      display: flex;
      justify-content: space-around;
      margin-bottom: 20px;
    }

    .tab-button {
      padding: 10px 20px;
      cursor: pointer;
      background-color: #4F46E5; /* Tab background color */
      color: white; /* Tab text color */
      border: none; /* No border */
      border-radius: 5px; /* Rounded corners */
      transition: background 0.3s, transform 0.3s; /* Smooth background transition and transform */
    }

    .tab-button:hover {
      background-color: #3B3A4A; /* Darker background on hover */
      transform: scale(1.05); /* Slightly scale up on hover */
    }

    .tab-button.active {
      background-color: #f4a261; /* Active tab background color */
      color: #14141F; /* Active tab text color */
      font-weight: bold; /* Bold text for active tab */
      border-bottom: 3px solid #2c3e50; /* Underline effect for active tab */
    }

    .tab-content {
      display: none; /* Hide all tab content by default */
    }

    .tab-content.active {
      display: block; /* Show active tab content */
    }

    .form-group {
      margin-bottom: 15px; /* Space between form groups */
    }

    .form-input {
      width: 100%; /* Full width for inputs */
      padding: 10px; /* Padding for inputs */
      border: 1px solid #ccc; /* Border for inputs */
      border-radius: 4px; /* Rounded corners */
      font-size: 16px; /* Font size for inputs */
    }

    /* Updated label color for better visibility */
    label {
      color: #f4a261; /* Change this to a more visible color */
      font-weight: bold; /* Make labels bold for emphasis */
    }

    .submit-button {
      background-color: #4F46E5; /* Button background color */
      color: white; /* Button text color */
      padding: 10px 15px; /* Button padding */
      border: none; /* No border */
      border-radius: 5px; /* Rounded corners */
      cursor: pointer; /* Pointer cursor on hover */
      transition: background 0.3s; /* Smooth background transition */
    }

    .submit-button:hover {
      background-color: #3B3A4A; /* Darker background on hover */
    }

    /* Modal styles */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1001; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
        border-radius: 5px; /* Rounded corners */
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .success-modal-content {
        background-color: #4CAF50; /* Green background for success */
        color: white; /* White text color */
        border-radius: 10px; /* Rounded corners */
        padding: 20px; /* Padding inside the modal */
        text-align: center; /* Center the text */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); /* Shadow for depth */
    }

    .success-modal-content h2 {
        margin: 0; /* Remove default margin */
        font-size: 24px; /* Increase font size for the heading */
    }

    .success-modal-content p {
        font-size: 16px; /* Font size for the message */
        margin-top: 10px; /* Space above the message */
    }

    /* Add this to your existing CSS */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%; /* Full width on mobile */
            height: auto; /* Allow height to adjust */
            position: relative; /* Change position for mobile */
        }

        .content-wrapper {
            margin-left: 0; /* Remove left margin on mobile */
            margin-top: 60px; /* Keep top margin for the top bar */
            padding: 10px; /* Reduce padding for mobile */
        }

        .settings-container {
            grid-template-columns: 1fr; /* Single column layout on mobile */
        }

        .settings-table {
            padding: 15px; /* Adjust padding for smaller screens */
        }

        .input-group {
            flex-direction: column; /* Stack inputs vertically */
        }

        .button-group {
            flex-direction: column; /* Stack buttons vertically */
            gap: 10px; /* Add space between buttons */
        }

        .btn-choose, .btn-change, .btn-update, .btn-change-password {
            width: 100%; /* Full width buttons */
        }

        .profile-container {
            width: 100%; /* Full width for profile container */
        }

        .profile-name {
            font-size: 16px; /* Adjust font size for mobile */
        }

        .table-header h2 {
            font-size: 1.5em; /* Increase header size for better visibility */
        }
    }

    /* Add this to your existing CSS */
    @media (max-width: 768px) {
        .tabs {
            flex-direction: row; /* Keep tabs in a row for mobile */
            justify-content: space-around; /* Space out the buttons */
            margin-top: 60px; /* Add margin to avoid overlap with the mobile header */
        }

        .tab-button {
            width: auto; /* Auto width for icon buttons */
            padding: 10px; /* Padding for better touch targets */
            background: none; /* No background */
            border: none; /* No border */
            color: #4F46E5; /* Icon color */
            font-size: 24px; /* Increase icon size */
            cursor: pointer; /* Pointer cursor on hover */
            transition: color 0.3s, background 0.3s; /* Smooth color and background transition */
            border-radius: 5px; /* Rounded corners for better aesthetics */
        }

        .tab-button:hover {
            color: #3B3A4A; /* Darker color on hover */
            background-color: rgba(244, 162, 97, 0.2); /* Light background on hover */
        }

        .tab-button.active {
            color: #f4a261; /* Active icon color */
            background-color: rgba(244, 162, 97, 0.3); /* Light background for active state */
            border-radius: 5px; /* Rounded corners for active state */
        }

        .tab-text {
            display: none; /* Hide text on mobile */
        }
    }

    @media (min-width: 769px) {
        .tab-text {
            display: inline; /* Show text on desktop */
        }

        .tab-button {
            padding: 10px 15px; /* Padding for text buttons */
            background: none; /* No background */
            border: none; /* No border */
            color: #4F46E5; /* Text color */
            font-size: 16px; /* Font size for text */
            cursor: pointer; /* Pointer cursor on hover */
            transition: color 0.3s, background 0.3s; /* Smooth color and background transition */
            border-radius: 5px; /* Rounded corners for better aesthetics */
        }

        .tab-button:hover {
            color: #3B3A4A; /* Darker color on hover */
            background-color: rgba(244, 162, 97, 0.2); /* Light background on hover */
        }

        .tab-button.active {
            color: #f4a261; /* Active text color */
            background-color: rgba(244, 162, 97, 0.3); /* Light background for active state */
            border-radius: 5px; /* Rounded corners for active state */
        }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo" style="display: flex; align-items: center;">
      <img src="<?php echo htmlspecialchars('../image/logo.png'); ?>" alt="Logo" style="height: 50px; margin-right: 10px;">
      <span class="cit" style="font-size: 28px; margin-right: 5px;">CIT</span>
      <span style="font-size: 24px;">CARE 360</span>
    </div>
    <ul class="nav-menu">
      <li><a href="homePage.php">Home</a></li>
      <li><a href="reportIncident.php">Report</a></li>
      <li><a href="reportStatus.php">Report Status</a></li>
    </ul>
    <div class="user-profile">
      <div class="notification" style="position: relative;">
        <button class="icon-button" onclick="redirectToNotifications()" style="position: relative;">
          <i class="fa-solid fa-bell"></i>
          <span class="notification-badge" style="position: absolute; top: -5px; right: -10px; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px;">
            <?php echo $unread_count > 0 ? htmlspecialchars($unread_count) : '0'; ?>
          </span>
        </button>
      </div>
      <div class="theme-switch-wrapper">
        <label class="theme-switch">
          <input type="checkbox" id="theme-switch" checked>
          <div class="slider"></div>
        </label>
      </div>
      <div class="profile-container">
        <button class="icon-button profile-icon">
          <img src="<?php echo htmlspecialchars($user_profile); ?>" alt="User Profile">
        </button>
        <span style="color: #fff; margin-left: 10px;"><?php echo htmlspecialchars($first_name); ?></span>
        <button class="icon-button profile-icon" onclick="toggleDropdown()">
          <i class="fa-solid fa-caret-down"></i>
        </button>
      </div>
      <div class="dropdown-content" id="profileDropdown">
        <a href="profile.php">Profile</a>
        <a href="settings.php">Settings</a>
        <a href="../studentPortal/logout.php">Sign Out</a>
      </div>
    </div>
  </nav>

  <!-- Complete Your Profile Dropdown Button -->
  <div class="profile-completion">
    <button class="dropdown-button" onclick="toggleProfileDropdown()">
        Complete Your Profile
    </button>
    <div class="dropdown-content" id="profileCompletionDropdown">
        <a href="#" id="additionalInfoLink" onclick="openModal('additionalInfoModal')">
            Additional Information <span id="additionalInfoCheckMark"><?php if ($has_additional_info) echo '&#10003;'; ?></span>
        </a>
        <a href="#" id="addressLink" onclick="openModal('addressModal')">
            Address <span id="addressCheckMark"><?php if ($has_address) echo '&#10003;'; ?></span>
        </a>
        <a href="#" id="contact1Link" onclick="openModal('contact1Modal')">
            Contact #1 <span id="contact1CheckMark"><?php if ($has_contact1) echo '&#10003;'; ?></span>
        </a>
        <a href="#" id="contact2Link" onclick="openModal('contact2Modal')">
            Contact #2 <span id="contact2CheckMark"><?php if ($has_contact2) echo '&#10003;'; ?></span></span>
        </a>
    </div>
  </div>

  <!-- Address Modal Structure -->
  <div id="addressModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addressModal')">&times;</span>
        <h2 style="color: #f4a261;">Permanent Address</h2>
        <form id="addressForm">
            <div class="form-group">
                <label for="houseNumber">House Number:</label>
                <input type="text" id="houseNumber" name="houseNumber" class="form-input" placeholder="Enter House Number" required value="<?php echo htmlspecialchars($house_number); ?>"><br>
            </div>
            <div class="form-group">
                <label for="province">Province:</label>
                <input type="text" id="province" name="province" class="form-input" placeholder="Enter Province" required value="<?php echo htmlspecialchars($province); ?>"><br>
            </div>
            <div class="form-group">
                <label for="municipality">Municipality:</label>
                <input type="text" id="municipality" name="municipality" class="form-input" placeholder="Enter Municipality" required value="<?php echo htmlspecialchars($municipality); ?>"><br>
            </div>
            <div class="form-group">
                <label for="barangay">Barangay:</label>
                <input type="text" id="barangay" name="barangay" class="form-input" placeholder="Enter Barangay" required value="<?php echo htmlspecialchars($barangay); ?>"><br>
            </div>
            <div class="form-group">
                <label for="zipcode">Zip Code:</label>
                <input type="text" id="zipcode" name="zipcode" class="form-input" placeholder="Enter Zip Code" required value="<?php echo htmlspecialchars($zip_code); ?>"><br>
            </div>
            <input type="submit" value="Save Address" class="submit-button">
        </form>
    </div>
  </div>

  <!-- Additional Information Modal Structure -->
  <div id="additionalInfoModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('additionalInfoModal')">&times;</span>
        <h2 style="color: #f4a261;">Additional Information</h2>
        <form id="additionalInfoForm" method="POST" action="save_additional_info.php">
            <div class="form-group">
                <label for="modalGender">Gender:</label>
                <select id="modalGender" name="modalGender" class="form-input">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="modalAge">Age:</label>
                <input type="number" id="modalAge" name="modalAge" class="form-input" placeholder="Enter Age" required value="<?php echo htmlspecialchars($age); ?>"><br>
            </div>
            <div class="form-group">
                <label for="modalPlaceOfBirth">Place of Birth:</label>
                <input type="text" id="modalPlaceOfBirth" name="modalPlaceOfBirth" class="form-input" placeholder="Enter Place of Birth" required value="<?php echo htmlspecialchars($place_of_birth); ?>"><br>
            </div>
            <div class="form-group">
                <label for="modalCivilStatus">Civil Status:</label>
                <select id="modalCivilStatus" name="modalCivilStatus" class="form-input">
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Separated">Separated</option>
                    <option value="Divorced">Divorced</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Civil Partnership">In a Civil Partnership</option>
                </select>
            </div>
            <div class="form-group">
                <label for="modalNationality">Nationality:</label>
                <input type="text" id="modalNationality" name="modalNationality" class="form-input" placeholder="Enter Nationality" required value="<?php echo htmlspecialchars($nationality); ?>"><br>
            </div>
            <div class="form-group">
                <label for="modalReligion">Religion:</label>
                <input type="text" id="modalReligion" name="modalReligion" class="form-input" placeholder="Enter Religion" required value="<?php echo htmlspecialchars($religion); ?>"><br>
            </div>
            <div class="form-group">
                <label for="modalHeight">Height:</label>
                <input type="text" id="modalHeight" name="modalHeight" class="form-input" placeholder="Enter Height" required value="<?php echo htmlspecialchars($height); ?>"><br>
            </div>
            <div class="form-group">
                <label for="modalWeight">Weight:</label>
                <input type="text" id="modalWeight" name="modalWeight" class="form-input" placeholder="Enter Weight" required value="<?php echo htmlspecialchars($weight); ?>"><br>
            </div>
            <div class="form-group">
                <label for="modalBloodType">Blood Type:</label>
                <select id="modalBloodType" name="modalBloodType" class="form-input">
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="Unknown">Unknown</option>
                </select>
            </div>
            <div class="form-group">
                <label for="modalPWD">PWD with Special Needs:</label>
                <input type="checkbox" id="modalPWD" name="modalPWD" <?php echo htmlspecialchars($pwd_with_special_needs) === '1' ? 'checked' : ''; ?>><br>
            </div>
            <div id="validationMessage" style="color: red;"></div> <!-- Validation message area -->
            <input type="submit" value="Save" class="submit-button">
        </form>
    </div>
  </div>

  <!-- Success Modal Structure -->
  <div id="successModal" class="modal">
    <div class="modal-content success-modal-content">
        <span class="close" onclick="closeModal('successModal')">&times;</span>
        <h2>Success!</h2>
        <p>Your contact information has been saved successfully.</p>
    </div>
  </div>

  <!-- Already Filled Modal Structure -->
  <div id="alreadyFilledModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('alreadyFilledModal')">&times;</span>
        <h2>Information Already Submitted</h2>
        <p>You have already filled out the Additional Information form.</p>
    </div>
  </div>

  <!-- Contact #1 Modal Structure -->
  <div id="contact1Modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('contact1Modal')">&times;</span>
        <h2 style="color: #f4a261;">Contact Person #1</h2> <!-- Change the color here -->
        <form id="contact1Form" method="POST" action="save_contact1.php">
            <div class="form-group">
                <label for="relationship1">Relationship:</label>
                <select name="relationship1" id="relationship1" class="form-input" required>
                    <option value="" disabled>Select Relationship</option>
                    <option value="Parent (Mother/Father)" <?php echo ($relationship1 === 'Parent (Mother/Father)') ? 'selected' : ''; ?>>Parent (Mother/Father)</option>
                    <option value="Guardian" <?php echo ($relationship1 === 'Guardian') ? 'selected' : ''; ?>>Guardian</option>
                    <option value="Sibling (Brother/Sister)" <?php echo ($relationship1 === 'Sibling (Brother/Sister)') ? 'selected' : ''; ?>>Sibling (Brother/Sister)</option>
                    <option value="Grandparent (Grandmother/Grandfather)" <?php echo ($relationship1 === 'Grandparent (Grandmother/Grandfather)') ? 'selected' : ''; ?>>Grandparent (Grandmother/Grandfather)</option>
                    <option value="Aunt/Uncle" <?php echo ($relationship1 === 'Aunt/Uncle') ? 'selected' : ''; ?>>Aunt/Uncle</option>
                    <option value="Cousin" <?php echo ($relationship1 === 'Cousin') ? 'selected' : ''; ?>>Cousin</option>
                    <option value="Step-Parent (Step-Mother/Step-Father)" <?php echo ($relationship1 === 'Step-Parent (Step-Mother/Step-Father)') ? 'selected' : ''; ?>>Step-Parent (Step-Mother/Step-Father)</option>
                    <option value="Step-Sibling (Step-Brother/Step-Sister)" <?php echo ($relationship1 === 'Step-Sibling (Step-Brother/Step-Sister)') ? 'selected' : ''; ?>>Step-Sibling (Step-Brother/Step-Sister)</option>
                    <option value="Family Friend" <?php echo ($relationship1 === 'Family Friend') ? 'selected' : ''; ?>>Family Friend</option>
                    <option value="Godparent" <?php echo ($relationship1 === 'Godparent') ? 'selected' : ''; ?>>Godparent</option>
                    <option value="Neighbor" <?php echo ($relationship1 === 'Neighbor') ? 'selected' : ''; ?>>Neighbor</option>
                    <option value="Teacher/Professor (with permission)" <?php echo ($relationship1 === 'Teacher/Professor (with permission)') ? 'selected' : ''; ?>>Teacher/Professor (with permission)</option>
                    <option value="Classmate/Close Friend (if authorized)" <?php echo ($relationship1 === 'Classmate/Close Friend (if authorized)') ? 'selected' : ''; ?>>Classmate/Close Friend (if authorized)</option>
                    <option value="Employer/Supervisor (if working student)" <?php echo ($relationship1 === 'Employer/Supervisor (if working student)') ? 'selected' : ''; ?>>Employer/Supervisor (if working student)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="lastname1">Last Name:</label>
                <input type="text" id="lastname1" name="lastname1" class="form-input" placeholder="Enter Last Name" required value="<?php echo htmlspecialchars($contact1['last_name'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="firstname1">First Name:</label>
                <input type="text" id="firstname1" name="firstname1" class="form-input" placeholder="Enter First Name" required value="<?php echo htmlspecialchars($contact1['first_name'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="middlename1">Middle Name:</label>
                <input type="text" id="middlename1" name="middlename1" class="form-input" placeholder="Enter Middle Name" value="<?php echo htmlspecialchars($contact1['middle_name'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="age1">Age:</label>
                <input type="number" id="age1" name="age1" class="form-input" placeholder="Enter Age" required value="<?php echo htmlspecialchars($contact1['age'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="telephone1">Telephone Number:</label>
                <input type="text" id="telephone1" name="telephone1" class="form-input" placeholder="Enter Telephone Number" required value="<?php echo htmlspecialchars($contact1['telephone_number'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="contactnumber1">Contact Number:</label>
                <input type="text" id="contactnumber1" name="contactnumber1" class="form-input" placeholder="Enter Contact Number" required value="<?php echo htmlspecialchars($contact1['contact_number'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="email1">Email:</label>
                <input type="email" id="email1" name="email1" class="form-input" placeholder="Enter Email" required value="<?php echo htmlspecialchars($contact1['email'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="completeaddress1">Complete Address:</label>
                <input type="text" id="completeaddress1" name="completeaddress1" class="form-input" placeholder="Enter Complete Address" required value="<?php echo htmlspecialchars($contact1['complete_address'] ?? ''); ?>"><br>
            </div>
            <input type="submit" value="Save Contact #1" class="submit-button">
        </form>
    </div>
  </div>

  <!-- Contact #2 Modal Structure -->
  <div id="contact2Modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('contact2Modal')">&times;</span>
        <h2 style="color: #f4a261;">Contact Person #2</h2> <!-- Change the color here -->
        <form id="contact2Form" method="POST" action="save_contact2.php">
            <div class="form-group">
                <label for="relationship2">Relationship:</label>
                <select name="relationship2" id="relationship2" class="form-input" required>
                    <option value="" disabled selected>Select Relationship</option>
                    <option value="Parent (Mother/Father)" <?php echo ($relationship2 === 'Parent (Mother/Father)') ? 'selected' : ''; ?>>Parent (Mother/Father)</option>
                    <option value="Guardian" <?php echo ($relationship2 === 'Guardian') ? 'selected' : ''; ?>>Guardian</option>
                    <option value="Sibling (Brother/Sister)" <?php echo ($relationship2 === 'Sibling (Brother/Sister)') ? 'selected' : ''; ?>>Sibling (Brother/Sister)</option>
                    <option value="Grandparent (Grandmother/Grandfather)" <?php echo ($relationship2 === 'Grandparent (Grandmother/Grandfather)') ? 'selected' : ''; ?>>Grandparent (Grandmother/Grandfather)</option>
                    <option value="Aunt/Uncle" <?php echo ($relationship2 === 'Aunt/Uncle') ? 'selected' : ''; ?>>Aunt/Uncle</option>
                    <option value="Cousin" <?php echo ($relationship2 === 'Cousin') ? 'selected' : ''; ?>>Cousin</option>
                    <option value="Step-Parent (Step-Mother/Step-Father)" <?php echo ($relationship2 === 'Step-Parent (Step-Mother/Step-Father)') ? 'selected' : ''; ?>>Step-Parent (Step-Mother/Step-Father)</option>
                    <option value="Step-Sibling (Step-Brother/Step-Sister)" <?php echo ($relationship2 === 'Step-Sibling (Step-Brother/Step-Sister)') ? 'selected' : ''; ?>>Step-Sibling (Step-Brother/Step-Sister)</option>
                    <option value="Family Friend" <?php echo ($relationship2 === 'Family Friend') ? 'selected' : ''; ?>>Family Friend</option>
                    <option value="Godparent" <?php echo ($relationship2 === 'Godparent') ? 'selected' : ''; ?>>Godparent</option>
                    <option value="Neighbor" <?php echo ($relationship2 === 'Neighbor') ? 'selected' : ''; ?>>Neighbor</option>
                    <option value="Teacher/Professor (with permission)" <?php echo ($relationship2 === 'Teacher/Professor (with permission)') ? 'selected' : ''; ?>>Teacher/Professor (with permission)</option>
                    <option value="Classmate/Close Friend (if authorized)" <?php echo ($relationship2 === 'Classmate/Close Friend (if authorized)') ? 'selected' : ''; ?>>Classmate/Close Friend (if authorized)</option>
                    <option value="Employer/Supervisor (if working student)" <?php echo ($relationship2 === 'Employer/Supervisor (if working student)') ? 'selected' : ''; ?>>Employer/Supervisor (if working student)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="lastname2">Last Name:</label>
                <input type="text" id="lastname2" name="lastname2" class="form-input" placeholder="Enter Last Name" required value="<?php echo htmlspecialchars($contact2_last_name); ?>"><br>
            </div>
            <div class="form-group">
                <label for="firstname2">First Name:</label>
                <input type="text" id="firstname2" name="firstname2" class="form-input" placeholder="Enter First Name" required value="<?php echo htmlspecialchars($contact2_first_name); ?>"><br>
            </div>
            <div class="form-group">
                <label for="middlename2">Middle Name:</label>
                <input type="text" id="middlename2" name="middlename2" class="form-input" placeholder="Enter Middle Name" value="<?php echo htmlspecialchars($contact2_middle_name); ?>"><br>
            </div>
            <div class="form-group">
                <label for="age2">Age:</label>
                <input type="number" id="age2" name="age2" class="form-input" placeholder="Enter Age" required value="<?php echo htmlspecialchars($contact2_age); ?>"><br>
            </div>
            <div class="form-group">
                <label for="telephone2">Telephone Number:</label>
                <input type="text" id="telephone2" name="telephone2" class="form-input" placeholder="Enter Telephone Number" required value="<?php echo htmlspecialchars($contact2_telephone_number); ?>"><br>
            </div>
            <div class="form-group">
                <label for="contactnumber2">Contact Number:</label>
                <input type="text" id="contactnumber2" name="contactnumber2" class="form-input" placeholder="Enter Contact Number" required value="<?php echo htmlspecialchars($contact2_contact_number); ?>"><br>
            </div>
            <div class="form-group">
                <label for="email2">Email:</label>
                <input type="email" id="email2" name="email2" class="form-input" placeholder="Enter Email" required value="<?php echo htmlspecialchars($contact2_email); ?>"><br>
            </div>
            <div class="form-group">
                <label for="completeaddress2">Complete Address:</label>
                <input type="text" id="completeaddress2" name="completeaddress2" class="form-input" placeholder="Enter Complete Address" required value="<?php echo htmlspecialchars($contact2_complete_address); ?>"><br>
            </div>
            <input type="submit" value="Save" class="submit-button">
        </form>
    </div>
  </div>

  <!-- Contact Person #2 Success Modal Structure -->
  <div id="contact2SuccessModal" class="modal">
    <div class="modal-content success-modal-content">
        <span class="close" onclick="closeModal('contact2SuccessModal')">&times;</span>
        <h2>Success!</h2>
        <p>Your Contact Person #2 information has been saved successfully.</p>
    </div>
  </div>

  <div class="main-content">
    <div class="tabs">
      <button class="tab-button active" onclick="openTab(event, 'Profile')">
          <i class="fa-solid fa-user"></i> <!-- Profile Icon -->
          <span class="tab-text">Profile</span> <!-- Profile Text -->
      </button>
      <button class="tab-button" onclick="openTab(event, 'Address')">
          <i class="fa-solid fa-map-marker-alt"></i> <!-- Address Icon -->
          <span class="tab-text">Address</span> <!-- Address Text -->
      </button>
      <button class="tab-button" onclick="openTab(event, 'Contact1')">
          <i class="fa-solid fa-address-book"></i> <!-- Contact Person 1 Icon -->
          <span class="tab-text">Contact Person 1</span> <!-- Contact Person 1 Text -->
      </button>
      <button class="tab-button" onclick="openTab(event, 'Contact2')">
          <i class="fa-solid fa-address-book"></i> <!-- Contact Person 2 Icon -->
          <span class="tab-text">Contact Person 2</span> <!-- Contact Person 2 Text -->
      </button>
    </div>

    <div id="Profile" class="tab-content active">
      <form>
        <div class="form-group">
          <label for="lastname">Last Name:</label>
          <input type="text" id="lastname" name="lastname" class="form-input" value="<?php echo htmlspecialchars($last_name); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="firstname">First Name:</label>
          <input type="text" id="firstname" name="firstname" class="form-input" value="<?php echo htmlspecialchars($first_name); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="middlename">Middle Name:</label>
          <input type="text" id="middlename" name="middlename" class="form-input" value="<?php echo htmlspecialchars($middle_name); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="phone">Phone Number:</label>
          <input type="text" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($phone_number); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="department">Department:</label>
          <input type="text" id="department" name="department" class="form-input" value="<?php echo htmlspecialchars($department); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="studentnumber">Student Number:</label>
          <input type="text" id="studentnumber" name="studentnumber" class="form-input" value="<?php echo htmlspecialchars($student_number); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="gender">Gender:</label>
          <input type="text" id="gender" name="gender" class="form-input" value="<?php echo htmlspecialchars($gender); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="age">Age:</label>
          <input type="number" id="age" name="age" class="form-input" value="<?php echo htmlspecialchars($age); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="placeofbirth">Place of Birth:</label>
          <input type="text" id="placeofbirth" name="placeofbirth" class="form-input" value="<?php echo htmlspecialchars($place_of_birth); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="civilstatus">Civil Status:</label>
          <input type="text" id="civilstatus" name="civilstatus" class="form-input" value="<?php echo htmlspecialchars($civil_status); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="nationality">Nationality:</label>
          <input type="text" id="nationality" name="nationality" class="form-input" value="<?php echo htmlspecialchars($nationality); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="religion">Religion:</label>
          <input type="text" id="religion" name="religion" class="form-input" value="<?php echo htmlspecialchars($religion); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="height">Height:</label>
          <input type="text" id="height" name="height" class="form-input" value="<?php echo htmlspecialchars($height); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="weight">Weight:</label>
          <input type="text" id="weight" name="weight" class="form-input" value="<?php echo htmlspecialchars($weight); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="bloodtype">Blood Type:</label>
          <input type="text" id="bloodtype" name="bloodtype" class="form-input" value="<?php echo htmlspecialchars($blood_type); ?>" readonly><br>
        </div>

        <div class="form-group">
          <label for="pwd">PWD with Special Needs:</label>
          <input type="text" id="pwd" name="pwd" class="form-input" value="<?php echo htmlspecialchars($pwd_with_special_needs); ?>" readonly><br>
        </div>

      </form>
    </div>

    <div id="Address" class="tab-content">
      <form>
        <div class="form-group">
          <label for="housenumber">House Number:</label>
          <input type="text" id="housenumber" name="housenumber" class="form-input" value="<?php echo htmlspecialchars($house_number); ?>" placeholder="Enter House Number" readonly><br>
        </div>

        <div class="form-group">
          <label for="province">Province:</label>
          <input type="text" id="province" name="province" class="form-input" value="<?php echo htmlspecialchars($province); ?>" placeholder="Enter Province" readonly><br>
        </div>

        <div class="form-group">
          <label for="municipality">Municipality:</label>
          <input type="text" id="municipality" name="municipality" class="form-input" value="<?php echo htmlspecialchars($municipality); ?>" placeholder="Enter Municipality" readonly><br>
        </div>

        <div class="form-group">
          <label for="barangay">Barangay:</label>
          <input type="text" id="barangay" name="barangay" class="form-input" value="<?php echo htmlspecialchars($barangay); ?>" placeholder="Enter Barangay" readonly><br>
        </div>

        <div class="form-group">
          <label for="zipcode">Zip Code:</label>
          <input type="text" id="zipcode" name="zipcode" class="form-input" value="<?php echo htmlspecialchars($zip_code); ?>" placeholder="Enter Zip Code" readonly><br>
        </div>

      </form>
    </div>

    <div id="Contact1" class="tab-content">
      <form>
        <div class="form-group">
          <label for="relationship1">Relationship:</label>
          <select name="relationship1" id="relationship1" class="form-input" required>
            <option value="" disabled>Select Relationship</option>
            <option value="Parent (Mother/Father)" <?php echo ($relationship1 === 'Parent (Mother/Father)') ? 'selected' : ''; ?>>Parent (Mother/Father)</option>
            <option value="Guardian" <?php echo ($relationship1 === 'Guardian') ? 'selected' : ''; ?>>Guardian</option>
            <option value="Sibling (Brother/Sister)" <?php echo ($relationship1 === 'Sibling (Brother/Sister)') ? 'selected' : ''; ?>>Sibling (Brother/Sister)</option>
            <option value="Grandparent (Grandmother/Grandfather)" <?php echo ($relationship1 === 'Grandparent (Grandmother/Grandfather)') ? 'selected' : ''; ?>>Grandparent (Grandmother/Grandfather)</option>
            <option value="Aunt/Uncle" <?php echo ($relationship1 === 'Aunt/Uncle') ? 'selected' : ''; ?>>Aunt/Uncle</option>
            <option value="Cousin" <?php echo ($relationship1 === 'Cousin') ? 'selected' : ''; ?>>Cousin</option>
            <option value="Step-Parent (Step-Mother/Step-Father)" <?php echo ($relationship1 === 'Step-Parent (Step-Mother/Step-Father)') ? 'selected' : ''; ?>>Step-Parent (Step-Mother/Step-Father)</option>
            <option value="Step-Sibling (Step-Brother/Step-Sister)" <?php echo ($relationship1 === 'Step-Sibling (Step-Brother/Step-Sister)') ? 'selected' : ''; ?>>Step-Sibling (Step-Brother/Step-Sister)</option>
            <option value="Family Friend" <?php echo ($relationship1 === 'Family Friend') ? 'selected' : ''; ?>>Family Friend</option>
            <option value="Godparent" <?php echo ($relationship1 === 'Godparent') ? 'selected' : ''; ?>>Godparent</option>
            <option value="Neighbor" <?php echo ($relationship1 === 'Neighbor') ? 'selected' : ''; ?>>Neighbor</option>
            <option value="Teacher/Professor (with permission)" <?php echo ($relationship1 === 'Teacher/Professor (with permission)') ? 'selected' : ''; ?>>Teacher/Professor (with permission)</option>
            <option value="Classmate/Close Friend (if authorized)" <?php echo ($relationship1 === 'Classmate/Close Friend (if authorized)') ? 'selected' : ''; ?>>Classmate/Close Friend (if authorized)</option>
            <option value="Employer/Supervisor (if working student)" <?php echo ($relationship1 === 'Employer/Supervisor (if working student)') ? 'selected' : ''; ?>>Employer/Supervisor (if working student)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="lastname1">Last Name:</label>
          <input type="text" id="lastname1" name="lastname1" class="form-input" placeholder="Enter Last Name" required value="<?php echo htmlspecialchars($contact1['last_name'] ?? ''); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="firstname1">First Name:</label>
          <input type="text" id="firstname1" name="firstname1" class="form-input" placeholder="Enter First Name" required value="<?php echo htmlspecialchars($contact1['first_name'] ?? ''); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="middlename1">Middle Name:</label>
          <input type="text" id="middlename1" name="middlename1" class="form-input" placeholder="Enter Middle Name" value="<?php echo htmlspecialchars($contact1['middle_name'] ?? ''); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="age1">Age:</label>
          <input type="number" id="age1" name="age1" class="form-input" placeholder="Enter Age" required value="<?php echo htmlspecialchars($contact1['age'] ?? ''); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="telephone1">Telephone Number:</label>
          <input type="text" id="telephone1" name="telephone1" class="form-input" placeholder="Enter Telephone Number" required value="<?php echo htmlspecialchars($contact1['telephone_number'] ?? ''); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="contactnumber1">Contact Number:</label>
          <input type="text" id="contactnumber1" name="contactnumber1" class="form-input" placeholder="Enter Contact Number" required value="<?php echo htmlspecialchars($contact1['contact_number'] ?? ''); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="email1">Email:</label>
          <input type="email" id="email1" name="email1" class="form-input" placeholder="Enter Email" required value="<?php echo htmlspecialchars($contact1['email'] ?? ''); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="completeaddress1">Complete Address:</label>
          <input type="text" id="completeaddress1" name="completeaddress1" class="form-input" placeholder="Enter Complete Address" required value="<?php echo htmlspecialchars($contact1['complete_address'] ?? ''); ?>" readonly><br>
        </div>
      </form>
    </div>

    <div id="Contact2" class="tab-content">
      <form>
        <div class="form-group">
          <label for="relationship2">Relationship:</label>
          <select name="relationship2" id="relationship2" class="form-input" required>
            <option value="" disabled selected>Select Relationship</option>
            <option value="Parent (Mother/Father)" <?php echo ($relationship2 === 'Parent (Mother/Father)') ? 'selected' : ''; ?>>Parent (Mother/Father)</option>
            <option value="Guardian" <?php echo ($relationship2 === 'Guardian') ? 'selected' : ''; ?>>Guardian</option>
            <option value="Sibling (Brother/Sister)" <?php echo ($relationship2 === 'Sibling (Brother/Sister)') ? 'selected' : ''; ?>>Sibling (Brother/Sister)</option>
            <option value="Grandparent (Grandmother/Grandfather)" <?php echo ($relationship2 === 'Grandparent (Grandmother/Grandfather)') ? 'selected' : ''; ?>>Grandparent (Grandmother/Grandfather)</option>
            <option value="Aunt/Uncle" <?php echo ($relationship2 === 'Aunt/Uncle') ? 'selected' : ''; ?>>Aunt/Uncle</option>
            <option value="Cousin" <?php echo ($relationship2 === 'Cousin') ? 'selected' : ''; ?>>Cousin</option>
            <option value="Step-Parent (Step-Mother/Step-Father)" <?php echo ($relationship2 === 'Step-Parent (Step-Mother/Step-Father)') ? 'selected' : ''; ?>>Step-Parent (Step-Mother/Step-Father)</option>
            <option value="Step-Sibling (Step-Brother/Step-Sister)" <?php echo ($relationship2 === 'Step-Sibling (Step-Brother/Step-Sister)') ? 'selected' : ''; ?>>Step-Sibling (Step-Brother/Step-Sister)</option>
            <option value="Family Friend" <?php echo ($relationship2 === 'Family Friend') ? 'selected' : ''; ?>>Family Friend</option>
            <option value="Godparent" <?php echo ($relationship2 === 'Godparent') ? 'selected' : ''; ?>>Godparent</option>
            <option value="Neighbor" <?php echo ($relationship2 === 'Neighbor') ? 'selected' : ''; ?>>Neighbor</option>
            <option value="Teacher/Professor (with permission)" <?php echo ($relationship2 === 'Teacher/Professor (with permission)') ? 'selected' : ''; ?>>Teacher/Professor (with permission)</option>
            <option value="Classmate/Close Friend (if authorized)" <?php echo ($relationship2 === 'Classmate/Close Friend (if authorized)') ? 'selected' : ''; ?>>Classmate/Close Friend (if authorized)</option>
            <option value="Employer/Supervisor (if working student)" <?php echo ($relationship2 === 'Employer/Supervisor (if working student)') ? 'selected' : ''; ?>>Employer/Supervisor (if working student)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="lastname2">Last Name:</label>
          <input type="text" id="lastname2" name="lastname2" class="form-input" placeholder="Enter Last Name" required value="<?php echo htmlspecialchars($contact2_last_name); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="firstname2">First Name:</label>
          <input type="text" id="firstname2" name="firstname2" class="form-input" placeholder="Enter First Name" required value="<?php echo htmlspecialchars($contact2_first_name); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="middlename2">Middle Name:</label>
          <input type="text" id="middlename2" name="middlename2" class="form-input" placeholder="Enter Middle Name" value="<?php echo htmlspecialchars($contact2_middle_name); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="age2">Age:</label>
          <input type="number" id="age2" name="age2" class="form-input" placeholder="Enter Age" required value="<?php echo htmlspecialchars($contact2_age); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="telephone2">Telephone Number:</label>
          <input type="text" id="telephone2" name="telephone2" class="form-input" placeholder="Enter Telephone Number" required value="<?php echo htmlspecialchars($contact2_telephone_number); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="contactnumber2">Contact Number:</label>
          <input type="text" id="contactnumber2" name="contactnumber2" class="form-input" placeholder="Enter Contact Number" required value="<?php echo htmlspecialchars($contact2_contact_number); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="email2">Email:</label>
          <input type="email" id="email2" name="email2" class="form-input" placeholder="Enter Email" required value="<?php echo htmlspecialchars($contact2_email); ?>" readonly><br>
        </div>
        <div class="form-group">
          <label for="completeaddress2">Complete Address:</label>
          <input type="text" id="completeaddress2" name="completeaddress2" class="form-input" placeholder="Enter Complete Address" required value="<?php echo htmlspecialchars($contact2_complete_address); ?>" readonly><br>
        </div>
      </form>
    </div>
  </div>
  
  <footer id="contact" class="footer">
    <div class="footer-content">
        <p>© 2025 - BULSU CIT - MALOLOS</p>
        <p>Developed by: CIT 360</p>
    </div>
  </footer>

  <nav class="mobile-navbar">
    <button class="icon-button <?php echo $currentPage === 'home' ? 'active' : ''; ?>" onclick="redirectToHome()">
      <div>
        <i class="fa-solid fa-home" style="color: #ecf0f1;"></i>
        <span>Home</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'report' ? 'active' : ''; ?>" onclick="redirectToReportIncident()">
      <div>
        <i class="fa-solid fa-flag" style="color: #ecf0f1;"></i>
        <span>Report</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'counseling' ? 'active' : ''; ?>" onclick="redirectToCounseling()">
      <div>
        <i class="fa-solid fa-comments" style="color: #ecf0f1;"></i>
        <span>Counseling</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'notifications' ? 'active' : ''; ?>" onclick="redirectToNotifications()">
      <div>
        <i class="fa-solid fa-bell" style="color: #ecf0f1;"></i>
        <span>Notifications</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" onclick="redirectToProfile()">
      <div>
        <i class="fa-solid fa-user" style="color: #ecf0f1;"></i>
        <span>Profile</span>
      </div>
    </button>
  </nav>

  <script>
    function openTab(evt, tabName) {
      // Hide all tab content
      var i, tabcontent, tabbuttons;
      tabcontent = document.getElementsByClassName("tab-content");
      for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";  
      }
      // Remove the active class from all tab buttons
      tabbuttons = document.getElementsByClassName("tab-button");
      for (i = 0; i < tabbuttons.length; i++) {
        tabbuttons[i].className = tabbuttons[i].className.replace(" active", "");
      }
      // Show the current tab and add an "active" class to the button that opened the tab
      document.getElementById(tabName).style.display = "block";  
      evt.currentTarget.className += " active";
    }

    function redirectToLogin() {
      window.location.href = 'login.php';
    }
    function redirectToRegister() {
      window.location.href = 'register.php';
    }
    function redirectToNotifications() {
      window.location.href = 'notification.php';
    }
    function redirectToReportIncident() {
      window.location.href = 'reportIncident.php';
    }
    function redirectToCounseling() {
      window.location.href = 'counseling.php';
    }
    function toggleDropdown() {
      const dropdown = document.getElementById('profileDropdown');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
    function redirectToHome() {
      window.location.href = 'homePage.php';
    }
    function redirectToLogout() {
      window.location.href = '../studentPortal/logout.php';
    }
    function redirectToProfile() {
      window.location.href = 'profile.php';
    }
    function toggleProfileDropdown() {
      const dropdown = document.getElementById('profileCompletionDropdown');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    let lastScrollTop = 0; // Variable to store the last scroll position
    const mobileNavbar = document.querySelector('.mobile-navbar'); // Select the mobile navbar

    window.addEventListener('scroll', function() {
      const currentScroll = window.pageYOffset || document.documentElement.scrollTop; // Get current scroll position

      if (currentScroll > lastScrollTop) {
        // Scrolling down
        mobileNavbar.style.transform = 'translateY(100%)'; // Hide the navbar
      } else {
        // Scrolling up
        mobileNavbar.style.transform = 'translateY(0)'; // Show the navbar
      }
      lastScrollTop = currentScroll <= 0 ? 0 : currentScroll; // Update last scroll position
    });

    function openModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = "none";
    }

    // Close the modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('additionalInfoModal');
      if (event.target === modal) {
        closeModal('additionalInfoModal');
      }
    }

    document.getElementById('additionalInfoForm').onsubmit = function(event) {
      event.preventDefault(); // Prevent the default form submission

      // Validate fields
      let validationMessage = '';
      const fields = [
        'modalGender', 'modalAge', 'modalPlaceOfBirth', 
        'modalCivilStatus', 'modalNationality', 'modalReligion', 
        'modalHeight', 'modalWeight', 'modalBloodType'
      ];

      fields.forEach(field => {
        if (document.getElementById(field).value.trim() === '') {
          validationMessage += `${field.replace('modal', '')} cannot be empty.<br>`;
        }
      });

      if (validationMessage) {
        document.getElementById('validationMessage').innerHTML = validationMessage;
        return; // Stop submission if there are validation errors
      }

      // If validation passes, submit the form
      const formData = new FormData(this);
      fetch('save_additional_info.php', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(data => {
        if (data.status === 'success') {
          // Show the success modal
          openModal('successModal');
          // Update the Additional Information link to show a check mark
          const additionalInfoCheckMark = document.getElementById('additionalInfoCheckMark');
          additionalInfoCheckMark.innerHTML = ' &#10003;'; // Add a check mark
          closeModal('additionalInfoModal'); // Close the additional info modal
        } else {
          document.getElementById('validationMessage').innerHTML = data.message;
        }
      });
    };

    document.getElementById('additionalInfoLink').onclick = function() {
        const additionalInfoCheckMark = document.getElementById('additionalInfoCheckMark');
        console.log("Check mark status:", additionalInfoCheckMark.innerHTML.trim()); // Debugging line
        if (additionalInfoCheckMark.innerHTML.trim() === '&#10003;') {
            // If the check mark is present, show the already filled modal
            openModal('alreadyFilledModal');
        } else {
            // Otherwise, open the additional information modal
            openModal('additionalInfoModal');
        }
    };

    document.getElementById('addressForm').onsubmit = function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(this); // Create a FormData object from the form

        fetch('save_address.php', { // Send the data to the new PHP file
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                openModal('successModal'); // Show success modal
                closeModal('addressModal'); // Close the address modal
            } else {
                alert(data.message); // Show error message
            }
        })
        .catch(error => console.error('Error:', error)); // Handle any errors
    };

    document.getElementById('contact1Form').onsubmit = function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(this); // Create a FormData object from the form

        fetch('save_contact1.php', { // Send the data to the new PHP file
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                openModal('successModal'); // Show success modal
                closeModal('contact1Modal'); // Close the contact modal
            } else {
                alert(data.message); // Show error message
            }
        })
        .catch(error => console.error('Error:', error)); // Handle any errors
    };

    // Fetch data from contact_persons table and populate fields
    fetch('get_contact_person.php') // Assuming you have a PHP file to get the contact person data
        .then(response => response.json())
        .then(data => {
            if (data) {
                // Populate relationship dropdown
                const relationshipSelect = document.getElementById('relationship1');
                relationshipSelect.value = data.relationship; // Set the selected value

                // Populate other fields
                document.getElementById('lastname1').value = data.last_name; // Populate last name
                document.getElementById('firstname1').value = data.first_name; // Populate first name
                document.getElementById('middlename1').value = data.middle_name; // Populate middle name
                document.getElementById('age1').value = data.age; // Populate age
                document.getElementById('telephone1').value = data.telephone_number; // Populate telephone number
                document.getElementById('contactnumber1').value = data.contact_number; // Populate contact number
                document.getElementById('email1').value = data.email; // Populate email
                document.getElementById('completeaddress1').value = data.complete_address; // Populate complete address
            }
        });

    document.getElementById('contact2Form').onsubmit = function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(this); // Create a FormData object from the form

        // Check if user_id exists in contact_persons_2
        fetch('check_user.php', { // New PHP file to check user_id
            method: 'POST',
            body: JSON.stringify({ user_id: formData.get('user_id') }), // Send user_id
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) { // If user_id exists
                // Populate the Contact2 form with data
                document.getElementById('lastname2').value = data.last_name; // Populate last name
                document.getElementById('firstname2').value = data.first_name; // Populate first name
                document.getElementById('middlename2').value = data.middle_name; // Populate middle name
                document.getElementById('age2').value = data.age; // Populate age
                document.getElementById('telephone2').value = data.telephone_number; // Populate telephone number
                document.getElementById('contactnumber2').value = data.contact_number; // Populate contact number
                document.getElementById('email2').value = data.email; // Populate email
                document.getElementById('completeaddress2').value = data.complete_address; // Populate complete address
                openModal('contact2Modal'); // Show the contact modal
            } else {
                // Proceed with the existing fetch to save data
                fetch('save_contact2.php', { // Send the data to the new PHP file
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        openModal('contact2SuccessModal'); // Show success modal for Contact Person #2
                        closeModal('contact2Modal'); // Close the contact modal
                    } else {
                        alert(data.message); // Show error message
                    }
                })
                .catch(error => console.error('Error:', error)); // Handle any errors
            }
        })
        .catch(error => console.error('Error:', error)); // Handle any errors
    };

    // Theme switch functionality
    const themeSwitch = document.getElementById('theme-switch');
    
    // Check for saved theme preference or use dark mode as default
    const currentTheme = localStorage.getItem('theme') || 'dark';
    if (currentTheme === 'dark') {
      document.body.classList.add('dark-mode');
      themeSwitch.checked = true;
    }

    themeSwitch.addEventListener('change', function() {
      if (this.checked) {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        window.location.href = 'settings.php'; // Stay in dark mode
      } else {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        window.location.href = '../lightModeStudent/settings.php'; // Switch to light mode
      }
    });
  </script>
</body>
</html>