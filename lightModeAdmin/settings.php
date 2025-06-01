<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit(); // Stop further execution
}

// Include database connection file
include('../connection/connection.php'); // Adjusted path to include the connection file from the parent directory

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the user's username from the session
$userEmail = $_SESSION['user'];

// Fetch the user data from the database
$query = "SELECT name, email, profile_image FROM cit_care.admin_users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Check if user data is found
if (!$userData) {
    // Alert removed
}

// Handle form submission for updating user data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $newName = $_POST['name'];
    $newEmail = $_POST['email'];

    // Check if the new email already exists
    $checkQuery = "SELECT COUNT(*) FROM cit_care.admin_users WHERE email = ? AND email != ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $newEmail, $userEmail);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        echo "<script>alert('Email already exists. Please choose a different email.');</script>";
    } else {
        // Update the user data in the database
        $updateQuery = "UPDATE cit_care.admin_users SET name = ?, email = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sss", $newName, $newEmail, $userEmail);
        $updateStmt->execute();

        // Update the session variable for email
        $_SESSION['user'] = $newEmail; // Update session with new email

        // Optionally, you can add a success message or redirect
        echo "<script>alert('Account updated successfully!');</script>";
        // Refresh the page to reflect changes
        header("Location: settings.php");
        exit();
    }
}

