<?php
require_once '../config/db_connect.php';

function has_permission($permission_name) {
    global $conn;
    if (!isset($_SESSION['user_id'])) return false;
    $user_id = $_SESSION['user_id'];
    // Check if user is super admin (role_id = 1)
    $super_sql = "SELECT 1 FROM user_roles WHERE user_id = ? AND role_id = 1 AND deleted_at IS NULL LIMIT 1";
    $super_stmt = mysqli_prepare($conn, $super_sql);
    mysqli_stmt_bind_param($super_stmt, 'i', $user_id);
    mysqli_stmt_execute($super_stmt);
    mysqli_stmt_store_result($super_stmt);
    $is_super_admin = mysqli_stmt_num_rows($super_stmt) > 0;
    mysqli_stmt_close($super_stmt);
    if ($is_super_admin) return true;
    $sql = "SELECT 1 FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            JOIN role_permissions rp ON r.id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? AND p.name = ? AND (ur.deleted_at IS NULL AND r.deleted_at IS NULL AND p.deleted_at IS NULL) LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $user_id, $permission_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $has_perm = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $has_perm;
}

// Define all sidebar modules and their required permissions
function get_sidebar_modules() {
    return [
        [
            'section' => 'User Management',
            'icon' => 'bi-people',
            'links' => [
                [
                    'label' => 'Users',
                    'url' => '../public/manage_users.php',
                    'icon' => 'bi-person',
                    'permission' => 'users.read_all'
                ],
                [
                    'label' => 'Roles',
                    'url' => '../public/manage_roles.php',
                    'icon' => 'bi-person-badge',
                    'permission' => 'users.manage_roles'
                ],
                [
                    'label' => 'Permissions',
                    'url' => '../public/manage_permissions.php',
                    'icon' => 'bi-shield-lock',
                    'permission' => 'users.manage_roles'
                ],
                [
                    'label' => 'Role Permissions',
                    'url' => '../public/manage_role_permissions.php',
                    'icon' => 'bi-shield-check',
                    'permission' => 'users.manage_roles'
                ],
                [
                    'label' => 'Employee Management',
                    'url' => '../public/employee_management.php',
                    'icon' => 'bi-person-workspace',
                    'permission' => 'employee.manage'
                ],
            ]
        ],
        [
            'section' => 'Business & Branches',
            'icon' => 'bi-building',
            'links' => [
                [
                    'label' => 'All Branches Dashboard',
                    'url' => '../public/branch_dashboard.php',
                    'icon' => 'bi-diagram-3',
                    'permission' => null
                ],
                // Dynamic branch links can be added in the sidebar include
            ]
        ],
        [
            'section' => 'Inventory',
            'icon' => 'bi-box-seam',
            'links' => [
                [
                    'label' => 'Fuel Types',
                    'url' => '../public/fuel_type_info.php',
                    'icon' => 'bi-droplet-half',
                    'permission' => 'inventory.view'
                ],
            ]
        ],
        [
            'section' => 'Financial',
            'icon' => 'bi-cash-stack',
            'links' => [
                [
                    'label' => 'Daily Sales Summary',
                    'url' => '../public/daily_sales_summary.php',
                    'icon' => 'bi-graph-up',
                    'permission' => 'financial.view_sales'
                ],
                [
                    'label' => 'Expenses',
                    'url' => '../public/expenses.php',
                    'icon' => 'bi-receipt',
                    'permission' => 'financial.view_expenses'
                ],
                [
                    'label' => 'Purchases',
                    'url' => '../public/purchases.php',
                    'icon' => 'bi-bag-check',
                    'permission' => 'financial.view_purchases'
                ],
                [
                    'label' => 'Cash Float',
                    'url' => '#',
                    'icon' => 'bi-cash',
                    'permission' => 'financial.view_cash_float'
                ],
                [
                    'label' => 'Bank Reconciliation',
                    'url' => '#',
                    'icon' => 'bi-bank',
                    'permission' => 'financial.view_bank_reconciliation'
                ],
            ]
        ],
        [
            'section' => 'Shift Management',
            'icon' => 'bi-clock-history',
            'links' => [
                [
                    'label' => 'Shifts',
                    'url' => '../public/shifts.php',
                    'icon' => 'bi-clock',
                    'permission' => 'shifts.view'
                ],
                [
                    'label' => 'Shift Assignments',
                    'url' => '../public/shift_assignments.php',
                    'icon' => 'bi-person-lines-fill',
                    'permission' => 'shift_assignments.view'
                ],
            ]
        ],
        [
            'section' => 'Maintenance & Compliance',
            'icon' => 'bi-tools',
            'links' => [
                [
                    'label' => 'Equipment Maintenance',
                    'url' => '#',
                    'icon' => 'bi-gear',
                    'permission' => 'maintenance.view'
                ],
                [
                    'label' => 'Regulatory Compliance',
                    'url' => '#',
                    'icon' => 'bi-clipboard-check',
                    'permission' => 'compliance.view'
                ],
                [
                    'label' => 'Fuel Quality Tests',
                    'url' => '#',
                    'icon' => 'bi-droplet',
                    'permission' => 'quality_tests.view'
                ],
                [
                    'label' => 'Safety Incidents',
                    'url' => '#',
                    'icon' => 'bi-exclamation-triangle',
                    'permission' => 'safety_incidents.view'
                ],
            ]
        ],
        [
            'section' => 'Reporting & Analytics',
            'icon' => 'bi-bar-chart',
            'links' => [
                [
                    'label' => 'Reports',
                    'url' => '../public/reports.php',
                    'icon' => 'bi-file-earmark-bar-graph',
                    'permission' => 'reports.view'
                ],
                [
                    'label' => 'Outstanding Credit',
                    'url' => '#',
                    'icon' => 'bi-credit-card',
                    'permission' => 'credit.view'
                ],
            ]
        ],
        [
            'section' => 'System',
            'icon' => 'bi-gear-wide-connected',
            'links' => [
                [
                    'label' => 'System Settings',
                    'url' => '#',
                    'icon' => 'bi-sliders',
                    'permission' => 'system.settings'
                ],
                [
                    'label' => 'Audit Logs',
                    'url' => '#',
                    'icon' => 'bi-journal-text',
                    'permission' => 'system.audit_logs'
                ],
                [
                    'label' => 'Notifications',
                    'url' => '../public/notifications.php',
                    'icon' => 'bi-bell',
                    'permission' => 'notifications.view'
                ],
            ]
        ],
    ];
}

