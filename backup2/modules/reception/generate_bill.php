<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get patient ID from URL
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
$bill_group_id = isset($_GET['bill_group_id']) ? $_GET['bill_group_id'] : '';

// Validate inputs
if (empty($patient_id)) {
    header('Location: billing.php?error=missing_patient_id');
    exit;
}

// Get patient details
$patient = getPatient($patient_id);

// Get bill details
if ($bill_group_id) {
    $query = "SELECT * FROM billing WHERE bill_group_id = '$bill_group_id'";
    $result = $conn->query($query);
    
    $bill_items = [];
    $total_amount = 0;
    $total_discount = 0;
    $total_paid = 0;
    
    while ($row = $result->fetch_assoc()) {
        $bill_items[] = $row;
        $total_amount += $row['amount'];
        $total_discount += $row['discount'];
        $total_paid += $row['paid_amount'];
    }
} else {
    // Get all bills for this patient
    $bills = getPatientBills($patient_id);
    
    $bill_items = [];
    $total_amount = 0;
    $total_discount = 0;
    $total_paid = 0;
    
    while ($bill = $bills->fetch_assoc()) {
        $bill_items[] = $bill;
        $total_amount += $bill['amount'];
        $total_discount += $bill['discount'];
        $total_paid += $bill['paid_amount'];
    }
}

// Generate PDF content
$pdf_content = '
<!DOCTYPE html>
<html>
<head>
    <title>Bill - Hospital Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .totals {
            text-align: right;
            margin-top: 20px;
        }
        .signature {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-box .line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>HOSPITAL MANAGEMENT SYSTEM</h1>
        <p>123 Medical Street, Lahore</p>
        <p>Phone: 0300-1234567</p>
    </div>
    
    <div class="info-box">
        <h3>Patient Information</h3>
        <div class="info-row">
            <span>Patient ID:</span>
            <span>' . $patient['id'] . '</span>
        </div>
        <div class="info-row">
            <span>Name:</span>
            <span>' . $patient['name'] . '</span>
        </div>
        <div class="info-row">
            <span>Age:</span>
            <span>' . $patient['age'] . '</span>
        </div>
        <div class="info-row">
            <span>Gender:</span>
            <span>' . $patient['gender'] . '</span>
        </div>
        <div class="info-row">
            <span>Contact:</span>
            <span>' . $patient['contact'] . '</span>
        </div>
        <div class="info-row">
            <span>Emergency Contact:</span>
            <span>' . ($patient['emergency_contact'] ?: 'N/A') . '</span>
        </div>
        <div class="info-row">
            <span>Relative Name:</span>
            <span>' . ($patient['relative_name'] ?: 'N/A') . '</span>
        </div>
        <div class="info-row">
            <span>NIC:</span>
            <span>' . ($patient['nic'] ?: 'N/A') . '</span>
        </div>
        <div class="info-row">
            <span>Address:</span>
            <span>' . ($patient['address'] ?: 'N/A') . '</span>
        </div>
    </div>
    
    <h3>Bill Details</h3>
    <table>
        <thead>
            <tr>
                <th>Service</th>
                <th>Amount</th>
                <th>Discount</th>
                <th>Paid</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

foreach ($bill_items as $item) {
    $pdf_content .= '
            <tr>
                <td>' . $item['service_name'] . '</td>
                <td>' . formatCurrency($item['amount']) . '</td>
                <td>' . formatCurrency($item['discount']) . '</td>
                <td>' . formatCurrency($item['paid_amount']) . '</td>
                <td>' . ucfirst($item['status']) . '</td>
            </tr>';
}

$pdf_content .= '
        </tbody>
    </table>
    
    <div class="totals">
        <div class="info-row">
            <span>Total Amount:</span>
            <span>' . formatCurrency($total_amount) . '</span>
        </div>
        <div class="info-row">
            <span>Total Discount:</span>
            <span>' . formatCurrency($total_discount) . '</span>
        </div>
        <div class="info-row">
            <span>Total Paid:</span>
            <span>' . formatCurrency($total_paid) . '</span>
        </div>
        <div class="info-row">
            <span>Balance:</span>
            <span>' . formatCurrency($total_amount - $total_discount - $total_paid) . '</span>
        </div>
    </div>
    
    <div class="signature">
        <div class="signature-box">
            <div class="line"></div>
            <p>Patient Signature</p>
        </div>
        <div class="signature-box">
            <div class="line"></div>
            <p>Hospital Signature</p>
        </div>
    </div>
</body>
</html>';

// Save PDF to file
$filename = 'bill_' . $patient_id . '_' . time() . '.pdf';
$file_path = '../../uploads/bills/' . $filename;

// In a real application, you would use a PDF library like TCPDF or mPDF
// For this example, we'll just save the HTML content
file_put_contents($file_path, $pdf_content);

// Redirect to billing page with success message
header('Location: billing.php?success=1&file=' . $filename);
exit;
?>