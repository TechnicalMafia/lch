<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get patient ID from URL if available
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $bill_group_id = 'BILL_' . time(); // Generate unique bill group ID
    
    // Process each service
    $service_names = $_POST['service_name'];
    $amounts = $_POST['amount'];
    $discounts = $_POST['discount'];
    $paid_amounts = $_POST['paid_amount'];
    
    $total_amount = 0;
    $total_discount = 0;
    $total_paid = 0;
    
    for ($i = 0; $i < count($service_names); $i++) {
        if (!empty($service_names[$i])) {
            $service_name = $service_names[$i];
            $amount = $amounts[$i];
            $discount = $discounts[$i];
            $paid_amount = $paid_amounts[$i];
            
            $total_amount += $amount;
            $total_discount += $discount;
            $total_paid += $paid_amount;
            
            $query = "INSERT INTO billing (patient_id, service_name, amount, discount, paid_amount, status, bill_group_id) 
                      VALUES ($patient_id, '$service_name', $amount, $discount, $paid_amount, 'paid', '$bill_group_id')";
            $conn->query($query);
        }
    }
    
    $success = "Bill created successfully with ID: $bill_group_id";
    
    // Get patient details
    $patient = getPatient($patient_id);
}

// Get all patients for dropdown
$patients = getAllPatients();

// Get all tests for dropdown
$tests = getAllTests();

// Get all rooms for dropdown
$rooms = getAllRooms();
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Billing</h1>
    <p class="text-gray-600">Create and manage patient bills</p>
</div>

