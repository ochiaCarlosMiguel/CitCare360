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
    <!-- Head Section -->
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Students</title>
        <link rel="icon" type="image/png" href="../favicon.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.11/jspdf.plugin.autotable.min.js"></script>
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
                        <li class="active"><a href="student.php"><i class="fas fa-user-graduate"></i>Student List</a></li>
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
        
            <!-- Main Content -->
            <div class="main-content">
                <div class="content-card dashboard-table">
                    <div class="table-header">
                        <i class="fas fa-user-graduate"></i>
                        <h2>STUDENTS</h2>
                    </div>
                    <div class="header-actions" style="justify-content: flex-end;">
                        <!-- Search Bar -->
                        <div class="search-container">
                            <input type="text" id="searchInput" placeholder="Search by name..." />
                            <select id="searchCategory">
                                <option value="all">All</option>
                                <option value="name">Name</option>
                                <option value="department">Department</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                    </div>
                    <hr class="header-line">
                    <div class="table-container">
                        <table id="studentsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Profile</th>
                                    <th>Name</th>
                                    <th>Student Number</th>
                                    <th>Department</th>
                                    <th>Contact No.</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Fetch users from the database, including user_profile from users table
                            $query = "
                                SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, d.name AS department_name, u.student_number, u.user_profile 
                                FROM users u
                                JOIN departments d ON u.department = d.id"; // Updated to join with departments table
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>{$row['id']}</td>"; // ID
                                    echo "<td style='text-align: center;'>";
                                    // Profile Image Handling
                                    $userProfile = !empty($row['user_profile']) ? htmlspecialchars($row['user_profile']) : ''; // Fetch from users table
                                    $imagePath = "../image/{$userProfile}"; // Construct the image path

                                    // Check if the userProfile has an unwanted prefix
                                    if (strpos($userProfile, 'image/') === 0) {
                                        $userProfile = substr($userProfile, strlen('image/')); // Remove the prefix
                                        $imagePath = "../image/{$userProfile}"; // Reconstruct the path
                                    }

                                    if ($userProfile && file_exists($imagePath)) {
                                        echo "<img src='{$imagePath}' alt='Profile Image' width='50' height='50' class='profile-image' data-fullsize='{$imagePath}' style='border-radius: 50%; object-fit: cover;'>";
                                    } else {
                                        echo "<img src='../image/default.png' alt='Default Image' width='50' height='50' class='profile-image' style='border-radius: 50%; object-fit: cover;'>"; // Default image
                                        echo "<p style='color: red;'>Image not found: {$imagePath}</p>"; // Debugging message
                                    }
                                    echo "</td>";
                                    echo "<td>{$row['first_name']} {$row['last_name']}</td>"; // Name
                                    echo "<td>{$row['student_number']}</td>"; // Student Number
                                    echo "<td>{$row['department_name']}</td>"; // Department
                                    echo "<td>{$row['phone_number']}</td>"; // Contact No.
                                    echo "<td>{$row['email']}</td>"; // Email
                                    echo "<td class='actions'>";
                                    echo "<button type='button' class='action-btn view highlighted' data-id='{$row['id']}'>View</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No users found.</td></tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <!-- Custom Image Modal -->
        <div id="imageModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" style="cursor: pointer; float: right; font-size: 24px;">&times;</span>
                <img id="modalImage" src="" alt="Profile Image" style="width: 100%; border-radius: 8px;">
            </div>
        </div>

        <!-- Custom View Modal -->
        <div id="viewModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 style="flex-grow: 1;">User Details</h2>
                    <button class="modal-btn cancel-btn" id="closeViewModal" style="background: none; border: none; color: #F8B83C; font-size: 28px; cursor: pointer;">
                        &times;
                    </button>
                </div>
                <div class="modal-body">
                    <p id="userDetails"></p>
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
                background-color: #F4A261;
                color: #1E1E1E;
            }

            .submenu a:hover {
                background-color: #363636;
                padding-left: 20px;
                transition: all 0.3s ease;
            }

        /* Main Content Styles */
        .main-content {
            margin-left: 249px;
            margin-top: 60px;
            padding: 30px 40px;
            position: relative;
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
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .import-btn {
            background-color: #2d2d2d;
            color: #AEB2B7;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .import-btn input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .import-btn:hover {
            background-color: #3d3d3d;
            color: #F8B83C;
            transform: translateY(-2px);
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
            border-bottom: 1px solid #333;
            margin: 10px 0 20px 0;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            color: #F8B83C;
            font-weight: bold;
            font-size: 14px;
            padding: 15px 12px;
            text-align: left;
            font-family: 'Century Gothic', sans-serif;
            background-color: rgba(30, 30, 30, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: none;
            border-right: none;
        }

        th:last-child {
            border-right: none;
        }

        td {
            padding: 15px 12px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            border: 1px solid #333;
            border-right: none;
            border-bottom: none;
        }

        /* Add border-right to last column */
        th:last-child, td:last-child {
            border-right: none;
        }

        /* Add border-bottom to last row */
        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(45, 45, 45, 0.6);
        }

        /* First column styles */
        th:first-child, td:first-child {
            border-left: none;
        }

        /* Status cell padding adjustment for badges */
        td .status-active,
        td .status-inactive {
            display: inline-block;
            margin: -3px 0;
        }

        /* Action column alignment */
        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .action-btn {
            background: none;
            border: none;
            color: #AEB2B7;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .action-btn.edit:hover {
            color: #2196F3;
            background-color: rgba(33, 150, 243, 0.1);
        }

        .action-btn.delete:hover {
            color: #F44336;
            background-color: rgba(244, 67, 54, 0.1);
        }

        /* Responsive Design */
        @media screen and (max-width: 1400px) {
            .main-content {
                padding: 20px;
            }
        }

        /* Add these styles to your CSS if not already present */
        .status-active {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .status-inactive {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
            padding: 5px 10px;
            border-radius: 4px;
        }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 50%; /* Center horizontally */
            top: 50%; /* Center vertically */
            transform: translate(-50%, -50%); /* Adjust for half of its width and height */
            width: 90%; /* Adjust width as needed */
            max-width: 600px; /* Set a max width for the modal */
            height: auto; /* Auto height to fit content */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #1E1E1E; /* Background color for modal content */
            padding: 20px;
            border-radius: 8px;
            width: 100%; /* Full width of the modal */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease; /* Optional animation */
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
            background-color: #750605;
            color: #F8B83C;
        }

        .delete-btn:hover {
            background-color: #8f0806;
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

        /* Image Modal Specific Styles */
        .image-modal {
            background-color: rgba(0, 0, 0, 0.9);
        }

        .image-modal-content {
            background-color: transparent;
            max-width: 90%;
            width: auto;
            padding: 0;
            box-shadow: none;
        }

        .image-modal-content img {
            max-width: 100%;
            max-height: 90vh;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
        }

        .close-modal {
            position: absolute;
            right: -30px;
            top: -30px;
            color: #fff;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #F8B83C;
        }

        /* Add this style to show pointer cursor on product images */
        .product-image {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .product-image:hover {
            transform: scale(1.05);
        }

        /* Import Alert Styles */
        .import-alert {
            position: fixed;
            top: 20px;
            right: -400px; /* Start off-screen */
            padding: 20px;
            border-radius: 8px;
            color: #fff;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 2000;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        .import-alert.success {
            background-color: rgba(76, 175, 80, 0.9);
        }

        .import-alert.error {
            background-color: rgba(244, 67, 54, 0.9);
        }

        .import-alert i {
            font-size: 24px;
        }

        .import-alert .content {
            flex-grow: 1;
        }

        .import-alert .title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .import-alert .message {
            font-size: 14px;
            opacity: 0.9;
        }

        .import-alert .close-alert {
            cursor: pointer;
            padding: 5px;
            transition: transform 0.3s ease;
        }

        .import-alert .close-alert:hover {
            transform: rotate(90deg);
        }

        /* Progress animation */
        .import-alert .progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background-color: rgba(255, 255, 255, 0.7);
            width: 100%;
            transform-origin: left;
            animation: progress 3s linear forwards;
        }

        @keyframes progress {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        @keyframes slideIn {
            from { transform: translateX(100%) scale(0.5); opacity: 0; }
            to { transform: translateX(0) scale(1); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0) scale(1); opacity: 1; }
            to { transform: translateX(100%) scale(0.5); opacity: 0; }
        }

        .export-btn {
            background-color: #2d2d2d;
            color: #AEB2B7;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            background-color: #3d3d3d;
            color: #F8B83C;
            transform: translateY(-2px);
        }

        /* Adjust spacing between buttons */
        .header-actions {
            gap: 15px;
        }

        th i.fas {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
        }

        th:hover {
            background-color: rgba(40, 40, 40, 0.8);
        }

        /* Search Container Styles */
        .search-container {
            display: flex;
            gap: 10px;
            flex-grow: 1;
            max-width: 500px;
            margin-right: auto;
        }

        #searchInput {
            flex-grow: 1;
            padding: 8px 15px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        #searchInput:focus {
            outline: none;
            border-color: #F8B83C;
            background-color: #363636;
        }

        #searchCategory {
            padding: 8px 15px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #2d2d2d;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #searchCategory:focus {
            outline: none;
            border-color: #F8B83C;
            background-color: #363636;
        }

        #searchCategory option {
            background-color: #2d2d2d;
            color: #AEB2B7;
        }

        /* Group the action buttons together */
        .header-actions > :not(.search-container) {
            display: flex;
            gap: 15px;
        }

        /* Custom View Modal */
        .view-modal {
            background-color: rgba(30, 30, 30, 0.95);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .view-modal-content {
            background-color: #1E1E1E;
            padding: 20px;
            border-radius: 12px;
            width: 80%;
            max-width: 600px;
            position: relative;
        }

        .view-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(248, 184, 60, 0.3);
            padding-bottom: 15px;
        }

        .view-modal-header h2 {
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
        }

        .close-view-modal {
            color: #AEB2B7;
            font-size: 24px;
            cursor: pointer;
        }

        .view-modal-body {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .view-modal-footer {
            display: flex;
            justify-content: flex-end;
        }

        .view-modal-btn {
            background-color: #750605;
            color: #F8B83C;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
        }

        /* Styles for Highlighted Button */
        .highlighted {
            background-color: #F4A261;
            color: #1E1E1E;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .highlighted:hover {
            background-color: #DFAF4A;
        }

        .modal-divider {
            border: none;
            border-top: 1px solid rgba(248, 184, 60, 0.3);
            margin: 10px 0;
        }

        /* Enhanced Table Styles */
        .dashboard-table {
            background-color: rgba(30, 30, 30, 0.85);
            border: 1px solid rgba(248, 184, 60, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .table-container {
            margin-top: 10px;
            border: none;
            background: rgba(9, 36, 59, 0.3);
            border-radius: 12px;
            padding: 5px;
        }

        table {
            border-collapse: separate;
            border-spacing: 0 8px;
            margin-top: -8px;
            width: 100%;
        }

        th {
            background-color: rgba(9, 36, 59, 0.8);
            color: #F8B83C;
            font-weight: 600;
            padding: 16px 20px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            border: none;
            text-align: left;
            font-family: 'Century Gothic', sans-serif;
        }

        td {
            background-color: rgba(45, 45, 45, 0.6);
            padding: 16px 20px;
            border: none;
            transition: all 0.3s ease;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            vertical-align: middle;
        }

        tr:hover td {
            background-color: rgba(45, 45, 45, 0.8);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Action Buttons Enhancement */
        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            min-width: 160px;
        }

        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 5px;
            white-space: nowrap;
            min-width: 70px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
        }

        .action-btn.edit {
            background-color: rgba(33, 150, 243, 0.1);
            color: #2196F3;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }

        .action-btn.edit:hover {
            background-color: rgba(33, 150, 243, 0.2);
            border-color: #2196F3;
        }

        .action-btn.delete {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .action-btn.delete:hover {
            background-color: rgba(244, 67, 54, 0.2);
            border-color: #F44336;
        }

        /* Profile Image Styles */
        td img.profile-image {
            border: 2px solid rgba(248, 184, 60, 0.3);
            transition: all 0.3s ease;
            border-radius: 50%;
            object-fit: cover;
        }

        td img.profile-image:hover {
            transform: scale(1.1);
            border-color: #F8B83C;
            box-shadow: 0 0 15px rgba(248, 184, 60, 0.3);
        }

        /* Enhanced Modal Image Styles */
        #imageModal {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.9); /* Darker background for better contrast */
            padding: 20px; /* Add padding around the modal */
        }

        #modalImage {
            max-width: 90%; /* Limit the width of the image */
            max-height: 90vh; /* Limit the height of the image */
            border-radius: 8px; /* Rounded corners for the image */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5); /* Subtle shadow for depth */
        }

        /* Enhanced User Details Modal Styles */
        .view-modal {
            background-color: rgba(30, 30, 30, 0.95); /* Darker background for the modal */
            border-radius: 12px; /* Rounded corners for the modal */
            padding: 20px; /* Add padding inside the modal */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5); /* Shadow for depth */
        }

        .view-modal-header {
            border-bottom: 1px solid rgba(248, 184, 60, 0.3); /* Subtle border for separation */
            padding-bottom: 15px; /* Padding below the header */
        }

        .view-modal-body {
            color: #AEB2B7; /* Text color for better readability */
            font-family: 'Century Gothic', sans-serif; /* Consistent font */
            line-height: 1.6; /* Improved line height for readability */
        }

        .modal-divider {
            border: none; /* Remove default border */
            border-top: 1px solid rgba(248, 184, 60, 0.3); /* Add a top border for separation */
            margin: 10px 0; /* Add margin for spacing */
        }
        </style>

        <!-- Scripts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add real-time date and time update function
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

                // Image click event
                const profileImages = document.querySelectorAll('.profile-image');
                const imageModal = document.getElementById('imageModal');
                const modalImage = document.getElementById('modalImage');
                const closeModal = document.querySelector('.close');

                profileImages.forEach(image => {
                    image.addEventListener('click', function() {
                        modalImage.src = this.dataset.fullsize; // Set the modal image source
                        imageModal.style.display = 'block'; // Show the modal
                    });
                });

                // Close modal when clicking on the close button
                closeModal.addEventListener('click', function() {
                    imageModal.style.display = 'none';
                });

                // Close modal when clicking outside of the image
                window.addEventListener('click', function(event) {
                    if (event.target === imageModal) {
                        imageModal.style.display = 'none';
                    }
                });

                // Add event listener for View button
                const viewButtons = document.querySelectorAll('.action-btn.view');
                const viewModal = document.getElementById('viewModal');
                const userDetails = document.getElementById('userDetails');
                const closeViewModal = document.getElementById('closeViewModal');

                viewButtons.forEach(button => {
                    button.addEventListener('click', function(event) {
                        event.preventDefault(); // Prevent the default button action
                        const userId = this.dataset.id;
                        console.log('Fetching details for user ID:', userId); // Debug log
                        // Fetch user details from multiple tables
                        fetch(`getUserDetails.php?id=${userId}`)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                userDetails.innerHTML = `
                                    <strong>Name:</strong> ${data.first_name} ${data.middle_name} ${data.last_name}<br>
                                    <strong>Username:</strong> ${data.user_name}<br>
                                    <strong>Email:</strong> ${data.email}<br>
                                    <strong>Phone Number:</strong> ${data.phone_number}<br>
                                    <strong>Department:</strong> ${data.department}<br>
                                    <strong>Student Number:</strong> ${data.student_number}<br>
                                    <strong>Gender:</strong> ${data.gender}<br>
                                    <strong>Age:</strong> ${data.age}<br>
                                    <strong>Place of Birth:</strong> ${data.place_of_birth}<br>
                                    <strong>Civil Status:</strong> ${data.civil_status}<br>
                                    <strong>Nationality:</strong> ${data.nationality}<br>
                                    <strong>Religion:</strong> ${data.religion}<br>
                                    <strong>Height:</strong> ${data.height}<br>
                                    <strong>Weight:</strong> ${data.weight}<br>
                                    <strong>Blood Type:</strong> ${data.blood_type}<br>
                                    <hr class="modal-divider">
                                    <strong>Address:</strong> ${data.house_number}, ${data.barangay}, ${data.municipality}, ${data.province}, ${data.zip_code}<br>
                                    <hr class="modal-divider">
                                    <strong>Contact Person 1:</strong> ${data.contact_person_1_first_name} ${data.contact_person_1_last_name}<br>
                                    <strong>Middle Name:</strong> ${data.contact_person_1_middle_name || 'N/A'}<br>
                                    <strong>Relationship:</strong> ${data.contact_person_1_relationship}<br>
                                    <strong>Telephone Number:</strong> ${data.contact_person_1_phone || 'N/A'}<br>
                                    <strong>Contact Number:</strong> ${data.contact_person_1_contact || 'N/A'}<br>
                                    <strong>Email:</strong> ${data.contact_person_1_email}<br>
                                    <strong>Address:</strong> ${data.contact_person_1_address}<br>
                                    <hr class="modal-divider">
                                    <strong>Contact Person 2:</strong> ${data.contact_person_2_first_name} ${data.contact_person_2_last_name}<br>
                                    <strong>Middle Name:</strong> ${data.contact_person_2_middle_name || 'N/A'}<br>
                                    <strong>Relationship:</strong> ${data.contact_person_2_relationship}<br>
                                    <strong>Telephone Number:</strong> ${data.contact_person_2_phone || 'N/A'}<br>
                                    <strong>Contact Number:</strong> ${data.contact_person_2_contact || 'N/A'}<br>
                                    <strong>Email:</strong> ${data.contact_person_2_email}<br>
                                    <strong>Address:</strong> ${data.contact_person_2_address}<br>
                                `;
                                viewModal.style.display = 'block'; // Show the modal
                            })
                            .catch(error => console.error('Error fetching user details:', error));
                    });
                });

                // Close modal functionality
                closeViewModal.addEventListener('click', function() {
                    viewModal.style.display = 'none'; // Hide the modal
                });

                // Close modal when clicking outside of it
                window.addEventListener('click', function(event) {
                    if (event.target === viewModal) {
                        viewModal.style.display = 'none';
                    }
                });

                // Search functionality
                const searchInput = document.getElementById('searchInput');
                const searchCategory = document.getElementById('searchCategory');
                const tableRows = document.querySelectorAll('tbody tr');

                searchInput.addEventListener('input', function() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const selectedCategory = searchCategory.value;

                    tableRows.forEach(row => {
                        const name = row.querySelector('td:nth-child(3)').textContent.toLowerCase(); // Name column
                        const department = row.querySelector('td:nth-child(5)').textContent.toLowerCase(); // Department column
                        const email = row.querySelector('td:nth-child(7)').textContent.toLowerCase(); // Email column

                        let isVisible = false;

                        if (selectedCategory === 'all') {
                            isVisible = name.includes(searchTerm) || department.includes(searchTerm) || email.includes(searchTerm);
                        } else if (selectedCategory === 'name') {
                            isVisible = name.includes(searchTerm);
                        } else if (selectedCategory === 'department') {
                            isVisible = department.includes(searchTerm);
                        } else if (selectedCategory === 'email') {
                            isVisible = email.includes(searchTerm);
                        }

                        row.style.display = isVisible ? '' : 'none'; // Show or hide the row
                    });
                });
            });
        </script>
        <!-- Add before the closing body tag -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>

        </script>
    </body>
</html>