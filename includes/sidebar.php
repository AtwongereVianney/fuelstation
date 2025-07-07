<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection if not already included
if (!isset($conn)) {
    include_once __DIR__ . '/../config/db_connect.php';
}

// Fetch branches
$branches = [];
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'super_admin') {
    $branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
    $branch_result = mysqli_query($conn, $branch_sql);
} else {
    $branch_id = intval($_SESSION['branch_id'] ?? 0);
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

<style>
:root {
    --sidebar-width: 280px;
    --sidebar-collapsed-width: 70px;
}

.sidebar {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
    position: fixed !important;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
    width: var(--sidebar-width);
    overflow-x: hidden;
    overflow-y: auto;
    transition: width 0.3s ease;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
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

body {
    margin-left: var(--sidebar-width);
    transition: margin-left 0.3s ease;
    /* Fixed compatibility issues */
    text-align: -webkit-match-parent;
    text-align: match-parent;
    text-align: inherit;
    text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%;
    color-adjust: exact;
    print-color-adjust: exact;
}

body.sidebar-collapsed {
    margin-left: var(--sidebar-collapsed-width);
}

.sidebar-text {
    white-space: nowrap;
}

.toggle-btn {
    position: fixed;
    top: 20px;
    left: calc(var(--sidebar-width) - 20px);
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
    transition: left 0.3s ease;
}

.toggle-btn:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
}

.toggle-btn:focus {
    outline: 2px solid #ffffff;
    outline-offset: 2px;
}

.sidebar.collapsed + .toggle-btn,
body.sidebar-collapsed .toggle-btn {
    left: calc(var(--sidebar-collapsed-width) - 20px);
}

.nav-link {
    border-radius: 12px;
    margin: 2px 0;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.nav-link.active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.nav-link:focus {
    outline: 2px solid #ffffff;
    outline-offset: 2px;
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

.logout-btn {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.logout-btn:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

.logout-btn:focus {
    outline: 2px solid #ffffff;
    outline-offset: 2px;
}

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
    z-index: 100000;
    left: 120%;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0;
    font-size: 0.875rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    transition: opacity 0.3s ease;
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

@media (max-width: 768px) {
    body {
        margin-left: 0;
    }
    
    .sidebar {
        left: -100%;
        transition: transform 0.3s ease;
    }
    
    .sidebar.show {
        left: 0;
    }
    
    .toggle-btn {
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 100000;
    }
}
</style>

<!-- Sidebar Navigation -->
<nav class="sidebar d-flex flex-column p-3" id="sidebar" role="navigation" aria-label="Main navigation">
    <!-- Toggle Button -->
    <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar navigation" title="Toggle sidebar">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    
    <!-- Header -->
    <div class="sidebar-header text-center">
        <div class="brand-logo d-flex align-items-center justify-content-center mb-3">
            <i class="fas fa-gas-pump me-3 fs-4" aria-hidden="true"></i>
            <span class="brand-text sidebar-text fw-bold fs-5">Fuel Station</span>
        </div>
        <small class="text-muted sidebar-text fw-light">Management System</small>
        <?php if (!empty($branches)): ?>
        <form method="post" class="mt-3">
            <label for="branchSelect" class="visually-hidden">Select Branch</label>
            <select 
                name="selected_branch_id" 
                id="branchSelect"
                class="form-select form-select-sm" 
                onchange="this.form.submit()"
                title="Select branch to manage"
                aria-label="Select branch to manage"
                autocomplete="off"
            >
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo htmlspecialchars($branch['id']); ?>" 
                            <?php if ($selected_branch_id == $branch['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>
    
    <!-- Navigation Menu -->
    <div class="flex-grow-1">
        <ul class="nav nav-pills flex-column mb-auto" role="menubar">
            
            <!-- Dashboard -->
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <a class="nav-link text-white d-flex align-items-center py-2" 
                       href="dashboard.php" 
                       role="menuitem"
                       aria-label="Dashboard"
                       title="Go to dashboard">
                        <i class="fas fa-tachometer-alt menu-icon text-blue-400" aria-hidden="true"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                    <span class="tooltip-text" aria-hidden="true">Dashboard</span>
                </div>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            
            <!-- User Management -->
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <div class="nav-link text-white d-flex align-items-center py-2 w-100" 
                         role="menuitem" 
                         aria-label="User Management"
                         title="User Management">
                        <i class="fas fa-users menu-icon text-blue-400" aria-hidden="true"></i>
                        <span class="sidebar-text">User Management</span>
                    </div>
                    <span class="tooltip-text" aria-hidden="true">User Management</span>
                </div>
                <ul class="list-unstyled ps-3" role="menu" aria-label="User Management submenu">
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="user.php" 
                           role="menuitem"
                           aria-label="Manage Users"
                           title="Manage Users">
                            <span class="sidebar-text">Users</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="manage_roles.php" 
                           role="menuitem"
                           aria-label="Manage Roles"
                           title="Manage Roles">
                            <span class="sidebar-text">Roles</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="manage_permissions.php" 
                           role="menuitem"
                           aria-label="Manage Permissions"
                           title="Manage Permissions">
                            <span class="sidebar-text">Permissions</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="manage_role_permissions.php" 
                           role="menuitem"
                           aria-label="Manage Role Permissions"
                           title="Manage Role Permissions">
                            <span class="sidebar-text">Role Permissions</span>
                        </a>
                    </li>
                    <!-- Dynamic Roles -->
                    <?php if (!empty($roles)): ?>
                        <?php foreach ($roles as $role): ?>
                            <li role="none">
                                <a class="nav-link text-white submenu-item py-2" 
                                   href="#" 
                                   role="menuitem"
                                   aria-label="<?php echo htmlspecialchars($role['display_name']); ?>"
                                   title="<?php echo htmlspecialchars($role['display_name']); ?>">
                                    <span class="sidebar-text"><?php echo htmlspecialchars($role['display_name']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            
            <!-- Business & Branches -->
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <div class="nav-link text-white d-flex align-items-center py-2 w-100" 
                         role="menuitem" 
                         aria-label="Branches"
                         title="Branches">
                        <i class="fas fa-building menu-icon text-green-400" aria-hidden="true"></i>
                        <span class="sidebar-text">Branches</span>
                    </div>
                    <span class="tooltip-text" aria-hidden="true">Branches</span>
                </div>
                <ul class="list-unstyled ps-3" role="menu" aria-label="Branches submenu">
                    <?php if (!empty($branches)): ?>
                        <?php foreach ($branches as $branch): ?>
                            <li role="none">
                                <a class="nav-link text-white submenu-item py-2" 
                                   href="branch_dashboard.php?branch_id=<?php echo htmlspecialchars($branch['id']); ?>"
                                   role="menuitem"
                                   aria-label="<?php echo htmlspecialchars($branch['branch_name']); ?> Dashboard"
                                   title="<?php echo htmlspecialchars($branch['branch_name']); ?> Dashboard">
                                    <span class="sidebar-text"><?php echo htmlspecialchars($branch['branch_name']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            
            <!-- Inventory -->
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <div class="nav-link text-white d-flex align-items-center py-2 w-100" 
                         role="menuitem" 
                         aria-label="Fuel Types"
                         title="Fuel Types">
                        <i class="fas fa-gas-pump menu-icon text-yellow-500" aria-hidden="true"></i>
                        <span class="sidebar-text">Fuel Types</span>
                    </div>
                    <span class="tooltip-text" aria-hidden="true">Fuel Types</span>
                </div>
                <ul class="list-unstyled ps-3" role="menu" aria-label="Fuel Types submenu">
                    <?php if (!empty($fuel_types)): ?>
                        <?php foreach ($fuel_types as $fuel): ?>
                            <li role="none">
                                <a class="nav-link text-white submenu-item py-2" 
                                   href="fuel_type_info.php?fuel_type_id=<?php echo htmlspecialchars($fuel['id']); ?>" 
                                   role="menuitem"
                                   aria-label="<?php echo htmlspecialchars($fuel['name']); ?>"
                                   title="<?php echo htmlspecialchars($fuel['name']); ?>">
                                    <span class="sidebar-text"><?php echo htmlspecialchars($fuel['name']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            
            <!-- Financial -->
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <div class="nav-link text-white d-flex align-items-center py-2 w-100" 
                         role="menuitem" 
                         aria-label="Financial"
                         title="Financial">
                        <i class="fas fa-chart-line menu-icon text-emerald-400" aria-hidden="true"></i>
                        <span class="sidebar-text">Financial</span>
                    </div>
                    <span class="tooltip-text" aria-hidden="true">Financial</span>
                </div>
                <ul class="list-unstyled ps-3" role="menu" aria-label="Financial submenu">
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="daily_sales_summary.php" 
                           role="menuitem"
                           aria-label="Daily Sales Summary"
                           title="Daily Sales Summary">
                            <span class="sidebar-text">Daily Sales Summary</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="expenses.php" 
                           role="menuitem"
                           aria-label="Expenses"
                           title="Expenses">
                            <span class="sidebar-text">Expenses</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Cash Float"
                           title="Cash Float">
                            <span class="sidebar-text">Cash Float</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Bank Reconciliation"
                           title="Bank Reconciliation">
                            <span class="sidebar-text">Bank Reconciliation</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            
            <!-- Shift Management -->
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <div class="nav-link text-white d-flex align-items-center py-2 w-100" 
                         role="menuitem" 
                         aria-label="Shift Management"
                         title="Shift Management">
                        <i class="fas fa-clock menu-icon text-orange-400" aria-hidden="true"></i>
                        <span class="sidebar-text">Shift Management</span>
                    </div>
                    <span class="tooltip-text" aria-hidden="true">Shift Management</span>
                </div>
                <ul class="list-unstyled ps-3" role="menu" aria-label="Shift Management submenu">
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="shifts.php" 
                           role="menuitem"
                           aria-label="Shifts"
                           title="Shifts">
                            <span class="sidebar-text">Shifts</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="shift_assignments.php" 
                           role="menuitem"
                           aria-label="Shift Assignments"
                           title="Shift Assignments">
                            <span class="sidebar-text">Shift Assignments</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            
            <!-- Maintenance & Compliance -->
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <div class="nav-link text-white d-flex align-items-center py-2 w-100" 
                         role="menuitem" 
                         aria-label="Maintenance & Compliance"
                         title="Maintenance & Compliance">
                        <i class="fas fa-tools menu-icon text-red-400" aria-hidden="true"></i>
                        <span class="sidebar-text">Maintenance & Compliance</span>
                    </div>
                    <span class="tooltip-text" aria-hidden="true">Maintenance & Compliance</span>
                </div>
                <ul class="list-unstyled ps-3" role="menu" aria-label="Maintenance & Compliance submenu">
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Equipment Maintenance"
                           title="Equipment Maintenance">
                            <span class="sidebar-text">Equipment Maintenance</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Regulatory Compliance"
                           title="Regulatory Compliance">
                            <span class="sidebar-text">Regulatory Compliance</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Fuel Quality Tests"
                           title="Fuel Quality Tests">
                            <span class="sidebar-text">Fuel Quality Tests</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Safety Incidents"
                           title="Safety Incidents">
                            <span class="sidebar-text">Safety Incidents</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            
            <!-- Reporting & Analytics -->
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <div class="nav-link text-white d-flex align-items-center py-2 w-100" 
                         role="menuitem" 
                         aria-label="Reporting & Analytics"
                         title="Reporting & Analytics">
                        <i class="fas fa-chart-pie menu-icon text-indigo-400" aria-hidden="true"></i>
                        <span class="sidebar-text">Reporting & Analytics</span>
                    </div>
                    <span class="tooltip-text" aria-hidden="true">Reporting & Analytics</span>
                </div>
                <ul class="list-unstyled ps-3" role="menu" aria-label="Reporting & Analytics submenu">
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Reports"
                           title="Reports">
                            <span class="sidebar-text">Reports</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Outstanding Credit"
                           title="Outstanding Credit">
                            <span class="sidebar-text">Outstanding Credit</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
            
            <!-- System -->
            <li class="nav-item menu-section" role="none">
                <div class="tooltip-container">
                    <div class="nav-link text-white d-flex align-items-center py-2 w-100" 
                         role="menuitem" 
                         aria-label="System"
                         title="System">
                        <i class="fas fa-cog menu-icon text-gray-400" aria-hidden="true"></i>
                        <span class="sidebar-text">System</span>
                    </div>
                    <span class="tooltip-text" aria-hidden="true">System</span>
                </div>
                <ul class="list-unstyled ps-3" role="menu" aria-label="System submenu">
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="System Settings"
                           title="System Settings">
                            <span class="sidebar-text">System Settings</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Audit Logs"
                           title="Audit Logs">
                            <span class="sidebar-text">Audit Logs</span>
                        </a>
                    </li>
                    <li role="none">
                        <a class="nav-link text-white submenu-item py-2" 
                           href="#" 
                           role="menuitem"
                           aria-label="Notifications"
                           title="Notifications">
                            <span class="sidebar-text">Notifications</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li role="none">
                <div class="divider" aria-hidden="true"></div>
            </li>
        </ul>
    </div>
    
    <!-- Divider -->
    <div class="divider" aria-hidden="true"></div>
    
    <!-- Logout Button -->
    <div class="mt-auto mb-4">
        <a href="logout.php" 
           class="btn btn-danger w-100 d-flex align-items-center justify-content-center logout-btn"
           aria-label="Logout from system"
           title="Logout from system">
            <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const toggleIcon = sidebarToggle.querySelector('i');
    
    // Load saved state from localStorage
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
        toggleIcon.classList.remove('fa-bars');
        toggleIcon.classList.add('fa-chevron-right');
        sidebarToggle.setAttribute('aria-label', 'Expand sidebar navigation');
        sidebarToggle.setAttribute('title', 'Expand sidebar');
    }
    
    // Toggle functionality
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        document.body.classList.toggle('sidebar-collapsed');
        
        // Update toggle icon and accessibility attributes
        if (sidebar.classList.contains('collapsed')) {
            toggleIcon.classList.remove('fa-bars');
            toggleIcon.classList.add('fa-chevron-right');
            sidebarToggle.setAttribute('aria-label', 'Expand sidebar navigation');
            sidebarToggle.setAttribute('title', 'Expand sidebar');
            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-bars');
            sidebarToggle.setAttribute('aria-label', 'Collapse sidebar navigation');
            sidebarToggle.setAttribute('title', 'Collapse sidebar');
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    });
    
    // Handle keyboard navigation
    sidebarToggle.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            sidebarToggle.click();
        }
    });
    
    // Handle mobile responsiveness
    function handleResize() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-collapsed');
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-bars');
            sidebarToggle.setAttribute('aria-label', 'Toggle sidebar navigation');
            sidebarToggle.setAttribute('title', 'Toggle sidebar');
        } else {
            // Restore saved state on desktop
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
                toggleIcon.classList.remove('fa-bars');
                toggleIcon.classList.add('fa-chevron-right');
                sidebarToggle.setAttribute('aria-label', 'Expand sidebar navigation');
                sidebarToggle.setAttribute('title', 'Expand sidebar');
            }
        }
    }
    
    window.addEventListener('resize', handleResize);
    
    // Mobile toggle functionality
    if (window.innerWidth <= 768) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
            
            // Update ARIA attributes for mobile
            const isOpen = sidebar.classList.contains('show');
            sidebar.setAttribute('aria-hidden', !isOpen);
            sidebarToggle.setAttribute('aria-expanded', isOpen);
        });
        
        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
                sidebar.setAttribute('aria-hidden', 'true');
                sidebarToggle.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Handle escape key to close mobile sidebar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                sidebar.setAttribute('aria-hidden', 'true');
                sidebarToggle.setAttribute('aria-expanded', 'false');
                sidebarToggle.focus();
            }
        });
    }
    
    // Set active link based on current page
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link[href]');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'dashboard.php')) {
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        } else {
            link.classList.remove('active');
            link.removeAttribute('aria-current');
        }
    });
    
    // Enhanced keyboard navigation for menu items
    const menuItems = document.querySelectorAll('[role="menuitem"]');
    menuItems.forEach((item, index) => {
        item.addEventListener('keydown', function(e) {
            let targetIndex;
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    targetIndex = (index + 1) % menuItems.length;
                    menuItems[targetIndex].focus();
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    targetIndex = (index - 1 + menuItems.length) % menuItems.length;
                    menuItems[targetIndex].focus();
                    break;
                    
                case 'Home':
                    e.preventDefault();
                    menuItems[0].focus();
                    break;
                    
                case 'End':
                    e.preventDefault();
                    menuItems[menuItems.length - 1].focus();
                    break;
                    
                case 'Enter':
                case ' ':
                    if (item.tagName === 'A') {
                        // Let the browser handle the link navigation
                        return;
                    }
                    e.preventDefault();
                    item.click();
                    break;
            }
        });
    });
    
    // Set initial ARIA attributes
    if (window.innerWidth <= 768) {
        sidebar.setAttribute('aria-hidden', 'true');
        sidebarToggle.setAttribute('aria-expanded', 'false');
    }
});
// Add this check to prevent unwanted behavior
function handleResize() {
    // Add a delay to prevent rapid firing
    clearTimeout(window.resizeTimer);
    window.resizeTimer = setTimeout(() => {
        if (window.innerWidth <= 768) {
            // Mobile logic
        } else {
            // Desktop logic
        }
    }, 100);
}

// Modify the click-outside handler
document.addEventListener('click', function(e) {
    // Check if the click is actually in the main document
    if (e.target.closest('body') && 
        !sidebar.contains(e.target) && 
        !sidebarToggle.contains(e.target) && 
        window.innerWidth <= 768) {
        sidebar.classList.remove('show');
        sidebar.setAttribute('aria-hidden', 'true');
        sidebarToggle.setAttribute('aria-expanded', 'false');
    }
});

// Add try-catch for localStorage operations
try {
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        // Apply collapsed state
    }
} catch (error) {
    console.warn('Could not access localStorage:', error);
}
</script>