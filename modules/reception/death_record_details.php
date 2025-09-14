<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get record ID from URL
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($record_id <= 0) {
    header('Location: /lch/modules/reception/view_death_records.php?error=invalid_id');
    exit;
}

// Get death record details
$record = getDeathRecord($record_id);

if (!$record) {
    header('Location: /lch/modules/reception/view_death_records.php?error=record_not_found');
    exit;
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Death Record Details</h1>
            <p class="text-gray-600">Certificate No: <?php echo $record['certificate_number']; ?></p>
        </div>
        <div class="space-x-3">
            <a href="death_certificates.php?id=<?php echo $record['id']; ?>" class="bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700">
                <i class="fas fa-certificate mr-2"></i> Print Certificate
            </a>
            <a href="view_death_records.php" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                <i class="fas fa-arrow-left mr-2"></i> Back to Records
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Deceased Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Deceased Information</h2>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">Name:</label>
                <p class="text-lg font-semibold text-red-600"><?php echo htmlspecialchars($record['deceased_name']); ?></p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Age at Death:</label>
                    <p class="font-medium text-lg"><?php echo $record['age_at_death']; ?> years</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">NIC Number:</label>
                    <p class="font-medium"><?php echo $record['deceased_nic'] ? htmlspecialchars($record['deceased_nic']) : 'Not available'; ?></p>
                </div>
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-600">Date & Time of Death:</label>
                <p class="font-medium"><?php echo date('d M Y', strtotime($record['date_of_death'])); ?> at <?php echo date('h:i A', strtotime($record['time_of_death'])); ?></p>
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-600">Place of Death:</label>
                <p class="font-medium"><?php echo htmlspecialchars($record['place_of_death']); ?></p>
            </div>
            
            <?php if ($record['patient_name']): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Patient Record:</label>
                <p class="font-medium text-blue-600"><?php echo htmlspecialchars($record['patient_name']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Family Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Family Information</h2>
        <div class="space-y-4">
            <div>
                <h3 class="text-md font-medium text-gray-700 mb-2">Parents</h3>
                <div class="bg-gray-50 p-3 rounded space-y-2">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Father's Name:</label>
                        <p class="font-medium"><?php echo htmlspecialchars($record['father_name']); ?></p>
                    </div>
                    <?php if ($record['mother_name']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Mother's Name:</label>
                        <p class="font-medium"><?php echo htmlspecialchars($record['mother_name']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($record['spouse_name']): ?>
            <div>
                <h3 class="text-md font-medium text-gray-700 mb-2">Spouse</h3>
                <div class="bg-blue-50 p-3 rounded">
                    <p class="font-medium"><?php echo htmlspecialchars($record['spouse_name']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div>
                <h3 class="text-md font-medium text-gray-700 mb-2">Next of Kin</h3>
                <div class="bg-green-50 p-3 rounded space-y-2">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Name:</label>
                        <p class="font-medium"><?php echo htmlspecialchars($record['next_of_kin']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Relation:</label>
                        <p class="font-medium"><?php echo htmlspecialchars($record['next_of_kin_relation']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Contact:</label>
                        <p class="font-medium"><?php echo htmlspecialchars($record['next_of_kin_contact']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Medical Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Medical Information</h2>
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Attending Doctor:</label>
                <p class="font-medium"><?php echo $record['doctor_name'] ? 'Dr. ' . htmlspecialchars($record['doctor_name']) : 'Not assigned'; ?></p>
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-600">Cause of Death:</label>
                <div class="bg-red-50 p-3 rounded mt-1">
                    <p class="text-sm"><?php echo nl2br(htmlspecialchars($record['cause_of_death'])); ?></p>
                </div>
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-600">Autopsy Information:</label>
                <div class="bg-yellow-50 p-3 rounded mt-1">
                    <div class="flex items-center mb-2">
                        <span class="text-sm font-medium">Autopsy Required:</span>
                        <span class="ml-2 px-2 py-1 rounded text-xs <?php echo $record['autopsy_required'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                            <?php echo $record['autopsy_required'] ? 'Yes' : 'No'; ?>
                        </span>
                    </div>
                    <?php if ($record['autopsy_required'] && $record['autopsy_notes']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Autopsy Notes:</label>
                        <p class="text-sm mt-1"><?php echo nl2br(htmlspecialchars($record['autopsy_notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-600">Certificate Information:</label>
                <div class="bg-gray-50 p-3 rounded mt-1">
                    <div class="text-sm space-y-1">
                        <div><strong>Certificate No:</strong> <?php echo $record['certificate_number']; ?></div>
                        <div><strong>Issued By:</strong> <?php echo htmlspecialchars($record['issued_by']); ?></div>
                        <div><strong>Issue Date:</strong> <?php echo date('d M Y, h:i A', strtotime($record['created_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Address Information -->
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Address Information</h2>
    <div class="bg-gray-50 p-4 rounded">
        <p><?php echo nl2br(htmlspecialchars($record['address'])); ?></p>
    </div>
</div>

<!-- Actions -->
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Actions</h2>
    <div class="flex space-x-4">
        <a href="death_certificates.php?id=<?php echo $record['id']; ?>" class="bg-red-600 text-white py-2 px-6 rounded hover:bg-red-700 transition">
            <i class="fas fa-certificate mr-2"></i> Generate Death Certificate
        </a>
        <a href="view_death_records.php" class="bg-blue-600 text-white py-2 px-6 rounded hover:bg-blue-700 transition">
            <i class="fas fa-list mr-2"></i> Back to All Records
        </a>
        <a href="birth_death_records.php" class="bg-gray-600 text-white py-2 px-6 rounded hover:bg-gray-700 transition">
            <i class="fas fa-home mr-2"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>