<?php
include_once __DIR__ . '/../config/db_connect.php';

// Fetch branches
$branches = [];
if ($_SESSION['role_name'] === 'super_admin') {
    $branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
    $branch_result = mysqli_query($conn, $branch_sql);
} else {
    $branch_id = intval($_SESSION['branch_id']);
    $branch_sql = "SELECT id, branch_name FROM branches WHERE id = $branch_id AND deleted_at IS NULL";
    $branch_result = mysqli_query($conn, $branch_sql);
}
if ($branch_result) {
    while ($row = mysqli_fetch_assoc($branch_result)) {
        $branches[] = $row;
    }
}

// Handle branch selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_branch_id'])) {
    $_SESSION['selected_branch_id'] = intval($_POST['selected_branch_id']);
    // Optionally redirect to the same page to avoid resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
$selected_branch_id = $_SESSION['selected_branch_id'] ?? ($branches[0]['id'] ?? null);

// Fetch fuel types
$fuel_types = [];
$fuel_sql = "SELECT id, name FROM fuel_types WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name";
$fuel_result = mysqli_query($conn, $fuel_sql);
if ($fuel_result) {
    while ($row = mysqli_fetch_assoc($fuel_result)) {
        $fuel_types[] = $row;
    }
}

// Fetch roles
$roles = [];
$role_sql = "SELECT id, display_name FROM roles WHERE deleted_at IS NULL ORDER BY display_name";
$role_result = mysqli_query($conn, $role_sql);
if ($role_result) {
    while ($row = mysqli_fetch_assoc($role_result)) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Station Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            width: var(--sidebar-width);
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        
        .sidebar.collapsed .brand-text {
            display: none;
        }
        
        .sidebar.collapsed .dropdown-toggle::after {
            display: none;
        }
        
        .sidebar.collapsed .user-info {
            display: none;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
        }
        
        .main-content.collapsed {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .sidebar-text {
            white-space: nowrap;
        }
        
        .toggle-btn {
            position: absolute;
            top: 20px;
            right: -15px;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            z-index: 1001;
        }
        
        .toggle-btn:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        }
        
        .nav-link {
            border-radius: 12px;
            margin: 2px 0;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .dropdown-toggle::after {
            /* Remove transition */
        }
        
        .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }
        
        .sidebar-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .menu-section {
            margin-bottom: 0.5rem;
        }
        
        .menu-icon {
            width: 20px;
            text-align: center;
            margin-right: 12px;
        }
        
        .submenu-item {
            position: relative;
            padding-left: 2.5rem;
        }
        
        .submenu-item::before {
            content: '';
            position: absolute;
            left: 1.5rem;
            top: 50%;
            width: 6px;
            height: 6px;
            background: #64748b;
            border-radius: 50%;
            transform: translateY(-50%);
        }
        
        .submenu-item:hover::before {
            background: #3b82f6;
        }
        
        .user-profile {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .brand-logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #64748b 50%, transparent 100%);
            margin: 1.5rem 0;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .logout-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        
        .page-content {
            padding: 2rem;
        }
        
        .demo-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -280px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
            
            .main-content.show {
                margin-left: 0;
            }
        }
        
        /* Tooltip styles for collapsed sidebar */
        .tooltip-container {
            position: relative;
        }
        
        .tooltip-text {
            visibility: hidden;
            width: 160px;
            background-color: #1f2937;
            color: white;
            text-align: center;
            border-radius: 8px;
            padding: 8px;
            position: absolute;
            z-index: 1002;
            left: 120%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            font-size: 0.875rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .tooltip-text::after {
            content: "";
            position: absolute;
            top: 50%;
            right: 100%;
            margin-top: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: transparent #1f2937 transparent transparent;
        }
        
        .sidebar.collapsed .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Scrollbar styling */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #1d4ed8, #7c3aed);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column p-3" id="sidebar">
            <!-- Toggle Button -->
            <button class="toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Header -->
            <div class="sidebar-header text-center py-12">
                <div class="brand-logo d-flex align-items-center justify-content-center mb-3">
                    <i class="fas fa-gas-pump me-3 fs-4"></i>
                    <span class="brand-text sidebar-text fw-bold fs-5">Fuel Station</span>
                </div>
                <small class="text-muted sidebar-text fw-light">Management System</small>
                <form method="post" class="mt-3">
                    <select name="selected_branch_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>" <?php if ($selected_branch_id == $branch['id']) echo 'selected'; ?>><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="flex-grow-1">
                <ul class="nav nav-pills flex-column mb-auto">
                    
                    <!-- Dashboard -->
                    <div class="divider"></div>
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <a class="nav-link text-white d-flex align-items-center py-2 active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt menu-icon text-blue-400"></i>
                                <span class="sidebar-text">Dashboard</span>
                            </a>
                            <span class="tooltip-text">Dashboard</span>
                        </div>
                    </li>
                    <div class="divider"></div>
                    
                    <!-- User Management -->
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <div class="nav-link text-white d-flex align-items-center py-2 w-100">
                                <i class="fas fa-users menu-icon text-blue-400"></i>
                                <span class="sidebar-text">User Management</span>
                            </div>
                            <span class="tooltip-text">User Management</span>
                        </div>
                        <ul class="list-unstyled ps-3">
                            <li><a class="nav-link text-white submenu-item py-2" href="user.php"><span class="sidebar-text">Users</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="manage_roles.php"><span class="sidebar-text">Roles</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="manage_permissions.php"><span class="sidebar-text">Permissions</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="manage_role_permissions.php"><span class="sidebar-text">Role Permissions</span></a></li>
                            <!-- Dynamic Roles -->
                            <?php foreach ($roles as $role): ?>
                                <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text"><?php echo htmlspecialchars($role['display_name']); ?></span></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <div class="divider"></div>
                    
                    <!-- Business & Branches -->
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <div class="nav-link text-white d-flex align-items-center py-2 w-100">
                                <i class="fas fa-building menu-icon text-green-400"></i>
                                <span class="sidebar-text">Branches</span>
                        </div>
                            <span class="tooltip-text">Branches</span>
                        </div>
                        <ul class="list-unstyled ps-3">
                            <?php foreach ($branches as $branch): ?>
                                <li><a class="nav-link text-white submenu-item py-2" href="branch_dashboard.php?branch_id=<?php echo $branch['id']; ?>">
                                    <span class="sidebar-text"><?php echo htmlspecialchars($branch['branch_name']); ?></span>
                                </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <div class="divider"></div>
                    
                    <!-- Inventory -->
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <div class="nav-link text-white d-flex align-items-center py-2 w-100">
                                <i class="fas fa-gas-pump menu-icon text-yellow-500"></i>
                                <span class="sidebar-text">Fuel Types</span>
                            </div>
                            <span class="tooltip-text">Fuel Types</span>
                        </div>
                        <ul class="list-unstyled ps-3">
                            <?php foreach ($fuel_types as $fuel): ?>
                                <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text"><?php echo htmlspecialchars($fuel['name']); ?></span></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <div class="divider"></div>
                    
                    <!-- Financial -->
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <div class="nav-link text-white d-flex align-items-center py-2 w-100">
                                <i class="fas fa-chart-line menu-icon text-emerald-400"></i>
                                <span class="sidebar-text">Financial</span>
                            </div>
                            <span class="tooltip-text">Financial</span>
                        </div>
                        <ul class="list-unstyled ps-3">
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Daily Sales Summary</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Expenses</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Cash Float</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Bank Reconciliation</span></a></li>
                        </ul>
                    </li>
                    <div class="divider"></div>
                    <!-- Shift Management -->
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <div class="nav-link text-white d-flex align-items-center py-2 w-100">
                                <i class="fas fa-clock menu-icon text-orange-400"></i>
                                <span class="sidebar-text">Shift Management</span>
                            </div>
                            <span class="tooltip-text">Shift Management</span>
                        </div>
                        <ul class="list-unstyled ps-3">
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Shifts</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Shift Assignments</span></a></li>
                        </ul>
                    </li>
                    <div class="divider"></div>
                    <!-- Maintenance & Compliance -->
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <div class="nav-link text-white d-flex align-items-center py-2 w-100">
                                <i class="fas fa-tools menu-icon text-red-400"></i>
                                <span class="sidebar-text">Maintenance & Compliance</span>
                            </div>
                            <span class="tooltip-text">Maintenance & Compliance</span>
                        </div>
                        <ul class="list-unstyled ps-3">
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Equipment Maintenance</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Regulatory Compliance</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Fuel Quality Tests</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Safety Incidents</span></a></li>
                        </ul>
                    </li>
                    <div class="divider"></div>
                    <!-- Reporting & Analytics -->
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <div class="nav-link text-white d-flex align-items-center py-2 w-100">
                                <i class="fas fa-chart-pie menu-icon text-indigo-400"></i>
                                <span class="sidebar-text">Reporting & Analytics</span>
                            </div>
                            <span class="tooltip-text">Reporting & Analytics</span>
                        </div>
                        <ul class="list-unstyled ps-3">
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Reports</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Outstanding Credit</span></a></li>
                        </ul>
                    </li>
                    <div class="divider"></div>
                    <!-- System -->
                    <li class="nav-item menu-section">
                        <div class="tooltip-container">
                            <div class="nav-link text-white d-flex align-items-center py-2 w-100">
                                <i class="fas fa-cog menu-icon text-gray-400"></i>
                                <span class="sidebar-text">System</span>
                            </div>
                            <span class="tooltip-text">System</span>
                        </div>
                        <ul class="list-unstyled ps-3">
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">System Settings</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Audit Logs</span></a></li>
                            <li><a class="nav-link text-white submenu-item py-2" href="#"><span class="sidebar-text">Notifications</span></a></li>
                        </ul>
                    </li>
                    <div class="divider"></div>
                </ul>
            </nav>
            
            <!-- Divider -->
            <div class="divider"></div>
            
            <!-- Logout Button at the end -->
            <div class="mt-auto mb-4 px-3">
                <a href="logout.php" class="btn btn-danger w-100 d-flex align-items-center justify-content-center logout-btn">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            <span class="sidebar-text">Logout</span>
                        </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleIcon = sidebarToggle.querySelector('i');
            
            // Load saved state from localStorage (if available)
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('collapsed');
                toggleIcon.classList.remove('fa-bars');
                toggleIcon.classList.add('fa-chevron-right');
            }
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');
                
                // Update toggle icon
                if (sidebar.classList.contains('collapsed')) {
                    toggleIcon.classList.remove('fa-bars');
                    toggleIcon.classList.add('fa-chevron-right');
                    localStorage.setItem('sidebarCollapsed', 'true');
                } else {
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-bars');
                    localStorage.setItem('sidebarCollapsed', 'false');
                }
            });
            
            // Close mobile sidebar when clicking on main content
            mainContent.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('show');
                    mainContent.classList.remove('show');
                }
            });
            
            // Handle mobile responsiveness
            function handleResize() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('collapsed');
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-bars');
                }
            }
            
            window.addEventListener('resize', handleResize);
            
            // Mobile toggle functionality
            if (window.innerWidth <= 768) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    mainContent.classList.toggle('show');
                });
            }
            
            // Add click handlers for navigation links
            const navLinks = document.querySelectorAll('.nav-link:not(.dropdown-toggle)');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // For demo purposes, prevent default navigation
                    e.preventDefault();
                    
                    // You can add your actual navigation logic here
                    console.log('Navigating to:', this.getAttribute('href'));
                });
            });
            
            // Auto-collapse dropdowns when sidebar is collapsed
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const target = mutation.target;
                        if (target.classList.contains('collapsed')) {
                            // Close all open dropdowns
                            const openDropdowns = document.querySelectorAll('.collapse.show');
                            openDropdowns.forEach(dropdown => {
                                const bsCollapse = new bootstrap.Collapse(dropdown, {
                                    toggle: false
                                });
                                bsCollapse.hide();
                            });
                        }
                    }
                });
            });
            
            observer.observe(sidebar, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>
</body>
</html>