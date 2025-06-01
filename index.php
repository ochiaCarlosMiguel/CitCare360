<?php
include './connection/connection.php'; // Include the database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>School Management System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Base styles */
        * {
            box-sizing: border-box; /* Include padding and border in element's total width and height */
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460);
            background-size: 300% 300%;
            color: #e0e0e0;
            text-align: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            animation: gradientBG 15s ease infinite;
        }
        html {
            height: 100%;
            background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460);
            background-size: 300% 300%;
            animation: gradientBG 15s ease infinite;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 10, 0.4);
            z-index: -1;
        }
        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        .logo {
            display: flex; /* Use flexbox for better alignment */
            align-items: center; /* Center items vertically */
            justify-content: center; /* Center items horizontally */
            margin-top: 50px; /* Increased margin for better spacing */
        }
        .logo span.cit {
            color: #4F46E5;
        }

        .container {
            flex: 1; /* Allow container to grow and push footer down */
            padding: 50px;
        }

        .glass-container {
            background: rgba(255, 255, 255, 0.1); /* More transparent for a modern glass effect */
            border: 1px solid rgba(255, 255, 255, 0.3); /* Light border */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3); /* Deeper shadow for depth */
            backdrop-filter: blur(15px); /* Enhanced blur effect */
            padding: 40px; /* Increased padding for better spacing */
            border-radius: 15px; /* More rounded corners */
            margin: 20px auto; /* Center the container */
            max-width: 600px; /* Limit the width for better layout */
        }

        footer {
            width: 100%;
            margin: 0;
            font-size: 0.8em;
            background-color: #333;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-top: 2px solid #f4a261;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            body {
                font-size: 0.9em;
            }

            header h1 {
                font-size: 2.5em; /* Adjusted for smaller screens */
            }

            header h2 {
                font-size: 1.2em; /* Adjusted for smaller screens */
            }

            button {
                font-size: 1em; /* Adjusted for smaller screens */
            }

            footer {
                font-size: 0.7em;
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            body {
                font-size: 0.8em;
            }

            header h1 {
                font-size: 2em; /* Adjusted for smaller screens */
            }

            header h2 {
                font-size: 1em; /* Adjusted for smaller screens */
            }

            button {
                font-size: 0.9em; /* Adjusted for smaller screens */
            }

            footer {
                font-size: 0.6em;
                padding: 10px;
            }
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 10, 0.9);
            z-index: -1;
        }

        header h1 {
            font-size: 3em; /* Increased font size for better visibility */
            color: #f4a261; /* Retained color for contrast */
            margin-bottom: 10px; /* Added margin for spacing */
        }

        header h2 {
            font-size: 1.5em; /* Increased font size for better visibility */
            color: #ffffff; /* Retained color for contrast */
            margin-bottom: 20px; /* Added margin for spacing */
        }

        .buttons {
            margin: 20px 0;
        }

        button {
            background-color: #4F46E5; /* Solid professional color */
            border: none; /* Remove border */
            color: white; /* White text for contrast */
            padding: 12px 24px; /* Increased padding for a more modern look */
            margin: 5px;
            cursor: pointer;
            font-size: 1.1em; /* Slightly larger font size */
            border-radius: 8px; /* More rounded corners */
            transition: background-color 0.3s ease, transform 0.2s; /* Added transform transition */
        }

        button:hover {
            background-color: #3B3B98; /* Slightly darker shade on hover */
            transform: scale(1.05); /* Slightly enlarge button on hover */
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
    </style>
</head>
<body>
    <div class="logo">
        <img src="./image/logo.png" alt="Logo" class="logo-image">
        <span class="cit">CIT</span>CARE 360
    </div>
    <div class="container">
        <header>
            <h1>College of Industrial Technology</h1>
            <h2>Student Support & Emergency System</h2>
        </header>
        <div class="glass-container">
            <h3>What are you?</h3>
            <div class="buttons">
                <a href="./studentPortal/login.php">
                    <button>STUDENT</button>
                </a>
                <a href="./adminPortal/login.php">
                    <button>ADMIN</button>
                </a>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for playful animation
        window.onload = function() {
            const logoImage = document.querySelector('.logo-image');
            logoImage.style.animation = 'rollAndZoom 2s forwards'; // Apply roll and zoom animation
        };

        // Keyframes for roll and zoom animation
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes rollAndZoom {
                0% {
                    transform: translateX(-100%) rotate(0deg); /* Start off-screen to the left */
                }
                50% {
                    transform: translateX(0) rotate(360deg); /* Roll into position */
                }
                100% {
                    transform: scale(1.5); /* Zoom in */
                }
            }
            .logo-image {
                width: 50px; /* Set a smaller size for the logo image */
                margin-right: 10px; /* Space between image and text */
                vertical-align: middle; /* Align image with text */
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>