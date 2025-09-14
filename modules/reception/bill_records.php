<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$token_filter = isset($_GET['token_filter']) ? $_GET['token_filter'] : 'today';
$token_type = isset($_GET['token_type']) ? $_GET['token_type'] : 'all';

// Build date filter query for bills
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

// Build date filter query for tokens
$token_date_filter = "";
if ($token_filter === 'today') {
    $token_date_filter = "DATE(t.created_at) = CURDATE()";
} elseif ($token_filter === 'yesterday') {
    $token_date_filter = "DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($token_filter === 'this_week') {
    $token_date_filter = "YEARWEEK(t.created_at) = YEARWEEK(CURDATE())";
} elseif ($token_filter === 'this_month') {
    $token_date_filter = "MONTH(t.created_at) = MONTH(CURDATE()) AND YEAR(t.created_at) = YEAR(CURDATE())";
} elseif ($token_filter === 'custom') {
    $token_date_filter = "DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";
}

// Add token type filter
$token_type_filter = "";
if ($token_type !== 'all') {
    $token_type_filter = " AND t.type = '$token_type'";
}

// Get bills
$query = "SELECT bill_group_id, patient_id, SUM(amount) as total_amount, SUM(discount) as total_discount, 
                 SUM(paid_amount) as total_paid, status, MAX(created_at) as created_at 
          FROM billing 
          WHERE $date_filter
          GROUP BY bill_group_id 
          ORDER BY created_at DESC";
$result = $conn->query($query);

// Get bill statistics
$stats_query = "SELECT 
                    COUNT(DISTINCT bill_group_id) as total_bills,
                    SUM(CASE WHEN status = 'paid' THEN paid_amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'refund' THEN paid_amount ELSE 0 END) as total_refund,
                    COUNT(CASE WHEN status = 'refund' THEN 1 END) as refund_count
                FROM billing 
                WHERE $date_filter";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get tokens with filter
$token_query = "SELECT t.*, p.name as patient_name, s.name as doctor_name, ts.test_name 
                FROM tokens t 
                JOIN patients p ON t.patient_id = p.id 
                LEFT JOIN staff s ON t.doctor_id = s.id 
                LEFT JOIN tests ts ON t.test_id = ts.id 
                WHERE $token_date_filter $token_type_filter
                ORDER BY t.created_at DESC";
$token_result = $conn->query($token_query);

// Get token statistics
$token_stats_query = "SELECT 
                        COUNT(*) as total_tokens,
                        COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting_tokens,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tokens,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_tokens,
                        COUNT(CASE WHEN type = 'doctor' THEN 1 END) as doctor_tokens,
                        COUNT(CASE WHEN type = 'lab' THEN 1 END) as lab_tokens
                      FROM tokens t
                      WHERE $token_date_filter $token_type_filter";
