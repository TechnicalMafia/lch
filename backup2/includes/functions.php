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
?>