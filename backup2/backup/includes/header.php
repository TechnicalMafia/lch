<?php
// Get the base path regardless of current directory
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s;
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        .content {
            transition: all 0.3s;
        }
        .content.expanded {
            margin-left: 80px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar w-64 bg-blue-800 text-white no-print">
            <div class="p-4 border-b border-blue-700">
                <h1 class="text-xl font-bold flex items-center">
                    <i class="fas fa-hospital mr-2"></i>
                    <span class="sidebar-text">Hospital System</span>
                </h1>
            </div>
            
            <div class="p-2">
                <a href="../index.php" class="flex items-center p-3 rounded hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="sidebar-text ml-3">Dashboard</span>
                </a>
                
                <?php if (hasRole('admin') || hasRole('reception')): ?>
                <div class="mt-2">
                    <div class="flex items-center p-3 text-blue-300 cursor-pointer" onclick="toggleSubmenu('reception-submenu')">
                        <i class="fas fa-user-plus w-6"></i>
                        <span class="sidebar-text ml-3">Reception</span>
                        <i class="fas fa-chevron-down ml-auto sidebar-text"></i>
                    </div>
                    <div id="reception-submenu" class="ml-6 hidden">
                        <a href="add_patient.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'add_patient.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-user-plus w-5"></i>
                            <span class="sidebar-text ml-2">Add Patient</span>
                        </a>
                        <a href="patient_list.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'patient_list.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-list w-5"></i>
                            <span class="sidebar-text ml-2">Patient List</span>
                        </a>
                        <a href="token.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'token.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-ticket-alt w-5"></i>
                            <span class="sidebar-text ml-2">Generate Token</span>
                        </a>
                        <a href="billing.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'billing.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-file-invoice-dollar w-5"></i>
                            <span class="sidebar-text ml-2">Billing</span>
                        </a>
                        <a href="room_allotment.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'room_allotment.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-bed w-5"></i>
                            <span class="sidebar-text ml-2">Room Allotment</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (hasRole('admin') || hasRole('doctor')): ?>
                <div class="mt-2">
                    <div class="flex items-center p-3 text-blue-300 cursor-pointer" onclick="toggleSubmenu('doctor-submenu')">
                        <i class="fas fa-user-md w-6"></i>
                        <span class="sidebar-text ml-3">Doctor</span>
                        <i class="fas fa-chevron-down ml-auto sidebar-text"></i>
                    </div>
                    <div id="doctor-submenu" class="ml-6 hidden">
                        <a href="../doctor/patient_queue.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'patient_queue.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-users w-5"></i>
                            <span class="sidebar-text ml-2">Patient Queue</span>
                        </a>
                        <a href="../doctor/view_patient.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'view_patient.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-user-injured w-5"></i>
                            <span class="sidebar-text ml-2">View Patient</span>
                        </a>
                        <a href="../doctor/add_comments.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'add_comments.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-comment-medical w-5"></i>
                            <span class="sidebar-text ml-2">Add Comments</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (hasRole('admin') || hasRole('lab')): ?>
                <div class="mt-2">
                    <div class="flex items-center p-3 text-blue-300 cursor-pointer" onclick="toggleSubmenu('lab-submenu')">
                        <i class="fas fa-flask w-6"></i>
                        <span class="sidebar-text ml-3">Lab</span>
                        <i class="fas fa-chevron-down ml-auto sidebar-text"></i>
                    </div>
                    <div id="lab-submenu" class="ml-6 hidden">
                        <a href="../lab/lab_tokens.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'lab_tokens.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-vial w-5"></i>
                            <span class="sidebar-text ml-2">Lab Tokens</span>
                        </a>
                        <a href="../lab/add_report.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'add_report.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-file-medical w-5"></i>
                            <span class="sidebar-text ml-2">Add Report</span>
                        </a>
                        <a href="../lab/view_reports.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'view_reports.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-clipboard-list w-5"></i>
                            <span class="sidebar-text ml-2">View Reports</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (hasRole('admin')): ?>
                <div class="mt-2">
                    <div class="flex items-center p-3 text-blue-300 cursor-pointer" onclick="toggleSubmenu('admin-submenu')">
                        <i class="fas fa-user-shield w-6"></i>
                        <span class="sidebar-text ml-3">Admin</span>
                        <i class="fas fa-chevron-down ml-auto sidebar-text"></i>
                    </div>
                    <div id="admin-submenu" class="ml-6 hidden">
                        <a href="../admin/users.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-users-cog w-5"></i>
                            <span class="sidebar-text ml-2">Manage Users</span>
                        </a>
                        <a href="../admin/staff.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-user-tie w-5"></i>
                            <span class="sidebar-text ml-2">Manage Staff</span>
                        </a>
                        <a href="../admin/pricing.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'pricing.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-tags w-5"></i>
                            <span class="sidebar-text ml-2">Manage Pricing</span>
                        </a>
                        <a href="../admin/reports.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-blue-700' : ''; ?>">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span class="sidebar-text ml-2">Reports</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="absolute bottom-0 w-full p-4 border-t border-blue-700">
                <a href="../logout.php" class="flex items-center p-3 rounded hover:bg-blue-700">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span class="sidebar-text ml-3">Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div id="content" class="content flex-1 flex flex-col overflow-hidden">
            <!-- Top Navbar -->
            <nav class="bg-white shadow-sm no-print">
                <div class="px-4 py-3 flex items-center justify-between">
                    <button id="toggle-sidebar" class="text-gray-600 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="text-gray-600 focus:outline-none">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                            </button>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                            </div>
                            <div class="ml-2">
                                <div class="text-sm font-medium"><?php echo $_SESSION['username']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo ucfirst($_SESSION['role']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto p-6">