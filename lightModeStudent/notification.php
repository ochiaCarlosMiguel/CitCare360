<?php
// Start the session
session_start();

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if user_id is not set
    header("Location: ../studentPortal/login.php");
    exit;
}

// Assuming you have a database connection file
include '../connection/connection.php';

// Fetch the user's first name from the database
$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session
$query = "SELECT first_name, user_profile FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $first_name = $user['first_name'];
    $user_profile = '../image/' . $user['user_profile'];
} else {
    echo "User not found.";
    // Handle the case where the user is not found in the database
    exit;
}

// Modify the notification query to join with incidents table and filter by user_id
$notification_query = "
    SELECT ih.incident_id, ih.previous_status, ih.new_status, ih.changed_at, ih.is_read 
    FROM incident_history ih
    JOIN incidents i ON ih.incident_id = i.id
    WHERE i.user_id = ?";
$notification_stmt = $conn->prepare($notification_query);
$notification_stmt->bind_param("i", $user_id);
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();

// Modify the counseling notifications query to join with counseling_appointments and filter by user_id
$counseling_query = "
    SELECT ch.counseling_id, ch.previous_status, ch.new_status, ch.changed_at, ch.is_read 
    FROM counseling_history ch
    JOIN counseling_appointments ca ON ch.counseling_id = ca.id
    WHERE ca.user_id = ?";
$counseling_stmt = $conn->prepare($counseling_query);
$counseling_stmt->bind_param("i", $user_id);
$counseling_stmt->execute();
$counseling_result = $counseling_stmt->get_result();

// Modify the unread count query to only count notifications for the current user
$unread_query = "
    SELECT 
    (SELECT COUNT(*) 
     FROM incident_history ih 
     JOIN incidents i ON ih.incident_id = i.id 
     WHERE ih.is_read = '0' AND i.user_id = ?) +
    (SELECT COUNT(*) 
     FROM counseling_history ch
     JOIN counseling_appointments ca ON ch.counseling_id = ca.id
     WHERE ch.is_read = '0' AND ca.user_id = ?) as total_unread";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("ii", $user_id, $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['total_unread'];

// Set the current page variable
$currentPage = 'home'; // Set this page as the current page

// Modify the delete old notifications query to only delete notifications for the current user
$delete_old_incidents = "
    DELETE ih FROM incident_history ih
    JOIN incidents i ON ih.incident_id = i.id
    WHERE ih.is_read = '1' 
    AND ih.changed_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND i.user_id = ?";
$delete_stmt = $conn->prepare($delete_old_incidents);
$delete_stmt->bind_param("i", $user_id);
$delete_stmt->execute();

// Modify the delete old counseling notifications query to only delete notifications for the current user
$delete_old_counseling = "
    DELETE ch FROM counseling_history ch
    JOIN counseling_appointments ca ON ch.counseling_id = ca.id
    WHERE ch.is_read = '1' 
    AND ch.changed_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND ca.user_id = ?";
