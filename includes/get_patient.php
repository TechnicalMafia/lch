<?php
include 'db.php';

if (isset($_GET['id'])) {
    $patient_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT name, age, gender FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($patient = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'patient' => $patient]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>