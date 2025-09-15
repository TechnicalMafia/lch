<?php
require_once '../../includes/functions.php';
requireRole('reception');

// Process form submission to update patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    $patient_id = $_POST['patient_id'];
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $contact = $_POST['contact'];
    $emergency_contact = $_POST['emergency_contact'];
    $relative_name = $_POST['relative_name'];
    $nic = $_POST['nic'];
    $address = $_POST['address'];
    
    // Update patient using prepared statement
    $query = "UPDATE patients SET name = ?, age = ?, gender = ?, contact = ?, 
              emergency_contact = ?, relative_name = ?, nic = ?, address = ? 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sissssssi", $name, $age, $gender, $contact, $emergency_contact, 
                      $relative_name, $nic, $address, $patient_id);
    
    if ($stmt->execute()) {
        $success = "Patient updated successfully";
    } else {
        $error = "Error updating patient: " . $stmt->error;
    }
}

// Get all patients
$patients = getAllPatients();
?>
<?php include '../../includes/header.php'; ?>

<!-- Success/Error Messages -->
<?php if (isset($success)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $success; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $error; ?>
        </div>
    </div>
<?php endif; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Patient List</h1>
    <p class="text-gray-600">View all registered patients</p>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <div class="relative">
            <input type="text" id="search" placeholder="Search patients..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
        </div>
        <a href="add_patient.php" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            <i class="fas fa-plus mr-2"></i>Add New Patient
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="patient-table-body">
                <?php if ($patients->num_rows > 0): ?>
                    <?php while ($patient = $patients->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo $patient['name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['age']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['gender']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['contact']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($patient['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="../doctor/view_patient.php?id=<?php echo $patient['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                <button onclick="openEditModal(<?php echo $patient['id']; ?>)" class="text-yellow-600 hover:text-yellow-900 mr-3">Edit</button>
                                <a href="token.php?patient_id=<?php echo $patient['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Token</a>
                                <a href="billing.php?patient_id=<?php echo $patient['id']; ?>" class="text-purple-600 hover:text-purple-900">Bill</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No patients found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Patient Modal -->
<div id="editPatientModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-user-edit mr-2 text-yellow-600"></i>
                    Edit Patient
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="editPatientForm" method="POST" class="space-y-4">
                <input type="hidden" name="update_patient" value="1">
                <input type="hidden" name="patient_id" id="edit_patient_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="name" id="edit_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age *</label>
                        <input type="number" name="age" id="edit_age" required min="0" max="150"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                        <select name="gender" id="edit_gender" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                        <input type="tel" name="contact" id="edit_contact" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label>
                        <input type="tel" name="emergency_contact" id="edit_emergency_contact" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Husband/Father/Guardian Name</label>
                        <input type="text" name="relative_name" id="edit_relative_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIC Number</label>
                        <input type="text" name="nic" id="edit_nic" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" id="edit_address" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition duration-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition duration-200">
                        <i class="fas fa-save mr-2"></i>Update Patient
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#patient-table-body tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    function openEditModal(patientId) {
        // Fetch patient data via AJAX
        fetch('get_patient_details.php?patient_id=' + patientId)
            .then(response => response.json())
            .then(data => {
                if (data.success !== false) {
                    // Populate form fields
                    document.getElementById('edit_patient_id').value = patientId;
                    document.getElementById('edit_name').value = data.name || '';
                    document.getElementById('edit_age').value = data.age || '';
                    document.getElementById('edit_gender').value = data.gender || '';
                    document.getElementById('edit_contact').value = data.contact || '';
                    document.getElementById('edit_emergency_contact').value = data.emergency_contact || '';
                    document.getElementById('edit_relative_name').value = data.relative_name || '';
                    document.getElementById('edit_nic').value = data.nic || '';
                    document.getElementById('edit_address').value = data.address || '';
                    
                    // Show modal
                    document.getElementById('editPatientModal').classList.remove('hidden');
                } else {
                    alert('Error loading patient data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading patient data');
            });
    }
    
    function closeEditModal() {
        document.getElementById('editPatientModal').classList.add('hidden');
        document.getElementById('editPatientForm').reset();
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editPatientModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>