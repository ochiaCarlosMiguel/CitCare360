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
$query = "SELECT profile_image, name, email FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user data is retrieved successfully
if ($user) {
    $profileImage = '../image/' . $user['profile_image']; // Ensure the image path is correct
    $userName = $user['name'];
    $userEmail = $user['email'];
} else {
    // Handle the case where user data is not found
    $profileImage = '../image/default.png'; // Default image
    $userName = 'Guest'; // Default name
    $userEmail = ''; // Default email
}

// Fetch count of new reports from the incidents table
$newReportsQuery = "SELECT id, full_name, subject_report, created_at FROM incidents WHERE status = 'NEW'";
$newReportsResult = $conn->query($newReportsQuery);
$newReports = $newReportsResult->fetch_all(MYSQLI_ASSOC); // Fetch all results as an associative array
$newReportsCount = count($newReports);

// Fetch count of active, resolved, and unresolved reports from the incidents table
$activeReportsQuery = "SELECT COUNT(*) as count FROM incidents WHERE status = 'ACTIVE'";
$activeReportsResult = $conn->query($activeReportsQuery);
$activeReportsCount = $activeReportsResult->fetch_assoc()['count'];

$resolvedReportsQuery = "SELECT COUNT(*) as count FROM incidents WHERE status = 'RESOLVED'";
$resolvedReportsResult = $conn->query($resolvedReportsQuery);
$resolvedReportsCount = $resolvedReportsResult->fetch_assoc()['count'];

