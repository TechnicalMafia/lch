<?php
require_once '../../includes/functions.php';
requireRole('doctor');

// Process form submission to mark token as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_token'])) {
    $token_id = $_POST['token_id'];
    $update_query = "UPDATE tokens SET status = 'completed' WHERE id = $token_id";
    if ($conn->query($update_query) === TRUE) {
        $success = "Token marked as completed";
        // Refresh the page to update the list
        header("Location: patient_queue.php?success=token_completed");
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Check if patient_id is provided in URL
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';

// Get waiting patients with correct query
$query = "SELECT t.*, p.name as patient_name 
          FROM tokens t 
          JOIN patients p ON t.patient_id = p.id 
          WHERE t.type = 'doctor' AND t.status = 'waiting' 
          ORDER BY t.created_at ASC";
$result = $conn->query($query);

// Get completed consultations for today
$today = date('Y-m-d');
$completed_query = "SELECT t.*, p.name as patient_name 
                   FROM tokens t 
                   JOIN patients p ON t.patient_id = p.id 
                   WHERE t.type = 'doctor' AND t.status = 'completed' AND DATE(t.updated_at) = '$today'
                   ORDER BY t.updated_at DESC";
$completed_result = $conn->query($completed_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Queue - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Patient Queue</h1>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 'token_completed'): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Token marked as completed successfully.
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Waiting Patients Section -->
        <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
            <div class="bg-blue-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-clock mr-2"></i>
                    Waiting Patients
                </h2>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Token No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Generated At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($token = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            <?php echo $token['token_no']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $token['patient_name']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d M Y h:i A', strtotime($token['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                            <button type="submit" name="complete_token" 
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-2">
                                                <i class="fas fa-check mr-1"></i> Complete
                                            </button>
                                        </form>
                                        <a href="view_patient.php?id=<?php echo $token['patient_id']; ?>" 
                                           class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No patients in queue</h3>
                    <p class="text-gray-500">There are no patients waiting for consultation at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Today's Completed Consultations Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-green-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Today's Completed Consultations
                </h2>
            </div>
            
            <?php if ($completed_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Token No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Completed At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($token = $completed_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            <?php echo $token['token_no']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $token['patient_name']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d M Y h:i A', strtotime($token['updated_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="view_patient.php?id=<?php echo $token['patient_id']; ?>" 
                                           class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-2">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                        <a href="add_comments.php?patient_id=<?php echo $token['patient_id']; ?>" 
                                           class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <i class="fas fa-comment-medical mr-1"></i> Add Comments
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <i class="fas fa-clipboard-check text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No completed consultations today</h3>
                    <p class="text-gray-500">No patient consultations have been completed today.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>