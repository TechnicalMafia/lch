<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get patient ID from URL if available
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $token_type = $_POST['token_type'];
    $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : null;
    $test_id = isset($_POST['test_id']) ? $_POST['test_id'] : null;
    
    // Generate token number
    $token_no = generateToken($token_type);
    
    // Insert token into database
    $query = "INSERT INTO tokens (patient_id, type, token_no, doctor_id, test_id) VALUES ($patient_id, '$token_type', '$token_no', " . ($doctor_id ? $doctor_id : "NULL") . ", " . ($test_id ? $test_id : "NULL") . ")";
    
    if ($conn->query($query) === TRUE) {
        $token_id = $conn->insert_id;
        $success = "Token generated successfully: $token_no";
        
        // Get patient details
        $patient = getPatient($patient_id);
        
        // Create billing record
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
        
        $billing_query = "INSERT INTO billing (patient_id, service_name, amount, paid_amount, status, token_id) 
                          VALUES ($patient_id, '$service_name', $amount, 0, 'unpaid', $token_id)";
        $conn->query($billing_query);
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process token cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_token'])) {
    $token_id = $_POST['token_id'];
    
    // Update token status to cancelled
    $update_token_query = "UPDATE tokens SET status = 'cancelled' WHERE id = $token_id";
    $conn->query($update_token_query);
    
    // Update corresponding billing record to refund
    $update_billing_query = "UPDATE billing SET status = 'refund' WHERE token_id = $token_id";
    $conn->query($update_billing_query);
    
    $success = "Token cancelled and refund processed successfully";
}

// Get all patients for dropdown
$patients = getAllPatients();

// Get all doctors for dropdown
$doctors = getAllStaff();

