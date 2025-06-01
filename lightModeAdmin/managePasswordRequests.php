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

// Fetch password reset requests from the password_reset_requests table
$query = "
    SELECT pr.id, pr.email, pr.request_date, pr.status, au.name AS processed_by, pr.processed_date 
    FROM password_reset_requests pr
    LEFT JOIN admin_users au ON pr.processed_by = au.id"; // Join to get the admin user's name
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id']) && isset($_POST['email'])) {
    $requestId = $_POST['request_id'];
    $email = $_POST['email'];

    // Generate a new password
    $newPassword = bin2hex(random_bytes(4)); // Example: Generate a random password

    // Check if the email exists in admin_users or users
    $checkUserQuery = "SELECT COUNT(*) as count FROM admin_users WHERE email = ? OR email = ?";
    $stmt = $conn->prepare($checkUserQuery);
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $userExists = $result->fetch_assoc();

    if ($userExists['count'] > 0) {
        // Update password in admin_users table
        $updateAdminQuery = "UPDATE admin_users SET password = ? WHERE email = ?";
        $updateUserQuery = "UPDATE users SET password = ? WHERE email = ?";

        // First, try to update in admin_users
        $stmt = $conn->prepare($updateAdminQuery);
        $stmt->bind_param("ss", password_hash($newPassword, PASSWORD_DEFAULT), $email);
        $stmt->execute();

        // Check if the update affected any rows
        if ($stmt->affected_rows === 0) {
            // If no rows were affected, try updating in users table
            $stmt = $conn->prepare($updateUserQuery);
            $stmt->bind_param("ss", password_hash($newPassword, PASSWORD_DEFAULT), $email);
            $stmt->execute();
        }

        // Check if the password was updated successfully
        if ($stmt->affected_rows > 0) {
            // Update the password reset request status
            $updateRequestQuery = "UPDATE password_reset_requests SET status = 'approved', processed_by = ?, processed_date = NOW() WHERE id = ?";
            $stmt = $conn->prepare($updateRequestQuery);
            $stmt->bind_param("si", $userName, $requestId); // Assuming $userName is the admin's name
            $stmt->execute();

            // Send the new password to the user (you can implement email functionality here)
            echo "Password reset successfully. New password: " . htmlspecialchars($newPassword);
        } else {
            echo "Error updating password. Please check if the email is correct.";
        }
    } else {
        echo "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>Reset Password Requests</title>
    <style>
        /* Base Styles */
        @import url('https://fonts.cdnfonts.com/css/century-gothic');
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&display=swap');
        * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

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

        /* Main Content Styles */
        .main-content {
            left: 120px; /* Increase margin to ensure space from the sidebar */
            margin-right: auto;
            margin-top: 60px;
            padding: 30px 40px;
            position: relative;
            max-width: 1200px; /* Ensure it doesn't exceed the container width */
        }

        .content-card {
            margin-left: auto; /* Align to the right within the main content */
            margin-right: 0;
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
            justify-content: flex-end;
            margin-bottom: 20px;
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
            border-bottom: 1px solid #F4A261;
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
            color: #2196F3;
            background-color: rgba(33, 150, 243, 0.1);
        }

        .action-btn.reset-pwd:hover {
            color: #F8B83C;
            background-color: rgba(248, 184, 60, 0.1);
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
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            animation: fadeIn 0.5s;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #1E1E1E;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            animation: slideIn 0.5s;
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
            background-color: #09243B;
            color: #F8B83C;
        }

        .delete-btn:hover {
            background-color: #10375A;
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

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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

        /* Enhanced Table Styles */
        .dashboard-table {
            background-color: #f5f5f5;
            border: 1px solid #e0e0e0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            margin-top: 10px;
            border: none;
            background: #ffffff;
            border-radius: 12px;
            padding: 5px;
        }

        table {
            border-collapse: separate;
            border-spacing: 0 8px;
            margin-top: -8px;
        }

        th {
            background-color: #e8e8e8;
            color: #4a4a4a;
            font-weight: 600;
            padding: 16px 20px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            border: none;
        }

        td {
            background-color: #ffffff;
            padding: 16px 20px;
            border: none;
            transition: all 0.3s ease;
            color: #4a4a4a;
        }

        tr:hover td {
            background-color: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Action column alignment fixes */
        th:last-child, td:last-child {
            text-align: center;
        }

        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            min-width: 160px;
        }

        /* Enhanced Action Buttons */
        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 5px;
            white-space: nowrap;
            min-width: 70px;
            text-align: center;
        }

        .action-btn.reset-pwd {
            background-color: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .action-btn.reset-pwd:hover {
            background-color: #bbdefb;
            border-color: #1976d2;
        }

        /* Table cell padding adjustment */
        td, th {
            padding: 16px 20px;
            vertical-align: middle;
        }

        /* Update header styles */
        .table-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .table-header i {
            color: #4a4a4a;
            font-size: 24px;
        }

        .table-header h2 {
            color: #4a4a4a;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            font-weight: bold;
        }

        .header-line {
            border: none;
            border-bottom: 1px solid #e0e0e0;
            margin: 10px 0 20px 0;
        }

        /* Status badge styles */
        .status-active,
        .status-inactive {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background-color: #ffebee;
            color: #d32f2f;
        }

        /* New styles */
        .message-container {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .password-container {
            background-color: rgba(9, 36, 59, 0.6);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(248, 184, 60, 0.3);
        }

        .password-container label {
            display: block;
            color: #F8B83C;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .password-display {
            display: flex;
            align-items: center;
            background-color: rgba(30, 30, 30, 0.8);
            padding: 12px;
            border-radius: 6px;
            border: 1px solid rgba(248, 184, 60, 0.2);
        }

        #passwordText {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            color: #4CAF50;
            flex-grow: 1;
            letter-spacing: 1px;
        }

        .copy-btn {
            background: none;
            border: none;
            color: #F8B83C;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .copy-btn:hover {
            background-color: rgba(248, 184, 60, 0.1);
            transform: translateY(-2px);
        }

        .copy-btn i {
            font-size: 16px;
        }

        .copy-success {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgba(76, 175, 80, 0.9);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            font-family: 'Century Gothic', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideInUp 0.3s ease, fadeOut 0.3s ease 1.7s forwards;
            z-index: 1100;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .password-container {
            background-color: rgba(9, 36, 59, 0.8);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid rgba(248, 184, 60, 0.3);
            margin-top: 20px;
        }

        .password-container label {
            display: block;
            color: #F8B83C;
            font-size: 14px;
            margin-bottom: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .password-display {
            display: flex;
            align-items: center;
            background-color: rgba(30, 30, 30, 0.9);
            padding: 15px;
            border-radius: 6px;
            border: 1px solid rgba(248, 184, 60, 0.2);
        }

        #passwordText {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            color: #4CAF50;
            flex-grow: 1;
            letter-spacing: 1.5px;
            text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
        }

        .copy-btn {
            background: none;
            border: 1px solid rgba(248, 184, 60, 0.3);
            color: #F8B83C;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .copy-btn:hover {
            background-color: rgba(248, 184, 60, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(248, 184, 60, 0.2);
        }

        .copy-btn i {
            font-size: 16px;
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
                        <li><a href="manageUsers.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
                        <li class="active"><a href="managePasswordRequests.php"><i class="fas fa-key"></i>Password Requests</a></li>
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
                    <!-- Main Content -->
                    <div class="main-content" style="margin: 0 auto; max-width: 1200px; padding: 30px 40px;">
                <div class="content-card dashboard-table" style="margin-top: 80px;">
                    <div class="table-header">
                        <i class="fas fa-key"></i>
                        <h2>Password Reset Requests</h2>
                    </div>
                    <hr class="header-line">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Processed By</th>
                                    <th>Processed Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                                    <td><?php echo htmlspecialchars($request['email']); ?></td>
                                    <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                    <td>
                                        <span class="<?php echo strtolower($request['status']) === 'pending' ? 'status-inactive' : 'status-active'; ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['processed_by']); ?></td>
                                    <td><?php echo htmlspecialchars($request['processed_date']); ?></td>
                                    <td class="actions">
                                        <form method="POST" action="resetPassword.php">
                                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($request['email']); ?>">
                                            <button type="submit" class="action-btn reset-pwd">Reset Password</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    
                <!-- Scripts -->
        <script>
            // Define copyPassword in global scope
            function copyPassword() {
                const passwordText = document.getElementById('passwordText').textContent;
                navigator.clipboard.writeText(passwordText)
                    .then(() => {
                        // Create a temporary success message
                        const copySuccess = document.createElement('div');
                        copySuccess.className = 'copy-success';
                        copySuccess.innerHTML = '<i class="fas fa-check"></i> Password copied!';
                        document.body.appendChild(copySuccess);

                        // Remove the message after 2 seconds
                        setTimeout(() => {
                            copySuccess.remove();
                        }, 2000);
                    })
                    .catch(err => {
                        console.error('Failed to copy text: ', err);
                        alert('Failed to copy password to clipboard');
                    });
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

                const forms = document.querySelectorAll('form'); // Select all forms for password reset

                forms.forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(form);

                        fetch('resetPassword.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.getElementById('modalMessage').innerHTML = `
                                    <div class="message-container">
                                        <i class="fas fa-check-circle" style="color: #4CAF50; margin-right: 10px;"></i>
                                        ${data.message}
                                    </div>
                                    ${data.new_password ? `
                                        <div class="password-container">
                                            <label>New Password:</label>
                                            <div class="password-display">
                                                <span id="passwordText">${data.new_password}</span>
                                                <button type="button" class="copy-btn" onclick="copyPassword()">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    ` : ''}
                                `;
                                document.getElementById('successModal').style.display = 'block';
                            } else {
                                document.getElementById('modalMessage').innerHTML = `
                                    <div class="message-container">
                                        <i class="fas fa-exclamation-circle" style="color: #F44336; margin-right: 10px;"></i>
                                        ${data.message}
                                    </div>
                                `;
                                document.getElementById('successModal').style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        });
                    });
                });
            });

            // Function to close the modal
            function closeModal() {
                document.getElementById('successModal').style.display = 'none';
            }
        </script>

        <!-- Enhanced Modal Structure -->
        <div id="successModal" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="fas fa-key warning-icon" style="color: #F8B83C;"></i>
                    <h2>Reset Password</h2>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button class="modal-btn confirm-btn" onclick="closeModal()">
                        <i class="fas fa-check"></i> OK
                    </button>
                </div>
            </div>
        </div>

        <style>
        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: #1E1E1E;
            position: relative;
            margin: 15% auto;
            padding: 0;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(248, 184, 60, 0.2);
            animation: slideIn 0.4s ease;
            overflow: hidden;
        }

        .modal-header {
            background-color: rgba(9, 36, 59, 0.8);
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(248, 184, 60, 0.2);
        }

        .modal-header h2 {
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 20px;
            margin: 0 0 0 15px;
            flex-grow: 1;
        }

        .modal-header .close {
            color: #AEB2B7;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0 10px;
        }

        .modal-header .close:hover {
            color: #F8B83C;
            transform: scale(1.1);
        }

        .modal-body {
            padding: 25px;
            color: #AEB2B7;
            font-family: 'Century Gothic', sans-serif;
            font-size: 15px;
            line-height: 1.5;
        }

        .modal-footer {
            padding: 20px;
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid rgba(248, 184, 60, 0.2);
            background-color: rgba(9, 36, 59, 0.4);
        }

        .modal-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .confirm-btn {
            background-color: #09243B;
            color: #F8B83C;
            border: 1px solid rgba(248, 184, 60, 0.3);
        }

        .confirm-btn:hover {
            background-color: #10375A;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Animations */
        @keyframes fadeIn {
            from { 
                opacity: 0;
                backdrop-filter: blur(0px);
            }
            to { 
                opacity: 1;
                backdrop-filter: blur(5px);
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .modal-content {
                width: 95%;
                margin: 30% auto;
            }
            
            .modal-header {
                padding: 15px;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .modal-footer {
                padding: 15px;
            }
        }
        </style>

</body>
</html>