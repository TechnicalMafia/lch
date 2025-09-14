<?php
require_once '../../includes/functions.php';
requireRole('lab');

// Get patient ID from URL if available
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$patient = null;

// Check if viewing a specific report
$view_report_id = isset($_GET['view_report']) ? intval($_GET['view_report']) : 0;

// If viewing a specific report, redirect to the report view page
if ($view_report_id > 0) {
    header("Location: /lch/modules/lab/report_view.php?id=" . $view_report_id);
    exit;
}

// Get lab reports with proper patient name joining
if ($patient_id > 0) {
    $patient = getPatient($patient_id);
    $query = "SELECT lr.*, p.name as patient_name 
              FROM lab_reports lr 
              JOIN patients p ON lr.patient_id = p.id 
              WHERE lr.patient_id = ? 
              ORDER BY lr.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $lab_reports = $stmt->get_result();
} else {
    $query = "SELECT lr.*, p.name as patient_name 
              FROM lab_reports lr 
              JOIN patients p ON lr.patient_id = p.id 
              ORDER BY lr.created_at DESC";
    $lab_reports = $conn->query($query);
}
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Lab Reports</h1>
    <p class="text-gray-600">View and manage lab test reports</p>
</div>

<?php if ($patient_id > 0 && $patient): ?>
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <h2 class="text-lg font-semibold text-gray-800">Patient: <?php echo htmlspecialchars($patient['name']); ?> (ID: <?php echo $patient['id']; ?>)</h2>
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
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($report['patient_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($report['test_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $report['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="/lch/modules/lab/report_view.php?id=<?php echo $report['id']; ?>" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 mr-2 no-sidebar-trigger">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <button onclick="printReport(<?php echo $report['id']; ?>); return false;" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700 no-sidebar-trigger">
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
            <h3 class="text-lg font-medium text-gray-900 mb-1">No lab reports found</h3>
            <p class="text-gray-500">There are no lab reports available.</p>
        </div>
    <?php endif; ?>
</div>

<style>
/* Prevent sidebar from collapsing when buttons are clicked */
.sidebar {
    transition: none !important;
}

/* Ensure buttons don't trigger sidebar events */
.no-sidebar-trigger {
    pointer-events: auto !important;
    z-index: 1000;
}
</style>

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

// Print report function - same as add_report.php
function printReport(reportId) {
    // Prevent event bubbling that might affect sidebar
    if (event) {
        event.stopPropagation();
    }
    
    // Open the print page in a new window/tab
    window.open('/lch/modules/lab/print_report.php?id=' + reportId, '_blank', 'width=800,height=600');
    
    // Return false to prevent any default behavior
    return false;
}
</script>

<?php include '../../includes/footer.php'; ?>