<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('doctor');

// Get patient ID from URL and validate it
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect back to patient queue if no valid patient ID
    header('Location: patient_queue.php');
    exit;
}

$patient_id = $_GET['id'];

// Get patient details
$patient = getPatient($patient_id);

// Check if patient exists
if (!$patient) {
    // Redirect back if patient not found
    header('Location: patient_queue.php?error=patient_not_found');
    exit;
}

// Get patient visits
$visits = getPatientVisits($patient_id);

// Get patient bills
$bills = getPatientBills($patient_id);

// Get patient lab reports with test results
$lab_reports_query = "SELECT lr.*, p.name as patient_name 
                      FROM lab_reports lr 
                      JOIN patients p ON lr.patient_id = p.id 
                      WHERE lr.patient_id = ? 
                      ORDER BY lr.created_at DESC";
$lab_stmt = $conn->prepare($lab_reports_query);
$lab_stmt->bind_param("i", $patient_id);
$lab_stmt->execute();
$lab_reports = $lab_stmt->get_result();

// Get patient tokens
$tokens = getPatientTokens($patient_id);
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Patient Details</h1>
    <p class="text-gray-600">View complete patient history</p>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'patient_not_found'): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    Patient not found.
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
                <div class="flex justify-between">
                    <span class="text-gray-600">Emergency Contact:</span>
                    <span class="font-medium"><?php echo $patient['emergency_contact'] ?: 'N/A'; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Relative Name:</span>
                    <span class="font-medium"><?php echo $patient['relative_name'] ?: 'N/A'; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">NIC:</span>
                    <span class="font-medium"><?php echo $patient['nic'] ?: 'N/A'; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Address:</span>
                    <span class="font-medium"><?php echo $patient['address'] ?: 'N/A'; ?></span>
                </div>
            </div>
            
            <div class="mt-6">
                <a href="add_comments.php?patient_id=<?php echo $patient_id; ?>" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition block text-center">
                    <i class="fas fa-comment-medical mr-2"></i> Add Visit Comments
                </a>
                <a href="patient_queue.php" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition block text-center mt-2">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Queue
                </a>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Visit History</h2>
            </div>
            
            <?php if ($visits->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comments</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicines</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($visit = $visits->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($visit['visit_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $visit['doctor_name']; ?></td>
                                    <td class="px-6 py-4"><?php echo $visit['comments'] ?: 'N/A'; ?></td>
                                    <td class="px-6 py-4"><?php echo $visit['medicines'] ?: 'N/A'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-8 text-center">
                    <i class="fas fa-history text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No visit history</h3>
                    <p class="text-gray-500">This patient has no previous visits recorded.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 gap-6">
            <!-- Lab Reports Section - Full Width -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Lab Reports</h2>
                </div>
                
                <?php if ($lab_reports->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Technician</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($report = $lab_reports->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-sm">#<?php echo str_pad($report['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($report['test_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $report['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($report['technician_name'] ?: 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                            <button onclick="viewReport(<?php echo $report['id']; ?>)" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 text-xs">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </button>
                                            <button onclick="printReport(<?php echo $report['id']; ?>)" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700 text-xs">
                                                <i class="fas fa-print mr-1"></i> Print
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-file-medical text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">No lab reports</h3>
                        <p class="text-gray-500">No lab reports found for this patient.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// View report in new window - same as print but without auto-print
function viewReport(reportId) {
    // Open the lab report view page in a new window
    window.open(`/lch/modules/lab/report_view.php?id=${reportId}`, '_blank', 'width=1000,height=700,scrollbars=yes,resizable=yes');
}

// Print report function
function printReport(reportId) {
    // Open the print page in a new window
    window.open(`/lch/modules/lab/print_report.php?id=${reportId}`, '_blank', 'width=800,height=600');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>