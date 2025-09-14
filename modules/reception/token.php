<?php
require_once '../../includes/functions.php';
requireRole('reception');

// Process form submission to generate token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_token'])) {
    // Check if required fields are set
    if (!isset($_POST['patient_id']) || empty($_POST['patient_id'])) {
        $error = "Please select a patient";
    } elseif (!isset($_POST['token_type']) || empty($_POST['token_type'])) {
        $error = "Please select a token type";
    } else {
        $patient_id = $_POST['patient_id'];
        $token_type = $_POST['token_type'];
        $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : null;
        $test_id = isset($_POST['test_id']) ? $_POST['test_id'] : null;
        
        // Generate token number
        $token_no = generateToken($token_type);
        
        // Insert token into database
        $query = "INSERT INTO tokens (patient_id, type, token_no, status, doctor_id, test_id) 
                  VALUES ($patient_id, '$token_type', '$token_no', 'waiting', " . ($doctor_id ? "'$doctor_id'" : "NULL") . ", " . ($test_id ? "'$test_id'" : "NULL") . ")";
        
        if ($conn->query($query) === TRUE) {
            $token_id = $conn->insert_id;
            $success = "Token generated successfully: $token_no";
            
            // Get patient details
            $patient = getPatient($patient_id);
            
            // Create billing record with token number and mark as paid
            if ($token_type === 'doctor' && $doctor_id) {
                $doctor = getStaff($doctor_id);
                $service_name = "Doctor Consultation - Dr. " . $doctor['name'];
                $amount = 1000; // Default fee
            } elseif ($token_type === 'lab' && $test_id) {
                $test = getTest($test_id);
                $service_name = "Lab Test - " . $test['test_name'];
                $amount = $test['price'];
            } else {
                $service_name = ($token_type === 'doctor') ? 'Doctor Consultation' : 'Lab Test';
                $amount = ($token_type === 'doctor') ? 1000 : 500; // Default fees
            }
            
            $billing_query = "INSERT INTO billing (patient_id, service_name, amount, paid_amount, status, token_id, token_number) 
                              VALUES ($patient_id, '$service_name', $amount, $amount, 'paid', $token_id, '$token_no')";
            $conn->query($billing_query);
            
            // Store token details for printing
            $print_token_data = [
                'token_no' => $token_no,
                'patient_id' => $patient_id,
                'patient_name' => $patient['name'],
                'token_type' => $token_type,
                'assigned_to' => ($token_type === 'doctor' && $doctor_id) ? $doctor['name'] : (($token_type === 'lab' && $test_id) ? getTest($test_id)['test_name'] : 'N/A')
            ];
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Process form submission to update token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_token'])) {
    $token_id = $_POST['token_id'];
    $doctor_id = isset($_POST['edit_doctor_id']) ? $_POST['edit_doctor_id'] : null;
    $test_id = isset($_POST['edit_test_id']) ? $_POST['edit_test_id'] : null;
    
    // Update token
    $update_query = "UPDATE tokens SET doctor_id = " . ($doctor_id ? "'$doctor_id'" : "NULL") . ", 
                     test_id = " . ($test_id ? "'$test_id'" : "NULL") . " 
                     WHERE id = $token_id";
    
    if ($conn->query($update_query) === TRUE) {
        // Update billing record
        $billing_query = "UPDATE billing SET service_name = " . 
                          ($doctor_id ? "'Doctor Consultation - Dr. " . getStaff($doctor_id)['name'] . "'" : 
                          ($test_id ? "'Lab Test - " . getTest($test_id)['test_name'] . "'" : "'Service'")) . 
                          " WHERE token_id = $token_id";
        
        if ($conn->query($billing_query) === TRUE) {
            $success = "Token updated successfully";
        } else {
            $error = "Error updating billing record: " . $conn->error;
        }
    } else {
        $error = "Error updating token: " . $conn->error;
    }
}

// Process form submission to refund token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refund_token'])) {
    $token_id = $_POST['token_id'];
    
    // Update token status to cancelled
    $update_token_query = "UPDATE tokens SET status = 'cancelled' WHERE id = $token_id";
    $conn->query($update_token_query);
    
    // Update corresponding billing record to refund
    $update_billing_query = "UPDATE billing SET status = 'refund' WHERE token_id = $token_id";
    if ($conn->query($update_billing_query) === TRUE) {
        $success = "Token refunded successfully";
    } else {
        $error = "Error refunding token: " . $conn->error;
    }
}

// Get all patients for dropdown
$patients = getAllPatients();

