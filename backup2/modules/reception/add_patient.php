<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $contact = $_POST['contact'];
    $emergency_contact = $_POST['emergency_contact'];
    $relative_name = $_POST['relative_name'];
    $nic = $_POST['nic'];
    $address = $_POST['address'];
    
    $query = "INSERT INTO patients (name, age, gender, contact, emergency_contact, relative_name, nic, address) 
              VALUES ('$name', $age, '$gender', '$contact', '$emergency_contact', '$relative_name', '$nic', '$address')";
    
    if ($conn->query($query) === TRUE) {
        $success = "Patient added successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add New Patient</h1>
    <p class="text-gray-600">Register a new patient in the system</p>
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

<div class="bg-white rounded-lg shadow p-6">
    <form method="post" action="add_patient.php">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                <input type="text" id="name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div>
                <label for="age" class="block text-gray-700 font-medium mb-2">Age</label>
                <input type="number" id="age" name="age" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div>
                <label for="gender" class="block text-gray-700 font-medium mb-2">Gender</label>
                <select id="gender" name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div>
                <label for="contact" class="block text-gray-700 font-medium mb-2">Contact Number</label>
                <input type="text" id="contact" name="contact" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div>
                <label for="emergency_contact" class="block text-gray-700 font-medium mb-2">Emergency Contact</label>
                <input type="text" id="emergency_contact" name="emergency_contact" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="relative_name" class="block text-gray-700 font-medium mb-2">Husband/Father/Guardian Name</label>
                <input type="text" id="relative_name" name="relative_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="nic" class="block text-gray-700 font-medium mb-2">NIC Number</label>
                <input type="text" id="nic" name="nic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="md:col-span-2">
                <label for="address" class="block text-gray-700 font-medium mb-2">Address</label>
                <textarea id="address" name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                Add Patient
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>