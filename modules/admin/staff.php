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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Manage Staff</h1>
        
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
        
        <!-- Add New Staff Form -->
        <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
            <div class="bg-indigo-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>
                    Add New Staff
                </h2>
            </div>
            <div class="p-6">
                <form method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Phone</label>
                            <input type="text" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">NIC</label>
                            <input type="text" name="nic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Salary (PKR)</label>
                            <input type="number" name="salary" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Duty Hours</label>
                            <input type="text" name="duty_hours" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Joining Date</label>
                            <input type="date" name="joining_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-gray-700 mb-2">Address</label>
                        <textarea name="address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="3" required></textarea>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="add_staff" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-plus mr-2"></i>Add Staff
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Hospital Staff Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-indigo-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-users mr-2"></i>
                    Hospital Staff
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Joining Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($staff_member = $staff->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition-colors" data-staff-id="<?php echo $staff_member['id']; ?>"
                                data-name="<?php echo htmlspecialchars($staff_member['name']); ?>"
                                data-address="<?php echo htmlspecialchars($staff_member['address']); ?>"
                                data-phone="<?php echo htmlspecialchars($staff_member['phone']); ?>"
                                data-nic="<?php echo htmlspecialchars($staff_member['nic']); ?>"
                                data-salary="<?php echo htmlspecialchars($staff_member['salary']); ?>"
                                data-duty-hours="<?php echo htmlspecialchars($staff_member['duty_hours']); ?>"
                                data-joining-date="<?php echo htmlspecialchars($staff_member['joining_date']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $staff_member['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $staff_member['name']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $staff_member['phone']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatCurrency($staff_member['salary']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $staff_member['joining_date']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="bg-yellow-500 text-white px-3 py-1 rounded-md hover:bg-yellow-600 mr-2 text-sm" onclick="editStaff(<?php echo $staff_member['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600 text-sm" onclick="confirmDelete(<?php echo $staff_member['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Edit Staff Modal -->
    <div id="editStaffModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4">Edit Staff</h2>
            <form method="post">
                <input type="hidden" id="edit_staff_id" name="staff_id">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Full Name</label>
                    <input type="text" id="edit_name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Address</label>
                    <textarea id="edit_address" name="address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="3" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Phone</label>
                    <input type="text" id="edit_phone" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">NIC</label>
                    <input type="text" id="edit_nic" name="nic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Salary (PKR)</label>
                    <input type="number" id="edit_salary" name="salary" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Duty Hours</label>
                    <input type="text" id="edit_duty_hours" name="duty_hours" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Joining Date</label>
                    <input type="date" id="edit_joining_date" name="joining_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_staff" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-save mr-2"></i>Update Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4">Confirm Delete</h2>
            <p>Are you sure you want to delete this staff member?</p>
            <div class="flex justify-end mt-4">
                <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2" onclick="closeDeleteModal()">Cancel</button>
                <form method="post" style="display: inline;">
                    <input type="hidden" id="delete_staff_id" name="staff_id">
                    <button type="submit" name="delete_staff" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Edit staff function
        function editStaff(staff_id) {
            const row = document.querySelector(`tr[data-staff-id="${staff_id}"]`);
            
            document.getElementById('edit_staff_id').value = staff_id;
            document.getElementById('edit_name').value = row.getAttribute('data-name');
            document.getElementById('edit_address').value = row.getAttribute('data-address');
            document.getElementById('edit_phone').value = row.getAttribute('data-phone');
            document.getElementById('edit_nic').value = row.getAttribute('data-nic');
            document.getElementById('edit_salary').value = row.getAttribute('data-salary');
            document.getElementById('edit_duty_hours').value = row.getAttribute('data-duty-hours');
            document.getElementById('edit_joining_date').value = row.getAttribute('data-joining-date');
            
            document.getElementById('editStaffModal').classList.remove('hidden');
        }
        
        // Close edit modal
        function closeEditModal() {
            document.getElementById('editStaffModal').classList.add('hidden');
        }
        
        // Confirm delete
        function confirmDelete(staff_id) {
            document.getElementById('delete_staff_id').value = staff_id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>