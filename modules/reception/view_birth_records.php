<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get birth records based on filter
if ($filter === 'date_range') {
    $birth_records = getBirthRecordsByDateRange($start_date, $end_date);
} else {
    $birth_records = getAllBirthRecords();
}

// Get statistics
$birth_stats = getBirthStatistics($start_date, $end_date);
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Birth Records</h1>
    <p class="text-gray-600">View and manage all birth records</p>
</div>

<!-- Filter Section -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Records</h2>
    <form method="get" action="view_birth_records.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label for="filter" class="block text-gray-700 font-medium mb-2">Filter Type</label>
            <select id="filter" name="filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleDateFields()">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Records</option>
                <option value="date_range" <?php echo $filter === 'date_range' ? 'selected' : ''; ?>>Date Range</option>
            </select>
        </div>
        
        <div id="start-date-field" class="<?php echo $filter !== 'date_range' ? 'hidden' : ''; ?>">
            <label for="start_date" class="block text-gray-700 font-medium mb-2">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div id="end-date-field" class="<?php echo $filter !== 'date_range' ? 'hidden' : ''; ?>">
            <label for="end_date" class="block text-gray-700 font-medium mb-2">End Date</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-baby text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Total Births</h2>
                <p class="text-2xl font-bold"><?php echo $birth_stats['total_births'] ?: 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-mars text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Male Births</h2>
                <p class="text-2xl font-bold"><?php echo $birth_stats['male_births'] ?: 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-pink-100 text-pink-500">
                <i class="fas fa-venus text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Female Births</h2>
                <p class="text-2xl font-bold"><?php echo $birth_stats['female_births'] ?: 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                <i class="fas fa-weight text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Avg Weight</h2>
                <p class="text-2xl font-bold"><?php echo $birth_stats['avg_weight'] ? round($birth_stats['avg_weight'], 2) . ' kg' : 'N/A'; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Birth Records Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Birth Records</h2>
        <div class="space-x-2">
            <a href="add_birth_record.php" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Birth Record
            </a>
            <a href="birth_death_records.php" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certificate No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Newborn Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parents</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date of Birth</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($birth_records->num_rows > 0): ?>
                    <?php while ($record = $birth_records->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo $record['certificate_number']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($record['newborn_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <div><strong>Father:</strong> <?php echo htmlspecialchars($record['father_name']); ?></div>
                                    <div><strong>Mother:</strong> <?php echo htmlspecialchars($record['mother_name']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <div><?php echo date('d M Y', strtotime($record['date_of_birth'])); ?></div>
                                    <div class="text-gray-500"><?php echo date('h:i A', strtotime($record['time_of_birth'])); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $record['gender'] === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                    <?php echo $record['gender']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php echo $record['weight'] ? $record['weight'] . ' kg' : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <a href="birth_record_details.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <a href="birth_certificates.php?id=<?php echo $record['id']; ?>" class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-certificate mr-1"></i> Certificate
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No birth records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleDateFields() {
    const filter = document.getElementById('filter').value;
    const startField = document.getElementById('start-date-field');
    const endField = document.getElementById('end-date-field');
    
    if (filter === 'date_range') {
        startField.classList.remove('hidden');
        endField.classList.remove('hidden');
    } else {
        startField.classList.add('hidden');
        endField.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>