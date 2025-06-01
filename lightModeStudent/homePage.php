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
    $user_profile = '../image/' . $user['user_profile']; // Changed from profile_image to user_profile
} else {
    echo "User not found.";
    $user_profile = '../image/default.png'; // Default image path
    $first_name = 'Guest'; // Default name
    // Handle the case where the user is not found in the database
    exit;
}

// Update the unread notifications query to only count notifications for the current user
$unread_query = "SELECT 
    (SELECT COUNT(*) FROM incident_history ih 
     JOIN incidents i ON ih.incident_id = i.id 
     WHERE ih.is_read = '0' AND i.user_id = ?) +
    (SELECT COUNT(*) FROM counseling_history ch 
     JOIN counseling_appointments ca ON ch.counseling_id = ca.id 
     WHERE ch.is_read = '0' AND ca.user_id = ?) as total_unread";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("ii", $user_id, $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['total_unread'];

// Set the current page variable
$currentPage = 'home'; // Set this page as the current page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home Page</title>
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
      color: #2c3e50; 
      line-height: 1.6; 
      font-size: 16px;
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

    .banner {
      position: relative;
      width: 100%;
      height: 650px;
      background: url('../image/bg.png') center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 50px;
      color: #fff;
      overflow: hidden;
      text-align: center;
    }

    .banner::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(116, 185, 255, 0.2));
      z-index: 1;
    }

    .banner .content {
      position: relative;
      z-index: 2;
      max-width: 800px;
      padding: 20px;
      color: #2c3e50;
    }

    .content h1, .content p {
      opacity: 0; /* Start hidden */
      transform: translateY(20px); /* Start slightly below */
      animation: fadeInUp 0.5s ease-out forwards; /* Animation properties */
    }

    .content h1 {
      animation-delay: 1s; /* Delay for the heading */
      font-size: 48px; 
      margin-bottom: 20px; 
      line-height: 1.2;
      font-family: 'Poppins', sans-serif;
      font-size: 36px; /* Main heading size */
    }
    .content p {
      animation-delay: 2s; /* Delay for the first paragraph */
      font-size: 18px; 
      margin-bottom: 20px;
      font-family: 'Nunito', sans-serif;
      font-size: 16px; /* Paragraph text size */
      line-height: 1.6; /* Line height for better readability */
    }

    .content p + p {
      animation-delay: 3s; /* Delay for the second paragraph */
    }

    /* Animated gradient background for services section resembling a moving snake */
    @keyframes snakeAnimation {
      0% {
        background-position: 0% 50%;
      }
      25% {
        background-position: 50% 50%;
      }
      50% {
        background-position: 100% 50%;
      }
      75% {
        background-position: 50% 50%;
      }
      100% {
        background-position: 0% 50%;
      }
    }

    .services {
      background: linear-gradient(135deg, #a8e6cf, #74b9ff, #ffd3b6, #a8e6cf);
      background-size: 400% 400%;
      animation: snakeAnimation 10s ease infinite;
      padding: 80px 50px; 
      text-align: center; 
      border-radius: 12px;
    }
    .services h2 { 
      font-size: 36px; 
      color: #2c3e50; 
      margin-bottom: 40px; 
    }
    .service-container { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; }
    .service-box {
      background: linear-gradient(135deg, #ffffff, #e3f2fd);
      color: #2d3436;
      width: 800px;
      padding: 40px;
      border-radius: 12px;
      position: relative;
      transition: transform 0.3s, box-shadow 0.3s;
      border: 1px solid #74b9ff;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .service-box:hover { 
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
      background: linear-gradient(135deg, #e3f2fd, #ffffff);
    }
    .service-box h3 {
      color: #0984e3;
    }
    .service-box p {
      font-size: 16px; /* Increased paragraph font size */
      text-align: left;
      margin-bottom: 20px; /* Added margin for spacing */
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

    /* Main content font sizes */
    h1 {
      font-size: 36px; /* Main heading size */
      line-height: 1.2; /* Adjust line height for better readability */
    }

    h2 {
      font-size: 28px; /* Subheading size */
      margin-bottom: 20px; /* Space below subheadings */
    }

    h3 {
      font-size: 24px; /* Smaller heading size */
      margin-bottom: 15px; /* Space below smaller headings */
    }

    p {
      font-size: 16px; /* Paragraph text size */
      line-height: 1.6; /* Line height for better readability */
      margin-bottom: 15px; /* Space below paragraphs */
    }

    /* Additional text elements */
    .welcome-message {
      font-size: 20px; /* Size for welcome message */
      color: #2c3e50;
    }

    @media (max-width: 768px) {
      .navbar {
        display: none; /* Hide the desktop navbar on mobile */
      }
      .mobile-navbar {
        display: flex; /* Show mobile navbar */
        justify-content: space-around; /* Space out buttons */
        background: #34495e; /* Updated background color for better contrast */
        padding: 10px 0; /* Add padding */
        position: fixed; /* Fix to the bottom */
        bottom: 0;
        width: 100%;
        z-index: 1000; /* Ensure it stays above other content */
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.3); /* Add shadow for depth */
        transition: transform 0.3s ease; /* Add transition for smooth animation */
      }
      .mobile-navbar .icon-button {
        background: none;
        color: #ecf0f1;
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
        color: #f39c12;
      }
      .mobile-navbar .icon-button:hover {
        color: #f39c12;
      }
      .nav-menu { 
        flex-direction: column;
        gap: 10px;
      }
      .service-container { 
        flex-direction: column;
      }
      .service-box { width: 90%; }
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

    .welcome-message {
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 25px;
      z-index: 2;
      color: #fff;
      opacity: 0;
      animation: fadeIn 0.5s ease-out forwards;
      animation-delay: 1s;
    }

    @keyframes fadeIn {
      to {
        opacity: 1;
      }
    }

    .waving-hand {
      display: inline-block;
      transition: transform 0.2s;
    }

    .waving-hand:hover {
      animation: wave 0.5s infinite;
    }

    @keyframes wave {
      0% { transform: rotate(0deg); }
      25% { transform: rotate(15deg); }
      50% { transform: rotate(-15deg); }
      75% { transform: rotate(10deg); }
      100% { transform: rotate(0deg); }
    }

    @keyframes fadeInUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .typing {
      display: inline-block;
      overflow: hidden;
      white-space: nowrap;
      border-right: 3px solid #f4a261;
      animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite, remove-cursor 5s forwards;
    }

    @keyframes typing {
      from { width: 0; }
      to { width: 100%; }
    }

    @keyframes blink-caret {
      from, to { border-color: transparent; }
      50% { border-color: #f4a261; }
    }

    @keyframes remove-cursor {
      to {
        border-color: transparent;
      }
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
      color: #6c5ce7; /* Adjust logo color for better visibility in dark mode */
    }

    .text-logo {
      font-size: 24px;
      color: #0984e3;
      font-weight: bold;
      line-height: 1;
    }

    .mobile-header .logout-button {
      background: none;
      border: none;
      color: #2d3436;
      font-size: 24px;
      cursor: pointer;
    }

    .mobile-navbar {
      background: linear-gradient(135deg, #a8e6cf, #74b9ff);
      color: #2d3436;
      box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
    }

    .mobile-navbar .icon-button {
      color: #2d3436;
    }

    .mobile-navbar .icon-button:hover {
      background: #dcedc1;
    }

    /* Additional styles for dark mode */
    body {
      background-color: #ffffff;
      color: #2c3e50;
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
      background: #ffffff;
      color: #2c3e50;
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
      <li><a href="#" class="active">Home</a></li>
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

  <header class="banner">
    <div class="welcome-message">
      <span class="typing">
        <span style="color: #949396;">Welcome Back,</span> 
        <span style="color: #f4a261;"><?php echo htmlspecialchars($first_name); ?>!</span> 
        <span style="color: #f4a261;">👋</span>
      </span>
    </div>
    <div class="content <?php echo $showAnimation ? 'animate-content' : ''; ?>">
      <h1>CITCARE 360: Your Student Support & Emergency Assistance Hub</h1>
      <p>A dedicated platform for CIT students to report emergencies, seek support, and schedule counseling—ensuring safety, guidance, and well-being.</p>
    </div>
  </header>

  <section id="services" class="services">
    <h2>Services</h2>
    <div class="service-container">
      <div class="service-box">
        <div class="icon-button" onclick="redirectToReportIncident()"><i class="fa-solid fa-caret-right"></i></div>
        <h3><i class="fa-solid fa-exclamation-triangle"></i> Report an Incident</h3>
        <p>Report issues like harassment, threats, or accidents quickly and securely.
        Users can provide details, name the person involved, and upload evidence such as images or videos. Don't worry this features ensures a safe, and confidential way for students to seek help, and support.</p>
      </div>
    </div>
  </section>
  
  <div class="spacer"></div>
  
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