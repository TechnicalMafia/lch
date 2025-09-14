<?php
require_once '../../includes/functions.php';
requireRole('admin');

// Process form submission to add new staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $nic = $_POST['nic'];
    $salary = $_POST['salary'];
    $duty_hours = $_POST['duty_hours'];
    $joining_date = $_POST['joining_date'];
    
    $query = "INSERT INTO staff (name, address, phone, nic, salary, duty_hours, joining_date) 
              VALUES ('$name', '$address', '$phone', '$nic', $salary, '$duty_hours', '$joining_date')";
    
    if ($conn->query($query) === TRUE) {
        $success = "Staff member added successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to update staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $staff_id = $_POST['staff_id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $nic = $_POST['nic'];
    $salary = $_POST['salary'];
    $duty_hours = $_POST['duty_hours'];
    $joining_date = $_POST['joining_date'];
    
    $query = "UPDATE staff SET name = '$name', address = '$address', phone = '$phone', nic = '$nic', 
              salary = $salary, duty_hours = '$duty_hours', joining_date = '$joining_date' 
              WHERE id = $staff_id";
    
    if ($conn->query($query) === TRUE) {
        $success = "Staff member updated successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to delete staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $staff_id = $_POST['staff_id'];
    
    $query = "DELETE FROM staff WHERE id = $staff_id";
    
    if ($conn->query($query) === TRUE) {
        $success = "Staff member deleted successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get all staff
$staff = getAllStaff();
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Manage Staff</h1>
    <p class="text-gray-600">Add, edit, and delete hospital staff</p>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Add New Staff</h2>
            <form method="post" action="staff.php">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                    <input type="text" id="name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-4">
                    <label for="address" class="block text-gray-700 font-medium mb-2">Address</label>
                    <textarea id="address" name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 font-medium mb-2">Phone</label>
                    <input type="text" id="phone" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-4">
                    <label for="nic" class="block text-gray-700 font-medium mb-2">NIC</label>
                    <input type="text" id="nic" name="nic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-4">
                    <label for="salary" class="block text-gray-700 font-medium mb-2">Salary (PKR)</label>
                    <input type="number" id="salary" name="salary" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-4">
                    <label for="duty_hours" class="block text-gray-700 font-medium mb-2">Duty Hours</label>
                    <input type="text" id="duty_hours" name="duty_hours" placeholder="e.g. 9AM-5PM" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-6">
                    <label for="joining_date" class="block text-gray-700 font-medium mb-2">Joining Date</label>
                    <input type="date" id="joining_date" name="joining_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="add_staff" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Add Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Hospital Staff</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joining Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($member = $staff->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $member['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $member['name']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $member['phone']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($member['salary']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($member['joining_date'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="editStaff(<?php echo $member['id']; ?>, '<?php echo $member['name']; ?>', '<?php echo $member['address']; ?>', '<?php echo $member['phone']; ?>', '<?php echo $member['nic']; ?>', <?php echo $member['salary']; ?>, '<?php echo $member['duty_hours']; ?>', '<?php echo $member['joining_date']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="post" action="staff.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                        <input type="hidden" name="staff_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" name="delete_staff" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="edit-staff-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Edit Staff</h2>
        <form method="post" action="staff.php">
            <input type="hidden" id="edit_staff_id" name="staff_id">
            
            <div class="mb-4">
                <label for="edit_name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                <input type="text" id="edit_name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="edit_address" class="block text-gray-700 font-medium mb-2">Address</label>
                <textarea id="edit_address" name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="mb-4">
                <label for="edit_phone" class="block text-gray-700 font-medium mb-2">Phone</label>
                <input type="text" id="edit_phone" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="edit_nic" class="block text-gray-700 font-medium mb-2">NIC</label>
                <input type="text" id="edit_nic" name="nic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="edit_salary" class="block text-gray-700 font-medium mb-2">Salary (PKR)</label>
                <input type="number" id="edit_salary" name="salary" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="edit_duty_hours" class="block text-gray-700 font-medium mb-2">Duty Hours</label>
                <input type="text" id="edit_duty_hours" name="duty_hours" placeholder="e.g. 9AM-5PM" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label for="edit_joining_date" class="block text-gray-700 font-medium mb-2">Joining Date</label>
                <input type="date" id="edit_joining_date" name="joining_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="submit" name="update_staff" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Update Staff
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function editStaff(id, name, address, phone, nic, salary, duty_hours, joining_date) {
        document.getElementById('edit_staff_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_address').value = address;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_nic').value = nic;
        document.getElementById('edit_salary').value = salary;
        document.getElementById('edit_duty_hours').value = duty_hours;
        document.getElementById('edit_joining_date').value = joining_date;
        
        document.getElementById('edit-staff-modal').classList.remove('hidden');
    }
    
    function closeEditModal() {
        document.getElementById('edit-staff-modal').classList.add('hidden');
    }
</script>

<?php include '../../includes/footer.php'; ?>