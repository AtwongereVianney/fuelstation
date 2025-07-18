<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/auth_helpers.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role_name'] ?? '';
$username = $_SESSION['username'] ?? '';
$role_display = $_SESSION['role_display_name'] ?? $role;

// Fetch user permissions
$user_permissions = [];
$sql = "SELECT p.name FROM permissions p
        JOIN role_permissions rp ON rp.permission_id = p.id
        JOIN user_roles ur ON ur.role_id = rp.role_id
        WHERE ur.user_id = ? AND p.deleted_at IS NULL";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $user_permissions[] = $row['name'];
}
mysqli_stmt_close($stmt);

$business_id = $_SESSION['business_id'] ?? null;
$current_year = date('Y');
$income_data = $expense_data = $service_demand_data = $months = [];
if ($role === 'super_admin' && $business_id) {
    // Prepare months
    for ($m = 1; $m <= 12; $m++) {
        $months[] = date('M', mktime(0,0,0,$m,1));
        $income_data[] = 0;
        $expense_data[] = 0;
        $service_demand_data[] = 0;
    }
    // Income (sales)
    $sql = "SELECT MONTH(transaction_date) as m, SUM(final_amount) as total FROM sales_transactions WHERE deleted_at IS NULL AND YEAR(transaction_date) = $current_year AND branch_id IN (SELECT id FROM branches WHERE business_id = $business_id AND deleted_at IS NULL) GROUP BY m";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $income_data[(int)$row['m']-1] = (float)$row['total'];
    }
    // Expenses
    $sql = "SELECT MONTH(expense_date) as m, SUM(amount) as total FROM expenses WHERE deleted_at IS NULL AND YEAR(expense_date) = $current_year AND branch_id IN (SELECT id FROM branches WHERE business_id = $business_id AND deleted_at IS NULL) GROUP BY m";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $expense_data[(int)$row['m']-1] = (float)$row['total'];
    }
    // Service demand: count of sales transactions (could be changed to another metric)
    $sql = "SELECT MONTH(transaction_date) as m, COUNT(*) as cnt FROM sales_transactions WHERE deleted_at IS NULL AND YEAR(transaction_date) = $current_year AND branch_id IN (SELECT id FROM branches WHERE business_id = $business_id AND deleted_at IS NULL) GROUP BY m";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $service_demand_data[(int)$row['m']-1] = (int)$row['cnt'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { min-height: 100vh; margin: 0; padding: 0; }
        .main-flex-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar-fixed { width: 240px; min-width: 200px; max-width: 300px; height: 100vh; position: sticky; top: 0; left: 0; z-index: 1020; background: #f8f9fa; border-right: 1px solid #dee2e6; }
        .main-content-scroll { flex: 1 1 0%; height: 100vh; overflow-y: auto; padding: 32px 24px 24px 24px; background: #fff; }
        @media (max-width: 767.98px) { .main-flex-container { display: block; height: auto; } .sidebar-fixed { display: none; } .main-content-scroll { height: auto; padding: 16px 8px; } }
    </style>
</head>
<body>
<!-- Responsive Sidebar Offcanvas for mobile -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileSidebarLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php include '../includes/sidebar.php'; ?>
    </div>
</div>
<div class="main-flex-container">
    <!-- Sidebar for desktop -->
    <div class="sidebar-fixed d-none d-md-block p-0">
    <?php include '../includes/sidebar.php'; ?>
    </div>
    <!-- Main content -->
    <div class="main-content-scroll">
        <!-- Mobile menu button -->
        <div class="d-md-none mb-3">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                <i class="bi bi-list"></i> Menu
            </button>
                </div>
        <?php include '../includes/header.php'; ?>
            <!-- Quick Start Section -->
            <div class="mb-4">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h5 class="card-title mb-3"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Start</h5>
                  <div class="row g-3">
                    <?php if ($role === 'super_admin' || in_array('financial.view_sales', $user_permissions)): ?>
                    <div class="col-6 col-md-3">
                      <a href="daily_sales_summary.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-cash-coin fs-3"></i><br>New Sale
                      </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === 'super_admin' || in_array('financial.view_purchases', $user_permissions)): ?>
                    <div class="col-6 col-md-3">
                      <a href="purchases.php" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-bag-plus fs-3"></i><br>New Purchase
                      </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === 'super_admin' || in_array('employee.manage', $user_permissions)): ?>
                    <div class="col-6 col-md-3">
                      <a href="employee_management.php" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-person-plus fs-3"></i><br>Add Employee
                      </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === 'super_admin' || in_array('reports.view', $user_permissions)): ?>
                    <div class="col-6 col-md-3">
                      <a href="reports.php" class="btn btn-outline-dark w-100 py-3">
                        <i class="bi bi-bar-chart-line fs-3"></i><br>View Reports
                      </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === 'super_admin' || in_array('financial.view_expenses', $user_permissions)): ?>
                    <div class="col-6 col-md-3">
                      <a href="expenses.php" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-currency-exchange fs-3"></i><br>Add Expense
                      </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === 'super_admin' || in_array('shift.assign', $user_permissions)): ?>
                    <div class="col-6 col-md-3">
                      <a href="shift_assignments.php" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-clock-history fs-3"></i><br>Assign Shift
                      </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === 'super_admin' || in_array('inventory.view', $user_permissions)): ?>
                    <div class="col-6 col-md-3">
                      <a href="fuel_type_info.php" class="btn btn-outline-danger w-100 py-3">
                        <i class="bi bi-droplet-half fs-3"></i><br>Storage Tanks
                      </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === 'super_admin' || in_array('notifications.view', $user_permissions)): ?>
                    <div class="col-6 col-md-3">
                      <a href="notifications.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-bell fs-3"></i><br>Notifications
                      </a>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="row g-4 mb-4">
                <?php
                // Users, Roles, Permissions, Branches (super admin only)
                if ($role === 'super_admin') {
                    $users_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"))[0];
                    $roles_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM roles WHERE deleted_at IS NULL"))[0];
                    $perms_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM permissions WHERE deleted_at IS NULL"))[0];
                    $branches_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM branches WHERE deleted_at IS NULL"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-primary h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-users me-2"></i>Users</h5><p class="card-text display-6 fw-bold"><?php echo $users_count; ?></p></div></div></div>
                    <div class="col-md-3"><div class="card text-bg-success h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-user-shield me-2"></i>Roles</h5><p class="card-text display-6 fw-bold"><?php echo $roles_count; ?></p></div></div></div>
                    <div class="col-md-3"><div class="card text-bg-warning h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-key me-2"></i>Permissions</h5><p class="card-text display-6 fw-bold"><?php echo $perms_count; ?></p></div></div></div>
                    <div class="col-md-3"><div class="card text-bg-info h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-building me-2"></i>Branches</h5><p class="card-text display-6 fw-bold"><?php echo $branches_count; ?></p></div></div></div>
                <?php }
                ?>
            </div>
        <?php if ($role === 'super_admin' && $business_id): ?>
        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">Income vs Expense (<?php echo $current_year; ?>)</div>
                    <div class="card-body">
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                </div>
                            </div>
            <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                    <div class="card-header bg-success text-white">Service Demand (<?php echo $current_year; ?>)</div>
                <div class="card-body">
                        <canvas id="serviceDemandChart"></canvas>
                    </div>
                </div>
            </div>
                </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        const months = <?php echo json_encode($months); ?>;
        const incomeData = <?php echo json_encode($income_data); ?>;
        const expenseData = <?php echo json_encode($expense_data); ?>;
        const serviceDemandData = <?php echo json_encode($service_demand_data); ?>;
        // Income vs Expense
        new Chart(document.getElementById('incomeExpenseChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    { label: 'Income', data: incomeData, backgroundColor: 'rgba(54, 162, 235, 0.7)' },
                    { label: 'Expense', data: expenseData, backgroundColor: 'rgba(255, 99, 132, 0.7)' }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
        // Service Demand
        new Chart(document.getElementById('serviceDemandChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    { label: 'Service Demand', data: serviceDemandData, borderColor: 'rgba(40,167,69,0.9)', backgroundColor: 'rgba(40,167,69,0.2)', fill: true }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
        </script>
            <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 