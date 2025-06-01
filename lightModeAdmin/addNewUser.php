<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit(); // Stop further execution
}

// Include database connection file
include('../connection/connection.php'); // Adjusted path to include the connection file from the parent directory

// Fetch data from user_roles table
$query = "SELECT * FROM user_roles";
$result = $conn->query($query);
$userRoles = [];
if ($result && $result->num_rows > 0) { // Check if result is valid
    while ($row = $result->fetch_assoc()) {
        $userRoles[] = $row;
    }
} else {
    error_log('No roles found or query error: ' . $conn->error);
}

// Fetch user details from the admin_users table
$userId = $_SESSION['user_id']; // Assuming user_id is stored in the session
$query = "SELECT profile_image, name FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$profileImage = '../image/' . $user['profile_image']; // Ensure the profile image path is correct
$userName = $user['name'];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = password_hash($_POST['new_password'] ?? '', PASSWORD_DEFAULT); // Hash the password
    $userRole = 'admin'; // Set user role to admin by default
    $profileImage = $_FILES['profileImage']['name'] ?? '';

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['type' => 'error', 'message' => 'Invalid email format.']);
        exit;
    }

    // Check if email already exists
    $checkEmailQuery = "SELECT id FROM admin_users WHERE email = ?";
    $checkStmt = $conn->prepare($checkEmailQuery);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['type' => 'error', 'message' => 'Email already exists.']);
        exit;
    }
    $checkStmt->close();

    // Move uploaded file to the desired directory
    if (!empty($profileImage)) {
        if ($_FILES['profileImage']['error'] !== UPLOAD_ERR_OK) {
            error_log('File upload error: ' . $_FILES['profileImage']['error']);
            echo json_encode(['type' => 'error', 'message' => 'File upload error.']);
            exit;
        }
        
        // Attempt to move the uploaded file to the correct directory
        $uploadPath = "../image/" . basename($profileImage);
        if (!move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadPath)) {
            error_log('Failed to move uploaded file to ' . $uploadPath);
            echo json_encode(['type' => 'error', 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    }

    // Validate user inputs
    if (empty($name) || empty($email) || empty($password) || empty($userRole)) {
        echo json_encode(['type' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Log the values being inserted for debugging
    error_log("Inserting user: Name: $name, Email: $email, User Role: $userRole, Profile Image: $profileImage");

    // Insert data into the database
    $query = "INSERT INTO admin_users (name, email, password, user_role, profile_image) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        error_log('MySQL prepare error: ' . $conn->error);
        echo json_encode(['type' => 'error', 'message' => 'Database prepare error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("sssss", $name, $email, $password, $userRole, $profileImage);
    $stmt->execute();

    if ($stmt->error) {
        error_log('MySQL execute error: ' . $stmt->error);
        echo json_encode(['type' => 'error', 'message' => 'Failed to add user: ' . $stmt->error]);
    } else if ($stmt->affected_rows > 0) {
        // Store user data in session
        $_SESSION['user'] = [
            'id' => $stmt->insert_id,
            'name' => $name,
            'email' => $email,
            'user_role' => $userRole,
            'profile_image' => $profileImage
        ];
        
        echo json_encode(['type' => 'success', 'message' => 'User added successfully!']);
    } else {
        error_log('No rows affected during user insertion.');
        echo json_encode(['type' => 'error', 'message' => 'Failed to add user. No rows affected.']);
    }

    $stmt->close();
    exit; // Ensure no further output is sent
}

// Close the database connection
$conn->close();

// If not a POST request, you can handle it as needed (e.g., show the form)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>Add New User</title>
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

        .main-content {
            margin-left: 249px;
            padding: 80px 20px 20px;
            font-family: 'Century Gothic', sans-serif;
        }

        /* Updated Form Container Background */
        .content-wrapper {
            background: rgba(255, 248, 240, 0.95); /* Soft cream color with slight transparency */
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .content-header {
            margin-bottom: 30px;
        }

        .content-header h2 {
            color: #5D4037; /* Soft brown color */
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            margin: 0 auto;
        }

        .header-line {
            height: 2px;
            background: #A1887F; /* Lighter brown color */
            border-bottom: 1px solid #A1887F;
        }

        .form-container {
            max-width: 500px;
            margin: 30px auto 0;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8D6E63; /* Medium brown for icons */
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 40px;
            padding-right: 45px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #D7CCC8; /* Light brown border */
            border-radius: 5px;
            color: #5D4037; /* Soft brown text */
            font-family: 'Century Gothic', sans-serif;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #8D6E63; /* Medium brown for the eye icon */
            cursor: pointer;
            padding: 5px;
            z-index: 1;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Updated Button Styles */
        .add-user-btn {
            width: 100%;
            padding: 12px;
            background: #8D6E63; /* Soft brown button */
            color: #FFFFFF;
            border: none;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .add-user-btn:hover {
            background: #6D4C41; /* Darker brown on hover */
            transform: scale(1.02);
        }

        /* Updated Error and Success Message Styles */
        .message {
            padding: 15px; /* Increased padding */
            border-radius: 8px; /* More rounded corners */
            margin-top: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            font-family: 'Century Gothic', sans-serif;
            font-weight: bold; /* Bold text for emphasis */
        }

        .message.error {
            background: rgba(255, 205, 210, 0.3); /* Soft red background */
            color: #C62828; /* Darker red text */
        }

        .message.success {
            background: rgba(200, 230, 201, 0.3); /* Soft green background */
            color: #2E7D32; /* Darker green text */
        }

        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 45px;
        }

        .form-group.select-group {
            position: relative;
        }

        .form-group.select-group .select-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8D6E63;
            pointer-events: none;
            z-index: 1;
        }

        .header-top {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #8D6E63; /* Medium brown text */
            text-decoration: none;
            font-family: 'Century Gothic', sans-serif;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            transition: all 0.3s ease;
            position: absolute;
            left: 0;
            border: 1px solid #D7CCC8;
        }

        .back-btn:hover {
            background: #8D6E63;
            color: #FFFFFF;
        }

        .image-upload-group {
            text-align: center;
            margin-bottom: 30px;
        }

        .image-preview {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #8D6E63;
            position: relative;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #5D4037;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            background: rgba(255, 255, 255, 0.8);
            padding: 5px;
            border-radius: 5px;
        }

        .hidden-input {
            display: none;
        }

        .upload-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.9);
            color: #8D6E63;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #D7CCC8;
        }

        .upload-label:hover {
            background: #8D6E63;
            color: #FFFFFF;
        }

        .image-upload-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .upload-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #403E3E;
            color: #AEB2B7;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-label:hover {
            background: #09243B;
            color: #F8B83C;
        }

        .camera-label {
            background: #8D6E63;
            color: #FFFFFF;
        }

        .camera-label:hover {
            background: #6D4C41;
        }

        #videoFeed {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        #videoFeed video {
            max-width: 100%;
            max-height: 80vh;
            margin-bottom: 20px;
        }

        .camera-controls {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .camera-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .capture-btn {
            background: #09243B;
            color: #F8B83C;
        }

        .cancel-btn {
            background: #403E3E;
            color: #AEB2B7;
        }

        .camera-btn:hover {
            opacity: 0.9;
        }

        /* Update form group focus states */
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #8D6E63;
            box-shadow: 0 0 0 2px rgba(141, 110, 99, 0.2);
        }

        /* Update placeholder color */
        .form-group input::placeholder,
        .form-group select::placeholder {
            color: #A1887F;
        }

        /* Update select dropdown arrow */
        .form-group.select-group .select-icon {
            color: #8D6E63;
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
            </div>>

            <!-- Navigation Links -->
            <ul class="nav-links">
                <!-- Dashboard Link -->
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                </li>
                <!-- User Management Dropdown -->
                <li class="dropdown open">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-users"></i>User Management
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="active"><a href="manageUsers.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
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
                <div class="profile-container">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Picture">
                    <span class="profile-name"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-caret-down"></i>
                    <div class="dropdown-content">
                        <a href="profile.php" id="profileBtn"><i class="fas fa-user"></i>Profile</a>
                        <a href="settings.php" id="settingsBtn"><i class="fas fa-cog"></i>Settings</a>
                        <a id="logoutBtn"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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

                // Add New User Form Handling
                const form = document.getElementById('addUserForm');
                const errorMessage = document.getElementById('errorMessage');
                const successMessage = document.getElementById('successMessage');
                const togglePassword = document.querySelector('.toggle-password');
                const passwordInput = document.getElementById('password');
                const profileImage = document.getElementById('profileImage');

                // Toggle password visibility
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });

                // Form submission
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent the default form submission
                    console.log('Form submitted'); // Log when the form is submitted

                    // Check if a photo is uploaded
                    const photoFile = profileImage.files[0];
                    if (!photoFile) {
                        errorMessage.textContent = 'Please upload a photo.';
                        errorMessage.style.display = 'flex';
                        return; // Stop form submission if no photo is uploaded
                    }

                    // Validate email format
                    const emailInput = document.getElementById('email');
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(emailInput.value)) {
                        errorMessage.textContent = 'Please enter a valid email address.';
                        errorMessage.style.display = 'flex';
                        return;
                    }

                    // Create FormData object
                    const formData = new FormData(this);

                    // Send AJAX request
                    fetch('addNewUser.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Response:', response); // Log the raw response
                        return response.json();
                    })
                    .then(data => {
                        console.log('Data:', data); // Log the parsed data
                        if (data.type === 'success') {
                            successMessage.textContent = data.message;
                            successMessage.style.display = 'flex';
                            form.reset(); // Reset the form after successful submission
                            window.location.href = 'manageUsers.php'; // Navigate to manageUsers.php instantly
                        } else {
                            errorMessage.textContent = data.message; // Set error message
                            errorMessage.style.display = 'flex';
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error); // Log the error
                        errorMessage.textContent = 'An error occurred. Please try again.'; // General error message
                        errorMessage.style.display = 'flex';
                    });
                });

                // Update time function with seconds
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
                    document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
                }

                // Update immediately and then every second
                updateDateTime();
                setInterval(updateDateTime, 1000);

                const cameraButton = document.getElementById('cameraButton');
                const imagePreview = document.getElementById('imagePreview');
                const videoFeed = document.getElementById('videoFeed');
                const video = document.getElementById('video');
                const canvas = document.getElementById('canvas');
                const captureBtn = document.getElementById('captureBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                const capturedImage = document.getElementById('capturedImage');
                
                let stream = null;

                // Handle file selection
                profileImage.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if(file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            document.querySelector('.image-overlay').style.display = 'none'; // Remove overlay when a photo is selected
                            imagePreview.style.filter = 'none'; // Remove blur effect
                        }
                        reader.readAsDataURL(file);
                    }
                });

                // Camera functionality
                async function startCamera() {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ 
                            video: { 
                                facingMode: 'user',
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            }, 
                            audio: false 
                        });
                        video.srcObject = stream;
                        videoFeed.style.display = 'flex';
                    } catch (err) {
                        console.error('Error accessing camera:', err);
                        alert('Unable to access camera. Please make sure you have granted camera permissions.');
                    }
                }

                function stopCamera() {
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                        video.srcObject = null;
                        videoFeed.style.display = 'none';
                    }
                }

                // Camera button click handler
                cameraButton.addEventListener('click', startCamera);

                // Capture button click handler
                captureBtn.addEventListener('click', () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    
                    // Convert canvas to blob
                    canvas.toBlob((blob) => {
                        // Create a unique filename using the current timestamp
                        const timestamp = Date.now();
                        const fileName = `captured-image-${timestamp}.jpg`; // Unique filename
                        
                        // Create a File object from the blob
                        const file = new File([blob], fileName, { type: "image/jpeg" });
                        
                        // Create a FileList-like object
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        
                        // Assign the FileList to profileImage input
                        profileImage.files = dataTransfer.files;
                        
                        // Update preview
                        imagePreview.src = canvas.toDataURL('image/jpeg');
                        
                        // Stop camera and hide video feed
                        stopCamera();
                    }, 'image/jpeg', 0.8);
                });

                // Cancel button click handler
                cancelBtn.addEventListener('click', stopCamera);

                // Check if device has camera capability
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    cameraButton.style.display = 'none';
                }
            });
        </script>

    <!-- Add New User Form Section -->
    <div class="main-content">

                <div class="content-wrapper">
            <!-- Header -->
            <div class="content-header">
                <div class="header-top">
                    <a href="manageUsers.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <h2><i class="fas fa-user-plus"></i> ADD NEW USER</h2>
                </div>
                <div class="header-line"></div>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <form id="addUserForm" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" placeholder="Full Name" required>
                    </div>

                    <div class="form-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Email Address" required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="new_password" placeholder="Password" required autocomplete="new-password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="form-group image-upload-group">
                        <div class="image-preview">
                            <img id="imagePreview" src="../image/logo.png" alt="Profile Preview" style="filter: blur(2px);">
                            <div class="image-overlay">Add a Photo</div>
                        </div>
                        <div class="image-upload-buttons">
                            <input type="file" id="profileImage" name="profileImage" accept="image/*" class="hidden-input">
                            <label for="profileImage" class="upload-label">
                                <i class="fas fa-image"></i>
                                Choose Image
                            </label>
                            
                            <button type="button" id="cameraButton" class="upload-label camera-label">
                                <i class="fas fa-camera"></i>
                                Take Photo
                            </button>
                            
                            <canvas id="canvas" style="display:none;"></canvas>
                            <input type="hidden" id="capturedImage" name="profileImage">
                        </div>
                    </div>

                    <button type="submit" class="add-user-btn">
                        <i class="fas fa-plus-circle"></i> Add User
                    </button>
                </form>

                <!-- Messages -->
                <div class="message error" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    Please fill in all required fields
                </div>
                <div class="message success" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    User added successfully!
                </div>
            </div>
        </div>
    </div>
    <div id="videoFeed">
        <video id="video" autoplay playsinline></video>
        <div class="camera-controls">
            <button type="button" class="camera-btn capture-btn" id="captureBtn">
                <i class="fas fa-camera"></i> Capture
            </button>
            <button type="button" class="camera-btn cancel-btn" id="cancelBtn">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</body>
</html>