// Handle the image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_photo'])) {
    // Handle the image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = $_FILES['profile_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Specify the allowed file extensions
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Set the new file name and path
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension; // Unique file name
            $uploadFileDir = '../image/';
            $dest_path = $uploadFileDir . $newFileName;

            // Move the file to the upload directory
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // Update the user's profile image in the database
                $updateImageQuery = "UPDATE cit_care.admin_users SET profile_image = ? WHERE email = ?";
                $updateImageStmt = $conn->prepare($updateImageQuery);
                $updateImageStmt->bind_param("ss", $newFileName, $userEmail);
                $updateImageStmt->execute();

                // Optionally, you can add a success message or redirect
                echo "<script>alert('Profile image updated successfully!');</script>";
                // Refresh the page to reflect changes
                header("Location: settings.php");
                exit();
            } else {
                echo "<script>alert('There was an error moving the uploaded file.');</script>";
            }
        } else {
            echo "<script>alert('Upload failed. Allowed file types: " . implode(", ", $allowedfileExtensions) . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>Settings</title>
    <style>
        /* Base Styles */
        @import url('https://fonts.cdnfonts.com/css/century-gothic');
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&display=swap');

        * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            /* Body and Background */
            body {  
                background: url('../image/bg.png') no-repeat center center fixed;
                -webkit-background-size: cover;
                -moz-background-size: cover;
                -o-background-size: cover;
                background-size: cover;
                min-height: 100vh;
                position: relative;
                overflow-x: hidden;
            }
            
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, rgba(255, 228, 196, 0.15) 0%, rgba(245, 245, 245, 0.25) 100%);
                z-index: -1;
                pointer-events: none;
            }
        
            /* Sidebar Styles */
            .sidebar {
            width: 249px;
            height: 100vh;
            background: #F5F5F5;
            position: fixed;
            left: 0;
            top: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            z-index: 1;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

            .logo-container {
                background-color: #E6B8AF;
                padding: 15px;
                display: flex;
                align-items: center;
                margin-bottom: 30px;
                min-height: 80px;
            }

            .logo-container img {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                object-fit: cover;
                background-color: #003366;
            }

            .logo-container h1 {
                color: #FFFFFF;
                font-family: 'Montserrat', sans-serif;
                font-size: 14px;
                font-weight: 700;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 180px;
                line-height: 1.2;
            }


        /* Navigation Styles */
        .nav-links {
            list-style: none;
            padding: 0 15px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            color: #4A4A4A;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: rgba(230, 184, 175, 0.1);
            color: #E6B8AF;
            transform: translateX(5px);
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.05);
        }

        .nav-links i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

            /* Topbar Styles */
            .topbar {
                position: fixed;
                top: 0;
                left: 249px; /* Same as sidebar width */
                right: 0;
                height: 60px;
                background-color: #FFFFFF;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0 20px;
                z-index: 1;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }

            .time-label {
                color: #4A4A4A;
                background-color: #F5F5F5;
                border: 2px solid #E6B8AF;
                font-family: 'Courier New', Courier, monospace;
                font-size: 22px;
                font-weight: bold;
                padding: 12px 20px;
                border-radius: 5px;
                box-shadow: 0 0 15px rgba(230, 184, 175, 0.2);
                text-align: center;
                display: inline-block;
                transition: transform 0.3s ease, opacity 0.3s ease;
                animation: none;
            }

            .profile-container {
                position: relative;
                background: #E6B8AF;
                width: 220px;
                height: 42px;
                border-radius: 25px;
                display: flex;
                align-items: center;
                padding: 0 15px;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(230, 184, 175, 0.2);
            }

            .profile-container:hover {
                background: #D4A5A5;
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            }

            .profile-container:hover::before {
                transform: translateX(100%);
            }

            .profile-container img {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                transition: all 0.3s ease;
            }

            .profile-container:hover img {
                border-color: rgba(255, 255, 255, 0.2);
                transform: scale(1.05);
            }

            .profile-name {
                color: #FFFFFF;
                font-family: 'Century Gothic', sans-serif;
                margin-left: 12px;
                font-size: 14px;
                font-weight: 500;
                flex-grow: 1;
                letter-spacing: 0.3px;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
                transition: color 0.3s ease;
            }

            .profile-container:hover .profile-name {
                color: #FFFFFF;
            }

            .dropdown-content {
                display: none;
                position: absolute;
                top: calc(100% + 8px);
                right: 0;
                background: #F5F5F5;
                min-width: 220px;
                border-radius: 12px;
                opacity: 0;
                transform: translateY(-10px);
                transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3),
                            0 4px 8px rgba(0, 0, 0, 0.2);
                border: 1px solid rgba(230, 184, 175, 0.2);
                z-index: 1000;
                overflow: hidden;
            }

            .dropdown-content.show-dropdown {
                display: block;
                opacity: 1;
                transform: translateY(0);
            }

            .dropdown-content a {
                color: #4A4A4A;
                font-family: 'Century Gothic', sans-serif;
                padding: 14px 18px;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 12px;
                transition: all 0.2s ease;
                position: relative;
                overflow: hidden;
            }

            .dropdown-content a::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                height: 100%;
                width: 3px;
                background: linear-gradient(to bottom, #F4A261, #E76F51);
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .dropdown-content a:hover::before {
                opacity: 1;
            }

            .dropdown-content a:first-child {
                border-radius: 12px 12px 0 0;
            }

            .dropdown-content a:last-child {
                border-radius: 0 0 12px 12px;
            }

            #profileBtn:hover {
                background-color: rgba(76, 175, 80, 0.1);
                color: #4CAF50;
                padding-left: 22px;
            }

            #settingsBtn:hover {
                background-color: rgba(33, 150, 243, 0.1);
                color: #2196F3;
                padding-left: 22px;
            }

            #logoutBtn:hover {
                background-color: rgba(255, 71, 71, 0.1);
                color: #ff4747;
                padding-left: 22px;
            }

            .fa-caret-down {
                transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                color: #FFFFFF;
                margin-left: 5px;
                font-size: 12px;
            }

            .profile-container.active .fa-caret-down {
                transform: rotate(180deg);
                color: #F4A261;
            }


            /* Active state */
            .nav-links li.active > a {
                background-color: #E6B8AF;
                color: #FFFFFF;
            }

        /* Dropdown styles */
        .dropdown .submenu {
            display: none;
            list-style: none;
            padding-left: 30px;
            margin-top: 5px;
        }

        .dropdown.open .submenu {
            display: block;
        }

        .submenu a {
            padding: 10px 15px;
            font-size: 0.9em;
        }

        .dropdown-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .arrow {
            transition: transform 0.3s ease;
        }

        .dropdown.open .arrow {
            transform: rotate(180deg);
        }

        /* Hover effects */
        .nav-links a:hover {
            background-color: rgba(230, 184, 175, 0.1);
            color: #E6B8AF;
            transform: translateX(5px);
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.05);
        }

        .submenu a:hover {
            padding-left: 20px;
        }

        .content-wrapper {
            margin-left: 249px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }

        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .settings-table {
            background: linear-gradient(135deg, #FFFFFF 0%, #F5F5F5 100%);
            border: 1px solid rgba(230, 184, 175, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .settings-table:hover {
            background: linear-gradient(135deg, #F5F5F5 0%, #FFFFFF 100%);
            border: 1px solid rgba(230, 184, 175, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(230, 184, 175, 0.15);
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(230, 184, 175, 0.1);
        }

        .table-header i {
            color: #E6B8AF;
            font-size: 1.4em;
            background: rgba(230, 184, 175, 0.1);
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .table-header:hover i {
            background: rgba(230, 184, 175, 0.2);
            transform: scale(1.1);
        }

        .table-header h2 {
            color: #4A4A4A;
            font-family: 'Century Gothic', sans-serif;
            font-size: 1.3em;
            font-weight: 600;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .divider {
            height: 2px;
            background: linear-gradient(
                90deg,
                rgba(230, 184, 175, 0) 0%,
                rgba(230, 184, 175, 0.3) 50%,
                rgba(230, 184, 175, 0) 100%
            );
            margin: 20px 0;
        }

        .photo-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }

        .photo-content img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid #FFFFFF;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .photo-content img:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(230, 184, 175, 0.2);
        }

        .photo-actions {
            display: flex;
            gap: 12px;
        }

        .account-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .input-group {
            display: flex;
            flex-direction: column-reverse;
            position: relative;
        }

        .input-group input {
            background: #FFFFFF;
            border: 2px solid rgba(230, 184, 175, 0.2);
            color: #4A4A4A;
            padding: 12px 15px;
            border-radius: 8px;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
            font-size: 1em;
        }

        .input-group input:focus {
            background: #FFFFFF;
            border: 2px solid #E6B8AF;
            box-shadow: 0 0 0 3px rgba(230, 184, 175, 0.1);
            outline: none;
        }

        .input-group label {
            color: #4A4A4A;
            font-family: 'Century Gothic', sans-serif;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .input-group input:focus + label {
            color: #D4A5A5;
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            gap: 15px;
        }

        .btn-choose, .btn-change, .btn-update, .btn-change-password {
            background: linear-gradient(135deg, #E6B8AF 0%, #D4A5A5 100%);
            color: #FFFFFF;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95em;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(230, 184, 175, 0.2);
        }

        .btn-choose:hover, .btn-change:hover, .btn-update:hover, .btn-change-password:hover {
            background: linear-gradient(135deg, #D4A5A5 0%, #C49292 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 184, 175, 0.3);
        }

        .btn-choose:active, .btn-change:active, .btn-update:active, .btn-change-password:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(230, 184, 175, 0.2);
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-family: 'Century Gothic', sans-serif;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%; /* Ensure the input takes full width */
            padding-right: 40px; /* Add padding to the right for the button */
        }

        .edit-btn {
            position: absolute;
            right: 10px; /* Adjust as needed */
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #AEB2B7; /* Adjust color as needed */
            z-index: 1; /* Ensure the button is above the input */
            transition: color 0.3s ease; /* Only transition color */
        }

        .edit-btn:hover {
            color: #F8B83C; /* Change color on hover */
            /* Ensure no other properties change on hover */
        }

        /* Add this to your existing CSS */
        button:disabled {
            background-color: #BBDEFB;
            color: #4A4A4A;
            cursor: not-allowed;
            opacity: 0.6;
            position: relative;
        }

        button:disabled:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #E6B8AF;
            color: #FFFFFF;
            padding: 5px;
            border-radius: 4px;
            white-space: nowrap;
            z-index: 10;
        }

        /* Theme Switch Styles */
        .theme-switch-wrapper {
            display: flex;
            align-items: center;
            margin-right: 20px;
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
            background-color: #E6B8AF;
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: .4s;
            border: 2px solid #FFFFFF;
        }

        .slider:before {
            background-color: #FFFFFF;
            bottom: 4px;
            content: "";
            height: 22px;
            left: 4px;
            position: absolute;
            transition: .4s;
            width: 22px;
        }

        input:checked + .slider {
            background-color: #D4A5A5;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
            background-color: #FFFFFF;
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        .theme-icon {
            color: #E6B8AF;
            margin-right: 10px;
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .theme-switch:hover .theme-icon {
            color: #D4A5A5;
        }

        .theme-switch:hover .slider {
            box-shadow: 0 0 10px rgba(244, 162, 97, 0.5);
        }

        .theme-switch:hover .slider:before {
            transform: scale(1.1);
        }

        input:checked + .slider:hover .slider:before {
            transform: translateX(26px) scale(1.1);
        }
    </style>

</head>
<body>
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <!-- Logo Section -->
            <div class="logo-container" style="text-align: center;">
                <img src="../image/logo.png" alt="Logo" style="width: 50px; height: 50px; margin-right: 10px;">
                <h1 style="font-size: 20px; color: #FFFFFF; margin: 0 5px;">CIT CARE 360</h1>
            </div>

            <!-- Navigation Links -->
            <ul class="nav-links">
                <!-- Dashboard Link -->
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                </li>
                <!-- User Management Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-users"></i>User Management
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="manageUsers.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                        <li><a href="managePasswordRequests.php"><i class="fas fa-key"></i>Password Requests</a></li>
                    </ul>
                </li>
                
                <!-- Incidents Report Link (Updated) -->
                <li>
                    <a href="incidentsReport.php">
                        <i class="fas fa-file-alt"></i>Incidents Report
                    </a>
                </li>
                                
                <!-- Meeting Schedules Link -->
                <li>
                    <a href="meetingSchedules.php">
                        <i class="fas fa-calendar-alt"></i>Meeting Schedules
                    </a>
                </li>

                <!-- New Block Time Management Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-clock"></i>Block Time Management
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="blockTime.php"><i class="fas fa-plus-circle"></i>Block Time</a></li>
                        <li><a href="viewBlockedTimes.php"><i class="fas fa-eye"></i>View Blocked Times</a></li>
                    </ul>
                </li>

                <li>
                    <a href="departments.php">
                        <i class="fas fa-building"></i>Departments
                    </a>
                </li>
                <!-- Students Dropdown (New) -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-graduate"></i>Students
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="student.php"><i class="fas fa-user-graduate"></i>Student List</a></li>
                        <li><a href="uploadStudents.php"><i class="fas fa-upload"></i>Upload Students</a></li>
                        <li><a href="existingStudents.php"><i class="fas fa-list"></i>Existing CIT Students</a></li>
                    </ul>
                </li>
                <!-- FAQ Link (New) -->
                <li>
                    <a href="faq.php">
                        <i class="fas fa-question-circle"></i>FAQ
                    </a>
                </li>
                                
            </ul>
        </div>

        <!-- Top Navigation Bar -->
        <div class="topbar">
            <div class="time-label" id="currentDateTime"></div>
            <div style="display: flex; align-items: center;">
                <div class="theme-switch-wrapper">
                    <i class="fas fa-sun theme-icon"></i>
                    <label class="theme-switch">
                        <input type="checkbox" id="themeSwitch" checked>
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="profile-container">
                    <img src="<?php 
                        $imagePath = isset($userData['profile_image']) && !empty($userData['profile_image']) ? '../image/' . htmlspecialchars($userData['profile_image']) : ''; 
                        echo $imagePath; 
                    ?>" alt="Current Photo" id="currentPhoto">
                    <span class="profile-name"><?php echo htmlspecialchars($userData['name'] ?? ''); ?></span>
                    <i class="fas fa-caret-down"></i>
                    <div class="dropdown-content">
                        <a href="profile.php" id="profileBtn"><i class="fas fa-user"></i>Profile</a>
                        <a href="settings.php" id="settingsBtn"><i class="fas fa-cog"></i>Settings</a>
                        <a id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
                </div>
            </div>
        </div>
    
            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <div class="settings-container">
                    <!-- Photo Change Table -->
                    <div class="settings-table">
                        <div class="table-header">
                            <i class="fas fa-camera"></i>
                            <h2>CHANGE MY PHOTO</h2>
                        </div>
                        <div class="divider"></div>
                        <div class="photo-content">
                            <img src="<?php echo isset($userData['profile_image']) && !empty($userData['profile_image']) ? htmlspecialchars('../image/' . $userData['profile_image']) : ''; ?>" alt="Current Photo" id="currentPhoto">
                            <form method="POST" enctype="multipart/form-data" action="">
                                <div class="photo-actions">
                                    <input type="file" name="profile_image" id="photoInput" accept="image/*" style="display: none;" onchange="previewImage(event); toggleChangeButton();">
                                    <button type="button" class="btn-choose" onclick="document.getElementById('photoInput').click()">
                                        Choose Image
                                    </button>
                                    <button type="submit" name="update_photo" class="btn-change" id="changeButton" disabled title="Choose image first">
                                        Change
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Account Edit Table -->
                    <div class="settings-table">
                        <div class="table-header">
                            <i class="fas fa-user-edit"></i>
                            <h2>EDIT MY ACCOUNT</h2>
                        </div>
                        <div class="divider"></div>
                        <form method="POST" action="">
                            <div class="account-content">
                                <div class="input-group">
                                    <div class="input-wrapper">
                                        <input type="text" name="name" value="<?php echo isset($userData['name']) ? htmlspecialchars($userData['name']) : ''; ?>" required id="nameInput">
                                    </div>
                                    <label>Name</label>
                                </div>
                                <div class="input-group">
                                    <div class="input-wrapper">
                                        <input type="email" name="email" value="<?php echo isset($userData['email']) ? htmlspecialchars($userData['email']) : ''; ?>" required id="emailInput">
                                    </div>
                                    <label>Email</label>
                                </div>
                                <div class="button-group">
                                    <button type="submit" name="update_account" class="btn-update" id="updateButton" disabled title="Change something">
                                        Update
                                    </button>
                                    <button type="button" class="btn-change-password" onclick="window.location.href='changePassword.php'">Change Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add datetime update function
                function updateDateTime() {
                    const now = new Date();
                    const options = { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit', 
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true 
                    };
                    const formattedDateTime = now.toLocaleDateString('en-US', options);
                    document.getElementById('currentDateTime').textContent = formattedDateTime;
                }

                // Update immediately and then every second
                updateDateTime();
                setInterval(updateDateTime, 1000);

                // Initialize Variables
                const profileContainer = document.querySelector('.profile-container');
                const dropdownContent = document.querySelector('.dropdown-content');
                const profileBtn = document.getElementById('profileBtn');
                const settingsBtn = document.getElementById('settingsBtn');
                const logoutBtn = document.getElementById('logoutBtn');
                let isDropdownOpen = false;

                // Dropdown Functions
                function openDropdown() {
                    dropdownContent.classList.add('show-dropdown');
                    profileContainer.classList.add('active');
                    isDropdownOpen = true;
                }

                function closeDropdown() {
                    dropdownContent.classList.remove('show-dropdown');
                    profileContainer.classList.remove('active');
                    isDropdownOpen = false;
                }

                // Event Listeners
                profileContainer.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (isDropdownOpen) {
                        closeDropdown();
                    } else {
                        openDropdown();
                    }
                });

                document.addEventListener('click', function(e) {
                    if (isDropdownOpen && !dropdownContent.contains(e.target)) {
                        closeDropdown();
                    }
                });

                // Navigation Handlers
                profileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'profile.php';
                });

                settingsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'settings.php';
                });

                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const confirmLogout = confirm('Are you sure you want to logout?');
                    
                    if (confirmLogout) {
                        window.location.href = '../adminPortal/logout.php';
                    }
                });

                // Keyboard Accessibility
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && isDropdownOpen) {
                        closeDropdown();
                    }
                });

                // Sidebar Dropdowns
                const dropdowns = document.querySelectorAll('.dropdown');
                dropdowns.forEach(dropdown => {
                    const toggleBtn = dropdown.querySelector('.dropdown-toggle');
                    
                    toggleBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        
                        // Close other dropdowns
                        dropdowns.forEach(other => {
                            if (other !== dropdown && other.classList.contains('open')) {
                                other.classList.remove('open');
                            }
                        });
                        
                        // Toggle current dropdown
                        dropdown.classList.toggle('open');
                    });
                });

                // Change Password Button Handler
                const changePasswordBtn = document.querySelector('.btn-change-password');
                changePasswordBtn.addEventListener('click', function() {
                    window.location.href = 'changePassword.php';
                });

                // Preview Image Function
                window.previewImage = function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.querySelector('.photo-content img').src = e.target.result; // Update the settings-table current photo
                        };
                        reader.readAsDataURL(file);
                    }
                    toggleChangeButton(); // Ensure this is called to enable the Change button
                };

                function toggleChangeButton() {
                    const fileInput = document.getElementById('photoInput');
                    const changeButton = document.getElementById('changeButton');
                    console.log('File input length:', fileInput.files.length); // Debugging line
                    changeButton.disabled = !fileInput.files.length; // Enable if a file is selected
                }

                function toggleUpdateButton() {
                    const nameInput = document.getElementById('nameInput');
                    const emailInput = document.getElementById('emailInput');
                    const updateButton = document.getElementById('updateButton');

                    // Check if there are changes in name or email
                    const hasChanges = nameInput.value !== "<?php echo isset($userData['name']) ? htmlspecialchars($userData['name']) : ''; ?>" || 
                                       emailInput.value !== "<?php echo isset($userData['email']) ? htmlspecialchars($userData['email']) : ''; ?>";

                    updateButton.disabled = !hasChanges; // Enable if there are changes
                }

                // Add event listeners to input fields to check for changes
                document.getElementById('nameInput').addEventListener('input', toggleUpdateButton);
                document.getElementById('emailInput').addEventListener('input', toggleUpdateButton);

                // Add theme switch functionality
                const themeSwitch = document.getElementById('themeSwitch');
                
                // Check if user has a saved preference
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme === 'dark') {
                    themeSwitch.checked = false;
                }

                themeSwitch.addEventListener('change', function() {
                    if (this.checked) {
                        localStorage.setItem('theme', 'light');
                        window.location.href = '../darkModeAdmin/settings.php';
                    } else {
                        localStorage.setItem('theme', 'dark');
                        window.location.href = '../lightModeAdmin/settings.php';
                    }
                });

                // Update theme icon based on current theme
                const themeIcon = document.querySelector('.theme-icon');
                if (!themeSwitch.checked) {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                }

                themeSwitch.addEventListener('change', function() {
                    if (this.checked) {
                        themeIcon.classList.remove('fa-moon');
                        themeIcon.classList.add('fa-sun');
                    } else {
                        themeIcon.classList.remove('fa-sun');
                        themeIcon.classList.add('fa-moon');
                    }
                });
            });
        </script>
</body>
</html>