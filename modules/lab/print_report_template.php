<?php
require_once '../../includes/functions.php';
requireRole('lab');

// Get report ID from URL
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id <= 0) {
    die('Invalid report ID');
}

// Get report details
$query = "SELECT lr.*, p.name as patient_name, p.age, p.gender 
          FROM lab_reports lr 
          JOIN patients p ON lr.patient_id = p.id 
          WHERE lr.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    die('Report not found');
}

// Get test results for this report
$results_query = "SELECT * FROM lab_test_results WHERE lab_report_id = ?";
$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("i", $report_id);
$results_stmt->execute();
$results_result = $results_stmt->get_result();

$test_results = [];
while ($row = $results_result->fetch_assoc()) {
    $test_results[$row['test_category']][$row['test_name']] = $row['result_value'];
}

// Check if HTML report file exists
$report_file = '../../uploads/reports/' . $report['file_path'];
if (file_exists($report_file)) {
    // Display the saved HTML report
    echo file_get_contents($report_file);
} else {
    // Generate report on the fly if file doesn't exist
    echo generatePrintableReport($report, $test_results);
}

// Function to generate printable report if file doesn't exist
function generatePrintableReport($report, $test_results) {
    $current_date = date('d/m/Y');
    $header_class = ($report['report_type'] == 'bio_chemistry' || $report['report_type'] == 'haematology') ? 'red' : 'blue';
    $title_color = ($report['report_type'] == 'bio_chemistry' || $report['report_type'] == 'haematology') ? '#dc2626' : '#2563eb';
    
    $report_titles = [
        'bio_chemistry' => 'BIO CHEMISTRY REPORT',
        'haematology' => 'HAEMATOLOGY REPORT', 
        'urine_analysis' => 'URINE ANALYSIS',
        'serology' => 'SEROLOGY REPORT'
    ];
    
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lab Report - ' . htmlspecialchars($report['patient_name']) . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 15px;
            font-size: 11px;
        }
        .shahmir-header {
            background: linear-gradient(135deg, ' . ($header_class == 'red' ? '#dc2626' : '#2563eb') . ' 0%, ' . ($header_class == 'red' ? '#dc2626' : '#2563eb') . ' 100%);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
        }
        .shahmir-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .logo-circle {
            width: 60px;
            height: 60px;
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            font-size: 24px;
        }
        .report-table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
            font-size: 10px;
        }
        .report-table th,
        .report-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        .report-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        .category-header {
            background-color: #e5e5e5 !important;
            font-weight: bold;
            text-align: center;
            color: ' . $title_color . ';
            font-size: 10px;
        }
        .hospital-footer {
            background: linear-gradient(135deg, ' . ($header_class == 'red' ? '#dc2626' : '#2563eb') . ' 0%, ' . ($header_class == 'red' ? '#dc2626' : '#2563eb') . ' 100%);
            color: white;
            padding: 8px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 9px;
            margin-top: 15px;
        }
        .patient-info-row {
            margin: 10px 0;
            font-size: 11px;
        }
        h2 { 
            color: ' . $title_color . '; 
            font-size: 18px; 
            margin: 20px 0; 
            text-align: center;
        }
        .result-value {
            font-weight: bold;
            font-size: 11px;
            text-align: center;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            @page { margin: 0.5in; }
        }
    </style>
</head>
<body>
    <div class="shahmir-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div class="shahmir-logo">
                <div class="logo-circle">🔬</div>
                <div>
                    <h1 style="font-size: 24px; font-weight: bold; margin: 0;">Shahmir Laboratory</h1>
                    <p style="font-size: 12px; opacity: 0.9; margin: 0;">Facility Available for Multi Lab Collection</p>
                </div>
            </div>
            <div style="text-align: right; font-size: 12px;">
                <p><strong>Timings</strong></p>
                <p>Monday-Sunday: Open Round The Clock</p>
            </div>
        </div>
        <div class="patient-info-row">
            <strong>Name:</strong> ' . htmlspecialchars($report['patient_name']) . ' &nbsp;&nbsp;&nbsp; 
            <strong>Age:</strong> ' . $report['age'] . ' &nbsp;&nbsp;&nbsp; 
            <strong>Sex:</strong> ' . $report['gender'] . ' &nbsp;&nbsp;&nbsp; 
            <strong>Date:</strong> ' . date('d/m/Y', strtotime($report['created_at'])) . '
        </div>
        <div style="font-size: 11px;">
            <strong>Referred By:</strong> .........................................................................................................................................
        </div>
    </div>
    
    <h2>' . $report_titles[$report['report_type']] . '</h2>';
    
    // Generate appropriate table based on report type
    if (!empty($test_results)) {
        if ($report['report_type'] == 'bio_chemistry') {
            $html = generateBioChemTable($test_results);
        } elseif ($report['report_type'] == 'haematology') {
            $html = generateHaematologyTable($test_results);
        } elseif ($report['report_type'] == 'urine_analysis') {
            $html = generateUrineTable($test_results);
        } elseif ($report['report_type'] == 'serology') {
            $html = generateSerologyTable($test_results);
        }
    }
    
    return $html . '
    <div style="margin-top: 30px; font-size: 10px;">
        <p><strong>Technician:</strong> ' . htmlspecialchars($report['technician_name']) . '</p>
        <p><strong>Remarks:</strong> ' . htmlspecialchars($report['remarks']) . '</p>
        <p><strong>Report Generated:</strong> ' . date('d/m/Y h:i A', strtotime($report['created_at'])) . '</p>
    </div>
    
    <div style="font-size: 10px; color: #dc2626; margin: 20px 0; text-align: center;">
        <strong>Not for Medico legal / Court use</strong><br>
        <strong>Please Call For Free Repeat Blood Test Within 24 Hours.</strong>
    </div>
    
    <div class="hospital-footer">
        <p style="font-weight: bold;">LIFE CARE HOSPITAL Maternity Home & Pain Clinic Naseem Town Opposite Utman Marriage Hall Haripur</p>
        <p style="font-size: 8px;">Contact: 0332-2400010, 0346-5888603, Phone: 0995-321234</p>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>';
}

