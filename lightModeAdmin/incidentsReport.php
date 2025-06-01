<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit(); // Stop further execution
}
// Include the database connection file
include '../connection/connection.php';

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

// Fetch data from user_roles table
$query = "SELECT * FROM user_roles";
$result = $conn->query($query);
$userRoles = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userRoles[] = $row;
    }
} else {
    $userRoles = []; // No roles found
}

// Fetch data from incidents table with filtering
$highlightId = isset($_GET['highlight']) ? intval($_GET['highlight']) : null;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // Get the filter parameter

$query = "SELECT * FROM incidents";
if ($filter === 'NEW') {
    $query .= " WHERE status = 'NEW'"; // Apply filter for 'NEW' status
}
$result = $conn->query($query);
$incidentsData = []; // Initialize an array to hold incidents data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $incidentsData[] = $row; // Store each row of data
    }
} else {
    $incidentsData = []; // No incidents found
}

$highlightId = isset($_GET['highlight']) ? intval($_GET['highlight']) : null;

// Close the database connection after all queries
$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <title>Incidents</title>
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

        /* Responsive Design */
        @media screen and (max-width: 1400px) {
            .main-content {
                padding: 20px;
            }
        }

        /* Add these status badge styles */
        .status-active,
        .status-inactive {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .status-inactive {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        /* Add these styles in your existing <style> tag */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 4px;
            color: white;
            font-family: 'Century Gothic', sans-serif;
            z-index: 1000;
            animation: slideIn 0.3s ease, slideOut 0.3s ease 2.7s;
        }

        .toast.success {
            background-color: rgba(76, 175, 80, 0.9);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(30,30,30,0.95); /* Solid dark background for readability */
            padding: 20px;
            border-radius: 12px; /* More rounded corners */
            width: 95%; /* Increased width for better visibility */
            max-width: 800px; /* Increased max width for larger images */
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.7); /* Deeper shadow for depth */
            animation: slideIn 0.5s ease; /* Added slide-in animation */
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translate(-50%, -60%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        .filter-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        .filter-dropdown {
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            background-color: #ffffff;
            color: #4a4a4a;
            font-family: 'Century Gothic', sans-serif;
            transition: border-color 0.3s ease;
            cursor: pointer;
            width: 150px;
        }

        .filter-dropdown:hover {
            border-color: #e6b8af;
        }

        .filter-dropdown:focus {
            outline: none;
            border-color: #e6b8af;
        }

        .filter-container label {
            font-family: 'Century Gothic', sans-serif;
            color: #4a4a4a;
            margin-right: 10px;
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

        .view-images-btn {
            background-color: #e6b8af;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .view-images-btn:hover {
            background-color: #d4a5a5;
            transform: translateY(-2px);
        }

        .view-images-btn:focus {
            outline: none;
            box-shadow: 0 0 5px rgba(230, 184, 175, 0.5);
        }

        /* Modal for displaying the enlarged image */
        #enlargedImage {
            max-width: 90%; /* Limit the width to 90% of the modal */
            max-height: 80vh; /* Limit the height to 80% of the viewport height */
            border-radius: 12px; /* Rounded corners */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5); /* Add shadow */
            display: block; /* Ensure it behaves like a block element */
            margin: 0 auto; /* Center the image horizontally */
        }

        /* View Image Button Styles */
        .view-images-btn {
            background-color: #F8B83C; /* Button background color */
            color: #1E1E1E; /* Button text color */
            border: none; /* Remove border */
            border-radius: 5px; /* Rounded corners */
            padding: 10px 15px; /* Padding for the button */
            font-family: 'Century Gothic', sans-serif; /* Font style */
            font-size: 14px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth background color transition */
            display: inline-flex; /* Align items in the center */
            align-items: center; /* Center items vertically */
            gap: 5px; /* Space between icon and text */
        }

        .view-images-btn:hover {
            background-color: #F4A261; /* Change background color on hover */
            transform: translateY(-2px); /* Slightly lift the button on hover */
        }

        .view-images-btn:focus {
            outline: none; /* Remove outline on focus */
            box-shadow: 0 0 5px rgba(248, 184, 60, 0.5); /* Add shadow on focus */
        }

        /* Enhanced Table Styles */
        .dashboard-table {
            background-color: #f8f9fa;
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
            width: 100%;
        }

        th {
            background-color: #f0f0f0;
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
            background-color: #f5f5f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Action column alignment */
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

        /* Table Header Section */
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

        .header-line {
            border: none;
            border-bottom: 1px solid #F4A261;
            margin: 10px 0 20px 0;
        }

        /* Enhanced Modal Styles for Images */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        /* Enhanced Image Modal Styles */
        #imagesModal .modal-content {
            background-color: rgba(30, 30, 30, 0.95);
            border: 2px solid #F8B83C;
            border-radius: 15px;
            padding: 25px;
            max-width: 900px;
            width: 95%;
        }

        #imagesContainer {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
            margin: 20px 0;
            background-color: rgba(9, 36, 59, 0.3);
            border-radius: 10px;
            padding: 20px;
        }

        #imagesContainer img {
            max-height: 500px;
            max-width: 100%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        #imagesContainer img:hover {
            transform: scale(1.02);
        }

        /* Navigation Buttons */
        .image-nav-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(248, 184, 60, 0.1);
            border: 2px solid #F8B83C;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .image-nav-button:hover {
            background-color: rgba(248, 184, 60, 0.2);
            transform: translateY(-50%) scale(1.1);
        }

        .image-nav-button.prev {
            left: 20px;
        }

        .image-nav-button.next {
            right: 20px;
        }

        .image-nav-button i {
            color: #F8B83C;
            font-size: 24px;
        }

        /* Image Counter */
        .image-counter {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(9, 36, 59, 0.8);
            padding: 8px 15px;
            border-radius: 20px;
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 14px;
            border: 1px solid rgba(248, 184, 60, 0.3);
        }

        /* Close Button */
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: #F8B83C;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-close:hover {
            background-color: rgba(248, 184, 60, 0.1);
            transform: rotate(90deg);
        }

        /* Modal Header */
        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(248, 184, 60, 0.2);
        }

        .modal-header h2 {
            color: #F8B83C;
            font-family: 'Century Gothic', sans-serif;
            font-size: 24px;
            margin: 0;
            flex-grow: 1;
        }

        /* Add these styles in your existing <style> tag */
        .view-images-btn:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
        }

        .view-images-btn:disabled:hover {
            background-color: #cccccc;
            transform: none;
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
                <li class="active">
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
            <div class="main-content">
                <div class="content-card dashboard-table">
                    <!-- Filter Dropdown -->
                    <div class="filter-container">
                        <label for="statusFilter" style="color: #AEB2B7; font-family: 'Century Gothic', sans-serif; margin-right: 10px;">Filter:</label>
                        <select id="statusFilter" class="filter-dropdown">
                            <option value="all">All</option>
                            <option value="new">New</option>
                            <option value="active">Active</option>
                            <option value="resolved">Resolved</option>
                            <option value="unresolved">Unresolved</option>
                        </select>
                    </div>
                    <div class="table-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h2>INCIDENT REPORT</h2>
                    </div>
                    <hr class="header-line">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>NAME</th>
                                    <th>STUDENT NUMBER</th>
                                    <th>EMAIL</th>
                                    <th>PHONE NUMBER</th>
                                    <th>DEPARTMENT</th>
                                    <th>SUBJECT</th>
                                    <th>DETAILS</th>
                                    <th>STATUS</th>
                                    <th>EVIDENCE</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incidentsData as $incident): ?>
                                    <tr style="<?php echo $highlightId === $incident['id'] ? 'background-color: #F4A261;' : ''; ?>">
                                        <td><?php echo htmlspecialchars($incident['id']); ?></td>
                                        <td><?php echo htmlspecialchars($incident['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($incident['student_number']); ?></td>
                                        <td><?php echo htmlspecialchars($incident['email']); ?></td>
                                        <td><?php echo htmlspecialchars($incident['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($incident['department']); ?></td>
                                        <td><?php echo htmlspecialchars($incident['subject_report']); ?></td>
                                        <td>
                                            <button class="view-images-btn" onclick="openDescriptionModal(<?php echo htmlspecialchars(json_encode($incident['description'])); ?>)">View</button>
                                        </td>
                                        <td>
                                            <select class="status-dropdown" data-id="<?php echo $incident['id']; ?>">
                                                <option value="NEW" <?php echo $incident['status'] === 'NEW' ? 'selected' : ''; ?>>NEW</option>
                                                <option value="ACTIVE" <?php echo $incident['status'] === 'ACTIVE' ? 'selected' : ''; ?>>ACTIVE</option>
                                                <option value="RESOLVED" <?php echo $incident['status'] === 'RESOLVED' ? 'selected' : ''; ?>>RESOLVED</option>
                                                <option value="UNRESOLVED" <?php echo $incident['status'] === 'UNRESOLVED' ? 'selected' : ''; ?>>UNRESOLVED</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="view-images-btn" onclick="openImagesModal(<?php echo htmlspecialchars(json_encode($incident['evidence'])); ?>)" <?php echo empty($incident['evidence']) ? 'disabled' : ''; ?>>
                                                <i class="fas fa-image"></i> View Images
                                            </button>
                                        </td>
                                        <td class="actions">
                                            <!-- Removed the edit button -->
                                            <!-- <button class="action-btn edit" data-id="<?php echo $incident['id']; ?>" title="Edit Group">
                                                <i class="fas fa-edit"></i>
                                            </button> -->
                                            <button class="action-btn delete" data-id="<?php echo $incident['id']; ?>" title="Delete Group">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
            document.addEventListener('DOMContentLoaded', function() {
                // Add DateTime update function
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

                // Add event listeners for edit and delete buttons
                const editButtons = document.querySelectorAll('.action-btn.edit');
                const deleteButtons = document.querySelectorAll('.action-btn.delete');

                editButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const groupId = this.getAttribute('data-id');
                        window.location.href = 'editGroups.php?id=' + groupId;
                    });
                });

                // Add these new functions after your existing code but before the closing script tag
                function showAlert(message, type = 'success') {
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

                // Replace the existing delete button event listeners with this new version
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const groupId = this.getAttribute('data-id');
                        const row = this.closest('tr');
                        const groupName = row.querySelector('td:nth-child(2)').textContent;
                        const statusCell = row.querySelector('td:nth-child(9) select'); // Get the status cell
                        const currentStatus = statusCell.value; // Get the current status

                        // Check if the status is 'NEW', 'ACTIVE', or 'RESOLVED'
                        if (currentStatus === 'NEW' || currentStatus === 'ACTIVE' || currentStatus === 'RESOLVED') {
                            // Show warning modal with a formal message
                            const warningMessage = document.getElementById('warningMessage');
                            warningMessage.textContent = `The report with a status of "${currentStatus}" cannot be deleted. Please change the status to unresolved issue before attempting to delete.`;
                            document.getElementById('statusWarningModal').style.display = 'block';
                            return; // Exit the function if the status is 'NEW', 'ACTIVE', or 'RESOLVED'
                        }

                        // Check if the status is 'UNRESOLVED'
                        if (currentStatus !== 'UNRESOLVED') {
                            alert(`This Report with a Status = ${currentStatus} can't be deleted.`);
                            return; // Exit the function if the status is not 'UNRESOLVED'
                        }

                        // Show modal
                        const modal = document.getElementById('deleteModal');
                        const groupNameSpan = document.getElementById('groupNameSpan');
                        groupNameSpan.textContent = groupName;
                        modal.style.display = 'block';
                        
                        // Handle cancel
                        document.getElementById('cancelDelete').onclick = function() {
                            modal.style.display = 'none';
                        };
                        
                        // Handle confirm delete
                        document.getElementById('confirmDelete').onclick = function() {
                            // Show loading state
                            const confirmBtn = this;
                            confirmBtn.disabled = true;
                            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                            
                            fetch('deleteGroup.php?id=' + groupId, {
                                method: 'POST',
                                credentials: 'same-origin'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Hide modal
                                    modal.style.display = 'none';
                                    
                                    // Animate row removal
                                    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                    row.style.opacity = '0';
                                    row.style.transform = 'translateX(-20px)';
                                    
                                    setTimeout(() => {
                                        row.remove();
                                        showAlert('Group deleted successfully');
                                        
                                        // Check if table is empty
                                        const tbody = document.querySelector('tbody');
                                        if (tbody.children.length === 0) {
                                            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No groups found</td></tr>';
                                        }
                                    }, 300);
                                } else {
                                    modal.style.display = 'none';
                                    showAlert(data.message || 'Error deleting group', 'error');
                                    // Reset button state
                                    confirmBtn.disabled = false;
                                    confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                modal.style.display = 'none';
                                showAlert('An error occurred while deleting the group', 'error');
                                confirmBtn.disabled = false;
                                confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                            });
                        };
                        
                        // Close modal when clicking outside
                        window.onclick = function(event) {
                            if (event.target === modal) {
                                modal.style.display = 'none';
                            }
                        };
                    });
                });

                // Add this event listener for submenu items
                const submenuItems = document.querySelectorAll('.submenu a');
                submenuItems.forEach(item => {
                    item.addEventListener('click', function(e) {
                        e.stopPropagation(); // Prevent the click from closing the dropdown
                    });
                });

                let currentDropdown; // To keep track of the current dropdown
                let currentIncidentId; // To keep track of the current incident ID
                let newStatus; // To store the new status temporarily

                // Add event listener for status dropdown changes
                const statusDropdowns = document.querySelectorAll('.status-dropdown');
                statusDropdowns.forEach(dropdown => {
                    dropdown.addEventListener('change', function() {
                        currentDropdown = this; // Store the current dropdown
                        currentIncidentId = this.getAttribute('data-id'); // Store the current incident ID
                        newStatus = this.value; // Store the new status

                        // Show confirmation modal
                        document.getElementById('newStatusSpan').textContent = newStatus;
                        document.getElementById('statusChangeModal').style.display = 'block';
                    });
                });

                // Handle confirm button click
                document.getElementById('confirmStatusChange').onclick = function() {
                    // Send the updated status to the server
                    fetch('updateStatus.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: currentIncidentId, status: newStatus }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Log the status change in the incident_history table
                            fetch('logStatusChange.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    incident_id: currentIncidentId,
                                    previous_status: currentDropdown.dataset.previousValue,
                                    new_status: newStatus
                                }),
                            });

                            showAlert('Status updated successfully');
                            // Update the dropdown value to the new status
                            currentDropdown.value = newStatus;
                        } else {
                            showAlert(data.message || 'Error updating status', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred while updating the status', 'error');
                    });

                    // Close the modal
                    document.getElementById('statusChangeModal').style.display = 'none';
                };

                // Handle cancel button click
                document.getElementById('cancelStatusChange').onclick = function() {
                    // Reset the dropdown to the previous value
                    currentDropdown.value = currentDropdown.dataset.previousValue;
                    document.getElementById('statusChangeModal').style.display = 'none';
                };

                // Store the previous value for cancel functionality
                statusDropdowns.forEach(dropdown => {
                    dropdown.dataset.previousValue = dropdown.value; // Initialize previous value
                    dropdown.addEventListener('focus', function() {
                        dropdown.dataset.previousValue = dropdown.value; // Update previous value on focus
                    });
                });

                // Add this event listener for the status filter dropdown
                document.getElementById('statusFilter').addEventListener('change', function() {
                    const selectedStatus = this.value.toUpperCase(); // Get the selected status
                    const rows = document.querySelectorAll('tbody tr'); // Get all table rows

                    rows.forEach(row => {
                        const statusCell = row.querySelector('td:nth-child(9) select'); // Get the status cell
                        const statusValue = statusCell.value; // Get the status value from the dropdown

                        // Show or hide the row based on the selected status
                        if (selectedStatus === 'ALL' || statusValue === selectedStatus) {
                            row.style.display = ''; // Show the row
                        } else {
                            row.style.display = 'none'; // Hide the row
                        }
                    });
                });
            });

            function openDescriptionModal(description) {
                // Create or select the modal
                let modal = document.getElementById('descModal');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'descModal';
                    modal.className = 'modal';
                    modal.innerHTML = `
                        <div class="modal-content" style="max-width:600px;">
                            <div class="modal-header">
                                <h2>Incident Details</h2>
                                <span class="close" onclick="closeDescriptionModal()">&times;</span>
                            </div>
                            <div class="modal-body" id="descModalBody" style="white-space: pre-line; color:#AEB2B7; font-family: 'Century Gothic', sans-serif;"></div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                }
                document.getElementById('descModalBody').textContent = description;
                modal.style.display = 'block';
            }
            function closeDescriptionModal() {
                let modal = document.getElementById('descModal');
                if (modal) modal.style.display = 'none';
            }

            // Also close desc modal on outside click
            window.onclick = function(event) {
                const imageModal = document.getElementById('imageModal');
                if (event.target === imageModal) {
                    closeModal();
                }
                const descModal = document.getElementById('descModal');
                if (descModal && event.target === descModal) {
                    closeDescriptionModal();
                }
            };
        </script>

        <!-- Custom Delete Modal -->
        <div id="deleteModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Delete Report</h2>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="groupNameSpan"></span>"?</p>
                    <p class="warning-text">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button id="cancelDelete" class="modal-btn cancel-btn">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button id="confirmDelete" class="modal-btn delete-btn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Add this modal for confirmation -->
        <div id="statusChangeModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Confirm Status Change</h2>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to change the status to "<span id="newStatusSpan"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <button id="cancelStatusChange" class="modal-btn cancel-btn">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button id="confirmStatusChange" class="modal-btn delete-btn">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal for displaying the image -->
        <div id="imageModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <img id="modalImage" src="" alt="Evidence" style="width: 100%; height: auto;">
            </div>
        </div>

        <!-- Modal for warning message -->
        <div id="statusWarningModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Warning</h2>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p id="warningMessage"></p>
                </div>
                <div class="modal-footer">
                    <button class="modal-btn cancel-btn" onclick="closeModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal for displaying all images -->
        <div id="imagesModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Evidence Images</h2>
                    <button class="modal-close" onclick="closeImagesModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="imagesContainer">
                    <!-- Image will be inserted here -->
                    <button class="image-nav-button prev" id="prevButton">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <img id="modalImage" src="" alt="Evidence">
                    <button class="image-nav-button next" id="nextButton">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="image-counter">
                    Image <span id="currentImageNum">1</span> of <span id="totalImages">1</span>
                </div>
            </div>
        </div>

        <!-- Modal for displaying the enlarged image -->
        <div id="enlargedImageModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="closeEnlargedImageModal()">&times;</span>
                <img id="enlargedImage" src="" alt="Enlarged Evidence" style="width: 100%; height: auto;">
            </div>
        </div>

        <script>
            function openModal(imageSrc) {
                document.getElementById('modalImage').src = imageSrc;
                document.getElementById('imageModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('imageModal').style.display = 'none';
                document.getElementById('deleteModal').style.display = 'none'; // Close delete modal
                document.getElementById('statusChangeModal').style.display = 'none'; // Close status change modal
                document.getElementById('statusWarningModal').style.display = 'none'; // Close warning modal
            }

            // Close the modal when clicking outside of the modal content
            window.onclick = function(event) {
                const modal = document.getElementById('imageModal');
                if (event.target === modal) {
                    closeModal();
                }
            };

            function openImagesModal(evidenceString) {
                const evidenceImages = evidenceString.split(',').map(image => image.trim());
                let currentIndex = 0;
                const totalImages = evidenceImages.length;

                const imagesContainer = document.getElementById('imagesContainer');
                const currentImageNum = document.getElementById('currentImageNum');
                const totalImagesSpan = document.getElementById('totalImages');
                const prevButton = document.querySelector('.image-nav-button.prev');
                const nextButton = document.querySelector('.image-nav-button.next');

                // Update total images count
                totalImagesSpan.textContent = totalImages;

                function updateImage() {
                    const imgElement = document.querySelector('#imagesContainer img');
                    imgElement.src = '../image/' + evidenceImages[currentIndex];
                    currentImageNum.textContent = currentIndex + 1;

                    // Update button visibility
                    prevButton.style.visibility = currentIndex === 0 ? 'hidden' : 'visible';
                    nextButton.style.visibility = currentIndex === totalImages - 1 ? 'hidden' : 'visible';
                }

                // Event listeners for navigation buttons
                prevButton.onclick = function() {
                    if (currentIndex > 0) {
                        currentIndex--;
                        updateImage();
                    }
                };

                nextButton.onclick = function() {
                    if (currentIndex < evidenceImages.length - 1) {
                        currentIndex++;
                        updateImage();
                    }
                };

                // Keyboard navigation
                document.addEventListener('keydown', function(e) {
                    if (document.getElementById('imagesModal').style.display === 'block') {
                        if (e.key === 'ArrowLeft' && currentIndex > 0) {
                            currentIndex--;
                            updateImage();
                        } else if (e.key === 'ArrowRight' && currentIndex < evidenceImages.length - 1) {
                            currentIndex++;
                            updateImage();
                        } else if (e.key === 'Escape') {
                            closeImagesModal();
                        }
                    }
                });

                // Initialize first image
                updateImage();
                document.getElementById('imagesModal').style.display = 'block';
            }

            function closeImagesModal() {
                document.getElementById('imagesModal').style.display = 'none';
            }

            function openEnlargedImageModal(imageSrc) {
                document.getElementById('enlargedImage').src = imageSrc;
                document.getElementById('enlargedImageModal').style.display = 'block';
            }

            function closeEnlargedImageModal() {
                document.getElementById('enlargedImageModal').style.display = 'none';
            }
        </script>
</body>
</html>