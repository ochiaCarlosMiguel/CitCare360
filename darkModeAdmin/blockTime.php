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

// Check if user data is retrieved successfully
if ($user) {
    $profileImage = '../image/' . $user['profile_image']; // Ensure the image path is correct
    $userName = $user['name'];
} else {
    // Handle the case where user data is not found
    $profileImage = '../image/default.png'; // Default image
    $userName = 'Guest'; // Default name
}


// Fetch departments from the database
$departments = [];
$result = $conn->query("SELECT id, name FROM departments");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row; // Store each department in the array
    }
}

// Fetch counselors from the admin_users table
$counselors = [];
$result = $conn->query("SELECT id, name FROM admin_users WHERE user_role = 'Counselor'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counselors[] = $row; // Store each counselor in the array
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <!-- Head Section -->
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Block Time</title>
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
                <li class="dropdown open">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-clock"></i>Block Time Management
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="active"><a href="blockTime.php"><i class="fas fa-plus-circle"></i>Block Time</a></li>
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

        <!-- Main Content Section (Updated for Event Creation) -->
        <div class="main-content">
            <div class="content-card dashboard-table">
                <div class="table-header">
                    <i class="fas fa-calendar-alt"></i>
                    <h2 class="calendar-title">Add Admin Blocked Time</h2>
                </div>
                <hr class="header-line">
                <div class="calendar">
                    <div class="calendar-header">
                        <button id="prevMonthBtn" class="nav-button">&lt; Previous</button>
                        <h3 id="currentMonth" class="current-month-label"></h3>
                        <button id="nextMonthBtn" class="nav-button">Next &gt;</button>
                    </div>
                    <div class="calendar-days">
                        <div class="day-header">SUNDAY</div>
                        <div class="day-header">MONDAY</div>
                        <div class="day-header">TUESDAY</div>
                        <div class="day-header">WEDNESDAY</div>
                        <div class="day-header">THURSDAY</div>
                        <div class="day-header">FRIDAY</div>
                        <div class="day-header">SATURDAY</div>
                    </div>
                    <div class="calendar-grid" id="calendarGrid">
                        <!-- Calendar days will be dynamically generated here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Blocking Time Slots -->
        <div id="blockTimeModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="modal-header">
                    <i class="fas fa-clock"></i>
                    <h2>Set Unavailable Time Slots</h2>
                </div>
                
                <div class="selected-date-display">
                    Selected Date: <span id="displaySelectedDate"></span>
                </div>

                <form id="blockTimeForm">
                    <input type="hidden" id="selectedDate" name="selectedDate">
                    
                    <div class="time-slots-container">
                        <h3>Select time slots you are NOT available:</h3>
                        <div class="time-slots">
                            <?php
                            // Generate time slots from 8 AM to 5 PM with 1-hour intervals
                            $start = strtotime('8:00');
                            $end = strtotime('17:00');
                            $interval = 60 * 60; // 1 hour interval

                            for ($time = $start; $time <= $end; $time += $interval) {
                                $timeString = date('H:i', $time);
                                echo "<div class='time-slot-item'>";
                                echo "<input type='checkbox' name='blocked_times[]' value='{$timeString}' id='time_{$timeString}'>";
                                echo "<label for='time_{$timeString}'>" . date('h:i A', $time) . "</label>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="modal-button save-btn">
                            <i class="fas fa-save"></i> Save Blocked Times
                        </button>
                        <button type="button" class="modal-button cancel-btn" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
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
            background-color: #F4A261;
            color: #1E1E1E;
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

            .main-content {
                margin-left: 249px; /* Adjust based on sidebar width */
                margin-top: 60px; /* Adjust for topbar height */
                padding: 30px 40px;
                position: relative;
            }

            .content-card {
                background: linear-gradient(145deg, #1a1a1a, #2a2a2a);
                border: 1px solid rgba(255, 255, 255, 0.05);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                padding: 30px;
                border-radius: 16px;
                max-width: 800px;
                margin: 20px auto;
            }

            .dashboard-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background-color: rgba(255, 255, 255, 0.05);
                border-radius: 8px;
                overflow: hidden;
            }

            .dashboard-table th {
                background-color: #09243B;
                color: #F8B83C;
                font-weight: 600;
                text-transform: uppercase;
                padding: 15px;
                text-align: left;
                font-family: 'Century Gothic', sans-serif;
            }

            .dashboard-table td {
                padding: 12px 15px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                color: #AEB2B7;
                font-family: 'Century Gothic', sans-serif;
            }

            .dashboard-table tr:last-child td {
                border-bottom: none;
            }

            .dashboard-table tr:hover {
                background-color: rgba(255, 255, 255, 0.05);
            }

            .dashboard-table .action-btn {
                padding: 8px 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-family: 'Century Gothic', sans-serif;
                transition: all 0.3s ease;
            }

            .dashboard-table .edit-btn {
                background-color: #e6b8af;
                color: #ffffff;
            }

            .dashboard-table .edit-btn:hover {
                background-color: #d4a5a5;
            }

            .dashboard-table .delete-btn {
                background-color: #ff4747;
                color: #ffffff;
            }

            .dashboard-table .delete-btn:hover {
                background-color: #ff3333;
            }

            .dashboard-table .status {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .dashboard-table .status.active {
                background-color: rgba(76, 175, 80, 0.1);
                color: #4CAF50;
            }

            .dashboard-table .status.inactive {
                background-color: rgba(255, 71, 71, 0.1);
                color: #ff4747;
            }

            .dashboard-table .status.pending {
                background-color: rgba(255, 152, 0, 0.1);
                color: #ff9800;
            }

            .table-header {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }

            .table-header i {
                font-size: 20px;
                color: #F8B83C;
                margin-right: 10px;
            }

            .header-line {
                flex-grow: 1;
                height: 1px;
                background-color: rgba(255, 255, 255, 0.1);
            }

            .calendar {
                margin-top: 20px;
                background-color: rgba(255, 255, 255, 0.05);
                padding: 20px;
                border-radius: 8px;
            }

            .calendar-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .calendar-days {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                text-align: center;
                font-weight: bold;
                background-color: rgba(0, 0, 0, 0.2);
                padding: 5px 0;
                border-radius: 8px;
            }

            .day-header {
                padding: 10px;
                background-color: #09243B;
                color: #F8B83C;
            }

            .calendar-grid {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 5px;
                margin-top: 10px;
            }

            .day {
                width: 100px;
                height: 100px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                padding: 10px;
                position: relative;
                background-color: rgba(255, 255, 255, 0.05);
                color: #AEB2B7;
                transition: background-color 0.3s ease;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                border-radius: 8px;
            }

            .day:hover {
                background-color: rgba(248, 184, 60, 0.1);
                border-color: rgba(248, 184, 60, 0.3);
            }

            .status-label {
                position: absolute;
                bottom: 5px;
                left: 5px;
                font-size: 12px;
                color: #F8B83C;
            }

            .reservation-count {
                position: absolute;
                top: 5px;
                right: 5px;
                font-size: 12px;
                font-weight: bold;
                color: #F8B83C;
            }

            .calendar-title {
                color: #F8B83C;
                font-family: 'Century Gothic', sans-serif;
                font-size: 20px;
                font-weight: bold;
                margin-left: 10px;
            }

            .current-month-label {
                color: #F8B83C;
                font-family: 'Century Gothic', sans-serif;
                font-size: 24px;
                font-weight: bold;
                text-align: center;
                flex-grow: 1;
            }

            .nav-button {
                background-color: #09243B;
                color: #F8B83C;
                border: 1px solid rgba(248, 184, 60, 0.3);
                border-radius: 5px;
                padding: 10px 15px;
                font-family: 'Century Gothic', sans-serif;
                font-size: 16px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .nav-button:hover {
                background-color: rgba(248, 184, 60, 0.1);
                transform: translateY(-2px);
            }

            /* New style for Add Event button */
            #addEventBtn {
                position: absolute; /* Position it absolutely */
                top: 10px; /* Adjust top position */
                right: 20px; /* Adjust right position */
            }

            /* Modal Styles */
            .modal {
                display: none; /* Hidden by default */
                position: fixed; /* Stay in place */
                z-index: 1; /* Sit on top */
                left: 0;
                top: 0;
                width: 100%; /* Full width */
                height: 100%; /* Full height */
                overflow: auto; /* Enable scroll if needed */
                background-color: rgba(0, 0, 0, 0.6); /* Black w/ opacity */
            }

            .modal-content {
                background-color: #2A2A2A; /* Dark background to match the page */
                margin: 10% auto; /* Center the modal */
                padding: 20px;
                border: 1px solid #888;
                width: 80%; /* Could be more or less, depending on screen size */
                border-radius: 8px; /* Rounded corners */
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5); /* Shadow for depth */
            }

            .close {
                color: #F8B83C; /* Close button color */
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .close:hover {
                color: #FFD700; /* Change color on hover */
            }

            h2 {
                color: #F8B83C; /* Title color */
                font-family: 'Montserrat', sans-serif;
                margin-bottom: 20px;
            }

            label {
                color: #AEB2B7; /* Label color */
                font-family: 'Century Gothic', sans-serif;
                margin-top: 10px;
                display: block;
            }

            input[type="date"],
            input[type="time"],
            textarea {
                width: 100%; /* Full width */
                padding: 10px; /* Padding for inputs */
                margin-top: 5px; /* Space above inputs */
                border: 1px solid #ccc; /* Border for inputs */
                border-radius: 5px; /* Rounded corners */
                background-color: #1E1E1E; /* Dark background for inputs */
                color: #AEB2B7; /* Text color */
                font-family: 'Century Gothic', sans-serif;
            }

            input[type="date"]:focus,
            input[type="time"]:focus,
            textarea:focus {
                border-color: #F8B83C; /* Highlight border on focus */
                outline: none; /* Remove default outline */
            }

            .modal-button {
                background-color: #F4A261; /* Button color */
                color: #1E1E1E; /* Button text color */
                border: none; /* No border */
                border-radius: 5px; /* Rounded corners */
                padding: 10px 15px; /* Padding for button */
                font-family: 'Century Gothic', sans-serif;
                font-size: 16px;
                cursor: pointer; /* Pointer cursor on hover */
                margin-top: 15px; /* Space above button */
                transition: background-color 0.3s ease; /* Transition for hover effect */
            }

            .modal-button:hover {
                background-color: #D89A4D; /* Darker shade on hover */
            }

            .styled-input,
            .styled-select {
                width: 100%; /* Full width */
                padding: 10px; /* Padding for inputs */
                margin-top: 5px; /* Space above inputs */
                border: 1px solid #ccc; /* Border for inputs */
                border-radius: 5px; /* Rounded corners */
                background-color: #1E1E1E; /* Dark background for inputs */
                color: #AEB2B7; /* Text color */
                font-family: 'Century Gothic', sans-serif;
            }

            .styled-input:focus,
            .styled-select:focus {
                border-color: #F8B83C; /* Highlight border on focus */
                outline: none; /* Remove default outline */
            }

            .user-list {
                list-style: none; /* Remove default list styling */
                padding: 0; /* Remove padding */
                margin: 0; /* Remove margin */
            }

            .user-item {
                display: flex; /* Use flexbox for alignment */
                justify-content: space-between; /* Space between name and button */
                align-items: center; /* Center items vertically */
                background-color: rgba(255, 255, 255, 0.1); /* Light background */
                border: 1px solid rgba(255, 255, 255, 0.2); /* Light border */
                border-radius: 5px; /* Rounded corners */
                padding: 10px; /* Padding for spacing */
                margin-bottom: 10px; /* Space between items */
                transition: background-color 0.3s; /* Smooth background transition */
            }

            .user-item:hover {
                background-color: rgba(244, 162, 97, 0.3); /* Change background on hover */
            }

            .user-name {
                color: #AEB2B7; /* Text color for user names */
                font-family: 'Century Gothic', sans-serif; /* Font style */
                font-size: 16px; /* Font size */
            }

            .selectUserBtn {
                background-color: #F4A261; /* Button background color */
                color: #1E1E1E; /* Button text color */
                border: none; /* No border */
                border-radius: 5px; /* Rounded corners */
                padding: 8px 12px; /* Padding for button */
                cursor: pointer; /* Pointer cursor on hover */
                transition: background-color 0.3s; /* Smooth background transition */
            }

            .selectUserBtn:hover {
                background-color: #D89A4D; /* Darker shade on hover */
            }

            .select-user-label {
                color: #F8B83C; /* Label text color */
                font-family: 'Montserrat', sans-serif; /* Font style */
                font-size: 20px; /* Font size */
                margin-bottom: 15px; /* Space below the label */
                text-align: center; /* Center the label */
                border-bottom: 2px solid rgba(255, 255, 255, 0.2); /* Underline effect */
                padding-bottom: 10px; /* Space below the text */
            }

            .time-slots-container {
                margin: 20px 0;
            }

            .time-slots {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
                max-height: 300px;
                overflow-y: auto;
            }

            .time-slot-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px;
                background-color: rgba(255, 255, 255, 0.1);
                border-radius: 4px;
            }

            .time-slot-item label {
                color: #AEB2B7;
                margin: 0;
            }

            .time-slot-item input[type="checkbox"] {
                accent-color: #F8B83C;
            }

            .modal-header {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #F8B83C;
                padding-bottom: 10px;
            }

            .modal-header i {
                font-size: 24px;
                color: #F8B83C;
                margin-right: 10px;
            }

            .modal-header h2 {
                margin: 0;
                color: #F8B83C;
            }

            .selected-date-display {
                background-color: rgba(248, 184, 60, 0.1);
                padding: 10px 15px;
                border-radius: 5px;
                margin-bottom: 20px;
                color: #F8B83C;
                font-size: 18px;
                font-family: 'Century Gothic', sans-serif;
            }

            .time-slots-container {
                background-color: rgba(255, 255, 255, 0.05);
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }

            .time-slots-container h3 {
                color: #AEB2B7;
                margin-bottom: 15px;
                font-size: 16px;
            }

            .time-slots {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                max-height: 300px;
                overflow-y: auto;
                padding: 10px;
            }

            .time-slot-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px;
                background-color: rgba(255, 255, 255, 0.1);
                border-radius: 6px;
                transition: background-color 0.3s ease;
            }

            .time-slot-item:hover {
                background-color: rgba(248, 184, 60, 0.1);
            }

            .time-slot-item label {
                color: #AEB2B7;
                margin: 0;
                cursor: pointer;
            }

            .time-slot-item input[type="checkbox"] {
                width: 18px;
                height: 18px;
                accent-color: #F8B83C;
                cursor: pointer;
            }

            .form-footer {
                display: flex;
                justify-content: flex-end;
                gap: 15px;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }

            .modal-button {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                font-family: 'Century Gothic', sans-serif;
                font-size: 16px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .save-btn {
                background-color: #F8B83C;
                color: #1E1E1E;
            }

            .save-btn:hover {
                background-color: #D89A4D;
            }

            .cancel-btn {
                background-color: #4a4a4a;
                color: #AEB2B7;
            }

            .cancel-btn:hover {
                background-color: #5a5a5a;
            }
        </style>

        <!-- Scripts -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const welcomeMessage = document.getElementById('welcomeMessage');
            const calendarGrid = document.getElementById('calendarGrid');
            const currentMonthLabel = document.getElementById('currentMonth');
            const prevMonthBtn = document.getElementById('prevMonthBtn');
            const nextMonthBtn = document.getElementById('nextMonthBtn');
            const blockTimeModal = document.getElementById('blockTimeModal');
            const closeModalBtn = document.querySelector('.close');
            const blockTimeForm = document.getElementById('blockTimeForm');
            const profileContainer = document.querySelector('.profile-container');
            const dropdownContent = document.querySelector('.dropdown-content');
            const profileBtn = document.getElementById('profileBtn');
            const settingsBtn = document.getElementById('settingsBtn');
            const logoutBtn = document.getElementById('logoutBtn');
            const currentDateTime = document.getElementById('currentDateTime');

            let currentDate = new Date();
            let isDropdownOpen = false;

            // Welcome Message
            welcomeMessage.style.display = 'block';
            setTimeout(() => {
                welcomeMessage.style.display = 'none';
            }, 5000);

            // Add this time update function
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
                currentDateTime.textContent = now.toLocaleDateString('en-US', options);
            }

            // Update time immediately and then every second
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Modal Functions
            window.openModal = function(date) {
                const selectedDate = document.getElementById('selectedDate');
                const displayDate = document.getElementById('displaySelectedDate');
                
                selectedDate.value = date;
                
                const formattedDate = new Date(date).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                displayDate.textContent = formattedDate;
                
                blockTimeModal.style.display = 'block';
                fetchBlockedTimes(date);
            }

            function closeModal() {
                blockTimeModal.style.display = 'none';
            }

            // Calendar Functions
            function renderCalendar() {
                calendarGrid.innerHTML = '';
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                const firstDay = new Date(year, month, 1).getDay();
                const lastDate = new Date(year, month + 1, 0).getDate();

                currentMonthLabel.textContent = currentDate.toLocaleString('default', { 
                    month: 'long', 
                    year: 'numeric' 
                });

                // Empty placeholders
                for (let i = 0; i < firstDay; i++) {
                    const emptyDay = document.createElement('div');
                    emptyDay.classList.add('day');
                    calendarGrid.appendChild(emptyDay);
                }

                // Create days
                for (let date = 1; date <= lastDate; date++) {
                    const dayElement = document.createElement('div');
                    dayElement.classList.add('day');
                    dayElement.textContent = date;

                    const dayDate = new Date(year, month, date);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (dayDate >= today) {
                        dayElement.addEventListener('click', function() {
                            const formattedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                            openModal(formattedDate);
                        });
                        dayElement.style.cursor = 'pointer';
                    } else {
                        dayElement.style.pointerEvents = 'none';
                        dayElement.style.color = '#A9A9A9';
                        dayElement.style.backgroundColor = '#555';
                    }

                    if (dayDate.getDay() === 0 || dayDate.getDay() === 6) {
                        const label = document.createElement('div');
                        label.classList.add('status-label');
                        label.textContent = 'Not Available';
                        dayElement.appendChild(label);
                        dayElement.style.pointerEvents = 'none';
                        dayElement.style.color = '#A9A9A9';
                        dayElement.style.backgroundColor = '#555';
                    }

                    calendarGrid.appendChild(dayElement);
                }
            }

            // API Functions
            function fetchBlockedTimes(date) {
                fetch(`getBlockedTimes.php?date=${date}`)
                    .then(response => response.json())
                    .then(data => {
                        document.querySelectorAll('input[name="blocked_times[]"]')
                            .forEach(checkbox => checkbox.checked = false);
                        
                        data.forEach(time => {
                            const checkbox = document.querySelector(`input[value="${time}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    })
                    .catch(error => console.error('Error fetching blocked times:', error));
            }

            // Event Listeners
            prevMonthBtn.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });

            nextMonthBtn.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });

            closeModalBtn.onclick = closeModal;

            window.onclick = function(event) {
                if (event.target == blockTimeModal) {
                    closeModal();
                }
            }

            blockTimeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('saveBlockedTimes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Time slots blocked successfully!');
                        closeModal();
                        renderCalendar();
                    } else {
                        alert('Error blocking time slots: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving blocked times.');
                });
            });

            // Profile Dropdown Functions
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

            // Profile Event Listeners
            profileContainer.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent click from bubbling up
                isDropdownOpen ? closeDropdown() : openDropdown();
            });

            // Close dropdown if clicking outside
            document.addEventListener('click', function(e) {
                if (isDropdownOpen && !dropdownContent.contains(e.target) && !profileContainer.contains(e.target)) {
                    closeDropdown();
                }
            });

            // Navigation Event Listeners
            profileBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'profile.php';
            });

            settingsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'settings.php';
            });

            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = '../adminPortal/logout.php';
                }
            });

            // Initialize
            renderCalendar();

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
        });
        </script>
    </body>
</html>