<?php
require_once '../../includes/functions.php';
requireRole('reception');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $room_id = $_POST['room_id'];
    $days = $_POST['days'];
    
    // Get room details
    $room = getRoom($room_id);
    $room_name = $room['room_name'];
    $price_per_day = $room['price'];
    $total_amount = $price_per_day * $days;
    
    // Update room status to occupied
    $update_query = "UPDATE rooms SET status = 'occupied' WHERE id = $room_id";
    $conn->query($update_query);
    
    // Create billing record
    $billing_query = "INSERT INTO billing (patient_id, service_name, amount, paid_amount, status) 
                      VALUES ($patient_id, '$room_name ($days days)', $total_amount, 0, 'unpaid')";
    
    if ($conn->query($billing_query) === TRUE) {
        $success = "Room allotted successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get all patients for dropdown
$patients = getAllPatients();

// Get available rooms
$available_rooms = getAvailableRooms();

// Get occupied rooms
$occupied_rooms = getOccupiedRooms();
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Room Allotment</h1>
    <p class="text-gray-600">Allot rooms to patients</p>
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
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Allot Room</h2>
        <form method="post" action="room_allotment.php">
            <div class="mb-4">
                <label for="patient_id" class="block text-gray-700 font-medium mb-2">Select Patient</label>
                <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select a patient</option>
                    <?php while ($patient = $patients->fetch_assoc()): ?>
                        <option value="<?php echo $patient['id']; ?>">
                            <?php echo $patient['name'] . ' (ID: ' . $patient['id'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="room_id" class="block text-gray-700 font-medium mb-2">Select Room</label>
                <select id="room_id" name="room_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select a room</option>
                    <?php while ($room = $available_rooms->fetch_assoc()): ?>
                        <option value="<?php echo $room['id']; ?>">
                            <?php echo $room['room_name'] . ' - ' . formatCurrency($room['price']) . '/day'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-6">
                <label for="days" class="block text-gray-700 font-medium mb-2">Number of Days</label>
                <input type="number" id="days" name="days" min="1" value="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    Allot Room
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Room Status</h2>
        
        <div class="mb-6">
            <h3 class="font-medium text-gray-700 mb-2">Available Rooms</h3>
            <div class="space-y-2">
                <?php 
                $available_rooms->data_seek(0);
                if ($available_rooms->num_rows > 0) {
                    while ($room = $available_rooms->fetch_assoc()): ?>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-md">
                            <div>
                                <div class="font-medium"><?php echo $room['room_name']; ?></div>
                                <div class="text-sm text-gray-600"><?php echo formatCurrency($room['price']); ?>/day</div>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Available</span>
                        </div>
                    <?php endwhile;
                } else {
                    echo "<p class='text-gray-500'>No available rooms</p>";
                }
                ?>
            </div>
        </div>
        
        <div>
            <h3 class="font-medium text-gray-700 mb-2">Occupied Rooms</h3>
            <div class="space-y-2">
                <?php 
                if ($occupied_rooms->num_rows > 0) {
                    while ($room = $occupied_rooms->fetch_assoc()): ?>
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded-md">
                            <div>
                                <div class="font-medium"><?php echo $room['room_name']; ?></div>
                                <div class="text-sm text-gray-600"><?php echo formatCurrency($room['price']); ?>/day</div>
                            </div>
                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Occupied</span>
                        </div>
                    <?php endwhile;
                } else {
                    echo "<p class='text-gray-500'>No occupied rooms</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>