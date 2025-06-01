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

// Get user_id from session first
$user_id = $_SESSION['user_id'];

// Update the unread notifications query to properly count notifications for the current user
$unread_query = "SELECT (
    SELECT COUNT(*) 
    FROM incident_history ih 
    INNER JOIN incidents i ON ih.incident_id = i.id 
    WHERE ih.is_read = '0' AND i.user_id = ?
) + (
    SELECT COUNT(*) 
    FROM counseling_history ch 
    INNER JOIN counseling_appointments ca ON ch.counseling_id = ca.id 
    WHERE ch.is_read = '0' AND ca.user_id = ?
) as total_unread";

$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("ii", $user_id, $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['total_unread'];

// Fetch the user's first name, last name, and user_profile from the database
$query = "SELECT first_name, last_name, user_profile FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $first_name = $user['first_name'];
    $last_name = $user['last_name']; // Fetch last name
    $user_profile = '../image/' . $user['user_profile'];
} else {
    echo "User not found.";
    // Handle the case where the user is not found in the database
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile</title>
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
      .main-content {
        padding-top: 180px; /* Adjust to the height of the mobile-header */
        padding-bottom: 60px; /* Adjust to the height of the mobile-navbar */
        flex-direction: column; /* Ensure elements stack vertically */
        align-items: center; /* Center align items */
        gap: 20px; /* Space between elements */
        min-height: 60vh; /* Adjusted height for better responsiveness */
      }
      .profile-image-container {
        max-width: 90%; /* Use a percentage for better responsiveness */
        padding: 20px; /* Reduce padding for smaller screens */
      }
      .profile-image {
        width: 120px; /* Smaller image size for mobile */
        height: 120px;
      }
      .profile-image-container h2 {
        font-size: 20px; /* Smaller font size for better fit */
      }
      .edit-profile-btn {
        padding: 10px 20px; /* Adjust button size */
        font-size: 14px; /* Smaller font size for button */
      }
    }

    @media (min-width: 769px) {
      .mobile-navbar {
        display: none;
      }
    }


    @media (max-width: 768px) {
      .navbar { 
        flex-direction: column; /* Stack navbar items on smaller screens */
      }
      .nav-menu { 
        flex-direction: column; /* Stack nav menu items */
        gap: 10px; /* Reduced gap for better spacing */
      }
      .service-container { 
        flex-direction: column; /* Stack service boxes on smaller screens */
      }
      .service-box { width: 90%; }
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
      gap: 10px; /* Adjusted gap for better spacing */
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      padding: 12px; /* Increased padding for better spacing */
      border-radius: 25px; /* Updated border radius for a more rounded look */
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .profile-icon {
      background: none;
      border: none;
      color: #ffffff;
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
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
      background: rgba(9, 132, 227, 0.1);
    }

    .profile-icon img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ffffff;
    }
    .main-content {
      background: url('../image/bg.png') no-repeat center center fixed; /* Use bg.png as background */
      background-size: cover; /* Ensure the image covers the entire section */
      background-color: rgba(10, 10, 10, 0.9); /* Darker overlay */
      background-blend-mode: overlay; /* Blend the color with the image */
      padding: 40px 20px; /* Increased padding for better spacing */
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 80vh; /* Adjusted height for better responsiveness */
      flex-direction: column; /* Stack elements vertically */
      gap: 20px; /* Space between elements */
    }

    .profile-image-container {
      background: rgba(34, 34, 50, 0.8); /* Semi-transparent background */
      backdrop-filter: blur(10px); /* Blur effect */
      border: 2px solid #3D3C4B; /* Border color */
      border-radius: 12px; /* Softer corners */
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
      padding: 40px; /* Increased padding for a more spacious look */
      max-width: 350px; /* Slightly wider for better content fit */
      margin: 0 auto;
      color: #fff;
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .profile-image-container:hover {
      transform: translateY(-10px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.5); /* More pronounced hover effect */
    }

    .profile-image {
      width: 160px; /* Slightly larger image */
      height: 160px;
      border-radius: 50%; /* Circular image for a modern look */
      object-fit: cover;
      margin-bottom: 10px; /* Reduced margin to bring the name closer */
      border: 3px solid #f4a261; /* Border to highlight the image */
    }

    .profile-image-container h2 {
      margin-bottom: 20px;
      font-size: 24px; /* Larger font for better readability */
      color: #f4a261;
      font-weight: 600; /* Bolder text for emphasis */
    }

    .edit-profile-btn {
      background: #e76f51;
      color: #fff;
      padding: 12px 24px; /* Larger button for better interaction */
      border-radius: 8px; /* Reduced border radius for sharper corners */
      cursor: pointer;
      transition: background 0.3s, transform 0.3s; /* Added transform for interaction */
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin: 0 auto; /* Center the button */
    }

    .edit-profile-btn:hover {
      background: #d65a3a;
      transform: scale(1.05); /* Slight scale effect on hover */
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
      color: #f4a261; /* Adjust logo color for better visibility in dark mode */
    }

    .text-logo {
      font-size: 24px; /* Font size for the text logo */
      color: #f4a261; /* Actual color for the text logo */
      font-weight: bold; /* Make the text bold */
      line-height: 1; /* Adjust line height for better alignment */
    }

    .mobile-header .logout-button {
      background: none; /* No background for button */
      border: none; /* No border */
      color: #e0e0e0; /* Ensure logout button has light color */
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
      background: rgba(9, 132, 227, 0.1); /* Darker hover background color */
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
      padding: 20px 50px; 
      border-top: 1px solid #74b9ff;
    }

    /* Adjust other sections as necessary */
    .services {
      background: #222; /* Dark background for services section */
      color: #e0e0e0; /* Light text color for services */
    }

    .spacer {
      display: none; /* Hide spacer as it's no longer needed */
    }

    @media (max-width: 768px) {
      .main-content {
        padding-top: 180px; /* Adjust to the height of the mobile-header */
        padding-bottom: 60px; /* Adjust to the height of the mobile-navbar */
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
  
  
  <div class="main-content">
    <div class="profile-image-container">
      <?php
      $image_path = '../image/' . basename($user_profile); // Ensure the path is correct and safe
      if (file_exists($image_path)) {
          echo '<img src="' . htmlspecialchars($image_path) . '" alt="User Profile" class="profile-image">'; // Display user profile image
      } else {
          echo '<img src="../image/default_profile.png" alt="Default Profile" class="profile-image">'; // Use a default image if the profile image is not found
      }
      ?>
      <h2><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h2>
      <button class="edit-profile-btn" onclick="window.location.href='settings.php'">
        <i class="fa-solid fa-edit"></i> Edit Profile
      </button>
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
        <i class="fa-solid fa-home" style="color: #ecf0f1;"></i>
        <span>Home</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'report' ? 'active' : ''; ?>" onclick="redirectToReportIncident()">
      <div>
        <i class="fa-solid fa-flag" style="color: #ecf0f1;"></i>
        <span>Report</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'counseling' ? 'active' : ''; ?>" onclick="redirectToCounseling()">
      <div>
        <i class="fa-solid fa-comments" style="color: #ecf0f1;"></i>
        <span>Counseling</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'notifications' ? 'active' : ''; ?>" onclick="redirectToNotifications()">
      <div>
        <i class="fa-solid fa-bell" style="color: #ecf0f1;"></i>
        <span>Notifications</span>
      </div>
    </button>
    <button class="icon-button <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" onclick="redirectToProfile()">
      <div>
        <i class="fa-solid fa-user" style="color: #ecf0f1;"></i>
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
    function redirectToHome() {
      window.location.href = 'homePage.php';
    }
    function redirectToLogout() {
      window.location.href = '../studentPortal/logout.php';
    }
    function redirectToProfile() {
      window.location.href = 'profile.php';
    }
    function toggleDropdown() {
      const dropdown = document.getElementById('profileDropdown');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
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

  </script>
</body>
</html>