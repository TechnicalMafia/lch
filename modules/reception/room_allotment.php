<?php
require_once '../../includes/functions.php';
requireRole('reception');

// First, let's create the room_assignments table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS room_assignments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    room_id INT(11) NOT NULL,
    patient_id INT(11) NOT NULL,
    days INT(11) NOT NULL,
    assigned_date DATE NOT NULL,
    billing_id INT(11) DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (billing_id) REFERENCES billing(id)
)";
$conn->query($create_table_query);

// Process form submission for room allotment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allot_room'])) {
    $patient_id = $_POST['patient_id'];
    $room_id = $_POST['room_id'];
    $days = $_POST['days'];
    
    // Check if patient already has a room assigned
    $check_assignment_query = "SELECT ra.*, r.room_name, p.name as patient_name 
                             FROM room_assignments ra 
                             JOIN rooms r ON ra.room_id = r.id 
                             JOIN patients p ON ra.patient_id = p.id 
                             WHERE ra.patient_id = $patient_id";
    $check_result = $conn->query($check_assignment_query);
    
    if ($check_result->num_rows > 0) {
        $existing_assignment = $check_result->fetch_assoc();
        $error = "Patient already assigned to " . $existing_assignment['room_name'] . ". Unassign first to assign a new room.";
    } else {
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
            $billing_id = $conn->insert_id;
            
            // Create room assignment record
            $assignment_query = "INSERT INTO room_assignments (room_id, patient_id, days, assigned_date, billing_id) 
                                VALUES ($room_id, $patient_id, $days, CURDATE(), $billing_id)";
            
            if ($conn->query($assignment_query) === TRUE) {
                $success = "Room allotted successfully";
            } else {
                $error = "Error creating room assignment: " . $conn->error;
            }
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Process form submission for unassigning room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unassign_room'])) {
    $assignment_id = $_POST['assignment_id'];
    $room_id = $_POST['room_id'];
    $billing_id = $_POST['billing_id'];
    
    // Update room status to available
    $update_room_query = "UPDATE rooms SET status = 'available' WHERE id = $room_id";
    $conn->query($update_room_query);
    
    // Update billing record to refunded
    $update_billing_query = "UPDATE billing SET status = 'refund' WHERE id = $billing_id";
    $conn->query($update_billing_query);
    
    // Delete room assignment
    $delete_assignment_query = "DELETE FROM room_assignments WHERE id = $assignment_id";
    
    if ($conn->query($delete_assignment_query) === TRUE) {
        $success = "Room unassigned successfully";
    } else {
        $error = "Error unassigning room: " . $conn->error;
    }
}

// Get all patients for dropdown
$patients = getAllPatients();
// Get available rooms
$available_rooms = getAvailableRooms();
// Get occupied rooms with assignment details
$occupied_rooms_query = "SELECT ra.id as assignment_id, ra.days, ra.assigned_date, ra.billing_id,
                          r.id as room_id, r.room_name, r.price,
                          p.id as patient_id, p.name as patient_name
                          FROM room_assignments ra 
                          JOIN rooms r ON ra.room_id = r.id 
                          JOIN patients p ON ra.patient_id = p.id 
                          ORDER BY ra.assigned_date DESC";
$occupied_rooms_result = $conn->query($occupied_rooms_query);
?>
<?php include '../../includes/header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Room Allotment</h1>
    <p class="text-gray-600">Allot rooms to patients</p>
</div>

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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Allot Room</h2>
        <form method="post" action="room_allotment.php">
            <input type="hidden" name="allot_room" value="1">
            
            <div class="mb-4">
                <label for="patient_id" class="block text-gray-700 font-medium mb-2">Select Patient</label>
                <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select a patient</option>
                    <?php 
                    $patients->data_seek(0);
                    while ($patient = $patients->fetch_assoc()): ?>
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
                    <?php 
                    $available_rooms->data_seek(0);
                    while ($room = $available_rooms->fetch_assoc()): ?>
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
                    <i class="fas fa-bed mr-2"></i>Allot Room
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
                if ($occupied_rooms_result->num_rows > 0) {
                    while ($room = $occupied_rooms_result->fetch_assoc()): ?>
                        <div class="p-3 bg-red-50 rounded-md">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-medium"><?php echo $room['room_name']; ?></div>
                                    <div class="text-sm text-gray-600"><?php echo formatCurrency($room['price']); ?>/day</div>
                                </div>
                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Occupied</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                <div>
                                    <span class="text-gray-600">Patient:</span>
                                    <span class="font-medium"><?php echo $room['patient_name']; ?> (ID: <?php echo $room['patient_id']; ?>)</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Days:</span>
                                    <span class="font-medium"><?php echo $room['days']; ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Assigned:</span>
                                    <span class="font-medium"><?php echo date('d M Y', strtotime($room['assigned_date'])); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <span class="font-medium">Active</span>
                                </div>
                            </div>
                            
                            <form method="post" action="room_allotment.php" onsubmit="return confirm('Are you sure you want to unassign this room? This will also mark the billing as refunded.');">
                                <input type="hidden" name="unassign_room" value="1">
                                <input type="hidden" name="assignment_id" value="<?php echo $room['assignment_id']; ?>">
                                <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                <input type="hidden" name="billing_id" value="<?php echo $room['billing_id']; ?>">
                                <button type="submit" class="w-full bg-yellow-600 text-white py-1 px-3 rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition text-sm">
                                    <i class="fas fa-door-open mr-1"></i> Unassign Room
                                </button>
                            </form>
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

<script>
// Add JavaScript to prevent form submission if patient already has a room
document.querySelector('form').addEventListener('submit', function(e) {
    const patientId = document.getElementById('patient_id').value;
    
    if (patientId) {
        // Check if patient already has a room assigned via AJAX
        fetch(`check_patient_room.php?patient_id=${patientId}`)
            .then(response => response.json())
            .then(data => {
                if (data.hasRoom) {
                    e.preventDefault();
                    alert(`Patient already assigned to ${data.roomName}. Unassign first to assign a new room.`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>