<?php
require_once '../../includes/functions.php';
requireRole('lab');

// Get patient ID from URL if available
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$patient = null;

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

// Handle AJAX request for report content
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_report' && isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    
    $report_query = "SELECT lr.*, p.name as patient_name 
                     FROM lab_reports lr 
                     JOIN patients p ON lr.patient_id = p.id 
                     WHERE lr.id = ?";
    $stmt = $conn->prepare($report_query);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report_result = $stmt->get_result();
    $report = $report_result->fetch_assoc();
    
    if ($report) {
        // Read report file content
        $file_path = '../../uploads/reports/' . $report['file_path'];
        $content = file_exists($file_path) ? file_get_contents($file_path) : 'Report file not found.';
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'report' => $report,
            'content' => $content
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Report not found']);
    }
    exit;
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
                                <button onclick="viewReport(<?php echo $report['id']; ?>)" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 mr-2">
                                    <i class="fas fa-eye mr-1"></i> View
                                </button>
                                <button onclick="printReport(<?php echo $report['id']; ?>)" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700">
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

<!-- Report View Modal -->
<div id="reportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Lab Report</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="p-6">
            <div id="reportContent" class="bg-gray-50 p-4 rounded-lg">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading report...</p>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end p-4 border-t space-x-3">
            <button onclick="printCurrentReport()" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">
                <i class="fas fa-print mr-2"></i>Print Report
            </button>
            <button onclick="closeModal()" class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentReportData = null;

// Search functionality
document.getElementById('search').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#report-table-body tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// View report in modal
function viewReport(reportId) {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportContent');
    const title = document.getElementById('modalTitle');
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Reset content
    content.innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
            <p class="text-gray-600 mt-2">Loading report...</p>
        </div>
    `;
    
    // Fetch report data
    fetch(`/lch/modules/lab/view_reports.php?ajax=get_report&report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentReportData = data.report;
                title.textContent = `Lab Report - ${data.report.test_name}`;
                content.innerHTML = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="font-semibold text-gray-700">Patient:</label>
                                <p class="text-gray-900">${data.report.patient_name}</p>
                            </div>
                            <div>
                                <label class="font-semibold text-gray-700">Test:</label>
                                <p class="text-gray-900">${data.report.test_name}</p>
                            </div>
                            <div>
                                <label class="font-semibold text-gray-700">Date:</label>
                                <p class="text-gray-900">${new Date(data.report.created_at).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <label class="font-semibold text-gray-700">Status:</label>
                                <p class="text-gray-900">${data.report.status}</p>
                            </div>
                        </div>
                        <div>
                            <label class="font-semibold text-gray-700">Report Content:</label>
                            <div class="mt-2 p-4 bg-white border rounded-lg">
                                <pre class="whitespace-pre-wrap text-sm text-gray-800">${data.content}</pre>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
                        <p class="text-red-600 mt-2">${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
                    <p class="text-red-600 mt-2">Error loading report</p>
                </div>
            `;
        });
}

// Close modal
function closeModal() {
    document.getElementById('reportModal').classList.add('hidden');
    currentReportData = null;
}

// Print report from modal
function printCurrentReport() {
    if (currentReportData) {
        printReport(currentReportData.id);
    }
}

// Print report function
function printReport(reportId) {
    // This will be enhanced when you provide the PDF forms
    window.open(`/lch/modules/lab/print_report.php?id=${reportId}`, '_blank');
}

// Close modal when clicking outside
document.getElementById('reportModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>