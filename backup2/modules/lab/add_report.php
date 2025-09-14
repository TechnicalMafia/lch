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

// Get all tests
$tests = getAllTests();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $token_id = intval($_POST['token_id']);
    $test_name = trim($_POST['test_name']);
    
    // Validation
    if (empty($test_name)) {
        $error = "Please select a test name";
    } else {
        // Generate a unique filename
        $filename = 'report_' . $patient_id . '_' . time() . '.txt';
        $file_path = $filename;
        
        // Collect form data for report content
        $report_data = [
            'patient_id' => $patient_id,
            'patient_name' => $patient['name'],
            'test_name' => $test_name,
            'date' => date('Y-m-d H:i:s'),
            'technician' => $_SESSION['username']
        ];
        
        // Collect test-specific data if available
        if (isset($_POST['test_data'])) {
            $report_data['test_results'] = $_POST['test_data'];
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../../uploads/reports/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Create report content
        $report_content = "LAB REPORT\n";
        $report_content .= "==========\n\n";
        $report_content .= "Patient ID: " . $report_data['patient_id'] . "\n";
        $report_content .= "Patient Name: " . $report_data['patient_name'] . "\n";
        $report_content .= "Test Name: " . $report_data['test_name'] . "\n";
        $report_content .= "Date: " . $report_data['date'] . "\n";
        $report_content .= "Lab Technician: " . $report_data['technician'] . "\n\n";
        $report_content .= "Test Results:\n";
        $report_content .= "=============\n";
        
        // Add any additional test data
        if (isset($report_data['test_results'])) {
            $report_content .= $report_data['test_results'] . "\n";
        } else {
            $report_content .= "Test completed successfully.\n";
            $report_content .= "Results are within normal parameters.\n";
        }
        
        $report_content .= "\n\nEnd of Report\n";
        
        // Save the report file
        if (file_put_contents($upload_dir . $filename, $report_content)) {
            // Insert lab report into database
            $query = "INSERT INTO lab_reports (patient_id, test_name, file_path, status) 
                      VALUES (?, ?, ?, 'completed')";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $patient_id, $test_name, $file_path);
            
            if ($stmt->execute()) {
                $success = "Lab report saved successfully";
                
                // Redirect back to lab tokens with success message
                header('Location: /lch/modules/lab/lab_tokens.php?success=report_saved');
                exit;
            } else {
                $error = "Error saving to database: " . $stmt->error;
            }
        } else {
            $error = "Error saving report file";
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add Lab Report</h1>
    <p class="text-gray-600">Create and save lab test reports</p>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <?php echo $success; ?>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Patient Information</h2>
            
            <div class="space-y-3">
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
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="post" action="/lch/modules/lab/add_report.php?patient_id=<?php echo $patient_id; ?>&token_id=<?php echo $token_id; ?>">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <input type="hidden" name="token_id" value="<?php echo $token_id; ?>">
                
                <div class="mb-4">
                    <label for="test_name" class="block text-gray-700 font-medium mb-2">Test Name <span class="text-red-500">*</span></label>
                    <select id="test_name" name="test_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select a test</option>
                        <?php while ($test = $tests->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($test['test_name']); ?>"><?php echo htmlspecialchars($test['test_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label for="test_data" class="block text-gray-700 font-medium mb-2">Test Results / Notes</label>
                    <textarea id="test_data" name="test_data" rows="8" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter test results, observations, and any additional notes...

Example:
- Hemoglobin: 12.5 g/dL (Normal: 12-16 g/dL)
- WBC: 7,500/µL (Normal: 4,000-11,000/µL)
- RBC: 4.2 million/µL (Normal: 4.0-5.5 million/µL)
- Platelets: 250,000/µL (Normal: 150,000-450,000/µL)

Remarks: All parameters within normal limits."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <a href="/lch/modules/lab/lab_tokens.php" class="bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Save Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-fill test results template based on selected test
document.getElementById('test_name').addEventListener('change', function() {
    const testName = this.value;
    const testDataField = document.getElementById('test_data');
    
    let template = '';
    
    switch(testName) {
        case 'CBC (Complete Blood Count)':
            template = `Hemoglobin: _____ g/dL (Normal: 12-16 g/dL)
WBC: _____ /µL (Normal: 4,000-11,000/µL)
RBC: _____ million/µL (Normal: 4.0-5.5 million/µL)
Platelets: _____ /µL (Normal: 150,000-450,000/µL)
Hematocrit: _____ % (Normal: 36-46%)

Remarks: `;
            break;
            
        case 'LFT (Liver Function Test)':
            template = `Bilirubin Total: _____ mg/dL (Normal: 0.3-1.2 mg/dL)
Bilirubin Direct: _____ mg/dL (Normal: 0.0-0.3 mg/dL)
ALT (SGPT): _____ U/L (Normal: 7-56 U/L)
AST (SGOT): _____ U/L (Normal: 10-40 U/L)
ALP: _____ U/L (Normal: 44-147 U/L)
Albumin: _____ g/dL (Normal: 3.5-5.0 g/dL)

Remarks: `;
            break;
            
        case 'RFT (Renal Function Test)':
            template = `Urea: _____ mg/dL (Normal: 6-24 mg/dL)
Creatinine: _____ mg/dL (Normal: 0.6-1.2 mg/dL)
Sodium: _____ mEq/L (Normal: 136-145 mEq/L)
Potassium: _____ mEq/L (Normal: 3.5-5.1 mEq/L)
Chloride: _____ mEq/L (Normal: 98-107 mEq/L)

Remarks: `;
            break;
            
        case 'Urine Analysis':
            template = `Physical Examination:
- Color: _____
- Appearance: _____
- Specific Gravity: _____

Chemical Examination:
- pH: _____
- Protein: _____
- Glucose: _____
- Ketones: _____

Microscopic Examination:
- RBC: _____ /hpf
- WBC: _____ /hpf
- Epithelial Cells: _____
- Casts: _____

Remarks: `;
            break;
            
        default:
            template = 'Test completed successfully.\n\nResults:\n\n\nRemarks: ';
    }
    
    testDataField.value = template;
});
</script>

<?php include '../../includes/footer.php'; ?>