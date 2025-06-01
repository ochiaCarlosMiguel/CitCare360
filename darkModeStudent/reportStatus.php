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


// Fetch the user's first name from the database
$query = "SELECT first_name, user_profile, email FROM users WHERE id = ?"; // Removed evidence from the query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $first_name = $user['first_name'];
    $user_profile = '../image/' . $user['user_profile'];
    $user_email = $user['email']; // Store user email
} else {
    echo "User not found.";
    // Handle the case where the user is not found in the database
    exit;
}

// Fetch incidents data with meeting schedule information
$incidents_query = "SELECT i.*, ms.meeting_date, ms.meeting_time, ms.status as meeting_status, 
                   au.name as admin_name
                   FROM incidents i 
                   LEFT JOIN meeting_schedules ms ON i.meeting_schedule_id = ms.id
                   LEFT JOIN admin_users au ON ms.admin_id = au.id
                   WHERE LOWER(i.email) = LOWER(?)";
$stmt = $conn->prepare($incidents_query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$incidents_result = $stmt->get_result();
$incidentsData = $incidents_result->fetch_all(MYSQLI_ASSOC);

// Displaying the incidents data in the table
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Status</title>
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
      background: url('../image/bg.png') no-repeat center center fixed; /* Only the image */
      background-size: cover; 
      color: #333; 
      line-height: 1.6; 
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
    }    /* Navbar Styles */
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
      .main-content {
        padding-top: 80px; /* Adjust to the height of the mobile-header */
        padding-bottom: 60px; /* Adjust to the height of the mobile-navbar */
      }
      .content-card {
        margin-top: 20px; /* Add margin to create space from the mobile-header */
        margin-bottom: 20px; /* Add margin to create space from the mobile-navbar */
      }
    }

    @media (min-width: 769px) {
      .mobile-navbar {
        display: none;
      }
    }

    @media (max-width: 768px) {
      .navbar { 
        flex-direction: column; /* Stack navbar items on smaller screens */
      }
      .nav-menu { 
        flex-direction: column; /* Stack nav menu items */
        gap: 10px; /* Reduced gap for better spacing */
      }
      .service-container { 
        flex-direction: column; /* Stack service boxes on smaller screens */
      }
      .service-box { width: 90%; }
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
      gap: 10px; /* Adjusted gap for better spacing */
      background-color: #444; /* Changed to a darker shade for better contrast */
      padding: 12px; /* Increased padding for better spacing */
      border-radius: 25px; /* Updated border radius for a more rounded look */
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Added shadow for depth */
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
          /* Main Content Styles */
          .main-content {
            flex: 1; /* Allow main content to grow and take available space */
            margin-left: auto; /* Center the main content */
            margin-right: auto; /* Center the main content */
            padding: 30px 40px;
            position: relative;
            max-width: 1200px; /* Set a max width for the main content */
        }

        .content-card {
            background-color: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
            background-color: rgba(30, 30, 30, 0.8);
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .table-header i {
            color: #F8B83C;
            font-size: 24px;
        }

        .table-header h2 {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            font-weight: bold;
        }

        .header-actions {
            display: flex;
            justify-content: space-between; /* Space between back button and label */
            align-items: center; /* Center items vertically */
            margin-bottom: 20px;
        }

        .back-btn {
            background-color: #4F46E5;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-family: 'Century Gothic', sans-serif;
            cursor: pointer;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background-color: #3B3A4A;
        }

        .incident-label {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            font-weight: bold;
            margin-right: 20px; /* Add some space from the right edge */
        }

        .add-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background-color: #8f0806;
            transform: translateY(-2px);
        }

        .header-line {
            border: none;
            border-bottom: 1px solid #F4A261;
            margin: 10px 0 20px 0;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto; /* Enable horizontal scrolling */
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; 
            justify-content: center; 
            margin: 0 auto; /* Center the container */
        }

        table {
            width: 100%; /* Ensure the table takes full width */
            max-width: 1200px; /* Set a max width for the table */
            border-collapse: separate;
            border-spacing: 0;
            table-layout: auto; /* Allow columns to adjust based on content */
        }

        th, td {
            padding: 10px 15px; /* Adjust padding for better spacing */
            text-align: left;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            word-wrap: break-word; /* Allow text to wrap within cells */
        }

        th {
            color: #F8B83C;
            font-weight: bold;
            background-color: rgba(30, 30, 30, 0.8);
        }

        td {
            color: #AEB2B7;
        }

        tr:hover td {
            background-color: rgba(45, 45, 45, 0.6);
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .table-container {
                width: 100%; /* Ensure the table container takes full width */
            }

            table {
                table-layout: fixed; /* Fix table layout for better control */
                width: 100%;
            }

            th, td {
                font-size: 12px; /* Smaller font size for smaller screens */
                padding: 8px 10px; /* Adjust padding for smaller screens */
                word-wrap: break-word; /* Ensure text wraps within cells */
            }

            th {
                display: none; /* Hide table headers on mobile */
            }

            td {
                background-color: rgba(30, 30, 30, 0.7); /* Slightly transparent background for cells */
                color: #AEB2B7; /* Text color for cells */
                display: block; /* Make each cell a block element */
                text-align: right; /* Align text to the right */
                position: relative; /* Position relative for pseudo-elements */
                padding-left: 50%; /* Add padding to the left */
            }

            td::before {
                content: attr(data-label); /* Use data-label attribute for labels */
                position: absolute; /* Position absolutely within the cell */
                left: 0; /* Align to the left */
                width: 45%; /* Set width for labels */
                padding-left: 10px; /* Padding for labels */
                font-weight: bold; /* Bold labels */
                text-align: left; /* Align labels to the left */
            }

            tr {
                display: block; /* Make each row a block element */
                margin-bottom: 10px; /* Space between rows */
            }
        }

        /* Further adjustments for very small screens */
        @media screen and (max-width: 480px) {
            th, td {
                font-size: 10px; /* Further reduce font size for very small screens */
                padding: 6px; /* Further adjust padding */
            }

            /* Hide additional columns if needed */
            th:nth-child(2), td:nth-child(2) { /* DESCRIPTION */
                display: none;
            }
        }

        /* Add these status badge styles */
        .status-active,
        .status-inactive {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .status-inactive {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        /* Add these styles in your existing <style> tag */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 4px;
            color: white;
            font-family: 'Century Gothic', sans-serif;
            z-index: 1000;
            animation: slideIn 0.3s ease, slideOut 0.3s ease 2.7s;
        }

        .toast.success {
            background-color: rgba(76, 175, 80, 0.9);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: #222; /* Solid background for accessibility */
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            animation: slideIn 0.3s ease;
            width: 90%;
            max-width: 600px;
            background: #fff; /* Solid white background for modal content */
            color: #222; /* Dark text for readability */
        }

        .modal-header {
            border-bottom: 1px solid #F8B83C;
            padding-bottom: 15px;
        }

        .modal-header h2 {
            color: #F8B83C;
            margin: 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-body {
            padding: 20px 0;
        }

        .modal-body p {
            color: #e0e0e0;
            text-align: center;
            margin: 0;
            font-size: 16px;
        }

        .modal-footer {
            display: flex;
            justify-content: space-between; /* Space between buttons */
            margin-top: 10px; /* Add some space above the footer */
        }

        .modal-btn {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-btn {
            background: #2d2d2d;
            color: #AEB2B7;
        }

        .delete-btn {
            background: #750605;
            color: #F8B83C;
        }

        .modal-btn:hover {
            transform: translateY(-2px);
        }

        .cancel-btn:hover {
            background: #3d3d3d !important;
        }

        .delete-btn:hover {
            background: #8f0806 !important;
        }

        /* Mobile Responsive Styles */
        @media screen and (max-width: 768px) {
            .modal-content {
                margin: 5% auto; /* Adjust margin for mobile */
                padding: 15px; /* Reduce padding for mobile */
                width: 95%; /* Increase width on mobile */
            }

            .modal-header h2 {
                font-size: 18px; /* Smaller font size for header */
            }

            .modal-body {
                padding: 15px 0;
            }

            .modal-body p {
                font-size: 14px; /* Smaller font size for body text */
            }

            .modal-footer {
                flex-direction: column; /* Stack buttons vertically on mobile */
                gap: 10px; /* Add space between buttons */
            }

            .modal-btn {
                width: 100%; /* Full width buttons */
                justify-content: center; /* Center button content */
                padding: 12px; /* Larger touch target */
                font-size: 16px; /* Larger font size for better readability */
            }

            /* Adjust warning text */
            .modal-body span {
                font-size: 12px;
                margin-top: 8px;
            }
        }

        /* Animation keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Alert Styles */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: #fff;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideInRight 0.5s ease, fadeOut 0.5s ease 2.5s forwards;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.9);
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.9);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        @keyframes slideIn {
            from {
                transform: translate(-50%, -60%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        .filter-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        .filter-dropdown {
            padding: 8px;
            border: 1px solid #F4A261;
            border-radius: 5px;
            background-color: #1E1E1E;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: border-color 0.3s ease;
        }

        .filter-dropdown:hover {
            border-color: #F8B83C;
        }

        .filter-dropdown:focus {
            outline: none;
            border-color: #F8B83C;
        }

        /* Status Dropdown Styles */
        .status-dropdown {
            padding: 8px;
            border: 1px solid #F4A261;
            border-radius: 5px;
            background-color: #1E1E1E;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: border-color 0.3s ease;
        }

        .status-dropdown:hover {
            border-color: #F8B83C;
        }

        .status-dropdown:focus {
            outline: none;
            border-color: #F8B83C;
        }

        /* Add this style for the modal image */
        #modalImage {
            border: 5px solid #F8B83C; /* Add a border */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5); /* Add shadow */
            max-width: 100%; /* Ensure it doesn't overflow */
            height: auto; /* Maintain aspect ratio */
        }

        html, body {
            height: 100%; /* Ensure the body takes full height */
            margin: 0; /* Remove default margin */
            display: flex; /* Use flexbox for layout */
            flex-direction: column; /* Stack elements vertically */
        }

        .main-content {
            flex: 1; /* Allow main content to grow and take available space */
            margin-left: auto; /* Center the main content */
            margin-right: auto; /* Center the main content */
            padding: 30px 40px;
            position: relative;
            max-width: 1200px; /* Set a max width for the main content */
        }

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
      background: #1c1c1c; /* Dark footer background */
      color: #e0e0e0; /* Light footer text color */
      padding: 20px 50px; 
    }

    /* Adjust other sections as necessary */
    .services {
      background: #222; /* Dark background for services section */
      color: #e0e0e0; /* Light text color for services */
    }

    .spacer {
      display: none; /* Hide spacer as it's no longer needed */
    }

    @media (max-width: 768px) {
      .main-content {
        padding-top: 150px; /* Adjust to the height of the mobile-header */
        padding-bottom: 60px; /* Adjust to the height of the mobile-navbar */
      }
    }

    /* Tab Filter Styles */
    .filter-tabs {
        display: flex;
        justify-content: flex-start;
        gap: 10px;
        margin-bottom: 20px;
    }

    .filter-tab {
        padding: 10px;
        border: none;
        border-radius: 5px;
        background-color: #1E1E1E;
        color: #AEB2B7;
        font-family: 'Century Gothic', sans-serif;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .filter-tab.active {
        background-color: #F8B83C; /* Active tab color */
        color: #1E1E1E; /* Active text color */
    }

    .filter-tab:hover {
        background-color: #F4A261; /* Hover color */
        color: #1E1E1E; /* Hover text color */
    }

    /* Mobile View: Icon Tabs */
    @media (max-width: 768px) {
        .filter-tab {
            padding: 8px;
            font-size: 18px; /* Adjust icon size */
        }
        .filter-tab span {
            display: none; /* Hide text on mobile */
        }
    }

    @media (max-width: 768px) {
        .header-actions {
            flex-direction: column; /* Stack elements vertically on mobile */
            align-items: flex-start; /* Align items to the start */
        }

        .incident-label {
            margin-top: 10px; /* Add margin to separate from the back button */
            margin-right: 0; /* Remove right margin */
            font-size: 18px; /* Adjust font size for mobile */
        }
    }
    .read-btn {
      background-color: #4F46E5;
      color: #fff;
      border: none;
      border-radius: 5px;
      padding: 5px 10px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .read-btn:hover {
      background-color: #3B3A4A;
    }

    /* Add styles for the disabled delete button */
    .action-btn.delete.disabled {
        background-color: #4a4a4a;
        cursor: not-allowed;
        opacity: 0.5;
    }

    .action-btn.delete.disabled:hover {
        transform: none;
        background-color: #4a4a4a;
    }

    /* Style for active delete button */
    .action-btn.delete {
        background: none;
        border: none;
        color: #ff4747;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .action-btn.delete:hover {
        color: #ff0000;
        transform: scale(1.1);
    }

    /* Tooltip container */
    .tooltip-container {
        position: relative;
        display: inline-block;
    }

    /* Tooltip text */
    .tooltip {
        visibility: hidden;
        background-color: #333;
        color: #fff;
        text-align: center;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        
        /* Position the tooltip */
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        
        /* Add animation */
        opacity: 0;
        transition: opacity 0.3s, visibility 0.3s;
        
        /* Add a nice border and shadow */
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Tooltip arrow */
    .tooltip::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
    }

    /* Show tooltip on hover */
    .tooltip-container:hover .tooltip {
        visibility: visible;
        opacity: 1;
    }

    /* Mobile responsiveness for tooltip */
    @media screen and (max-width: 768px) {
        .tooltip {
            font-size: 11px;
            padding: 6px 10px;
            width: max-content;
            max-width: 200px; /* Maximum width on mobile */
            white-space: normal; /* Allow text to wrap */
        }
        
        /* Adjust position for better mobile visibility */
        .tooltip-container:hover .tooltip {
            bottom: 140%;
        }
    }

    /* Update existing disabled button styles */
    .action-btn.delete.disabled {
        background: none;
        border: none;
        color: #4a4a4a;
        cursor: not-allowed;
        opacity: 0.5;
        transition: opacity 0.3s;
    }

    .action-btn.delete.disabled:hover {
        opacity: 0.7; /* Slight opacity change on hover for feedback */
    }

    /* Update the Status Tooltip Styles */
    .status-tooltip {
        display: none;
        position: fixed; /* Change from absolute to fixed */
        top: 50%; /* Center vertically */
        left: 50%;
        transform: translate(-50%, -50%); /* Center both horizontally and vertically */
        background-color: rgba(51, 51, 51, 0.95); /* Slightly transparent background */
        color: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 9999; /* Very high z-index to ensure it's above everything */
        text-align: center;
        animation: fadeInScale 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        min-width: 280px;
        max-width: 90%;
        backdrop-filter: blur(5px); /* Add blur effect behind tooltip */
        -webkit-backdrop-filter: blur(5px);
    }

    .status-tooltip::after {
        content: '';
        position: absolute;
        bottom: -8px; /* Position arrow at bottom */
        left: 50%;
        margin-left: -8px;
        border-width: 8px;
        border-style: solid;
        border-color: rgba(51, 51, 51, 0.95) transparent transparent transparent;
    }

    /* Add new animation for scale effect */
    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    }

    /* Mobile responsiveness for tooltip */
    @media screen and (max-width: 768px) {
        .status-tooltip {
            font-size: 13px;
            padding: 12px 16px;
            min-width: 260px;
        }
    }

    /* Add overlay style */
    .tooltip-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9998; /* Just below the tooltip */
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }

    #imageModal .modal-content {
        width: 300px; /* Set the desired width */
        height: auto; /* Adjust height as needed */
        max-width: 100%; /* Ensure it doesn't exceed the viewport */
    }

    .view-image-btn:hover {
        background-color: #3B3A4A; /* Darker background on hover */
    }

    .watch-video-btn {
        background-color: #4F46E5; /* Button background color */
        color: white; /* Text color */
        border: none; /* No border */
        border-radius: 5px; /* Rounded corners */
        padding: 8px 12px; /* Padding for the button */
        cursor: pointer; /* Pointer cursor on hover */
        transition: background 0.3s; /* Smooth background transition */
    }

    .watch-video-btn:hover {
        background-color: #3B3A4A; /* Darker background on hover */
    }

    .meeting-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .meeting-details {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .meeting-date, .meeting-time {
        color: #4F46E5;
        font-weight: 500;
    }

    .meeting-status {
        margin-top: 5px;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-badge.pending {
        background-color: rgba(255, 193, 7, 0.1);
        color: #FFC107;
    }

    .status-badge.approved {
        background-color: rgba(76, 175, 80, 0.1);
        color: #4CAF50;
    }

    .status-badge.rejected {
        background-color: rgba(244, 67, 54, 0.1);
        color: #F44336;
    }

    .admin-name {
        color: #666;
        font-size: 12px;
        margin-top: 3px;
    }

    .no-meeting {
        color: #999;
        font-style: italic;
    }

    @media screen and (max-width: 768px) {
        .meeting-info {
            text-align: right;
        }
        
        .meeting-details {
            align-items: flex-end;
        }
        
        .admin-name {
            text-align: right;
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
      <li><a href="reportIncident.php">Report</a></li>
      <li><a href="reportStatus.php"  class="active">Report Status</a></li>

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
              <!-- Main Content -->
              <div class="main-content">
                <div class="spacer"></div>
                <div class="content-card dashboard-table">
                    <!-- Add this tooltip container here -->
                    <div id="statusTooltip" class="status-tooltip">
                        This report cannot be deleted because the status is <span id="tooltipStatus"></span>
                    </div>
                    <div class="header-actions">
                        <!-- Back Button -->
                        <button class="back-btn" onclick="window.location.href='reportIncident.php'">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <!-- Incident Report Label -->
                        <div class="incident-label">
                            <i class="fas fa-exclamation-triangle"></i> INCIDENT REPORT
                        </div>
                    </div>
                    <!-- Tab Filter -->
                    <div class="filter-tabs">
                        <button class="filter-tab active" onclick="filterIncidents('all')">
                            <i class="fas fa-list"></i> <span>All</span>
                        </button>
                        <button class="filter-tab" onclick="filterIncidents('NEW')">
                            <i class="fas fa-star"></i> <span>New</span>
                        </button>
                        <button class="filter-tab" onclick="filterIncidents('ACTIVE')">
                            <i class="fas fa-play"></i> <span>Active</span>
                        </button>
                        <button class="filter-tab" onclick="filterIncidents('RESOLVED')">
                            <i class="fas fa-check"></i> <span>Resolved</span>
                        </button>
                        <button class="filter-tab" onclick="filterIncidents('UNRESOLVED')">
                            <i class="fas fa-times"></i> <span>Unresolved</span>
                        </button>
                    </div>
                    <hr class="header-line">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>SUBJECT</th>
                                    <th>DESCRIPTION</th>
                                    <th>EVIDENCE</th>
                                    <th>STATUS</th>
                                    <th>MEETING</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="incidentTableBody">
                                <?php if (empty($incidentsData)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">No incidents found for this user.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($incidentsData as $incident): ?>
                                        <tr class="incident-row" data-status="<?php echo strtolower(htmlspecialchars($incident['status'])); ?>">
                                            <td data-label="SUBJECT"><?php echo htmlspecialchars($incident['subject_report']); ?></td>
                                            <td data-label="DESCRIPTION">
                                                <button class="read-btn" onclick="openDescriptionModal(<?php echo htmlspecialchars(json_encode($incident['description'])); ?>)">Read</button>
                                            </td>
                                            <td data-label="EVIDENCE">
                                                <?php if (!empty($incident['evidence'])): ?>
                                                    <button class="view-image-btn" onclick="openImageModal(<?php echo htmlspecialchars(json_encode(explode(',', $incident['evidence']))); ?>)" style="background-color: #4F46E5; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer; transition: background 0.3s;">
                                                        View Images
                                                    </button>
                                                <?php else: ?>
                                                    <button class="view-image-btn" style="background-color: #aaa; color: #fff; border: none; border-radius: 5px; padding: 8px 12px; cursor: not-allowed; opacity: 0.6;" disabled>
                                                        View Images
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="STATUS">
                                                <span class="status-label"><?php echo htmlspecialchars($incident['status']); ?></span>
                                            </td>
                                            <td data-label="MEETING">
                                                <?php if (!empty($incident['meeting_date'])): ?>
                                                    <div class="meeting-info">
                                                        <div class="meeting-details">
                                                            <span class="meeting-date"><?php echo date('F j, Y', strtotime($incident['meeting_date'])); ?></span>
                                                            <span class="meeting-time"><?php echo date('h:i A', strtotime($incident['meeting_time'])); ?></span>
                                                        </div>
                                                        <div class="meeting-status">
                                                            <span class="status-badge <?php echo strtolower($incident['meeting_status']); ?>">
                                                                <?php echo htmlspecialchars($incident['meeting_status']); ?>
                                                            </span>
                                                        </div>
                                                        <?php if (!empty($incident['admin_name'])): ?>
                                                            <div class="admin-name">
                                                                With: <?php echo htmlspecialchars($incident['admin_name']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="no-meeting">No meeting scheduled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="ACTION" class="actions">
                                                <?php 
                                                $status = strtoupper($incident['status']);
                                                if ($status === 'NEW' || $status === 'UNRESOLVED'): 
                                                ?>
                                                    <button class="action-btn delete" data-id="<?php echo $incident['id']; ?>" title="Delete Report">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn delete disabled" 
                                                            onmouseenter="showTooltip('<?php echo htmlspecialchars($status); ?>')" 
                                                            onmouseleave="hideTooltip()" 
                                                            disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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

  <!-- Modal for displaying the description -->
  <div id="descriptionModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeDescriptionModal()">&times;</span>
        <p id="descriptionText"></p>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                Delete Confirmation
            </h2>
        </div>
        <div class="modal-body">
            <p>
                Are you sure you want to delete this reported issue?
                <span style="color: #ff4747; display: block;">
                    This action cannot be undone.
                </span>
            </p>
        </div>
        <div class="modal-footer">
            <button id="cancelDelete" class="modal-btn cancel-btn">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button id="confirmDelete" class="modal-btn delete-btn">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
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

  <!-- Modal for displaying images -->
  <div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeImageModal()">&times;</span>
        <div id="modalImageContainer">
            <img id="modalImage" src="" alt="Evidence Image" style="max-width: 100%; height: auto; cursor: pointer;" onclick="openFullImage()">
        </div>
        <div class="modal-footer">
            <button id="prevImage" class="modal-btn" onclick="changeImage(-1)">Previous</button>
            <button id="nextImage" class="modal-btn" onclick="changeImage(1)">Next</button>
        </div>
    </div>
  </div>

  <!-- Add this modal for video playback -->
  <div id="videoModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeVideoModal()">&times;</span>
        <video id="modalVideo" width="100%" controls>
            <source id="videoSource" src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>
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
    function redirectToReportStatus() {
      window.location.href = 'reportStatus.php'; // Adjust the URL as needed
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
    function toggleDropdown() {
      const dropdown = document.getElementById('profileDropdown');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
    function openDescriptionModal(description) {
        document.getElementById('descriptionText').innerText = description;
        document.getElementById('descriptionModal').style.display = 'block';
    }

    function closeDescriptionModal() {
        document.getElementById('descriptionModal').style.display = 'none';
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

    function filterIncidents(status) {
        const rows = document.querySelectorAll('#incidentTableBody tr');
        rows.forEach(row => {
            if (status === 'all' || row.getAttribute('data-status') === status.toLowerCase()) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Update active tab
        const tabs = document.querySelectorAll('.filter-tab');
        tabs.forEach(tab => {
            tab.classList.remove('active');
            if (tab.textContent.trim().toLowerCase() === status.toLowerCase() || (status === 'all' && tab.textContent.trim().toLowerCase() === 'all')) {
                tab.classList.add('active');
            }
        });
    }

    // Add these new functions
    let currentDeleteId = null;
    const deleteModal = document.getElementById('deleteModal');

    // Function to open delete modal
    function openDeleteModal(id) {
        currentDeleteId = id; // Store the ID of the incident to be deleted
        deleteModal.style.display = 'block';
    }

    // Add event listeners when the document loads
    document.addEventListener('DOMContentLoaded', function() {
        // Set up delete button listeners
        document.querySelectorAll('.action-btn.delete:not(.disabled)').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                openDeleteModal(id);
            });
        });

        // Set up cancel delete button
        document.getElementById('cancelDelete').addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });

        // Set up confirm delete button
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (currentDeleteId) {
                const formData = new FormData();
                formData.append('id', currentDeleteId);
                
                fetch('deleteIncident.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Refresh the page after successful deletion
                    } else {
                        alert('Error deleting incident: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting incident. Please try again.');
                });
                
                deleteModal.style.display = 'none';
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        };
    });

    // Show tooltip
    function showTooltip(status) {
        const tooltip = document.getElementById('statusTooltip');
        const statusSpan = document.getElementById('tooltipStatus');
        statusSpan.textContent = status;
        tooltip.style.display = 'block';
    }

    // Hide tooltip
    function hideTooltip() {
        const tooltip = document.getElementById('statusTooltip');
        tooltip.style.display = 'none';
    }

    let currentImageIndex = 0; // To track the current image index
    let imageFiles = []; // Array to hold image file paths

    function openImageModal(images) {
        imageFiles = images; // Store the image paths
        currentImageIndex = 0; // Reset to the first image
        showImage(currentImageIndex); // Show the first image
        document.getElementById('imageModal').style.display = 'block'; // Open the modal
        console.log(imageFiles); // Log the image files to verify
        document.body.classList.add('blurred-background'); // Add class to blur background
    }

    function closeImageModal() {
        document.getElementById('imageModal').style.display = 'none'; // Close the modal
        document.body.classList.remove('blurred-background'); // Remove class to restore background
    }

    function showImage(index) {
        const modalImage = document.getElementById('modalImage');
        modalImage.src = imageFiles[index]; // Set the image source
    }

    function changeImage(direction) {
        currentImageIndex += direction; // Change the index based on direction
        if (currentImageIndex < 0) {
            currentImageIndex = imageFiles.length - 1; // Loop to the last image
        } else if (currentImageIndex >= imageFiles.length) {
            currentImageIndex = 0; // Loop to the first image
        }
        showImage(currentImageIndex); // Show the new image
    }

    function openFullImage() {
        const modalImage = document.getElementById('modalImage');
        const fullImageModal = document.createElement('div'); // Create a new div for the full image modal
        fullImageModal.style.position = 'fixed';
        fullImageModal.style.top = '0';
        fullImageModal.style.left = '0';
        fullImageModal.style.width = '100%';
        fullImageModal.style.height = '100%';
        fullImageModal.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
        fullImageModal.style.display = 'flex';
        fullImageModal.style.alignItems = 'center';
        fullImageModal.style.justifyContent = 'center';
        fullImageModal.style.zIndex = '1001'; // Ensure it is above other content

        const fullImage = document.createElement('img');
        fullImage.src = modalImage.src; // Set the source to the current image
        fullImage.style.maxWidth = '90%'; // Set max width for the full image
        fullImage.style.maxHeight = '90%'; // Set max height for the full image
        fullImage.style.cursor = 'pointer'; // Change cursor to pointer

        fullImageModal.appendChild(fullImage); // Add the full image to the modal
        document.body.appendChild(fullImageModal); // Add the modal to the body

        // Close the full image modal when clicked
        fullImageModal.onclick = function() {
            document.body.removeChild(fullImageModal); // Remove the modal from the body
        };
    }

    function openVideoModal(videoFile) {
        const videoSource = document.getElementById('videoSource');
        videoSource.src = '../video_evidence/' + videoFile; // Set the video source
        document.getElementById('modalVideo').load(); // Load the new video
        document.getElementById('videoModal').style.display = 'block'; // Show the modal
    }

    function closeVideoModal() {
        document.getElementById('videoModal').style.display = 'none'; // Hide the modal
        const video = document.getElementById('modalVideo');
        video.pause(); // Pause the video when closing the modal
        video.currentTime = 0; // Reset the video to the start
    }
  </script>
</body>
</html>