function generateBioChemTable($test_results) {
    $html = '<table class="report-table">
        <thead><tr>
            <th style="width: 15%">TEST</th>
            <th style="width: 10%">RESULT</th>
            <th style="width: 8%">UNIT</th>
            <th style="width: 17%">REF. RANGE</th>
            <th style="width: 15%">TEST</th>
            <th style="width: 10%">RESULT</th>
            <th style="width: 8%">UNIT</th>
            <th style="width: 17%">REF. RANGE</th>
        </tr></thead><tbody>';
    
    foreach ($test_results as $category => $tests) {
        $html .= '<tr class="category-header"><td colspan="8"><strong>' . htmlspecialchars($category) . '</strong></td></tr>';
        foreach ($tests as $test_name => $result_value) {
            if (!empty($result_value)) {
                // Get reference data from database
                global $conn;
                $ref_stmt = $conn->prepare("SELECT unit, reference_range FROM test_templates WHERE test_name = ? AND report_type = 'bio_chemistry'");
                $ref_stmt->bind_param("s", $test_name);
                $ref_stmt->execute();
                $ref_result = $ref_stmt->get_result();
                $ref_data = $ref_result->fetch_assoc();
                
                $unit = $ref_data['unit'] ?? '';
                $reference = $ref_data['reference_range'] ?? '';
                
                $html .= '<tr>
                    <td>' . htmlspecialchars($test_name) . '</td>
                    <td class="result-value">' . htmlspecialchars($result_value) . '</td>
                    <td style="text-align: center;">' . htmlspecialchars($unit) . '</td>
                    <td style="font-size: 8px;">' . nl2br(htmlspecialchars($reference)) . '</td>
                    <td colspan="4"></td>
                </tr>';
            }
        }
    }
    
    $html .= '</tbody></table>';
    return $html;
}

