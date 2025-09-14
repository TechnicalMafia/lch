<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get record ID from URL
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($record_id <= 0) {
    header('Location: /lch/modules/reception/view_birth_records.php?error=invalid_id');
    exit;
}

// Get birth record details
$record = getBirthRecord($record_id);

if (!$record) {
    header('Location: /lch/modules/reception/view_birth_records.php?error=record_not_found');
    exit;
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Birth Record Details</h1>
            <p class="text-gray-600">Certificate No: <?php echo $record['certificate_number']; ?></p>
        </div>
        <div class="space-x-3">
            <a href="birth_certificates.php?id=<?php echo $record['id']; ?>" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">
                <i class="fas fa-certificate mr-2"></i> Print Certificate
            </a>
            <a href="view_birth_records.php" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                <i class="fas fa-arrow-left mr-2"></i> Back to Records
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Newborn Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Newborn Information</h2>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">Name:</label>
                <p class="text-lg font-semibold text-green-600"><?php echo htmlspecialchars($record['newborn_name']); ?></p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Gender:</label>
                    <p class="font-medium">
                        <span class="px-2 py-1 rounded-full text-xs <?php echo $record['gender'] === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                            <?php echo $record['gender']; ?>
                        </span>
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Delivery Type:</label>
                    <p class="font-medium"><?php echo $record['delivery_type']; ?></p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Weight:</label>
                    <p class="font-medium"><?php echo $record['weight'] ? $record['weight'] . ' kg' : 'Not recorded'; ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Length:</label>
                    <p class="font-medium"><?php echo $record['length'] ? $record['length'] . ' cm' : 'Not recorded'; ?></p>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Date & Time of Birth:</label>
                <p class="font-medium"><?php echo date('d M Y', strtotime($record['date_of_birth'])); ?> at <?php echo date('h:i A', strtotime($record['time_of_birth'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Parents Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Parents Information</h2>
        <div class="space-y-4">
            <div>
                <h3 class="text-md font-medium text-gray-700 mb-2">Father</h3>
                <div class="bg-blue-50 p-3 rounded">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Name:</label>
                        <p class="font-medium"><?php echo htmlspecialchars($record['father_name']); ?></p>
                    </div>
                    <div class="mt-2">
                        <label class="text-sm font-medium text-gray-600">NIC:</label>
                        <p class="font-medium"><?php echo htmlspecialchars($record['father_nic']); ?></p>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="text-md font-medium text-gray-700 mb-2">Mother</h3>
                <div class="bg-pink-50 p-3 rounded">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Name:</label>
                        <p class="font-medium"><?php echo htmlspecialchars($record['mother_name']); ?></p>
                    </div>
                    <div class="mt-2">
                        <label class="text-sm font-medium text-gray-600">NIC:</label>
                        <p class="font-medium"><?php echo $record['mother_nic'] ? htmlspecialchars($record['mother_nic']) : 'Not provided'; ?></p>
                    </div>
                    <?php if ($record['mother_patient_name']): ?>
                    <div class="mt-2">
                        <label class="text-sm font-medium text-gray-600">Patient Record:</label>
                        <p class="font-medium text-blue-600"><?php echo htmlspecialchars($record['mother_patient_name']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Medical & Additional Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Medical Information</h2>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-600">Attending Doctor:</label>
                <p class="font-medium"><?php echo $record['doctor_name'] ? 'Dr. ' . htmlspecialchars($record['doctor_name']) : 'Not assigned'; ?></p>
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-600">Birth Complications:</label>
                <div class="bg-gray-50 p-3 rounded mt-1">
                    <p class="text-sm"><?php echo $record['birth_complications'] ? nl2br(htmlspecialchars($record['birth_complications'])) : 'No complications reported'; ?></p>
                </div>
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-600">Certificate Information:</label>
                <div class="bg-green-50 p-3 rounded mt-1">
                    <div class="text-sm">
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
        <a href="birth_certificates.php?id=<?php echo $record['id']; ?>" class="bg-green-600 text-white py-2 px-6 rounded hover:bg-green-700 transition">
            <i class="fas fa-certificate mr-2"></i> Generate Birth Certificate
        </a>
        <a href="view_birth_records.php" class="bg-blue-600 text-white py-2 px-6 rounded hover:bg-blue-700 transition">
            <i class="fas fa-list mr-2"></i> Back to All Records
        </a>
        <a href="birth_death_records.php" class="bg-gray-600 text-white py-2 px-6 rounded hover:bg-gray-700 transition">
            <i class="fas fa-home mr-2"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>