<?php if (isset($success)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <?php echo $success; ?>
    <div class="mt-2">
        <button onclick="printBill()" class="bg-green-600 text-white py-1 px-3 rounded text-sm hover:bg-green-700">
            <i class="fas fa-print mr-1"></i> Print Bill
        </button>
        <a href="generate_bill.php?patient_id=<?php echo $patient_id; ?>" class="bg-blue-600 text-white py-1 px-3 rounded text-sm hover:bg-blue-700 ml-2">
            <i class="fas fa-file-pdf mr-1"></i> Generate PDF Bill
        </a>
    </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow p-6">
    <form method="post" action="billing.php" id="billing-form">
        <div class="mb-4">
            <label for="patient_id" class="block text-gray-700 font-medium mb-2">Select Patient</label>
            <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">Select a patient</option>
                <?php while ($patient = $patients->fetch_assoc()): ?>
                    <option value="<?php echo $patient['id']; ?>" <?php echo ($patient['id'] == $patient_id) ? 'selected' : ''; ?>>
                        <?php echo $patient['name'] . ' (ID: ' . $patient['id'] . ')'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-800">Services</h3>
                <button type="button" id="add-service-btn" class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 text-sm">
                    <i class="fas fa-plus mr-1"></i> Add Service
                </button>
            </div>
            
            <div id="services-container">
                <!-- Initial service row -->
                <div class="service-row grid grid-cols-1 md:grid-cols-5 gap-4 mb-4 p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Service Name</label>
                        <select name="service_name[]" class="service-name w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Select a service</option>
                            <option value="Doctor Consultation">Doctor Consultation</option>
                            <option value="Emergency Fee">Emergency Fee</option>
                            <optgroup label="Lab Tests">
                                <?php 
                                $tests->data_seek(0);
                                while ($test = $tests->fetch_assoc()): ?>
                                    <option value="<?php echo $test['test_name']; ?>"><?php echo $test['test_name']; ?></option>
                                <?php endwhile; ?>
                            </optgroup>
                            <optgroup label="Room Charges">
                                <?php 
                                $rooms->data_seek(0);
                                while ($room = $rooms->fetch_assoc()): ?>
                                    <option value="<?php echo $room['room_name']; ?>"><?php echo $room['room_name']; ?></option>
                                <?php endwhile; ?>
                            </optgroup>
                            <option value="Medicines">Medicines</option>
                            <option value="Other Services">Other Services</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Amount (PKR)</label>
                        <input type="number" name="amount[]" class="service-amount w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Discount (PKR)</label>
                        <input type="number" name="discount[]" class="service-discount w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="0">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Paid Amount (PKR)</label>
                        <input type="number" name="paid_amount[]" class="service-paid w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="button" class="remove-service-btn bg-red-600 text-white py-2 px-3 rounded hover:bg-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between">
                    <div>
                        <div class="text-lg font-medium">Total Amount: <span id="total-amount">0</span> PKR</div>
                        <div class="text-lg font-medium">Total Discount: <span id="total-discount">0</span> PKR</div>
                        <div class="text-lg font-medium">Total Paid: <span id="total-paid">0</span> PKR</div>
                    </div>
                    <div>
                        <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                            Create Bill
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow p-6 mt-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Recent Bills</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Services</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $query = "SELECT bill_group_id, patient_id, SUM(amount) as total_amount, status, MAX(created_at) as created_at 
                          FROM billing 
                          GROUP BY bill_group_id 
                          ORDER BY created_at DESC 
                          LIMIT 10";
                $result = $conn->query($query);
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $patient = getPatient($row['patient_id']);
                        $service_count_query = "SELECT COUNT(*) as count FROM billing WHERE bill_group_id = '" . $row['bill_group_id'] . "'";
                        $service_count_result = $conn->query($service_count_query);
                        $service_count = $service_count_result->fetch_assoc()['count'];
                        
                        echo "<tr>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . $row['bill_group_id'] . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . $patient['name'] . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . $service_count . " services</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>" . formatCurrency($row['total_amount']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap'>";
                        if ($row['status'] === 'paid') {
                            echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800'>Paid</span>";
                        } elseif ($row['status'] === 'unpaid') {
                            echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800'>Unpaid</span>";
                        } elseif ($row['status'] === 'refund') {
                            echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800'>Refund</span>";
                        }
                        echo "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap text-sm'>";
                        echo "<a href='bill_details.php?bill_group_id=" . $row['bill_group_id'] . "' class='bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700 mr-2'>";
                        echo "<i class='fas fa-eye mr-1'></i> View";
                        echo "</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='px-6 py-4 text-center text-gray-500'>No bills found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4 text-center">
        <a href="bill_records.php" class="text-blue-600 hover:text-blue-800 text-sm">View All Bills</a>
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
                <span><?php echo isset($bill_group_id) ? $bill_group_id : ''; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="font-bold">DATE:</span>
                <span><?php echo date('d M Y'); ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span class="font-bold">TIME:</span>
                <span><?php echo date('h:i A'); ?></span>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="font-bold mb-2">PATIENT DETAILS:</div>
            <div class="flex justify-between mb-1">
                <span>ID:</span>
                <span><?php echo isset($patient) ? $patient['id'] : ''; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Name:</span>
                <span><?php echo isset($patient) ? $patient['name'] : ''; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Age:</span>
                <span><?php echo isset($patient) ? $patient['age'] : ''; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Gender:</span>
                <span><?php echo isset($patient) ? $patient['gender'] : ''; ?></span>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="font-bold mb-2">BILL DETAILS:</div>
            <div class="mb-2">
                <div class="text-sm font-medium">Services:</div>
                <div class="text-xs">
                    <?php
                    if (isset($service_names)) {
                        for ($i = 0; $i < count($service_names); $i++) {
                            if (!empty($service_names[$i])) {
                                echo "• " . $service_names[$i] . ": " . formatCurrency($amounts[$i]) . "<br>";
                            }
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="flex justify-between mb-1">
                <span>Total Amount:</span>
                <span><?php echo isset($total_amount) ? formatCurrency($total_amount) : ''; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Total Discount:</span>
                <span><?php echo isset($total_discount) ? formatCurrency($total_discount) : ''; ?></span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Total Paid:</span>
                <span><?php echo isset($total_paid) ? formatCurrency($total_paid) : ''; ?></span>
            </div>
        </div>
        
        <div class="text-center text-xs mt-6">
            <p>Thank you for your payment</p>
            <p>Please keep this receipt for your records</p>
        </div>
    </div>
</div>

<script>
    // Add new service row
    document.getElementById('add-service-btn').addEventListener('click', function() {
        const container = document.getElementById('services-container');
        const serviceRow = document.createElement('div');
        serviceRow.className = 'service-row grid grid-cols-1 md:grid-cols-5 gap-4 mb-4 p-4 border border-gray-200 rounded-lg';
        
        serviceRow.innerHTML = `
            <div>
                <label class="block text-gray-700 font-medium mb-2">Service Name</label>
                <select name="service_name[]" class="service-name w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Select a service</option>
                    <option value="Doctor Consultation">Doctor Consultation</option>
                    <option value="Emergency Fee">Emergency Fee</option>
                    <optgroup label="Lab Tests">
                        <?php 
                        $tests->data_seek(0);
                        while ($test = $tests->fetch_assoc()): ?>
                            <option value="<?php echo $test['test_name']; ?>"><?php echo $test['test_name']; ?></option>
                        <?php endwhile; ?>
                    </optgroup>
                    <optgroup label="Room Charges">
                        <?php 
                        $rooms->data_seek(0);
                        while ($room = $rooms->fetch_assoc()): ?>
                            <option value="<?php echo $room['room_name']; ?>"><?php echo $room['room_name']; ?></option>
                        <?php endwhile; ?>
                    </optgroup>
                    <option value="Medicines">Medicines</option>
                    <option value="Other Services">Other Services</option>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">Amount (PKR)</label>
                <input type="number" name="amount[]" class="service-amount w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">Discount (PKR)</label>
                <input type="number" name="discount[]" class="service-discount w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="0">
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">Paid Amount (PKR)</label>
                <input type="number" name="paid_amount[]" class="service-paid w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="flex items-end">
                <button type="button" class="remove-service-btn bg-red-600 text-white py-2 px-3 rounded hover:bg-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        container.appendChild(serviceRow);
        
        // Add event listeners to the new row
        addServiceRowEventListeners(serviceRow);
        
        // Update totals
        updateTotals();
    });
    
    // Remove service row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-service-btn')) {
            e.target.closest('.service-row').remove();
            updateTotals();
        }
    });
    
    // Add event listeners to initial service row
    function addServiceRowEventListeners(row) {
        const serviceSelect = row.querySelector('.service-name');
        const amountInput = row.querySelector('.service-amount');
        const discountInput = row.querySelector('.service-discount');
        const paidInput = row.querySelector('.service-paid');
        
        // Auto-fill amount when service is selected
        serviceSelect.addEventListener('change', function() {
            const service = this.value;
            
            // Set default amounts based on service
            if (service === 'Doctor Consultation') {
                amountInput.value = '1000';
            } else if (service === 'Emergency Fee') {
                amountInput.value = '2000';
            } else if (service === 'Medicines') {
                amountInput.value = '500';
            } else {
                // Check if it's a lab test
                <?php 
                $tests->data_seek(0);
                while ($test = $tests->fetch_assoc()): ?>
                    if (service === '<?php echo $test['test_name']; ?>') {
                        amountInput.value = '<?php echo $test['price']; ?>';
                    }
                <?php endwhile; ?>
                
                // Check if it's a room
                <?php 
                $rooms->data_seek(0);
                while ($room = $rooms->fetch_assoc()): ?>
                    if (service === '<?php echo $room['room_name']; ?>') {
                        amountInput.value = '<?php echo $room['price']; ?>';
                    }
                <?php endwhile; ?>
            }
            
            // Calculate paid amount
            const amount = parseFloat(amountInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            paidInput.value = (amount - discount).toFixed(2);
            
            updateTotals();
        });
        
        // Calculate paid amount when amount or discount changes
        amountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            paidInput.value = (amount - discount).toFixed(2);
            updateTotals();
        });
        
        discountInput.addEventListener('input', function() {
            const amount = parseFloat(amountInput.value) || 0;
            const discount = parseFloat(this.value) || 0;
            paidInput.value = (amount - discount).toFixed(2);
            updateTotals();
        });
        
        paidInput.addEventListener('input', updateTotals);
    }
    
    // Add event listeners to initial service row
    const initialServiceRow = document.querySelector('.service-row');
    if (initialServiceRow) {
        addServiceRowEventListeners(initialServiceRow);
    }
    
    // Update totals
    function updateTotals() {
        const amountInputs = document.querySelectorAll('.service-amount');
        const discountInputs = document.querySelectorAll('.service-discount');
        const paidInputs = document.querySelectorAll('.service-paid');
        
        let totalAmount = 0;
        let totalDiscount = 0;
        let totalPaid = 0;
        
        amountInputs.forEach(input => {
            totalAmount += parseFloat(input.value) || 0;
        });
        
        discountInputs.forEach(input => {
            totalDiscount += parseFloat(input.value) || 0;
        });
        
        paidInputs.forEach(input => {
            totalPaid += parseFloat(input.value) || 0;
        });
        
        document.getElementById('total-amount').textContent = totalAmount.toFixed(2);
        document.getElementById('total-discount').textContent = totalDiscount.toFixed(2);
        document.getElementById('total-paid').textContent = totalPaid.toFixed(2);
    }
    
    function printBill() {
        const printContent = document.getElementById('bill-print').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>