// Get all tests for dropdown
$tests = getAllTests();
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Generate Token</h1>
    <p class="text-gray-600">Generate tokens for doctor consultation or lab tests</p>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <?php echo $success; ?>
    <div class="mt-2">
        <button onclick="printToken('<?php echo $token_no; ?>', '<?php echo $patient['id']; ?>', '<?php echo $patient['name']; ?>', '<?php echo $token_type; ?>')" class="bg-green-600 text-white py-1 px-3 rounded text-sm hover:bg-green-700">
            <i class="fas fa-print mr-1"></i> Print Token
        </button>
    </div>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="post" action="token.php">
            <div class="mb-4">
                <label for="patient_id" class="block text-gray-700 font-medium mb-2">Select Patient</label>
                <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select a patient</option>
                    <?php while ($patient = $patients->fetch_assoc()): ?>
                        <option value="<?php echo $patient['id']; ?>" <?php echo ($patient['id'] == $patient_id) ? 'selected' : ''; ?>>
                            <?php echo $patient['name'] . ' (ID: ' . $patient['id'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Token Type</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="token_type" value="doctor" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" checked onchange="toggleTokenOptions('doctor')">
                        <span class="ml-2 text-gray-700">Doctor Consultation</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="token_type" value="lab" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" onchange="toggleTokenOptions('lab')">
                        <span class="ml-2 text-gray-700">Lab Test</span>
                    </label>
                </div>
            </div>
            
            <div id="doctor-options" class="mb-4">
                <label for="doctor_id" class="block text-gray-700 font-medium mb-2">Select Doctor</label>
                <select id="doctor_id" name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select a doctor</option>
                    <?php 
                    $doctors->data_seek(0);
                    while ($doctor = $doctors->fetch_assoc()): ?>
                        <option value="<?php echo $doctor['id']; ?>">
                            <?php echo $doctor['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div id="lab-options" class="mb-4 hidden">
                <label for="test_id" class="block text-gray-700 font-medium mb-2">Select Test</label>
                <select id="test_id" name="test_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select a test</option>
                    <?php 
                    $tests->data_seek(0);
                    while ($test = $tests->fetch_assoc()): ?>
                        <option value="<?php echo $test['id']; ?>">
                            <?php echo $test['test_name'] . ' - ' . formatCurrency($test['price']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Generate Token
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Today's Tokens</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token No</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $today = date('Y-m-d');
                    $query = "SELECT t.*, p.name as patient_name FROM tokens t JOIN patients p ON t.patient_id = p.id WHERE DATE(t.created_at) = '$today' ORDER BY t.created_at";
                    $result = $conn->query($query);
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td class='px-4 py-2 whitespace-nowrap font-medium'>" . $row['token_no'] . "</td>";
                            echo "<td class='px-4 py-2 whitespace-nowrap'>" . $row['patient_name'] . "</td>";
                            echo "<td class='px-4 py-2 whitespace-nowrap'>" . ucfirst($row['type']) . "</td>";
                            echo "<td class='px-4 py-2 whitespace-nowrap'>";
                            if ($row['type'] === 'doctor' && $row['doctor_id']) {
                                $doctor = getStaff($row['doctor_id']);
                                echo "Dr. " . $doctor['name'];
                            } elseif ($row['type'] === 'lab' && $row['test_id']) {
                                $test = getTest($row['test_id']);
                                echo $test['test_name'];
                            } else {
                                echo "Not assigned";
                            }
                            echo "</td>";
                            echo "<td class='px-4 py-2 whitespace-nowrap'>";
                            if ($row['status'] === 'waiting') {
                                echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800'>Waiting</span>";
                            } elseif ($row['status'] === 'completed') {
                                echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800'>Completed</span>";
                            } elseif ($row['status'] === 'cancelled') {
                                echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800'>Cancelled</span>";
                            }
                            echo "</td>";
                            echo "<td class='px-4 py-2 whitespace-nowrap text-sm'>";
                            if ($row['status'] === 'waiting') {
                                echo "<form method='post' action='token.php' class='inline' onsubmit=\"return confirm('Are you sure you want to cancel this token?');\">";
                                echo "<input type='hidden' name='token_id' value='" . $row['id'] . "'>";
                                echo "<button type='submit' name='cancel_token' class='bg-red-600 text-white py-1 px-3 rounded hover:bg-red-700'>";
                                echo "<i class='fas fa-times mr-1'></i> Cancel";
                                echo "</button>";
                                echo "</form>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='px-4 py-2 text-center text-gray-500'>No tokens generated today</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Token Print Template -->
<div id="token-print" class="hidden">
    <div class="p-4" style="width: 300px; font-family: monospace;">
        <div class="text-center mb-2">
            <h3 class="font-bold">HOSPITAL MANAGEMENT SYSTEM</h3>
            <p class="text-sm">123 Medical Street, Lahore</p>
            <p class="text-sm">Phone: 0300-1234567</p>
        </div>
        
        <div class="border-t border-b border-dashed py-2 my-2">
            <div class="flex justify-between mb-1">
                <span class="font-bold">TOKEN NO:</span>
                <span id="print-token-no"></span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="font-bold">DATE:</span>
                <span id="print-date"></span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="font-bold">TIME:</span>
                <span id="print-time"></span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="font-bold">TYPE:</span>
                <span id="print-type"></span>
            </div>
        </div>
        
        <div class="mb-2">
            <div class="font-bold mb-1">PATIENT DETAILS:</div>
            <div class="flex justify-between mb-1">
                <span>ID:</span>
                <span id="print-patient-id"></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Name:</span>
                <span id="print-patient-name"></span>
            </div>
        </div>
        
        <div class="text-center text-xs mt-4">
            <p>Please wait for your turn</p>
            <p>Thank you for visiting</p>
        </div>
    </div>
</div>

<script>
    function toggleTokenOptions(type) {
        if (type === 'doctor') {
            document.getElementById('doctor-options').classList.remove('hidden');
            document.getElementById('lab-options').classList.add('hidden');
        } else {
            document.getElementById('doctor-options').classList.add('hidden');
            document.getElementById('lab-options').classList.remove('hidden');
        }
    }
    
    function printToken(tokenNo, patientId, patientName, tokenType) {
        // Set the values in the print template
        document.getElementById('print-token-no').textContent = tokenNo;
        document.getElementById('print-date').textContent = '<?php echo date('d M Y'); ?>';
        document.getElementById('print-time').textContent = '<?php echo date('h:i A'); ?>';
        document.getElementById('print-type').textContent = tokenType.charAt(0).toUpperCase() + tokenType.slice(1);
        document.getElementById('print-patient-id').textContent = patientId;
        document.getElementById('print-patient-name').textContent = patientName;
        
        // Print the token
        const printContent = document.getElementById('token-print').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>