<?php
require_once '../../includes/functions.php';
requireRole('lab');

// Get patient ID from URL if available
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';

// Get all lab reports or patient-specific reports
if ($patient_id) {
    $lab_reports = getPatientLabReports($patient_id);
    $patient = getPatient($patient_id);
} else {
    $lab_reports = getAllLabReports();
}
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Lab Reports</h1>
    <p class="text-gray-600">View and manage lab test reports</p>
</div>

<?php if ($patient_id && $patient): ?>
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <h2 class="text-lg font-semibold text-gray-800">Patient: <?php echo $patient['name']; ?> (ID: <?php echo $patient['id']; ?>)</h2>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Lab Reports</h2>
        <div class="relative">
            <input type="text" id="search" placeholder="Search reports..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
        </div>
    </div>
    
    <?php if ($lab_reports->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="report-table-body">
                    <?php while ($report = $lab_reports->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $report['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $report['patient_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $report['test_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $report['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="../../uploads/reports/<?php echo $report['file_path']; ?>" target="_blank" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-file-pdf mr-1"></i> View
                                </a>
                                <a href="add_report.php?patient_id=<?php echo $report['patient_id']; ?>" class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="p-8 text-center">
            <i class="fas fa-file-medical text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-1">No lab reports found</h3>
            <p class="text-gray-500">There are no lab reports available.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    // Search functionality
    document.getElementById('search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#report-table-body tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>