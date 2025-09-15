<?php
require_once '../../includes/functions.php';
requireRole('reception');
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Get current user information
try {
    $current_user = getUser($_SESSION['user_id']);
    $current_username = $current_user['username'];
} catch (Exception $e) {
    $current_username = 'Unknown';
}
// Get filter parameters
$token_type_filter = isset($_GET['token_type_filter']) ? $_GET['token_type_filter'] : 'all';
$pending_from_date = isset($_GET['pending_from_date']) ? $_GET['pending_from_date'] : date('Y-m-d', strtotime('-7 days'));
$pending_to_date = isset($_GET['pending_to_date']) ? $_GET['pending_to_date'] : date('Y-m-d');
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
        
        // Validate lab token has test selected
        if ($token_type === 'lab' && !$test_id) {
            $error = "Please select a test for lab token";
        } else {
            // For doctor tokens, check if patient already has a waiting doctor token
            if ($token_type === 'doctor') {
                $check_doctor_token_query = "SELECT id FROM tokens 
                                           WHERE patient_id = $patient_id 
                                           AND type = 'doctor' 
                                           AND status = 'waiting'";
                $check_result = $conn->query($check_doctor_token_query);
                
                if ($check_result->num_rows > 0) {
                    $error = "Patient already has a waiting doctor token. Please complete or cancel the existing token first.";
                }
            }
            
            // Only proceed if no error
            if (!isset($error)) {
                // Create billing record first and mark as paid immediately
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
                
                // Insert billing record as PAID
                $billing_query = "INSERT INTO billing (patient_id, service_name, amount, paid_amount, status) 
                                  VALUES ($patient_id, '$service_name', $amount, $amount, 'paid')";
                
                if ($conn->query($billing_query) === TRUE) {
                    $bill_id = $conn->insert_id;
                    
                    // Generate token number
                    $token_no = generateToken($token_type);
                    
                    // Insert token into database
                    $query = "INSERT INTO tokens (patient_id, type, token_no, status, doctor_id, test_id) 
                              VALUES ($patient_id, '$token_type', '$token_no', 'waiting', " . ($doctor_id ? "'$doctor_id'" : "NULL") . ", " . ($test_id ? "'$test_id'" : "NULL") . ")";
                    
                    if ($conn->query($query) === TRUE) {
                        $token_id = $conn->insert_id;
                        
                        // Update billing record with token_id
                        $update_billing_query = "UPDATE billing SET token_id = $token_id WHERE id = $bill_id";
                        $conn->query($update_billing_query);
                        
                        $success = "Token generated successfully: $token_no (Bill ID: $bill_id)";
                        
                        // Get patient details
                        $patient = getPatient($patient_id);
                        
                        // Store token details for printing
                        $print_token_data = [
                            'token_no' => $token_no,
                            'patient_id' => $patient_id,
                            'patient_name' => $patient['name'],
                            'token_type' => $token_type,
                            'assigned_to' => ($token_type === 'doctor' && $doctor_id) ? $doctor['name'] : (($token_type === 'lab' && $test_id) ? getTest($test_id)['test_name'] : 'N/A'),
                            'bill_id' => $bill_id,
                            'generated_by' => $current_username,
                            'amount' => $amount
                        ];
                    } else {
                        $error = "Error creating token: " . $conn->error;
                    }
                } else {
                    $error = "Error creating billing record: " . $conn->error;
                }
            }
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
        $success = "Token cancelled successfully";
    } else {
        $error = "Error cancelling token: " . $conn->error;
    }
}
// Get all patients for dropdown
$patients = getAllPatients();
// Get all doctors for dropdown (from users with role 'doctor')
$doctor_query = "SELECT u.id as user_id, s.id as staff_id, s.name FROM users u JOIN staff s ON u.staff_id = s.id WHERE u.role = 'doctor'";
$doctors = $conn->query($doctor_query);
// Get all tests for dropdown
$tests = getAllTests();
// Get today's tokens with filter
$today = date('Y-m-d');
$token_filter_clause = "";
if ($token_type_filter !== 'all') {
    $token_filter_clause = " AND t.type = '$token_type_filter'";
}
$today_tokens_query = "SELECT t.*, p.name as patient_name, 
                      CASE WHEN t.doctor_id IS NOT NULL THEN s.name ELSE NULL END as doctor_name,
                      CASE WHEN t.test_id IS NOT NULL THEN ts.test_name ELSE NULL END as test_name,
                      b.id as bill_id, b.status as bill_status
                      FROM tokens t 
                      JOIN patients p ON t.patient_id = p.id 
                      LEFT JOIN staff s ON t.doctor_id = s.id 
                      LEFT JOIN tests ts ON t.test_id = ts.id 
                      LEFT JOIN billing b ON t.id = b.token_id
                      WHERE DATE(t.created_at) = '$today' AND t.status != 'cancelled' $token_filter_clause
                      ORDER BY t.created_at DESC";
