<?php
require_once '../../includes/functions.php';
requireRole('reception');

// Get all patients
$patients = getAllPatients();
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Patient List</h1>
    <p class="text-gray-600">View all registered patients</p>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <div class="relative">
            <input type="text" id="search" placeholder="Search patients..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
        </div>
        <a href="add_patient.php" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            <i class="fas fa-plus mr-2"></i>Add New Patient
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="patient-table-body">
                <?php if ($patients->num_rows > 0): ?>
                    <?php while ($patient = $patients->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo $patient['name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['age']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['gender']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['contact']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($patient['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="../doctor/view_patient.php?id=<?php echo $patient['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                <a href="token.php?patient_id=<?php echo $patient['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Token</a>
                                <a href="billing.php?patient_id=<?php echo $patient['id']; ?>" class="text-purple-600 hover:text-purple-900">Bill</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No patients found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#patient-table-body tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>