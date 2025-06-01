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

// Fetch meeting schedules with incident and student details
$query = "SELECT ms.*, i.full_name as student_name, i.student_number, i.email, 
          au.name as admin_name, i.subject_report
          FROM meeting_schedules ms
          JOIN incidents i ON ms.incident_id = i.id
          JOIN admin_users au ON ms.admin_id = au.id
          ORDER BY ms.meeting_date DESC, ms.meeting_time DESC";
$result = $conn->query($query);
$meetings = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $meetings[] = $row;
    }
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>Meeting Schedules</title>
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
                color: #09243B;
            }

            /* Topbar Styles */
            .topbar {
                position: fixed;
                top: 0;
                left: 249px;
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

            .profile-container img {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                transition: all 0.3s ease;
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
            }

            .content-card {
                background-color: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(9, 36, 59, 0.1);
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .table-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
            }

            .table-header i {
                color: #09243B;
                font-size: 24px;
            }

            .table-header h2 {
                color: #09243B;
                font-family: 'Century Gothic', sans-serif;
                font-size: 20px;
                font-weight: bold;
            }

            /* Table Styles */
            .table-container {
                overflow-x: auto;
                border-radius: 8px;
                border: 1px solid rgba(9, 36, 59, 0.1);
            }

            table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }

            th {
                color: #09243B;
                font-weight: bold;
                font-size: 14px;
                padding: 15px 12px;
                text-align: left;
                font-family: 'Century Gothic', sans-serif;
                background-color: rgba(9, 36, 59, 0.05);
                border: 1px solid rgba(9, 36, 59, 0.1);
            }

            td {
                padding: 15px 12px;
                color: #333333;
                font-family: 'Century Gothic', sans-serif;
                font-size: 14px;
                border: 1px solid rgba(9, 36, 59, 0.1);
            }

            tr:hover td {
                background-color: rgba(9, 36, 59, 0.05);
            }

            /* Status Badge Styles */
            .status-badge {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                display: inline-block;
            }

            .status-pending {
                background-color: rgba(255, 193, 7, 0.1);
                color: #FFC107;
            }

            .status-approved {
                background-color: rgba(76, 175, 80, 0.1);
                color: #4CAF50;
            }

            .status-rejected {
                background-color: rgba(244, 67, 54, 0.1);
                color: #F44336;
            }

            /* Action Buttons */
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
                color: #333333;
            }

            .action-btn.approve {
                background-color: rgba(76, 175, 80, 0.1);
                color: #4CAF50;
                border: 1px solid rgba(76, 175, 80, 0.3);
            }

            .action-btn.reject {
                background-color: rgba(244, 67, 54, 0.1);
                color: #F44336;
                border: 1px solid rgba(244, 67, 54, 0.3);
            }

            .action-btn:hover {
                transform: translateY(-2px);
            }

            .action-btn.approve:hover {
                background-color: rgba(76, 175, 80, 0.2);
            }

            .action-btn.reject:hover {
                background-color: rgba(244, 67, 54, 0.2);
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
            }

            .modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background-color: #FFFFFF;
                padding: 20px;
                border-radius: 12px;
                width: 95%;
                max-width: 500px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid rgba(9, 36, 59, 0.1);
            }

            .modal-header h2 {
                color: #09243B;
                font-family: 'Century Gothic', sans-serif;
                font-size: 20px;
                margin: 0;
            }

            .modal-body {
                margin-bottom: 20px;
            }

            .modal-body p {
                color: #333333;
                font-family: 'Century Gothic', sans-serif;
                margin: 0 0 10px 0;
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
                background-color: #E8E8E8;
                color: #333333;
            }

            .confirm-btn {
                background-color: #09243B;
                color: #FFFFFF;
            }

            .cancel-btn:hover {
                background-color: #D8D8D8;
            }

            .confirm-btn:hover {
                background-color: #0A2B4A;
            }

            /* Alert Styles */
            .alert {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 8px;
                color: #FFFFFF;
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
            <li class="active">
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-card">
            <div class="table-header">
                <i class="fas fa-calendar-alt"></i>
                <h2>MEETING SCHEDULES</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>STUDENT</th>
                            <th>STUDENT NUMBER</th>
                            <th>EMAIL</th>
                            <th>SUBJECT</th>
                            <th>ADMIN</th>
                            <th>DATE</th>
                            <th>TIME</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meetings as $meeting): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($meeting['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($meeting['student_number']); ?></td>
                                <td><?php echo htmlspecialchars($meeting['email']); ?></td>
                                <td><?php echo htmlspecialchars($meeting['subject_report']); ?></td>
                                <td><?php echo htmlspecialchars($meeting['admin_name']); ?></td>
                                <td><?php echo date('F j, Y', strtotime($meeting['meeting_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($meeting['meeting_time'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($meeting['status']); ?>">
                                        <?php echo htmlspecialchars($meeting['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($meeting['status'] === 'PENDING'): ?>
                                        <button class="action-btn approve" onclick="updateStatus(<?php echo $meeting['id']; ?>, 'APPROVED')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="action-btn reject" onclick="updateStatus(<?php echo $meeting['id']; ?>, 'REJECTED')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Status Update</h2>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <span id="statusAction"></span> this meeting schedule?</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="modal-btn confirm-btn" onclick="confirmStatusUpdate()">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global variables for status update
        let currentMeetingId;
        let newStatus;

        // Status Update Functions
        function updateStatus(meetingId, status) {
            currentMeetingId = meetingId;
            newStatus = status;
            document.getElementById('statusAction').textContent = status.toLowerCase();
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        function confirmStatusUpdate() {
            fetch('updateMeetingStatus.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    meeting_id: currentMeetingId,
                    status: newStatus
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Status updated successfully', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert(data.message || 'Error updating status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while updating the status', 'error');
            });

            closeModal();
        }

        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                ${message}
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }

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

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('statusModal');
                if (event.target === modal) {
                    closeModal();
                }
            };
        });
    </script>
</body>
</html> 