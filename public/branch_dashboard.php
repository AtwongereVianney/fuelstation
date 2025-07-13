<?php
session_start();
include '../config/db_connect.php';

// Handle branch selection from GET parameter first
if (isset($_GET['branch_id'])) {
    $_SESSION['selected_branch_id'] = intval($_GET['branch_id']);
}
$selected_branch_id = $_SESSION['selected_branch_id'] ?? null;

// Fetch branch info with error handling
$branch_sql = "SELECT * FROM branches WHERE id = ? AND deleted_at IS NULL LIMIT 1";
$branch_stmt = mysqli_prepare($conn, $branch_sql);

if (!$branch_stmt) {
    echo '<div class="alert alert-danger m-4">Database error: ' . mysqli_error($conn) . '</div>';
    exit;
}

mysqli_stmt_bind_param($branch_stmt, 'i', $selected_branch_id);
mysqli_stmt_execute($branch_stmt);
$branch_result = mysqli_stmt_get_result($branch_stmt);
$branch = mysqli_fetch_assoc($branch_result);
mysqli_stmt_close($branch_stmt);


// Store branch name for easy access
$branch_name = $branch['branch_name'] ?? 'Unknown Branch';

// Expenses
$expenses_sql = "SELECT SUM(amount) as total_expenses FROM expenses WHERE branch_id = ? AND deleted_at IS NULL";
$expenses_stmt = mysqli_prepare($conn, $expenses_sql);
mysqli_stmt_bind_param($expenses_stmt, 'i', $selected_branch_id);
mysqli_stmt_execute($expenses_stmt);
$expenses_result = mysqli_stmt_get_result($expenses_stmt);
$total_expenses = mysqli_fetch_assoc($expenses_result)['total_expenses'] ?? 0;
mysqli_stmt_close($expenses_stmt);

// Total Sales
$sales_sql = "SELECT SUM(final_amount) as total_sales FROM sales_transactions WHERE branch_id = ? AND deleted_at IS NULL";
$sales_stmt = mysqli_prepare($conn, $sales_sql);
mysqli_stmt_bind_param($sales_stmt, 'i', $selected_branch_id);
mysqli_stmt_execute($sales_stmt);
$sales_result = mysqli_stmt_get_result($sales_stmt);
$total_sales = mysqli_fetch_assoc($sales_result)['total_sales'] ?? 0;
mysqli_stmt_close($sales_stmt);

// Daily Sales (today)
$today = date('Y-m-d');
$daily_sales_sql = "SELECT SUM(final_amount) as daily_sales FROM sales_transactions WHERE branch_id = ? AND transaction_date = ? AND deleted_at IS NULL";
$daily_sales_stmt = mysqli_prepare($conn, $daily_sales_sql);
mysqli_stmt_bind_param($daily_sales_stmt, 'is', $selected_branch_id, $today);
mysqli_stmt_execute($daily_sales_stmt);
$daily_sales_result = mysqli_stmt_get_result($daily_sales_stmt);
$daily_sales = mysqli_fetch_assoc($daily_sales_result)['daily_sales'] ?? 0;
mysqli_stmt_close($daily_sales_stmt);

// Attendants
$attendants_sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.status FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id WHERE u.branch_id = ? AND r.name = 'fuel_attendant' AND u.deleted_at IS NULL";
$attendants_stmt = mysqli_prepare($conn, $attendants_sql);
mysqli_stmt_bind_param($attendants_stmt, 'i', $selected_branch_id);
mysqli_stmt_execute($attendants_stmt);
$attendants_result = mysqli_stmt_get_result($attendants_stmt);
$attendants = [];
while ($row = mysqli_fetch_assoc($attendants_result)) {
    $attendants[] = $row;
}
mysqli_stmt_close($attendants_stmt);

// Shifts and their attendants
$shifts_sql = "SELECT s.id, s.shift_name, s.start_time, s.end_time, sa.user_id, u.first_name, u.last_name FROM shifts s LEFT JOIN shift_assignments sa ON s.id = sa.shift_id LEFT JOIN users u ON sa.user_id = u.id WHERE s.branch_id = ? AND s.deleted_at IS NULL ORDER BY s.start_time";
$shifts_stmt = mysqli_prepare($conn, $shifts_sql);
mysqli_stmt_bind_param($shifts_stmt, 'i', $selected_branch_id);
mysqli_stmt_execute($shifts_stmt);
$shifts_result = mysqli_stmt_get_result($shifts_stmt);
$shifts = [];
while ($row = mysqli_fetch_assoc($shifts_result)) {
    $shifts[] = $row;
}
mysqli_stmt_close($shifts_stmt);

