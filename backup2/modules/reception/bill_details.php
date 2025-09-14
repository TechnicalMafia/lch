<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get bill group ID from URL
$bill_group_id = isset($_GET['bill_group_id']) ? $_GET['bill_group_id'] : '';

// Get bill details
$query = "SELECT * FROM billing WHERE bill_group_id = '$bill_group_id'";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    // Redirect if bill not found
    header('Location: billing.php');
    exit;
}

$bill_items = [];
$total_amount = 0;
$total_discount = 0;
$total_paid = 0;
$patient_id = null;

while ($row = $result->fetch_assoc()) {
    $bill_items[] = $row;
    $total_amount += $row['amount'];
    $total_discount += $row['discount'];
    $total_paid += $row['paid_amount'];
    $patient_id = $row['patient_id'];
}

$patient = getPatient($patient_id);
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Bill Details</h1>
    <p class="text-gray-600">View detailed bill information</p>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Bill #<?php echo $bill_group_id; ?></h2>
            <p class="text-gray-600">Date: <?php echo date('d M Y', strtotime($bill_items[0]['created_at'])); ?></p>
        </div>
        <div>
            <button onclick="printBill()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                <i class="fas fa-print mr-2"></i> Print Bill
            </button>
            <a href="generate_bill.php?bill_group_id=<?php echo $bill_group_id; ?>" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ml-2">
                <i class="fas fa-file-pdf mr-2"></i> Generate PDF
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-1">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Patient Information</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ID:</span>
                        <span class="font-medium"><?php echo $patient['id']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name:</span>
                        <span class="font-medium"><?php echo $patient['name']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Age:</span>
                        <span class="font-medium"><?php echo $patient['age']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Gender:</span>
                        <span class="font-medium"><?php echo $patient['gender']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Contact:</span>
                        <span class="font-medium"><?php echo $patient['contact']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-2">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Bill Summary</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-sm text-gray-600">Total Amount</div>
                        <div class="text-xl font-bold"><?php echo formatCurrency($total_amount); ?></div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-sm text-gray-600">Total Discount</div>
                        <div class="text-xl font-bold"><?php echo formatCurrency($total_discount); ?></div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="text-sm text-gray-600">Total Paid</div>
                        <div class="text-xl font-bold"><?php echo formatCurrency($total_paid); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($bill_items as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $item['service_name']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($item['amount']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($item['discount']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo formatCurrency($item['paid_amount']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                if ($item['status'] === 'paid') echo 'bg-green-100 text-green-800';
                                elseif ($item['status'] === 'unpaid') echo 'bg-yellow-100 text-yellow-800';
                                else echo 'bg-red-100 text-red-800';
                            ?>">
                                <?php echo ucfirst($item['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Actions</h2>
    <div class="flex space-x-4">
        <a href="billing.php" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            <i class="fas fa-arrow-left mr-2"></i> Back to Billing
        </a>
        <a href="bill_records.php" class="bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
            <i class="fas fa-list mr-2"></i> View All Bills
        </a>
    </div>
</div>

<!-- Bill Print Template -->
<div id="bill-print" class="hidden">
    <div class="p-6" style="width: 300px; font-family: monospace;">
        <div class="text-center mb-4">
            <h3 class="font-bold text-lg">HOSPITAL MANAGEMENT SYSTEM</h3>
            <p class="text-sm">123 Medical Street, Lahore</p>
            <p class="text-sm">Phone: 0300-1234567</p>
        </div>
        
        <div class="border-t border-b border-dashed py-2 my-2">
            <div class="flex justify-between mb-1">
                <span class="font-bold">BILL NO:</span>
                <span><?php echo $bill_group_id; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="font-bold">DATE:</span>
                <span><?php echo date('d M Y', strtotime($bill_items[0]['created_at'])); ?></span>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="font-bold mb-2">PATIENT DETAILS:</div>
            <div class="flex justify-between mb-1">
                <span>ID:</span>
                <span><?php echo $patient['id']; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Name:</span>
                <span><?php echo $patient['name']; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Age:</span>
                <span><?php echo $patient['age']; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Gender:</span>
                <span><?php echo $patient['gender']; ?></span>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="font-bold mb-2">BILL DETAILS:</div>
            <?php foreach ($bill_items as $item): ?>
                <div class="mb-2">
                    <div><?php echo $item['service_name']; ?></div>
                    <div class="flex justify-between text-sm">
                        <span>Amount: <?php echo formatCurrency($item['amount']); ?></span>
                        <span>Paid: <?php echo formatCurrency($item['paid_amount']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="border-t border-dashed pt-2">
            <div class="flex justify-between font-bold mb-1">
                <span>Total Amount:</span>
                <span><?php echo formatCurrency($total_amount); ?></span>
            </div>
            <div class="flex justify-between font-bold mb-1">
                <span>Total Discount:</span>
                <span><?php echo formatCurrency($total_discount); ?></span>
            </div>
            <div class="flex justify-between font-bold">
                <span>Total Paid:</span>
                <span><?php echo formatCurrency($total_paid); ?></span>
            </div>
        </div>
        
        <div class="text-center text-xs mt-6">
            <p>Thank you for your payment</p>
            <p>Please keep this receipt for your records</p>
        </div>
    </div>
</div>

<script>
    function printBill() {
        const printContent = document.getElementById('bill-print').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>