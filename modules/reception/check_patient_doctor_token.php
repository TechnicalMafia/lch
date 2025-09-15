<?php
require_once '../../includes/functions.php';
requireRole('reception');

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    
    // Check if patient already has a waiting doctor token
    $check_query = "SELECT token_no FROM tokens 
                   WHERE patient_id = $patient_id 
                   AND type = 'doctor' 
                   AND status = 'waiting'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'hasToken' => true,
            'tokenNo' => $row['token_no']
        ]);
    } else {
        echo json_encode(['hasToken' => false]);
    }
}
?>