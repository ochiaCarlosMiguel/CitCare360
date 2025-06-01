<?php
// Start the session
session_start();

// Include database connection file
include('../connection/connection.php'); // Adjusted path to include the connection file from the parent directory

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    // Check if the email exists in admin_users or users
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? UNION SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Insert password reset request
        $stmt = $conn->prepare("INSERT INTO password_reset_requests (email) VALUES (?)");
        $stmt->bind_param("s", $email);
        
        if ($stmt->execute()) {
            $success = "Your password reset request has been submitted. Please contact the administrator.";
        } else {
            $error = "Error submitting request. Please try again.";
        }
    } else {
        $error = "Email does not exist.";
    }
}

// Change the title and form action for forgot password
$title = "Forgot Password - CITCARE 360";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="../favicon.png">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: url('../image/bg.png') no-repeat center center fixed;
            background-size: cover;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
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
            background: rgba(10, 10, 10, 0.5); /* Lighter overlay with reduced opacity */
            z-index: -1;
        }
        .logo {
            font-size: 36px;
            font-weight: 700;
            color: #f4a261;
            text-shadow: 2px 2px #09243B;
            margin-bottom: 20px;
            text-align: center;
        }
        .logo span.cit {
            color: #4F46E5;
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 20px;
            flex-direction: column;
        }
        .login-box-container {
            background: rgba(34, 34, 50, 0.8);
            backdrop-filter: blur(10px);
            padding: 20px;
            border: 2px solid #3D3C4B;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 351px;
            height: auto;
            box-sizing: border-box;
            position: relative;
        }
        .login-box {
            width: 100%;
            text-align: center;
        }
        .login-box h2 {
            margin-bottom: 20px;
            color: #fff;
        }
        .login-box input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #ccc;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #ccc;
        }
        .login-box button {
            width: 100%;
            padding: 10px;
            background: #4F46E5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .login-box button:hover {
            background: #f4a261;
        }
        .exit-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: #f4a261;
            font-size: 20px;
            cursor: pointer;
        }
        @media (max-width: 600px) {
            .login-box-container {
                padding: 20px;
            }
            .logo {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box-container">
            <button class="exit-btn" onclick="window.location.href='../index.php';">
                <i class="fas fa-times"></i>
            </button>
            <div class="login-box">
                <div class="logo"><span class="cit">CIT</span>CARE 360</div>
                <h2>Forgot Password</h2>
                <?php if (isset($error)) { echo "<p style='color: red;'>$error</p>"; } ?>
                <?php if (isset($success)) { echo "<p style='color: green;'>$success</p>"; } ?>
                <form method="POST" action="" autocomplete="off">
                    <div style="position: relative;">
                        <input type="email" name="email" placeholder="Enter your email" required autocomplete="off">
                    </div>
                    <button type="submit">Submit Reset Request</button>
                    <p><a href="login.php" style="color: #4F46E5;">Back to Login</a></p>
                </form>
            </div>
        </div>
    </div>
    <script>
        function redirectToHomePage() {
            window.location.href = '/admin/dashboard.php';
        }
    </script>
</body>
</html>
