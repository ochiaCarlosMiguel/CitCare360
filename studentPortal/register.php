<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ?>
    <?php
    include '../connection/connection.php'; // Include the connection file
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Log submitted data for debugging
        error_log("Submitted data: " . print_r($_POST, true)); // Check what is being submitted

        // Check if department_id is set
        if (!isset($_POST['department_id'])) {
            echo "<script>alert('Department ID is missing.');</script>";
            exit; // Stop further execution if department ID is not set
        }

        // Capture the selected department ID
        $departmentId = $_POST['department_id']; 

        // Validate department exists
        $departmentCheck = $conn->prepare("SELECT id FROM departments WHERE id = ?");
        $departmentCheck->bind_param("i", $departmentId); // Bind the department ID
        $departmentCheck->execute();
        $departmentCheck->store_result();

        if ($departmentCheck->num_rows === 0) {
            echo "<script>alert('Selected department does not exist.');</script>";
            exit; // Stop further execution if department is invalid
        }

        // Check if email, student number, phone number, or user name already exists
        $email = $_POST['email'];
        $studentNumber = $_POST['student_number'];
        $phoneNumber = $_POST['phone_number'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $departmentId = $_POST['department_id'];

        // Verify if student exists in cit_students table
        $verifyStudent = $conn->prepare("SELECT id FROM cit_students WHERE 
            student_number = ? AND 
            first_name = ? AND 
            last_name = ? AND 
            email = ? AND 
            department_id = ?");
        $verifyStudent->bind_param("ssssi", $studentNumber, $firstName, $lastName, $email, $departmentId);
        $verifyStudent->execute();
        $verifyStudent->store_result();

        if ($verifyStudent->num_rows === 0) {
            echo "<script>alert('You are not authorized to register. Please verify your student information.');</script>";
            exit;
        }

        // Check for existing email
        $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();

        // Check for existing student number
        $checkStudentNumber = $conn->prepare("SELECT student_number FROM users WHERE student_number = ?");
        $checkStudentNumber->bind_param("s", $studentNumber);
        $checkStudentNumber->execute();
        $checkStudentNumber->store_result();

        // Check for existing phone number
        $checkPhoneNumber = $conn->prepare("SELECT phone_number FROM users WHERE phone_number = ?");
        $checkPhoneNumber->bind_param("s", $phoneNumber);
        $checkPhoneNumber->execute();
        $checkPhoneNumber->store_result();

        // Set error messages if duplicates are found
        if ($checkEmail->num_rows > 0) {
            echo "<script>document.getElementById('emailError').textContent = 'This email is already registered.';</script>";
        } elseif ($checkStudentNumber->num_rows > 0) {
            echo "<script>document.getElementById('studentNumberError').textContent = 'This student number is already registered.';</script>";
        } elseif ($checkPhoneNumber->num_rows > 0) {
            echo "<script>document.getElementById('phoneNumberError').textContent = 'This phone number is already registered.';</script>";
        } else {
            // Handle file upload
            if (isset($_FILES["user_profile"]) && $_FILES["user_profile"]["error"] == 0) {
                $targetDir = "../image/";
                // Check if the directory exists, if not, create it
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $targetFile = $targetDir . basename($_FILES["user_profile"]["name"]);
                $uploadOk = 1;
                $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

                // Check if image file is a actual image or fake image
                $check = getimagesize($_FILES["user_profile"]["tmp_name"]);
                if ($check !== false) {
                    $uploadOk = 1;
                } else {
                    echo "<script>alert('File is not an image.');</script>";
                    $uploadOk = 0;
                }

                // Check file size (limit to 5MB)
                if ($_FILES["user_profile"]["size"] > 5000000) {
                    echo "<script>alert('Sorry, your file is too large.');</script>";
                    $uploadOk = 0;
                }

                // Allow certain file formats
                if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                    echo "<script>alert('Sorry, only JPG, JPEG, PNG & GIF files are allowed.');</script>";
                    $uploadOk = 0;
                }

                // Check if $uploadOk is set to 0 by an error
                if ($uploadOk == 0) {
                    echo "<script>alert('Sorry, your file was not uploaded.');</script>";
                } else {
                    if (!move_uploaded_file($_FILES["user_profile"]["tmp_name"], $targetFile)) {
                        echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
                    }
                }
            } else {
                echo "<script>alert('Profile image is required.');</script>";
                exit; // Stop further execution if no image is uploaded
            }

            // Encrypt the password
            $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);

            // Set parameters and execute
            $firstName = $_POST['first_name'];
            $lastName = $_POST['last_name'];
            $middleName = $_POST['middle_name'] ?? ''; // Default to empty string if not set
            $email = $_POST['email'];
            $phoneNumber = $_POST['phone_number'];
            $studentNumber = $_POST['student_number'];
            $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);

            // Prepare and bind
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, middle_name, email, phone_number, department, student_number, password, user_profile, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssssssss", $firstName, $lastName, $middleName, $email, $phoneNumber, $departmentId, $studentNumber, $passwordHash, $targetFile);

            if ($stmt->execute()) {
                echo "<script>alert('New record created successfully');</script>";
            } else {
                echo "<script>alert('Error: " . $stmt->error . "');</script>";
            }

            // Close the statement
            $stmt->close();
        }

        $checkEmail->close();
        $checkStudentNumber->close();
        $checkPhoneNumber->close();
    }

    // Fetch department names from the database
    $departmentQuery = $conn->prepare("SELECT id, name FROM departments");
    $departmentQuery->execute();
    $departmentResult = $departmentQuery->get_result();
    $departments = $departmentResult->fetch_all(MYSQLI_ASSOC);

    // Log the departments array for debugging
    error_log("Departments: " . print_r($departments, true)); // Check the contents of the departments array

    // Ensure the connection is open before executing queries
    if ($conn) {
        // Check for errors in the query
        if ($departmentQuery->error) {
            error_log("Database error: " . $departmentQuery->error);
        }
    } else {
        die("Database connection failed.");
    }

    // Close the connection at the end of the script
    $conn->close();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CITCARE 360</title>
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
        .registration-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 20px;
        }
        .registration-box-container {
            background: rgba(34, 34, 50, 0.8);
            backdrop-filter: blur(10px);
            padding: 20px;
            border: 2px solid #3D3C4B;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            position: relative;
            margin: 20px;
        }
        .registration-box {
            width: 100%;
            text-align: center;
        }
        .registration-box h2 {
            margin-bottom: 20px;
            color: #fff;
        }
        .registration-box input, .registration-box select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            position: relative;
        }
        .registration-box button {
            width: 100%;
            padding: 10px;
            background: #4F46E5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .registration-box button:hover {
            background: #f4a261;
        }
        .secondary-button {
            background: #4F46E5;
            margin-top: 10px;
            border-radius: 8px;
        }
        .secondary-button:hover {
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
            .registration-box-container {
                padding: 15px;
                margin: 10px;
            }
            .registration-box h2 {
                font-size: 24px;
            }
            .registration-box input, .registration-box select {
                padding: 8px;
                margin-bottom: 15px;
            }
            .registration-box button {
                padding: 10px;
                font-size: 16px;
            }
            .error-message {
                font-size: 12px;
            }
        }
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: -15px;
            margin-bottom: 15px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-box-container">
            <button class="exit-btn" onclick="redirectToLogin()">
                <i class="fas fa-times"></i>
            </button>
            <div class="registration-box">
                <div class="logo"><span class="cit">CIT</span>CARE 360</div>
                <h2>Register</h2>
                <form method="POST" action="" id="registrationForm" enctype="multipart/form-data">
                    <div style="position: relative;">
                        <input type="text" name="first_name" placeholder="First Name" required aria-label="First Name">
                        <div class="error-message" id="firstNameError"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="text" name="middle_name" placeholder="Middle Initial" aria-label="Middle Name">
                        <div class="error-message" id="middleNameError"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="text" name="last_name" placeholder="Last Name" required aria-label="Last Name">
                        <div class="error-message" id="lastNameError"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="email" name="email" placeholder="Email" required aria-label="Email">
                        <div class="error-message" id="emailError"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="tel" name="phone_number" placeholder="Phone Number" required aria-label="Phone Number" pattern="(\+63|0)9\d{9}" title="Please enter a valid Philippine phone number">
                        <div class="error-message" id="phoneNumberError"></div>
                    </div>
                    <div style="position: relative;">
                        <select name="department_id" required aria-label="Department">
                            <option value="" disabled selected>Choose Department</option>
                            <?php foreach ($departments as $department): ?>
                                <?php if (!empty($department['id']) && !empty($department['name'])): ?>
                                    <option value="<?php echo htmlspecialchars($department['id']); ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-message" id="departmentError"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="text" name="student_number" placeholder="Student Number" required aria-label="Student Number" pattern="\d*" title="Please enter a valid student number">
                        <div class="error-message" id="studentNumberError"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="password" name="password" placeholder="Password" required aria-label="Password">
                        <div class="error-message" id="passwordError"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required aria-label="Confirm Password">
                        <div class="error-message" id="confirmPasswordError"></div>
                    </div>
                    <div style="position: relative;">
                        <input type="file" name="user_profile" accept="image/*" aria-label="User Profile">
                        <div class="error-message" id="userProfileError"></div>
                    </div>
                    <div style="position: relative; text-align: left; margin-bottom: 20px; display: flex; align-items: center; gap: 6px;">
                        <input type="checkbox" name="terms_conditions" id="terms_conditions" required style="margin: 0;">
                        <label for="terms_conditions" style="margin: 0; font-weight: 400; white-space: nowrap; font-size: 14px;">
                            I read and agreed to the
                            <a href="terms_conditions.php" style="color: #a259c4; text-decoration: underline; white-space: nowrap;"> Terms & Conditions</a>
                            &amp; <br>
                            <a href="privacy_policy.php" style="color: #a259c4; text-decoration: underline; white-space: nowrap;"> Privacy Policy</a>
                        </label>
                        <div class="error-message" id="termsError" style="flex-basis: 100%;"></div>
                    </div>
                    <button type="submit">Register</button>
                </form>
                <button class="secondary-button" onclick="redirectToLogin()">Already have an account?</button>
            </div>
        </div>
    </div>
    <script>
        function redirectToLogin() {
            window.location.href = 'login.php';
        }

        function redirectToLandingPage() {
            window.location.href = 'landingPage.php';
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        document.getElementById('registrationForm').addEventListener('submit', function(event) {
            let hasError = false;

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(div => div.textContent = '');

            // Validate all fields
            const fields = ['first_name', 'last_name', 'email', 'phone_number', 'department_id', 'student_number', 'password', 'confirm_password'];
            fields.forEach(field => {
                const input = document.querySelector(`input[name="${field}"], select[name="${field}"]`);
                const errorDiv = document.getElementById(`${field}Error`);
                if (!input.value.trim()) {
                    errorDiv.textContent = `${field.replace('_', ' ')} is required.`;
                    hasError = true;
                }
            });

            // Validate phone number format
            const phoneNumberInput = document.querySelector('input[name="phone_number"]');
            const phoneNumberErrorDiv = document.getElementById('phoneNumberError');
            const phonePattern = /^(\+63|0)9\d{9}$/;
            if (!phonePattern.test(phoneNumberInput.value)) {
                phoneNumberErrorDiv.textContent = "Please enter a valid Philippine phone number.";
                hasError = true;
            }

            // Validate student number is numeric
            const studentNumberInput = document.querySelector('input[name="student_number"]');
            const studentNumberErrorDiv = document.getElementById('studentNumberError');
            if (isNaN(studentNumberInput.value) || studentNumberInput.value.trim() === '') {
                studentNumberErrorDiv.textContent = "Student Number must be a number.";
                hasError = true;
            }

            // Validate email
            const emailInput = document.querySelector('input[name="email"]');
            const email = emailInput.value;
            const emailErrorDiv = document.getElementById('emailError');
            if (!validateEmail(email)) {
                emailErrorDiv.textContent = "Invalid Email Address";
                hasError = true;
            }

            // Validate password match
            const passwordInput = document.querySelector('input[name="password"]');
            const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
            const passwordErrorDiv = document.getElementById('passwordError');
            if (passwordInput.value !== confirmPasswordInput.value) {
                passwordErrorDiv.textContent = "Passwords do not match";
                hasError = true;
            }

            // Validate image upload
            const userProfileInput = document.querySelector('input[name="user_profile"]');
            const userProfileErrorDiv = document.getElementById('userProfileError');
            if (!userProfileInput.files.length) {
                userProfileErrorDiv.textContent = "Profile image is required.";
                hasError = true;
            }

            // Validate terms and conditions checkbox
            const termsCheckbox = document.querySelector('input[name="terms_conditions"]');
            const termsErrorDiv = document.getElementById('termsError');
            if (!termsCheckbox.checked) {
                termsErrorDiv.textContent = "You must agree to the Terms & Conditions and Privacy Policy to register.";
                hasError = true;
            }

            // Check for existing values in the database
            const existingValues = {
                email: emailInput.value,
                phone_number: phoneNumberInput.value,
                student_number: studentNumberInput.value,
                first_name: document.querySelector('input[name="first_name"]').value,
                last_name: document.querySelector('input[name="last_name"]').value,
                department_id: document.querySelector('select[name="department_id"]').value
            };

            // Make an AJAX request to check for existing values
            if (!hasError) {
                fetch('check_existing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(existingValues)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.emailExists) {
                        emailErrorDiv.textContent = "This email is already registered.";
                        hasError = true;
                    }
                    if (data.phoneExists) {
                        phoneNumberErrorDiv.textContent = "This phone number is already registered.";
                        hasError = true;
                    }
                    if (data.studentExists) {
                        studentNumberErrorDiv.textContent = "This student number is already registered.";
                        hasError = true;
                    }

                    // Show validation errors for each field
                    if (data.validationErrors) {
                        if (data.validationErrors.first_name) {
                            document.getElementById('firstNameError').textContent = "First name does not match our records.";
                            hasError = true;
                        }
                        if (data.validationErrors.last_name) {
                            document.getElementById('lastNameError').textContent = "Last name does not match our records.";
                            hasError = true;
                        }
                        if (data.validationErrors.email) {
                            document.getElementById('emailError').textContent = "Email does not match our records.";
                            hasError = true;
                        }
                        if (data.validationErrors.student_number) {
                            document.getElementById('studentNumberError').textContent = "Student number does not match our records.";
                            hasError = true;
                        }
                        if (data.validationErrors.department_id) {
                            document.getElementById('departmentError').textContent = "Department does not match our records.";
                            hasError = true;
                        }
                    }

                    // If there are no errors, submit the form
                    if (!hasError) {
                        event.target.submit(); // Submit the form
                    }
                });
                event.preventDefault(); // Prevent the default form submission
            } else {
                event.preventDefault(); // Prevent form submission if there are errors
            }
        });
    </script>
</body>
</html>