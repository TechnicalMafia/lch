<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $deceased_name = $_POST['deceased_name'];
    $father_name = $_POST['father_name'];
    $mother_name = $_POST['mother_name'];
    $spouse_name = $_POST['spouse_name'];
    $deceased_nic = $_POST['deceased_nic'];
    $date_of_death = $_POST['date_of_death'];
    $time_of_death = $_POST['time_of_death'];
    $age_at_death = $_POST['age_at_death'];
    $cause_of_death = $_POST['cause_of_death'];
    $place_of_death = $_POST['place_of_death'];
    $doctor_id = $_POST['doctor_id'];
    $address = $_POST['address'];
    $next_of_kin = $_POST['next_of_kin'];
    $next_of_kin_relation = $_POST['next_of_kin_relation'];
    $next_of_kin_contact = $_POST['next_of_kin_contact'];
    $autopsy_required = isset($_POST['autopsy_required']) ? 1 : 0;
    $autopsy_notes = $_POST['autopsy_notes'];
    $created_by = $_SESSION['user_id'];
    
    // Generate certificate number
    $certificate_number = generateDeathCertificateNumber();
    $issued_by = $_SESSION['username'];
    
    $query = "INSERT INTO death_records (patient_id, deceased_name, father_name, mother_name, spouse_name, 
              deceased_nic, date_of_death, time_of_death, age_at_death, cause_of_death, place_of_death, 
              doctor_id, address, next_of_kin, next_of_kin_relation, next_of_kin_contact, 
              autopsy_required, autopsy_notes, certificate_number, issued_by, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssssssisissssssissi", $patient_id, $deceased_name, $father_name, $mother_name, 
                      $spouse_name, $deceased_nic, $date_of_death, $time_of_death, $age_at_death, 
                      $cause_of_death, $place_of_death, $doctor_id, $address, $next_of_kin, 
                      $next_of_kin_relation, $next_of_kin_contact, $autopsy_required, $autopsy_notes, 
                      $certificate_number, $issued_by, $created_by);
    
    if ($stmt->execute()) {
        $death_record_id = $conn->insert_id;
        $success = "Death record added successfully. Certificate Number: $certificate_number";
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
    <h1 class="text-2xl font-bold text-gray-800">Add Death Record</h1>
    <p class="text-gray-600">Register a death and generate death certificate</p>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <?php echo $success; ?>
    <div class="mt-2">
        <a href="death_certificates.php?id=<?php echo $death_record_id; ?>" class="bg-green-600 text-white py-1 px-3 rounded text-sm hover:bg-green-700">
            <i class="fas fa-certificate mr-1"></i> Print Death Certificate
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
    <form method="post" action="add_death_record.php">
        <!-- Deceased Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Deceased Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="patient_id" class="block text-gray-700 font-medium mb-2">Select Patient <span class="text-red-500">*</span></label>
                    <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="fillPatientInfo()">
                        <option value="">Select a patient</option>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <option value="<?php echo $patient['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($patient['name']); ?>"
                                    data-age="<?php echo $patient['age']; ?>"
                                    data-contact="<?php echo htmlspecialchars($patient['contact']); ?>"
                                    data-address="<?php echo htmlspecialchars($patient['address']); ?>">
                                <?php echo $patient['name'] . ' (ID: ' . $patient['id'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label for="deceased_name" class="block text-gray-700 font-medium mb-2">Deceased Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="deceased_name" name="deceased_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="deceased_nic" class="block text-gray-700 font-medium mb-2">Deceased NIC Number</label>
                    <input type="text" id="deceased_nic" name="deceased_nic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="age_at_death" class="block text-gray-700 font-medium mb-2">Age at Death <span class="text-red-500">*</span></label>
                    <input type="number" id="age_at_death" name="age_at_death" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>
        </div>

        <!-- Family Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Family Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="father_name" class="block text-gray-700 font-medium mb-2">Father's Name <span class="text-red-500">*</span></label>
                    <input type="text" id="father_name" name="father_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="mother_name" class="block text-gray-700 font-medium mb-2">Mother's Name</label>
                    <input type="text" id="mother_name" name="mother_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="spouse_name" class="block text-gray-700 font-medium mb-2">Spouse Name (if applicable)</label>
                    <input type="text" id="spouse_name" name="spouse_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Death Details -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Death Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="date_of_death" class="block text-gray-700 font-medium mb-2">Date of Death <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="date_of_death" name="date_of_death" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="time_of_death" class="block text-gray-700 font-medium mb-2">Time of Death <span class="text-red-500">*</span></label>
                    <input type="time" id="time_of_death" name="time_of_death" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="place_of_death" class="block text-gray-700 font-medium mb-2">Place of Death <span class="text-red-500">*</span></label>
                    <input type="text" id="place_of_death" name="place_of_death" value="Life Care Hospital" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>
        </div>

        <!-- Medical Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Medical Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cause_of_death" class="block text-gray-700 font-medium mb-2">Cause of Death <span class="text-red-500">*</span></label>
                    <textarea id="cause_of_death" name="cause_of_death" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Enter detailed cause of death..."></textarea>
                </div>
                
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
            </div>
        </div>

        <!-- Next of Kin Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Next of Kin Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="next_of_kin" class="block text-gray-700 font-medium mb-2">Next of Kin Name <span class="text-red-500">*</span></label>
                    <input type="text" id="next_of_kin" name="next_of_kin" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="next_of_kin_relation" class="block text-gray-700 font-medium mb-2">Relation <span class="text-red-500">*</span></label>
                    <select id="next_of_kin_relation" name="next_of_kin_relation" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Relation</option>
                        <option value="Spouse">Spouse</option>
                        <option value="Son">Son</option>
                        <option value="Daughter">Daughter</option>
                        <option value="Father">Father</option>
                        <option value="Mother">Mother</option>
                        <option value="Brother">Brother</option>
                        <option value="Sister">Sister</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label for="next_of_kin_contact" class="block text-gray-700 font-medium mb-2">Contact Number <span class="text-red-500">*</span></label>
                    <input type="text" id="next_of_kin_contact" name="next_of_kin_contact" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>
        </div>

        <!-- Autopsy Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Autopsy Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" id="autopsy_required" name="autopsy_required" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" onchange="toggleAutopsyNotes()">
                        <span class="ml-2 text-gray-700 font-medium">Autopsy Required</span>
                    </label>
                </div>
                
                <div id="autopsy_notes_section" class="hidden">
                    <label for="autopsy_notes" class="block text-gray-700 font-medium mb-2">Autopsy Notes</label>
                    <textarea id="autopsy_notes" name="autopsy_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter autopsy related notes..."></textarea>
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
            <button type="submit" class="bg-red-600 text-white py-2 px-6 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">
                Add Death Record
            </button>
        </div>
    </form>
</div>

<script>
    // Auto-fill patient information when patient is selected
    function fillPatientInfo() {
        const select = document.getElementById('patient_id');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.value) {
            document.getElementById('deceased_name').value = selectedOption.dataset.name || '';
            document.getElementById('age_at_death').value = selectedOption.dataset.age || '';
            document.getElementById('address').value = selectedOption.dataset.address || '';
            document.getElementById('next_of_kin_contact').value = selectedOption.dataset.contact || '';
        } else {
            document.getElementById('deceased_name').value = '';
            document.getElementById('age_at_death').value = '';
            document.getElementById('address').value = '';
            document.getElementById('next_of_kin_contact').value = '';
        }
    }
    
    // Toggle autopsy notes section
    function toggleAutopsyNotes() {
        const checkbox = document.getElementById('autopsy_required');
        const notesSection = document.getElementById('autopsy_notes_section');
        
        if (checkbox.checked) {
            notesSection.classList.remove('hidden');
        } else {
            notesSection.classList.add('hidden');
        }
    }
    
    // Set current date and time as default
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const currentDateTime = now.toISOString().slice(0, 16);
        const currentTime = now.toTimeString().slice(0, 5);
        
        document.getElementById('date_of_death').value = currentDateTime;
        document.getElementById('time_of_death').value = currentTime;
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>