<?php
require_once '../../includes/functions.php';
requireRole('admin');

// Process form submission to add new test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_test'])) {
    $test_name = $_POST['test_name'];
    $price = $_POST['price'];
    
    $query = "INSERT INTO tests (test_name, price) VALUES ('$test_name', $price)";
    
    if ($conn->query($query) === TRUE) {
        $success = "Test added successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to update test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_test'])) {
    $test_id = $_POST['test_id'];
    $test_name = $_POST['test_name'];
    $price = $_POST['price'];
    
    $query = "UPDATE tests SET test_name = '$test_name', price = $price WHERE id = $test_id";
    
    if ($conn->query($query) === TRUE) {
        $success = "Test updated successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to delete test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_test'])) {
    $test_id = $_POST['test_id'];
    
    $query = "DELETE FROM tests WHERE id = $test_id";
    
    if ($conn->query($query) === TRUE) {
        $success = "Test deleted successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to add new room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $room_name = $_POST['room_name'];
    $price = $_POST['price'];
    
    $query = "INSERT INTO rooms (room_name, price) VALUES ('$room_name', $price)";
    
    if ($conn->query($query) === TRUE) {
        $success = "Room added successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to update room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    $room_id = $_POST['room_id'];
    $room_name = $_POST['room_name'];
    $price = $_POST['price'];
    
    $query = "UPDATE rooms SET room_name = '$room_name', price = $price WHERE id = $room_id";
    
    if ($conn->query($query) === TRUE) {
        $success = "Room updated successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to delete room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $room_id = $_POST['room_id'];
    
    $query = "DELETE FROM rooms WHERE id = $room_id";
    
    if ($conn->query($query) === TRUE) {
        $success = "Room deleted successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get all tests
$tests = getAllTests();

// Get all rooms
$rooms = getAllRooms();
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Manage Pricing</h1>
    <p class="text-gray-600">Manage lab tests and room pricing</p>
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Lab Tests</h2>
            <button onclick="showAddTestForm()" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Test
            </button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($test = $tests->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $test['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $test['test_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($test['price']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="editTest(<?php echo $test['id']; ?>, '<?php echo $test['test_name']; ?>', <?php echo $test['price']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="post" action="pricing.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this test?');">
                                    <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                    <button type="submit" name="delete_test" class="text-red-600 hover:text-red-900">
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
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Rooms</h2>
            <button onclick="showAddRoomForm()" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Room
            </button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $room['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $room['room_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($room['price']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $room['status'] === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($room['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="editRoom(<?php echo $room['id']; ?>, '<?php echo $room['room_name']; ?>', <?php echo $room['price']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="post" action="pricing.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this room?');">
                                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                    <button type="submit" name="delete_room" class="text-red-600 hover:text-red-900">
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

<!-- Add Test Modal -->
<div id="add-test-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Add New Test</h2>
        <form method="post" action="pricing.php">
            <div class="mb-4">
                <label for="test_name" class="block text-gray-700 font-medium mb-2">Test Name</label>
                <input type="text" id="test_name" name="test_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label for="price" class="block text-gray-700 font-medium mb-2">Price (PKR)</label>
                <input type="number" id="price" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeAddTestModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="submit" name="add_test" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Add Test
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Test Modal -->
<div id="edit-test-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Edit Test</h2>
        <form method="post" action="pricing.php">
            <input type="hidden" id="edit_test_id" name="test_id">
            
            <div class="mb-4">
                <label for="edit_test_name" class="block text-gray-700 font-medium mb-2">Test Name</label>
                <input type="text" id="edit_test_name" name="test_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label for="edit_price" class="block text-gray-700 font-medium mb-2">Price (PKR)</label>
                <input type="number" id="edit_price" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditTestModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="submit" name="update_test" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Update Test
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Room Modal -->
<div id="add-room-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Add New Room</h2>
        <form method="post" action="pricing.php">
            <div class="mb-4">
                <label for="room_name" class="block text-gray-700 font-medium mb-2">Room Name</label>
                <input type="text" id="room_name" name="room_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label for="price" class="block text-gray-700 font-medium mb-2">Price (PKR)</label>
                <input type="number" id="price" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeAddRoomModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="submit" name="add_room" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Add Room
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Room Modal -->
<div id="edit-room-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Edit Room</h2>
        <form method="post" action="pricing.php">
            <input type="hidden" id="edit_room_id" name="room_id">
            
            <div class="mb-4">
                <label for="edit_room_name" class="block text-gray-700 font-medium mb-2">Room Name</label>
                <input type="text" id="edit_room_name" name="room_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label for="edit_price" class="block text-gray-700 font-medium mb-2">Price (PKR)</label>
                <input type="number" id="edit_price" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditRoomModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="submit" name="update_room" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Update Room
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function showAddTestForm() {
        document.getElementById('add-test-modal').classList.remove('hidden');
    }
    
    function closeAddTestModal() {
        document.getElementById('add-test-modal').classList.add('hidden');
    }
    
    function editTest(id, test_name, price) {
        document.getElementById('edit_test_id').value = id;
        document.getElementById('edit_test_name').value = test_name;
        document.getElementById('edit_price').value = price;
        
        document.getElementById('edit-test-modal').classList.remove('hidden');
    }
    
    function closeEditTestModal() {
        document.getElementById('edit-test-modal').classList.add('hidden');
    }
    
    function showAddRoomForm() {
        document.getElementById('add-room-modal').classList.remove('hidden');
    }
    
    function closeAddRoomModal() {
        document.getElementById('add-room-modal').classList.add('hidden');
    }
    
    function editRoom(id, room_name, price) {
        document.getElementById('edit_room_id').value = id;
        document.getElementById('edit_room_name').value = room_name;
        document.getElementById('edit_price').value = price;
        
        document.getElementById('edit-room-modal').classList.remove('hidden');
    }
    
    function closeEditRoomModal() {
        document.getElementById('edit-room-modal').classList.add('hidden');
    }
</script>

<?php include '../../includes/footer.php'; ?>