$unresolvedReportsQuery = "SELECT COUNT(*) as count FROM incidents WHERE status = 'UNRESOLVED'";
$unresolvedReportsResult = $conn->query($unresolvedReportsQuery);
$unresolvedReportsCount = $unresolvedReportsResult->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
    <!-- Head Section -->
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="icon" type="image/png" href="../favicon.png">
    </head>

    <body>
        <!-- Welcome Message -->
        <div id="welcomeMessage" class="welcome-message" style="display: none;">
            Welcome, <?php echo htmlspecialchars($userName); ?>!
        </div>

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
                <li class="active">
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

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Dashboard Label -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">Dashboard</h1>
                <div class="dashboard-date" id="currentDate"></div>
            </div> 

            
            <!-- Add spacing below the Dashboard label -->
            <div style="margin-bottom: 20px;"></div>

            <!-- Stats Cards Row -->
            <div class="stats-container">
                <!-- New Reports Stats -->
                <div class="stat-card">
                    <div class="icon-holder" style="background-color: #09243B;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h2 class="stat-count"><?php echo htmlspecialchars($newReportsCount); ?></h2>
                        <p class="stat-label">New Reports</p>
                    </div>
                </div>

                <!-- Active Reports Stats -->
                <div class="stat-card">
                    <div class="icon-holder" style="background-color: #F4A261;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h2 class="stat-count"><?php echo htmlspecialchars($activeReportsCount); ?></h2>
                        <p class="stat-label">Active Reports</p>
                    </div>
                </div>

                <!-- Resolved Reports Stats -->
                <div class="stat-card">
                    <div class="icon-holder" style="background-color: #2C3E50;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h2 class="stat-count"><?php echo htmlspecialchars($resolvedReportsCount); ?></h2>
                        <p class="stat-label">Resolved Reports</p>
                    </div>
                </div>

                <!-- Unresolved Reports Stats -->
                <div class="stat-card">
                    <div class="icon-holder" style="background-color: #E74C3C;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h2 class="stat-count"><?php echo htmlspecialchars($unresolvedReportsCount); ?></h2>
                        <p class="stat-label">Unresolved Reports</p>
                    </div>
                </div>
            </div>

            <!-- Tables Section -->
            <div class="table-container">
                <div class="table-row">
                    <!-- New Reports -->
                    <div class="dashboard-table">
                        <div class="table-header">
                            <i class="fas fa-file-alt"></i>
                            <h2>NEW REPORTS</h2>
                        </div>
                        <hr class="header-line">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>NAME</th>
                                    <th>SUBJECT</th>
                                    <th>DATE</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($newReports as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['id']); ?></td>
                                        <td><?php echo htmlspecialchars($report['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($report['subject_report']); ?></td>
                                        <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                                        <td>
                                            <button class="view-button" onclick="navigateToReport(<?php echo htmlspecialchars($report['id']); ?>)">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($newReports)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No new reports available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Styles -->
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
                padding: 15px 15px;
                position: relative;
            }

            /* Stats cards container */
            .stats-container {
                display: flex;
                gap: 25px;
                flex-wrap: wrap;
                margin: 0 15px 30px 15px;
                justify-content: space-between;
            }

            .stat-card {
                flex: 1;
                min-width: 240px;
                height: 120px;
                background: #ffffff;
                border-radius: 15px;
                display: flex;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                border: 1px solid #e0e0e0;
                transition: all 0.3s ease;
            }

            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            }

            .icon-holder {
                width: 100px;
                height: 120px;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow: hidden;
                background: #e6b8af;
            }

            .icon-holder i {
                color: #ffffff;
                font-size: 2.5rem;
                z-index: 2;
                transition: all 0.3s ease;
            }

            .stat-info {
                flex-grow: 1;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                background-color: #ffffff;
                border-left: 1px solid #e0e0e0;
            }

            .stat-count {
                font-family: 'Century Gothic', sans-serif;
                font-size: 32px;
                color: #4a4a4a;
                margin-bottom: 5px;
                font-weight: bold;
            }

            .stat-label {
                font-family: 'Century Gothic', sans-serif;
                font-size: 16px;
                color: #666666;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            /* Dashboard Header Enhancement */
            .dashboard-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
            }

            .dashboard-title {
                font-family: 'Montserrat', sans-serif;
                font-size: 28px;
                font-weight: 700;
                color: #e6b8af;
                text-transform: uppercase;
                letter-spacing: 1px;
                position: relative;
                padding-left: 15px;
            }

            .dashboard-title::before {
                content: '';
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);
                width: 5px;
                height: 70%;
                background: #e6b8af;
                border-radius: 3px;
            }

            .dashboard-date {
                font-family: 'Century Gothic', sans-serif;
                font-size: 16px;
                color: #666666;
                background-color: #ffffff;
                padding: 8px 15px;
                border-radius: 5px;
                border-left: 3px solid #e6b8af;
            }

            /* Responsive Styles */
            @media screen and (max-width: 1400px) {
                .stats-container {
                    gap: 35px;
                }
                
                .stat-card {
                    margin-bottom: 0;
                }
            }

            @media screen and (min-width: 1401px) {
                .stats-container {
                    padding: 0;
                }
            }

            .tables-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 0;
                margin-top: 15px;
            }

            .dashboard-table {
                background: #f8f9fa;
                border-radius: 15px;
                padding: 25px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                border: 1px solid #e0e0e0;
                margin-bottom: 25px;
            }

            .table-header {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #e6b8af;
            }

            .table-header i {
                color: #e6b8af;
                font-size: 24px;
            }

            .table-header h2 {
                color: #4a4a4a;
                font-family: 'Montserrat', sans-serif;
                font-size: 18px;
                font-weight: 600;
                letter-spacing: 1px;
            }

            table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 8px;
            }

            th {
                color: #4a4a4a;
                font-weight: 600;
                font-size: 14px;
                text-transform: uppercase;
                padding: 15px;
                background-color: #f0f0f0;
                border-bottom: 2px solid #e6b8af;
            }

            td {
                color: #666666;
                font-size: 14px;
                padding: 15px;
                background-color: #ffffff;
                transition: all 0.3s ease;
            }

            tr:hover td {
                background-color: #f5f5f5;
                transform: scale(1.01);
            }

            .view-button {
                background-color: #e6b8af;
                color: #ffffff;
                border: none;
                border-radius: 5px;
                padding: 8px 12px;
                cursor: pointer;
                transition: background-color 0.3s, color 0.3s;
            }

            .view-button:hover {
                background-color: #d4a5a5;
                color: #ffffff;
            }
        </style>

        <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Show welcome message
                const welcomeMessage = document.getElementById('welcomeMessage');
                welcomeMessage.style.display = 'block';

                // Hide welcome message after 5 seconds
                setTimeout(() => {
                    welcomeMessage.style.display = 'none';
                }, 5000);

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

               // Time update function
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
                    
                    // Update dashboard date
                    const dateOptions = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', dateOptions);
                }

                // Update immediately and then every second
                updateDateTime();
                setInterval(updateDateTime, 1000);
            });

            function navigateToReport(reportId) {
                // Store the report ID in session storage
                sessionStorage.setItem('selectedReportId', reportId);
                // Navigate to incidentsReport.php with the filter set to 'NEW' and highlight the specific report
                window.location.href = 'incidentsReport.php?filter=NEW&highlight=' + reportId;
            }
        </script>

        <!-- Add before the closing body tag -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>

        </script>
    </body>
</html>