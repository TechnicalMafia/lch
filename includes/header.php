<?php
// Get the base path regardless of current directory
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/db.php';

// Define the base URL for the project
$baseURL = '/lch'; // Change this to match your project folder name
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
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        .content {
            transition: all 0.3s;
            margin-left: 256px; /* 64 * 4 = 256px (w-64) */
            min-height: 100vh;
        }
        .content.expanded {
            margin-left: 80px;
        }
        
        /* Submenu styling */
        .submenu {
            display: none;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .submenu.show {
            display: block;
        }
        
        /* Menu header cursor */
        .menu-header {
            cursor: pointer;
        }
        .menu-header:hover {
            background-color: rgb(29, 78, 216); /* hover:bg-blue-700 */
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
        }
        
        /* Prevent sidebar toggle conflicts */
        .no-sidebar-trigger {
            pointer-events: auto !important;
            z-index: 1001;
        }
        
        /* Responsive design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar w-64 bg-blue-800 text-white no-print">
            <!-- Sidebar Content Container -->
            <div class="flex flex-col h-full">
                <!-- Sidebar Header -->
                <div class="p-4 border-b border-blue-700 flex-shrink-0">
                    <h1 class="text-xl font-bold flex items-center">
                        <i class="fas fa-hospital mr-2"></i>
                        <span class="sidebar-text">Hospital System</span>
                    </h1>
                </div>
                
                <!-- Sidebar Navigation - Scrollable Area -->
                <div class="flex-1 overflow-y-auto p-2">
                    <a href="<?php echo $baseURL; ?>/index.php" class="flex items-center p-3 rounded hover:bg-blue-700 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-700' : ''; ?>">
                        <i class="fas fa-tachometer-alt w-6"></i>
                        <span class="sidebar-text ml-3">Dashboard</span>
                    </a>
                    
                    <?php if (hasRole('admin') || hasRole('reception')): ?>
                    <div class="mt-2">
                        <div class="menu-header flex items-center p-3 text-blue-300 rounded" data-submenu="reception-submenu">
                            <i class="fas fa-user-plus w-6"></i>
                            <span class="sidebar-text ml-3">Reception</span>
                            <i class="fas fa-chevron-down ml-auto sidebar-text submenu-chevron"></i>
                        </div>
                        <div id="reception-submenu" class="submenu ml-6">
                            <a href="<?php echo $baseURL; ?>/modules/reception/add_patient.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'add_patient.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-user-plus w-5"></i>
                                <span class="sidebar-text ml-2">Add Patient</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/reception/patient_list.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'patient_list.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-list w-5"></i>
                                <span class="sidebar-text ml-2">Patient List</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/reception/token.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'token.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-ticket-alt w-5"></i>
                                <span class="sidebar-text ml-2">Generate Token</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/reception/billing.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'billing.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-file-invoice-dollar w-5"></i>
                                <span class="sidebar-text ml-2">Billing</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/reception/room_allotment.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'room_allotment.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-bed w-5"></i>
                                <span class="sidebar-text ml-2">Room Allotment</span>
                            </a>
                            <!-- Birth & Death Records Menu Item -->
                            <a href="<?php echo $baseURL; ?>/modules/reception/birth_death_records.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo in_array(basename($_SERVER['PHP_SELF']), ['birth_death_records.php', 'add_birth_record.php', 'add_death_record.php', 'view_birth_records.php', 'view_death_records.php', 'birth_certificates.php', 'death_certificates.php', 'birth_record_details.php', 'death_record_details.php']) ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-heart w-5"></i>
                                <span class="sidebar-text ml-2">Birth & Death Records</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasRole('admin') || hasRole('doctor')): ?>
                    <div class="mt-2">
                        <div class="menu-header flex items-center p-3 text-blue-300 rounded" data-submenu="doctor-submenu">
                            <i class="fas fa-user-md w-6"></i>
                            <span class="sidebar-text ml-3">Doctor</span>
                            <i class="fas fa-chevron-down ml-auto sidebar-text submenu-chevron"></i>
                        </div>
                        <div id="doctor-submenu" class="submenu ml-6">
                            <a href="<?php echo $baseURL; ?>/modules/doctor/patient_queue.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'patient_queue.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-users w-5"></i>
                                <span class="sidebar-text ml-2">Patient Queue</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/doctor/view_patient.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'view_patient.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-user-injured w-5"></i>
                                <span class="sidebar-text ml-2">View Patient</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/doctor/add_comments.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'add_comments.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-comment-medical w-5"></i>
                                <span class="sidebar-text ml-2">Add Comments</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasRole('admin') || hasRole('lab')): ?>
                    <div class="mt-2">
                        <div class="menu-header flex items-center p-3 text-blue-300 rounded" data-submenu="lab-submenu">
                            <i class="fas fa-flask w-6"></i>
                            <span class="sidebar-text ml-3">Lab</span>
                            <i class="fas fa-chevron-down ml-auto sidebar-text submenu-chevron"></i>
                        </div>
                        <div id="lab-submenu" class="submenu ml-6">
                            <a href="<?php echo $baseURL; ?>/modules/lab/lab_tokens.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'lab_tokens.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-vial w-5"></i>
                                <span class="sidebar-text ml-2">Lab Tokens</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/lab/add_report.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'add_report.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-file-medical w-5"></i>
                                <span class="sidebar-text ml-2">Add Report</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/lab/view_reports.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'view_reports.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-clipboard-list w-5"></i>
                                <span class="sidebar-text ml-2">View Reports</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasRole('admin')): ?>
                    <div class="mt-2">
                        <div class="menu-header flex items-center p-3 text-blue-300 rounded" data-submenu="admin-submenu">
                            <i class="fas fa-user-shield w-6"></i>
                            <span class="sidebar-text ml-3">Admin</span>
                            <i class="fas fa-chevron-down ml-auto sidebar-text submenu-chevron"></i>
                        </div>
                        <div id="admin-submenu" class="submenu ml-6">
                            <a href="<?php echo $baseURL; ?>/modules/admin/users.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-users-cog w-5"></i>
                                <span class="sidebar-text ml-2">Manage Users</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/admin/staff.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-user-tie w-5"></i>
                                <span class="sidebar-text ml-2">Manage Staff</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/admin/pricing.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'pricing.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-tags w-5"></i>
                                <span class="sidebar-text ml-2">Manage Pricing</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/modules/admin/reports.php" class="flex items-center p-2 rounded hover:bg-blue-700 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-blue-700' : ''; ?>">
                                <i class="fas fa-chart-bar w-5"></i>
                                <span class="sidebar-text ml-2">Reports</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Logout Button - Fixed at Bottom -->
                <div class="border-t border-blue-700 p-4 flex-shrink-0 bg-blue-800">
                    <a href="<?php echo $baseURL; ?>/logout.php" class="flex items-center p-3 rounded hover:bg-blue-700 no-sidebar-trigger">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span class="sidebar-text ml-3">Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div id="content" class="content flex-1 flex flex-col">
            <!-- Top Navbar -->
            <nav class="bg-white shadow-sm no-print sticky top-0 z-50">
                <div class="px-4 py-3 flex items-center justify-between">
                    <button id="toggle-sidebar" class="text-gray-600 focus:outline-none no-sidebar-trigger">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="text-gray-600 focus:outline-none no-sidebar-trigger">
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
            <div class="flex-1 p-6">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle submenu toggles using event delegation
    document.addEventListener('click', function(e) {
        const menuHeader = e.target.closest('.menu-header');
        if (menuHeader) {
            e.preventDefault();
            e.stopPropagation();
            
            const submenuId = menuHeader.getAttribute('data-submenu');
            const submenu = document.getElementById(submenuId);
            const chevron = menuHeader.querySelector('.submenu-chevron');
            
            if (submenu && chevron) {
                // Toggle submenu visibility
                submenu.classList.toggle('show');
                
                // Toggle chevron direction
                if (submenu.classList.contains('show')) {
                    chevron.classList.remove('fa-chevron-down');
                    chevron.classList.add('fa-chevron-up');
                } else {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            }
        }
    });

    // Initialize submenus based on current page
    const activeSubmenus = ['reception-submenu', 'doctor-submenu', 'lab-submenu', 'admin-submenu'];
    activeSubmenus.forEach(function(submenuId) {
        const submenu = document.getElementById(submenuId);
        if (submenu) {
            // Check if any child has active class
            const activeChild = submenu.querySelector('.bg-blue-700');
            if (activeChild) {
                submenu.classList.add('show');
                // Update chevron for active submenu
                const menuHeader = document.querySelector(`[data-submenu="${submenuId}"]`);
                if (menuHeader) {
                    const chevron = menuHeader.querySelector('.submenu-chevron');
                    if (chevron) {
                        chevron.classList.remove('fa-chevron-down');
                        chevron.classList.add('fa-chevron-up');
                    }
                }
            }
        }
    });

    // Toggle sidebar functionality
    const toggleSidebarBtn = document.getElementById('toggle-sidebar');
    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            
            if (window.innerWidth <= 1024) {
                sidebar.classList.toggle('open');
            } else {
                // Desktop behavior
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            }
        });
    }

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggle-sidebar');
        
        // Check if click is outside sidebar and not on toggle button
        if (window.innerWidth <= 1024 && 
            sidebar && toggleBtn &&
            !sidebar.contains(event.target) && 
            !toggleBtn.contains(event.target) &&
            !event.target.classList.contains('no-sidebar-trigger')) {
            sidebar.classList.remove('open');
        }
    });

    // Handle responsive sidebar
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        
        if (sidebar && content && window.innerWidth > 1024) {
            sidebar.classList.remove('open');
            // Reset to normal state on desktop
            if (sidebar.classList.contains('collapsed')) {
                content.classList.add('expanded');
            } else {
                content.classList.remove('expanded');
            }
        }
    });
});
</script>