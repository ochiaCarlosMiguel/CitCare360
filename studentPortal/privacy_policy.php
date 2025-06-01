<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - CITCARE 360</title>
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
        .privacy-container {
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
        .privacy-container h1 {
            color: #f4a261;
            text-align: center;
            margin-bottom: 20px;
        }
        .privacy-container h2 {
            color: #4F46E5;
            margin-top: 20px;
        }
        .privacy-container p {
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
            .privacy-container {
                padding: 20px;
                margin: 10px;
            }
            .privacy-container h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <button class="back-button" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </button>
    <div class="privacy-container">
        <div class="logo"><span class="cit">CIT</span>CARE 360</div>
        <h1>Privacy Policy</h1>
        
        <h2>1. Information We Collect</h2>
        <p>We collect the following types of information:
            <ul>
                <li>Personal identification information (name, email, phone number)</li>
                <li>Student information (student number, department)</li>
                <li>Profile picture</li>
                <li>Usage data and cookies</li>
            </ul>
        </p>

        <h2>2. How We Use Your Information</h2>
        <p>We use the collected information for:
            <ul>
                <li>Account creation and management</li>
                <li>Verification of student status</li>
                <li>Communication regarding your account</li>
                <li>Improving our services</li>
            </ul>
        </p>

        <h2>3. Data Protection</h2>
        <p>We implement appropriate security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction.</p>

        <h2>4. Data Sharing</h2>
        <p>We do not sell, trade, or rent your personal information to third parties. We may share your information only with:
            <ul>
                <li>School administration for verification purposes</li>
                <li>Service providers who assist in operating our platform</li>
                <li>When required by law</li>
            </ul>
        </p>

        <h2>5. Your Rights</h2>
        <p>You have the right to:
            <ul>
                <li>Access your personal information</li>
                <li>Correct inaccurate data</li>
                <li>Request deletion of your data</li>
                <li>Opt-out of certain data processing</li>
            </ul>
        </p>

        <h2>6. Cookies</h2>
        <p>We use cookies to improve your experience on our platform. You can control cookie settings through your browser preferences.</p>

        <h2>7. Changes to Privacy Policy</h2>
        <p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>

        <h2>8. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at privacy@citcare360.com</p>
    </div>
</body>
</html> 