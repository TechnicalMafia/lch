<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get recent records for dashboard
$recent_births = getBirthRecordsByDateRange(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
$recent_deaths = getDeathRecordsByDateRange(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));

// Get statistics for current month
$start_of_month = date('Y-m-01');
$end_of_month = date('Y-m-d');
$birth_stats = getBirthStatistics($start_of_month, $end_of_month);
$death_stats = getDeathStatistics($start_of_month, $end_of_month);
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Birth & Death Records</h1>
    <p class="text-gray-600">Manage hospital birth and death certificates</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-baby text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Births This Month</h2>
                <p class="text-2xl font-bold"><?php echo $birth_stats['total_births'] ?: 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-venus-mars text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Gender Ratio</h2>
                <p class="text-sm">Male: <?php echo $birth_stats['male_births'] ?: 0; ?> | Female: <?php echo $birth_stats['female_births'] ?: 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500">
                <i class="fas fa-heart-broken text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Deaths This Month</h2>
                <p class="text-2xl font-bold"><?php echo $death_stats['total_deaths'] ?: 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                <i class="fas fa-chart-line text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Avg Age at Death</h2>
                <p class="text-2xl font-bold"><?php echo $death_stats['avg_age_at_death'] ? round($death_stats['avg_age_at_death']) : 0; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Birth Records</h2>
        <div class="space-y-3">
            <a href="add_birth_record.php" class="block w-full bg-green-600 text-white py-3 px-4 rounded-md hover:bg-green-700 text-center transition">
                <i class="fas fa-plus mr-2"></i> Add Birth Record
            </a>
            <a href="view_birth_records.php" class="block w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 text-center transition">
                <i class="fas fa-list mr-2"></i> View All Birth Records
            </a>
            <a href="birth_certificates.php" class="block w-full bg-purple-600 text-white py-3 px-4 rounded-md hover:bg-purple-700 text-center transition">
                <i class="fas fa-certificate mr-2"></i> Generate Birth Certificates
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Death Records</h2>
        <div class="space-y-3">
            <a href="add_death_record.php" class="block w-full bg-red-600 text-white py-3 px-4 rounded-md hover:bg-red-700 text-center transition">
                <i class="fas fa-plus mr-2"></i> Add Death Record
            </a>
            <a href="view_death_records.php" class="block w-full bg-gray-600 text-white py-3 px-4 rounded-md hover:bg-gray-700 text-center transition">
                <i class="fas fa-list mr-2"></i> View All Death Records
            </a>
            <a href="death_certificates.php" class="block w-full bg-black text-white py-3 px-4 rounded-md hover:bg-gray-800 text-center transition">
                <i class="fas fa-certificate mr-2"></i> Generate Death Certificates
            </a>
        </div>
    </div>
</div>

<!-- Recent Records -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Births -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Recent Births (Last 30 Days)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Newborn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mother</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($recent_births->num_rows > 0): ?>
                        <?php while ($birth = $recent_births->fetch_assoc()): ?>
                            <tr>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($birth['newborn_name']); ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($birth['mother_name']); ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm"><?php echo date('d M Y', strtotime($birth['date_of_birth'])); ?></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $birth['gender'] === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                        <?php echo $birth['gender']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No recent births</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Deaths -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Recent Deaths (Last 30 Days)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($recent_deaths->num_rows > 0): ?>
                        <?php while ($death = $recent_deaths->fetch_assoc()): ?>
                            <tr>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($death['deceased_name']); ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm"><?php echo date('d M Y', strtotime($death['date_of_death'])); ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm"><?php echo $death['age_at_death']; ?> years</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <?php echo $death['certificate_number'] ? 'Certified' : 'Pending'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No recent deaths</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>