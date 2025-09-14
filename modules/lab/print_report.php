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
$results_query = "SELECT * FROM lab_test_results WHERE lab_report_id = ? ORDER BY test_category, test_name";
$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("i", $report_id);
$results_stmt->execute();
$results_result = $results_stmt->get_result();

$test_results = [];
while ($row = $results_result->fetch_assoc()) {
    $test_results[$row['test_category']][] = $row;
}

// Check if HTML report file exists and try to display it first
$report_file = '../../uploads/reports/' . $report['file_path'];
if (file_exists($report_file) && !empty($report['file_path'])) {
    echo file_get_contents($report_file);
    exit;
}

// If file doesn't exist, generate report on the fly
$report_titles = [
    'bio_chemistry' => 'BIO CHEMISTRY REPORT',
    'haematology' => 'HAEMATOLOGY REPORT', 
    'urine_analysis' => 'URINE ANALYSIS',
    'serology' => 'SEROLOGY REPORT'
];

$header_class = ($report['report_type'] == 'bio_chemistry' || $report['report_type'] == 'haematology') ? 'red' : 'blue';
$title_color = ($report['report_type'] == 'bio_chemistry' || $report['report_type'] == 'haematology') ? '#dc2626' : '#2563eb';
$bg_color = ($header_class == 'red') ? '#dc2626' : '#2563eb';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lab Report - <?php echo htmlspecialchars($report['patient_name']); ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 15px;
            font-size: 11px;
        }
        .shahmir-header {
            background: linear-gradient(135deg, <?php echo $bg_color; ?> 0%, <?php echo $bg_color; ?> 100%);
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
            color: <?php echo $title_color; ?>;
            font-size: 10px;
        }
        .hospital-footer {
            background: linear-gradient(135deg, <?php echo $bg_color; ?> 0%, <?php echo $bg_color; ?> 100%);
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
            color: <?php echo $title_color; ?>; 
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
            <strong>Name:</strong> <?php echo htmlspecialchars($report['patient_name']); ?> &nbsp;&nbsp;&nbsp; 
            <strong>Age:</strong> <?php echo $report['age']; ?> &nbsp;&nbsp;&nbsp; 
            <strong>Sex:</strong> <?php echo $report['gender']; ?> &nbsp;&nbsp;&nbsp; 
            <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($report['created_at'])); ?>
        </div>
        <div style="font-size: 11px;">
            <strong>Referred By:</strong> .........................................................................................................................................
        </div>
    </div>
    
    <h2><?php echo $report_titles[$report['report_type']]; ?></h2>

    <?php if (!empty($test_results)): ?>
        <table class="report-table">
            <?php if ($report['report_type'] == 'bio_chemistry'): ?>
                <thead>
                    <tr>
                        <th style="width: 15%">TEST</th>
                        <th style="width: 10%">RESULT</th>
                        <th style="width: 8%">UNIT</th>
                        <th style="width: 17%">REF. RANGE</th>
                        <th style="width: 15%">TEST</th>
                        <th style="width: 10%">RESULT</th>
                        <th style="width: 8%">UNIT</th>
                        <th style="width: 17%">REF. RANGE</th>
                    </tr>
                </thead>
            <?php else: ?>
                <thead>
                    <tr>
                        <th style="width: 40%">TEST</th>
                        <th style="width: 20%">RESULT</th>
                        <th style="width: 15%">UNIT</th>
                        <th style="width: 25%">REFERENCE RANGE</th>
                    </tr>
                </thead>
            <?php endif; ?>
            
            <tbody>
                <?php foreach ($test_results as $category => $tests): ?>
                    <tr class="category-header">
                        <td colspan="<?php echo $report['report_type'] == 'bio_chemistry' ? '8' : '4'; ?>">
                            <strong><?php echo htmlspecialchars($category); ?></strong>
                        </td>
                    </tr>
                    <?php foreach ($tests as $test): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                            <td class="result-value"><?php echo htmlspecialchars($test['result_value']); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($test['unit']); ?></td>
                            <td style="font-size: 8px;"><?php echo nl2br(htmlspecialchars($test['reference_range'])); ?></td>
                            <?php if ($report['report_type'] == 'bio_chemistry'): ?>
                                <td colspan="4"></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 40px;">
            <p style="font-size: 14px; color: #666;">No test results available for this report.</p>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px; font-size: 10px;">
        <p><strong>Technician:</strong> <?php echo htmlspecialchars($report['technician_name'] ?? 'N/A'); ?></p>
        <p><strong>Remarks:</strong> <?php echo htmlspecialchars($report['remarks'] ?? 'No remarks'); ?></p>
        <p><strong>Report Generated:</strong> <?php echo date('d/m/Y h:i A', strtotime($report['created_at'])); ?></p>
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
</html>