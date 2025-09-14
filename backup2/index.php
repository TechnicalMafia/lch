<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-600">Welcome back, <?php echo $_SESSION['username']; ?>!</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Total Patients</h2>
                <p class="text-2xl font-bold">
                    <?php 
                    $result = getAllPatients();
                    echo $result->num_rows;
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-calendar-check text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Today's Visits</h2>
                <p class="text-2xl font-bold">
                    <?php 
                    $today = date('Y-m-d');
                    $query = "SELECT COUNT(*) as count FROM visits WHERE visit_date = '$today'";
                    $result = $conn->query($query);
                    $row = $result->fetch_assoc();
                    echo $row['count'];
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                <i class="fas fa-ticket-alt text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Pending Tokens</h2>
                <p class="text-2xl font-bold">
                    <?php 
                    $query = "SELECT COUNT(*) as count FROM tokens WHERE status = 'waiting'";
                    $result = $conn->query($query);
                    $row = $result->fetch_assoc();
                    echo $row['count'];
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-700">Today's Revenue</h2>
                <p class="text-2xl font-bold">
                    <?php 
                    $today = date('Y-m-d');
                    $query = "SELECT SUM(paid_amount) as total FROM billing WHERE DATE(created_at) = '$today' AND status = 'paid'";
                    $result = $conn->query($query);
                    $row = $result->fetch_assoc();
                    echo formatCurrency($row['total'] ?: 0);
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Recent Patients</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $result = getAllPatients();
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td class='px-6 py-4 whitespace-nowrap'>" . $row['name'] . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap'>" . $row['age'] . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap'>" . $row['contact'] . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap'>" . date('d M Y', strtotime($row['created_at'])) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='px-6 py-4 text-center text-gray-500'>No patients found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Recent Visits</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $result = getAllVisits();
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td class='px-6 py-4 whitespace-nowrap'>" . $row['patient_name'] . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap'>" . $row['doctor_name'] . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap'>" . date('d M Y', strtotime($row['visit_date'])) . "</td>";
                            echo "<td class='px-6 py-4 whitespace-nowrap'><span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800'>Completed</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='px-6 py-4 text-center text-gray-500'>No visits found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>