$token_stats_result = $conn->query($token_stats_query);
$token_stats = $token_stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Records & Token Management - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Bill Records & Token Management</h1>
            <p class="text-gray-600">Comprehensive view of billing records and token management</p>
        </div>

        <!-- Tab Navigation -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('bills')" id="bills-tab" class="tab-btn py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>Bill Records
                </button>
                <button onclick="showTab('tokens')" id="tokens-tab" class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-ticket-alt mr-2"></i>Token Management
                </button>
            </nav>
        </div>

        <!-- Bills Tab Content -->
        <div id="bills-content" class="tab-content">
            <!-- Bill Filters -->
            <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
                <div class="bg-indigo-600 text-white px-6 py-4">
                    <h2 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-filter mr-2"></i>
                        Filter Bills
                    </h2>
                </div>
                <div class="p-6">
                    <form method="get" action="bill_records.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="hidden" name="tab" value="bills">
                        <div>
                            <label for="filter" class="block text-gray-700 font-medium mb-2">Date Range</label>
                            <select id="filter" name="filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="toggleDateFields()">
                                <option value="today" <?php echo $filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo $filter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="this_week" <?php echo $filter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="this_month" <?php echo $filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="custom" <?php echo $filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                            </select>
                        </div>
                        
                        <div id="start-date-field" class="<?php echo $filter !== 'custom' ? 'hidden' : ''; ?>">
                            <label for="start_date" class="block text-gray-700 font-medium mb-2">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <div id="end-date-field" class="<?php echo $filter !== 'custom' ? 'hidden' : ''; ?>">
                            <label for="end_date" class="block text-gray-700 font-medium mb-2">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-search mr-2"></i>Apply Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bill Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                            <i class="fas fa-file-invoice-dollar text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-700">Total Bills</h2>
                            <p class="text-2xl font-bold"><?php echo $stats['total_bills'] ?: 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-700">Total Revenue</h2>
                            <p class="text-2xl font-bold"><?php echo formatCurrency($stats['total_revenue'] ?: 0); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-500">
                            <i class="fas fa-undo text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-700">Total Refunds</h2>
                            <p class="text-2xl font-bold"><?php echo formatCurrency($stats['total_refund'] ?: 0); ?></p>
                            <p class="text-sm text-gray-500"><?php echo $stats['refund_count'] ?: 0; ?> refunds</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                            <i class="fas fa-chart-pie text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-700">Net Revenue</h2>
                            <p class="text-2xl font-bold"><?php echo formatCurrency(($stats['total_revenue'] ?: 0) - ($stats['total_refund'] ?: 0)); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bills Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="bg-indigo-600 text-white px-6 py-4">
                    <h2 class="text-xl font-semibold flex items-center justify-between">
                        <span><i class="fas fa-list-alt mr-2"></i>Billing Records</span>
                        <a href="billing.php" class="bg-indigo-700 hover:bg-indigo-800 px-3 py-1 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i>New Bill
                        </a>
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php $patient = getPatient($row['patient_id']); ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-medium text-gray-900"><?php echo $row['bill_group_id']; ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $patient['name']; ?></div>
                                            <div class="text-sm text-gray-500">ID: <?php echo $patient['id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatCurrency($row['total_amount']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatCurrency($row['total_paid']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                                if ($row['status'] === 'paid') echo 'bg-green-100 text-green-800';
                                                elseif ($row['status'] === 'unpaid') echo 'bg-yellow-100 text-yellow-800';
                                                else echo 'bg-red-100 text-red-800';
                                            ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="bill_details.php?bill_group_id=<?php echo $row['bill_group_id']; ?>" 
                                               class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 mr-2">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($row['status'] !== 'refund'): ?>
                                            <button onclick="confirmRefund('<?php echo $row['bill_group_id']; ?>')"
                                                    class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <i class="fas fa-file-invoice text-gray-400 text-4xl mb-4"></i>
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">No bills found</h3>
                                        <p class="text-gray-500">No bills found for the selected criteria</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tokens Tab Content -->
        <div id="tokens-content" class="tab-content hidden">
            <!-- Token Filters -->
            <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
                <div class="bg-green-600 text-white px-6 py-4">
                    <h2 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-filter mr-2"></i>
                        Filter Tokens
                    </h2>
                </div>
                <div class="p-6">
                    <form method="get" action="bill_records.php" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="hidden" name="tab" value="tokens">
                        <div>
                            <label for="token_filter" class="block text-gray-700 font-medium mb-2">Date Range</label>
                            <select id="token_filter" name="token_filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="today" <?php echo $token_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo $token_filter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="this_week" <?php echo $token_filter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="this_month" <?php echo $token_filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="custom" <?php echo $token_filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                            </select>
                        </div>

                        <div>
                            <label for="token_type" class="block text-gray-700 font-medium mb-2">Token Type</label>
                            <select id="token_type" name="token_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="all" <?php echo $token_type === 'all' ? 'selected' : ''; ?>>All Tokens</option>
                                <option value="doctor" <?php echo $token_type === 'doctor' ? 'selected' : ''; ?>>Doctor Only</option>
                                <option value="lab" <?php echo $token_type === 'lab' ? 'selected' : ''; ?>>Lab Only</option>
                            </select>
                        </div>
                        
                        <div id="token-start-date-field" class="<?php echo $token_filter !== 'custom' ? 'hidden' : ''; ?>">
                            <label for="token_start_date" class="block text-gray-700 font-medium mb-2">Start Date</label>
                            <input type="date" id="token_start_date" name="start_date" value="<?php echo $start_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div id="token-end-date-field" class="<?php echo $token_filter !== 'custom' ? 'hidden' : ''; ?>">
                            <label for="token_end_date" class="block text-gray-700 font-medium mb-2">End Date</label>
                            <input type="date" id="token_end_date" name="end_date" value="<?php echo $end_date; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-search mr-2"></i>Apply Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Token Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-blue-100 text-blue-500">
                            <i class="fas fa-ticket-alt text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-gray-700">Total</h3>
                            <p class="text-lg font-bold"><?php echo $token_stats['total_tokens'] ?: 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-yellow-100 text-yellow-500">
                            <i class="fas fa-clock text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-gray-700">Waiting</h3>
                            <p class="text-lg font-bold"><?php echo $token_stats['waiting_tokens'] ?: 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-check-circle text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-gray-700">Completed</h3>
                            <p class="text-lg font-bold"><?php echo $token_stats['completed_tokens'] ?: 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-red-100 text-red-500">
                            <i class="fas fa-times-circle text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-gray-700">Cancelled</h3>
                            <p class="text-lg font-bold"><?php echo $token_stats['cancelled_tokens'] ?: 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-purple-100 text-purple-500">
                            <i class="fas fa-user-md text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-gray-700">Doctor</h3>
                            <p class="text-lg font-bold"><?php echo $token_stats['doctor_tokens'] ?: 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-indigo-100 text-indigo-500">
                            <i class="fas fa-flask text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-gray-700">Lab</h3>
                            <p class="text-lg font-bold"><?php echo $token_stats['lab_tokens'] ?: 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tokens Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-green-600 text-white px-6 py-4">
                    <h2 class="text-xl font-semibold flex items-center justify-between">
                        <span><i class="fas fa-ticket-alt mr-2"></i>Token Records</span>
                        <a href="token.php" class="bg-green-700 hover:bg-green-800 px-3 py-1 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i>New Token
                        </a>
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($token_result->num_rows > 0): ?>
                                <?php while ($token = $token_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $token['type'] === 'doctor' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'; ?>">
                                                <?php echo $token['token_no']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $token['patient_name']; ?></div>
                                            <div class="text-sm text-gray-500">ID: <?php echo $token['patient_id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $token['type'] === 'doctor' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'; ?>">
                                                <i class="fas fa-<?php echo $token['type'] === 'doctor' ? 'user-md' : 'flask'; ?> mr-1"></i>
                                                <?php echo ucfirst($token['type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php 
                                            if ($token['type'] === 'doctor' && $token['doctor_name']) {
                                                echo "Dr. " . $token['doctor_name'];
                                            } elseif ($token['type'] === 'lab' && $token['test_name']) {
                                                echo $token['test_name'];
                                            } else {
                                                echo "<span class='text-gray-500'>Not assigned</span>";
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d M Y, h:i A', strtotime($token['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                                if ($token['status'] === 'waiting') echo 'bg-yellow-100 text-yellow-800';
                                                elseif ($token['status'] === 'completed') echo 'bg-green-100 text-green-800';
                                                else echo 'bg-red-100 text-red-800';
                                            ?>">
                                                <i class="fas fa-<?php 
                                                    if ($token['status'] === 'waiting') echo 'clock';
                                                    elseif ($token['status'] === 'completed') echo 'check-circle';
                                                    else echo 'times-circle';
                                                ?> mr-1"></i>
                                                <?php echo ucfirst($token['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="printToken('<?php echo $token['token_no']; ?>', '<?php echo $token['patient_id']; ?>', '<?php echo $token['patient_name']; ?>', '<?php echo $token['type']; ?>', '<?php echo ($token['type'] === 'doctor' && $token['doctor_name']) ? "Dr. " . $token['doctor_name'] : (($token['type'] === 'lab' && $token['test_name']) ? $token['test_name'] : 'N/A'); ?>')" 
                                                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 mr-2">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <?php if ($token['status'] === 'waiting'): ?>
                                            <button onclick="confirmTokenCancel(<?php echo $token['id']; ?>)"
                                                    class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <i class="fas fa-ticket-alt text-gray-400 text-4xl mb-4"></i>
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">No tokens found</h3>
                                        <p class="text-gray-500">No tokens found for the selected criteria</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Confirmation Modal -->
    <div id="refundModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4 text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Refund
            </h2>
            <p class="mb-4">Are you sure you want to refund this bill? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRefundModal()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Cancel
                </button>
                <form method="post" action="process_refund.php" style="display: inline;">
                    <input type="hidden" id="refund_bill_id" name="bill_group_id">
                    <button type="submit" 
                            class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        <i class="fas fa-undo mr-2"></i>Confirm Refund
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Token Cancel Confirmation Modal -->
    <div id="tokenCancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4 text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i>Cancel Token
            </h2>
            <p class="mb-4">Are you sure you want to cancel this token? This will also refund the associated payment.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeTokenCancelModal()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Cancel
                </button>
                <form method="post" action="process_refund.php" style="display: inline;">
                    <input type="hidden" id="cancel_token_id" name="token_id">
                    <button type="submit" name="cancel_token"
                            class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        <i class="fas fa-times mr-2"></i>Cancel Token
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Token Print Template -->
    <div id="tokenTemplate" style="display: none;">
        <div style="width: 300px; padding: 15px; font-family: monospace; font-size: 12px;">
            <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 10px;">
                <h3 style="font-weight: bold; font-size: 14px; margin: 0;">HOSPITAL MANAGEMENT SYSTEM</h3>
                <p style="font-size: 10px; margin: 0;">123 Medical Street, Lahore</p>
                <p style="font-size: 10px; margin: 0;">Phone: 0300-1234567</p>
            </div>
            <div style="margin: 10px 0;">
                <div><strong>TOKEN NO:</strong> <span id="printTokenNo"></span></div>
                <div><strong>DATE:</strong> <span id="printDate"></span></div>
                <div><strong>TIME:</strong> <span id="printTime"></span></div>
            </div>
            <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; margin: 10px 0;">
                <div><strong>TYPE:</strong> <span id="printType"></span></div>
                <div><strong>ASSIGNED TO:</strong> <span id="printAssignedTo"></span></div>
            </div>
            <div style="margin: 10px 0;">
                <div style="font-weight: bold; text-decoration: underline;">PATIENT DETAILS:</div>
                <div><strong>ID:</strong> <span id="printPatientId"></span></div>
                <div><strong>NAME:</strong> <span id="printPatientName"></span></div>
            </div>
            <div style="text-align: center; margin-top: 15px; border-top: 1px dashed #000; padding-top: 5px; font-size: 10px;">
                <div>Please wait for your turn</div>
                <div>Thank you for visiting</div>
            </div>
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

        // Initialize tab from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'bills';
        showTab(activeTab);

        // Date field toggles for bills
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

        // Date field toggles for tokens
        document.getElementById('token_filter').addEventListener('change', function() {
            const filter = this.value;
            const startField = document.getElementById('token-start-date-field');
            const endField = document.getElementById('token-end-date-field');
            
            if (filter === 'custom') {
                startField.classList.remove('hidden');
                endField.classList.remove('hidden');
            } else {
                startField.classList.add('hidden');
                endField.classList.add('hidden');
            }
        });

        // Refund modal functions
        function confirmRefund(billGroupId) {
            document.getElementById('refund_bill_id').value = billGroupId;
            document.getElementById('refundModal').classList.remove('hidden');
        }

        function closeRefundModal() {
            document.getElementById('refundModal').classList.add('hidden');
        }

        // Token cancel modal functions
        function confirmTokenCancel(tokenId) {
            document.getElementById('cancel_token_id').value = tokenId;
            document.getElementById('tokenCancelModal').classList.remove('hidden');
        }

        function closeTokenCancelModal() {
            document.getElementById('tokenCancelModal').classList.add('hidden');
        }

        // Print token function
        function printToken(tokenNo, patientId, patientName, tokenType, assignedTo) {
            const currentDate = new Date().toLocaleDateString();
            const currentTime = new Date().toLocaleTimeString();
            const tokenTypeFormatted = tokenType.charAt(0).toUpperCase() + tokenType.slice(1);
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Get the template HTML
            const template = document.getElementById('tokenTemplate').innerHTML;
            
            // Create the complete HTML document
            let printContent = '<!DOCTYPE html>';
            printContent += '<html><head><title>Token - ' + tokenNo + '</title>';
            printContent += '<style>body { margin: 0; padding: 0; font-family: monospace; }</style>';
            printContent += '</head><body>';
            printContent += template;
            printContent += '<script>';
            printContent += 'document.getElementById("printTokenNo").textContent = "' + tokenNo + '";';
            printContent += 'document.getElementById("printDate").textContent = "' + currentDate + '";';
            printContent += 'document.getElementById("printTime").textContent = "' + currentTime + '";';
            printContent += 'document.getElementById("printType").textContent = "' + tokenTypeFormatted + '";';
            printContent += 'document.getElementById("printAssignedTo").textContent = "' + assignedTo + '";';
            printContent += 'document.getElementById("printPatientId").textContent = "' + patientId + '";';
            printContent += 'document.getElementById("printPatientName").textContent = "' + patientName + '";';
            printContent += 'window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 500); }';
            printContent += '<\/script></body></html>';
            
            printWindow.document.write(printContent);
            printWindow.document.close();
        }
    </script>
</body>
</html>