// Get all doctors for dropdown (from users with role 'doctor')
$doctor_query = "SELECT u.id as user_id, s.id as staff_id, s.name FROM users u JOIN staff s ON u.staff_id = s.id WHERE u.role = 'doctor'";
$doctors = $conn->query($doctor_query);

// Get all tests for dropdown
$tests = getAllTests();

// Get today's tokens
$today = date('Y-m-d');
$today_tokens_query = "SELECT t.*, p.name as patient_name, 
                      CASE WHEN t.doctor_id IS NOT NULL THEN s.name ELSE NULL END as doctor_name,
                      CASE WHEN t.test_id IS NOT NULL THEN ts.test_name ELSE NULL END as test_name
                      FROM tokens t 
                      JOIN patients p ON t.patient_id = p.id 
                      LEFT JOIN staff s ON t.doctor_id = s.id 
                      LEFT JOIN tests ts ON t.test_id = ts.id 
                      WHERE DATE(t.created_at) = '$today' AND t.status != 'cancelled'
                      ORDER BY t.created_at DESC";
$today_tokens = $conn->query($today_tokens_query);

// Get refunded tokens
$refunded_tokens_query = "SELECT t.*, p.name as patient_name, 
                         CASE WHEN t.doctor_id IS NOT NULL THEN s.name ELSE NULL END as doctor_name,
                         CASE WHEN t.test_id IS NOT NULL THEN ts.test_name ELSE NULL END as test_name
                         FROM tokens t 
                         JOIN patients p ON t.patient_id = p.id 
                         LEFT JOIN staff s ON t.doctor_id = s.id 
                         LEFT JOIN tests ts ON t.test_id = ts.id 
                         WHERE DATE(t.created_at) = '$today' AND t.status = 'cancelled'
                         ORDER BY t.created_at DESC";
