<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hospital_system';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check user role
function hasRole($role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /lch/login.php');
        exit;
    }
}

// NEW: Simple admin check
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// FIXED: Redirect if not authorized - EXPLICIT admin handling
function requireRole($role) {
    requireLogin();
    
    // Admin can access EVERYTHING - no restrictions
    if (isAdmin()) {
        return; // Allow access
    }
    
    // For non-admin users, check specific role
    if ($_SESSION['role'] !== $role) {
        header('Location: /lch/index.php?error=access_denied&required=' . $role . '&current=' . $_SESSION['role']);
        exit;
    }
}

// Alternative function for explicit access control
function requireAnyRole($roles) {
    requireLogin();
    
    // Admin can access everything
    if (isAdmin()) {
        return;
    }
    
    // Check if user has any of the specified roles
    if (is_array($roles)) {
        foreach ($roles as $role) {
            if ($_SESSION['role'] === $role) {
                return;
            }
        }
    } else {
        if ($_SESSION['role'] === $roles) {
            return;
        }
    }
    
    header('Location: /lch/index.php?error=access_denied');
    exit;
}

// Generate unique token number
function generateToken($type) {
    global $conn;
    
    $prefix = ($type === 'doctor') ? 'D' : 'L';
    $date = date('Ymd');
    
    $query = "SELECT token_no FROM tokens WHERE token_no LIKE '$prefix$date%' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastToken = $row['token_no'];
        $lastNumber = intval(substr($lastToken, -3));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . $date . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
}

// Format currency
function formatCurrency($amount) {
    return 'PKR ' . number_format($amount, 2);
}