// Financial performance (summary)
$summary_sql = "SELECT 
    (SELECT SUM(final_amount) FROM sales_transactions WHERE branch_id = ? AND deleted_at IS NULL) as total_sales,
    (SELECT SUM(amount) FROM expenses WHERE branch_id = ? AND deleted_at IS NULL) as total_expenses,
    (SELECT COUNT(*) FROM sales_transactions WHERE branch_id = ? AND deleted_at IS NULL) as total_transactions,
    (SELECT COUNT(*) FROM users WHERE branch_id = ? AND deleted_at IS NULL) as total_users
";
$summary_stmt = mysqli_prepare($conn, $summary_sql);
mysqli_stmt_bind_param($summary_stmt, 'iiii', $selected_branch_id, $selected_branch_id, $selected_branch_id, $selected_branch_id);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = mysqli_fetch_assoc($summary_result);
mysqli_stmt_close($summary_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Dashboard - <?php echo htmlspecialchars($branch['branch_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
<div class="container-fluid">
    <div class="row flex-nowrap">
        <!-- Sidebar for desktop -->
        <div class="col-auto d-none d-md-block p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <!-- Main content -->
        <div class="col ps-md-4 pt-3 main-content">
            <!-- Mobile menu button -->
            <div class="d-md-none mb-3">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="fas fa-bars"></i> Menu
                </button>
            </div>
            <h2 class="mb-4">Branch Dashboard: <?php echo htmlspecialchars($branch_name); ?></h2>
            <div class="row mb-4 g-3">
                <div class="col-12 col-md-3">
                    <div class="card text-bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <p class="card-text fs-4">UGX <?php echo number_format($total_sales, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card text-bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Today's Sales</h5>
                            <p class="card-text fs-4">UGX <?php echo number_format($daily_sales, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card text-bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Expenses</h5>
                            <p class="card-text fs-4">UGX <?php echo number_format($total_expenses, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card text-bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Transactions</h5>
                            <p class="card-text fs-4"><?php echo number_format($summary['total_transactions'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Attendants</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($attendants)): ?>
                                    <?php foreach ($attendants as $attendant): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($attendant['id']); ?></td>
                                            <td><?php echo htmlspecialchars($attendant['username']); ?></td>
                                            <td><?php echo htmlspecialchars($attendant['first_name'] . ' ' . $attendant['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($attendant['status']); ?></td>
                                            <td class="text-end">
                                                <a href="#" class="btn btn-sm btn-primary me-1"><i class="bi bi-pencil"></i> Edit</a>
                                                <a href="#" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No attendants found for this branch</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">Shifts & Assignments</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Shift Name</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Attendant</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($shifts)): ?>
                                    <?php foreach ($shifts as $shift): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($shift['shift_name']); ?></td>
                                            <td><?php echo htmlspecialchars($shift['start_time']); ?></td>
                                            <td><?php echo htmlspecialchars($shift['end_time']); ?></td>
                                            <td><?php echo htmlspecialchars(($shift['first_name'] ?? '') . ' ' . ($shift['last_name'] ?? '')); ?></td>
                                            <td class="text-end">
                                                <a href="#" class="btn btn-sm btn-primary me-1"><i class="bi bi-eye"></i> View</a>
                                                <a href="#" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No shifts found for this branch</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">Financial Performance</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Total Sales: <strong>UGX <?php echo number_format($summary['total_sales'] ?? 0, 2); ?></strong></li>
                        <li class="list-group-item">Total Expenses: <strong>UGX <?php echo number_format($summary['total_expenses'] ?? 0, 2); ?></strong></li>
                        <li class="list-group-item">Total Transactions: <strong><?php echo number_format($summary['total_transactions'] ?? 0); ?></strong></li>
                        <li class="list-group-item">Total Users: <strong><?php echo number_format($summary['total_users'] ?? 0); ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Accordion behavior: only one section open at a time
  document.querySelectorAll('.sidebar-heading[data-bs-toggle="collapse"]').forEach(function(heading) {
    heading.addEventListener('click', function() {
      document.querySelectorAll('.sidebar-heading[data-bs-toggle="collapse"]').forEach(function(other) {
        if (other !== heading) {
          let target = document.querySelector(other.getAttribute('data-bs-target'));
          if (target && target.classList.contains('show')) {
            new bootstrap.Collapse(target, {toggle: true}).hide();
            other.classList.add('collapsed');
            // Set arrow to down
            let arrow = other.querySelector('.collapse-arrow');
            if (arrow) {
              arrow.classList.remove('bi-caret-up-fill');
              arrow.classList.add('bi-caret-down-fill');
            }
          }
        }
      });
      heading.classList.toggle('collapsed');
      // Toggle arrow direction
      let arrow = heading.querySelector('.collapse-arrow');
      if (arrow) {
        arrow.classList.toggle('bi-caret-down-fill');
        arrow.classList.toggle('bi-caret-up-fill');
      }
    });
  });
  // Sidebar expand/collapse toggle
  var sidebarToggle = document.getElementById('sidebarToggle');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function(e) {
      e.stopPropagation();
      document.querySelector('.sidebar').classList.toggle('collapsed');
    });
  }
});
</script>
</body>
</html> 