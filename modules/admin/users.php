<?php
require_once '../../includes/functions.php';
requireRole('admin');

$success = '';
$error = '';

// Process form submission to add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $staff_id = !empty($_POST['staff_id']) ? $_POST['staff_id'] : null;
    
    // Use the new validation function
    $result = createUser($username, $password, $role, $staff_id);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Process form submission to update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $staff_id = !empty($_POST['edit_staff_id']) ? $_POST['edit_staff_id'] : null;
    
    // Use the new validation function
    $result = updateUser($user_id, $username, $password, $role, $staff_id);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Process form submission to delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    // Check if user exists and is not the current user
    if ($user_id === $_SESSION['user_id']) {
        $error = "You cannot delete your own account";
    } else {
        $query = "DELETE FROM users WHERE id = $user_id";
        if ($conn->query($query) === TRUE) {
            $success = "User deleted successfully";
        } else {
            $error = "Error deleting user: " . $conn->error;
        }
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
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
            <p class="text-gray-600">Create and manage user accounts for the hospital system</p>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
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
                <form method="post" id="addUserForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block text-gray-700 font-medium mb-2">Username <span class="text-red-500">*</span></label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                   required
                                   minlength="3"
                                   maxlength="50"
                                   pattern="[a-zA-Z0-9_-]+"
                                   title="Username can only contain letters, numbers, underscore, and hyphen"
                                   onblur="checkUsername(this.value)">
                            <div id="username-feedback" class="mt-1 text-sm hidden"></div>
                            <div class="mt-1 text-xs text-gray-500">
                                Must be 3-50 characters long. Only letters, numbers, underscore, and hyphen allowed.
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-gray-700 font-medium mb-2">Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                       required
                                       minlength="6">
                                <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye" id="password-toggle"></i>
                                </button>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                Must be at least 6 characters long
                            </div>
                        </div>
                        
                        <div>
                            <label for="role" class="block text-gray-700 font-medium mb-2">Role <span class="text-red-500">*</span></label>
                            <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="reception">Reception</option>
                                <option value="doctor">Doctor</option>
                                <option value="lab">Lab</option>
                            </select>
                        </div>
                        
                        <div id="staff-dropdown" style="display: none;">
                            <label for="staff_id" class="block text-gray-700 font-medium mb-2">Staff Member</label>
                            <select id="staff_id" name="staff_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Staff Member</option>
                                <?php while ($staff_member = $staff->fetch_assoc()): ?>
                                    <option value="<?php echo $staff_member['id']; ?>"><?php echo $staff_member['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="mt-1 text-xs text-gray-500">
                                Link this user account to a staff member (optional for Doctor/Lab roles)
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span class="text-red-500">*</span> Required fields
                        </div>
                        <button type="submit" name="add_user" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo $user['id']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-indigo-600">
                                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $user['username']; ?></div>
                                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                                <div class="text-xs text-green-600">(Current User)</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                        if ($user['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                                        elseif ($user['role'] === 'reception') echo 'bg-blue-100 text-blue-800';
                                        elseif ($user['role'] === 'doctor') echo 'bg-green-100 text-green-800';
                                        else echo 'bg-yellow-100 text-yellow-800';
                                    ?>">
                                        <i class="fas fa-<?php 
                                            if ($user['role'] === 'admin') echo 'crown';
                                            elseif ($user['role'] === 'reception') echo 'desk';
                                            elseif ($user['role'] === 'doctor') echo 'user-md';
                                            else echo 'flask';
                                        ?> mr-1"></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    if ($user['staff_id']) {
                                        $staff_member = getStaff($user['staff_id']);
                                        echo $staff_member ? $staff_member['name'] : '<span class="text-red-500">Staff not found</span>';
                                    } else {
                                        echo '<span class="text-gray-500">Not linked</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>', '<?php echo $user['password']; ?>', '<?php echo $user['role']; ?>', <?php echo $user['staff_id'] ?: 'null'; ?>)" 
                                            class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 mr-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')" 
                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-xs">(Cannot delete own account)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4 text-indigo-600">
                <i class="fas fa-user-edit mr-2"></i>Edit User
            </h2>
            <form method="post" id="editUserForm">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="mb-4">
                    <label for="edit_username" class="block text-gray-700 font-medium mb-2">Username <span class="text-red-500">*</span></label>
                    <input type="text" 
                           id="edit_username" 
                           name="username" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                           required
                           minlength="3"
                           maxlength="50"
                           pattern="[a-zA-Z0-9_-]+"
                           onblur="checkUsername(this.value, document.getElementById('edit_user_id').value)">
                    <div id="edit-username-feedback" class="mt-1 text-sm hidden"></div>
                </div>
                <div class="mb-4">
                    <label for="edit_password" class="block text-gray-700 font-medium mb-2">Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" 
                               id="edit_password" 
                               name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                               required
                               minlength="6">
                        <button type="button" onclick="togglePassword('edit_password')" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="edit_password-toggle"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="edit_role" class="block text-gray-700 font-medium mb-2">Role <span class="text-red-500">*</span></label>
                    <select id="edit_role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="admin">Admin</option>
                        <option value="reception">Reception</option>
                        <option value="doctor">Doctor</option>
                        <option value="lab">Lab</option>
                    </select>
                </div>
                <div id="edit_staff_dropdown" class="mb-4" style="display: none;">
                    <label for="edit_staff_id" class="block text-gray-700 font-medium mb-2">Staff Member</label>
                    <select id="edit_staff_id" name="edit_staff_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Staff Member</option>
                        <?php 
                        $staff->data_seek(0); // Reset pointer
                        while ($staff_member = $staff->fetch_assoc()): ?>
                            <option value="<?php echo $staff_member['id']; ?>"><?php echo $staff_member['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" 
                            class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" name="update_user" 
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        <i class="fas fa-save mr-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4 text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Delete
            </h2>
            <p class="mb-4">Are you sure you want to delete the user "<span id="delete-username" class="font-semibold"></span>"? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Cancel
                </button>
                <form method="post" style="display: inline;">
                    <input type="hidden" id="delete_user_id" name="user_id">
                    <button type="submit" name="delete_user" 
                            class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        <i class="fas fa-trash mr-2"></i>Delete User
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
        
        // Handle edit role change
        document.getElementById('edit_role').addEventListener('change', function() {
            const editStaffDropdown = document.getElementById('edit_staff_dropdown');
            if (this.value === 'doctor' || this.value === 'lab') {
                editStaffDropdown.style.display = 'block';
            } else {
                editStaffDropdown.style.display = 'none';
            }
        });
        
        // Username availability checker
        let usernameCheckTimeout;
        function checkUsername(username, excludeId = null) {
            clearTimeout(usernameCheckTimeout);
            
            if (username.length < 3) return;
            
            const feedbackId = excludeId ? 'edit-username-feedback' : 'username-feedback';
            const feedback = document.getElementById(feedbackId);
            
            usernameCheckTimeout = setTimeout(() => {
                // Simulate AJAX call - In real implementation, you'd make an actual AJAX request
                // For now, we'll just do basic validation
                if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                    feedback.className = 'mt-1 text-sm text-red-600';
                    feedback.textContent = 'Invalid characters in username';
                    feedback.classList.remove('hidden');
                } else {
                    feedback.className = 'mt-1 text-sm text-green-600';
                    feedback.textContent = 'Username format is valid';
                    feedback.classList.remove('hidden');
                }
            }, 500);
        }
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(inputId + '-toggle');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }
        
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
            document.getElementById('edit-username-feedback').classList.add('hidden');
        }
        
        // Confirm delete
        function confirmDelete(id, username) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('delete-username').textContent = username;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        // Form validation
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                e.preventDefault();
                alert('Username can only contain letters, numbers, underscore, and hyphen');
                return false;
            }
        });
        
        // Similar validation for edit form
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const username = document.getElementById('edit_username').value;
            const password = document.getElementById('edit_password').value;
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                e.preventDefault();
                alert('Username can only contain letters, numbers, underscore, and hyphen');
                return false;
            }
        });
    </script>
</body>
</html>