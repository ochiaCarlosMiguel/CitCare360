<?php
// Start the session
session_start();

// Include database connection file
include('../connection/connection.php'); // Adjusted path to include the connection file from the parent directory

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the request ID and email from the POST data
    $requestId = $_POST['request_id'];
    $email = $_POST['email'];

    // Generate a new password (you can customize this logic)
    $newPassword = bin2hex(random_bytes(4)); // Generates a random 8-character password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT); // Hash the new password

    // Update the user's password in admin_users table
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    $stmt->execute();

    // Check if the password was updated in admin_users
    if ($stmt->affected_rows === 0) {
        // If no rows were affected, try updating in users table
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        $stmt->execute();
    }

    // Check if the password was updated successfully
    if ($stmt->affected_rows > 0) {
        // Update the status of the password reset request
        $stmt = $conn->prepare("UPDATE password_reset_requests SET status = 'approved', processed_by = ?, processed_date = NOW() WHERE id = ?");
        $adminUserId = $_SESSION['user_id']; // Use the admin's user ID
        $stmt->bind_param("ii", $adminUserId, $requestId);
        
        if ($stmt->execute()) {
            // Return success response as JSON
            echo json_encode([
                'status' => 'success',
                'message' => 'Password has been reset successfully.',
                'new_password' => $newPassword
            ]);
        } else {
            // Return error response as JSON
            echo json_encode([
                'status' => 'error',
                'message' => "Error updating the request status. Please try again."
            ]);
        }
    } else {
        // Return error response as JSON
        echo json_encode([
            'status' => 'error',
            'message' => "Error resetting the password. Please try again."
        ]);
    }
} else {
    // Invalid request method
    echo json_encode([
        'status' => 'error',
        'message' => "Invalid request."
    ]);
}
?>
