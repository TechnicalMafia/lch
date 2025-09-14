<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $newborn_name = $_POST['newborn_name'];
    $father_name = $_POST['father_name'];
    $mother_name = $_POST['mother_name'];
    $father_nic = $_POST['father_nic'];
    $mother_nic = $_POST['mother_nic'];
    $date_of_birth = $_POST['date_of_birth'];
    $time_of_birth = $_POST['time_of_birth'];
    $weight = $_POST['weight'];
    $length = $_POST['length'];
    $gender = $_POST['gender'];
    $delivery_type = $_POST['delivery_type'];
    $birth_complications = $_POST['birth_complications'];
    $doctor_id = $_POST['doctor_id'];
    $address = $_POST['address'];
    $created_by = $_SESSION['user_id'];
    
    // Generate certificate number
    $certificate_number = generateBirthCertificateNumber();
    $issued_by = $_SESSION['username'];
    
    $query = "INSERT INTO birth_records (patient_id, newborn_name, father_name, mother_name, father_nic, mother_nic, 
              date_of_birth, time_of_birth, weight, length, gender, delivery_type, birth_complications, 
              doctor_id, address, certificate_number, issued_by, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssssssddsssisssi", $patient_id, $newborn_name, $father_name, $mother_name, 
                      $father_nic, $mother_nic, $date_of_birth, $time_of_birth, $weight, $length, 
                      $gender, $delivery_type, $birth_complications, $doctor_id, $address, 
                      $certificate_number, $issued_by, $created_by);
    
    if ($stmt->execute()) {
        $birth_record_id = $conn->insert_id;
        $success = "Birth record added successfully. Certificate Number: $certificate_number";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// Get all patients for dropdown
$patients = getAllPatients();

// Get all doctors for dropdown
$doctors = getAllStaff();
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add Birth Record</h1>
    <p class="text-gray-600">Register a new birth and generate birth certificate</p>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <?php echo $success; ?>
    <div class="mt-2">
        <a href="birth_certificates.php?id=<?php echo $birth_record_id; ?>" class="bg-green-600 text-white py-1 px-3 rounded text-sm hover:bg-green-700">
            <i class="fas fa-certificate mr-1"></i> Print Birth Certificate
        </a>
        <a href="birth_death_records.php" class="bg-blue-600 text-white py-1 px-3 rounded text-sm hover:bg-blue-700 ml-2">
            <i class="fas fa-arrow-left mr-1"></i> Back to Records
        </a>
    </div>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow p-6">
    <form method="post" action="add_birth_record.php">
        <!-- Mother/Patient Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Mother Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="patient_id" class="block text-gray-700 font-medium mb-2">Select Mother (Patient) <span class="text-red-500">*</span></label>
                    <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="fillMotherInfo()">
                        <option value="">Select a patient</option>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <option value="<?php echo $patient['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($patient['name']); ?>"
                                    data-contact="<?php echo htmlspecialchars($patient['contact']); ?>"
                                    data-address="<?php echo htmlspecialchars($patient['address']); ?>">
                                <?php echo $patient['name'] . ' (ID: ' . $patient['id'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label for="mother_name" class="block text-gray-700 font-medium mb-2">Mother's Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="mother_name" name="mother_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="mother_nic" class="block text-gray-700 font-medium mb-2">Mother's NIC Number</label>
                    <input type="text" id="mother_nic" name="mother_nic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Father Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Father Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="father_name" class="block text-gray-700 font-medium mb-2">Father's Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="father_name" name="father_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="father_nic" class="block text-gray-700 font-medium mb-2">Father's NIC Number <span class="text-red-500">*</span></label>
                    <input type="text" id="father_nic" name="father_nic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>
        </div>

        <!-- Newborn Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Newborn Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="newborn_name" class="block text-gray-700 font-medium mb-2">Newborn's Name <span class="text-red-500">*</span></label>
                    <input type="text" id="newborn_name" name="newborn_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="gender" class="block text-gray-700 font-medium mb-2">Gender <span class="text-red-500">*</span></label>
                    <select id="gender" name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                
                <div>
                    <label for="delivery_type" class="block text-gray-700 font-medium mb-2">Delivery Type <span class="text-red-500">*</span></label>
                    <select id="delivery_type" name="delivery_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Type</option>
                        <option value="Normal">Normal Delivery</option>
                        <option value="C-Section">C-Section</option>
                        <option value="Assisted">Assisted Delivery</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Birth Details -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Birth Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label for="date_of_birth" class="block text-gray-700 font-medium mb-2">Date of Birth <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="date_of_birth" name="date_of_birth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="time_of_birth" class="block text-gray-700 font-medium mb-2">Time of Birth <span class="text-red-500">*</span></label>
                    <input type="time" id="time_of_birth" name="time_of_birth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="weight" class="block text-gray-700 font-medium mb-2">Weight (kg)</label>
                    <input type="number" id="weight" name="weight" step="0.01" placeholder="3.50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="length" class="block text-gray-700 font-medium mb-2">Length (cm)</label>
                    <input type="number" id="length" name="length" step="0.1" placeholder="50.0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Medical Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Medical Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="doctor_id" class="block text-gray-700 font-medium mb-2">Attending Doctor</label>
                    <select id="doctor_id" name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Doctor</option>
                        <?php 
                        $doctors->data_seek(0);
                        while ($doctor = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['id']; ?>">Dr. <?php echo $doctor['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label for="birth_complications" class="block text-gray-700 font-medium mb-2">Birth Complications (if any)</label>
                    <textarea id="birth_complications" name="birth_complications" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter any complications during birth..."></textarea>
                </div>
            </div>
        </div>

        <!-- Address -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Address Information</h3>
            <div>
                <label for="address" class="block text-gray-700 font-medium mb-2">Full Address <span class="text-red-500">*</span></label>
                <textarea id="address" name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Enter complete address..."></textarea>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <a href="birth_death_records.php" class="bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                Cancel
            </a>
            <button type="submit" class="bg-green-600 text-white py-2 px-6 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                Add Birth Record
            </button>
        </div>
    </form>
</div>

<script>
    // Auto-fill mother information when patient is selected
    function fillMotherInfo() {
        const select = document.getElementById('patient_id');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.value) {
            document.getElementById('mother_name').value = selectedOption.dataset.name || '';
            document.getElementById('address').value = selectedOption.dataset.address || '';
        } else {
            document.getElementById('mother_name').value = '';
            document.getElementById('address').value = '';
        }
    }
    
    // Set current date and time as default
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const currentDateTime = now.toISOString().slice(0, 16);
        const currentTime = now.toTimeString().slice(0, 5);
        
        document.getElementById('date_of_birth').value = currentDateTime;
        document.getElementById('time_of_birth').value = currentTime;
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>