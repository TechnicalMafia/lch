<?php
require_once '../../includes/functions.php';
requireRole('reception');

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    
    $check_assignment_query = "SELECT ra.*, r.room_name 
                             FROM room_assignments ra 
                             JOIN rooms r ON ra.room_id = r.id 
                             WHERE ra.patient_id = $patient_id";
    $result = $conn->query($check_assignment_query);
    
    if ($result->num_rows > 0) {
        $assignment = $result->fetch_assoc();
        echo json_encode([
            'hasRoom' => true,
            'roomName' => $assignment['room_name']
        ]);
    } else {
        echo json_encode(['hasRoom' => false]);
    }
}
?>