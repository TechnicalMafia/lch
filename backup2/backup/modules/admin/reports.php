<?php
require_once '../../includes/functions.php';
requireRole('admin');

// Get date range from form submission
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

// Get revenue report
$revenue_report = getRevenueReport($start_date, $end_date);

// Get patient statistics
$patient_stats = getPatientStatistics($start_date, $end_date);

// Get service statistics
$service_stats = getServiceStatistics($start_date, $end_date);
?>

<?php include '../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Reports</h1>
    <p class="text-gray-600">View hospital statistics and financial reports</p>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Date Range</h2>
    <form method="post" action="reports.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="start_date" class="block text-gray-700 font-medium mb-2">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        
        <div>
            <label for="end_date" class="block text-gray-700 font-medium mb-2">End Date</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition w-full">
                Generate Report
            </button>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-money-bill-wave text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Total Revenue</h2>
                <p class="text-2xl font-bold"><?php echo formatCurrency($revenue_report['total_paid']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                <i class="fas fa-hourglass-half text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Pending Payments</h2>
                <p class="text-2xl font-bold"><?php echo formatCurrency($revenue_report['total_unpaid']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500">
                <i class="fas fa-undo text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Refunds</h2>
                <p class="text-2xl font-bold"><?php echo formatCurrency($revenue_report['total_refund']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Unique Patients</h2>
                <p class="text-2xl font-bold"><?php echo $patient_stats['unique_patients']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Patient Statistics</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-blue-800">Unique Patients</h3>
                    <p class="text-2xl font-bold text-blue-600"><?php echo $patient_stats['unique_patients']; ?></p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-green-800">Total Visits</h3>
                    <p class="text-2xl font-bold text-green-600"><?php echo $patient_stats['total_visits']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Revenue Breakdown</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Paid</span>
                        <span class="text-sm font-medium text-gray-700"><?php echo formatCurrency($revenue_report['total_paid']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($revenue_report['total_paid'] / ($revenue_report['total_paid'] + $revenue_report['total_unpaid'] + $revenue_report['total_refund'])) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Unpaid</span>
                        <span class="text-sm font-medium text-gray-700"><?php echo formatCurrency($revenue_report['total_unpaid']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: <?php echo ($revenue_report['total_unpaid'] / ($revenue_report['total_paid'] + $revenue_report['total_unpaid'] + $revenue_report['total_refund'])) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Refund</span>
                        <span class="text-sm font-medium text-gray-700"><?php echo formatCurrency($revenue_report['total_refund']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo ($revenue_report['total_refund'] / ($revenue_report['total_paid'] + $revenue_report['total_unpaid'] + $revenue_report['total_refund'])) * 100; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow mt-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Service Statistics</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Amount</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($service_stats->num_rows > 0): ?>
                    <?php while ($service = $service_stats->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $service['service_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $service['count']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($service['total_amount']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($service['total_amount'] / $service['count']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No service data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>