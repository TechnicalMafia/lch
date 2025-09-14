<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('doctor');

// Get patient ID from URL and validate it
if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    // Redirect back to patient queue if no valid patient ID
    header('Location: patient_queue.php');
    exit;
}

$patient_id = $_GET['patient_id'];

// Get patient details
$patient = getPatient($patient_id);

// Check if patient exists
if (!$patient) {
    // Redirect back if patient not found
    header('Location: patient_queue.php?error=patient_not_found');
    exit;
}

// Initialize variables
$success = '';
$error = '';
$comments = '';
$medicines = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = 1; // Default doctor ID (should be logged in doctor's ID)
    $visit_date = $_POST['visit_date'];
    $comments = $_POST['comments'];
    $medicines = $_POST['medicines'];
    
    if (empty($visit_date) || empty($comments) || empty($medicines)) {
        $error = "All fields are required";
    } else {
        $query = "INSERT INTO visits (patient_id, doctor_id, visit_date, comments, medicines) 
                  VALUES ($patient_id, $doctor_id, '$visit_date', '$comments', '$medicines')";
        
        if ($conn->query($query) === TRUE) {
            $success = "Visit record added successfully";
            
            // Get the last inserted visit ID
            $visit_id = $conn->insert_id;
            
            // Create billing record for consultation
            $billing_query = "INSERT INTO billing (patient_id, service_name, amount, paid_amount, status) 
                              VALUES ($patient_id, 'Doctor Consultation', 1000, 0, 'unpaid')";
            $conn->query($billing_query);
            
            // Get the billing ID
            $billing_id = $conn->insert_id;
            
            // Update visit with billing ID
            $update_query = "UPDATE visits SET billing_id = $billing_id WHERE id = $visit_id";
            $conn->query($update_query);
            
            // Clear form fields after successful submission
            $comments = '';
            $medicines = '';
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add Visit Comments</h1>
    <p class="text-gray-600">Record patient visit details</p>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'patient_not_found'): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    Patient not found.
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <?php echo $success; ?>
    <div class="mt-2">
        <button onclick="printConsultation()" class="bg-green-600 text-white py-1 px-3 rounded text-sm hover:bg-green-700">
            <i class="fas fa-print mr-1"></i> Print Consultation Slip
        </button>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Patient Information</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">ID:</span>
                    <span class="font-medium"><?php echo $patient['id']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Name:</span>
                    <span class="font-medium"><?php echo $patient['name']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Age:</span>
                    <span class="font-medium"><?php echo $patient['age']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Gender:</span>
                    <span class="font-medium"><?php echo $patient['gender']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Contact:</span>
                    <span class="font-medium"><?php echo $patient['contact']; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <form method="post" action="add_comments.php">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                
                <div class="mb-4">
                    <label for="visit_date" class="block text-gray-700 font-medium mb-2">Visit Date</label>
                    <input type="date" id="visit_date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-4">
                    <label for="comments" class="block text-gray-700 font-medium mb-2">Comments / Diagnosis</label>
                    <textarea id="comments" name="comments" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required><?php echo htmlspecialchars($comments); ?></textarea>
                </div>
                
                <div class="mb-6">
                    <label for="medicines" class="block text-gray-700 font-medium mb-2">Prescribed Medicines</label>
                    <textarea id="medicines" name="medicines" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required><?php echo htmlspecialchars($medicines); ?></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Save Visit Record
                    </button>
                    <a href="view_patient.php?id=<?php echo $patient_id; ?>" class="bg-gray-600 text-white py-2 px-6 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ml-2">
                        Back to Patient
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Consultation Slip Print Template -->
<div id="consultation-print" class="hidden">
    <div class="p-6" style="width: 300px; font-family: monospace;">
        <div class="text-center mb-4">
            <h3 class="font-bold text-lg">HOSPITAL MANAGEMENT SYSTEM</h3>
            <p class="text-sm">123 Medical Street, Lahore</p>
            <p class="text-sm">Phone: 0300-1234567</p>
        </div>
        
        <div class="border-t border-b border-dashed py-2 my-2">
            <div class="flex justify-between mb-1">
                <span class="font-bold">DATE:</span>
                <span><?php echo date('d M Y'); ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="font-bold">TIME:</span>
                <span><?php echo date('h:i A'); ?></span>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="font-bold mb-2">PATIENT DETAILS:</div>
            <div class="flex justify-between mb-1">
                <span>ID:</span>
                <span><?php echo $patient['id']; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Name:</span>
                <span><?php echo $patient['name']; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Age:</span>
                <span><?php echo $patient['age']; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Gender:</span>
                <span><?php echo $patient['gender']; ?></span>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="font-bold mb-2">CONSULTATION DETAILS:</div>
            <div class="mb-2">
                <span class="font-bold">Diagnosis:</span>
                <p><?php echo htmlspecialchars($comments); ?></p>
            </div>
            <div>
                <span class="font-bold">Medicines:</span>
                <p><?php echo htmlspecialchars($medicines); ?></p>
            </div>
        </div>
        
        <div class="text-center text-xs mt-6">
            <p>Dr. [Doctor Name]</p>
            <p>Signature: _______________</p>
        </div>
    </div>
</div>

<script>
    function printConsultation() {
        const printContent = document.getElementById('consultation-print').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>