$delete_counseling_stmt = $conn->prepare($delete_old_counseling);
$delete_counseling_stmt->bind_param("i", $user_id);
$delete_counseling_stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link rel="icon" type="image/png" href="../favicon.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    * { 
      box-sizing: border-box; 
      scroll-behavior: smooth; 
    }
    body { 
      margin: 0; 
      font-family: 'Poppins', sans-serif; 
      background: url('../image/bg.png') no-repeat center center fixed;
      background-size: cover; 
      color: #2d3436; 
      line-height: 1.6; 
    }
    
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.5);
      z-index: -1;
    }
    a { 
      text-decoration: none; 
      color: inherit; 
      transition: color 0.3s; 
    }
    
    /* Navbar Styles */
    .navbar { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      padding: 15px 50px; 
      position: sticky; 
      top: 0; 
      z-index: 1000; 
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .navbar .logo {
      font-size: 36px;
      font-weight: 700;
      color: #2d3436;
      text-shadow: 1px 1px #ffffff;
    }
    .navbar .logo span.cit {
      color: #0984e3;
    }
    .nav-menu { 
      display: flex; 
      gap: 30px; 
      list-style: none; 
      margin: 0 auto;
    }
    .nav-menu li a { 
      color: #2d3436;
      padding: 10px 15px;
      border-radius: 4px; 
      transition: background 0.3s, color 0.3s;
      font-size: 18px;
    }
    .nav-menu li a:hover { 
      background: rgba(9, 132, 227, 0.1);
      color: #0984e3;
    }
    .nav-menu li a.active {
      background: rgba(9, 132, 227, 0.15);
      color: #0984e3;
      border-radius: 4px;
    }

    @media (min-width: 1024px) {
      .navbar {
        padding: 15px 30px; /* Adjust padding for larger screens */
      }
      .nav-menu li a {
        font-size: 16px; /* Adjust font size for larger screens */
        padding: 8px 12px; /* Adjust padding for larger screens */
      }
      .login-btn {
        padding: 8px 20px; /* Adjust padding for larger screens */
      }
    }

    @media (min-width: 775px) and (max-width: 1023px) {
      .navbar {
        padding: 10px 20px; /* Adjust padding for medium screens */
      }
      .nav-menu li a {
        font-size: 16px; /* Adjust font size for medium screens */
        padding: 8px 10px; /* Adjust padding for medium screens */
      }
      .login-btn {
        padding: 8px 15px; /* Adjust padding for medium screens */
      }
    }

    .icon-button {
      background: linear-gradient(135deg, #0984e3, #74b9ff);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      cursor: pointer;
      transition: all 0.3s;
    }
    .icon-button:hover {
      background: linear-gradient(135deg, #74b9ff, #0984e3);
      transform: scale(1.1);
    }

    
    @media (max-width: 768px) {
      .navbar {
        display: none; /* Hide the desktop navbar on mobile */
      }
      .mobile-navbar {
        display: flex; /* Show mobile navbar */
        justify-content: space-around; /* Space out buttons */
        background: linear-gradient(135deg, #a8e6cf, #74b9ff);
        padding: 10px 0; /* Add padding */
        position: fixed; /* Fix to the bottom */
        bottom: 0;
        width: 100%;
        z-index: 1000; /* Ensure it stays above other content */
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1); /* Add shadow for depth */
        transition: transform 0.3s ease; /* Add transition for smooth animation */
      }
      .mobile-navbar .icon-button {
        background: none;
        color: #2d3436;
        font-size: 24px;
        padding: 10px;
        text-align: center;
        border: none;
        border-radius: 0;
      }
      .mobile-navbar .icon-button div {
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      .mobile-navbar .icon-button span {
        font-size: 12px;
        margin-top: 4px;
      }
      .mobile-navbar .icon-button.active {
        color: #0984e3;
      }
      .mobile-navbar .icon-button:hover {
        color: #0984e3;
      }
      .nav-menu { 
        flex-direction: column;
        gap: 10px;
      }
      .service-container { 
        flex-direction: column;
      }
      .service-box { width: 90%; }
      .footer {
        display: none;
      }
      body {
        font-size: 14px; /* Slightly smaller font size on mobile */
      }
      h1 {
        font-size: 28px; /* Adjust heading size for mobile */
      }
      h2 {
        font-size: 24px; /* Adjust subheading size for mobile */
      }
      h3 {
        font-size: 20px; /* Adjust smaller heading size for mobile */
      }
      p {
        font-size: 14px; /* Adjust paragraph size for mobile */
      }
      .services {
        background-size: 150% 150%; /* Reduce background size for mobile */
        background-position: center; /* Center the background */
        padding: 0px 20px 0px 50px; /* Adjust padding for mobile view */
      }
    }

    @media (min-width: 769px) {
      .mobile-navbar {
        display: none;
      }
    }

    .notification {
      margin-right: 20px;
      margin-left: -180px;
    }

    .user-profile {
      position: relative;
      display: flex;
      align-items: center;
      margin-left: auto;
    }

    .profile-container {
      display: flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      padding: 12px;
      border-radius: 25px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      border: 1px solid #74b9ff;
    }

    .profile-icon {
      background: none;
      border: none;
      color: #2d3436;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      top: 100%;
      right: 0;
      background: linear-gradient(135deg, #ffffff, #e3f2fd);
      min-width: 160px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      z-index: 1;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #74b9ff;
    }

    .dropdown-content a {
      color: #2d3436;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      transition: background 0.3s;
    }

    .dropdown-content a:hover {
      background-color: #e3f2fd;
    }

    .profile-icon img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #fff;
    }



    /* Header styles */
    .mobile-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px 20px;
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      color: #2d3436;
    }

    .logo-container {
      display: flex;
      align-items: center; /* Center the logo and text vertically */
    }

    .mobile-header .logo {
      max-height: 60px; /* Increased maximum height for the logo */
      height: auto; /* Maintain aspect ratio */
      width: auto; /* Maintain aspect ratio */
      margin-right: 10px; /* Space between logo and text */
      color: #74b9ff; /* Adjust logo color for better visibility in dark mode */
    }

    .text-logo {
      font-size: 24px; /* Font size for the text logo */
      color: #74b9ff; /* Actual color for the text logo */
      font-weight: bold; /* Make the text bold */
      line-height: 1; /* Adjust line height for better alignment */
    }

    .mobile-header .logout-button {
      background: none; /* No background for button */
      border: none; /* No border */
      color: #2d3436; /* Ensure logout button has light color */
      font-size: 24px; /* Size for the logout icon */
      cursor: pointer; /* Pointer cursor for button */
    }

    .mobile-navbar {
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      color: #2d3436;
      box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
    }

    .mobile-navbar .icon-button {
      color: #2d3436; /* Ensure icon buttons have light color */
    }

    .mobile-navbar .icon-button:hover {
      background: rgba(9, 132, 227, 0.1);
    }

    /* Additional styles for dark mode */
    body {
      background-color: #121212; /* Dark background for the entire page */
      color: #e0e0e0; /* Light text color for the entire page */
    }

    /* Adjust other elements as needed for dark mode */
    .footer {
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      color: #2d3436;
      border-top: 1px solid #74b9ff;
      padding: 20px 50px; 
    }

    /* Adjust other sections as necessary */
    .services {
      background: #222; /* Dark background for services section */
      color: #e0e0e0; /* Light text color for services */
    }

    .notification-item {
      display: flex;
      align-items: center;
      padding: 15px;
      border-radius: 8px;
      background: #2c3e50;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      margin-bottom: 10px;
      transition: background 0.3s;
    }

    .notification-text {
      flex-grow: 1;
      color: #ecf0f1;
    }

    .notification-text span {
      margin: 0 5px;
    }

    /* Base styles for notification container */
    .notification-container {
      width: 400px; /* Original size for desktop */
      height: 600px; /* Original height for desktop */
      overflow-y: auto; 
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      border-radius: 8px; 
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3); 
      padding: 20px; 
      color: #2d3436;
    }

    /* Add this CSS for mobile responsiveness */
    @media (max-width: 768px) {
      .notification-container {
        width: 90%; /* Make the container take 90% of the screen width */
        max-width: 400px; /* Set a maximum width for larger screens */
        height: auto; /* Allow height to adjust based on content */
        max-height: 80vh; /* Set a maximum height for better visibility */
        padding: 10px; /* Adjust padding for mobile view */
      }
      .notification-item {
        padding: 10px; /* Adjust padding for notification items */
      }
    }
  </style>
</head>
<body>
  <header class="mobile-header">
    <div class="logo-container">
      <img src="<?php echo htmlspecialchars('../image/logo.png'); ?>" alt="Logo" class="logo"> <!-- Image Logo -->
      <span class="text-logo">CIT 360</span> <!-- Text Logo -->
    </div>
    <button class="logout-button" onclick="redirectToLogout()">
      <i class="fa-solid fa-sign-out-alt"></i> <!-- Updated Logout icon -->
    </button>
  </header>

  <div class="overlay"></div> <!-- Overlay div -->
  <nav class="navbar">
    <div class="logo" style="display: flex; align-items: center;">
      <img src="<?php echo htmlspecialchars('../image/logo.png'); ?>" alt="Logo" style="height: 50px; margin-right: 10px;">
      <span class="cit" style="font-size: 28px; margin-right: 5px;">CIT</span>
      <span style="font-size: 24px;">CARE 360</span>
    </div>
    <ul class="nav-menu">
      <li><a href="homePage.php">Home</a></li>
      <li><a href="reportIncident.php">Report</a></li>
      <li><a href="reportStatus.php">Report Status</a></li>
      <!-- <li><a href="#contact">Contact</a></li> -->
    </ul>
    <div class="user-profile">
    <div class="notification" style="position: relative;">
        <button class="icon-button" onclick="redirectToNotifications()" style="position: relative;">
          <i class="fa-solid fa-bell"></i>
          <span class="notification-badge" style="position: absolute; top: -5px; right: -10px; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px;">
            <?php echo $unread_count > 0 ? htmlspecialchars($unread_count) : '0'; ?>
          </span>
        </button>
      </div>

      <div class="profile-container">
        <button class="icon-button profile-icon">
        <img src="<?php echo htmlspecialchars($user_profile); ?>" alt="User Profile">
        </button>
        <span style="color: #fff; margin-left: 10px;"><?php echo htmlspecialchars($first_name); ?></span>
        <button class="icon-button profile-icon" onclick="toggleDropdown()">
          <i class="fa-solid fa-caret-down"></i>
        </button>
      </div>
      <div class="dropdown-content" id="profileDropdown">
        <a href="profile.php">Profile</a>
        <a href="settings.php">Settings</a>
        <a href="../studentPortal/logout.php">Sign Out</a>
      </div>
    </div>
  </nav>

  <div class="main-content" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 100px);"> <!-- Adjust min-height to fit above footer -->
    <div class="notification-container" id="notificationContainer" style="width: 400px; height: 600px; overflow-y: auto; background: linear-gradient(135deg, #a8e6cf, #74b9ff); border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3); padding: 20px; color: #2d3436;">
      <h2 style="text-align: center; color: #2d3436;">Notifications</h2> <!-- Header for Notifications -->
      
      <!-- Tab Buttons -->
      <div style="display: flex; justify-content: flex-start; margin-bottom: 20px;">
        <button id="newTab" onclick="showTab('new')" style="background: linear-gradient(135deg, #0984e3, #74b9ff); color: #ffffff; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer; margin-right: 10px;">New</button>
        <button id="readTab" onclick="showTab('read')" style="background: linear-gradient(135deg, #34495e, #74b9ff); color: #ffffff; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer;">Read</button>
      </div>

      <div id="newNotifications" style="display: block;"> <!-- New Notifications Section -->
        <?php
        // Reset the result pointer to fetch again for unread notifications
        $notification_result->data_seek(0);
        $counseling_result->data_seek(0);
        
        // Initialize counters for unread notifications
        $unreadIncidentCount = 0;
        $unreadCounselingCount = 0;
        
        // Count unread incident notifications
        while ($notification = $notification_result->fetch_assoc()) {
            if ($notification['is_read'] == '0') {
                $unreadIncidentCount++;
            }
        }
        
        // Count unread counseling notifications
        while ($counseling = $counseling_result->fetch_assoc()) {
            if ($counseling['is_read'] == '0') {
                $unreadCounselingCount++;
            }
        }
        
        // If there are no unread notifications, display message
        if ($unreadIncidentCount == 0 && $unreadCounselingCount == 0) {
            echo '<div style="text-align: center; padding: 20px; color: #bbb;">
                    <i class="fa-regular fa-bell-slash" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>No new notifications</p>
                  </div>';
        } else {
            // Reset pointers again for displaying notifications
            $notification_result->data_seek(0);
            $counseling_result->data_seek(0);
            
            // Display unread incident notifications
            while ($notification = $notification_result->fetch_assoc()): 
                if ($notification['is_read'] == '0'):
                    ?>
                    <div class="notification-item" onclick="markAsRead(this, '<?php echo htmlspecialchars($notification['incident_id']); ?>')" style="display: flex; align-items: center; padding: 15px; border-radius: 8px; background: #2c3e50; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 10px; transition: background 0.3s;">
                        <i class="fa-solid fa-exclamation-triangle" style="color: #ecf0f1; font-size: 50px; margin-right: 15px;"></i>
                        <div class="notification-text" style="flex-grow: 1; color: #ecf0f1;">
                            <p style="margin: 0; font-weight: bold;">Incident Update</p>
                            <div style="font-size: 12px; color: #bbb;">
                                <span style="text-decoration: line-through;"><?php echo htmlspecialchars($notification['previous_status']); ?></span>
                                <span style="margin: 0 5px;">→</span>
                                <span><?php echo htmlspecialchars($notification['new_status']); ?></span>
                            </div>
                            <span class="notification-date" style="font-size: 12px; color: #bbb;">Changed At: <?php echo htmlspecialchars($notification['changed_at']); ?></span>
                        </div>
                        <span class="notification-bullet" style="background: red; width: 12px; height: 12px; border-radius: 50%; display: inline-block;"></span>
                    </div>
                    <?php
                endif;
            endwhile;

            // Display unread counseling notifications
            while ($counseling = $counseling_result->fetch_assoc()):
                if ($counseling['is_read'] == '0'):
                    ?>
                    <div class="notification-item" onclick="markCounselingAsRead(this, '<?php echo htmlspecialchars($counseling['counseling_id']); ?>')" style="display: flex; align-items: center; padding: 15px; border-radius: 8px; background: #2c3e50; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 10px; transition: background 0.3s;">
                        <i class="fa-solid fa-user-md" style="color: #ecf0f1; font-size: 50px; margin-right: 15px;"></i>
                        <div class="notification-text" style="flex-grow: 1; color: #ecf0f1;">
                            <p style="margin: 0; font-weight: bold;">Counseling Update</p>
                            <div style="font-size: 12px; color: #bbb;">
                                <span style="text-decoration: line-through;"><?php echo htmlspecialchars($counseling['previous_status']); ?></span>
                                <span style="margin: 0 5px;">→</span>
                                <span><?php echo htmlspecialchars($counseling['new_status']); ?></span>
                            </div>
                            <span class="notification-date" style="font-size: 12px; color: #bbb;">Changed At: <?php echo htmlspecialchars($counseling['changed_at']); ?></span>
                        </div>
                        <span class="notification-bullet" style="background: red; width: 12px; height: 12px; border-radius: 50%; display: inline-block;"></span>
                    </div>
                    <?php
                endif;
            endwhile;
        }
        ?>
      </div>

      <div id="readNotifications" style="display: none;"> <!-- Read Notifications Section -->
        <?php 
        // Reset the result pointers
        $notification_result->data_seek(0);
        $counseling_result->data_seek(0);
        
        // Initialize counters for read notifications
        $readIncidentCount = 0;
        $readCounselingCount = 0;
        
        // Count read incident notifications
        while ($notification = $notification_result->fetch_assoc()) {
            if ($notification['is_read'] == '1') {
                $readIncidentCount++;
            }
        }
        
        // Count read counseling notifications
        while ($counseling = $counseling_result->fetch_assoc()) {
            if ($counseling['is_read'] == '1') {
                $readCounselingCount++;
            }
        }
        
        // If there are no read notifications, display message
        if ($readIncidentCount == 0 && $readCounselingCount == 0) {
            echo '<div style="text-align: center; padding: 20px; color: #bbb;">
                    <i class="fa-regular fa-bell-slash" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>No read notifications</p>
                  </div>';
        } else {
            // Reset pointers again for displaying notifications
            $notification_result->data_seek(0);
            $counseling_result->data_seek(0);
            
            // Display read incident notifications
            while ($notification = $notification_result->fetch_assoc()):
                if ($notification['is_read'] == '1'):
                    // Calculate time difference
                    $changed_time = new DateTime($notification['changed_at']);
                    $current_time = new DateTime();
                    $time_diff = $current_time->diff($changed_time);
                    $days_remaining = 7 - $time_diff->days;
                    ?>
                    <div class="notification-item" style="display: flex; align-items: center; padding: 15px; border-radius: 8px; background: #34495e; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 10px; transition: background 0.3s;">
                        <i class="fa-solid fa-exclamation-triangle" style="color: #ecf0f1; font-size: 50px; margin-right: 15px;"></i>
                        <div class="notification-text" style="flex-grow: 1; color: #ecf0f1;">
                            <p style="margin: 0; font-weight: bold;">Incident Update</p>
                            <div style="font-size: 12px; color: #bbb;">
                                <span style="text-decoration: line-through;"><?php echo htmlspecialchars($notification['previous_status']); ?></span>
                                <span style="margin: 0 5px;">→</span>
                                <span><?php echo htmlspecialchars($notification['new_status']); ?></span>
                            </div>
                            <span class="notification-date" style="font-size: 12px; color: #bbb;">Changed At: <?php echo htmlspecialchars($notification['changed_at']); ?></span>
                            <div class="time-remaining" style="font-size: 11px; color: <?php echo $days_remaining <= 2 ? '#ff6b6b' : '#bbb'; ?>; margin-top: 5px;">
                                Will be deleted in <?php echo $days_remaining; ?> day<?php echo $days_remaining != 1 ? 's' : ''; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                endif;
            endwhile;

            // Display read counseling notifications
            while ($counseling = $counseling_result->fetch_assoc()):
                if ($counseling['is_read'] == '1'):
                    // Calculate time difference
                    $changed_time = new DateTime($counseling['changed_at']);
                    $current_time = new DateTime();
                    $time_diff = $current_time->diff($changed_time);
                    $days_remaining = 7 - $time_diff->days;
                    ?>
                    <div class="notification-item" style="display: flex; align-items: center; padding: 15px; border-radius: 8px; background: #34495e; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 10px; transition: background 0.3s;">
                        <i class="fa-solid fa-user-md" style="color: #ecf0f1; font-size: 50px; margin-right: 15px;"></i>
                        <div class="notification-text" style="flex-grow: 1; color: #ecf0f1;">
                            <p style="margin: 0; font-weight: bold;">Counseling Update</p>
                            <div style="font-size: 12px; color: #bbb;">
                                <span style="text-decoration: line-through;"><?php echo htmlspecialchars($counseling['previous_status']); ?></span>
                                <span style="margin: 0 5px;">→</span>
                                <span><?php echo htmlspecialchars($counseling['new_status']); ?></span>
                            </div>
                            <span class="notification-date" style="font-size: 12px; color: #bbb;">Changed At: <?php echo htmlspecialchars($counseling['changed_at']); ?></span>
                            <div class="time-remaining" style="font-size: 11px; color: <?php echo $days_remaining <= 2 ? '#ff6b6b' : '#bbb'; ?>; margin-top: 5px;">
                                Will be deleted in <?php echo $days_remaining; ?> day<?php echo $days_remaining != 1 ? 's' : ''; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                endif;
            endwhile;
        }
        ?>
      </div>
    </div>
  </div>

  <footer id="contact" class="footer">
    <div class="footer-content" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px;">
      <p style="margin: 0; font-size: 12px; color: #f4a261;">© 2025 - BULSU CIT - MALOLOS</p>
      <p style="margin: 0; font-size: 12px;">Developed by: CIT 360</p>
    </div>
  </footer>

  <nav class="mobile-navbar">
    <button class="icon-button <?php echo $currentPage === 'home' ? 'active' : ''; ?>" onclick="redirectToHome()">
      <div>
        <i class="fa-solid fa-home" style="color: #2d3436;"></i>
        <span>Home</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'report' ? 'active' : ''; ?>" onclick="redirectToReportIncident()">
      <div>
        <i class="fa-solid fa-flag" style="color: #2d3436;"></i>
        <span>Report</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'counseling' ? 'active' : ''; ?>" onclick="redirectToCounseling()">
      <div>
        <i class="fa-solid fa-comments" style="color: #2d3436;"></i>
        <span>Counseling</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'notifications' ? 'active' : ''; ?>" onclick="redirectToNotifications()">
      <div>
        <i class="fa-solid fa-bell" style="color: #2d3436;"></i>
        <span>Notifications</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" onclick="redirectToProfile()">
      <div>
        <i class="fa-solid fa-user" style="color: #2d3436;"></i>
        <span>Profile</span>
      </div>
    </button>
  </nav>

  <script>
    function redirectToLogin() {
      window.location.href = 'login.php';
    }
    function redirectToRegister() {
      window.location.href = 'register.php';
    }
    function redirectToNotifications() {
      window.location.href = 'notification.php';
    }
    function redirectToReportIncident() {
      window.location.href = 'reportIncident.php';
    }
    function redirectToCounseling() {
      window.location.href = 'counseling.php';
    }
    function toggleDropdown() {
      const dropdown = document.getElementById('profileDropdown');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
    function redirectToHome() {
      window.location.href = 'homePage.php';
    }
    function redirectToLogout() {
      window.location.href = '../studentPortal/logout.php';
    }
    function redirectToProfile() {
      window.location.href = 'profile.php';
    }

    function markAsRead(notificationDiv, incidentId) {
        const bullet = notificationDiv.querySelector('.notification-bullet');

        // Check if the bullet is visible (indicating it's unread)
        if (bullet.style.display !== 'none') {
            bullet.style.display = 'none'; // Hide the bullet when notification is clicked

            // Move the notification to the Read tab
            const readNotificationsContainer = document.getElementById('readNotifications');
            const newNotificationsContainer = document.getElementById('newNotifications');

            // Remove the notification from New tab
            newNotificationsContainer.removeChild(notificationDiv);

            // Append the notification to Read tab
            readNotificationsContainer.appendChild(notificationDiv);
            notificationDiv.style.background = '#34495e'; // Optional: Change background color for read notifications

            // Log the incident ID being sent
            console.log('Sending incident_id:', incidentId);

            // Update the database to mark the notification as read
            fetch('update_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ incident_id: incidentId }),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // Navigate to reportStatus.php
                window.location.href = 'reportStatus.php?incident_id=' + incidentId; // Pass the incident ID if needed
            })
            .catch(error => {
                console.error('There was a problem with the fetch operation:', error);
            });
        }
    }

    function showTab(tab) {
      const newNotifications = document.getElementById('newNotifications');
      const readNotifications = document.getElementById('readNotifications');
      if (tab === 'new') {
        newNotifications.style.display = 'block';
        readNotifications.style.display = 'none';
        document.getElementById('newTab').style.background = 'linear-gradient(135deg, #0984e3, #74b9ff)';
        document.getElementById('readTab').style.background = 'linear-gradient(135deg, #34495e, #74b9ff)';
      } else {
        newNotifications.style.display = 'none';
        readNotifications.style.display = 'block';
        document.getElementById('readTab').style.background = 'linear-gradient(135deg, #0984e3, #74b9ff)';
        document.getElementById('newTab').style.background = 'linear-gradient(135deg, #34495e, #74b9ff)';
      }
    }

    let lastScrollTop = 0; // Variable to store the last scroll position
    const mobileNavbar = document.querySelector('.mobile-navbar'); // Select the mobile navbar

    window.addEventListener('scroll', function() {
      const currentScroll = window.pageYOffset || document.documentElement.scrollTop; // Get current scroll position

      if (currentScroll > lastScrollTop) {
        // Scrolling down
        mobileNavbar.style.transform = 'translateY(100%)'; // Hide the navbar
      } else {
        // Scrolling up
        mobileNavbar.style.transform = 'translateY(0)'; // Show the navbar
      }
      lastScrollTop = currentScroll <= 0 ? 0 : currentScroll; // Update last scroll position
    });

    let readNotifications = []; // Array to track read notifications

    function markCounselingAsRead(notificationDiv, counselingId) {
        const bullet = notificationDiv.querySelector('.notification-bullet');

        // Check if the bullet is visible (indicating it's unread)
        if (bullet.style.display !== 'none') {
            bullet.style.display = 'none'; // Hide the bullet when notification is clicked

            // Move the notification to the Read tab
            const readNotificationsContainer = document.getElementById('readNotifications');
            const newNotificationsContainer = document.getElementById('newNotifications');

            // Remove the notification from New tab
            newNotificationsContainer.removeChild(notificationDiv);

            // Append the notification to Read tab
            readNotificationsContainer.appendChild(notificationDiv);
            notificationDiv.style.background = '#34495e';

            // Update the database to mark the notification as read
            fetch('update_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ counseling_id: counselingId }),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // Navigate to counselingStatus.php
                window.location.href = 'counselingStatus.php?counseling_id=' + counselingId;
            })
            .catch(error => {
                console.error('There was a problem with the fetch operation:', error);
            });
        }
    }

    // Function to adjust notification container styles based on screen size
    function adjustNotificationContainer() {
      const notificationContainer = document.getElementById('notificationContainer');
      if (window.innerWidth <= 768) {
        // Mobile view styles
        notificationContainer.style.width = '90%';
        notificationContainer.style.maxWidth = '400px';
        notificationContainer.style.height = 'auto';
        notificationContainer.style.maxHeight = '80vh';
        notificationContainer.style.padding = '10px';
      } else {
        // Desktop view styles
        notificationContainer.style.width = '400px';
        notificationContainer.style.height = '600px';
        notificationContainer.style.padding = '20px';
      }
    }

    // Adjust on load
    window.onload = adjustNotificationContainer;
    // Adjust on resize
    window.onresize = adjustNotificationContainer;
  </script>
</body>
</html>