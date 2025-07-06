<?php
session_start();
require_once '../config/db_connect.php';
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

function has_permission($perm) {
    global $user_permissions, $role;
    return $role === 'super_admin' || in_array($perm, $user_permissions);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            
        </div>
        <div>
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 rounded">
                <div class="container-fluid">
                    <span class="navbar-text">Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role_display); ?>)</span>
                </div>
            </nav>
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
                // Sales
                if (has_permission('sales_transactions.view')) {
                    $total_sales = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(final_amount),0) FROM sales_transactions WHERE deleted_at IS NULL"))[0];
                    $today_sales = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(final_amount),0) FROM sales_transactions WHERE deleted_at IS NULL AND transaction_date = CURDATE()"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-secondary h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-cash-register me-2"></i>Total Sales</h5><p class="card-text display-6 fw-bold">UGX <?php echo number_format($total_sales, 2); ?></p><div class="small">Today: UGX <?php echo number_format($today_sales, 2); ?></div><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Credit Sales
                if (has_permission('credit_sales.view')) {
                    $credit_sales = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(credit_amount),0) FROM credit_sales WHERE deleted_at IS NULL"))[0];
                    $outstanding_credit = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(remaining_balance),0) FROM credit_sales WHERE deleted_at IS NULL AND payment_status IN ('pending','partial','overdue')"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-light h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-credit-card me-2"></i>Credit Sales</h5><p class="card-text display-6 fw-bold">UGX <?php echo number_format($credit_sales, 2); ?></p><div class="small">Outstanding: UGX <?php echo number_format($outstanding_credit, 2); ?></div><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Fuel Purchases
                if (has_permission('fuel_purchases.view')) {
                    $fuel_purchases = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(total_cost),0) FROM fuel_purchases WHERE deleted_at IS NULL"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-success h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-gas-pump me-2"></i>Fuel Purchases</h5><p class="card-text display-6 fw-bold">UGX <?php echo number_format($fuel_purchases, 2); ?></p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Inventory
                if (has_permission('storage_tanks.view')) {
                    $fuel_types = mysqli_query($conn, "SELECT name, (SELECT IFNULL(SUM(current_level),0) FROM storage_tanks WHERE fuel_type_id = ft.id AND deleted_at IS NULL) as total_stock FROM fuel_types ft WHERE deleted_at IS NULL");
                ?>
                    <div class="col-md-3"><div class="card text-bg-dark h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-boxes me-2"></i>Inventory</h5>
                        <ul class="list-unstyled mb-0">
                        <?php while ($ft = mysqli_fetch_assoc($fuel_types)): ?>
                            <li><?php echo htmlspecialchars($ft['name']); ?>: <strong><?php echo number_format($ft['total_stock'], 2); ?> L</strong></li>
                        <?php endwhile; ?>
                        </ul>
                        <a href="#" class="btn btn-link p-0 mt-2">View Details</a>
                    </div></div></div>
                <?php }
                // Expenses
                if (has_permission('expenses.view')) {
                    $total_expenses = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) FROM expenses WHERE deleted_at IS NULL"))[0];
                    $today_expenses = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) FROM expenses WHERE deleted_at IS NULL AND expense_date = CURDATE()"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-danger h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-money-bill-wave me-2"></i>Expenses</h5><p class="card-text display-6 fw-bold">UGX <?php echo number_format($total_expenses, 2); ?></p><div class="small">Today: UGX <?php echo number_format($today_expenses, 2); ?></div><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Equipment Maintenance
                if (has_permission('equipment_maintenance.view')) {
                    $open_maintenance = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM equipment_maintenance WHERE deleted_at IS NULL AND status IN ('scheduled','in_progress')"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-warning h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-tools me-2"></i>Open Maintenance</h5><p class="card-text display-6 fw-bold"><?php echo $open_maintenance; ?></p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Safety Incidents
                if (has_permission('safety_incidents.view')) {
                    $recent_incidents = mysqli_query($conn, "SELECT * FROM safety_incidents WHERE deleted_at IS NULL ORDER BY incident_date DESC, incident_time DESC LIMIT 5");
                    $incidents_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM safety_incidents WHERE deleted_at IS NULL"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-danger h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-exclamation-triangle me-2"></i>Safety Incidents</h5><p class="card-text display-6 fw-bold"><?php echo $incidents_count; ?></p><a href="#" class="btn btn-link p-0 mt-2" data-bs-toggle="collapse" data-bs-target="#recentIncidents">Recent</a></div></div></div>
                <?php }
                // Loyalty Customers
                if (has_permission('loyalty_customers.view')) {
                    $loyalty_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM loyalty_customers WHERE deleted_at IS NULL"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-info h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-gift me-2"></i>Loyalty Customers</h5><p class="card-text display-6 fw-bold"><?php echo $loyalty_count; ?></p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Notifications
                if (has_permission('notifications.view')) {
                    $notif_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM notifications WHERE deleted_at IS NULL AND (user_id = $user_id OR user_id IS NULL)"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-light h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-bell me-2"></i>Notifications</h5><p class="card-text display-6 fw-bold"><?php echo $notif_count; ?></p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Staff Attendance
                if (has_permission('attendance.view')) {
                    $present = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM shift_assignments WHERE date = CURDATE() AND status = 'present'"))[0];
                    $absent = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM shift_assignments WHERE date = CURDATE() AND status = 'absent'"))[0];
                    $total = $present + $absent;
                ?>
                    <div class="col-md-3"><div class="card text-bg-secondary h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-user-check me-2"></i>Attendance</h5><p class="card-text display-6 fw-bold"><?php echo "$present / $total Present"; ?></p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Pump Readings
                if (has_permission('pump_readings.view')) {
                    $today_readings = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pump_readings WHERE reading_date = CURDATE()"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-info h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-tachometer-alt me-2"></i>Pump Readings</h5><p class="card-text display-6 fw-bold"><?php echo $today_readings; ?> Today</p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Supplier Deliveries
                if (has_permission('supplier_deliveries.view')) {
                    $month_deliveries = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM supplier_deliveries WHERE MONTH(delivery_date) = MONTH(CURDATE()) AND YEAR(delivery_date) = YEAR(CURDATE())"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-success h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-truck me-2"></i>Supplier Deliveries</h5><p class="card-text display-6 fw-bold"><?php echo $month_deliveries; ?> This Month</p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Customer Feedback
                if (has_permission('customer_feedback.view')) {
                    $unread_feedback = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM customer_feedback WHERE status = 'unread'"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-warning h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-comment-dots me-2"></i>Feedback</h5><p class="card-text display-6 fw-bold"><?php echo $unread_feedback; ?> New</p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Price Changes
                if (has_permission('fuel_prices.view')) {
                    $last_price = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM fuel_price_history WHERE deleted_at IS NULL ORDER BY effective_date DESC, created_at DESC LIMIT 1"));
                    $last_date = $last_price ? $last_price['effective_date'] : 'N/A';
                ?>
                    <div class="col-md-3"><div class="card text-bg-danger h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-tag me-2"></i>Price Changes</h5><p class="card-text display-6 fw-bold"><?php echo htmlspecialchars($last_date); ?></p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Shift Management
                if (has_permission('shifts.view')) {
                    $open_shifts = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM shift_assignments WHERE status IN ('scheduled', 'active') AND deleted_at IS NULL"))[0];
                ?>
                    <div class="col-md-3"><div class="card text-bg-primary h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-user-clock me-2"></i>Open Shifts</h5><p class="card-text display-6 fw-bold"><?php echo $open_shifts; ?></p><a href="#" class="btn btn-link p-0 mt-2">View Details</a></div></div></div>
                <?php }
                // Stock Alerts
                if (has_permission('inventory.view')) {
                    $low_stock = mysqli_query($conn, "SELECT ft.name, st.current_level, st.minimum_level FROM storage_tanks st LEFT JOIN fuel_types ft ON st.fuel_type_id = ft.id WHERE st.deleted_at IS NULL AND st.current_level <= st.minimum_level");
                    $low_count = mysqli_num_rows($low_stock);
                ?>
                    <div class="col-md-3"><div class="card text-bg-dark h-100"><div class="card-body"><h5 class="card-title"><i class="fas fa-exclamation-circle me-2"></i>Stock Alerts</h5><p class="card-text display-6 fw-bold"><?php echo $low_count; ?> Low</p><a href="#" class="btn btn-link p-0 mt-2" data-bs-toggle="collapse" data-bs-target="#lowStockTable">View List</a></div></div></div>
                <?php }
                ?>
            </div>
            <?php if ($role === 'super_admin'): ?>
                <?php
                $recent_activity = [];
                $res = mysqli_query($conn, "SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE a.deleted_at IS NULL ORDER BY a.created_at DESC LIMIT 5");
                while ($row = mysqli_fetch_assoc($res)) {
                    $recent_activity[] = $row;
                }
                ?>
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">Recent Activity</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['record_id']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">My Profile</h5>
                                <?php
                                $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id AND deleted_at IS NULL");
                                $user = mysqli_fetch_assoc($res);
                                ?>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></li>
                                    <li class="list-group-item"><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></li>
                                    <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                                    <li class="list-group-item"><strong>Role:</strong> <?php echo htmlspecialchars($role_display); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php if (has_permission('branches.view')): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                <div class="card-body">
                                <h5 class="card-title">Branch Info</h5>
                                <?php
                                $branch = null;
                                if (!empty($user['branch_id'])) {
                                    $res = mysqli_query($conn, "SELECT * FROM branches WHERE id = " . intval($user['branch_id']) . " AND deleted_at IS NULL");
                                    $branch = mysqli_fetch_assoc($res);
                                }
                                ?>
                                <?php if ($branch): ?>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Branch:</strong> <?php echo htmlspecialchars($branch['branch_name']); ?></li>
                                        <li class="list-group-item"><strong>Type:</strong> <?php echo htmlspecialchars($branch['branch_type']); ?></li>
                                        <li class="list-group-item"><strong>Manager:</strong> <?php echo htmlspecialchars($branch['manager_name']); ?></li>
                                        <li class="list-group-item"><strong>Status:</strong> <?php echo htmlspecialchars($branch['status']); ?></li>
                                    </ul>
                    <?php else: ?>
                                    <div class="alert alert-info mb-0">No branch assigned.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>                
            <?php endif; ?>
            <?php if (has_permission('sales_transactions.view')): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Recent Sales Transactions</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Transaction #</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Attendant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT st.*, u.username as attendant FROM sales_transactions st LEFT JOIN users u ON st.attendant_id = u.id WHERE st.deleted_at IS NULL ORDER BY st.transaction_date DESC, st.transaction_time DESC LIMIT 5");
                            while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['transaction_date'] . ' ' . $row['transaction_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['transaction_number']); ?></td>
                                <td>UGX <?php echo number_format($row['final_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                <td><?php echo htmlspecialchars($row['attendant']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (has_permission('credit_sales.view')): ?>
            <div class="card mb-4">
                <div class="card-header bg-light">Recent Credit Sales</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT cs.*, ca.company_name FROM credit_sales cs LEFT JOIN customer_accounts ca ON cs.customer_account_id = ca.id WHERE cs.deleted_at IS NULL ORDER BY cs.due_date DESC LIMIT 5");
                            while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                <td>UGX <?php echo number_format($row['credit_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (has_permission('equipment_maintenance.view')): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning">Open Equipment Maintenance</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM equipment_maintenance WHERE deleted_at IS NULL AND status IN ('scheduled','in_progress') ORDER BY maintenance_date DESC LIMIT 5");
                            while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['maintenance_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['equipment_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (has_permission('safety_incidents.view')): ?>
            <div class="card mb-4 collapse" id="recentIncidents">
                <div class="card-header bg-danger text-white">Recent Safety Incidents</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM safety_incidents WHERE deleted_at IS NULL ORDER BY incident_date DESC, incident_time DESC LIMIT 5");
                            while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['incident_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['incident_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['severity']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (has_permission('loyalty_customers.view')): ?>
            <div class="card mb-4">
                <div class="card-header bg-info">Recent Loyalty Customers</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Total Points</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM loyalty_customers WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
                            while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['total_points']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (has_permission('notifications.view')): ?>
            <div class="card mb-4">
                <div class="card-header bg-light">Recent Notifications</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Title</th><th>Message</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM notifications WHERE deleted_at IS NULL AND (user_id = $user_id OR user_id IS NULL) ORDER BY created_at DESC LIMIT 5");
                            while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['message']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (has_permission('storage_tanks.view')): ?>
            <div class="card mb-4 collapse" id="lowStockTable">
                <div class="card-header bg-dark text-white">Low Stock Items</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Product</th><th>Current Level</th><th>Minimum Level</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            mysqli_data_seek($low_stock, 0);
                            while ($row = mysqli_fetch_assoc($low_stock)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['current_level']); ?></td>
                                <td><?php echo htmlspecialchars($row['minimum_level']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>


        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 