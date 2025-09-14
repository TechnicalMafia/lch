<?php
require_once '../../includes/functions.php';
requireRole('lab');

// Get report ID from URL
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id <= 0) {
    header('Location: /lch/modules/lab/view_reports.php?error=invalid_report');
    exit;
}

// Get report details
$query = "SELECT lr.*, p.name as patient_name, p.age, p.gender, p.contact 
          FROM lab_reports lr 
          JOIN patients p ON lr.patient_id = p.id 
          WHERE lr.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    header('Location: /lch/modules/lab/view_reports.php?error=report_not_found');
    exit;
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

$report_titles = [
    'bio_chemistry' => 'BIO CHEMISTRY REPORT',
    'haematology' => 'HAEMATOLOGY REPORT', 
    'urine_analysis' => 'URINE ANALYSIS',
    'serology' => 'SEROLOGY REPORT'
];

$header_colors = [
    'bio_chemistry' => 'bg-red-600',
    'haematology' => 'bg-red-600', 
    'urine_analysis' => 'bg-blue-600',
    'serology' => 'bg-blue-600'
];
?>

<?php include '../../includes/header.php'; ?>

<style>
/* Report styling matching the forms */
.shahmir-header {
    background: linear-gradient(135deg, <?php echo $header_colors[$report['report_type']] == 'bg-red-600' ? '#dc2626' : '#2563eb'; ?> 0%, <?php echo $header_colors[$report['report_type']] == 'bg-red-600' ? '#dc2626' : '#2563eb'; ?> 100%);
    color: white;
    padding: 20px;
    border-radius: 8px 8px 0 0;
    position: relative;
    overflow: hidden;
}

.shahmir-logo {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}

.logo-circle {
    width: 70px;
    height: 70px;
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
    font-size: 12px;
    font-family: Arial, sans-serif;
}

.report-table th,
.report-table td {
    border: 1px solid #333;
    padding: 8px 10px;
    text-align: left;
    vertical-align: top;
}

.report-table th {
    background-color: #f5f5f5;
    font-weight: bold;
    text-align: center;
    font-size: 11px;
}

.category-header {
    background-color: #e5e5e5 !important;
    font-weight: bold;
    text-align: center;
    color: <?php echo $header_colors[$report['report_type']] == 'bg-red-600' ? '#dc2626' : '#2563eb'; ?>;
    font-size: 12px;
}

.hospital-footer {
    background: linear-gradient(135deg, <?php echo $header_colors[$report['report_type']] == 'bg-red-600' ? '#dc2626' : '#2563eb'; ?> 0%, <?php echo $header_colors[$report['report_type']] == 'bg-red-600' ? '#dc2626' : '#2563eb'; ?> 100%);
    color: white;
    padding: 12px;
    text-align: center;
    border-radius: 0 0 8px 8px;
    font-size: 11px;
    margin-top: 20px;
}

.result-value {
    font-weight: bold;
    color: #1f2937;
    text-align: center;
}

.no-sidebar-trigger {
    pointer-events: auto !important;
    z-index: 1000;
}

@media print {
    .no-print { display: none !important; }
    body { font-size: 11px; }
    .shahmir-header { break-inside: avoid; }
    .report-table { break-inside: avoid; }
}
</style>

<div class="mb-6 no-print">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Lab Report Details</h1>
            <p class="text-gray-600">Report ID: #<?php echo $report['id']; ?></p>
        </div>
        <div class="space-x-3">
            <button onclick="printReport(<?php echo $report['id']; ?>); return false;" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 no-sidebar-trigger">
                <i class="fas fa-print mr-2"></i> Print Report
            </button>
            <a href="/lch/modules/lab/view_reports.php" class="bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700 no-sidebar-trigger">
                <i class="fas fa-arrow-left mr-2"></i> Back to Reports
            </a>
        </div>
    </div>
</div>

<!-- Report Display -->
<div class="bg-white shadow-lg rounded-lg overflow-hidden">
    <!-- Shahmir Laboratory Header -->
    <div class="shahmir-header">
        <div class="relative z-10">
            <div class="flex justify-between items-start">
                <div class="shahmir-logo">
                    <div class="logo-circle">
                        <i class="fas fa-microscope text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">Shahmir Laboratory</h1>
                        <p class="text-lg opacity-90">Facility Available for Multi Lab Collection</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-lg"><strong>Timings</strong></p>
                    <p>Monday-Sunday: Open Round The Clock</p>
                </div>
            </div>
            <div class="mt-4 text-lg">
                <strong>Name:</strong> <?php echo htmlspecialchars($report['patient_name']); ?> &nbsp;&nbsp;&nbsp; 
                <strong>Age:</strong> <?php echo $report['age']; ?> &nbsp;&nbsp;&nbsp; 
                <strong>Sex:</strong> <?php echo $report['gender']; ?> &nbsp;&nbsp;&nbsp; 
                <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($report['created_at'])); ?>
            </div>
            <div class="text-base mt-2">
                <strong>Referred By:</strong> .........................................................................................................................................
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="p-6">
        <h2 class="text-2xl font-bold text-center mb-6" style="color: <?php echo $header_colors[$report['report_type']] == 'bg-red-600' ? '#dc2626' : '#2563eb'; ?>">
            <?php echo $report_titles[$report['report_type']]; ?>
        </h2>

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
                                <td style="font-size: 10px;"><?php echo nl2br(htmlspecialchars($test['reference_range'])); ?></td>
                                <?php if ($report['report_type'] == 'bio_chemistry'): ?>
                                    <td colspan="4"></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-flask text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-600">No test results found for this report.</p>
            </div>
        <?php endif; ?>

        <!-- Additional Information -->
        <div class="mt-8 grid grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold text-gray-800 mb-2">Report Information</h3>
                <div class="text-sm space-y-1">
                    <p><strong>Technician:</strong> <?php echo htmlspecialchars($report['technician_name'] ?? 'N/A'); ?></p>
                    <p><strong>Generated:</strong> <?php echo date('d/m/Y h:i A', strtotime($report['created_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $report['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($report['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 mb-2">Remarks</h3>
                <div class="text-sm bg-gray-50 p-3 rounded">
                    <?php echo !empty($report['remarks']) ? nl2br(htmlspecialchars($report['remarks'])) : 'No remarks provided.'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Hospital Footer -->
    <div class="hospital-footer">
        <p class="font-bold text-base">LIFE CARE HOSPITAL Maternity Home & Pain Clinic Naseem Town Opposite Utman Marriage Hall Haripur</p>
        <p class="text-sm mt-1">Contact: 0332-2400010, 0346-5888603, Phone: 0995-321234</p>
        <div class="mt-3 text-xs">
            <p><strong>Not for Medico legal / Court use</strong></p>
            <p><strong>Please Call For Free Repeat Blood Test Within 24 Hours.</strong></p>
        </div>
    </div>
</div>

<script>
// Print report function - same as other pages
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