<?php
include('../connection/connection.php'); // Include your database connection

if (isset($_GET['id'])) {
    $userId = intval($_GET['id']); // Get the user ID from the request
    $query = "
        SELECT 
            u.first_name, 
            u.middle_name,
            u.last_name, 
            u.email, 
            u.phone_number, 
            u.department, 
            u.student_number, 
            u.user_profile,
            a.gender,
            a.age,
            a.place_of_birth,
            a.civil_status,
            a.nationality,
            a.religion,
            a.height,
            a.weight,
            a.blood_type,
            ad.house_number,
            ad.province,
            ad.municipality,
            ad.barangay,
            ad.zip_code,
            cp1.relationship AS contact_person_1_relationship,
            cp1.first_name AS contact_person_1_first_name,
            cp1.last_name AS contact_person_1_last_name,
            cp1.middle_name AS contact_person_1_middle_name,
            cp1.telephone_number AS contact_person_1_phone,
            cp1.contact_number AS contact_person_1_contact,
            cp1.email AS contact_person_1_email,
            cp1.complete_address AS contact_person_1_address,
            cp2.relationship AS contact_person_2_relationship,
            cp2.first_name AS contact_person_2_first_name,
            cp2.last_name AS contact_person_2_last_name,
            cp2.middle_name AS contact_person_2_middle_name,
            cp2.telephone_number AS contact_person_2_phone,
            cp2.contact_number AS contact_person_2_contact,
            cp2.email AS contact_person_2_email,
            cp2.complete_address AS contact_person_2_address
        FROM users u
        LEFT JOIN user_additional_info a ON u.id = a.user_id
        LEFT JOIN user_addresses ad ON u.id = ad.user_id
        LEFT JOIN contact_persons cp1 ON u.id = cp1.user_id
        LEFT JOIN contact_persons_2 cp2 ON u.id = cp2.user_id
        WHERE u.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user); // Return user data as JSON
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>