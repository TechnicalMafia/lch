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

// Process form submission to add new custom service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $service_name = $_POST['service_name'];
    $service_type = $_POST['service_type'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    if (addCustomService($service_name, $service_type, $price, $description)) {
        $success = "Custom service added successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to update custom service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $service_id = $_POST['service_id'];
    $service_name = $_POST['service_name'];
    $service_type = $_POST['service_type'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    
    if (updateCustomService($service_id, $service_name, $service_type, $price, $description, $status)) {
        $success = "Custom service updated successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to delete custom service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $service_id = $_POST['service_id'];
    
    if (deleteCustomService($service_id)) {
        $success = "Custom service deleted successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Process form submission to toggle service status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $service_id = $_POST['service_id'];
    
    if (toggleCustomServiceStatus($service_id)) {
        $success = "Service status updated successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get all tests
$tests = getAllTests();

// Get all rooms
$rooms = getAllRooms();

// Get all custom services
$custom_services = getAllCustomServices();
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Manage Pricing</h1>
    <p class="text-gray-600">Manage lab tests, room pricing, and custom services</p>
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

<!-- Tab Navigation -->
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-8">
        <button onclick="showTab('lab-tests')" id="lab-tests-tab" class="tab-btn py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
            Lab Tests
        </button>
        <button onclick="showTab('rooms')" id="rooms-tab" class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Rooms
        </button>
        <button onclick="showTab('custom-services')" id="custom-services-tab" class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Custom Services
        </button>
    </nav>
</div>

<!-- Lab Tests Tab -->
<div id="lab-tests-content" class="tab-content">
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
</div>

<!-- Rooms Tab -->
<div id="rooms-content" class="tab-content hidden">
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

<!-- Custom Services Tab -->
<div id="custom-services-content" class="tab-content hidden">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Custom Services & Doctor Fees</h2>
            <button onclick="showAddServiceForm()" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Service
            </button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($service = $custom_services->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $service['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo $service['service_name']; ?></div>
                                    <?php if (!empty($service['description'])): ?>
                                        <div class="text-sm text-gray-500"><?php echo substr($service['description'], 0, 50) . (strlen($service['description']) > 50 ? '...' : ''); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php 
                                        if ($service['service_type'] === 'consultation') echo 'bg-blue-100 text-blue-800';
                                        elseif ($service['service_type'] === 'checkup') echo 'bg-green-100 text-green-800';
                                        elseif ($service['service_type'] === 'procedure') echo 'bg-purple-100 text-purple-800';
                                        elseif ($service['service_type'] === 'therapy') echo 'bg-yellow-100 text-yellow-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo ucfirst($service['service_type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($service['price']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $service['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($service['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="editService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['service_name'], ENT_QUOTES); ?>', '<?php echo $service['service_type']; ?>', <?php echo $service['price']; ?>, '<?php echo htmlspecialchars($service['description'], ENT_QUOTES); ?>', '<?php echo $service['status']; ?>')" class="text-blue-600 hover:text-blue-900 mr-2">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="post" action="pricing.php" class="inline mr-2">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <button type="submit" name="toggle_status" class="text-yellow-600 hover:text-yellow-900">
                                        <i class="fas fa-toggle-<?php echo $service['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                    </button>
                                </form>
                                <form method="post" action="pricing.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <button type="submit" name="delete_service" class="text-red-600 hover:text-red-900">
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
                <label for="room_price" class="block text-gray-700 font-medium mb-2">Price (PKR)</label>
                <input type="number" id="room_price" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
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
                <label for="edit_room_price" class="block text-gray-700 font-medium mb-2">Price (PKR)</label>
                <input type="number" id="edit_room_price" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
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

<!-- Add Custom Service Modal -->
<div id="add-service-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Add New Service</h2>
        <form method="post" action="pricing.php">
            <div class="mb-4">
                <label for="service_name" class="block text-gray-700 font-medium mb-2">Service Name</label>
                <input type="text" id="service_name" name="service_name" placeholder="e.g. General Consultation" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="service_type" class="block text-gray-700 font-medium mb-2">Service Type</label>
                <select id="service_type" name="service_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="consultation">Consultation</option>
                    <option value="checkup">Checkup</option>
                    <option value="procedure">Procedure</option>
                    <option value="therapy">Therapy</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="service_price" class="block text-gray-700 font-medium mb-2">Price (PKR)</label>
                <input type="number" id="service_price" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label for="service_description" class="block text-gray-700 font-medium mb-2">Description (Optional)</label>
                <textarea id="service_description" name="description" rows="3" placeholder="Brief description of the service" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeAddServiceModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="submit" name="add_service" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Add Service
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Custom Service Modal -->
<div id="edit-service-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Edit Service</h2>
        <form method="post" action="pricing.php">
            <input type="hidden" id="edit_service_id" name="service_id">
            
            <div class="mb-4">
                <label for="edit_service_name" class="block text-gray-700 font-medium mb-2">Service Name</label>
                <input type="text" id="edit_service_name" name="service_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="edit_service_type" class="block text-gray-700 font-medium mb-2">Service Type</label>
                <select id="edit_service_type" name="service_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="consultation">Consultation</option>
                    <option value="checkup">Checkup</option>
                    <option value="procedure">Procedure</option>
                    <option value="therapy">Therapy</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="edit_service_price" class="block text-gray-700 font-medium mb-2">Price (PKR)</label>
                <input type="number" id="edit_service_price" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="edit_service_description" class="block text-gray-700 font-medium mb-2">Description</label>
                <textarea id="edit_service_description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="mb-6">
                <label for="edit_service_status" class="block text-gray-700 font-medium mb-2">Status</label>
                <select id="edit_service_status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditServiceModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="submit" name="update_service" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Update Service
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab functionality
    function showTab(tabName) {
        // Hide all tab contents
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.add('hidden'));
        
        // Remove active styling from all tabs
        const tabs = document.querySelectorAll('.tab-btn');
        tabs.forEach(tab => {
            tab.classList.remove('border-blue-500', 'text-blue-600');
            tab.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show selected tab content
        document.getElementById(tabName + '-content').classList.remove('hidden');
        
        // Add active styling to selected tab
        const activeTab = document.getElementById(tabName + '-tab');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
        activeTab.classList.add('border-blue-500', 'text-blue-600');
    }

    // Test functions
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
    
    // Room functions
    function showAddRoomForm() {
        document.getElementById('add-room-modal').classList.remove('hidden');
    }
    
    function closeAddRoomModal() {
        document.getElementById('add-room-modal').classList.add('hidden');
    }
    
    function editRoom(id, room_name, price) {
        document.getElementById('edit_room_id').value = id;
        document.getElementById('edit_room_name').value = room_name;
        document.getElementById('edit_room_price').value = price;
        
        document.getElementById('edit-room-modal').classList.remove('hidden');
    }
    
    function closeEditRoomModal() {
        document.getElementById('edit-room-modal').classList.add('hidden');
    }
    
    // Custom Service functions
    function showAddServiceForm() {
        document.getElementById('add-service-modal').classList.remove('hidden');
    }
    
    function closeAddServiceModal() {
        document.getElementById('add-service-modal').classList.add('hidden');
    }
    
    function editService(id, service_name, service_type, price, description, status) {
        document.getElementById('edit_service_id').value = id;
        document.getElementById('edit_service_name').value = service_name;
        document.getElementById('edit_service_type').value = service_type;
        document.getElementById('edit_service_price').value = price;
        document.getElementById('edit_service_description').value = description;
        document.getElementById('edit_service_status').value = status;
        
        document.getElementById('edit-service-modal').classList.remove('hidden');
    }
    
    function closeEditServiceModal() {
        document.getElementById('edit-service-modal').classList.add('hidden');
    }
</script>

<?php include '../../includes/footer.php'; ?>