<?php
require_once '../../includes/functions.php';
requireRole('admin');

// Process form submission to add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
    
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
    
    $query = "UPDATE users SET username = '$username', password = '$password', role = '$role' WHERE id = $user_id";
    
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
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
    <p class="text-gray-600">Add, edit, and delete system users</p>
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
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Add New User</h2>
            <form method="post" action="users.php">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                    <input type="text" id="username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="text" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-6">
                    <label for="role" class="block text-gray-700 font-medium mb-2">Role</label>
                    <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="admin">Admin</option>
                        <option value="reception">Reception</option>
                        <option value="doctor">Doctor</option>
                        <option value="lab">Lab</option>
                    </select>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="add_user" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">System Users</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['username']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['password']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                        if ($user['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                                        elseif ($user['role'] === 'reception') echo 'bg-blue-100 text-blue-800';
                                        elseif ($user['role'] === 'doctor') echo 'bg-green-100 text-green-800';
                                        else echo 'bg-yellow-100 text-yellow-800';
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>', '<?php echo $user['password']; ?>', '<?php echo $user['role']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="post" action="users.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="text-red-600 hover:text-red-900">
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

<!-- Edit User Modal -->
<div id="edit-user-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Edit User</h2>
        <form method="post" action="users.php">
            <input type="hidden" id="edit_user_id" name="user_id">
            
            <div class="mb-4">
                <label for="edit_username" class="block text-gray-700 font-medium mb-2">Username</label>
                <input type="text" id="edit_username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="edit_password" class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="text" id="edit_password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label for="edit_role" class="block text-gray-700 font-medium mb-2">Role</label>
                <select id="edit_role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="admin">Admin</option>
                    <option value="reception">Reception</option>
                    <option value="doctor">Doctor</option>
                    <option value="lab">Lab</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="submit" name="update_user" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function editUser(id, username, password, role) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_password').value = password;
        document.getElementById('edit_role').value = role;
        
        document.getElementById('edit-user-modal').classList.remove('hidden');
    }
    
    function closeEditModal() {
        document.getElementById('edit-user-modal').classList.add('hidden');
    }
</script>

<?php include '../../includes/footer.php'; ?>