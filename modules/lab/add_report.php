<?php
require_once '../../includes/functions.php';
requireRole('lab');

// Get patient ID and token ID from URL with validation
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$token_id = isset($_GET['token_id']) ? intval($_GET['token_id']) : 0;

// Validate patient ID
if ($patient_id <= 0) {
    header('Location: /lch/modules/lab/lab_tokens.php?error=no_patient_id');
    exit;
}

// Get patient details with validation
$patient = getPatient($patient_id);
if (!$patient) {
    header('Location: /lch/modules/lab/lab_tokens.php?error=patient_not_found');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $token_id = intval($_POST['token_id']);
    $report_type = $_POST['report_type'];
    $technician_name = $_POST['technician_name'];
    $remarks = $_POST['remarks'] ?? '';
    
    // Validation
    if (empty($report_type)) {
        $error = "Please select a report type";
    } else {
        // Insert main lab report
        $query = "INSERT INTO lab_reports (patient_id, test_name, file_path, status, report_type, technician_name, remarks) 
                  VALUES (?, ?, ?, 'completed', ?, ?, ?)";
        
        $test_display_name = ucfirst(str_replace('_', ' ', $report_type)) . ' Report';
        $filename = $report_type . '_' . $patient_id . '_' . time() . '.html';
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", $patient_id, $test_display_name, $filename, $report_type, $technician_name, $remarks);
        
        if ($stmt->execute()) {
            $lab_report_id = $conn->insert_id;
            
            // Insert individual test results
            $all_saved = true;
            if (isset($_POST['test_results'])) {
                foreach ($_POST['test_results'] as $category => $tests) {
                    foreach ($tests as $test_name => $result_value) {
                        if (!empty($result_value)) {
                            // Get reference data for this test
                            $ref_query = "SELECT unit, reference_range FROM test_templates WHERE report_type = ? AND test_category = ? AND test_name = ?";
                            $ref_stmt = $conn->prepare($ref_query);
                            $ref_stmt->bind_param("sss", $report_type, $category, $test_name);
                            $ref_stmt->execute();
                            $ref_result = $ref_stmt->get_result();
                            $ref_data = $ref_result->fetch_assoc();
                            
                            $unit = $ref_data['unit'] ?? '';
                            $reference_range = $ref_data['reference_range'] ?? '';
                            
                            // Insert test result
                            $result_query = "INSERT INTO lab_test_results (lab_report_id, test_category, test_name, result_value, unit, reference_range) 
                                           VALUES (?, ?, ?, ?, ?, ?)";
                            $result_stmt = $conn->prepare($result_query);
                            $result_stmt->bind_param("isssss", $lab_report_id, $category, $test_name, $result_value, $unit, $reference_range);
                            
                            if (!$result_stmt->execute()) {
                                $all_saved = false;
                                break;
                            }
                        }
                    }
                    if (!$all_saved) break;
                }
            }
            
            if ($all_saved) {
                // Generate HTML report file for printing
                $report_html = generateReportHTML($patient, $report_type, $technician_name, $remarks, $_POST['test_results'] ?? [], $lab_report_id);
                $report_file_path = '../../uploads/reports/' . $filename;
                
                // Create uploads directory if it doesn't exist
                if (!is_dir('../../uploads/reports/')) {
                    mkdir('../../uploads/reports/', 0755, true);
                }
                
                file_put_contents($report_file_path, $report_html);
                
                $success = "Lab report saved successfully";
                // Store the latest report data for print
                $latest_report_data = [
                    'patient' => $patient,
                    'report_type' => $report_type,
                    'technician_name' => $technician_name,
                    'remarks' => $remarks,
                    'test_results' => $_POST['test_results'] ?? [],
                    'lab_report_id' => $lab_report_id,
                    'filename' => $filename
                ];
            } else {
                $error = "Error saving test results";
            }
        } else {
            $error = "Error creating report: " . $stmt->error;
        }
    }
}

