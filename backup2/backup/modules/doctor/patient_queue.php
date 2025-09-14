<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('doctor');

// Get doctor tokens
$doctor_tokens = getDoctorTokens();

// Process form submission to mark token as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_token'])) {
    $token_id = $_POST['token_id'];
    
    $update_query = "UPDATE tokens SET status = 'completed' WHERE id = $token_id";
    
    if ($conn->query($update_query) === TRUE) {
        $success = "Token marked as completed";
        // Refresh the page to update the list
        header("Location: patient_queue.php");
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Check if patient_id is provided in URL
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Patient Queue</h1>
    <p class="text-gray-600">View and manage patient tokens</p>
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

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Waiting Patients</h2>
    </div>
    
    <?php if ($doctor_tokens->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($token = $doctor_tokens->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo $token['token_no']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $token['patient_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y, h:i A', strtotime($token['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <form method="post" action="patient_queue.php" class="inline">
                                    <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                    <button type="submit" name="complete_token" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700">
                                        <i class="fas fa-check mr-1"></i> Complete
                                    </button>
                                </form>
                                <a href="view_patient.php?id=<?php echo $token['patient_id']; ?>" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 ml-2">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="p-8 text-center">
            <i class="fas fa-users text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-1">No patients in queue</h3>
            <p class="text-gray-500">There are no patients waiting for consultation at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Today's Completed Consultations</h2>
    
    <?php
    $today = date('Y-m-d');
    $query = "SELECT t.*, p.name as patient_name FROM tokens t JOIN patients p ON t.patient_id = p.id 
              WHERE t.type = 'doctor' AND t.status = 'completed' AND DATE(t.created_at) = '$today' 
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
                                <a href="view_patient.php?id=<?php echo $token['patient_id']; ?>" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <a href="add_comments.php?patient_id=<?php echo $token['patient_id']; ?>" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700 ml-2">
                                    <i class="fas fa-comment-medical mr-1"></i> Add Comments
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-4">
            <p class="text-gray-500">No completed consultations today.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>