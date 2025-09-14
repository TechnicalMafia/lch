<?php
require_once '../../includes/functions.php';
requireRole('lab');

// Get lab tokens
$lab_tokens = getLabTokens();

// Process form submission to mark token as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_token'])) {
    $token_id = $_POST['token_id'];
    
    $update_query = "UPDATE tokens SET status = 'completed' WHERE id = $token_id";
    
    if ($conn->query($update_query) === TRUE) {
        $success = "Token marked as completed";
        // Refresh the page to update the list
        header("Location: /lch/modules/lab/lab_tokens.php?success=token_completed");
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Lab Tokens</h1>
    <p class="text-gray-600">View and manage lab test tokens</p>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <?php 
    if ($_GET['success'] === 'token_completed') {
        echo "Token marked as completed successfully";
    } elseif ($_GET['success'] === 'report_saved') {
        echo "Lab report saved successfully";
    }
    ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <?php 
    if ($_GET['error'] === 'no_patient_id') {
        echo "No patient ID provided. Please select a patient from the lab tokens.";
    } elseif ($_GET['error'] === 'patient_not_found') {
        echo "Patient not found. Please check the patient ID and try again.";
    }
    ?>
</div>
<?php endif; ?>

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

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Waiting Patients</h2>
    </div>
    
    <?php if ($lab_tokens->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($token = $lab_tokens->fetch_assoc()): ?>
                        <?php
                        // Get test name if test_id exists
                        $test_name = 'General Lab Test';
                        if (!empty($token['test_id'])) {
                            $test = getTest($token['test_id']);
                            if ($test) {
                                $test_name = $test['test_name'];
                            }
                        }
                        
                        // Check if report already exists for this token
                        $report_exists_query = "SELECT id FROM lab_reports WHERE patient_id = {$token['patient_id']} AND created_at >= '{$token['created_at']}'";
                        $report_exists = $conn->query($report_exists_query);
                        $has_report = $report_exists->num_rows > 0;
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo $token['token_no']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $token['patient_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $test_name; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y, h:i A', strtotime($token['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if (!$has_report): ?>
                                <a href="/lch/modules/lab/add_report.php?patient_id=<?php echo $token['patient_id']; ?>&token_id=<?php echo $token['id']; ?>" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 mr-2">
                                    <i class="fas fa-file-medical mr-1"></i> Add Report
                                </a>
                                <?php else: ?>
                                <span class="bg-green-100 text-green-800 py-1 px-3 rounded text-xs mr-2">
                                    <i class="fas fa-check mr-1"></i> Report Added
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($has_report): ?>
                                <form method="post" action="/lch/modules/lab/lab_tokens.php" class="inline">
                                    <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                    <button type="submit" name="complete_token" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700" onclick="return confirm('Mark this token as completed?')">
                                        <i class="fas fa-check mr-1"></i> Complete
                                    </button>
                                </form>
                                <?php else: ?>
                                <button class="bg-gray-400 text-white py-1 px-3 rounded cursor-not-allowed" disabled title="Add report first">
                                    <i class="fas fa-check mr-1"></i> Complete
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="p-8 text-center">
            <i class="fas fa-vial text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-1">No patients in queue</h3>
            <p class="text-gray-500">There are no patients waiting for lab tests at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Today's Completed Tests</h2>
    
    <?php
    $today = date('Y-m-d');
    $query = "SELECT t.*, p.name as patient_name FROM tokens t JOIN patients p ON t.patient_id = p.id 
              WHERE t.type = 'lab' AND t.status = 'completed' AND DATE(t.created_at) = '$today' 
              ORDER BY t.created_at DESC";
    $completed_tokens = $conn->query($query);
    ?>
    
    <?php if ($completed_tokens->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($token = $completed_tokens->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo $token['token_no']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $token['patient_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y, h:i A', strtotime($token['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="/lch/modules/lab/view_reports.php?patient_id=<?php echo $token['patient_id']; ?>" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700">
                                    <i class="fas fa-eye mr-1"></i> View Reports
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-4">
            <p class="text-gray-500">No completed tests today.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>