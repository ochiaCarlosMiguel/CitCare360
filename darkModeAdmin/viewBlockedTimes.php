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

if (!$user) {
    // Handle the case where the user is not found
    echo "User not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>Schedule</title>
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

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            color: #F8B83C;
        }

        /* Content Card for Scheduler */
        .scheduler {
            background: linear-gradient(145deg, #1a1a1a, #2a2a2a);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin: 20px auto;
            max-width: 1000px;
        }

        .scheduler-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .scheduler-title {
            color: #F8B83C;
            font-size: 24px;
            font-weight: 600;
            font-family: 'Century Gothic', sans-serif;
        }

        .scheduler-controls {
            display: flex;
            gap: 15px;
        }

        .scheduler-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
            background: linear-gradient(145deg, #2a2a2a, #3a3a3a);
            color: #E0E0E0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .scheduler-btn:hover {
            background: linear-gradient(145deg, #3a3a3a, #4a4a4a);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .scheduler-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .scheduler-day {
            background: linear-gradient(145deg, #2a2a2a, #3a3a3a);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            min-height: 150px;
        }

        .scheduler-day-header {
            color: #F8B83C;
            font-weight: 600;
            margin-bottom: 10px;
            font-family: 'Century Gothic', sans-serif;
        }

        .scheduler-time-slot {
            background: linear-gradient(145deg, #1a1a1a, #2a2a2a);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            color: #E0E0E0;
            font-family: 'Century Gothic', sans-serif;
        }

        .scheduler-time-slot.blocked {
            background: linear-gradient(145deg, rgba(255, 71, 71, 0.2), rgba(255, 71, 71, 0.1));
            border-color: rgba(255, 71, 71, 0.3);
            color: #ff4747;
        }

        .scheduler-time-slot.available {
            background: linear-gradient(145deg, rgba(76, 175, 80, 0.2), rgba(76, 175, 80, 0.1));
            border-color: rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }

        .scheduler-time-slot:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(30, 30, 30, 0.85); /* Darker background for the table */
            border-radius: 8px; /* Rounded corners */
            overflow: hidden; /* Prevents overflow of rounded corners */
        }

        th, td {
            border: 1px solid rgba(255, 255, 255, 0.2); /* Slightly more visible borders */
            padding: 15px; /* Increased padding for better spacing */
            text-align: left;
            color: #AEB2B7;
            font-size: 1em; /* Consistent font size */
        }

        th {
            background-color: rgba(244, 162, 97, 0.9); /* More vibrant header background */
            color: #fff;
            font-weight: bold;
            text-transform: uppercase; /* Uppercase for header text */
        }

        tr:nth-child(even) {
            background-color: rgba(50, 50, 50, 0.8); /* Distinct color for even rows */
        }

        tr:hover {
            background-color: rgba(244, 162, 97, 0.7); /* Highlight row on hover */
            color: #1E1E1E; /* Change text color on hover */
            transition: background-color 0.3s ease; /* Smooth transition for hover effect */
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .main-content {
                padding: 10px;
            }

            #scheduler {
                padding: 15px;
            }

            th, td {
                padding: 10px; /* Adjust padding for smaller screens */
                font-size: 0.9em; /* Slightly smaller font size on mobile */
            }
        }

        /* Date Filter Styles */
        .date-filter {
            padding: 10px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: linear-gradient(145deg, #2a2a2a, #3a3a3a);
            color: #E0E0E0;
            font-family: 'Century Gothic', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .date-filter:hover {
            background: linear-gradient(145deg, #3a3a3a, #4a4a4a);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .date-filter::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }

        .date-filter::-webkit-datetime-edit {
            color: #E0E0E0;
        }

        .date-filter::-webkit-datetime-edit-fields-wrapper {
            color: #E0E0E0;
        }

        .date-filter::-webkit-datetime-edit-text {
            color: #E0E0E0;
        }

        .date-filter::-webkit-datetime-edit-month-field,
        .date-filter::-webkit-datetime-edit-day-field,
        .date-filter::-webkit-datetime-edit-year-field {
            color: #E0E0E0;
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
                <li class="dropdown open">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-clock"></i>Block Time Management
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="blockTime.php"><i class="fas fa-plus-circle"></i>Block Time</a></li>
                        <li class="active"><a href="viewBlockedTimes.php"><i class="fas fa-eye"></i>View Blocked Times</a></li>
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

            <!-- Main Content Area for Scheduler -->
    <div class="main-content">
        <h2 style="color: #F8B83C;">Counselor Blocked Times</h2>
        <div class="scheduler">
            <div class="scheduler-header">
                <h1 class="scheduler-title">Counselor Blocked Times</h1>
                <div class="scheduler-controls">
                    <input type="date" id="dateFilter" class="date-filter" value="<?php echo date('Y-m-d'); ?>">
                    <button class="scheduler-btn" id="filterBtn">Filter</button>
                    <button class="scheduler-btn" id="todayBtn">Today</button>
                </div>
            </div>
            <div class="scheduler-grid" id="schedulerGrid">
                <?php
                // Get the selected date from the request, default to today
                $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
                
                // Fetch counselor blocked times from the database with counselor names
                $query = "
                    SELECT 
                        c.blocked_date, 
                        c.blocked_time, 
                        a.name AS admin_name 
                    FROM 
                        admin_blocked_times c 
                    JOIN 
                        admin_users a ON c.admin_id = a.id
                    WHERE 
                        DATE(c.blocked_date) = ?"; // Filter by selected date
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $selectedDate);
                $stmt->execute();
                $result = $stmt->get_result();

                // Initialize an array to group data by date
                $groupedData = [];

                while ($row = $result->fetch_assoc()) {
                    // Format the date
                    $date = new DateTime($row['blocked_date']);
                    $formattedDate = $date->format('l, F j, Y'); // Format to "Wednesday, April 16, 2025"

                    // Group by formatted date
                    if (!isset($groupedData[$formattedDate])) {
                        $groupedData[$formattedDate] = [];
                    }
                    $groupedData[$formattedDate][] = [
                        'blocked_time' => htmlspecialchars($row['blocked_time']),
                        'admin_name' => htmlspecialchars($row['admin_name']),
                    ];
                }

                // Display the grouped data
                foreach ($groupedData as $date => $times) {
                    // Display the first entry with rowspan
                    echo "<div class='scheduler-day'>
                            <div class='scheduler-day-header'>".$date."</div>
                            <div class='scheduler-time-slot blocked'>".$times[0]['admin_name']."</div>";
                    // Display subsequent entries for the same date
                    for ($i = 1; $i < count($times); $i++) {
                        echo "<div class='scheduler-time-slot blocked'>".$times[$i]['blocked_time']."</div>";
                    }
                    echo "</div>";
                }
                ?>
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
                        // Send request to logout script
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

                // Add date filter functionality
                const dateFilter = document.getElementById('dateFilter');
                const filterBtn = document.getElementById('filterBtn');
                const todayBtn = document.getElementById('todayBtn');
                const schedulerGrid = document.getElementById('schedulerGrid');

                // Set initial date to today
                dateFilter.value = new Date().toISOString().split('T')[0];

                // Filter button click handler
                filterBtn.addEventListener('click', function() {
                    const selectedDate = dateFilter.value;
                    window.location.href = `viewBlockedTimes.php?date=${selectedDate}`;
                });

                // Today button click handler
                todayBtn.addEventListener('click', function() {
                    const today = new Date().toISOString().split('T')[0];
                    dateFilter.value = today;
                    window.location.href = `viewBlockedTimes.php?date=${today}`;
                });
            });
        </script>
</body>
</html>