<?php
session_start(); // Start the session

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); // Report all errors except notices and warnings

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit(); // Stop further execution
}

// Include database connection file
include('../connection/connection.php'); // Adjusted path to include the connection file from the parent directory

// Fetch user details from the admin_users table based on the selected user ID
$userId = $_GET['user_id']; // Get the user ID from the query string
$query = "SELECT id, name, email, user_role, password, profile_image FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user data is retrieved successfully
if ($user) {
    $profileImage = '../image/' . $user['profile_image']; // Ensure the image path is correct
    $userName = $user['name'];
    $userEmail = $user['email']; // Changed from username to email
    $userRole = $user['user_role']; // New variable for user role
    $userPassword = $user['password']; // New variable for password
} else {
    // Handle the case where user data is not found
    $profileImage = '../image/default.png'; // Default image
    $userName = 'Guest'; // Default name
    $userEmail = ''; // Default email
    $userRole = ''; // Default user role
    $userPassword = ''; // Default password
}

// Check if the request is for changing the password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    // Prevent any output before JSON response
    ob_clean(); // Clear any previous output
    header('Content-Type: application/json'); // Set JSON content type

    $newPassword = $_POST['new_password'];
    $userId = $_POST['user_id'];

    // Hash the new password before storing it
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password in the database
    $updateQuery = "UPDATE admin_users SET password = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("si", $hashedPassword, $userId);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    }
    exit; // Stop execution after sending response
}

