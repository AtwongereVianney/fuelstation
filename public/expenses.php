<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';

// Fetch all branches for dropdown
$branches = [];
$branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$branch_result = mysqli_query($conn, $branch_sql);
if ($branch_result) {
    while ($row = mysqli_fetch_assoc($branch_result)) {
        $branches[] = $row;
    }
}

// Get selected branch and date range
$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : ($branches[0]['id'] ?? null);
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Helper for safe output
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }

// Fetch expenses for the branch and date range
$expenses = [];
$summary = [
    'total' => 0,
    'count' => 0,
    'by_category' => []
];
if ($selected_branch_id && $start_date && $end_date) {
    $sql = "SELECT e.*, u.first_name AS approved_first, u.last_name AS approved_last FROM expenses e LEFT JOIN users u ON e.approved_by = u.id WHERE e.branch_id = $selected_branch_id AND e.expense_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND e.deleted_at IS NULL ORDER BY e.expense_date DESC, e.id DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $expenses[] = $row;
            $summary['total'] += $row['amount'];
            $summary['count']++;
            $cat = $row['expense_category'];
            if (!isset($summary['by_category'][$cat])) $summary['by_category'][$cat] = 0;
            $summary['by_category'][$cat] += $row['amount'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <i class="fas fa-bars"></i> Menu
            </button>
        </div>
        <h2 class="mb-4">Expenses</h2>
        <form method="get" class="mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="branch_id" class="form-label">Select Branch:</label>
                    <select name="branch_id" id="branch_id" class="form-select">
                        <?php foreach ($branches as $b): ?>
                            <option value="<?php echo $b['id']; ?>" <?php if ($b['id'] == $selected_branch_id) echo 'selected'; ?>><?php echo h($b['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="start_date" class="form-label">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo h($start_date); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label for="end_date" class="form-label">End Date:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo h($end_date); ?>">
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary w-100">View Expenses</button>
                </div>
            </div>
        </form>
        <div class="mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Summary</h5>
                    <div class="row g-3">
                        <div class="col-12 col-md-4"><strong>Total Expenses:</strong> <?php echo h(number_format($summary['total'], 2)); ?></div>
                        <div class="col-12 col-md-4"><strong>Number of Expenses:</strong> <?php echo h($summary['count']); ?></div>
                        <div class="col-12 col-md-4">
                            <strong>By Category:</strong>
                            <ul class="mb-0">
                                <?php foreach ($summary['by_category'] as $cat => $amt): ?>
                                    <li><?php echo h($cat); ?>: <?php echo h(number_format($amt, 2)); ?></li>
                                <?php endforeach; ?>
                                <?php if (!$summary['by_category']): ?><li>None</li><?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <h5 class="mb-3">Expense Records</h5>
        <?php if ($expenses): ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Vendor</th>
                            <th>Status</th>
                            <th>Approved By</th>
                            <th>Receipt #</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td><?php echo h($e['expense_date']); ?></td>
                                <td><?php echo h($e['expense_category']); ?></td>
                                <td><?php echo h($e['description']); ?></td>
                                <td><?php echo h(number_format($e['amount'], 2)); ?></td>
                                <td><?php echo h($e['payment_method']); ?></td>
                                <td><?php echo h($e['vendor_name']); ?></td>
                                <td><?php echo h($e['status']); ?></td>
                                <td><?php echo h(trim($e['approved_first'] . ' ' . $e['approved_last'])); ?></td>
                                <td><?php echo h($e['receipt_number']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
        <?php else: ?>
            <div class="alert alert-info">No expenses found for this branch and date range.</div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 