// Filter sidebar modules based on user permissions
function get_accessible_sidebar_modules() {
    $modules = get_sidebar_modules();
    $accessible = [];
    // If business owner, show all modules (like super admin)
    $is_business_owner = false;
    if (isset($_SESSION['user_id'])) {
        global $conn;
        $user_id = $_SESSION['user_id'];
        $owner_sql = "SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = $user_id AND r.name = 'business_owner' AND r.deleted_at IS NULL LIMIT 1";
        $owner_result = mysqli_query($conn, $owner_sql);
        $is_business_owner = ($owner_result && mysqli_num_rows($owner_result) > 0);
    }
    if ($is_business_owner) {
        // Show all modules/links
        foreach ($modules as $module) {
            $accessible[] = [
                'section' => $module['section'],
                'icon' => $module['icon'],
                'links' => $module['links']
            ];
        }
        return $accessible;
    }
    // Default: filter by permissions
    foreach ($modules as $module) {
        $links = [];
        foreach ($module['links'] as $link) {
            if ($link['permission'] === null || has_permission($link['permission'])) {
                $links[] = $link;
            }
        }
        if (!empty($links)) {
            $accessible[] = [
                'section' => $module['section'],
                'icon' => $module['icon'],
                'links' => $links
            ];
        }
    }
    return $accessible;
}
?> 