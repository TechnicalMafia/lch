<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build date filter query
$date_filter = "";
if ($filter === 'today') {
    $date_filter = "DATE(created_at) = CURDATE()";
} elseif ($filter === 'yesterday') {
    $date_filter = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($filter === 'this_week') {
    $date_filter = "YEARWEEK(created_at) = YEARWEEK(CURDATE())";
} elseif ($filter === 'this_month') {
    $date_filter = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} elseif ($filter === 'custom') {
    $date_filter = "DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
}

// Get bills
$query = "SELECT bill_group_id, patient_id, SUM(amount) as total_amount, SUM(discount) as total_discount, 
                 SUM(paid_amount) as total_paid, status, MAX(created_at) as created_at 
          FROM billing 
          WHERE $date_filter
          GROUP BY bill_group_id 
          ORDER BY created_at DESC";
$result = $conn->query($query);

// Get refund statistics
$refund_query = "SELECT SUM(paid_amount) as total_refund, COUNT(*) as refund_count 
                 FROM billing 
                 WHERE status = 'refund' AND $date_filter";
$refund_result = $conn->query($refund_query);
$refund_stats = $refund_result->fetch_assoc();
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Bill Records</h1>
    <p class="text-gray-600">View and manage all billing records</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Bills</h2>
    <form method="get" action="bill_records.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label for="filter" class="block text-gray-700 font-medium mb-2">Date Range</label>
            <select id="filter" name="filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleDateFields()">
                <option value="today" <?php echo $filter === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="yesterday" <?php echo $filter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                <option value="this_week" <?php echo $filter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                <option value="this_month" <?php echo $filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                <option value="custom" <?php echo $filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
            </select>
        </div>
        
        <div id="start-date-field" class="<?php echo $filter !== 'custom' ? 'hidden' : ''; ?>">
            <label for="start_date" class="block text-gray-700 font-medium mb-2">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div id="end-date-field" class="<?php echo $filter !== 'custom' ? 'hidden' : ''; ?>">
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

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Total Bills</h2>
                <p class="text-2xl font-bold"><?php echo $result->num_rows; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-money-bill-wave text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Total Revenue</h2>
                <p class="text-2xl font-bold">
                    <?php 
                    $revenue_query = "SELECT SUM(paid_amount) as total FROM billing WHERE status = 'paid' AND $date_filter";
                    $revenue_result = $conn->query($revenue_query);
                    $revenue = $revenue_result->fetch_assoc();
                    echo formatCurrency($revenue['total'] ?: 0);
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500">
                <i class="fas fa-undo text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Total Refunds</h2>
                <p class="text-2xl font-bold"><?php echo formatCurrency($refund_stats['total_refund'] ?: 0); ?></p>
                <p class="text-sm text-gray-500"><?php echo $refund_stats['refund_count'] ?: 0; ?> refunds</p>
            </div>
        </div>
    </div>
</div>

<!-- Bills Table -->
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Billing Records</h2>
        <a href="billing.php" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 text-sm">
            <i class="fas fa-plus mr-1"></i> New Bill
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                        $patient = getPatient($row['patient_id']);
                        $service_count_query = "SELECT COUNT(*) as count FROM billing WHERE bill_group_id = '" . $row['bill_group_id'] . "'";
                        $service_count_result = $conn->query($service_count_query);
                        $service_count = $service_count_result->fetch_assoc()['count'];
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo $row['bill_group_id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($row['total_amount']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                    if ($row['status'] === 'paid') echo 'bg-green-100 text-green-800';
                                    elseif ($row['status'] === 'unpaid') echo 'bg-yellow-100 text-yellow-800';
                                    else echo 'bg-red-100 text-red-800';
                                ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="bill_details.php?bill_group_id=<?php echo $row['bill_group_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <?php if ($row['status'] !== 'refund'): ?>
                                <form method="post" action="process_refund.php" class="inline" onsubmit="return confirm('Are you sure you want to refund this bill?');">
                                    <input type="hidden" name="bill_group_id" value="<?php echo $row['bill_group_id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-undo mr-1"></i> Refund
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No bills found for the selected criteria</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Refunds Section -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Refund Records</h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Refund Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Refund Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $refund_query = "SELECT bill_group_id, patient_id, SUM(paid_amount) as refund_amount, MAX(created_at) as refund_date 
                               FROM billing 
                               WHERE status = 'refund' AND $date_filter
                               GROUP BY bill_group_id 
                               ORDER BY refund_date DESC";
                $refund_result = $conn->query($refund_query);
                
                if ($refund_result->num_rows > 0): ?>
                    <?php while ($row = $refund_result->fetch_assoc()): ?>
                        <?php 
                        $patient = getPatient($row['patient_id']);
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo $row['bill_group_id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $patient['name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y, h:i A', strtotime($row['refund_date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($row['refund_amount']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="bill_details.php?bill_group_id=<?php echo $row['bill_group_id']; ?>" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye mr-1"></i> View Details
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No refund records found for the selected criteria</td>
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
        
        if (filter === 'custom') {
            startField.classList.remove('hidden');
            endField.classList.remove('hidden');
        } else {
            startField.classList.add('hidden');
            endField.classList.add('hidden');
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>