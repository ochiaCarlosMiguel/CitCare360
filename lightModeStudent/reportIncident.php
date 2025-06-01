<?php
// Start the session and get user_id first
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

// Fetch the user's full name, student number, email, phone number, and department from the database
$query = "SELECT first_name, last_name, student_number, email, phone_number, d.name AS department_name, user_profile 
          FROM users u 
          JOIN departments d ON u.department = d.id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $first_name = $user['first_name'];
    $full_name = $user['first_name'] . ' ' . $user['last_name'];
    $student_number = $user['student_number'];
    $email = $user['email'];
    $phone_number = $user['phone_number'];
    $department = $user['department_name'];
    $user_profile = '../image/' . $user['user_profile'];
} else {
    echo "User not found.";
    // Handle the case where the user is not found in the database
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch input data from the form
    $full_name = $_POST['fullName'];
    $student_number = $_POST['studentNumber'];
    $email = $_POST['email'];
    $phone_number = $_POST['phoneNumber'];
    $department = $_POST['department'];
    
    // Check if the subject is "Other" and get the input from the otherSubject field
    $subject_report = $_POST['subject'];
    if ($subject_report === 'Other') {
        $subject_report = $_POST['otherSubject']; // Use the value from the otherSubject input
    }

    $description = $_POST['description'];
    $user_id = $_SESSION['user_id']; // Get the user_id from session

    // Handle file upload
    $evidence = [];
    if (isset($_FILES['evidence'])) {
        foreach ($_FILES['evidence']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['evidence']['error'][$key] === UPLOAD_ERR_OK) {
                $upload_dir = '../image/'; // Directory to store uploaded files
                $file_name = uniqid() . '_' . basename($_FILES['evidence']['name'][$key]); // Unique file name
                $target_file = $upload_dir . $file_name;

                // Move the uploaded file to the designated directory
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $evidence[] = $target_file; // Store the file path in the evidence array
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error uploading the file.']);
                    exit;
                }
            }
        }
    }

    // Update the validation logic to allow zero files
    if (count($evidence) > 5) {
        echo json_encode(['success' => false, 'message' => 'Please upload a maximum of 5 images.']);
        exit;
    }

    // Convert the evidence array to a comma-separated string
    $evidence_string = implode(',', $evidence);

    // First insert the incident
    $insert_query = "INSERT INTO incidents (user_id, full_name, student_number, email, phone_number, department, subject_report, description, evidence) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);

    if ($insert_stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $insert_stmt->bind_param("issssssss", 
        $user_id,
        $full_name, 
        $student_number, 
        $email, 
        $phone_number, 
        $department, 
        $subject_report, 
        $description, 
        $evidence_string
    );
    
    try {
        if (!$insert_stmt->execute()) {
            throw new Exception($insert_stmt->error);
        }

        // Get the last inserted ID
        $incident_id = $conn->insert_id;

        // Handle meeting schedule if provided
        if (isset($_POST['meeting_date']) && isset($_POST['meeting_time']) && isset($_POST['admin_id'])) {
            $meeting_date = $_POST['meeting_date'];
            $meeting_time = $_POST['meeting_time'];
            $admin_id = $_POST['admin_id'];

            // Insert meeting schedule with the incident_id
            $schedule_query = "INSERT INTO meeting_schedules (incident_id, admin_id, meeting_date, meeting_time, status) 
                             VALUES (?, ?, ?, ?, 'PENDING')";
            $schedule_stmt = $conn->prepare($schedule_query);
            $schedule_stmt->bind_param("iiss", $incident_id, $admin_id, $meeting_date, $meeting_time);
            
            if (!$schedule_stmt->execute()) {
                throw new Exception($schedule_stmt->error);
            }

            // Get the meeting schedule ID
            $meeting_schedule_id = $conn->insert_id;

            // Update the incident with the meeting_schedule_id
            $update_incident_query = "UPDATE incidents SET meeting_schedule_id = ? WHERE id = ?";
            $update_incident_stmt = $conn->prepare($update_incident_query);
            $update_incident_stmt->bind_param("ii", $meeting_schedule_id, $incident_id);
            
            if (!$update_incident_stmt->execute()) {
                throw new Exception($update_incident_stmt->error);
            }
        }

        // Prepare incident data for email
        $incidentData = [
            'full_name' => $full_name,
            'student_number' => $student_number,
            'department' => $department,
            'subject_report' => $subject_report,
            'description' => $description
        ];

        // Fetch admin emails
        $admin_query = "SELECT email FROM admin_users";
        $admin_result = $conn->query($admin_query);
        $adminEmails = [];
        while ($row = $admin_result->fetch_assoc()) {
            $adminEmails[] = $row['email'];
        }

        // Include email functionality
        require_once '../connection/email.php';

        // Send email notification
        $emailResult = sendIncidentNotification($incidentData, $adminEmails);

        echo json_encode(['success' => true, 'message' => 'Your report has been submitted successfully!']);
    } catch (Exception $e) {
        error_log('Execute failed: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error submitting the incident report: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch available admins and their blocked times
$admin_query = "SELECT au.id, au.name, au.email, 
                GROUP_CONCAT(CONCAT(abt.blocked_date, '|', TIME_FORMAT(abt.blocked_time, '%H:%i')) SEPARATOR ';') as blocked_times
                FROM admin_users au
                LEFT JOIN admin_blocked_times abt ON au.id = abt.admin_id
                WHERE au.user_role = 'admin'
                GROUP BY au.id";
$admin_result = $conn->query($admin_query);
$admins = [];
while ($row = $admin_result->fetch_assoc()) {
    $admins[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Incidents</title>
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
    }
    
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.5);
      z-index: -1;
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
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      padding: 15px 50px; 
      position: sticky; 
      top: 0; 
      z-index: 1000; 
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .navbar .logo {
      font-size: 36px;
      font-weight: 700;
      color: #2d3436;
      text-shadow: 1px 1px #ffffff;
    }
    .navbar .logo span.cit {
      color: #0984e3;
    }
    .nav-menu { 
      display: flex; 
      gap: 30px; 
      list-style: none; 
      margin: 0 auto;
    }
    .nav-menu li a { 
      color: #2d3436;
      padding: 10px 15px;
      border-radius: 4px; 
      transition: background 0.3s, color 0.3s;
      font-size: 18px;
    }
    .nav-menu li a:hover { 
      background: rgba(9, 132, 227, 0.1);
      color: #0984e3;
    }
    .nav-menu li a.active {
      background: rgba(9, 132, 227, 0.15);
      color: #0984e3;
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
      background: linear-gradient(135deg, #0984e3, #74b9ff);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      cursor: pointer;
      transition: all 0.3s;
    }
    .icon-button:hover {
      background: linear-gradient(135deg, #74b9ff, #0984e3);
      transform: scale(1.1);
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
      .service-container { 
        flex-direction: column;
      }
      .service-box { width: 90%; }
      .footer {
        display: none;
      }
      body {
        font-size: 14px; /* Slightly smaller font size on mobile */
      }
      h1 {
        font-size: 28px; /* Adjust heading size for mobile */
      }
      h2 {
        font-size: 24px; /* Adjust subheading size for mobile */
      }
      h3 {
        font-size: 20px; /* Adjust smaller heading size for mobile */
      }
      p {
        font-size: 14px; /* Adjust paragraph size for mobile */
      }
      .services {
        background-size: 150% 150%; /* Reduce background size for mobile */
        background-position: center; /* Center the background */
        padding: 0px 20px 0px 50px; /* Adjust padding for mobile view */
      }
    }

    @media (min-width: 769px) {
      .mobile-navbar {
        display: none;
      }
    }

    .notification {
      margin-right: 20px;
      margin-left: -180px;
    }

    .user-profile {
      position: relative;
      display: flex;
      align-items: center;
      margin-left: auto;
    }

    .profile-container {
      display: flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      padding: 12px;
      border-radius: 25px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      border: 1px solid #74b9ff;
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
      background: linear-gradient(135deg, #ffffff, #e3f2fd);
      min-width: 160px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      z-index: 1;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #74b9ff;
    }

    .dropdown-content a {
      color: #2d3436;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      transition: background 0.3s;
    }

    .dropdown-content a:hover {
      background: rgba(9, 132, 227, 0.1);
    }

    .profile-icon img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #fff;
    }


    .main-content {
      padding: 40px 20px;
      color: #fff;
      position: relative; /* Position relative to stack above the pseudo-element */
      z-index: 1; /* Ensure it is above the overlay */
    }

    .card {
      background-color: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      margin: auto;
    }

    .card h2, .card h3 {
      color: #0984e3;
      margin-bottom: 20px;
    }

    .card p {
      color: #2d3436;
      margin-bottom: 20px;
    }
    .report-status-btn {
    background: #4F46E5; /* Button background color */
    color: #fff; /* Text color */
    padding: 10px 20px; /* Padding for the button */
    border-radius: 8px; /* Rounded corners */
    border: none; /* Remove default border */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background 0.3s, transform 0.3s; /* Smooth transition effects */
    font-size: 16px; /* Font size */
  }

  .report-status-btn:hover {
    background: #3B3A4A; /* Darker background on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
  }

    .report-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .report-form label {
      font-weight: bold;
      color: #2d3436;
      margin-bottom: 5px;
    }

    .report-form input, .report-form select, .report-form textarea {
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #74b9ff;
      background-color: #ffffff;
      color: #2d3436;
      transition: border-color 0.3s;
    }

    .report-form input:focus, .report-form select:focus, .report-form textarea:focus {
      border-color: #0984e3;
      outline: none;
    }

    .evidence-section {
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-top: 30px;
    }

    .evidence-upload {
      border: 2px dashed #3D3C4B;
      padding: 20px;
      text-align: center;
      width: 100%;
      max-width: 800px;
      height: 150px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background-color: #333;
      border-radius: 8px;
    }

    .evidence-preview {
      margin-top: 20px;
      padding: 10px;
      background-color: #222232;
      border-radius: 8px;
      max-width: 800px;
      min-height: 150px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
    }

    .take-photo {
      background: #f4a261;
      color: #fff;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .take-photo:hover {
      background: #e76f51;
    }

    @media (max-width: 768px) {
      .card {
        padding: 20px;
      }
    }

    .file-display {
      display: flex;
      align-items: center;
      background-color: #333;
      border-radius: 8px;
      padding: 10px;
      margin: 10px 0;
      width: 100%;
      max-width: 800px;
      color: #fff;
      transition: background-color 0.3s;
    }

    .file-display:hover {
      background-color: #444;
    }

    .file-icon {
      font-size: 24px;
      margin-right: 10px;
    }

    .file-info {
      flex-grow: 1;
    }

    .file-name {
      font-weight: bold;
    }

    .file-size {
      font-size: 12px;
      color: #aaa;
    }

    .progress-bar {
      width: 100%;
      background-color: #444;
      border-radius: 4px;
      overflow: hidden;
      margin-top: 5px;
    }

    .progress {
      height: 5px;
      background-color: #4caf50;
      width: 0;
      animation: progressAnimation 2s forwards;
    }

    @keyframes progressAnimation {
      from { width: 0; }
      to { width: 100%; }
    }

    .done-label {
      font-size: 12px;
      color: #4caf50;
      margin-left: 10px;
      display: none;
    }

    .delete-button {
      background: none;
      border: none;
      color: #e76f51;
      cursor: pointer;
      font-size: 16px;
      margin-left: 10px;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.8);
    }

    .modal-content {
      margin: 15% auto;
      padding: 20px;
      width: 80%;
      max-width: 600px;
      background-color: #333;
      border-radius: 8px;
      text-align: center;
    }

    .modal-content img, .modal-content video {
      width: 100%;
      height: auto;
      border-radius: 8px;
    }

    .close-modal {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close-modal:hover,
    .close-modal:focus {
      color: #fff;
      text-decoration: none;
      cursor: pointer;
    }

    .alert {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: #f4a261;
      color: #fff;
      padding: 15px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      z-index: 1001;
      display: none; /* Hidden by default */
      transition: opacity 0.3s ease;
    }

    .alert.show {
      display: block;
      opacity: 1;
    }

    .alert .close-alert {
      background: none;
      border: none;
      color: #fff;
      font-size: 20px;
      cursor: pointer;
      float: right;
      margin-left: 10px;
    }

    .submit-btn {
      background: linear-gradient(135deg, #0984e3, #74b9ff);
      color: #ffffff;
      padding: 15px 30px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
      width: 100%;
      max-width: 300px;
      margin: 20px auto;
    }
    .nav-menu li a.active {
      background: rgba(9, 132, 227, 0.15);
      color: #0984e3;
      border-radius: 4px;
    }

    .camera-container {
      text-align: center;
      margin: 20px 0;
    }

    #video {
      width: 100%;
      max-width: 600px;
      border: 2px solid #4F46E5;
      border-radius: 8px;
    }

    .camera-controls {
      margin-top: 10px;
    }

    .take-photo {
      background: #4F46E5;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .take-photo:hover {
      background: #3B3A4A;
    }

    .evidence-preview {
      margin-top: 20px;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
    }

    .file-display {
      margin: 10px;
      border: 1px solid #4F46E5;
      border-radius: 8px;
      padding: 10px;
      background-color: rgba(30, 30, 30, 0.7);
      color: #fff;
    }

    .dropdown {
      position: relative;
      display: inline-block;
    }

    .dropdown-toggle {
      display: flex; /* Flexbox for icon and text alignment */
      align-items: center; /* Center items vertically */
      color: white; /* Text color */
      text-decoration: none; /* Remove underline */
      padding: 10px 20px; /* Padding for the dropdown toggle */
      border-radius: 5px; /* Rounded corners */
      transition: background-color 0.3s; /* Smooth background color transition */
    }

    .dropdown-toggle:hover {
      background-color: #3B3A4A; /* Darker background on hover */
    }

    .submenu {
        display: none; /* Ensure submenu is hidden by default */
        position: absolute; /* Position it below the button */
        background-color: #222232; /* Dropdown background color */
        min-width: 160px; /* Minimum width of dropdown */
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); /* Shadow for dropdown */
        z-index: 1; /* Ensure it appears above other content */
        border-radius: 5px; /* Rounded corners for dropdown */
        transition: display 0.3s ease; /* Smooth transition */
    }
    .dropdown:hover .submenu {
        display: none; /* Ensure submenu does not appear on hover */
    }

    .submenu li {
      list-style: none; /* Remove list style */
    }

    .submenu a {
      color: white; /* Link text color */
      padding: 12px 16px; /* Padding for links */
      text-decoration: none; /* Remove underline */
      display: flex; /* Flex display for icon and text alignment */
      align-items: center; /* Center items vertically */
      transition: background 0.3s; /* Smooth background transition */
    }

    .submenu a:hover {
      background-color: #3B3A4A; /* Background color on link hover */
    }

    .submenu a i {
      margin-right: 8px; /* Space between icon and text */
    }
            /* Header styles */
            .mobile-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px 20px;
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      color: #2d3436;
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
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      color: #2d3436;
      box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
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
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      color: #2d3436;
      padding: 20px 50px; 
      border-top: 1px solid #74b9ff;
    }
    .spacer {
      display: none; /* Hide spacer as it's no longer needed */
    }

    @media (max-width: 768px) {
      .main-content {
        padding-top: 120px; /* Adjust to the height of the mobile-header */
        padding-bottom: 60px; /* Adjust to the height of the mobile-navbar */
      }
    }

  </style>
</head>
<body>  
<header class="mobile-header">
    <div class="logo-container">
      <img src="<?php echo htmlspecialchars('../image/logo.png'); ?>" alt="Logo" class="logo"> <!-- Image Logo -->
      <span class="text-logo">CIT 360</span> <!-- Text Logo -->
    </div>
    <button class="logout-button" onclick="redirectToLogout()">
      <i class="fa-solid fa-sign-out-alt"></i> <!-- Updated Logout icon -->
    </button>
  </header>

  <div class="overlay"></div> <!-- Overlay div -->
  <nav class="navbar">
    <div class="logo" style="display: flex; align-items: center;">
      <img src="<?php echo htmlspecialchars('../image/logo.png'); ?>" alt="Logo" style="height: 50px; margin-right: 10px;">
      <span class="cit" style="font-size: 28px; margin-right: 5px;">CIT</span>
      <span style="font-size: 24px;">CARE 360</span>
    </div>
    <ul class="nav-menu">
      <li><a href="homePage.php">Home</a></li>
      <li><a href="reportIncident.php" class="active">Report</a></li>
      <li><a href="reportStatus.php">Report Status</a></li>
      <!-- <li><a href="#contact">Contact</a></li> -->
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
  
  <div class="main-content">
    <!-- Remove the conditional display of the success message -->
    <!-- <?php if (isset($successMessage)): ?>
        <h2><?php echo htmlspecialchars($successMessage); ?></h2>
    <?php endif; ?> -->
    <div class="card">
       <h2 style="display: flex; justify-content: space-between; align-items: center;">
         Report an Incident
       </h2>
      <form class="report-form" id="incidentForm" method="POST" action="" enctype="multipart/form-data">
        <label for="fullName">Full Name</label>
        <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($full_name); ?>" readonly>

        <label for="studentNumber">Student Number</label>
        <input type="text" id="studentNumber" name="studentNumber" value="<?php echo htmlspecialchars($student_number); ?>" readonly>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>

        <label for="phoneNumber">Phone Number</label>
        <input type="tel" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($phone_number); ?>" readonly>

        <label for="department">Department</label>
        <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($department); ?>" readonly>

        <label for="subject">Subject of Report</label>
        <select id="subject" name="subject" required onchange="toggleOtherSubject()">
            <option value="">Select a subject</option>
            <option value="Harassment">Harassment</option>
            <option value="Discrimination">Discrimination</option>
            <option value="Sexual harassment">Sexual harassment</option>
            <option value="Verbal abuse">Verbal abuse</option>
            <option value="Cyberbullying">Cyberbullying</option>
            <option value="Physical assault">Physical assault</option>
            <option value="Threats and intimidation">Threats and intimidation</option>
            <option value="Possession of weapons">Possession of weapons</option>
            <option value="Sexual assault">Sexual assault</option>
            <option value="Stalking">Stalking</option>
            <option value="Cheating and plagiarism">Cheating and plagiarism</option>
            <option value="Theft and vandalism">Theft and vandalism</option>
            <option value="Fraud and scams">Fraud and scams</option>
            <option value="Self-harm and suicidal threats">Self-harm and suicidal threats</option>
            <option value="Depression and anxiety">Depression and anxiety</option>
            <option value="Medical emergencies">Medical emergencies</option>
            <option value="Fire incidents">Fire incidents</option>
            <option value="Laboratory accidents">Laboratory accidents</option>
            <option value="Conflicts with teachers or staff">Conflicts with teachers or staff</option>
            <option value="Trespassing in restricted areas">Trespassing in restricted areas</option>
            <option value="Other">Other</option>
        </select>
        <input type="text" id="otherSubject" name="otherSubject" placeholder="Please specify if 'Other'" style="display:none;">

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="12" style="min-height: 300px; font-size: 14px; line-height: 1.6;" placeholder="Describe the incident in detail" required></textarea>

        <div class="schedule-section" style="margin-top: 20px;">
            <h3 style="color: #0984e3; margin-bottom: 15px;">Schedule Meeting (Optional)</h3>
            <div class="schedule-form" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label for="admin_id">Select Admin</label>
                    <select id="admin_id" name="admin_id" onchange="loadAvailableTimes()">
                        <option value="">Select an admin</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo htmlspecialchars($admin['id']); ?>">
                                <?php echo htmlspecialchars($admin['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="meeting_date">Meeting Date</label>
                    <input type="date" id="meeting_date" name="meeting_date" min="<?php echo date('Y-m-d'); ?>" onchange="loadAvailableTimes()">
                </div>
                <div>
                    <label for="meeting_time">Meeting Time</label>
                    <select id="meeting_time" name="meeting_time">
                        <option value="">Select a time</option>
                    </select>
                </div>
            </div>
        </div>

        <label for="evidence">Upload Evidence (Image) <span style="font-size: 12px; color: #e76f51;">(Optional)</span></label>
        <input type="file" id="fileInput" name="evidence[]" accept="image/*" multiple>
        
        <div class="evidence-preview"></div>

        <button type="submit" class="submit-btn">Submit</button>
      </form>
    </div>
  </div>

  <div class="spacer"></div>

  <footer id="contact" class="footer">
    <div class="footer-content" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px;">
      <p style="margin: 0; font-size: 12px; color: #f4a261;">© 2025 - BULSU CIT - MALOLOS</p>
      <p style="margin: 0; font-size: 12px;">Developed by: CIT 360</p>
    </div>
  </footer>

  <div id="mediaModal" class="modal">
    <div class="modal-content">
      <span class="close-modal">&times;</span>
      <div id="modalMediaContainer"></div>
    </div>
  </div>
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
  <div id="successModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeSuccessModal()">&times;</span>
        <p>Your report has been submitted successfully!</p>
    </div>
  </div>
  <div id="alertModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeAlertModal()">&times;</span>
        <p id="alertMessage" style="color: #e76f51; font-size: 18px;">Your alert message here!</p>
    </div>
  </div>
  <script>
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
    function redirectToLogout() {
      window.location.href = '../studentPortal/logout.php';
    }
    function redirectToHome() {
      window.location.href = 'homePage.php';
    }
    function redirectToProfile() {
      window.location.href = 'profile.php';
    }
    function redirectToReportStatus() {
    window.location.href = 'reportStatus.php'; // Adjust the URL as needed
    }
    function toggleDropdown() {
      const dropdown = document.getElementById('profileDropdown');
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
    })
    
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.querySelector('.evidence-preview'); // Ensure this is defined

    // Initialize an array to hold selected files
    let selectedFiles = [];

    // Handle file input
    fileInput.addEventListener('change', (event) => {
        const files = event.target.files;
        for (let i = 0; i < files.length; i++) {
            // Check if the file is already selected
            if (!selectedFiles.includes(files[i])) {
                selectedFiles.push(files[i]); // Add new file to the array
                const fileURL = URL.createObjectURL(files[i]);
                createFileDisplay(fileURL, files[i].type.startsWith('video') ? 'video' : 'image', files[i]);
            }
        }
    });

    // Function to create a display for captured media
    function createFileDisplay(mediaSrc, type, file) {
        const fileContainer = document.createElement('div');
        fileContainer.className = 'file-display';
        fileContainer.style.position = 'relative'; // Position relative for absolute positioning of the delete button

        if (type === 'image') {
            const img = document.createElement('img');
            img.src = mediaSrc;
            img.style.width = '100%';
            img.style.borderRadius = '8px';
            fileContainer.appendChild(img);
        } else if (type === 'video') {
            const videoElement = document.createElement('video');
            videoElement.src = mediaSrc;
            videoElement.controls = true;
            videoElement.style.width = '100%';
            videoElement.style.borderRadius = '8px';
            fileContainer.appendChild(videoElement);
        }

        // Create delete button
        const deleteButton = document.createElement('span');
        deleteButton.innerHTML = '&times;'; // "X" character
        deleteButton.style.position = 'absolute';
        deleteButton.style.top = '10px';
        deleteButton.style.right = '10px';
        deleteButton.style.cursor = 'pointer';
        deleteButton.style.color = '#e76f51'; // Color for the delete button
        deleteButton.style.fontSize = '24px'; // Size of the delete button
        deleteButton.onclick = () => {
            // Remove the file from the selectedFiles array
            selectedFiles = selectedFiles.filter(selectedFile => selectedFile !== file);
            // Remove the file display from the preview container
            previewContainer.removeChild(fileContainer);
        };

        fileContainer.appendChild(deleteButton); // Append the delete button to the file container
        previewContainer.appendChild(fileContainer); // This should work now
    }

    const mediaModal = document.getElementById('mediaModal');
    const modalMediaContainer = document.getElementById('modalMediaContainer');
    const closeModal = document.querySelector('.close-modal');

    function showModal(mediaElement, type) {
      modalMediaContainer.innerHTML = '';
      const mediaClone = mediaElement.cloneNode(true);
      if (type === 'video') {
        mediaClone.controls = true;
      }
      modalMediaContainer.appendChild(mediaClone);
      mediaModal.style.display = 'block';
    }

    closeModal.onclick = function() {
      mediaModal.style.display = 'none';
    }

    window.onclick = function(event) {
      if (event.target === mediaModal) {
        mediaModal.style.display = 'none';
      }
    }

    document.getElementById('incidentForm').addEventListener('submit', function(event) {
      // Prevent the default form submission
      event.preventDefault();

      // Create a FormData object to send the form data
      const formData = new FormData(this);

      // Append all selected files to the FormData
      selectedFiles.forEach(file => {
          formData.append('evidence[]', file);
      });

      // Send the form data using fetch
      fetch('', {
          method: 'POST',
          body: formData
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              // Show the success modal
              const successModal = document.getElementById('successModal');
              successModal.style.display = 'block';

              // Scroll to the success modal to ensure visibility
              successModal.scrollIntoView({ behavior: 'smooth' });

              // Hide the modal after 5 seconds and reload the page
              setTimeout(() => {
                  closeSuccessModal();
                  showAlert(data.message);
                  location.reload(); // Reload the page to avoid double submission
              }, 5000);
          } else {
              // Show the alert modal with the error message
              showAlertModal(data.message); // Show the error message in the modal
          }
      })
      .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while submitting the form.');
      });
    });

    function showAlert(message) {
      const alertBox = document.getElementById('alertBox');
      alertBox.querySelector('span').textContent = message;
      alertBox.classList.add('show');

      // Scroll to the alert box to ensure visibility
      alertBox.scrollIntoView({ behavior: 'smooth' });

      // Hide the alert after 5 seconds and reload the page
      setTimeout(() => {
        closeAlert();
        location.reload(); // Reload the page to reset the form
      }, 5000);
    }

    function closeAlert() {
      const alertBox = document.getElementById('alertBox');
      alertBox.classList.remove('show');
    }

    function closeSuccessModal() {
        const successModal = document.getElementById('successModal');
        successModal.style.display = 'none';
        location.reload(); // Reload the page immediately after closing the modal
    }

    function toggleMenu() {
        const navMenu = document.getElementById('navMenu');
        const hamburger = document.querySelector('.hamburger');
        navMenu.classList.toggle('active');
        hamburger.classList.toggle('active'); // Toggle active class for animation
    }

    function toggleDropdownContent(event) {
        event.preventDefault(); // Prevent the default anchor behavior
        const dropdown = document.getElementById('incidentCounselingDropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
    
    function toggleOtherSubject() {
        const subjectSelect = document.getElementById('subject');
        const otherSubjectInput = document.getElementById('otherSubject');
        const descriptionTextarea = document.getElementById('description');
        
        if (subjectSelect.value === 'Other') {
            otherSubjectInput.style.display = 'block';
        } else {
            otherSubjectInput.style.display = 'none';
        }

        // Insert template if a subject is selected (including 'Other')
        if (subjectSelect.value !== '') {
            const currentDate = new Date().toLocaleString();
            const template = `📄 Incident Report Template

On [${currentDate}], an incident took place at [insert exact location]. The individuals involved were [insert full names and their respective roles]. The incident unfolded as follows: [provide a clear and detailed account of what happened, including the sequence of events, contributing factors, and the situation before, during, and after the incident]. Witnesses present at the scene included [insert names and roles, or indicate "none" if no witnesses were present]. Following the incident, the immediate actions taken were: [explain any steps taken in response to the incident, such as reporting, first aid, or intervention]. I would like to respectfully recommend or request the following: [state any suggestions, preventive measures, or assistance needed].

This report is submitted by [${document.getElementById('fullName').value}] on [${currentDate}].`;

            descriptionTextarea.value = template;
        } else {
            descriptionTextarea.value = ''; // Clear the textarea if no subject is selected
        }
    }

    // Add these new functions for scheduling
    function loadAvailableTimes() {
        const adminId = document.getElementById('admin_id').value;
        const meetingDate = document.getElementById('meeting_date').value;
        const timeSelect = document.getElementById('meeting_time');
        
        if (!adminId || !meetingDate) {
            timeSelect.innerHTML = '<option value="">Select a time</option>';
            return;
        }

        // Get admin's blocked times
        const admin = <?php echo json_encode($admins); ?>.find(a => a.id == adminId);
        const blockedTimes = admin.blocked_times ? admin.blocked_times.split(';') : [];
        
        // Generate time slots (9 AM to 5 PM)
        const timeSlots = [];
        for (let hour = 9; hour <= 17; hour++) {
            timeSlots.push(`${hour.toString().padStart(2, '0')}:00`);
        }

        // Update time select options
        timeSelect.innerHTML = '<option value="">Select a time</option>';
        timeSlots.forEach(time => {
            const fullDateTime = `${meetingDate}|${time}`;
            const isBlocked = blockedTimes.includes(fullDateTime);
            
            const option = document.createElement('option');
            option.value = time;
            option.textContent = isBlocked ? `${time} (Not Available)` : time;
            option.disabled = isBlocked;
            option.style.color = isBlocked ? '#e76f51' : '#2d3436';
            timeSelect.appendChild(option);
        });
    }

    // Add validation to form submission
    document.getElementById('incidentForm').addEventListener('submit', function(event) {
        const adminId = document.getElementById('admin_id').value;
        const meetingDate = document.getElementById('meeting_date').value;
        const meetingTime = document.getElementById('meeting_time').value;

        // If any scheduling field is filled, all must be filled
        if (adminId || meetingDate || meetingTime) {
            if (!adminId || !meetingDate || !meetingTime) {
                event.preventDefault();
                alert('Please fill in all scheduling fields or leave them all empty.');
                return;
            }
        }
    });
  </script>
</body>
</html>