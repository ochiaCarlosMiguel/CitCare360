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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>FAQ</title>
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
            background-color: #F4A261;
            color: #F8B83C;
        }

        .submenu a:hover {
            padding-left: 20px;
        }

        .main-content {
            margin-left: 249px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .password-container {
            background-color: #1E1E1E;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .password-container h2 {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            text-align: center;
            margin-bottom: 30px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #403E3E;
            border-radius: 5px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
        }

        .input-group input:focus {
            outline: none;
            border-color: #750605;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #AEB2B7;
            cursor: pointer;
            padding: 5px;
        }

        .toggle-password:hover {
            color: #F8B83C;
        }

        .change-btn {
            width: 100%;
            padding: 12px;
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .change-btn:hover {
            background-color: #8f0807;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .header-container i {
            color: #F8B83C;
            font-size: 24px;
        }

        .divider {
            height: 1px;
            background-color: #403E3E;
            margin-bottom: 30px;
        }

        .update-btn {
            width: 100%;
            padding: 12px;
            background-color: #09243B;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            font-family: 'Century Gothic', sans-serif;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .update-btn:disabled {
            background-color: #4a4848;
            color: #AEB2B7;
            cursor: not-allowed;
        }

        .update-btn:not(:disabled):hover {
            background-color: #10375A;
        }

        .success-message {
            margin-top: 15px;
            padding: 10px;
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid #4CAF50;
            color: #4CAF50;
            border-radius: 5px;
            text-align: center;
            font-family: 'Century Gothic', sans-serif;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #403E3E;
            border-radius: 5px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
        }

        .back-btn {
            position: fixed;
            top: 80px;  /* Position below the topbar */
            left: 269px; /* Position to the right of the sidebar */
            display: flex;
            align-items: center;
            gap: 5px;
            color: #AEB2B7;
            text-decoration: none;
            font-family: 'Century Gothic', sans-serif;
            transition: color 0.3s ease;
            z-index: 1;
        }

        .back-btn:hover {
            color: #F8B83C;
        }

        /* Add these new styles for error message */
        .error-message {
            margin-top: 15px;
            padding: 10px;
            background-color: rgba(255, 71, 71, 0.1);
            border: 1px solid #ff4747;
            color: #ff4747;
            border-radius: 5px;
            text-align: center;
            font-family: 'Century Gothic', sans-serif;
            display: none;
        }

        .faq-container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #2d2d2d;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .faq-title {
            color: #F8B83C;
            text-align: center;
            margin-bottom: 15px;
        }

        .faq-intro {
            color: #AEB2B7;
            text-align: center;
            margin-bottom: 20px;
        }

        .faq-item {
            margin-bottom: 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .faq-item:hover {
            background-color: #4a4848;
        }

        .faq-question {
            color: #F8B83C;
            font-weight: bold;
            padding: 10px;
        }

        .faq-answer {
            display: none;
            color: #AEB2B7;
            padding: 10px;
            border-left: 3px solid #F8B83C;
            margin-top: 5px;
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        /* New styles for FAQ sections */
        .faq-section-title {
            color: #F8B83C;
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            margin-top: 25px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #403E3E;
        }

        .faq-answer ul {
            margin-top: 10px;
            margin-left: 20px;
            margin-bottom: 10px;
        }

        .faq-answer li {
            margin-bottom: 5px;
            color: #AEB2B7;
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
                <li class="active">
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
                // Add datetime update functionality
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

                document.querySelectorAll('.faq-item').forEach(item => {
                    item.addEventListener('click', () => {
                        item.classList.toggle('active');
                    });
                });

            });
        </script>
</body>
</html>

<div class="main-content">
    <div class="faq-container">
        <h2 class="faq-title">Frequently Asked Questions</h2>
        <p class="faq-intro">Welcome to the FAQ section! Here you will find answers to common questions about using our system. Click on a question to reveal the answer.</p>
        
        <!-- Dashboard Section -->
        <h3 class="faq-section-title">Dashboard</h3>
        <div class="faq-item">
            <div class="faq-question">What information is displayed on my dashboard?</div>
            <div class="faq-answer">Your dashboard provides an overview of incident reports:
                <ul>
                    <li>New Reports</li>
                    <li>Active Reports</li>
                    <li>Resolved Reports</li>
                    <li>Unresolved Reports</li>
                </ul>
            </div>
        </div>
        
        <!-- User Management Section -->
        <h3 class="faq-section-title">User Management</h3>
        <div class="faq-item">
            <div class="faq-question">What can I do in the User Management section?</div>
            <div class="faq-answer">In the User Management section, you can:
                <ul>
                    <li>View and manage user accounts</li>
                    <li>Create new user accounts</li>
                    <li>Edit existing user information</li>
                    <li>Manage user roles and permissions</li>
                    <li>Handle password reset requests</li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">What features are available in Manage Users?</div>
            <div class="faq-answer">In Manage Users, you can:
                <ul>
                    <li>View all user accounts in a table format</li>
                    <li>Click on profile images to view them in full size</li>
                    <li>Add new users with required information:
                        <ul>
                            <li>Full name (required)</li>
                            <li>Legitimate Gmail account (required for incident report notifications)</li>
                            <li>Profile picture (can be uploaded from file manager or taken via camera)</li>
                        </ul>
                    </li>
                    <li>Edit existing user information:
                        <ul>
                            <li>Update user's full name</li>
                            <li>Change email address</li>
                            <li>Reset password to a strong password</li>
                        </ul>
                    </li>
                    <li>Delete user accounts (with restrictions):
                        <ul>
                            <li>Cannot delete users with existing system activities</li>
                            <li>Must first delete all data created by the user</li>
                            <li>This includes any foreign key relationships in other tables</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">How does the Password Requests feature work?</div>
            <div class="faq-answer">The Password Requests system works as follows:
                <ul>
                    <li>Users can request password reset from the login page's "Forgot Password" section</li>
                    <li>They must provide either their full name or Gmail account</li>
                    <li>In the Password Requests section, you can:
                        <ul>
                            <li>View all pending password reset requests</li>
                            <li>See request details:
                                <ul>
                                    <li>User ID</li>
                                    <li>Full Name</li>
                                    <li>Request Date</li>
                                    <li>Status (Pending by default)</li>
                                    <li>Processed By (blank until action taken)</li>
                                    <li>Processed Date</li>
                                </ul>
                            </li>
                            <li>Take action by clicking the "Reset Password" button</li>
                            <li>View the new generated password in a modal</li>
                            <li>Copy the new password to send to the user</li>
                        </ul>
                    </li>
                    <li>After password reset:
                        <ul>
                            <li>The user should change their password in Settings</li>
                            <li>The generated password is random and hard to remember</li>
                            <li>Status will be updated to show the action was taken</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Incidents Report Section -->
        <h3 class="faq-section-title">Incidents Report</h3>
        <div class="faq-item">
            <div class="faq-question">What features are available in the Incidents Report table?</div>
            <div class="faq-answer">The Incidents Report table provides the following features:
                <ul>
                    <li>View all reported incidents in a comprehensive list</li>
                    <li>Access detailed information through:
                        <ul>
                            <li>Details Column:
                                <ul>
                                    <li>Click the "Read" button to open a modal</li>
                                    <li>View the full description of the incident</li>
                                </ul>
                            </li>
                            <li>Evidence Column:
                                <ul>
                                    <li>Click the "View Image" button to see submitted evidence</li>
                                    <li>View image evidence provided by students</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Manage incident status:
                        <ul>
                            <li>Change status based on current situation:
                                <ul>
                                    <li>Active: When action is being taken or explanation is provided</li>
                                    <li>Resolved: When the incident has been successfully resolved</li>
                                    <li>Unresolved: When the incident could not be resolved</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Delete functionality:
                        <ul>
                            <li>Can only delete incidents with "Unresolved" status</li>
                            <li>Cannot delete incidents with:
                                <ul>
                                    <li>New status</li>
                                    <li>Active status</li>
                                    <li>Resolved status</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Filter functionality:
                        <ul>
                            <li>Filter incidents based on their status</li>
                            <li>Quickly find specific types of incidents</li>
                            <li>Organize and manage reports more efficiently</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Block Time Management Section -->
        <h3 class="faq-section-title">Block Time Management</h3>
        <div class="faq-item">
            <div class="faq-question">How do I manage blocked time slots?</div>
            <div class="faq-answer">The Block Time Management system provides the following features:
                <ul>
                    <li>Block Time Section:
                        <ul>
                            <li>Calendar-based date selection:
                                <ul>
                                    <li>Choose available dates from the calendar</li>
                                    <li>Weekends (Saturday and Sunday) are not available</li>
                                    <li>Cannot select dates that have already passed</li>
                                </ul>
                            </li>
                            <li>Time slot management:
                                <ul>
                                    <li>Select specific time slots you are unavailable</li>
                                    <li>Check the boxes for unavailable time slots</li>
                                    <li>Save your blocked times or cancel the operation</li>
                                </ul>
                            </li>
                            <li>Purpose:
                                <ul>
                                    <li>Prevent students from scheduling meetings during blocked times</li>
                                    <li>Manage your availability effectively</li>
                                    <li>Ensure proper scheduling of appointments</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>View Blocked Time Section:
                        <ul>
                            <li>View all blocked time slots:
                                <ul>
                                    <li>See unavailable times set by users</li>
                            <li>Prevent student meeting requests during blocked times</li>
                            <li>Maintain an organized schedule</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Departments Section -->
        <h3 class="faq-section-title">Departments</h3>
        <div class="faq-item">
            <div class="faq-question">What features are available in the Departments section?</div>
            <div class="faq-answer">The Departments section provides the following features:
                <ul>
                    <li>Department Management:
                        <ul>
                            <li>Add new departments:
                                <ul>
                                    <li>Add newly established departments in CIT</li>
                                    <li>Input department name (only name is required)</li>
                                </ul>
                            </li>
                            <li>View existing departments:
                                <ul>
                                    <li>See all current CIT departments</li>
                                    <li>View department names</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Edit Department Information:
                        <ul>
                            <li>Update department names:
                                <ul>
                                    <li>Click the edit icon in the Action column</li>
                                    <li>Make changes to the department name</li>
                                    <li>Update button is disabled until changes are made</li>
                                </ul>
                            </li>
                            <li>Update process:
                                <ul>
                                    <li>Click "Update Department" when changes are made</li>
                                    <li>Receive success message: "Department's updated successfully!"</li>
                                    <li>Automatic navigation back to Departments section</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Delete Department:
                        <ul>
                            <li>Remove departments:
                                <ul>
                                    <li>Click the delete icon in the Action column</li>
                                    <li>Delete departments that are no longer part of CIT</li>
                                    <li>Remove departments that have been discontinued</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Students Section -->
        <h3 class="faq-section-title">Students</h3>
        <div class="faq-item">
            <div class="faq-question">What features are available in the Student List section?</div>
            <div class="faq-answer">The Student List section provides the following features:
                <ul>
                    <li>View all registered students:
                        <ul>
                            <li>See students who created accounts in CITCare360</li>
                            <li>View student details in a comprehensive table</li>
                            <li>Profile images can be clicked to view in full size</li>
                        </ul>
                    </li>
                    <li>View Student Details:
                        <ul>
                            <li>Click "View" button in Actions column</li>
                            <li>See complete student information:
                                <ul>
                                    <li>Basic Information</li>
                                    <li>Address</li>
                                    <li>Contact Person 1</li>
                                    <li>Contact Person 2</li>
                                </ul>
                            </li>
                            <li>Note: Fields will be null if students haven't filled additional information</li>
                        </ul>
                    </li>
                    <li>Export Functionality:
                        <ul>
                            <li>Export all registered students</li>
                            <li>Includes full student details</li>
                            <li>Download in PDF format</li>
                            <li>Export filtered results based on current search criteria</li>
                            <li>Export specific year level or department data</li>
                        </ul>
                    </li>
                    <li>Search and Filter:
                        <ul>
                            <li>Filter students by:
                                <ul>
                                    <li>Name</li>
                                    <li>Department</li>
                                    <li>Email</li>
                                    <li>Year Level (1st to 4th year)</li>
                                </ul>
                            </li>
                            <li>Advanced filtering options:
                                <ul>
                                    <li>Filter by specific year level</li>
                                    <li>Filter by department</li>
                                    <li>Combine multiple filters for precise results</li>
                                    <li>Real-time filtering as you type</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">What can I do in the Upload Students section?</div>
            <div class="faq-answer">In the Upload Students section, you can:
                <ul>
                    <li>Add individual students manually:
                        <ul>
                            <li>Fill in required student information:
                                <ul>
                                    <li>Student Number (required)</li>
                                    <li>Year Level (required, select from 1st to 4th year)</li>
                                    <li>First Name (required)</li>
                                    <li>Last Name (required)</li>
                                    <li>Middle Initial (M.I.)</li>
                                    <li>Email Address (required)</li>
                                    <li>Department (required, select from available departments)</li>
                                </ul>
                            </li>
                            <li>Click "Add Student" button to save</li>
                            <li>Receive immediate feedback on success or error</li>
                        </ul>
                    </li>
                    <li>Navigation:
                        <ul>
                            <li>Use the "Back" button to return to Existing CIT Students</li>
                            <li>Access the form through the Students dropdown menu</li>
                        </ul>
                    </li>
                    <li>Form Features:
                        <ul>
                            <li>Real-time validation of required fields</li>
                            <li>Clear error messages for invalid inputs</li>
                            <li>Success confirmation upon adding a student</li>
                            <li>Automatic form reset after successful submission</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">What features are available in the Existing CIT Students section?</div>
            <div class="faq-answer">The Existing CIT Students section provides the following features:
                <ul>
                    <li>View all CIT students:
                        <ul>
                            <li>See students from 1st to 4th year</li>
                            <li>Acts as a blueprint for student registration</li>
                            <li>Only students in this list can register in the system</li>
                        </ul>
                    </li>
                    <li>Registration Verification:
                        <ul>
                            <li>System checks for matching records in:
                                <ul>
                                    <li>First Name</li>
                                    <li>Last Name</li>
                                    <li>Email</li>
                                    <li>Student Number</li>
                                    <li>Department</li>
                                </ul>
                            </li>
                            <li>Students must match records to register</li>
                        </ul>
                    </li>
                    <li>Student Status:
                        <ul>
                            <li>View registration status of students</li>
                            <li>See if students have registered to the system</li>
                        </ul>
                    </li>
                    <li>Filter and Search:
                        <ul>
                            <li>Filter students by year level</li>
                            <li>Filter students by department</li>
                            <li>Search for specific students by:
                                <ul>
                                    <li>Student Number</li>
                                    <li>First Name</li>
                                    <li>Middle Initial</li>
                                    <li>Email</li>
                                    <li>Year Level</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Export Functionality:
                        <ul>
                            <li>Export complete list of CIT students</li>
                            <li>Download in PDF format</li>
                        </ul>
                    </li>
                    <li>Import Students:
                        <ul>
                            <li>Import multiple students via Excel</li>
                            <li>Excel format must match table columns</li>
                            <li>Bulk upload for efficiency</li>
                        </ul>
                    </li>
                    <li>Semester Management:
                        <ul>
                            <li>View current semester status</li>
                            <li>Set Semester:
                                <ul>
                                    <li>Choose between Semester 1 and Semester 2</li>
                                    <li>Sets the initial semester for the system</li>
                                    <li>Does not affect student year levels</li>
                                </ul>
                            </li>
                            <li>Change Semester:
                                <ul>
                                    <li>Automatically updates semester status</li>
                                    <li>Increases year levels when changing from Semester 2 to Semester 1</li>
                                    <li>Requires confirmation before proceeding</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Student Management:
                        <ul>
                            <li>Delete students from the system</li>
                            <li>Cannot delete registered students</li>
                            <li>Requires confirmation before deletion</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <h3 class="faq-section-title">FAQ</h3>
        <div class="faq-item">
            <div class="faq-question">What features are available in the FAQ section?</div>
            <div class="faq-answer">The FAQ section provides the following features:
                <ul>
                    <li>Interactive Question Display:
                        <ul>
                            <li>Click on any question to reveal its answer</li>
                            <li>Questions are organized by category for easy navigation</li>
                            <li>Visual feedback when hovering over questions</li>
                            <li>Accordion-style expand/collapse functionality</li>
                        </ul>
                    </li>
                    <li>Comprehensive Categories:
                        <ul>
                            <li>Dashboard features and overview</li>
                            <li>User Management capabilities</li>
                            <li>Incidents Report handling</li>
                            <li>Block Time Management</li>
                            <li>Department management</li>
                            <li>Student-related features</li>
                            <li>Profile and Settings management</li>
                        </ul>
                    </li>
                    <li>Detailed Information:
                        <ul>
                            <li>Step-by-step instructions for each feature</li>
                            <li>Clear explanations of system capabilities</li>
                            <li>Important restrictions and limitations</li>
                            <li>Best practices and tips</li>
                        </ul>
                    </li>
                    <li>User-Friendly Interface:
                        <ul>
                            <li>Dark mode optimized for reduced eye strain</li>
                            <li>Responsive design for all screen sizes</li>
                            <li>Clear visual hierarchy with section titles</li>
                            <li>Consistent styling with the rest of the system</li>
                        </ul>
                    </li>
                    <li>Navigation Features:
                        <ul>
                            <li>Quick access to all system features</li>
                            <li>Easy-to-follow instructions</li>
                            <li>Clear categorization of information</li>
                            <li>Visual indicators for active sections</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Profile Section -->
        <h3 class="faq-section-title">Profile</h3>
        <div class="faq-item">
            <div class="faq-question">What can I see and do in the Profile section?</div>
            <div class="faq-answer">The Profile section provides the following features:
                <ul>
                    <li>Profile Information Display:
                        <ul>
                            <li>View your full name</li>
                            <li>See your current profile information</li>
                        </ul>
                    </li>
                    <li>Profile Management:
                        <ul>
                            <li>Edit Profile Button:
                                <ul>
                                    <li>Click to navigate to the Settings section</li>
                                    <li>Access all profile editing options</li>
                                    <li>Make changes to your account information</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Navigation:
                        <ul>
                            <li>Quick access to profile settings through the Edit Profile button</li>
                            <li>Easy navigation to the Settings section for making changes</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Settings Section -->
        <h3 class="faq-section-title">Settings</h3>
        <div class="faq-item">
            <div class="faq-question">What features are available in the Settings section?</div>
            <div class="faq-answer">The Settings section provides the following features:
                <ul>
                    <li>Theme Customization:
                        <ul>
                            <li>Switch between Light Mode and Dark Mode</li>
                            <li>Change the system's appearance based on your preference</li>
                            <li>Toggle between themes with a single click</li>
                        </ul>
                    </li>
                    <li>Change My Photo:
                        <ul>
                            <li>Update your profile picture by clicking the "Choose Image" button</li>
                            <li>Select a new image from your file manager</li>
                            <li>Click the "Change" button to save your new profile picture</li>
                            <li>Note: The Change button is disabled until you select a new image</li>
                        </ul>
                    </li>
                    <li>Edit My Account:
                        <ul>
                            <li>Update your Full Name and Email address</li>
                            <li>Make changes to your account information</li>
                            <li>Note: The Update button is disabled until you make changes to the text fields</li>
                        </ul>
                    </li>
                    <li>Change Password:
                        <ul>
                            <li>Navigate to the Change Password section</li>
                            <li>Update your account password</li>
                            <li>Ensure your account security</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Change Password Section -->
        <h3 class="faq-section-title">Change Password</h3>
        <div class="faq-item">
            <div class="faq-question">What features are available in the Change Password section?</div>
            <div class="faq-answer">The Change Password section provides the following features:
                <ul>
                    <li>Password Update Form:
                        <ul>
                            <li>Old Password Field:
                                <ul>
                                    <li>Enter your current password for verification</li>
                                    <li>Required field for security validation</li>
                                    <li>Password visibility can be toggled using the eye icon</li>
                                </ul>
                            </li>
                            <li>New Password Field:
                                <ul>
                                    <li>Enter your desired new password</li>
                                    <li>Required field for password update</li>
                                    <li>Password visibility can be toggled using the eye icon</li>
                                </ul>
                            </li>
                            <li>Change Button:
                                <ul>
                                    <li>Click to submit the password change request</li>
                                    <li>Validates both old and new passwords</li>
                                    <li>Updates password upon successful validation</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Validation and Security:
                        <ul>
                            <li>Old Password Verification:
                                <ul>
                                    <li>System verifies if the entered old password matches current password</li>
                                    <li>Prevents unauthorized password changes</li>
                                    <li>Displays error message if old password is incorrect</li>
                                </ul>
                            </li>
                            <li>Password Update Process:
                                <ul>
                                    <li>New password is securely hashed before storage</li>
                                    <li>System confirms successful password update</li>
                                    <li>Provides feedback message for operation status</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>User Experience:
                        <ul>
                            <li>Clear visual feedback for all actions</li>
                            <li>Error messages for invalid inputs</li>
                            <li>Success confirmation upon password update</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Import Guide Section -->
        <h3 class="faq-section-title">Import Guide</h3>
        <div class="faq-item">
            <div class="faq-question">How do I import students using an Excel file?</div>
            <div class="faq-answer">To import students using an Excel file, follow these steps:
                <ul>
                    <li>Prepare your Excel file with the following columns in order:
                        <ul>
                            <li>Student Number</li>
                            <li>First Name</li>
                            <li>Last Name</li>
                            <li>Middle Initial (M.I.)</li>
                            <li>Email Address</li>
                            <li>Year Level</li>
                            <li>Department</li>
                        </ul>
                    </li>
                    <li>Ensure your file is in one of these formats:
                        <ul>
                            <li>Excel Workbook (*.xlsx)</li>
                            <li>Excel 97-2003 Workbook (*.xls)</li>
                            <li>CSV (Comma delimited) (*.csv)</li>
                        </ul>
                    </li>
                    <li>Data validation rules:
                        <ul>
                            <li>Name validation:
                                <ul>
                                    <li>Cannot import if First Name, Last Name, and M.I. all match existing data</li>
                                    <li>Allowed if only First Name matches</li>
                                    <li>Allowed if only Last Name matches</li>
                                    <li>Allowed if only M.I. matches</li>
                                </ul>
                            </li>
                            <li>Year Level validation:
                                <ul>
                                    <li>Must be between 1 and 4</li>
                                    <li>Cannot be 0 or negative</li>
                                    <li>Cannot be 5 or higher</li>
                                </ul>
                            </li>
                            <li>Email validation:
                                <ul>
                                    <li>Cannot import if email address already exists</li>
                                    <li>Prevents duplicate email addresses in the system</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li>Import process:
                        <ul>
                            <li>Go to the Upload Students section</li>
                            <li>Click the "Import" button</li>
                            <li>Select your prepared Excel file</li>
                            <li>The system will validate and import the data</li>
                            <li>You will receive a success message or error notifications if any issues are found</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