// Check if the request is for updating user details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    // Check if user_id is set
    if (!isset($_POST['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User ID is missing.']);
        exit; // Stop further execution
    }

    $name = $_POST['name'];
    $email = $_POST['email']; // Changed from username to email
    $role = $_POST['role'];
    $userId = $_POST['user_id'];

    // Prepare the SQL query to update user details
    $updateQuery = "UPDATE admin_users SET name = ?, email = ?, user_role = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sssi", $name, $email, $role, $userId);

    // Execute the update and check for errors
    if ($updateStmt->execute()) {
        header('Content-Type: application/json'); // Add content type header
        echo json_encode(['success' => true, 'message' => 'User details updated successfully.']);
    } else {
        // Log the SQL error for debugging
        error_log("SQL Error: " . $conn->error);
        header('Content-Type: application/json'); // Add content type header
        echo json_encode(['success' => false, 'message' => 'Failed to update user details.']);
    }
    exit; // Ensure no further output is sent
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>Edit User</title>
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

        .content-wrapper {
            margin-left: 249px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }

        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .settings-table {
            background: rgba(30, 30, 30, 0.8); /* Darker background */
            border-radius: 10px;
            padding: 20px; /* Adjust padding to reduce height */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5); /* Optional: Keep or adjust shadow */
        }

        .change-password-title {
            color: #F8B83C; /* Title color */
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5em;
            margin-bottom: 20px;
            text-align: center;
        }

        .input-group {
            margin-bottom: 10px; /* Reduce space between input fields */
        }

        .input-group input {
            width: 100%; /* Full width */
            padding: 10px;
            border: 1px solid #AEB2B7; /* Border color */
            border-radius: 5px;
            background: rgba(54, 51, 51, 0.7); /* Input background */
            color: #AEB2B7; /* Input text color */
        }

        .btn-change-password {
            width: 100%; /* Full width button */
            padding: 10px;
            background-color: #4CAF50; /* Button color */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease; /* Added transform for scale effect */
        }

        .btn-change-password:hover {
            background-color: #45a049; /* Darker button on hover */
            transform: scale(1.05); /* Slightly enlarge button on hover */
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .table-header i {
            color: #F8B83C;
            font-size: 1.2em;
        }

        .table-header h2 {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 1.2em;
            margin: 0;
        }

        .divider {
            height: 1px;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            margin: 15px 0;
        }

        .account-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column-reverse;
        }

        .input-group input {
            background: rgba(54, 51, 51, 0.5);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            background: rgba(54, 51, 51, 0.7);
            border: 1px solid rgba(248, 184, 60, 0.5);
            outline: none;
        }

        .input-group label {
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
        }

        .btn-update {
            background-color: #4CAF50; /* Button color */
            color: white;
            padding: 10px; /* Adjust padding for better appearance */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease; /* Added transform for scale effect */
        }

        .btn-update:hover {
            background-color: #45a049; /* Darker button on hover */
            transform: scale(1.05); /* Slightly enlarge button on hover */
        }

        .btn-change-password {
            background-color: #2196F3; /* Button color */
            color: white;
            padding: 10px; /* Adjust padding for better appearance */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease; /* Added transform for scale effect */
        }

        .btn-change-password:hover {
            background-color: #1e88e5; /* Darker button on hover */
            transform: scale(1.05); /* Slightly enlarge button on hover */
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Add these new styles */
        .password-wrapper {
            position: relative;
            width: 100%;
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
        }

        .password-requirements {
            margin: 15px 0;
            font-size: 0.9em;
            color: #AEB2B7;
        }

        .requirement {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement i {
            font-size: 0.8em;
            color: #666;
        }

        .requirement.valid i {
            color: #4CAF50;
        }

        .btn-update:disabled, .btn-change-password:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            display: none;
            align-items: center;
            gap: 8px;
        }

        .message.success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        /* Add animation for messages */
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .message.show {
            display: flex;
            animation: slideIn 0.3s ease;
        }

        /* Simplified back button styles */
        .back-button-container {
            margin-bottom: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background-color: #403E3E;
            color: #AEB2B7;
            text-decoration: none;
            border-radius: 4px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            background-color: #2d2d2d;
            color: #F8B83C;
        }

        .back-button i {
            font-size: 12px;
        }

        /* Modify the select input styles */
        .input-group select {
            background: rgba(54, 51, 51, 0.5);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 4px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            transition: all 0.3s ease;
            width: 100%;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        /* Modify the dropdown arrow to only appear for select elements */
        .input-group:has(select)::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #AEB2B7;
            pointer-events: none;
        }

        /* Style for focus state */
        .input-group select:focus {
            background: rgba(54, 51, 51, 0.7);
            border: 1px solid rgba(248, 184, 60, 0.5);
            outline: none;
        }

        /* Style for select options */
        .input-group select option {
            background: #363333;
            color: #AEB2B7;
            padding: 10px;
        }

        /* Remove the dropdown arrow from select elements */
        .input-group:has(select)::after {
            display: none; /* Hide the custom dropdown arrow */
        }
        
        /* Remove default dropdown arrow from select elements */
        .input-group select {
            background-image: none;
        }

        /* Message Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0, 0, 0, 0.7); /* Darker background for better contrast */
        }

        .modal-content {
            background-color: #2c2c2c; /* Dark background for the modal */
            margin: 15% auto; 
            padding: 20px;
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5); /* Shadow for depth */
            width: 80%; 
            max-width: 500px; /* Max width for larger screens */
            color: #f8f8f8; /* Light text color */
        }

        .close {
            color: #f8b83c; /* Close button color */
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: #f4a261; /* Change color on hover */
            text-decoration: none;
            cursor: pointer;
        }

        .modal-close-btn {
            background-color: #f8b83c; /* Button color */
            color: #2c2c2c; /* Text color */
            border: none;
            border-radius: 5px; /* Rounded corners */
            padding: 10px 20px; /* Padding for the button */
            cursor: pointer;
            transition: background-color 0.3s ease; /* Smooth transition */
            margin-top: 20px; /* Space above the button */
            display: block; /* Block display for full width */
            width: 100%; /* Full width button */
        }

        .modal-close-btn:hover {
            background-color: #f4a261; /* Darker button on hover */
        }

        .content-card {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .dashboard-table {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .table-header i {
            color: #e6b8af;
            font-size: 24px;
        }

        .table-header h2 {
            color: #4a4a4a;
            font-family: 'Century Gothic', sans-serif;
            font-size: 18px;
            font-weight: bold;
        }

        /* Table Container Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            background-color: #f0f0f0;
            color: #4a4a4a;
            font-weight: 600;
            font-size: 14px;
            padding: 15px;
            text-align: left;
            font-family: 'Century Gothic', sans-serif;
            border-bottom: 1px solid #e0e0e0;
        }

        td {
            padding: 12px 15px;
            color: #4a4a4a;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.3s ease;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f5f5f5;
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
            min-width: 100px;
        }

        .action-btn {
            background: none;
            border: none;
            color: #4a4a4a;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .action-btn i {
            font-size: 14px;
        }

        .action-btn.edit:hover {
            color: #e6b8af;
            background-color: rgba(230, 184, 175, 0.1);
        }

        .action-btn.delete:hover {
            color: #ff4747;
            background-color: rgba(255, 71, 71, 0.1);
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
                <li class="dropdown open">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-users"></i>User Management
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="active"><a href="manageUsers.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
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
    
    
            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <!-- Simplified back button -->
                <div class="back-button-container">
                    <a href="manageUsers.php" class="back-button">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                
                <div class="settings-container" style="display: flex; gap: 0; justify-content: center;">
                    <!-- First Table -->
                    <div class="settings-table" style="flex: 1; max-width: 500px;">
                        <div class="table-header">
                            <i class="fas fa-user-edit"></i>
                            <h2>UPDATE <?php echo ($editUserData['name'] ?? 'USER') ?> ACCOUNT</h2>
                        </div>
                        <div class="divider"></div>
                        <form id="updateUserForm">
                            <div class="account-content">
                                <div class="input-group">
                                    <input type="text" id="updateName" name="name" 
                                        value="<?php echo htmlspecialchars($userName); ?>" 
                                        data-original="<?php echo htmlspecialchars($userName); ?>">
                                    <label>Name</label>
                                </div>
                                <div class="input-group">
                                    <input type="text" id="updateEmail" name="email" 
                                        value="<?php echo htmlspecialchars($userEmail); ?>" 
                                        data-original="<?php echo htmlspecialchars($userEmail); ?>">
                                    <label>Email</label>
                                </div>
                                <div class="input-group">
                                    <input type="text" id="updateRole" name="role" 
                                        value="<?php echo htmlspecialchars($userRole); ?>" 
                                        data-original="<?php echo htmlspecialchars($userRole); ?>" 
                                        readonly>
                                    <label>User Role</label>
                                </div>
                                <button type="submit" class="btn-update" disabled>
                                    <i class="fas fa-save"></i> Update
                                </button>
                            </div>
                        </form>
                        <div class="message success" id="updateSuccessMessage">
                            <i class="fas fa-check-circle"></i>
                            Updated Successfully!
                        </div>
                    </div>

                    <!-- Second Table -->
                    <div class="settings-table" style="max-width: 400px; margin: 0;"> <!-- Centered and adjusted -->
                        <h2 class="change-password-title">Change Your Password</h2>
                        <form id="changePasswordForm">
                            <div class="input-group">
                                <input type="password" id="currentPassword" name="currentPassword" placeholder="Current Password" required>
                                <label>Current Password</label>
                            </div>
                            <div class="input-group">
                                <div class="password-wrapper">
                                    <input type="password" id="newPassword" name="newPassword" placeholder="New Password" required>
                                    <button type="button" class="toggle-password" data-target="newPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <label>New Password</label>
                            </div>
                            <div class="password-requirements">
                                <div class="requirement" data-requirement="length">
                                    <i class="fas fa-times"></i> At least 8 characters
                                </div>
                                <div class="requirement" data-requirement="uppercase">
                                    <i class="fas fa-times"></i> At least one uppercase letter
                                </div>
                                <div class="requirement" data-requirement="lowercase">
                                    <i class="fas fa-times"></i> At least one lowercase letter
                                </div>
                                <div class="requirement" data-requirement="number">
                                    <i class="fas fa-times"></i> At least one number
                                </div>
                            </div>
                            <button type="submit" class="btn-change-password">
                                <i class="fas fa-key"></i> Change
                            </button>
                        </form>
                        <div class="message success" id="passwordSuccessMessage">
                            <i class="fas fa-check-circle"></i>
                            Changed Successfully!
                        </div>
                    </div>
                </div>
            </div>
    
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

                // Form change detection for update form
                const updateForm = document.getElementById('updateUserForm');
                const passwordForm = document.getElementById('changePasswordForm');
                const updateBtn = updateForm.querySelector('.btn-update');
                const updateInputs = updateForm.querySelectorAll('input, select');

                updateInputs.forEach(input => {
                    input.addEventListener('input', () => {
                        let hasChanges = false;
                        updateInputs.forEach(field => {
                            if (field.value !== field.dataset.original) {
                                hasChanges = true;
                            }
                        });
                        updateBtn.disabled = !hasChanges;
                    });
                });

                // Password validation and requirements
                const newPasswordInput = document.getElementById('newPassword');
                const passwordBtn = document.querySelector('.btn-change-password');
                const requirements = {
                    length: str => str.length >= 8,
                    uppercase: str => /[A-Z]/.test(str),
                    lowercase: str => /[a-z]/.test(str),
                    number: str => /[0-9]/.test(str)
                };

                function validatePasswordForm() {
                    const newValue = newPasswordInput.value;
                    let validRequirements = true;

                    // Check password requirements
                    Object.keys(requirements).forEach(req => {
                        const element = document.querySelector(`[data-requirement="${req}"]`);
                        const isValid = requirements[req](newValue);
                        element.classList.toggle('valid', isValid);
                        if (!isValid) validRequirements = false;
                    });

                    // Enable button only if new password meets requirements
                    passwordBtn.disabled = !validRequirements;
                }

                newPasswordInput.addEventListener('input', validatePasswordForm);

                // Toggle password visibility for new password field
                document.querySelector('.toggle-password').addEventListener('click', () => {
                    const input = document.getElementById('newPassword');
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    document.querySelector('.toggle-password i').classList.toggle('fa-eye');
                    document.querySelector('.toggle-password i').classList.toggle('fa-eye-slash');
                });

                // Modified password form submission with requirements reset
                passwordForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const currentPassword = document.getElementById('currentPassword').value;
                    const newPassword = document.getElementById('newPassword').value;

                    // Check if the new password is the same as the current password
                    if (newPassword === currentPassword) {
                        showModal('Error', 'The new password cannot be the same as the current password.');
                        return;
                    }

                    try {
                        // Validate the current password
                        const validationResponse = await fetch('validatePassword.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ userId: <?php echo $userId; ?>, currentPassword: currentPassword })
                        });

                        const validationResult = await validationResponse.json();

                        if (!validationResult.success) {
                            showModal('Error', 'Current password is incorrect. Please try again.');
                            return;
                        }

                        // Proceed to change the password
                        const formData = new FormData();
                        formData.append('change_password', '1');
                        formData.append('new_password', newPassword);
                        formData.append('user_id', <?php echo $userId; ?>);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const responseText = await response.text(); // Get response as text first
                        
                        try {
                            const result = JSON.parse(responseText); // Parse the response text
                            if (result.success) {
                                // Show success message
                                const successMessage = document.getElementById('passwordSuccessMessage');
                                successMessage.classList.add('show');
                                setTimeout(() => {
                                    successMessage.classList.remove('show');
                                }, 3000);
                                
                                // Clear password fields
                                document.getElementById('currentPassword').value = '';
                                document.getElementById('newPassword').value = '';
                                
                                // Reset password requirements
                                document.querySelectorAll('.requirement').forEach(req => {
                                    req.classList.remove('valid');
                                });
                                
                                // Disable the submit button
                                passwordBtn.disabled = true;
                            } else {
                                showModal('Error', result.message || 'Failed to change password.');
                            }
                        } catch (parseError) {
                            console.error('Response parsing error:', responseText);
                            showModal('Error', 'Invalid server response. Please try again.');
                        }
                    } catch (error) {
                        console.error('Error changing password:', error);
                        showModal('Error', 'Error changing password. Please try again later.');
                    }
                });

                // Function to show modal
                function showModal(title, message) {
                    const modal = document.getElementById('messageModal');
                    const modalTitle = document.getElementById('modalTitle');
                    const modalMessage = document.getElementById('modalMessage');

                    modalTitle.textContent = title;
                    modalMessage.textContent = message;
                    modal.style.display = 'block';
                }

                // Close modal when clicking the close button
                document.querySelector('.close').onclick = function() {
                    document.getElementById('messageModal').style.display = 'none';
                };

                // Close modal when clicking the close button in the modal
                document.getElementById('modalCloseButton').onclick = function() {
                    document.getElementById('messageModal').style.display = 'none';
                };

                // Close modal when clicking outside of it
                window.onclick = function(event) {
                    const modal = document.getElementById('messageModal');
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                };

                // Update form submission
                updateForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const newName = document.getElementById('updateName').value;
                    const newEmail = document.getElementById('updateEmail').value;
                    const userId = <?php echo $userId; ?>;

                    try {
                        const formData = new FormData();
                        formData.append('update_user', '1');
                        formData.append('name', newName);
                        formData.append('email', newEmail);
                        formData.append('role', document.getElementById('updateRole').value);
                        formData.append('user_id', userId);

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            const successMessage = document.getElementById('updateSuccessMessage');
                            successMessage.classList.add('show');
                            setTimeout(() => {
                                successMessage.classList.remove('show');
                            }, 3000);
                            
                            // Update data-original attributes
                            updateInputs.forEach(input => {
                                input.dataset.original = input.value;
                            });
                            updateBtn.disabled = true;
                        } else {
                            showModal('Error', result.message || 'Failed to update user details.');
                        }
                    } catch (error) {
                        console.error('Error updating user details:', error);
                        showModal('Error', 'There was an error processing your request. Please try again.');
                    }
                });
            });
        </script>

        <!-- Message Modal -->
        <div id="messageModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modalTitle"></h2>
                <p id="modalMessage"></p>
                <button id="modalCloseButton" class="modal-close-btn">Close</button>
            </div>
        </div>
</body>
</html>