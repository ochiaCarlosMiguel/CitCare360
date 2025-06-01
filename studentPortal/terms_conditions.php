<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - CITCARE 360</title>
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
            background: rgba(10, 10, 10, 0.5);
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
        .terms-container {
            background: rgba(34, 34, 50, 0.8);
            backdrop-filter: blur(10px);
            padding: 30px;
            border: 2px solid #3D3C4B;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            color: #fff;
            margin: 20px;
        }
        .terms-container h1 {
            color: #f4a261;
            text-align: center;
            margin-bottom: 20px;
        }
        .terms-container h2 {
            color: #4F46E5;
            margin-top: 20px;
        }
        .terms-container p {
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #4F46E5;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .back-button:hover {
            background: #f4a261;
        }
        @media (max-width: 600px) {
            .terms-container {
                padding: 20px;
                margin: 10px;
            }
            .terms-container h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <button class="back-button" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </button>
    <div class="terms-container">
        <div class="logo"><span class="cit">CIT</span>CARE 360</div>
        <h1>Terms & Conditions</h1>
        
        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using CITCARE 360, you accept and agree to be bound by the terms and provision of this agreement.</p>

        <h2>2. User Account</h2>
        <p>You are responsible for maintaining the confidentiality of your account and password. You agree to accept responsibility for all activities that occur under your account.</p>

        <h2>3. Student Information</h2>
        <p>You must provide accurate and complete information during registration. Any false information may result in account termination.</p>

        <h2>4. Privacy</h2>
        <p>Your personal information will be handled in accordance with our Privacy Policy. Please review our Privacy Policy for more information.</p>

        <h2>5. Code of Conduct</h2>
        <p>Users must not engage in any activity that disrupts or interferes with the service, including but not limited to:
            <ul>
                <li>Uploading or transmitting viruses or malicious code</li>
                <li>Attempting to gain unauthorized access to the system</li>
                <li>Harassing or threatening other users</li>
            </ul>
        </p>

        <h2>6. Intellectual Property</h2>
        <p>All content and materials available on CITCARE 360 are protected by intellectual property rights and are the property of their respective owners.</p>

        <h2>7. Termination</h2>
        <p>We reserve the right to terminate or suspend your account at any time, without notice, for conduct that we believe violates these Terms and Conditions or is harmful to other users, us, or third parties.</p>

        <h2>8. Changes to Terms</h2>
        <p>We reserve the right to modify these terms at any time. We will notify users of any changes by posting the new Terms and Conditions on this page.</p>

        <h2>9. Contact Information</h2>
        <p>If you have any questions about these Terms and Conditions, please contact us at support@citcare360.com</p>
    </div>
</body>
</html> 