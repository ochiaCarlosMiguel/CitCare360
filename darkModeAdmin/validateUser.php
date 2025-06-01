<?php
session_start();
include('../connection/connection.php');

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];
$username = $data['username'];
$userId = $data['userId'];

// Prepare the SQL query to check for existing name or username
$query = "SELECT COUNT(*) as count FROM admin_users WHERE (name = ? OR username = ?) AND id != ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $name, $username, $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Name or username already exists.']);
} else {
    echo json_encode(['success' => true]);
}

// Check if the request is for updating user details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $userId = $_POST['user_id']; // Ensure you are passing the user ID

    // Prepare the SQL query to update user details
    $updateQuery = "UPDATE admin_users SET name = ?, username = ?, user_role = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sssi", $name, $username, $role, $userId);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User details updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user details.']);
    }
}
?>
