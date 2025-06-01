<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit(); // Stop further execution
}

// Include database connection file
include('../connection/connection.php'); // Adjusted path to include the connection file from the parent directory

// Fetch user details from the admin_users table
$userId = $_SESSION['user_id']; // Assuming user_id is stored in the session
$query = "SELECT profile_image, name FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$profileImage = '../image/' . $user['profile_image']; // Ensure the image path is correct
$userName = $user['name'];

// Fetch all departments from the database
$query = "SELECT * FROM departments"; // SQL query to select all departments
$result = mysqli_query($conn, $query); // Execute the query

// Check for errors
if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}

// Prepare an array to hold the departments
$departments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row; // Store each department in the array
}

// Check if the form is submitted to add a new department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['departmentName'])) {
    $departmentName = mysqli_real_escape_string($conn, $_POST['departmentName']); // Sanitize input

    // Insert the new department into the database
    $insertQuery = "INSERT INTO departments (name) VALUES ('$departmentName')";
    if (mysqli_query($conn, $insertQuery)) {
        // Optionally, you can redirect or display a success message
        echo "<script>alert('Department added successfully!');</script>";
    } else {
        die("Error adding department: " . mysqli_error($conn));
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $departmentId = mysqli_real_escape_string($conn, $_POST['departmentId']);
    $deleteQuery = "DELETE FROM departments WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $departmentId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting department: ' . mysqli_error($conn)]);
    }
    exit(); // Stop further execution
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>Departments</title>
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

        /* Main Content Styles */
        .main-content {
            margin-left: 249px;
            margin-top: 60px;
            padding: 30px 40px;
            position: relative;
        }

        .dashboard-table {
            background-color: #f8f9fa;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .dashboard-table:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .table-header i {
            color: #e6b8af;
            font-size: 24px;
        }

        .table-header h2 {
            color: #4a4a4a;
            font-family: 'Century Gothic', sans-serif;
            font-size: 18px;
            font-weight: bold;
        }

        /* Table Container Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            background-color: #f0f0f0;
            color: #4a4a4a;
            font-weight: 600;
            font-size: 14px;
            padding: 15px;
            text-align: left;
            font-family: 'Century Gothic', sans-serif;
            border-bottom: 1px solid #e0e0e0;
        }

        /* Add text-align center for the Actions column header */
        th:last-child {
            text-align: center;
        }

        td {
            padding: 12px 15px;
            color: #4a4a4a;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.3s ease;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f5f5f5;
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
            min-width: 100px; /* Ensure minimum width for actions column */
        }

        .action-btn {
            background: none;
            border: none;
            color: #4a4a4a;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .action-btn i {
            font-size: 14px;
        }

        .action-btn.edit:hover {
            color: #e6b8af;
            background-color: rgba(230, 184, 175, 0.1);
        }

        .action-btn.delete:hover {
            color: #ff4747;
            background-color: rgba(255, 71, 71, 0.1);
        }

        /* Add Department Form Styles */
        .add-department-form {
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #4a4a4a;
            margin-bottom: 8px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            background-color: #ffffff;
            color: #4a4a4a;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #e6b8af;
            outline: none;
        }

        .add-btn {
            background-color: #e6b8af;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            padding: 12px 20px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            justify-content: center;
        }

        .add-btn:hover {
            background-color: #d4a5a5;
            transform: translateY(-2px);
        }

        .add-btn:active {
            transform: translateY(0);
        }

        /* Responsive Design */
        @media screen and (max-width: 1400px) {
            .main-content {
                padding: 20px;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #1E1E1E;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }

        .modal-header h2 {
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            margin: 0;
        }

        .warning-icon {
            color: #ff4747;
            font-size: 24px;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body p {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            margin: 0 0 10px 0;
        }

        .warning-text {
            color: #ff4747 !important;
            font-size: 14px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .cancel-btn {
            background-color: #2d2d2d;
            color: #AEB2B7;
        }

        .cancel-btn:hover {
            background-color: #3d3d3d;
        }

        .delete-btn {
            background-color: #09243B;
            color: #F8B83C;
        }

        .delete-btn:hover {
            background-color: #10375A;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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

        /* Success Alert Styles */
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 300px;
            max-width: 400px;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.9);
            border-left: 4px solid #2e7d32;
        }

        .alert i {
            font-size: 20px;
        }

        .alert-message {
            flex-grow: 1;
            font-size: 14px;
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
            from { 
                opacity: 1;
                transform: translateX(0);
            }
            to { 
                opacity: 0;
                transform: translateX(10px);
            }
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
                
                <!-- Incidents Report Link -->
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

                <li class="active">
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
                <!-- FAQ Link -->
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
            <!-- Main Content -->
            <div class="main-content">
                <div style="display: flex; gap: 20px;">
                    <!-- Add Department Table -->
                    <div class="content-card dashboard-table" style="flex: 1; height: 300px; display: flex; flex-direction: column;">
                        <div class="table-header">
                            <i class="fas fa-plus-circle"></i>
                            <h2>ADD NEW DEPARTMENT</h2>
                        </div>
                        <hr class="header-line">
                        <form method="POST" id="addDepartmentForm">
                            <div class="table-container" style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <label for="departmentName" style="display: block; color: #AEB2B7; margin-bottom: 8px; font-family: 'Century Gothic', sans-serif;">Department Name</label>
                                    <input type="text" id="departmentName" name="departmentName" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid rgba(255, 255, 255, 0.1); background-color: rgba(30, 30, 30, 0.8); color: #AEB2B7; font-family: 'Century Gothic', sans-serif; margin-bottom: 20px;">
                                </div>
                                <button type="submit" class="add-btn" style="width: 100%;">
                                    <i class="fas fa-plus"></i>
                                    ADD DEPARTMENT
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Departments List Table -->
                    <div class="content-card dashboard-table" style="flex: 2;">
                        <div class="table-header">
                            <i class="fas fa-list"></i>
                            <h2>ALL DEPARTMENTS</h2>
                        </div>
                        <hr class="header-line">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Departments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Display fetched departments -->
                                    <?php foreach ($departments as $department): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($department['id']); ?></td>
                                            <td><?php echo htmlspecialchars($department['name']); ?></td>
                                            <td class='actions'>
                                                <button class='action-btn edit' data-id='<?php echo htmlspecialchars($department['id']); ?>' data-name='<?php echo htmlspecialchars($department['name']); ?>'><i class='fas fa-edit'></i></button>
                                                <button class='action-btn delete' data-id='<?php echo htmlspecialchars($department['id']); ?>'><i class='fas fa-trash'></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- End of fetched departments -->
                                    <?php if (empty($departments)): ?>
                                        <tr><td colspan='3' style='text-align: center;'>No departments found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
    
                <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Function to update and display the current date and time
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

                // Update date and time immediately, then every second
                updateDateTime();
                setInterval(updateDateTime, 1000);

                // Initialize Variables for profile dropdown
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

                // Event Listeners for profile dropdown
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

                // Navigation Handlers for profile actions
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

                // Keyboard Accessibility for closing dropdown
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && isDropdownOpen) {
                        closeDropdown();
                    }
                });

                // Sidebar Dropdowns for navigation
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

                // Add Delete Functionality for department rows
                const deleteButtons = document.querySelectorAll('.action-btn.delete');
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const departmentId = this.getAttribute('data-id');
                        const departmentName = row.querySelector('td:nth-child(2)').textContent;
                        
                        // Show modal
                        const modal = document.getElementById('deleteModal');
                        const departmentNameSpan = document.getElementById('departmentNameSpan');
                        departmentNameSpan.textContent = departmentName;
                        modal.style.display = 'block';
                        
                        // Handle confirm delete
                        document.getElementById('confirmDelete').onclick = function() {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('departmentId', departmentId);
                            
                            fetch('departments.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                // Hide modal
                                modal.style.display = 'none';
                                
                                // Animate row removal
                                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-20px)';
                                
                                setTimeout(() => {
                                    row.remove();
                                    showAlert('Department deleted successfully!', 'success');
                                }, 300);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                modal.style.display = 'none';
                                showAlert('Error deleting department', 'error');
                            });
                        };
                    });
                });

                // Function to reset department IDs in the database
                function resetDepartmentIDs() {
                    fetch('resetDepartmentIDs.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Department IDs reset successfully.');
                        } else {
                            console.error('Error resetting department IDs:', data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }

                // Add Edit Functionality for department rows
                const editButtons = document.querySelectorAll('.action-btn.edit');
                editButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const departmentId = this.getAttribute('data-id');
                        const departmentName = this.getAttribute('data-name');
                        
                        window.location.href = `editDepartment.php?id=${encodeURIComponent(departmentId)}&name=${encodeURIComponent(departmentName)}`;
                    });
                });

                // Add Category Form Submission
                const addDepartmentForm = document.getElementById('addDepartmentForm');
                const departmentNameInput = document.getElementById('departmentName');

                addDepartmentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const departmentName = departmentNameInput.value.trim();
                    
                    if (departmentName === '') {
                        alert('Please enter a department name');
                        return;
                    }

                    // Create form data
                    const formData = new FormData();
                    formData.append('departmentName', departmentName);

                    // Send POST request
                    fetch('departments.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // First check if the response can be parsed as JSON
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        }
                        // If not JSON, try to parse the text response
                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                // If can't parse as JSON, check if it contains success message
                                if (text.includes('success')) {
                                    return { success: true, message: 'Department added successfully!' };
                                }
                                throw new Error('Invalid response format');
                            }
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            // Clear the input field
                            departmentNameInput.value = '';
                            
                            // Add new row to the table
                            const tbody = document.querySelector('table tbody');
                            
                            // Remove "No departments found" row if it exists
                            const noDataRow = tbody.querySelector('td[colspan="3"]');
                            if (noDataRow) {
                                noDataRow.closest('tr').remove();
                            }
                            
                            const newRow = document.createElement('tr');
                            newRow.innerHTML = `
                                <td>${data.department.id}</td>
                                <td>${data.department.name}</td>
                                <td class='actions'>
                                    <button class='action-btn edit' data-id='${data.department.id}' data-name='${data.department.name}'><i class='fas fa-edit'></i></button>
                                    <button class='action-btn delete' data-id='${data.department.id}'><i class='fas fa-trash'></i></button>
                                </td>
                            `;
                            
                            // Add fade-in effect
                            newRow.style.opacity = '0';
                            tbody.insertBefore(newRow, tbody.firstChild);
                            
                            // Trigger reflow
                            newRow.offsetHeight;
                            
                            // Add transition and fade in
                            newRow.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            newRow.style.opacity = '1';
                            newRow.style.transform = 'translateX(0)';
                            
                            // Add event listeners to new buttons
                            attachDeleteHandler(newRow.querySelector('.action-btn.delete'));
                            attachEditHandler(newRow.querySelector('.action-btn.edit'));
                            
                            // Show success message
                            alert('Department added successfully!');
                        } else {
                            alert(data.message || 'Error adding department');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Don't show error alert since department was likely added successfully
                        window.location.reload(); // Fallback to reload if needed
                    });
                });

                // Function to attach delete handler to buttons
                function attachDeleteHandler(button) {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const departmentId = this.getAttribute('data-id');
                        const departmentName = row.querySelector('td:nth-child(2)').textContent;
                        
                        // Show modal
                        const modal = document.getElementById('deleteModal');
                        const departmentNameSpan = document.getElementById('departmentNameSpan');
                        departmentNameSpan.textContent = departmentName;
                        modal.style.display = 'block';
                        
                        // Handle cancel
                        document.getElementById('cancelDelete').onclick = function() {
                            modal.style.display = 'none';
                        };
                        
                        // Handle confirm delete
                        document.getElementById('confirmDelete').onclick = function() {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('departmentId', departmentId);
                            
                            fetch('departments.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                // Hide modal
                                modal.style.display = 'none';
                                
                                // Animate row removal
                                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(-20px)';
                                
                                setTimeout(() => {
                                    row.remove();
                                    showAlert('Department deleted successfully!', 'success');
                                }, 300);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                modal.style.display = 'none';
                                showAlert('Error deleting department', 'error');
                            });
                        };
                    });
                }

                // Function to attach edit handler to buttons
                function attachEditHandler(button) {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const departmentId = this.getAttribute('data-id');
                        const departmentName = this.getAttribute('data-name');
                        
                        window.location.href = `editDepartment.php?id=${encodeURIComponent(departmentId)}&name=${encodeURIComponent(departmentName)}`;
                    });
                }

                // Attach handlers to existing buttons
                document.querySelectorAll('.action-btn.delete').forEach(attachDeleteHandler);
                document.querySelectorAll('.action-btn.edit').forEach(attachEditHandler);

                // Function to show alert messages
                function showAlert(message, type = 'success') {
                    const alert = document.createElement('div');
                    alert.className = `alert alert-${type}`;
                    alert.innerHTML = `
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        ${message}
                    `;
                    document.body.appendChild(alert);
                    
                    // Remove alert after animation
                    setTimeout(() => {
                        alert.remove();
                    }, 3000);
                }
            });
        </script>

        <!-- Custom Delete Modal -->
        <div id="deleteModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                    <h2>Delete Department</h2>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="departmentNameSpan"></span>"?</p>
                    <p class="warning-text">Warning: This action cannot be undone!</p>
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
</body>
</html>