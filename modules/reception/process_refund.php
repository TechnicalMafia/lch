<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Process refund
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_group_id = $_POST['bill_group_id'];
    
    // Update all billing records with this bill_group_id to refund status
    $update_query = "UPDATE billing SET status = 'refund' WHERE bill_group_id = '$bill_group_id'";
    
    if ($conn->query($update_query) === TRUE) {
        // Redirect back to bill records with success message
        header('Location: bill_records.php?success=refund');
        exit;
    } else {
        // Redirect back with error message
        header('Location: bill_records.php?error=refund');
        exit;
    }
} else {
    // Redirect back if not POST request
    header('Location: bill_records.php');
    exit;
}
?>