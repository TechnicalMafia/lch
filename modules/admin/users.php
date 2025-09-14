<?php
require_once '../../includes/functions.php';
requireRole('admin');

// Process form submission to add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $staff_id = !empty($_POST['staff_id']) ? $_POST['staff_id'] : null;
    
    $query = "INSERT INTO users (username, password, role, staff_id) VALUES ('$username', '$password', '$role', " . ($staff_id ? "'$staff_id'" : "NULL") . ")";
    if ($conn->query($query) === TRUE) {
        $success = "User added successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $staff_id = !empty($_POST['edit_staff_id']) ? $_POST['edit_staff_id'] : null;
    
    $query = "UPDATE users SET username = '$username', password = '$password', role = '$role', staff_id = " . ($staff_id ? "'$staff_id'" : "NULL") . " WHERE id = $user_id";
    if ($conn->query($query) === TRUE) {
        $success = "User updated successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $query = "DELETE FROM users WHERE id = $user_id";
    if ($conn->query($query) === TRUE) {
        $success = "User deleted successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get all users
$users = getAllUsers();

// Get staff for dropdown
$staff = getAllStaff();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Manage Users</h1>
        
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
        
        <!-- Add New User Form -->
        <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
            <div class="bg-indigo-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>
                    Add New User
                </h2>
            </div>
            <div class="p-6">
                <form method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Username</label>
                            <input type="text" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Password</label>
                            <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Role</label>
                            <select name="role" id="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="admin">Admin</option>
                                <option value="reception">Reception</option>
                                <option value="doctor">Doctor</option>
                                <option value="lab">Lab</option>
                            </select>
                        </div>
                        <div id="staff-dropdown" style="display: none;">
                            <label class="block text-gray-700 mb-2">Staff Name</label>
                            <select name="staff_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Staff</option>
                                <?php while ($staff_member = $staff->fetch_assoc()): ?>
                                    <option value="<?php echo $staff_member['id']; ?>"><?php echo $staff_member['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="add_user" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-plus mr-2"></i>Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- System Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-indigo-600 text-white px-6 py-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-users-cog mr-2"></i>
                    System Users
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $user['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $user['username']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $user['password']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                        <?php 
                                        if ($user['role'] === 'admin') {
                                            echo 'bg-purple-100 text-purple-800';
                                        } elseif ($user['role'] === 'reception') {
                                            echo 'bg-blue-100 text-blue-800';
                                        } elseif ($user['role'] === 'doctor') {
                                            echo 'bg-green-100 text-green-800';
                                        } else {
                                            echo 'bg-yellow-100 text-yellow-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    if ($user['staff_id']) {
                                        $staff_member = getStaff($user['staff_id']);
                                        echo $staff_member ? $staff_member['name'] : 'N/A';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="bg-yellow-500 text-white px-3 py-1 rounded-md hover:bg-yellow-600 mr-2 text-sm" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>', '<?php echo $user['password']; ?>', '<?php echo $user['role']; ?>', <?php echo $user['staff_id'] ?: 'null'; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600 text-sm" onclick="confirmDelete(<?php echo $user['id']; ?>)">
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
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4">Edit User</h2>
            <form method="post">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Username</label>
                    <input type="text" id="edit_username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Password</label>
                    <input type="password" id="edit_password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Role</label>
                    <select id="edit_role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="admin">Admin</option>
                        <option value="reception">Reception</option>
                        <option value="doctor">Doctor</option>
                        <option value="lab">Lab</option>
                    </select>
                </div>
                <div id="edit_staff_dropdown" class="mb-4" style="display: none;">
                    <label class="block text-gray-700 mb-2">Staff Name</label>
                    <select id="edit_staff_id" name="edit_staff_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Staff</option>
                        <?php 
                        $staff->data_seek(0); // Reset pointer
                        while ($staff_member = $staff->fetch_assoc()): ?>
                            <option value="<?php echo $staff_member['id']; ?>"><?php echo $staff_member['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_user" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-save mr-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4">Confirm Delete</h2>
            <p>Are you sure you want to delete this user?</p>
            <div class="flex justify-end mt-4">
                <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 mr-2" onclick="closeDeleteModal()">Cancel</button>
                <form method="post" style="display: inline;">
                    <input type="hidden" id="delete_user_id" name="user_id">
                    <button type="submit" name="delete_user" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Show/hide staff dropdown based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const staffDropdown = document.getElementById('staff-dropdown');
            if (this.value === 'doctor' || this.value === 'lab') {
                staffDropdown.style.display = 'block';
            } else {
                staffDropdown.style.display = 'none';
            }
        });
        
        // Edit user function
        function editUser(id, username, password, role, staffId) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_password').value = password;
            document.getElementById('edit_role').value = role;
            
            // Show/hide staff dropdown based on role
            const editStaffDropdown = document.getElementById('edit_staff_dropdown');
            if (role === 'doctor' || role === 'lab') {
                editStaffDropdown.style.display = 'block';
                if (staffId) {
                    document.getElementById('edit_staff_id').value = staffId;
                }
            } else {
                editStaffDropdown.style.display = 'none';
            }
            
            document.getElementById('editUserModal').classList.remove('hidden');
        }
        
        // Close edit modal
        function closeEditModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }
        
        // Confirm delete
        function confirmDelete(id) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        // Handle edit role change
        document.getElementById('edit_role').addEventListener('change', function() {
            const editStaffDropdown = document.getElementById('edit_staff_dropdown');
            if (this.value === 'doctor' || this.value === 'lab') {
                editStaffDropdown.style.display = 'block';
            } else {
                editStaffDropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>