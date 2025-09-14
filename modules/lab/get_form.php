<?php
include '../includes/db.php';

if (!isset($_GET['type'])) {
    exit('No test type specified');
}

$test_type = $_GET['type'];

// Map test types to report types in database
$report_type_map = [
    'cbc' => 'haematology',
    'biochem' => 'bio_chemistry', 
    'serology' => 'serology',
    'urine' => 'urine_analysis'
];

if (!isset($report_type_map[$test_type])) {
    exit('Invalid test type');
}

$report_type = $report_type_map[$test_type];

// Get test templates from database
$stmt = $conn->prepare("SELECT * FROM test_templates WHERE report_type = ? ORDER BY display_order ASC");
$stmt->bind_param("s", $report_type);
$stmt->execute();
$result = $stmt->get_result();

$tests = [];
while ($row = $result->fetch_assoc()) {
    $tests[] = $row;
}

if (empty($tests)) {
    exit('No tests found for this type');
}

// Group tests by category
$grouped_tests = [];
foreach ($tests as $test) {
    $grouped_tests[$test['test_category']][] = $test;
}

// Set header colors based on report type
$header_color = ($report_type == 'bio_chemistry' || $report_type == 'haematology') ? 'bg-red-600' : 'bg-blue-600';
$report_title = [
    'bio_chemistry' => 'BIO CHEMISTRY REPORT',
    'haematology' => 'HAEMATOLOGY REPORT', 
    'urine_analysis' => 'URINE ANALYSIS',
    'serology' => 'SEROLOGY REPORT'
];
?>

<div class="lab-report-form bg-white p-6 border rounded-lg">
    <!-- Hospital Header -->
    <div class="text-center mb-6 border-b pb-4">
        <div class="flex items-center justify-center mb-2">
            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-blue-800">SHAHMIR LABORATORY</h1>
                <p class="text-sm text-gray-600">Facility Available for Multi Lab Collection</p>
            </div>
        </div>
    </div>

    <!-- Report Header -->
    <div class="<?php echo $header_color; ?> text-white text-center py-2 mb-4 rounded">
        <h2 class="text-lg font-bold"><?php echo $report_title[$report_type]; ?></h2>
    </div>

    <!-- Patient Info Section -->
    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
        <div>
            <strong>Patient Name:</strong> <span id="patient-name">__________________</span><br>
            <strong>Age:</strong> <span id="patient-age">____</span> &nbsp;&nbsp;
            <strong>Gender:</strong> <span id="patient-gender">____</span>
        </div>
        <div class="text-right">
            <strong>Date:</strong> <?php echo date('d/m/Y'); ?><br>
            <strong>Sample ID:</strong> <span id="sample-id">__________</span>
        </div>
    </div>

    <!-- Test Results Form -->
    <?php foreach ($grouped_tests as $category => $category_tests): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold bg-gray-100 p-2 mb-3 rounded"><?php echo $category; ?></h3>
            
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-300 text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border border-gray-300 p-2 text-left">Test</th>
                            <th class="border border-gray-300 p-2 text-center">Result</th>
                            <th class="border border-gray-300 p-2 text-center">Unit</th>
                            <th class="border border-gray-300 p-2 text-center">Reference Range</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category_tests as $test): ?>
                        <tr>
                            <td class="border border-gray-300 p-2 font-medium">
                                <?php echo htmlspecialchars($test['test_name']); ?>
                            </td>
                            <td class="border border-gray-300 p-2">
                                <input 
                                    type="text" 
                                    name="test_results[<?php echo $category; ?>][<?php echo $test['test_name']; ?>]"
                                    class="w-full p-1 border rounded text-center focus:border-blue-500 focus:outline-none"
                                    placeholder="Enter result"
                                >
                            </td>
                            <td class="border border-gray-300 p-2 text-center text-gray-600">
                                <?php echo htmlspecialchars($test['unit'] ?? ''); ?>
                            </td>
                            <td class="border border-gray-300 p-2 text-center text-gray-600 text-xs">
                                <?php echo nl2br(htmlspecialchars($test['reference_range'] ?? '')); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Footer -->
    <div class="mt-8 pt-4 border-t text-center text-sm text-gray-600">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p><strong>Lab Technician:</strong> ________________</p>
                <p class="mt-2">Signature & Date</p>
            </div>
            <div>
                <p><strong>Pathologist:</strong> ________________</p>
                <p class="mt-2">Signature & Date</p>
            </div>
        </div>
        <div class="mt-4 text-xs">
            <p><strong>Life Care Hospital</strong></p>
            <p>Contact: 0345-9007891 | Address: Your Hospital Address</p>
        </div>
    </div>
</div>

<script>
// Auto-populate patient data when form loads
document.addEventListener('DOMContentLoaded', function() {
    const patientId = document.querySelector('input[name="patient_id"]').value;
    if (patientId) {
        // Fetch patient data
        fetch('../includes/get_patient.php?id=' + patientId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('patient-name').textContent = data.patient.name;
                    document.getElementById('patient-age').textContent = data.patient.age;
                    document.getElementById('patient-gender').textContent = data.patient.gender;
                    document.getElementById('sample-id').textContent = 'S' + patientId + Date.now().toString().slice(-4);
                }
            })
            .catch(error => console.log('Error fetching patient data:', error));
    }
});
</script>