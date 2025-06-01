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

// Get the user's email from the session
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
            }
            
            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.75);
                z-index: -1;
            }
            /* Sidebar Styles */
            .sidebar {
            width: 249px;
            height: 100vh;
            background: linear-gradient(135deg, #1A1A1A, #2B2B2B); /* Darker gradient */
            position: fixed;
            left: 0;
            top: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            z-index: 1;
        }

            .logo-container {
                background-color: #09243B;
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
                color: #F8B83C;
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
            color: #AEB2B7;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: #2d2d2d;
            color: #F8B83C;
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
                background-color: #363333;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0 20px;
                z-index: 1;
            }

            .time-label {
                color: #C0C0C0; /* Light gray text for a classic robot feel */
                font-family: 'Courier New', Courier, monospace; /* Monospaced font for a retro look */
                font-size: 22px; /* Adjusted font size */
                font-weight: bold; /* Bold text */
                background-color: rgba(0, 0, 0, 0.7); /* Darker semi-transparent background */
                padding: 12px 20px; /* Adjusted padding for better spacing */
                border: 2px solid #A0A0A0; /* Metallic border */
                border-radius: 5px; /* Slightly rounded corners */
                box-shadow: 0 0 10px rgba(192, 192, 192, 0.5); /* Subtle glow effect */
                text-align: center; /* Center text */
                display: inline-block; /* Inline-block for better control */
                transition: transform 0.3s ease, opacity 0.3s ease; /* Smooth transitions */
                animation: none; /* Remove pulse animation for a more static look */
            }

            .profile-container {
                position: relative;
                background: linear-gradient(145deg, #2a2a2a, #3a3a3a);
                width: 220px;
                height: 42px;
                border-radius: 25px;
                display: flex;
                align-items: center;
                padding: 0 15px;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2), 
                            0 2px 4px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }

            .profile-container:hover {
                background: linear-gradient(145deg, #3a3a3a, #4a4a4a);
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25), 
                            0 3px 6px rgba(0, 0, 0, 0.15);
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
                color: #E0E0E0;
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
                background: linear-gradient(145deg, #1a1a1a, #2a2a2a);
                min-width: 220px;
                border-radius: 12px;
                opacity: 0;
                transform: translateY(-10px);
                transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3),
                            0 4px 8px rgba(0, 0, 0, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.05);
                z-index: 1000;
                overflow: hidden;
            }

            .dropdown-content.show-dropdown {
                display: block;
                opacity: 1;
                transform: translateY(0);
            }

            .dropdown-content a {
                color: #D0D0D0;
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
                color: #AEB2B7;
                margin-left: 5px;
                font-size: 12px;
            }

            .profile-container.active .fa-caret-down {
                transform: rotate(180deg);
                color: #F4A261;
            }


            /* Active state */
            .nav-links li.active > a {
                background-color: #007BFF; /* Updated background color to a stylish blue */
                color: #FFFFFF; /* Updated text color to white for better contrast */
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
            background-color: #2d2d2d;
            color: #F8B83C;
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
            background: rgba(30, 30, 30, 0.7); /* Semi-transparent background */
            backdrop-filter: blur(10px); /* Glass blur effect */
            -webkit-backdrop-filter: blur(10px); /* For Safari support */
            border: 1px solid rgba(255, 255, 255, 0.1); /* Subtle border */
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        /* Optional: Add subtle hover effect */
        .settings-table:hover {
            background: rgba(30, 30, 30, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .table-header i {
            color: #F8B83C;
            font-size: 1.2em;
        }

        .table-header h2 {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 1.2em;
            margin: 0;
        }

        .divider {
            height: 1px;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            margin: 15px 0;
        }

        .photo-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .photo-content img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .photo-actions {
            display: flex;
            gap: 10px;
        }

        .account-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column-reverse;
        }

        .input-group input {
            background: rgba(54, 51, 51, 0.5);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            background: rgba(54, 51, 51, 0.7);
            border: 1px solid rgba(248, 184, 60, 0.5);
            outline: none;
        }

        .input-group label {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .btn-choose, .btn-change, .btn-update, .btn-change-password {
            background-color: #09243B; /* Background color */
            color: #F4A261; /* Text color */
            padding: 12px 20px; /* Increased padding for larger buttons */
            font-size: 16px; /* Increased font size for better readability */
            border: 2px solid #F4A261; /* Optional: Add a border to enhance visibility */
            border-radius: 5px; /* Rounded corners */
            transition: background-color 0.3s, transform 0.3s; /* Smooth transition for hover effects */
        }

        .btn-choose:hover, .btn-change:hover, .btn-update:hover, .btn-change-password:hover {
            background-color: #F4A261; /* Change background on hover */
            color: #09243B; /* Change text color on hover */
            transform: scale(1.05); /* Slightly enlarge on hover */
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
            background-color: #555; /* Darker background for disabled state */
            color: #ccc; /* Lighter text color for disabled state */
            cursor: not-allowed; /* Change cursor to indicate disabled state */
            opacity: 0.6; /* Slightly transparent to indicate disabled */
            position: relative; /* Position relative for tooltip */
        }

        button:disabled:hover::after {
            content: attr(title); /* Use the title attribute for the tooltip */
            position: absolute;
            bottom: 100%; /* Position above the button */
            left: 50%;
            transform: translateX(-50%);
            background-color: #333; /* Tooltip background color */
            color: #fff; /* Tooltip text color */
            padding: 5px;
            border-radius: 4px;
            white-space: nowrap; /* Prevent text wrapping */
            z-index: 10; /* Ensure tooltip is above other elements */
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
            background-color: #09243B;
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: .4s;
            border: 2px solid #F4A261;
        }

        .slider:before {
            background-color: #F4A261;
            bottom: 4px;
            content: "";
            height: 22px;
            left: 4px;
            position: absolute;
            transition: .4s;
            width: 22px;
        }

        input:checked + .slider {
            background-color: #F4A261;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
            background-color: #09243B;
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        .theme-icon {
            color: #F4A261;
            margin-right: 10px;
            font-size: 20px;
        }

        /* Add hover effect */
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
                <h1 style="font-size: 20px; color: #F8B83C; margin: 0 5px;">CIT CARE 360</h1>
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
                    <i class="fas fa-moon theme-icon"></i>
                    <label class="theme-switch">
                        <input type="checkbox" id="themeSwitch">
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
                if (savedTheme === 'light') {
                    themeSwitch.checked = true;
                }

                themeSwitch.addEventListener('change', function() {
                    if (this.checked) {
                        localStorage.setItem('theme', 'light');
                        window.location.href = '../lightModeAdmin/settings.php';
                    } else {
                        localStorage.setItem('theme', 'dark');
                        window.location.href = '../darkModeAdmin/settings.php';
                    }
                });

                // Update theme icon based on current theme
                const themeIcon = document.querySelector('.theme-icon');
                if (themeSwitch.checked) {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
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