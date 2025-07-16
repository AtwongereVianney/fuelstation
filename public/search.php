<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/auth_helpers.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [
    'users' => [],
    'roles' => [],
    'permissions' => [],
    'employees' => [],
    'branches' => [],
    'fuel_types' => [],
    'expenses' => [],
    'notifications' => [],
    'shifts' => [],
    'shift_assignments' => [],
    'daily_sales' => [],
];
if ($q !== '') {
    // Users
    if (has_permission('users.read_all')) {
        $sql = "SELECT id, username, email, first_name, last_name FROM users WHERE (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?) AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['users'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Roles
    if (has_permission('users.manage_roles')) {
        $sql = "SELECT id, name, display_name, description FROM roles WHERE (name LIKE ? OR display_name LIKE ? OR description LIKE ?) AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['roles'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Permissions
    if (has_permission('users.manage_roles')) {
        $sql = "SELECT id, name, display_name, description FROM permissions WHERE (name LIKE ? OR display_name LIKE ? OR description LIKE ?) AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['permissions'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Employees (users table, but show as employees)
    if (has_permission('employee.manage')) {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, b.branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.id WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR b.branch_name LIKE ?) AND u.deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssss', $like, $like, $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['employees'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Branches
    if (has_permission('branches.view')) {
        $sql = "SELECT id, branch_name FROM branches WHERE branch_name LIKE ? AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['branches'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Fuel Types
    if (has_permission('inventory.view')) {
        $sql = "SELECT id, name, code, description FROM fuel_types WHERE (name LIKE ? OR code LIKE ? OR description LIKE ?) AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['fuel_types'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Expenses
    if (has_permission('financial.view_expenses')) {
        $sql = "SELECT id, description, amount, expense_date FROM expenses WHERE (description LIKE ? OR expense_category LIKE ?) AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['expenses'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Notifications
    if (has_permission('notifications.view')) {
        $sql = "SELECT id, title, message, created_at FROM notifications WHERE (title LIKE ? OR message LIKE ?) AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['notifications'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Shifts
    if (has_permission('shift.view')) {
        $sql = "SELECT id, shift_name, start_time, end_time, description FROM shifts WHERE (shift_name LIKE ? OR description LIKE ?) AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['shifts'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Shift Assignments
    if (has_permission('shift.assign')) {
        $sql = "SELECT sa.id, sa.assignment_date, sa.status, s.shift_name, u.first_name, u.last_name FROM shift_assignments sa JOIN shifts s ON sa.shift_id = s.id JOIN users u ON sa.user_id = u.id WHERE (s.shift_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR sa.status LIKE ?) AND sa.deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['shift_assignments'][] = $row;
        mysqli_stmt_close($stmt);
    }
    // Daily Sales Summaries
    if (has_permission('financial.view_sales')) {
        $sql = "SELECT id, business_date, total_sales, status FROM daily_sales_summary WHERE (status LIKE ?) AND deleted_at IS NULL LIMIT 10";
        $like = "%$q%";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $results['daily_sales'][] = $row;
        mysqli_stmt_close($stmt);
    }
}
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex" style="min-height:100vh;">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
  <div class="main-content" style="padding-top: 50px;">
    <div class="py-4 px-4">
      <h2 class="mb-4">Search Results for <span class="text-primary">"<?php echo h($q); ?>"</span></h2>
      <?php if ($q === ''): ?>
          <div class="alert alert-info">Enter a search term above.</div>
      <?php else: ?>
          <?php
          $any = false;
          foreach ($results as $module => $items) {
              if (!empty($items)) { $any = true; break; }
          }
          ?>
          <?php if (!$any): ?>
              <div class="alert alert-warning">No results found.</div>
          <?php endif; ?>
          <?php if (!empty($results['users'])): ?>
              <h4>Users</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['users'] as $user): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($user['username']); ?></strong> (<?php echo h($user['email']); ?>) - <?php echo h($user['first_name'] . ' ' . $user['last_name']); ?>
                          <a href="user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['roles'])): ?>
              <h4>Roles</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['roles'] as $role): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($role['display_name']); ?></strong> (<?php echo h($role['name']); ?>) - <?php echo h($role['description']); ?>
                          <a href="manage_roles.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['permissions'])): ?>
              <h4>Permissions</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['permissions'] as $perm): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($perm['display_name']); ?></strong> (<?php echo h($perm['name']); ?>) - <?php echo h($perm['description']); ?>
                          <a href="manage_permissions.php?id=<?php echo $perm['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['employees'])): ?>
              <h4>Employees</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['employees'] as $emp): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($emp['first_name'] . ' ' . $emp['last_name']); ?></strong> (<?php echo h($emp['email']); ?>, <?php echo h($emp['phone']); ?>) - Branch: <?php echo h($emp['branch_name']); ?>
                          <a href="employee_management.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['branches'])): ?>
              <h4>Branches</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['branches'] as $branch): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($branch['branch_name']); ?></strong>
                          <a href="branch_dashboard.php?branch_id=<?php echo $branch['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['fuel_types'])): ?>
              <h4>Fuel Types</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['fuel_types'] as $ft): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($ft['name']); ?></strong> (<?php echo h($ft['code']); ?>) - <?php echo h($ft['description']); ?>
                          <a href="fuel_type_info.php?fuel_type_id=<?php echo $ft['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['expenses'])): ?>
              <h4>Expenses</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['expenses'] as $exp): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($exp['description']); ?></strong> - UGX <?php echo h(number_format($exp['amount'], 2)); ?> (<?php echo h($exp['expense_date']); ?>)
                          <a href="expenses.php?highlight=<?php echo $exp['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['notifications'])): ?>
              <h4>Notifications</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['notifications'] as $n): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($n['title']); ?></strong> - <?php echo h($n['message']); ?> (<?php echo h($n['created_at']); ?>)
                          <a href="notifications.php?highlight=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['shifts'])): ?>
              <h4>Shifts</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['shifts'] as $shift): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($shift['shift_name']); ?></strong> (<?php echo h($shift['start_time']); ?> - <?php echo h($shift['end_time']); ?>) - <?php echo h($shift['description']); ?>
                          <a href="shifts.php?highlight=<?php echo $shift['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['shift_assignments'])): ?>
              <h4>Shift Assignments</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['shift_assignments'] as $a): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($a['shift_name']); ?></strong> - <?php echo h($a['first_name'] . ' ' . $a['last_name']); ?> (<?php echo h($a['assignment_date']); ?>, <?php echo h($a['status']); ?>)
                          <a href="shift_assignments.php?highlight=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
          <?php if (!empty($results['daily_sales'])): ?>
              <h4>Daily Sales Summaries</h4>
              <ul class="list-group mb-4">
                  <?php foreach ($results['daily_sales'] as $ds): ?>
                      <li class="list-group-item">
                          <strong><?php echo h($ds['business_date']); ?></strong> - UGX <?php echo h(number_format($ds['total_sales'], 2)); ?> (<?php echo h($ds['status']); ?>)
                          <a href="daily_sales_summary.php?highlight=<?php echo $ds['id']; ?>" class="btn btn-sm btn-outline-primary float-end">View</a>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>
</body>
</html> 