// Get patient by ID
function getPatient($id) {
    global $conn;
    $query = "SELECT * FROM patients WHERE id = $id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Get user by ID
function getUser($id) {
    global $conn;
    $query = "SELECT * FROM users WHERE id = $id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Get staff by ID
function getStaff($id) {
    global $conn;
    $query = "SELECT * FROM staff WHERE id = $id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Get test by ID
function getTest($id) {
    global $conn;
    $query = "SELECT * FROM tests WHERE id = $id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Get room by ID
function getRoom($id) {
    global $conn;
    $query = "SELECT * FROM rooms WHERE id = $id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Get all patients
function getAllPatients() {
    global $conn;
    $query = "SELECT * FROM patients ORDER BY created_at DESC";
    return $conn->query($query);
}

// Get all users
function getAllUsers() {
    global $conn;
    $query = "SELECT * FROM users ORDER BY id";
    return $conn->query($query);
}

// Get all staff
function getAllStaff() {
    global $conn;
    $query = "SELECT * FROM staff ORDER BY id";
    return $conn->query($query);
}

// Get all tests
function getAllTests() {
    global $conn;
    $query = "SELECT * FROM tests ORDER BY id";
    return $conn->query($query);
}

// Get all rooms
function getAllRooms() {
    global $conn;
    $query = "SELECT * FROM rooms ORDER BY id";
    return $conn->query($query);
}

// Get all bills
function getAllBills() {
    global $conn;
    $query = "SELECT b.*, p.name as patient_name FROM billing b JOIN patients p ON b.patient_id = p.id ORDER BY b.created_at DESC";
    return $conn->query($query);
}

// Get all tokens
function getAllTokens() {
    global $conn;
    $query = "SELECT t.*, p.name as patient_name FROM tokens t JOIN patients p ON t.patient_id = p.id ORDER BY t.created_at DESC";
    return $conn->query($query);
}

// Get all lab reports
function getAllLabReports() {
    global $conn;
    $query = "SELECT lr.*, p.name as patient_name FROM lab_reports lr JOIN patients p ON lr.patient_id = p.id ORDER BY lr.created_at DESC";
    return $conn->query($query);
}

// Get all visits
function getAllVisits() {
    global $conn;
    $query = "SELECT v.*, p.name as patient_name, s.name as doctor_name FROM visits v JOIN patients p ON v.patient_id = p.id JOIN staff s ON v.doctor_id = s.id ORDER BY v.visit_date DESC";
    return $conn->query($query);
}

// Get doctor tokens
function getDoctorTokens() {
    global $conn;
    $query = "SELECT t.*, p.name as patient_name FROM tokens t JOIN patients p ON t.patient_id = p.id WHERE t.type = 'doctor' AND t.status = 'waiting' ORDER BY t.created_at ASC";
    return $conn->query($query);
}

// Get lab tokens
function getLabTokens() {
    global $conn;
    $query = "SELECT t.*, p.name as patient_name FROM tokens t JOIN patients p ON t.patient_id = p.id WHERE t.type = 'lab' AND t.status = 'waiting' ORDER BY t.created_at ASC";
    return $conn->query($query);
}

// Get patient visits
function getPatientVisits($patient_id) {
    global $conn;
    $query = "SELECT v.*, s.name as doctor_name FROM visits v JOIN staff s ON v.doctor_id = s.id WHERE v.patient_id = $patient_id ORDER BY v.visit_date DESC";
    return $conn->query($query);
}

// Get patient bills
function getPatientBills($patient_id) {
    global $conn;
    $query = "SELECT * FROM billing WHERE patient_id = $patient_id ORDER BY created_at DESC";
    return $conn->query($query);
}

// Get patient lab reports
function getPatientLabReports($patient_id) {
    global $conn;
    $query = "SELECT * FROM lab_reports WHERE patient_id = $patient_id ORDER BY created_at DESC";
    return $conn->query($query);
}

// Get patient tokens
function getPatientTokens($patient_id) {
    global $conn;
    $query = "SELECT * FROM tokens WHERE patient_id = $patient_id ORDER BY created_at DESC";
    return $conn->query($query);
}

// Get available rooms
function getAvailableRooms() {
    global $conn;
    $query = "SELECT * FROM rooms WHERE status = 'available' ORDER BY id";
    return $conn->query($query);
}

// Get occupied rooms
function getOccupiedRooms() {
    global $conn;
    $query = "SELECT * FROM rooms WHERE status = 'occupied' ORDER BY id";
    return $conn->query($query);
}

// Get revenue report
function getRevenueReport($start_date, $end_date) {
    global $conn;
    $query = "SELECT 
                SUM(CASE WHEN status = 'paid' THEN paid_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = 'unpaid' THEN amount - paid_amount ELSE 0 END) as total_unpaid,
                SUM(CASE WHEN status = 'refund' THEN paid_amount ELSE 0 END) as total_refund
              FROM billing 
              WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Get patient statistics
function getPatientStatistics($start_date, $end_date) {
    global $conn;
    $query = "SELECT 
                COUNT(DISTINCT patient_id) as unique_patients,
                COUNT(*) as total_visits
              FROM visits 
              WHERE visit_date BETWEEN '$start_date' AND '$end_date'";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Get service statistics
function getServiceStatistics($start_date, $end_date) {
    global $conn;
    $query = "SELECT 
                service_name,
                COUNT(*) as count,
                SUM(amount) as total_amount
              FROM billing 
              WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
              GROUP BY service_name
              ORDER BY count DESC";
    return $conn->query($query);
}

// ==================== BIRTH & DEATH RECORDS FUNCTIONS ====================

// Birth Records Functions
function getAllBirthRecords() {
    global $conn;
    $query = "SELECT br.*, p.name as mother_patient_name, s.name as doctor_name 
              FROM birth_records br 
              LEFT JOIN patients p ON br.patient_id = p.id 
              LEFT JOIN staff s ON br.doctor_id = s.id 
              ORDER BY br.created_at DESC";
    return $conn->query($query);
}

function getBirthRecord($id) {
    global $conn;
    $query = "SELECT br.*, p.name as mother_patient_name, p.contact as mother_contact, s.name as doctor_name 
              FROM birth_records br 
              LEFT JOIN patients p ON br.patient_id = p.id 
              LEFT JOIN staff s ON br.doctor_id = s.id 
              WHERE br.id = $id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

function getBirthRecordsByDateRange($start_date, $end_date) {
    global $conn;
    $query = "SELECT br.*, p.name as mother_patient_name, s.name as doctor_name 
              FROM birth_records br 
              LEFT JOIN patients p ON br.patient_id = p.id 
              LEFT JOIN staff s ON br.doctor_id = s.id 
              WHERE DATE(br.date_of_birth) BETWEEN '$start_date' AND '$end_date' 
              ORDER BY br.date_of_birth DESC";
    return $conn->query($query);
}

// Death Records Functions
function getAllDeathRecords() {
    global $conn;
    $query = "SELECT dr.*, p.name as patient_name, s.name as doctor_name 
              FROM death_records dr 
              LEFT JOIN patients p ON dr.patient_id = p.id 
              LEFT JOIN staff s ON dr.doctor_id = s.id 
              ORDER BY dr.created_at DESC";
    return $conn->query($query);
}

function getDeathRecord($id) {
    global $conn;
    $query = "SELECT dr.*, p.name as patient_name, p.contact as patient_contact, s.name as doctor_name 
              FROM death_records dr 
              LEFT JOIN patients p ON dr.patient_id = p.id 
              LEFT JOIN staff s ON dr.doctor_id = s.id 
              WHERE dr.id = $id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

function getDeathRecordsByDateRange($start_date, $end_date) {
    global $conn;
    $query = "SELECT dr.*, p.name as patient_name, s.name as doctor_name 
              FROM death_records dr 
              LEFT JOIN patients p ON dr.patient_id = p.id 
              LEFT JOIN staff s ON dr.doctor_id = s.id 
              WHERE DATE(dr.date_of_death) BETWEEN '$start_date' AND '$end_date' 
              ORDER BY dr.date_of_death DESC";
    return $conn->query($query);
}

// Generate Certificate Numbers
function generateBirthCertificateNumber() {
    global $conn;
    $year = date('Y');
    $query = "SELECT certificate_number FROM birth_records WHERE certificate_number LIKE 'BC$year%' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNumber = intval(substr($row['certificate_number'], -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return 'BC' . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

function generateDeathCertificateNumber() {
    global $conn;
    $year = date('Y');
    $query = "SELECT certificate_number FROM death_records WHERE certificate_number LIKE 'DC$year%' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNumber = intval(substr($row['certificate_number'], -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return 'DC' . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

// Statistics Functions
function getBirthStatistics($start_date, $end_date) {
    global $conn;
    $query = "SELECT 
                COUNT(*) as total_births,
                SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_births,
                SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_births,
                SUM(CASE WHEN delivery_type = 'Normal' THEN 1 ELSE 0 END) as normal_deliveries,
                SUM(CASE WHEN delivery_type = 'C-Section' THEN 1 ELSE 0 END) as c_section_deliveries,
                AVG(weight) as avg_weight,
                AVG(length) as avg_length
              FROM birth_records 
              WHERE DATE(date_of_birth) BETWEEN '$start_date' AND '$end_date'";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

function getDeathStatistics($start_date, $end_date) {
    global $conn;
    $query = "SELECT 
                COUNT(*) as total_deaths,
                AVG(age_at_death) as avg_age_at_death,
                SUM(CASE WHEN autopsy_required = 1 THEN 1 ELSE 0 END) as autopsy_cases
              FROM death_records 
              WHERE DATE(date_of_death) BETWEEN '$start_date' AND '$end_date'";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}







//this is code is for prciing updates




// Custom Services Functions
function getAllCustomServices() {
    global $conn;
    $query = "SELECT * FROM custom_services ORDER BY service_type, service_name";
    return $conn->query($query);
}

function getCustomService($id) {
    global $conn;
    $query = "SELECT * FROM custom_services WHERE id = $id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

function getActiveCustomServices() {
    global $conn;
    $query = "SELECT * FROM custom_services WHERE status = 'active' ORDER BY service_type, service_name";
    return $conn->query($query);
}

function getCustomServicesByType($type) {
    global $conn;
    $query = "SELECT * FROM custom_services WHERE service_type = '$type' AND status = 'active' ORDER BY service_name";
    return $conn->query($query);
}

function addCustomService($service_name, $service_type, $price, $description = '') {
    global $conn;
    $service_name = mysqli_real_escape_string($conn, $service_name);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "INSERT INTO custom_services (service_name, service_type, price, description) 
              VALUES ('$service_name', '$service_type', $price, '$description')";
    
    return $conn->query($query);
}

function updateCustomService($id, $service_name, $service_type, $price, $description = '', $status = 'active') {
    global $conn;
    $service_name = mysqli_real_escape_string($conn, $service_name);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "UPDATE custom_services SET 
              service_name = '$service_name', 
              service_type = '$service_type', 
              price = $price, 
              description = '$description',
              status = '$status'
              WHERE id = $id";
    
    return $conn->query($query);
}

function deleteCustomService($id) {
    global $conn;
    $query = "DELETE FROM custom_services WHERE id = $id";
    return $conn->query($query);
}

function toggleCustomServiceStatus($id) {
    global $conn;
    $query = "UPDATE custom_services SET 
              status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END 
              WHERE id = $id";
    return $conn->query($query);
}


// Add these new functions to your existing functions.php file----------------

/**
 * Check if username already exists in the database
 * @param string $username The username to check
 * @param int $exclude_id Optional user ID to exclude from check (for updates)
 * @return bool True if username exists, false otherwise
 */
function isUsernameExists($username, $exclude_id = null) {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $username);
    $query = "SELECT id FROM users WHERE username = '$username'";
    
    // If excluding a specific user ID (for updates)
    if ($exclude_id !== null) {
        $query .= " AND id != " . intval($exclude_id);
    }
    
    $result = $conn->query($query);
    return $result->num_rows > 0;
}

/**
 * Validate username - checks for duplicates and format
 * @param string $username The username to validate
 * @param int $exclude_id Optional user ID to exclude from check (for updates)
 * @return array Array with 'valid' (bool) and 'message' (string)
 */
function validateUsername($username, $exclude_id = null) {
    // Check if username is empty
    if (empty(trim($username))) {
        return ['valid' => false, 'message' => 'Username cannot be empty'];
    }
    
    // Check minimum length
    if (strlen($username) < 3) {
        return ['valid' => false, 'message' => 'Username must be at least 3 characters long'];
    }
    
    // Check maximum length
    if (strlen($username) > 50) {
        return ['valid' => false, 'message' => 'Username cannot be longer than 50 characters'];
    }
    
    // Check for valid characters (alphanumeric, underscore, hyphen only)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return ['valid' => false, 'message' => 'Username can only contain letters, numbers, underscore, and hyphen'];
    }
    
    // Check if username already exists
    if (isUsernameExists($username, $exclude_id)) {
        return ['valid' => false, 'message' => 'Username already exists. Please choose a different username'];
    }
    
    return ['valid' => true, 'message' => 'Username is valid'];
}

/**
 * Safe user creation with validation
 * @param string $username
 * @param string $password
 * @param string $role
 * @param int $staff_id
 * @return array Result array with 'success' (bool) and 'message' (string)
 */
function createUser($username, $password, $role, $staff_id = null) {
    global $conn;
    
    // Validate username
    $validation = validateUsername($username);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }
    
    // Validate password
    if (empty(trim($password))) {
        return ['success' => false, 'message' => 'Password cannot be empty'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
    }
    
    // Validate role
    $valid_roles = ['admin', 'reception', 'doctor', 'lab'];
    if (!in_array($role, $valid_roles)) {
        return ['success' => false, 'message' => 'Invalid role selected'];
    }
    
    // Sanitize inputs
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);
    $role = mysqli_real_escape_string($conn, $role);
    
    // Prepare query
    $query = "INSERT INTO users (username, password, role, staff_id) VALUES ('$username', '$password', '$role', " . 
             ($staff_id ? "'" . intval($staff_id) . "'" : "NULL") . ")";
    
    if ($conn->query($query) === TRUE) {
        return ['success' => true, 'message' => 'User created successfully'];
    } else {
        return ['success' => false, 'message' => 'Error creating user: ' . $conn->error];
    }
}

/**
 * Safe user update with validation
 * @param int $user_id
 * @param string $username
 * @param string $password
 * @param string $role
 * @param int $staff_id
 * @return array Result array with 'success' (bool) and 'message' (string)
 */
function updateUser($user_id, $username, $password, $role, $staff_id = null) {
    global $conn;
    
    // Validate user ID
    $user_id = intval($user_id);
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }
    
    // Validate username (excluding current user)
    $validation = validateUsername($username, $user_id);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }
    
    // Validate password
    if (empty(trim($password))) {
        return ['success' => false, 'message' => 'Password cannot be empty'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
    }
    
    // Validate role
    $valid_roles = ['admin', 'reception', 'doctor', 'lab'];
    if (!in_array($role, $valid_roles)) {
        return ['success' => false, 'message' => 'Invalid role selected'];
    }
    
    // Sanitize inputs
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);
    $role = mysqli_real_escape_string($conn, $role);
    
    // Prepare query
    $query = "UPDATE users SET username = '$username', password = '$password', role = '$role', staff_id = " .
             ($staff_id ? "'" . intval($staff_id) . "'" : "NULL") . " WHERE id = $user_id";
    
    if ($conn->query($query) === TRUE) {
        return ['success' => true, 'message' => 'User updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Error updating user: ' . $conn->error];
    }
}

/**
 * Check if NIC already exists (for patient/staff registration)
 * @param string $nic
 * @param string $table Table name ('patients' or 'staff')
 * @param int $exclude_id Optional ID to exclude from check
 * @return bool
 */
function isNICExists($nic, $table, $exclude_id = null) {
    global $conn;
    
    if (empty(trim($nic))) {
        return false; // Empty NIC is allowed
    }
    
    $nic = mysqli_real_escape_string($conn, $nic);
    $query = "SELECT id FROM $table WHERE nic = '$nic'";
    
    if ($exclude_id !== null) {
        $query .= " AND id != " . intval($exclude_id);
    }
    
    $result = $conn->query($query);
    return $result->num_rows > 0;
}

/**
 * Check if contact number already exists
 * @param string $contact
 * @param string $table Table name ('patients' or 'staff')
 * @param int $exclude_id Optional ID to exclude from check
 * @return bool
 */
function isContactExists($contact, $table, $exclude_id = null) {
    global $conn;
    
    $contact = mysqli_real_escape_string($conn, $contact);
    $query = "SELECT id FROM $table WHERE contact = '$contact' OR phone = '$contact'";
    
    if ($exclude_id !== null) {
        $query .= " AND id != " . intval($exclude_id);
    }
    
    $result = $conn->query($query);
    return $result->num_rows > 0;
}

// Add these functions to the end of your existing functions.php file


?>