$refunded_tokens = $conn->query($refunded_tokens_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Token - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Token Print Styles */
        .token-container {
            width: 300px;
            padding: 10px;
            margin: 0 auto;
            font-family: monospace;
            font-size: 12px;
        }
        .token-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        .token-section {
            margin: 10px 0;
        }
        .token-border-section {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            margin: 5px 0;
        }
        .token-footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        @media print {
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Generate Token</h1>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
                <?php if (isset($print_token_data)): ?>
                    <div class="mt-3">
                        <button onclick="printToken('<?php echo $print_token_data['token_no']; ?>', '<?php echo $print_token_data['patient_id']; ?>', '<?php echo $print_token_data['patient_name']; ?>', '<?php echo $print_token_data['token_type']; ?>', '<?php echo $print_token_data['assigned_to']; ?>')" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                            <i class="fas fa-print mr-2"></i>Print Token
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Generate Token Form -->
        <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
            <div class="bg-indigo-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Generate New Token
                </h2>
            </div>
            <div class="p-6">
                <form method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Select Patient</label>
                            <select name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="">Select Patient</option>
                                <?php while ($patient = $patients->fetch_assoc()): ?>
                                    <option value="<?php echo $patient['id']; ?>"><?php echo $patient['name']; ?> (ID: <?php echo $patient['id']; ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Token Type</label>
                            <select name="token_type" id="token_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="doctor">Doctor Consultation</option>
                                <option value="lab">Lab Test</option>
                            </select>
                        </div>
                        <div id="doctor-dropdown">
                            <label class="block text-gray-700 mb-2">Select Doctor</label>
                            <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Doctor</option>
                                <?php 
                                $doctors->data_seek(0); // Reset pointer
                                while ($doctor = $doctors->fetch_assoc()): ?>
                                    <option value="<?php echo $doctor['staff_id']; ?>"><?php echo $doctor['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div id="test-dropdown" style="display: none;">
                            <label class="block text-gray-700 mb-2">Select Test</label>
                            <select name="test_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Test</option>
                                <?php while ($test = $tests->fetch_assoc()): ?>
                                    <option value="<?php echo $test['id']; ?>"><?php echo $test['test_name']; ?> (PKR <?php echo $test['price']; ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="generate_token" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-plus mr-2"></i>Generate Token
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Today's Tokens Card -->
        <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
            <div class="bg-blue-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-ticket-alt mr-2"></i>
                    Today's Tokens
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Token No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To / Test Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($today_tokens->num_rows > 0): ?>
                            <?php while ($token = $today_tokens->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            <?php echo $token['token_no']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $token['patient_name']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo ucfirst($token['type']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        if ($token['type'] === 'doctor' && $token['doctor_name']) {
                                            echo "Dr. " . $token['doctor_name'];
                                        } elseif ($token['type'] === 'lab' && $token['test_name']) {
                                            echo $token['test_name'];
                                        } else {
                                            echo "Not assigned";
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            if ($token['status'] === 'waiting') {
                                                echo 'bg-yellow-200 text-yellow-800';
                                            } elseif ($token['status'] === 'completed') {
                                                echo 'bg-green-200 text-green-800';
                                            } else {
                                                echo 'bg-red-200 text-red-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($token['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600 mr-1 text-sm" 
                                                onclick="printToken('<?php echo $token['token_no']; ?>', '<?php echo $token['patient_id']; ?>', '<?php echo $token['patient_name']; ?>', '<?php echo $token['type']; ?>', '<?php echo ($token['type'] === 'doctor' && $token['doctor_name']) ? "Dr. " . $token['doctor_name'] : (($token['type'] === 'lab' && $token['test_name']) ? $token['test_name'] : 'N/A'); ?>')">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button class="bg-yellow-500 text-white px-3 py-1 rounded-md hover:bg-yellow-600 mr-1 text-sm" 
                                                onclick="editToken(<?php echo $token['id']; ?>, '<?php echo $token['type']; ?>', <?php echo $token['doctor_id'] ?: 'null'; ?>, <?php echo $token['test_id'] ?: 'null'; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600 text-sm" 
                                                onclick="confirmRefund(<?php echo $token['id']; ?>)">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-ticket-alt text-gray-400 text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-1">No tokens generated today</h3>
                                    <p class="text-gray-500">No tokens have been generated today.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Refunded Tokens Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-red-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-undo-alt mr-2"></i>
                    Cancelled/Refunded Tokens
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Token No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To / Test Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Refunded At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($refunded_tokens->num_rows > 0): ?>
                            <?php while ($token = $refunded_tokens->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                            <?php echo $token['token_no']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $token['patient_name']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo ucfirst($token['type']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        if ($token['type'] === 'doctor' && $token['doctor_name']) {
                                            echo "Dr. " . $token['doctor_name'];
                                        } elseif ($token['type'] === 'lab' && $token['test_name']) {
                                            echo $token['test_name'];
                                        } else {
                                            echo "Not assigned";
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d M Y h:i A', strtotime($token['updated_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fas fa-undo text-gray-400 text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-1">No refunded tokens today</h3>
                                    <p class="text-gray-500">No tokens have been refunded today.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Edit Token Modal -->
    <div id="editTokenModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4">Edit Token</h2>
            <form method="post">
                <input type="hidden" id="edit_token_id" name="token_id">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Token Type</label>
                    <input type="text" id="edit_token_type" class="w-full px-3 py-2 border border-gray-300 rounded-md" readonly>
                </div>
                <div id="edit_doctor_dropdown" class="mb-4">
                    <label class="block text-gray-700 mb-2">Select Doctor</label>
                    <select id="edit_doctor_id" name="edit_doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Doctor</option>
                        <?php 
                        $doctors->data_seek(0); // Reset pointer
                        while ($doctor = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['staff_id']; ?>"><?php echo $doctor['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div id="edit_test_dropdown" class="mb-4" style="display: none;">
                    <label class="block text-gray-700 mb-2">Select Test</label>
                    <select id="edit_test_id" name="edit_test_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Test</option>
                        <?php 
                        $tests->data_seek(0); // Reset pointer
                        while ($test = $tests->fetch_assoc()): ?>
                            <option value="<?php echo $test['id']; ?>"><?php echo $test['test_name']; ?> (PKR <?php echo $test['price']; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_token" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Update Token</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Refund Confirmation Modal -->
    <div id="refundModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4">Confirm Refund</h2>
            <p>Are you sure you want to refund this token? This will mark the token as cancelled and update the billing record.</p>
            <div class="flex justify-end mt-4">
                <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2" onclick="closeRefundModal()">Cancel</button>
                <form method="post" style="display: inline;">
                    <input type="hidden" id="refund_token_id" name="token_id">
                    <button type="submit" name="refund_token" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">Refund Token</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Hidden Token Template -->
    <div id="tokenTemplate" style="display: none;">
        <div class="token-container">
            <div class="token-header">
                <div style="font-weight: bold; font-size: 14px;">HOSPITAL MANAGEMENT SYSTEM</div>
                <div style="font-size: 10px;">123 Medical Street, Lahore</div>
                <div style="font-size: 10px;">Phone: 0300-1234567</div>
            </div>
            <div class="token-section">
                <div><strong>TOKEN NO:</strong> <span id="printTokenNo"></span></div>
                <div><strong>DATE:</strong> <span id="printDate"></span></div>
                <div><strong>TIME:</strong> <span id="printTime"></span></div>
            </div>
            <div class="token-border-section">
                <div><strong>TYPE:</strong> <span id="printType"></span></div>
                <div><strong>ASSIGNED TO:</strong> <span id="printAssignedTo"></span></div>
            </div>
            <div class="token-section">
                <div style="font-weight: bold; text-decoration: underline;">PATIENT DETAILS:</div>
                <div><strong>ID:</strong> <span id="printPatientId"></span></div>
                <div><strong>NAME:</strong> <span id="printPatientName"></span></div>
            </div>
            <div class="token-footer">
                <div>Please wait for your turn</div>
                <div>Thank you for visiting</div>
            </div>
        </div>
    </div>
    
    <script>
        // Show/hide dropdowns based on token type
        document.getElementById('token_type').addEventListener('change', function() {
            const doctorDropdown = document.getElementById('doctor-dropdown');
            const testDropdown = document.getElementById('test-dropdown');
            
            if (this.value === 'doctor') {
                doctorDropdown.style.display = 'block';
                testDropdown.style.display = 'none';
            } else {
                doctorDropdown.style.display = 'none';
                testDropdown.style.display = 'block';
            }
        });
        
        // Edit token function
        function editToken(tokenId, tokenType, doctorId, testId) {
            document.getElementById('edit_token_id').value = tokenId;
            document.getElementById('edit_token_type').value = tokenType.charAt(0).toUpperCase() + tokenType.slice(1);
            
            const editDoctorDropdown = document.getElementById('edit_doctor_dropdown');
            const editTestDropdown = document.getElementById('edit_test_dropdown');
            
            if (tokenType === 'doctor') {
                editDoctorDropdown.style.display = 'block';
                editTestDropdown.style.display = 'none';
                if (doctorId) {
                    document.getElementById('edit_doctor_id').value = doctorId;
                }
            } else {
                editDoctorDropdown.style.display = 'none';
                editTestDropdown.style.display = 'block';
                if (testId) {
                    document.getElementById('edit_test_id').value = testId;
                }
            }
            
            document.getElementById('editTokenModal').classList.remove('hidden');
        }
        
        // Close edit modal
        function closeEditModal() {
            document.getElementById('editTokenModal').classList.add('hidden');
        }
        
        // Confirm refund
        function confirmRefund(tokenId) {
            document.getElementById('refund_token_id').value = tokenId;
            document.getElementById('refundModal').classList.remove('hidden');
        }
        
        // Close refund modal
        function closeRefundModal() {
            document.getElementById('refundModal').classList.add('hidden');
        }
        
        // Print token function using a template
        function printToken(tokenNo, patientId, patientName, tokenType, assignedTo) {
            // Get current date and time
            const currentDate = new Date().toLocaleDateString();
            const currentTime = new Date().toLocaleTimeString();
            const tokenTypeFormatted = tokenType.charAt(0).toUpperCase() + tokenType.slice(1);
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Get the template HTML
            const template = document.getElementById('tokenTemplate').innerHTML;
            
            // Create the complete HTML document
            let printContent = '<!DOCTYPE html>';
            printContent += '<html>';
            printContent += '<head>';
            printContent += '<title>Token - ' + tokenNo + '</title>';
            printContent += '<style>';
            printContent += 'body { margin: 0; padding: 0; font-family: monospace; font-size: 12px; }';
            printContent += '.token-container { width: 300px; padding: 10px; margin: 0 auto; }';
            printContent += '.token-header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }';
            printContent += '.token-section { margin: 10px 0; }';
            printContent += '.token-border-section { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; margin: 5px 0; }';
            printContent += '.token-footer { text-align: center; margin-top: 15px; border-top: 1px dashed #000; padding-top: 5px; }';
            printContent += '@media print { body { margin: 0; padding: 0; } }';
            printContent += '</style>';
            printContent += '</head>';
            printContent += '<body>';
            printContent += template;
            printContent += '<script>';
            printContent += 'document.getElementById("printTokenNo").textContent = "' + tokenNo + '";';
            printContent += 'document.getElementById("printDate").textContent = "' + currentDate + '";';
            printContent += 'document.getElementById("printTime").textContent = "' + currentTime + '";';
            printContent += 'document.getElementById("printType").textContent = "' + tokenTypeFormatted + '";';
            printContent += 'document.getElementById("printAssignedTo").textContent = "' + assignedTo + '";';
            printContent += 'document.getElementById("printPatientId").textContent = "' + patientId + '";';
            printContent += 'document.getElementById("printPatientName").textContent = "' + patientName + '";';
            printContent += 'window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 500); }';
            printContent += '<\/script>';
            printContent += '</body>';
            printContent += '</html>';
            
            // Write the content to the new window
            printWindow.document.write(printContent);
            printWindow.document.close();
        }
    </script>
</body>
</html>