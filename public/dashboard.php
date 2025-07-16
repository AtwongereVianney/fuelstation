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
        .main-content {
            flex-grow: 1;
            padding: 2rem 1rem;
            min-width: 0;
        }
        .sidebar + .main-content {
            transition: margin-left 0.2s;
        }
        .sidebar.collapsed + .main-content {
            margin-left: 64px;
        }
    </style>
</head>
<body>
<div class="d-flex" style="min-height:100vh;">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        <!-- Quick Start Section -->
        <div class="mb-4">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Start</h5>
              <div class="row g-3">
                <div class="col-6 col-md-3">
                  <a href="daily_sales_summary.php" class="btn btn-outline-primary w-100 py-3">
                    <i class="bi bi-cash-coin fs-3"></i><br>New Sale
                  </a>
                </div>
                <div class="col-6 col-md-3">
                  <a href="purchases/handle_action.php" class="btn btn-outline-success w-100 py-3">
                    <i class="bi bi-bag-plus fs-3"></i><br>New Purchase
                  </a>
                </div>
                <div class="col-6 col-md-3">
                  <a href="employee_management.php" class="btn btn-outline-info w-100 py-3">
                    <i class="bi bi-person-plus fs-3"></i><br>Add Employee
                  </a>
                </div>
                <div class="col-6 col-md-3">
                  <a href="reports.php" class="btn btn-outline-dark w-100 py-3">
                    <i class="bi bi-bar-chart-line fs-3"></i><br>View Reports
                  </a>
                </div>
                <div class="col-6 col-md-3">
                  <a href="expenses.php" class="btn btn-outline-warning w-100 py-3">
                    <i class="bi bi-currency-exchange fs-3"></i><br>Add Expense
                  </a>
                </div>
                <div class="col-6 col-md-3">
                  <a href="shift_assignments.php" class="btn btn-outline-secondary w-100 py-3">
                    <i class="bi bi-clock-history fs-3"></i><br>Assign Shift
                  </a>
                </div>
                <div class="col-6 col-md-3">
                  <a href="fuel_type_info.php" class="btn btn-outline-danger w-100 py-3">
                    <i class="bi bi-droplet-half fs-3"></i><br>Storage Tanks
                  </a>
                </div>
                <div class="col-6 col-md-3">
                  <a href="notifications.php" class="btn btn-outline-primary w-100 py-3">
                    <i class="bi bi-bell fs-3"></i><br>Notifications
                  </a>
                </div>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 