$today_tokens = $conn->query($today_tokens_query);
// Get refunded tokens with filter
$refunded_tokens_query = "SELECT t.*, p.name as patient_name, 
                         CASE WHEN t.doctor_id IS NOT NULL THEN s.name ELSE NULL END as doctor_name,
                         CASE WHEN t.test_id IS NOT NULL THEN ts.test_name ELSE NULL END as test_name
                         FROM tokens t 
                         JOIN patients p ON t.patient_id = p.id 
                         LEFT JOIN staff s ON t.doctor_id = s.id 
                         LEFT JOIN tests ts ON t.test_id = ts.id 
                         WHERE DATE(t.created_at) = '$today' AND t.status = 'cancelled' $token_filter_clause
                         ORDER BY t.created_at DESC";
$refunded_tokens = $conn->query($refunded_tokens_query);
// Get token statistics
$stats_query = "SELECT 
                    COUNT(*) as total_tokens,
                    COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting_tokens,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tokens,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_tokens,
                    COUNT(CASE WHEN type = 'doctor' THEN 1 END) as doctor_tokens,
                    COUNT(CASE WHEN type = 'lab' THEN 1 END) as lab_tokens
                FROM tokens t
                WHERE DATE(t.created_at) = '$today' $token_filter_clause";
$stats_result = $conn->query($stats_query);
$token_stats = $stats_result->fetch_assoc();
// Get pending tokens with date filter
$pending_date_filter = " AND DATE(t.created_at) BETWEEN '$pending_from_date' AND '$pending_to_date'";
$pending_tokens_query = "SELECT t.*, p.name as patient_name, 
                        CASE WHEN t.doctor_id IS NOT NULL THEN s.name ELSE NULL END as doctor_name,
                        CASE WHEN t.test_id IS NOT NULL THEN ts.test_name ELSE NULL END as test_name,
                        b.id as bill_id
                        FROM tokens t 
                        JOIN patients p ON t.patient_id = p.id 
                        LEFT JOIN staff s ON t.doctor_id = s.id 
                        LEFT JOIN tests ts ON t.test_id = ts.id 
                        LEFT JOIN billing b ON t.id = b.token_id
                        WHERE t.status = 'waiting' $pending_date_filter
                        ORDER BY t.created_at DESC";
$pending_tokens = $conn->query($pending_tokens_query);
// Get pending token statistics
$pending_stats_query = "SELECT 
                            COUNT(*) as total_pending,
                            COUNT(CASE WHEN type = 'doctor' THEN 1 END) as pending_doctor,
                            COUNT(CASE WHEN type = 'lab' THEN 1 END) as pending_lab
                        FROM tokens t
                        WHERE t.status = 'waiting' $pending_date_filter";