// Function to generate report HTML for saving and printing
function generateReportHTML($patient, $report_type, $technician_name, $remarks, $test_results, $lab_report_id) {
    $current_date = date('d/m/Y');
    $current_time = date('h:i A');
    
    $header_class = ($report_type == 'bio_chemistry' || $report_type == 'haematology') ? 'red' : 'blue';
    $title_color = ($report_type == 'bio_chemistry' || $report_type == 'haematology') ? '#dc2626' : '#2563eb';
    
    $report_titles = [
        'bio_chemistry' => 'BIO CHEMISTRY REPORT',
        'haematology' => 'HAEMATOLOGY REPORT', 
        'urine_analysis' => 'URINE ANALYSIS',
        'serology' => 'SEROLOGY REPORT'
    ];
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lab Report - Shahmir Laboratory</title>
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
            padding: 4px 6px;
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
        h2 { color: ' . $title_color . '; font-size: 18px; margin: 20px 0; }
        @media print {
            body { margin: 0; padding: 10px; }
        }
        .result-value {
            font-weight: bold;
            font-size: 11px;
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
            Name: ' . htmlspecialchars($patient['name']) . ' &nbsp;&nbsp;&nbsp; Age: ' . $patient['age'] . ' &nbsp;&nbsp;&nbsp; Sex: ' . $patient['gender'] . ' &nbsp;&nbsp;&nbsp; Date: ' . $current_date . '
        </div>
        <div style="font-size: 11px;">
            Referred By: .........................................................................................................................................
        </div>
    </div>
    
    <h2>' . $report_titles[$report_type] . '</h2>';
    
    // Add test results table based on report type
    if (!empty($test_results)) {
        if ($report_type == 'bio_chemistry') {
            $html .= '<table class="report-table">
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
        } else {
            $html .= '<table class="report-table">
                <thead><tr>
                    <th style="width: 30%">TEST</th>
                    <th style="width: 25%">RESULT</th>
                    <th style="width: 15%">UNIT</th>
                    <th style="width: 30%">REFERENCE RANGE</th>
                </tr></thead><tbody>';
        }
        
        foreach ($test_results as $category => $tests) {
            $html .= '<tr class="category-header"><td colspan="4"><strong>' . htmlspecialchars($category) . '</strong></td></tr>';
            foreach ($tests as $test_name => $result_value) {
                if (!empty($result_value)) {
                    // Get reference range from your database
                    global $conn;
                    $ref_stmt = $conn->prepare("SELECT unit, reference_range FROM test_templates WHERE report_type = ? AND test_name = ?");
                    $ref_stmt->bind_param("ss", $report_type, $test_name);
                    $ref_stmt->execute();
                    $ref_result = $ref_stmt->get_result();
                    $ref_data = $ref_result->fetch_assoc();
                    
                    $unit = $ref_data['unit'] ?? '';
                    $reference = $ref_data['reference_range'] ?? '';
                    
                    $html .= '<tr>
                        <td>' . htmlspecialchars($test_name) . '</td>
                        <td class="result-value">' . htmlspecialchars($result_value) . '</td>
                        <td style="text-align: center;">' . htmlspecialchars($unit) . '</td>
                        <td style="font-size: 9px;">' . htmlspecialchars($reference) . '</td>';
                    
                    if ($report_type == 'bio_chemistry') {
                        $html .= '<td colspan="4"></td>';
                    }
                    $html .= '</tr>';
                }
            }
        }
        
        $html .= '</tbody></table>';
    }
    
    $html .= '<div style="margin-top: 30px;">
        <p><strong>Technician:</strong> ' . htmlspecialchars($technician_name) . '</p>
        <p><strong>Remarks:</strong> ' . htmlspecialchars($remarks) . '</p>
        <p><strong>Date & Time:</strong> ' . $current_date . ' at ' . $current_time . '</p>
    </div>
    
    <div style="font-size: 10px; color: #dc2626; margin: 20px 0;">
        <strong>Not for Medico legal / Court use</strong><br>
        <strong>Please Call For Free Repeat Blood Test Within 24 Hours.</strong>
    </div>
    
    <div class="hospital-footer">
        <p style="font-weight: bold;">LIFE CARE HOSPITAL Maternity Home & Pain Clinic Naseem Town Opposite Utman Marriage Hall Haripur</p>
        <p style="font-size: 8px;">Contact: 0332-2400010, 0346-5888603, Phone: 0995-321234</p>
    </div>
</body>
</html>';
    
    return $html;
}
?>

<?php include '../../includes/header.php'; ?>

<style>
/* Shahmir Laboratory Styling */
.shahmir-header {
    background: linear-gradient(135deg, #dc2626 0%, #dc2626 100%);
    color: white;
    padding: 15px;
    border-radius: 8px 8px 0 0;
    position: relative;
    overflow: hidden;
}

.shahmir-header.blue {
    background: linear-gradient(135deg, #2563eb 0%, #2563eb 100%);
}

.shahmir-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><path d="M0,0 Q50,20 100,0 L100,20 L0,20 Z" fill="rgba(0,0,0,0.1)"/></svg>');
    background-size: 100% 100%;
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
}

.report-table {
    border-collapse: collapse;
    width: 100%;
    margin: 20px 0;
    font-size: 11px;
    font-family: Arial, sans-serif;
}

.report-table th,
.report-table td {
    border: 1px solid #333;
    padding: 4px 6px;
    text-align: left;
    vertical-align: top;
}

.report-table th {
    background-color: #f5f5f5;
    font-weight: bold;
    text-align: center;
    font-size: 10px;
}

.category-header {
    background-color: #e5e5e5 !important;
    font-weight: bold;
    text-align: center;
    color: #dc2626;
    font-size: 11px;
}

.category-header.blue {
    color: #2563eb;
}

.test-input {
    border: none;
    background: transparent;
    width: 100%;
    padding: 1px;
    font-size: 10px;
    min-height: 16px;
}

.test-input:focus {
    outline: 1px solid #2563eb;
    background: #f0f9ff;
}

.unit-cell {
    min-width: 50px;
    text-align: center;
    font-size: 9px;
    font-weight: bold;
}

.reference-cell {
    min-width: 120px;
    font-size: 9px;
    line-height: 1.1;
    padding: 2px 4px;
}

.hospital-footer {
    background: linear-gradient(135deg, #2563eb 0%, #2563eb 100%);
    color: white;
    padding: 8px;
    text-align: center;
    border-radius: 0 0 8px 8px;
    font-size: 10px;
    margin-top: 15px;
}

.hospital-footer.red {
    background: linear-gradient(135deg, #dc2626 0%, #dc2626 100%);
}

.patient-info-row {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
    font-size: 11px;
    color: #333;
}

.print-only {
    display: none;
}

@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    .shahmir-header { break-inside: avoid; }
    .report-table { break-inside: avoid; }
    body { font-size: 11px; }
}
</style>

<div class="mb-6 no-print">
    <h1 class="text-2xl font-bold text-gray-800">Add Lab Report</h1>
    <p class="text-gray-600">Create professional lab reports with Shahmir Laboratory format</p>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 no-print">
    <?php echo $success; ?>
    <div class="mt-2">
        <button onclick="printReport()" class="bg-green-600 text-white py-1 px-3 rounded text-sm hover:bg-green-700">
            <i class="fas fa-print mr-1"></i> Print Report
        </button>
        <a href="/lch/modules/lab/lab_tokens.php" class="bg-blue-600 text-white py-1 px-3 rounded text-sm hover:bg-blue-700 ml-2">
            <i class="fas fa-arrow-left mr-1"></i> Back to Lab Tokens
        </a>
    </div>
</div>

<!-- Print Section -->
<?php if (isset($latest_report_data)): ?>
<div class="print-only">
    <?php
    // Display the generated HTML report for printing
    echo generateReportHTML(
        $latest_report_data['patient'],
        $latest_report_data['report_type'], 
        $latest_report_data['technician_name'],
        $latest_report_data['remarks'],
        $latest_report_data['test_results'],
        $latest_report_data['lab_report_id']
    );
    ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 no-print">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 no-print">
    <!-- Patient Info Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Patient Information</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">ID:</span>
                    <span class="font-medium"><?php echo $patient['id']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Name:</span>
                    <span class="font-medium"><?php echo $patient['name']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Age:</span>
                    <span class="font-medium"><?php echo $patient['age']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Gender:</span>
                    <span class="font-medium"><?php echo $patient['gender']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Contact:</span>
                    <span class="font-medium"><?php echo $patient['contact']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="post" action="/lch/modules/lab/add_report.php?patient_id=<?php echo $patient_id; ?>&token_id=<?php echo $token_id; ?>" id="reportForm">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <input type="hidden" name="token_id" value="<?php echo $token_id; ?>">
                
                <!-- Report Type Selection -->
                <div class="mb-6">
                    <label for="report_type" class="block text-gray-700 font-medium mb-2">Select Report Type <span class="text-red-500">*</span></label>
                    <select id="report_type" name="report_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="loadReportForm()">
                        <option value="">Choose a report type</option>
                        <option value="bio_chemistry">Bio Chemistry Report</option>
                        <option value="haematology">Haematology Report</option>
                        <option value="urine_analysis">Urine Analysis</option>
                        <option value="serology">Serology Report</option>
                    </select>
                </div>

                <!-- Technician Name -->
                <div class="mb-4">
                    <label for="technician_name" class="block text-gray-700 font-medium mb-2">Lab Technician Name</label>
                    <input type="text" id="technician_name" name="technician_name" value="<?php echo $_SESSION['username']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Dynamic Report Form Container -->
                <div id="reportFormContainer"></div>

                <!-- Remarks -->
                <div class="mb-6" id="remarksSection" style="display: none;">
                    <label for="remarks" class="block text-gray-700 font-medium mb-2">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Any additional observations or comments..."></textarea>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3" id="formActions" style="display: none;">
                    <a href="/lch/modules/lab/lab_tokens.php" class="bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Save Report & Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadReportForm() {
    const reportType = document.getElementById('report_type').value;
    const container = document.getElementById('reportFormContainer');
    const remarksSection = document.getElementById('remarksSection');
    const formActions = document.getElementById('formActions');
    
    if (!reportType) {
        container.innerHTML = '';
        remarksSection.style.display = 'none';
        formActions.style.display = 'none';
        return;
    }
    
    // Show remarks and actions
    remarksSection.style.display = 'block';
    formActions.style.display = 'flex';
    
    // Generate form based on type
    let formHTML = '';
    
    switch(reportType) {
        case 'bio_chemistry':
            formHTML = generateBioChemistryForm();
            break;
        case 'haematology':
            formHTML = generateHaematologyForm();
            break;
        case 'urine_analysis':
            formHTML = generateUrineAnalysisForm();
            break;
        case 'serology':
            formHTML = generateSerologyForm();
            break;
    }
    
    container.innerHTML = formHTML;
}

function generateBioChemistryForm() {
    return `
        <div class="shahmir-header">
            <div class="relative z-10">
                <div class="flex justify-between items-start">
                    <div class="shahmir-logo">
                        <div class="logo-circle">
                            <i class="fas fa-microscope text-white text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">Shahmir Laboratory</h1>
                            <p class="text-sm opacity-90">Facility Available for Multi Lab Collection</p>
                        </div>
                    </div>
                    <div class="text-right text-sm">
                        <p><strong>Timings</strong></p>
                        <p>Monday-Sunday: Open Round The Clock</p>
                    </div>
                </div>
                <div class="patient-info-row">
                    <span>Name: <?php echo $patient['name']; ?> &nbsp;&nbsp;&nbsp; Age: <?php echo $patient['age']; ?> &nbsp;&nbsp;&nbsp; Sex: <?php echo $patient['gender']; ?> &nbsp;&nbsp;&nbsp; Date: <?php echo date('d/m/Y'); ?></span>
                </div>
                <div class="text-sm">
                    Referred By.........................................................................................................................................
                </div>
            </div>
        </div>
        
        <h2 class="text-xl font-bold text-red-600 my-4">BIO CHEMISTRY REPORT</h2>
        
        <table class="report-table">
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
            <tbody>
                <tr class="category-header">
                    <td colspan="4"><strong>BLOOD GLUCOSE LEVELS</strong></td>
                    <td colspan="4"><strong>CARDIAC ENZYMES</strong></td>
                </tr>
                <tr>
                    <td>Glucose (Fasting)</td>
                    <td><input type="text" name="test_results[BLOOD GLUCOSE LEVELS][Glucose (Fasting)]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">60 - 110</td>
                    <td>SGOT (AST)</td>
                    <td><input type="text" name="test_results[CARDIAC ENZYMES][SGOT (AST)]" class="test-input"></td>
                    <td class="unit-cell">U/L</td>
                    <td class="reference-cell">Upto: 38</td>
                </tr>
                <tr>
                    <td>Glucose (Random)</td>
                    <td><input type="text" name="test_results[BLOOD GLUCOSE LEVELS][Glucose (Random)]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">80 - 160</td>
                    <td>LDH</td>
                    <td><input type="text" name="test_results[CARDIAC ENZYMES][LDH]" class="test-input"></td>
                    <td class="unit-cell">U/L</td>
                    <td class="reference-cell">160 - 320</td>
                </tr>
                <tr class="category-header">
                    <td colspan="4"><strong>LIVER FUNCTION TEST</strong></td>
                    <td>CK-NAC</td>
                    <td><input type="text" name="test_results[CARDIAC ENZYMES][CK-NAC]" class="test-input"></td>
                    <td class="unit-cell">U/L</td>
                    <td class="reference-cell">Male: &lt;190<br>Female: &lt;167</td>
                </tr>
                <tr>
                    <td>Bilirubin (Total)</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][Bilirubin (Total)]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">up to : 1.0<br>After 1 Week: 5.0</td>
                    <td colspan="4" class="category-header"><strong>RENAL FUNCTION TEST</strong></td>
                </tr>
                <tr>
                    <td>Bilirubin (Direct)</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][Bilirubin (Direct)]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">up to : 0.4</td>
                    <td>Blood Urea</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][Blood Urea]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">up to : 50</td>
                </tr>
                <tr>
                    <td>Bilirubin (Indirect)</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][Bilirubin (Indirect)]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">up to : 0.8</td>
                    <td>Creatinine</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][Creatinine]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">up to : 1.1</td>
                </tr>
                <tr>
                    <td>ALT (SGPT)</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][ALT (SGPT)]" class="test-input"></td>
                    <td class="unit-cell">U/L</td>
                    <td class="reference-cell">up to : 42</td>
                    <td>Uric Acid</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][Uric Acid]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">Male: 3.4 - 7.0<br>Female: 2.4 - 5.7<br>Children: 2.0 - 5.5</td>
                </tr>
                <tr>
                    <td>Alkaline Phosphatase</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][Alkaline Phosphatase]" class="test-input"></td>
                    <td class="unit-cell">U/L</td>
                    <td class="reference-cell">Male: 80-306<br>Female: 64-306<br>Children: Upto: 644</td>
                    <td>BUN</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][BUN]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">15 - 40</td>
                </tr>
                <tr>
                    <td>Gamma GT</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][Gamma GT]" class="test-input"></td>
                    <td class="unit-cell">U/L</td>
                    <td class="reference-cell">Male: Upto: 51<br>Female: Upto: 30</td>
                    <td>Sodium</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][Sodium]" class="test-input"></td>
                    <td class="unit-cell">mmol/L</td>
                    <td class="reference-cell">135 - 145</td>
                </tr>
                <tr>
                    <td>Albumin</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][Albumin]" class="test-input"></td>
                    <td class="unit-cell">G/dl</td>
                    <td class="reference-cell">3.5 - 5.5</td>
                    <td>Potassium</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][Potassium]" class="test-input"></td>
                    <td class="unit-cell">mmol/L</td>
                    <td class="reference-cell">3.5 - 5.5</td>
                </tr>
                <tr>
                    <td>Protein (Total)</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][Protein (Total)]" class="test-input"></td>
                    <td class="unit-cell">G/dl</td>
                    <td class="reference-cell">Premature: 3.6 - 7.0<br>Children: 5.1 - 7.5<br>Adults: 6.0 - 8.3</td>
                    <td>Calcium</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][Calcium]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">8.1 - 10.4</td>
                </tr>
                <tr>
                    <td>Globulins</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][Globulins]" class="test-input"></td>
                    <td class="unit-cell">G/dl</td>
                    <td class="reference-cell">1.5 - 3.0</td>
                    <td>Phosphorous</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][Phosphorous]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">Premature: 4.0-8.0<br>Children: 2.5-6.0<br>Adults: 2.7-4.5</td>
                </tr>
                <tr>
                    <td>A/G Ratio</td>
                    <td><input type="text" name="test_results[LIVER FUNCTION TEST][A/G Ratio]" class="test-input"></td>
                    <td class="unit-cell"></td>
                    <td class="reference-cell">1.5:1 - &lt; 2.5:1</td>
                    <td>CPK-MB</td>
                    <td><input type="text" name="test_results[RENAL FUNCTION TEST][CPK-MB]" class="test-input"></td>
                    <td class="unit-cell">U/L</td>
                    <td class="reference-cell">05-24</td>
                </tr>
                <tr class="category-header">
                    <td colspan="8"><strong>LIPID PROFILE</strong></td>
                </tr>
                <tr>
                    <td>Cholesterol</td>
                    <td><input type="text" name="test_results[LIPID PROFILE][Cholesterol]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">Adults: &lt; 190</td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <td>Triglycerides</td>
                    <td><input type="text" name="test_results[LIPID PROFILE][Triglycerides]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">Suspected: 165</td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <td>HDL Cholesterol</td>
                    <td><input type="text" name="test_results[LIPID PROFILE][HDL Cholesterol]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">Male: >55<br>Female: >65</td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <td>LDL Cholesterol</td>
                    <td><input type="text" name="test_results[LIPID PROFILE][LDL Cholesterol]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">Borderline: &lt;130<br>Suspicious: &lt;130<br>Elevated: &lt;190</td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <td>VLDL Cholesterol</td>
                    <td><input type="text" name="test_results[LIPID PROFILE][VLDL Cholesterol]" class="test-input"></td>
                    <td class="unit-cell">mg/dl</td>
                    <td class="reference-cell">03 - 32</td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>
        
        <div class="hospital-footer red">
            <p class="font-bold">LIFE CARE HOSPITAL Maternity Home & Pain Clinic Naseem Town Opposite Utman Marriage Hall Haripur</p>
            <p class="text-xs">Contact: 0332-2400010, 0346-5888603, Phone: 0995-321234</p>
        </div>
    `;
}

function generateHaematologyForm() {
    return `
        <div class="shahmir-header">
            <div class="relative z-10">
                <div class="flex justify-between items-start">
                    <div class="shahmir-logo">
                        <div class="logo-circle">
                            <i class="fas fa-microscope text-white text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">Shahmir Laboratory</h1>
                            <p class="text-sm opacity-90">Facility Available for Multi Lab Collection</p>
                        </div>
                    </div>
                    <div class="text-right text-sm">
                        <p><strong>Timings</strong></p>
                        <p>Monday-Sunday: Open Round The Clock</p>
                    </div>
                </div>
                <div class="patient-info-row">
                    <span>Name: <?php echo $patient['name']; ?> &nbsp;&nbsp;&nbsp; Age: <?php echo $patient['age']; ?> &nbsp;&nbsp;&nbsp; Sex: <?php echo $patient['gender']; ?> &nbsp;&nbsp;&nbsp; Date: <?php echo date('d/m/Y'); ?></span>
                </div>
                <div class="text-sm">
                    Referred By.........................................................................................................................................
                </div>
            </div>
        </div>
        
        <h2 class="text-xl font-bold text-red-600 my-4">HAEMATOLOGY REPORT</h2>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 25%">TEST</th>
                    <th style="width: 20%">RESULT</th>
                    <th style="width: 15%">UNIT</th>
                    <th style="width: 40%">REFERENCE RANGE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>WBC Count</td>
                    <td><input type="text" name="test_results[HAEMATOLOGY REPORT][WBC Count]" class="test-input"></td>
                    <td class="unit-cell">/cmm</td>
                    <td class="reference-cell">4,000 - 11,000</td>
                </tr>
                <tr>
                    <td>R.B.C Count</td>
                    <td><input type="text" name="test_results[HAEMATOLOGY REPORT][R.B.C Count]" class="test-input"></td>
                    <td class="unit-cell">Million/Cmm</td>
                    <td class="reference-cell">4.25-5.9</td>
                </tr>
                <tr>
                    <td>Haemoglobin</td>
                    <td><input type="text" name="test_results[HAEMATOLOGY REPORT][Haemoglobin]" class="test-input"></td>
                    <td class="unit-cell">g/dl</td>
                    <td class="reference-cell">(M): 13- 18, (F): 12 - 16</td>
                </tr>
                <tr>
                    <td>PCV / HCT</td>
                    <td><input type="text" name="test_results[HAEMATOLOGY REPORT][PCV / HCT]" class="test-input"></td>
                    <td class="unit-cell">%</td>
                    <td class="reference-cell">40-50</td>
                </tr>
                <tr>
                    <td>MCV</td>
                    <td><input type="text" name="test_results[HAEMATOLOGY REPORT][MCV]" class="test-input"></td>
                    <td class="unit-cell">fl</td>
                    <td class="reference-cell">80-95</td>
                </tr>
                <tr>
                    <td>MCH</td>
                    <td><input type="text" name="test_results[HAEMATOLOGY REPORT][MCH]" class="test-input"></td>
                    <td class="unit-cell">Pg</td>
                    <td class="reference-cell">27-34</td>
                </tr>
                <tr>
                    <td>MCHC</td>
                    <td><input type="text" name="test_results[HAEMATOLOGY REPORT][MCHC]" class="test-input"></td>
                    <td class="unit-cell">g/dl</td>
                    <td class="reference-cell">30-35</td>
                </tr>
                <tr>
                    <td>Platelet Count</td>
                    <td><input type="text" name="test_results[HAEMATOLOGY REPORT][Platelet Count]" class="test-input"></td>
                    <td class="unit-cell">/Cmm</td>
                    <td class="reference-cell">150,000 - 400,000</td>
                </tr>
                <tr class="category-header">
                    <td colspan="4"><strong>Different Leukocyte Count (DLC)</strong></td>
                </tr>
                <tr>
                    <td>Neutrophils</td>
                    <td><input type="text" name="test_results[Different Leukocyte Count (DLC)][Neutrophils]" class="test-input"></td>
                    <td class="unit-cell">%</td>
                    <td class="reference-cell">60 after age of 02 years</td>
                </tr>
                <tr>
                    <td>Lymphocytes</td>
                    <td><input type="text" name="test_results[Different Leukocyte Count (DLC)][Lymphocytes]" class="test-input"></td>
                    <td class="unit-cell">%</td>
                    <td class="reference-cell">35 after age of 02 year</td>
                </tr>
                <tr>
                    <td>Monocytes</td>
                    <td><input type="text" name="test_results[Different Leukocyte Count (DLC)][Monocytes]" class="test-input"></td>
                    <td class="unit-cell">%</td>
                    <td class="reference-cell">04 - 09</td>
                </tr>
                <tr>
                    <td>Eosinophils</td>
                    <td><input type="text" name="test_results[Different Leukocyte Count (DLC)][Eosinophils]" class="test-input"></td>
                    <td class="unit-cell">%</td>
                    <td class="reference-cell">00 - 04</td>
                </tr>
                <tr>
                    <td>Basophils</td>
                    <td><input type="text" name="test_results[Different Leukocyte Count (DLC)][Basophils]" class="test-input"></td>
                    <td class="unit-cell">%</td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>Blast Cells</td>
                    <td><input type="text" name="test_results[Different Leukocyte Count (DLC)][Blast Cells]" class="test-input"></td>
                    <td class="unit-cell">%</td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>Reticulocytes Count</td>
                    <td><input type="text" name="test_results[Different Leukocyte Count (DLC)][Reticulocytes Count]" class="test-input"></td>
                    <td class="unit-cell">%</td>
                    <td class="reference-cell">2.0</td>
                </tr>
                <tr class="category-header">
                    <td colspan="4"><strong>ESR</strong></td>
                </tr>
                <tr>
                    <td>ESR</td>
                    <td><input type="text" name="test_results[ESR][ESR]" class="test-input"></td>
                    <td class="unit-cell">1st hour / mm</td>
                    <td class="reference-cell">(M): Upto:20, (F): upto:15</td>
                </tr>
                <tr class="category-header">
                    <td colspan="4"><strong>RBC MORPHOLOGY</strong></td>
                </tr>
                <tr>
                    <td>Microcytosis</td>
                    <td><input type="text" name="test_results[RBC MORPHOLOGY][Microcytosis]" class="test-input"></td>
                    <td class="unit-cell"></td>
                    <td class="reference-cell">Sickle Cells</td>
                </tr>
                <tr>
                    <td>Macrocytosis</td>
                    <td><input type="text" name="test_results[RBC MORPHOLOGY][Macrocytosis]" class="test-input"></td>
                    <td class="unit-cell"></td>
                    <td class="reference-cell">Spherocytes</td>
                </tr>
                <tr>
                    <td>Anisocytosis</td>
                    <td><input type="text" name="test_results[RBC MORPHOLOGY][Anisocytosis]" class="test-input"></td>
                    <td class="unit-cell"></td>
                    <td class="reference-cell">Elliptocytosis</td>
                </tr>
                <tr>
                    <td>Hypochromia</td>
                    <td><input type="text" name="test_results[RBC MORPHOLOGY][Hypochromia]" class="test-input"></td>
                    <td class="unit-cell"></td>
                    <td class="reference-cell">Target Cells</td>
                </tr>
                <tr>
                    <td>Poikilocytosis</td>
                    <td><input type="text" name="test_results[RBC MORPHOLOGY][Poikilocytosis]" class="test-input"></td>
                    <td class="unit-cell"></td>
                    <td class="reference-cell">Normochromic</td>
                </tr>
            </tbody>
        </table>
        
        <div class="hospital-footer red">
            <p class="font-bold">LIFE CARE HOSPITAL Maternity Home & Pain Clinic Naseem Town Opposite Utman Marriage Hall Haripur</p>
            <p class="text-xs">Contact: 0332-2400010, 0346-5888603, Phone: 0995-321234</p>
        </div>
    `;
}

function generateUrineAnalysisForm() {
    return `
        <div class="shahmir-header blue">
            <div class="relative z-10">
                <div class="flex justify-between items-start">
                    <div class="shahmir-logo">
                        <div class="logo-circle">
                            <i class="fas fa-microscope text-white text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">Shahmir Laboratory</h1>
                            <p class="text-sm opacity-90">Facility Available for Multi Lab Collection</p>
                        </div>
                    </div>
                    <div class="text-right text-sm">
                        <p><strong>Timings</strong></p>
                        <p>Monday-Sunday: Open Round The Clock</p>
                    </div>
                </div>
                <div class="patient-info-row">
                    <span>Name: <?php echo $patient['name']; ?> &nbsp;&nbsp;&nbsp; Age: <?php echo $patient['age']; ?> &nbsp;&nbsp;&nbsp; Sex: <?php echo $patient['gender']; ?> &nbsp;&nbsp;&nbsp; Date: <?php echo date('d/m/Y'); ?></span>
                </div>
                <div class="text-sm">
                    Referred By.........................................................................................................................................
                </div>
            </div>
        </div>
        
        <h2 class="text-xl font-bold text-blue-600 my-4">URINE ANALYSIS</h2>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 30%">TEST</th>
                    <th style="width: 25%">RESULT</th>
                    <th style="width: 45%">REFERENCE RANGE</th>
                </tr>
            </thead>
            <tbody>
                <tr class="category-header blue">
                    <td colspan="3"><strong>PHYSICAL ANALYSIS</strong></td>
                </tr>
                <tr>
                    <td>Colour</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Colour]" class="test-input"></td>
                    <td class="reference-cell">Pale Yellow</td>
                </tr>
                <tr>
                    <td>Turbidity</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Turbidity]" class="test-input"></td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>pH</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][pH]" class="test-input"></td>
                    <td class="reference-cell">5.0-6.0</td>
                </tr>
                <tr>
                    <td>Specific Gravity</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Specific Gravity]" class="test-input"></td>
                    <td class="reference-cell">1.005 - 1.030 (After 12 hrs,<br>Fluid restriction > 1.025)</td>
                </tr>
                <tr>
                    <td>Glucose</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Glucose]" class="test-input"></td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>Albumin (Protein)</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Albumin (Protein)]" class="test-input"></td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>Blood</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Blood]" class="test-input"></td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>Ketones</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Ketones]" class="test-input"></td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>Nitrate</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Nitrate]" class="test-input"></td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>Bilirubin</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Bilirubin]" class="test-input"></td>
                    <td class="reference-cell">Nil</td>
                </tr>
                <tr>
                    <td>Urobilinogen</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Urobilinogen]" class="test-input"></td>
                    <td class="reference-cell">Normal</td>
                </tr>
                <tr>
                    <td>Leukocytes</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Leukocytes]" class="test-input"></td>
                    <td class="reference-cell">Negative</td>
                </tr>
                <tr>
                    <td>Bile Salt</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Bile Salt]" class="test-input"></td>
                    <td class="reference-cell">Negative</td>
                </tr>
                <tr>
                    <td>Bile Pigment</td>
                    <td><input type="text" name="test_results[PHYSICAL ANALYSIS][Bile Pigment]" class="test-input"></td>
                    <td class="reference-cell">Normal</td>
                </tr>
                <tr class="category-header blue">
                    <td colspan="3"><strong>MICROSCOPIC ANALYSIS</strong></td>
                </tr>
                <tr>
                    <td>Pus Cells</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Pus Cells]" class="test-input"></td>
                    <td class="reference-cell">M:00- 03, F:00 - 05/HPF</td>
                </tr>
                <tr>
                    <td>Red Blood Cells</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Red Blood Cells]" class="test-input"></td>
                    <td class="reference-cell">00 - 02 / HPF</td>
                </tr>
                <tr>
                    <td>Epithelial Cells</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Epithelial Cells]" class="test-input"></td>
                    <td class="reference-cell">M:00- 03, F:00 - 10/HPF</td>
                </tr>
                <tr>
                    <td>Casts</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Casts]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
                <tr>
                    <td>Amorphous Urates</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Amorphous Urates]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
                <tr>
                    <td>Amorphour Phosphates</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Amorphour Phosphates]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
                <tr>
                    <td>Spermatozoa</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Spermatozoa]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
                <tr>
                    <td>Bacteria</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Bacteria]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
                <tr>
                    <td>Mucus Thread</td>
                    <td><input type="text" name="test_results[MICROSCOPIC ANALYSIS][Mucus Thread]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
                <tr class="category-header blue">
                    <td colspan="3"><strong>CRYSTALS</strong></td>
                </tr>
                <tr>
                    <td>Calcium Oxalate Crystals</td>
                    <td><input type="text" name="test_results[CRYSTALS][Calcium Oxalate Crystals]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
                <tr>
                    <td>Uric Acid Crystals</td>
                    <td><input type="text" name="test_results[CRYSTALS][Uric Acid Crystals]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
                <tr>
                    <td>Tripple Phosphate Crystals</td>
                    <td><input type="text" name="test_results[CRYSTALS][Tripple Phosphate Crystals]" class="test-input"></td>
                    <td class="reference-cell">Nil / HPF</td>
                </tr>
            </tbody>
        </table>
        
        <div class="hospital-footer">
            <p class="font-bold">LIFE CARE HOSPITAL Maternity Home & Pain Clinic Naseem Town Opposite Utman Marriage Hall Haripur</p>
            <p class="text-xs">Contact: 0332-2400010, 0346-5888603, Phone: 0995-321234</p>
        </div>
    `;
}

function generateSerologyForm() {
    return `
        <div class="shahmir-header blue">
            <div class="relative z-10">
                <div class="flex justify-between items-start">
                    <div class="shahmir-logo">
                        <div class="logo-circle">
                            <i class="fas fa-microscope text-white text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">Shahmir Laboratory</h1>
                            <p class="text-sm opacity-90">Facility Available for Multi Lab Collection</p>
                        </div>
                    </div>
                    <div class="text-right text-sm">
                        <p><strong>Timings</strong></p>
                        <p>Monday-Sunday: Open Round The Clock</p>
                    </div>
                </div>
                <div class="patient-info-row">
                    <span>Name: <?php echo $patient['name']; ?> &nbsp;&nbsp;&nbsp; Age: <?php echo $patient['age']; ?> &nbsp;&nbsp;&nbsp; Sex: <?php echo $patient['gender']; ?> &nbsp;&nbsp;&nbsp; Date: <?php echo date('d/m/Y'); ?></span>
                </div>
                <div class="text-sm">
                    Referred By.........................................................................................................................................
                </div>
            </div>
        </div>
        
        <h2 class="text-xl font-bold text-blue-600 my-4">SEROLOGY REPORT</h2>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 25%">TEST</th>
                    <th style="width: 20%">RESULT</th>
                    <th style="width: 25%">TEST</th>
                    <th style="width: 30%">RESULT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Hbs Ag by (Screening)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Hbs Ag by (Screening)]" class="test-input"></td>
                    <td>Pregnancy Test (ICT)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Pregnancy Test (ICT)]" class="test-input"></td>
                </tr>
                <tr>
                    <td>Anti HCV by(Screening)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Anti HCV by(Screening)]" class="test-input"></td>
                    <td>Toxoplasma (IgG)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Toxoplasma (IgG)]" class="test-input"></td>
                </tr>
                <tr>
                    <td>Dengue (IgG)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Dengue (IgG)]" class="test-input"></td>
                    <td>Toxoplasma (IgM)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Toxoplasma (IgM)]" class="test-input"></td>
                </tr>
                <tr>
                    <td>Dengue (IgM)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Dengue (IgM)]" class="test-input"></td>
                    <td>Tuberculosis (IgG)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Tuberculosis (IgG)]" class="test-input"></td>
                </tr>
                <tr>
                    <td>H. Pylori Antibody</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][H. Pylori Antibody]" class="test-input"></td>
                    <td>Tuberculosis (IgM)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Tuberculosis (IgM)]" class="test-input"></td>
                </tr>
                <tr>
                    <td>HIV (AIDS)by (Screening)</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][HIV (AIDS)by (Screening)]" class="test-input"></td>
                    <td>Chickengunya IgG</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Chickengunya IgG]" class="test-input"></td>
                </tr>
                <tr>
                    <td>ICT Malaria Parasite Ag</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][ICT Malaria Parasite Ag]" class="test-input"></td>
                    <td>Chickengunya IgM</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][Chickengunya IgM]" class="test-input"></td>
                </tr>
                <tr>
                    <td>VDRL Test</td>
                    <td><input type="text" name="test_results[SEROLOGY REPORT][VDRL Test]" class="test-input"></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
        
        <h3 class="text-lg font-bold text-blue-600 my-4">COAGULATION PROFILE</h3>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 25%">TEST</th>
                    <th style="width: 25%">RESULT</th>
                    <th style="width: 25%">UNIT</th>
                    <th style="width: 25%">REFERENCE RANGE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Prothrombin time</td>
                    <td><input type="text" name="test_results[COAGULATION PROFILE][Prothrombin time]" class="test-input"></td>
                    <td class="unit-cell">Seconds</td>
                    <td class="reference-cell">11-13</td>
                </tr>
                <tr>
                    <td>Control</td>
                    <td><input type="text" name="test_results[COAGULATION PROFILE][Control]" class="test-input"></td>
                    <td class="unit-cell">Seconds</td>
                    <td class="reference-cell">11-13</td>
                </tr>
                <tr>
                    <td>APTT</td>
                    <td><input type="text" name="test_results[COAGULATION PROFILE][APTT]" class="test-input"></td>
                    <td class="unit-cell">Seconds</td>
                    <td class="reference-cell">25-35</td>
                </tr>
                <tr>
                    <td>INR</td>
                    <td><input type="text" name="test_results[COAGULATION PROFILE][INR]" class="test-input"></td>
                    <td class="unit-cell">Seconds</td>
                    <td class="reference-cell">0.8-1.2</td>
                </tr>
            </tbody>
        </table>
        
        <h3 class="text-lg font-bold text-blue-600 my-4">BLEEDING TIME</h3>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 25%">TEST</th>
                    <th style="width: 25%">RESULT</th>
                    <th style="width: 25%">UNIT</th>
                    <th style="width: 25%">REFERENCE RANGE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Bleeding Time</td>
                    <td><input type="text" name="test_results[BLEEDING TIME][Bleeding Time]" class="test-input"></td>
                    <td class="unit-cell">Min / Sec</td>
                    <td class="reference-cell">01 - 06 Minutes</td>
                </tr>
            </tbody>
        </table>
        
        <h3 class="text-lg font-bold text-blue-600 my-4">CLOTTING TIME</h3>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 25%">TEST</th>
                    <th style="width: 25%">RESULT</th>
                    <th style="width: 25%">UNIT</th>
                    <th style="width: 25%">REFERENCE RANGE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Clotting Time</td>
                    <td><input type="text" name="test_results[CLOTTING TIME][Clotting Time]" class="test-input"></td>
                    <td class="unit-cell">Min / Sec</td>
                    <td class="reference-cell">03 - 10 Minutes</td>
                </tr>
            </tbody>
        </table>
        
        <div class="hospital-footer">
            <p class="font-bold">LIFE CARE HOSPITAL Maternity Home & Pain Clinic Naseem Town Opposite Utman Marriage Hall Haripur</p>
            <p class="text-xs">Contact: 0332-2400010, 0346-5888603, Phone: 0995-321234</p>
        </div>
    `;
}

function printReport() {
    window.print();
}
</script>

<?php include '../../includes/footer.php'; ?>