function generateHaematologyTable($test_results) {
    $html = '<table class="report-table">
        <thead><tr>
            <th style="width: 30%">TEST</th>
            <th style="width: 25%">RESULT</th>
            <th style="width: 15%">UNIT</th>
            <th style="width: 30%">REFERENCE RANGE</th>
        </tr></thead><tbody>';
    
    foreach ($test_results as $category => $tests) {
        $html .= '<tr class="category-header"><td colspan="4"><strong>' . htmlspecialchars($category) . '</strong></td></tr>';
        foreach ($tests as $test_name => $result_value) {
            if (!empty($result_value)) {
                global $conn;
                $ref_stmt = $conn->prepare("SELECT unit, reference_range FROM test_templates WHERE test_name = ? AND report_type = 'haematology'");
                $ref_stmt->bind_param("s", $test_name);
                $ref_stmt->execute();
                $ref_result = $ref_stmt->get_result();
                $ref_data = $ref_result->fetch_assoc();
                
                $unit = $ref_data['unit'] ?? '';
                $reference = $ref_data['reference_range'] ?? '';
                
                $html .= '<tr>
                    <td>' . htmlspecialchars($test_name) . '</td>
                    <td class="result-value">' . htmlspecialchars($result_value) . '</td>
                    <td style="text-align: center;">' . htmlspecialchars($unit) . '</td>
                    <td style="font-size: 8px;">' . nl2br(htmlspecialchars($reference)) . '</td>
                </tr>';
            }
        }
    }
    
    $html .= '</tbody></table>';
    return $html;
}

function generateUrineTable($test_results) {
    $html = '<table class="report-table">
        <thead><tr>
            <th style="width: 40%">TEST</th>
            <th style="width: 30%">RESULT</th>
            <th style="width: 30%">REFERENCE RANGE</th>
        </tr></thead><tbody>';
    
    foreach ($test_results as $category => $tests) {
        $html .= '<tr class="category-header"><td colspan="3"><strong>' . htmlspecialchars($category) . '</strong></td></tr>';
        foreach ($tests as $test_name => $result_value) {
            if (!empty($result_value)) {
                global $conn;
                $ref_stmt = $conn->prepare("SELECT reference_range FROM test_templates WHERE test_name = ? AND report_type = 'urine_analysis'");
                $ref_stmt->bind_param("s", $test_name);
                $ref_stmt->execute();
                $ref_result = $ref_stmt->get_result();
                $ref_data = $ref_result->fetch_assoc();
                
                $reference = $ref_data['reference_range'] ?? '';
                
                $html .= '<tr>
                    <td>' . htmlspecialchars($test_name) . '</td>
                    <td class="result-value">' . htmlspecialchars($result_value) . '</td>
                    <td style="font-size: 8px;">' . nl2br(htmlspecialchars($reference)) . '</td>
                </tr>';
            }
        }
    }
    
    $html .= '</tbody></table>';
    return $html;
}

function generateSerologyTable($test_results) {
    $html = '<table class="report-table">
        <thead><tr>
            <th style="width: 50%">TEST</th>
            <th style="width: 50%">RESULT</th>
        </tr></thead><tbody>';
    
    foreach ($test_results as $category => $tests) {
        $html .= '<tr class="category-header"><td colspan="2"><strong>' . htmlspecialchars($category) . '</strong></td></tr>';
        foreach ($tests as $test_name => $result_value) {
            if (!empty($result_value)) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($test_name) . '</td>
                    <td class="result-value">' . htmlspecialchars($result_value) . '</td>
                </tr>';
            }
        }
    }
    
    $html .= '</tbody></table>';
    return $html;
}
?>