$pending_stats_result = $conn->query($pending_stats_query);
$pending_stats = $pending_stats_result->fetch_assoc();
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
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .thermal-print {
            background: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            max-width: 300px;
            margin: 0 auto;
            font-family: 'Courier New', monospace;
            color: black;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .thermal-print, .thermal-print * {
                visibility: visible;
            }
            .thermal-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Token Generation & Management</h1>
            <p class="text-gray-600">Generate tokens for doctor consultations and lab tests</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $success; ?>
                    </div>
                    <?php if (isset($print_token_data)): ?>
                        <button onclick="showPrintPreview('<?php echo htmlspecialchars(json_encode($print_token_data)); ?>')" 
                                class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            <i class="fas fa-print mr-2"></i>Print Token
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
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
                <form method="post" id="tokenForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="patient_id" class="block text-gray-700 font-medium mb-2">Select Patient <span class="text-red-500">*</span></label>
                            <select name="patient_id" id="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="">Select Patient</option>
                                <?php 
                                if ($patients) {
                                    $patients->data_seek(0);
                                    while ($patient = $patients->fetch_assoc()): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo $patient['name']; ?> (ID: <?php echo $patient['id']; ?>) - <?php echo $patient['contact']; ?>
                                        </option>
                                    <?php endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="token_type" class="block text-gray-700 font-medium mb-2">Token Type <span class="text-red-500">*</span></label>
                            <select name="token_type" id="token_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="">Select Token Type</option>
                                <option value="doctor">Doctor Consultation</option>
                                <option value="lab">Lab Test</option>
                            </select>
                        </div>
                        
                        <div id="doctor-dropdown" style="display: none;">
                            <label for="doctor_id" class="block text-gray-700 font-medium mb-2">Select Doctor</label>
                            <select name="doctor_id" id="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Doctor (Optional)</option>
                                <?php 
                                if ($doctors) {
                                    $doctors->data_seek(0);
                                    while ($doctor = $doctors->fetch_assoc()): ?>
                                        <option value="<?php echo $doctor['staff_id']; ?>">Dr. <?php echo $doctor['name']; ?></option>
                                    <?php endwhile;
                                }
                                ?>
                            </select>
                            <div class="mt-1 text-xs text-gray-500">Optional - Leave blank for general consultation</div>
                        </div>
                        
                        <div id="test-dropdown" style="display: none;">
                            <label for="test_id" class="block text-gray-700 font-medium mb-2">Select Test <span class="text-red-500">*</span></label>
                            <select name="test_id" id="test_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Test</option>
                                <?php 
                                if ($tests) {
                                    $tests->data_seek(0);
                                    while ($test = $tests->fetch_assoc()): ?>
                                        <option value="<?php echo $test['id']; ?>">
                                            <?php echo $test['test_name']; ?> - <?php echo formatCurrency($test['price']); ?>
                                        </option>
                                    <?php endwhile;
                                }
                                ?>
                            </select>
                            <div class="mt-1 text-xs text-gray-500">Required for lab tokens</div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span class="text-red-500">*</span> Required fields
                        </div>
                        <button type="submit" name="generate_token" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-plus mr-2"></i>Generate Token
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Token Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-500">
                        <i class="fas fa-ticket-alt text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-gray-700">Total Today</h3>
                        <p class="text-xl font-bold"><?php echo $token_stats['total_tokens'] ?: 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-yellow-100 text-yellow-500">
                        <i class="fas fa-clock text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-gray-700">Waiting</h3>
                        <p class="text-xl font-bold"><?php echo $token_stats['waiting_tokens'] ?: 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-green-100 text-green-500">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-gray-700">Completed</h3>
                        <p class="text-xl font-bold"><?php echo $token_stats['completed_tokens'] ?: 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-red-100 text-red-500">
                        <i class="fas fa-times-circle text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-gray-700">Cancelled</h3>
                        <p class="text-xl font-bold"><?php echo $token_stats['cancelled_tokens'] ?: 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-purple-100 text-purple-500">
                        <i class="fas fa-user-md text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-gray-700">Doctor</h3>
                        <p class="text-xl font-bold"><?php echo $token_stats['doctor_tokens'] ?: 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-indigo-100 text-indigo-500">
                        <i class="fas fa-flask text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-gray-700">Lab</h3>
                        <p class="text-xl font-bold"><?php echo $token_stats['lab_tokens'] ?: 0; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Today's Tokens Card -->
        <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
            <div class="bg-blue-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center justify-between">
                    <span><i class="fas fa-ticket-alt mr-2"></i>Today's Active Tokens</span>
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To / Test</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($today_tokens && $today_tokens->num_rows > 0): ?>
                            <?php while ($token = $today_tokens->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium <?php echo $token['type'] === 'doctor' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'; ?>">
                                            <?php echo $token['token_no']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $token['patient_name']; ?></div>
                                        <div class="text-sm text-gray-500">ID: <?php echo $token['patient_id']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $token['type'] === 'doctor' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'; ?>">
                                            <i class="fas fa-<?php echo $token['type'] === 'doctor' ? 'user-md' : 'flask'; ?> mr-1"></i>
                                            <?php echo ucfirst($token['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php 
                                            if ($token['type'] === 'doctor' && $token['doctor_name']) {
                                                echo "Dr. " . $token['doctor_name'];
                                            } elseif ($token['type'] === 'lab' && $token['test_name']) {
                                                echo $token['test_name'];
                                            } else {
                                                echo "<span class='text-gray-500'>Not assigned</span>";
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($token['bill_id']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                #<?php echo $token['bill_id']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                            if ($token['status'] === 'waiting') echo 'bg-yellow-100 text-yellow-800';
                                            elseif ($token['status'] === 'completed') echo 'bg-green-100 text-green-800';
                                            else echo 'bg-red-100 text-red-800';
                                        ?>">
                                            <i class="fas fa-<?php 
                                                if ($token['status'] === 'waiting') echo 'clock';
                                                elseif ($token['status'] === 'completed') echo 'check-circle';
                                                else echo 'times-circle';
                                            ?> mr-1"></i>
                                            <?php echo ucfirst($token['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('h:i A', strtotime($token['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="showPrintPreview('<?php echo htmlspecialchars(json_encode([
                                            'token_no' => $token['token_no'],
                                            'patient_id' => $token['patient_id'],
                                            'patient_name' => $token['patient_name'],
                                            'token_type' => $token['type'],
                                            'assigned_to' => ($token['type'] === 'doctor' && $token['doctor_name']) ? "Dr. " . $token['doctor_name'] : (($token['type'] === 'lab' && $token['test_name']) ? $token['test_name'] : 'N/A'),
                                            'bill_id' => $token['bill_id'] ?? null,
                                            'generated_by' => $current_username,
                                            'amount' => $token['type'] === 'doctor' ? 1000 : 500
                                        ])); ?>')" 
                                                class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 mr-2 text-xs">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button onclick="editToken(<?php echo $token['id']; ?>, '<?php echo $token['type']; ?>', <?php echo $token['doctor_id'] ?: 'null'; ?>, <?php echo $token['test_id'] ?: 'null'; ?>)" 
                                                class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 mr-2 text-xs">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmRefund(<?php echo $token['id']; ?>)" 
                                                class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-xs">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <i class="fas fa-ticket-alt text-gray-400 text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-1">No active tokens</h3>
                                    <p class="text-gray-500">No tokens have been generated today.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pending Tokens Section -->
        <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
            <div class="bg-orange-600 text-white px-6 py-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <h2 class="text-xl font-semibold flex items-center mb-2 md:mb-0">
                        <i class="fas fa-hourglass-half mr-2"></i>
                        All Pending Tokens
                    </h2>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm">
                            Total: <span class="font-bold"><?php echo $pending_stats['total_pending'] ?: 0; ?></span>
                            (Dr: <span class="font-bold"><?php echo $pending_stats['pending_doctor'] ?: 0; ?></span>
                            | Lab: <span class="font-bold"><?php echo $pending_stats['pending_lab'] ?: 0; ?></span>)
                        </div>
                        <form method="get" class="flex items-center space-x-2">
                            <input type="hidden" name="token_type_filter" value="<?php echo $token_type_filter; ?>">
                            <input type="date" name="pending_from_date" value="<?php echo $pending_from_date; ?>" 
                                   class="px-2 py-1 border border-gray-300 rounded text-sm">
                            <span class="text-white">to</span>
                            <input type="date" name="pending_to_date" value="<?php echo $pending_to_date; ?>" 
                                   class="px-2 py-1 border border-gray-300 rounded text-sm">
                            <button type="submit" class="bg-orange-700 text-white px-3 py-1 rounded text-sm hover:bg-orange-800">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To / Test</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waiting Since</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($pending_tokens && $pending_tokens->num_rows > 0): ?>
                            <?php while ($token = $pending_tokens->fetch_assoc()): 
                                $created_time = strtotime($token['created_at']);
                                $current_time = time();
                                $waiting_hours = floor(($current_time - $created_time) / 3600);
                                $waiting_days = floor($waiting_hours / 24);
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium <?php echo $token['type'] === 'doctor' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'; ?>">
                                            <?php echo $token['token_no']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $token['patient_name']; ?></div>
                                        <div class="text-sm text-gray-500">ID: <?php echo $token['patient_id']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $token['type'] === 'doctor' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'; ?>">
                                            <i class="fas fa-<?php echo $token['type'] === 'doctor' ? 'user-md' : 'flask'; ?> mr-1"></i>
                                            <?php echo ucfirst($token['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php 
                                            if ($token['type'] === 'doctor' && $token['doctor_name']) {
                                                echo "Dr. " . $token['doctor_name'];
                                            } elseif ($token['type'] === 'lab' && $token['test_name']) {
                                                echo $token['test_name'];
                                            } else {
                                                echo "<span class='text-gray-500'>Not assigned</span>";
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d M Y, h:i A', strtotime($token['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                            if ($waiting_days > 0) echo 'bg-red-100 text-red-800';
                                            elseif ($waiting_hours > 2) echo 'bg-yellow-100 text-yellow-800';
                                            else echo 'bg-green-100 text-green-800';
                                        ?>">
                                            <?php 
                                            if ($waiting_days > 0) {
                                                echo $waiting_days . ' day' . ($waiting_days > 1 ? 's' : '');
                                            } elseif ($waiting_hours > 0) {
                                                echo $waiting_hours . ' hour' . ($waiting_hours > 1 ? 's' : '');
                                            } else {
                                                echo '< 1 hour';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php 
                                        // Create a safe data array for JSON encoding
                                        $token_data = [
                                            'token_no' => $token['token_no'],
                                            'patient_id' => $token['patient_id'],
                                            'patient_name' => $token['patient_name'],
                                            'token_type' => $token['type'],
                                            'assigned_to' => ($token['type'] === 'doctor' && $token['doctor_name']) ? "Dr. " . $token['doctor_name'] : (($token['type'] === 'lab' && $token['test_name']) ? $token['test_name'] : 'N/A'),
                                            'bill_id' => $token['bill_id'] ?? null,
                                            'generated_by' => $current_username,
                                            'amount' => $token['type'] === 'doctor' ? 1000 : 500
                                        ];
                                        ?>
                                        <button onclick="showPrintPreview('<?php echo htmlspecialchars(json_encode($token_data)); ?>')" 
                                                class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 mr-2 text-xs">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button onclick="editToken(<?php echo $token['id']; ?>, '<?php echo $token['type']; ?>', <?php echo $token['doctor_id'] ?: 'null'; ?>, <?php echo $token['test_id'] ?: 'null'; ?>)" 
                                                class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 mr-2 text-xs">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmRefund(<?php echo $token['id']; ?>)" 
                                                class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-xs">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-hourglass-end text-gray-400 text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-1">No pending tokens</h3>
                                    <p class="text-gray-500">
                                        No pending tokens found for the selected date range.
                                    </p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Cancelled/Refunded Tokens Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-red-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-undo-alt mr-2"></i>
                    Cancelled/Refunded Tokens Today
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To / Test</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cancelled At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($refunded_tokens && $refunded_tokens->num_rows > 0): ?>
                            <?php while ($token = $refunded_tokens->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                            <?php echo $token['token_no']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $token['patient_name']; ?></div>
                                        <div class="text-sm text-gray-500">ID: <?php echo $token['patient_id']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="fas fa-<?php echo $token['type'] === 'doctor' ? 'user-md' : 'flask'; ?> mr-1"></i>
                                            <?php echo ucfirst($token['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                                        <?php echo date('h:i A', strtotime($token['updated_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fas fa-undo text-gray-400 text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-1">No cancelled tokens</h3>
                                    <p class="text-gray-500">No tokens have been cancelled today.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Edit Token Modal -->
    <div id="editTokenModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2 class="text-xl font-semibold mb-4 text-indigo-600">
                <i class="fas fa-edit mr-2"></i>Edit Token
            </h2>
            <form method="post">
                <input type="hidden" id="edit_token_id" name="token_id">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Token Type</label>
                    <input type="text" id="edit_token_type" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                </div>
                <div id="edit_doctor_dropdown" class="mb-4">
                    <label for="edit_doctor_id" class="block text-gray-700 font-medium mb-2">Select Doctor</label>
                    <select id="edit_doctor_id" name="edit_doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Doctor</option>
                        <?php 
                        if ($doctors) {
                            $doctors->data_seek(0);
                            while ($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['staff_id']; ?>">Dr. <?php echo $doctor['name']; ?></option>
                            <?php endwhile;
                        }
                        ?>
                    </select>
                </div>
                <div id="edit_test_dropdown" class="mb-4" style="display: none;">
                    <label for="edit_test_id" class="block text-gray-700 font-medium mb-2">Select Test</label>
                    <select id="edit_test_id" name="edit_test_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Test</option>
                        <?php 
                        if ($tests) {
                            $tests->data_seek(0);
                            while ($test = $tests->fetch_assoc()): ?>
                                <option value="<?php echo $test['id']; ?>"><?php echo $test['test_name']; ?> - <?php echo formatCurrency($test['price']); ?></option>
                            <?php endwhile;
                        }
                        ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" 
                            class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" name="update_token" 
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        <i class="fas fa-save mr-2"></i>Update Token
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Refund Confirmation Modal -->
    <div id="refundModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRefundModal()">&times;</span>
            <h2 class="text-xl font-semibold mb-4 text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Cancel
            </h2>
            <p class="mb-4">Are you sure you want to cancel this token? This will mark the token as cancelled and update the billing record to refunded status.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRefundModal()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Cancel
                </button>
                <form method="post" style="display: inline;">
                    <input type="hidden" id="refund_token_id" name="token_id">
                    <button type="submit" name="refund_token" 
                            class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        <i class="fas fa-times mr-2"></i>Cancel Token
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Print Preview Modal -->
    <div id="printPreviewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePrintPreview()">&times;</span>
            <h3 class="text-xl font-bold mb-4">Token Preview</h3>
            <div id="thermalContent" class="thermal-print">
                <!-- Thermal print content will be inserted here -->
            </div>
            <div class="mt-4 flex justify-end space-x-3">
                <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <button onclick="closePrintPreview()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
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
                document.getElementById('test_id').removeAttribute('required');
            } else if (this.value === 'lab') {
                doctorDropdown.style.display = 'none';
                testDropdown.style.display = 'block';
                document.getElementById('test_id').setAttribute('required', 'required');
            } else {
                doctorDropdown.style.display = 'none';
                testDropdown.style.display = 'none';
                document.getElementById('test_id').removeAttribute('required');
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
            
            document.getElementById('editTokenModal').style.display = 'block';
        }
        
        // Close edit modal
        function closeEditModal() {
            document.getElementById('editTokenModal').style.display = 'none';
        }
        
        // Confirm refund
        function confirmRefund(tokenId) {
            document.getElementById('refund_token_id').value = tokenId;
            document.getElementById('refundModal').style.display = 'block';
        }
        
        // Close refund modal
        function closeRefundModal() {
            document.getElementById('refundModal').style.display = 'none';
        }
        
        // Close print preview
        function closePrintPreview() {
            document.getElementById('printPreviewModal').style.display = 'none';
        }
        
        // Show print preview with thermal print styling
        function showPrintPreview(tokenData) {
            const data = typeof tokenData === 'string' ? JSON.parse(tokenData) : tokenData;
            
            const currentDate = new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            const currentTime = new Date().toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            
            const thermalHTML = `
                <div style="text-align: center; padding: 8px 0; border-bottom: 2px solid #000; margin-bottom: 8px;">
                    <div style="font-size: 14px; font-weight: bold; margin: 0; text-transform: uppercase;">LIFE CARE HOSPITAL</div>
                    <div style="font-size: 9px; margin: 1px 0; font-weight: normal;">Sector A GT road, Haripur</div>
                    <div style="font-size: 9px; margin: 1px 0; font-weight: normal;">Phone: (0995) 321234</div>
                </div>
                
                <div style="text-align: center; margin: 6px 0; padding: 4px; background: #f0f0f0; border-radius: 4px;">
                    <div style="font-size: 10px; font-weight: normal; margin: 0; color: #000;">TOKEN NUMBER</div>
                    <div style="font-size: 18px; font-weight: bold; margin: 1px 0; color: #000; letter-spacing: 1px;">${data.token_no}</div>
                    <div style="font-size: 9px; margin: 1px 0; color: #000;">
                        ${currentDate} | ${currentTime}
                    </div>
                </div>
                
                <div style="text-align: center; margin: 6px 0; padding: 4px; background: #e8f5e8; border-radius: 4px;">
                    <div style="font-size: 10px; font-weight: bold; margin: 0 0 1px 0; color: #000;">BILL INFORMATION</div>
                    <div style="font-size: 10px; font-weight: bold; margin: 1px 0; color: #000;">Bill ID: ${data.bill_id || 'N/A'}</div>
                    <div style="font-size: 10px; font-weight: bold; margin: 1px 0; color: #000;">Amount: PKR ${parseFloat(data.amount).toFixed(2)}</div>
                    <div style="font-size: 10px; font-weight: bold; margin: 1px 0; color: #000;">Status: PAID</div>
                </div>
                
                <div style="margin: 8px 0; padding: 4px; border-left: 4px solid #000; background: #f8f9fa;">
                    <div style="font-size: 10px; font-weight: bold; margin: 0 0 1px 0; color: #000;">TOKEN TYPE</div>
                    <div style="font-size: 12px; font-weight: bold; margin: 0 0 1px 0; color: #000; text-transform: uppercase;">${data.token_type.charAt(0).toUpperCase() + data.token_type.slice(1)} Token</div>
                    <div style="font-size: 9px; margin: 1px 0; color: #000;">ASSIGNED TO:</div>
                    <div style="font-size: 10px; font-weight: bold; margin: 0; color: #000;">${data.assigned_to}</div>
                </div>
                
                <div style="margin: 8px 0; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                    <div style="font-size: 10px; font-weight: bold; margin: 0 0 4px 0; text-align: center; text-transform: uppercase; color: #000; border-bottom: 1px solid #ddd; padding-bottom: 2px;">Patient Information</div>
                    <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                        <span style="font-weight: bold; color: #000;">ID:</span>
                        <span style="font-weight: bold; color: #000;">${data.patient_id}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                        <span style="font-weight: bold; color: #000;">Name:</span>
                        <span style="font-weight: bold; color: #000; max-width: 150px; text-align: right;">${data.patient_name}</span>
                    </div>
                </div>
                
                <div style="margin: 8px 0; padding: 4px; background: #f0f8ff; border-radius: 4px;">
                    <div style="font-size: 10px; font-weight: bold; margin: 0 0 1px 0; color: #000;">GENERATED BY</div>
                    <div style="font-size: 10px; font-weight: bold; margin: 0; color: #000;">${data.generated_by}</div>
                </div>
                
                <div style="margin: 10px 0; padding: 6px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; text-align: center;">
                    <div style="font-size: 10px; font-weight: bold; margin: 0 0 3px 0; color: #000;">⚠️ IMPORTANT INSTRUCTIONS</div>
                    <div style="font-size: 8px; margin: 1px 0; color: #000;">• Please wait for your turn to be called</div>
                    <div style="font-size: 8px; margin: 1px 0; color: #000;">• Keep this token safe for verification</div>
                    <div style="font-size: 8px; margin: 1px 0; color: #000;">• Present this token when called</div>
                </div>
                
                <div style="text-align: center; margin-top: 10px; padding-top: 6px; border-top: 1px dashed #000; font-size: 8px; color: #000;">
                    <div style="font-weight: bold; margin: 0 0 1px 0;">Thank you for choosing Life Care Hospital</div>
                    <div style="margin: 0 0 1px 0;">We value your health and time</div>
                    <div style="font-size: 7px; margin: 3px 0 0 0; color: #666;">Generated on: ${new Date().toLocaleString()}</div>
                </div>
            `;
            
            document.getElementById('thermalContent').innerHTML = thermalHTML;
            document.getElementById('printPreviewModal').style.display = 'block';
        }
        
        // Form validation
        document.getElementById('tokenForm').addEventListener('submit', function(e) {
            const tokenType = document.getElementById('token_type').value;
            const testId = document.getElementById('test_id').value;
            
            if (tokenType === 'lab' && !testId) {
                e.preventDefault();
                alert('Please select a test for lab token.');
                return false;
            }
        });
        
        // Auto-focus on patient selection after page load
        window.addEventListener('load', function() {
            document.getElementById('patient_id').focus();
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editTokenModal');
            const refundModal = document.getElementById('refundModal');
            const printModal = document.getElementById('printPreviewModal');
            
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === refundModal) {
                refundModal.style.display = 'none';
            }
            if (event.target === printModal) {
                printModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>