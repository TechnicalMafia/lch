<?php
require_once '../../includes/functions.php';
requireRole('lab');

// Get patient ID from URL
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';

// Get patient details
$patient = getPatient($patient_id);

// Get all tests
$tests = getAllTests();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $test_name = $_POST['test_name'];
    
    // Generate a unique filename
    $filename = 'report_' . $patient_id . '_' . time() . '.pdf';
    $file_path = $filename;
    
    // Insert lab report into database
    $query = "INSERT INTO lab_reports (patient_id, test_name, file_path, status) 
              VALUES ($patient_id, '$test_name', '$file_path', 'completed')";
    
    if ($conn->query($query) === TRUE) {
        $success = "Lab report added successfully";
        
        // In a real application, you would generate the PDF here
        // For this example, we'll just create a placeholder file
        $file_content = "Lab Report for Patient ID: $patient_id\nTest: $test_name\nDate: " . date('Y-m-d');
        file_put_contents('../../uploads/reports/' . $filename, $file_content);
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add Lab Report</h1>
    <p class="text-gray-600">Create and upload lab test reports</p>
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
            
            <?php if ($patient): ?>
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
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-gray-500">Patient not found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="post" action="add_report.php">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                
                <div class="mb-4">
                    <label for="test_name" class="block text-gray-700 font-medium mb-2">Test Name</label>
                    <select id="test_name" name="test_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select a test</option>
                        <?php while ($test = $tests->fetch_assoc()): ?>
                            <option value="<?php echo $test['test_name']; ?>"><?php echo $test['test_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Test Type</label>
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" onclick="showTestForm('cbc')" class="bg-blue-100 hover:bg-blue-200 text-blue-800 py-2 px-4 rounded-md transition">
                            CBC
                        </button>
                        <button type="button" onclick="showTestForm('lft')" class="bg-blue-100 hover:bg-blue-200 text-blue-800 py-2 px-4 rounded-md transition">
                            LFT
                        </button>
                        <button type="button" onclick="showTestForm('rft')" class="bg-blue-100 hover:bg-blue-200 text-blue-800 py-2 px-4 rounded-md transition">
                            RFT
                        </button>
                        <button type="button" onclick="showTestForm('urine')" class="bg-blue-100 hover:bg-blue-200 text-blue-800 py-2 px-4 rounded-md transition">
                            Urine Analysis
                        </button>
                    </div>
                </div>
                
                <div id="test-forms" class="mb-6">
                    <!-- CBC Form -->
                    <div id="cbc-form" class="hidden border border-gray-200 rounded-lg p-4">
                        <h3 class="font-medium text-gray-800 mb-3">Complete Blood Count (CBC)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Hemoglobin (g/dL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">WBC (×10³/µL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">RBC (×10⁶/µL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Platelets (×10³/µL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Hematocrit (%)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    
                    <!-- LFT Form -->
                    <div id="lft-form" class="hidden border border-gray-200 rounded-lg p-4">
                        <h3 class="font-medium text-gray-800 mb-3">Liver Function Test (LFT)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Bilirubin Total (mg/dL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Bilirubin Direct (mg/dL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Bilirubin Indirect (mg/dL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">ALT (SGPT) (U/L)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">AST (SGOT) (U/L)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">ALP (U/L)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Albumin (g/dL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    
                    <!-- RFT Form -->
                    <div id="rft-form" class="hidden border border-gray-200 rounded-lg p-4">
                        <h3 class="font-medium text-gray-800 mb-3">Renal Function Test (RFT)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Urea (mg/dL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Creatinine (mg/dL)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Sodium (mEq/L)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Potassium (mEq/L)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Chloride (mEq/L)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Urine Analysis Form -->
                    <div id="urine-form" class="hidden border border-gray-200 rounded-lg p-4">
                        <h3 class="font-medium text-gray-800 mb-3">Urine Analysis</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Color</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Pale Yellow</option>
                                    <option>Yellow</option>
                                    <option>Amber</option>
                                    <option>Red</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">pH</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Protein</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Negative</option>
                                    <option>Trace</option>
                                    <option>1+</option>
                                    <option>2+</option>
                                    <option>3+</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Glucose</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Negative</option>
                                    <option>Trace</option>
                                    <option>1+</option>
                                    <option>2+</option>
                                    <option>3+</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Ketones</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>Negative</option>
                                    <option>Trace</option>
                                    <option>1+</option>
                                    <option>2+</option>
                                    <option>3+</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Microscopy RBC</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Microscopy WBC</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Microscopy Casts</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Save Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showTestForm(testType) {
        // Hide all forms
        document.getElementById('cbc-form').classList.add('hidden');
        document.getElementById('lft-form').classList.add('hidden');
        document.getElementById('rft-form').classList.add('hidden');
        document.getElementById('urine-form').classList.add('hidden');
        
        // Show selected form
        document.getElementById(testType + '-form').classList.remove('hidden');
        
        // Set test name in dropdown
        const testNames = {
            'cbc': 'CBC (Complete Blood Count)',
            'lft': 'LFT (Liver Function Test)',
            'rft': 'RFT (Renal Function Test)',
            'urine': 'Urine Analysis'
        };
        
        document.getElementById('test_name').value = testNames[testType];
    }
</script>

<?php include '../../includes/footer.php'; ?>