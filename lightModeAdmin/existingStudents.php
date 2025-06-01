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
    <title>Document</title>
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
                <li class="dropdown open">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-graduate"></i>Students
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="student.php"><i class="fas fa-user-graduate"></i>Student List</a></li>
                        <li><a href="uploadStudents.php"><i class="fas fa-upload"></i>Upload Students</a></li>
                        <li class="active"><a href="existingStudents.php"><i class="fas fa-list"></i>Existing CIT Students</a></li>
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
    
        <!-- Main Content -->
        <div class="main-content" style="margin-left: 249px; padding: 20px; margin-top: 60px;">
            <div class="content-wrapper" style="background: #FFFFFF; border-radius: 15px; padding: 40px; max-width: 1200px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                <!-- Header -->
                <div class="content-header" style="margin-bottom: 30px;">
                    <div class="header-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h2 style="color: #E6B8AF; font-family: 'Montserrat', sans-serif; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-list"></i> Existing CIT Students
                        </h2>
                        <div class="action-buttons" style="display: flex; gap: 10px;">
                            <button id="importBtn" class="action-btn" style="background: #E6B8AF; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-file-import"></i> Import
                            </button>
                            <button id="exportBtn" class="action-btn" style="background: #E6B8AF; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-file-export"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="header-line" style="height: 2px; background: #E6B8AF; border-bottom: 1px solid #E6B8AF;"></div>
                </div>

                <!-- Semester Display -->
                <div class="semester-section" style="margin-bottom: 20px; background: #F5F5F5; padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-alt" style="color: #E6B8AF; font-size: 20px;"></i>
                        <span style="font-family: 'Century Gothic', sans-serif; color: #4A4A4A; font-size: 16px;">Current Semester:</span>
                        <span id="currentSemester" style="font-family: 'Century Gothic', sans-serif; color: #E6B8AF; font-size: 18px; font-weight: bold;">
                            <?php
                            // Fetch current semester from database
                            $semesterQuery = "SELECT value FROM settings WHERE setting_name = 'current_semester'";
                            $semesterResult = $conn->query($semesterQuery);
                            $currentSemester = $semesterResult->fetch_assoc()['value'] ?? '1';
                            echo "Semester " . $currentSemester;
                            ?>
                        </span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button id="setSemesterBtn" style="background: #4CAF50; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-calendar-plus"></i> Set Semester
                        </button>
                        <button id="updateSemesterBtn" style="background: #E6B8AF; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-sync-alt"></i> Change Semester
                        </button>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-section" style="margin-bottom: 20px;">
                    <div class="search-container" style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <div class="search-input" style="flex: 1; position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #4A4A4A;"></i>
                            <input type="text" id="searchInput" placeholder="Search by Student No., First Name, M.I., Email, or Year Level" 
                                style="width: 100%; padding: 12px 40px; background: #F5F5F5; border: 1px solid #E6B8AF; border-radius: 5px; color: #4A4A4A; font-family: 'Century Gothic', sans-serif;">
                        </div>
                        <div class="filter-container" style="position: relative;">
                            <select id="yearLevelFilter" style="padding: 12px 40px; background: #F5F5F5; border: 1px solid #E6B8AF; border-radius: 5px; color: #4A4A4A; font-family: 'Century Gothic', sans-serif; appearance: none; -webkit-appearance: none; -moz-appearance: none; cursor: pointer;">
                                <option value="">All Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <i class="fas fa-filter" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #4A4A4A;"></i>
                            <i class="fas fa-chevron-down" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #4A4A4A; pointer-events: none;"></i>
                        </div>
                        <div class="filter-container" style="position: relative;">
                            <select id="departmentFilter" style="padding: 12px 40px; background: #F5F5F5; border: 1px solid #E6B8AF; border-radius: 5px; color: #4A4A4A; font-family: 'Century Gothic', sans-serif; appearance: none; -webkit-appearance: none; -moz-appearance: none; cursor: pointer;">
                                <option value="">All Departments</option>
                                <?php
                                // Fetch departments for the filter
                                $deptQuery = "SELECT id, name FROM departments ORDER BY name";
                                $deptResult = $conn->query($deptQuery);
                                if ($deptResult && $deptResult->num_rows > 0) {
                                    while ($dept = $deptResult->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($dept['id']) . "'>" . htmlspecialchars($dept['name']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <i class="fas fa-building" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #4A4A4A;"></i>
                            <i class="fas fa-chevron-down" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #4A4A4A; pointer-events: none;"></i>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-container" style="overflow-x: auto;">
                    <table id="studentsTable" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #E6B8AF; color: #FFFFFF;">
                                <th style="padding: 12px; text-align: left;">Student No.</th>
                                <th style="padding: 12px; text-align: left;">First Name</th>
                                <th style="padding: 12px; text-align: left;">Last Name</th>
                                <th style="padding: 12px; text-align: left;">M.I.</th>
                                <th style="padding: 12px; text-align: left;">Email Address</th>
                                <th style="padding: 12px; text-align: left;">Year Level</th>
                                <th style="padding: 12px; text-align: left;">Department</th>
                                <th style="padding: 12px; text-align: left;">Status</th>
                                <th style="padding: 12px; text-align: left;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody">
                            <?php
                            // Fetch students from the database
                            $query = "SELECT cs.student_number, cs.first_name, cs.last_name, cs.middle_name, cs.email, cs.year_level, d.id as department_id, d.name as department_name,
                                    CASE 
                                        WHEN u.id IS NOT NULL THEN 'Registered'
                                        ELSE 'Unregistered'
                                    END as status
                                    FROM cit_students cs 
                                    LEFT JOIN departments d ON cs.department_id = d.id 
                                    LEFT JOIN users u ON cs.student_number = u.student_number
                                    ORDER BY cs.last_name, cs.first_name";
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr style='border-bottom: 1px solid #E6B8AF;'>";
                                    echo "<td style='padding: 12px; color: #4A4A4A;'>" . htmlspecialchars($row['student_number']) . "</td>";
                                    echo "<td style='padding: 12px; color: #4A4A4A;'>" . htmlspecialchars($row['first_name']) . "</td>";
                                    echo "<td style='padding: 12px; color: #4A4A4A;'>" . htmlspecialchars($row['last_name']) . "</td>";
                                    echo "<td style='padding: 12px; color: #4A4A4A;'>" . htmlspecialchars($row['middle_name']) . "</td>";
                                    echo "<td style='padding: 12px; color: #4A4A4A;'>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td style='padding: 12px; color: #4A4A4A;'>" . htmlspecialchars($row['year_level']) . "</td>";
                                    echo "<td style='padding: 12px; color: #4A4A4A;' data-department-id='" . htmlspecialchars($row['department_id']) . "'>" . htmlspecialchars($row['department_name']) . "</td>";
                                    echo "<td style='padding: 12px; color: " . ($row['status'] === 'Registered' ? '#4CAF50' : '#FF5252') . ";'>" . htmlspecialchars($row['status']) . "</td>";
                                    echo "<td style='padding: 12px;'>";
                                    echo "<button class='delete-btn' data-student-number='" . htmlspecialchars($row['student_number']) . "' data-status='" . htmlspecialchars($row['status']) . "' style='background: #E6B8AF; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;'>";
                                    echo "<i class='fas fa-trash'></i> Delete";
                                    echo "</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='10' style='padding: 12px; text-align: center; color: #4A4A4A;'>No students found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Semester Setup Modal -->
        <div id="semesterSetupModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 400px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
                <h3 style="color: #E6B8AF; margin-bottom: 20px; font-family: 'Montserrat', sans-serif;">Set Semester</h3>
                <p style="color: #4A4A4A; margin-bottom: 20px; font-family: 'Century Gothic', sans-serif;">Please select the current semester for the system:</p>
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button class="semester-btn" data-semester="1" style="flex: 1; padding: 10px; background: #F5F5F5; border: 2px solid #E6B8AF; border-radius: 5px; cursor: pointer; font-family: 'Century Gothic', sans-serif;">Semester 1</button>
                    <button class="semester-btn" data-semester="2" style="flex: 1; padding: 10px; background: #F5F5F5; border: 2px solid #E6B8AF; border-radius: 5px; cursor: pointer; font-family: 'Century Gothic', sans-serif;">Semester 2</button>
                </div>
                <p style="color: #666; font-size: 0.9em; font-style: italic; font-family: 'Century Gothic', sans-serif;">Note: This will set the semester without affecting student year levels.</p>
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

                // Search functionality
                const searchInput = document.getElementById('searchInput');
                const yearLevelFilter = document.getElementById('yearLevelFilter');
                const departmentFilter = document.getElementById('departmentFilter');
                const tableBody = document.getElementById('studentsTableBody');
                const rows = tableBody.getElementsByTagName('tr');

                function filterTable() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const selectedYearLevel = yearLevelFilter.value;
                    const selectedDepartment = departmentFilter.value;
                    
                    Array.from(rows).forEach(row => {
                        const cells = row.getElementsByTagName('td');
                        let found = false;
                        let yearLevelMatch = true;
                        let departmentMatch = true;
                        
                        // Check year level filter
                        if (selectedYearLevel) {
                            const yearLevelCell = cells[5]; // Year Level is the 6th column (index 5)
                            yearLevelMatch = yearLevelCell.textContent.trim() === selectedYearLevel;
                        }
                        
                        // Check department filter
                        if (selectedDepartment) {
                            const departmentCell = cells[6]; // Department is the 7th column (index 6)
                            const departmentId = departmentCell.getAttribute('data-department-id');
                            departmentMatch = departmentId === selectedDepartment;
                        }
                        
                        // Check search term
                        if (searchTerm) {
                            for (let i = 0; i < cells.length; i++) {
                                const cellText = cells[i].textContent.toLowerCase();
                                if (cellText.includes(searchTerm)) {
                                    found = true;
                                    break;
                                }
                            }
                        } else {
                            found = true;
                        }
                        
                        row.style.display = (found && yearLevelMatch && departmentMatch) ? '' : 'none';
                    });
                }

                searchInput.addEventListener('input', filterTable);
                yearLevelFilter.addEventListener('change', filterTable);
                departmentFilter.addEventListener('change', filterTable);

                // Import button functionality
                document.getElementById('importBtn').addEventListener('click', function() {
                    // Create file input
                    const fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.accept = '.csv,.xlsx,.xls';
                    
                    fileInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const formData = new FormData();
                            formData.append('file', file);
                            
                            fetch('processStudentImport.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Students imported successfully!');
                                    location.reload();
                                } else {
                                    alert('Error importing students: ' + data.message);
                                }
                            })
                            .catch(error => {
                                alert('Error importing students: ' + error);
                            });
                        }
                    });
                    
                    fileInput.click();
                });

                // Export button functionality
                document.getElementById('exportBtn').addEventListener('click', function() {
                    window.location.href = 'exportStudents.php';
                });

                // Add semester management functionality
                document.getElementById('setSemesterBtn').addEventListener('click', function() {
                    document.getElementById('semesterSetupModal').style.display = 'block';
                });

                // Add Change Semester button functionality
                document.getElementById('updateSemesterBtn').addEventListener('click', function() {
                    if (confirm('Are you sure you want to change the semester? This will increase year levels for students when changing from Semester 2 to Semester 1.')) {
                        fetch('updateSemester.php')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Semester changed successfully!');
                                    location.reload();
                                } else {
                                    alert('Error changing semester: ' + data.message);
                                }
                            })
                            .catch(error => {
                                alert('Error changing semester: ' + error);
                            });
                    }
                });

                // Close modal when clicking outside
                document.getElementById('semesterSetupModal').addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.style.display = 'none';
                    }
                });

                // Semester setup buttons
                document.querySelectorAll('.semester-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const semester = this.getAttribute('data-semester');
                        
                        fetch('setInitialSemester.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'semester=' + semester
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Semester set successfully!');
                                location.reload();
                            } else {
                                alert('Error setting semester: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('Error setting semester: ' + error);
                        });
                    });
                });

                // Add delete functionality
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const studentNumber = this.getAttribute('data-student-number');
                        const status = this.getAttribute('data-status');
                        
                        if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                            fetch('deleteStudent.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    student_number: studentNumber,
                                    status: status
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Student deleted successfully!');
                                    location.reload();
                                } else {
                                    alert('Error deleting student: ' + data.message);
                                }
                            })
                            .catch(error => {
                                alert('Error deleting student: ' + error);
                            });
                        }
                    });
                });
            });
        </script>
</body>
</html>