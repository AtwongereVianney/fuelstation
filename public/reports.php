<?php
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

// Report types
$report_types = [
    'sales' => 'Sales Transactions',
    'expenses' => 'Expenses',
    'credit' => 'Credit Sales',
    'purchases' => 'Fuel Purchases',
];

// Get filters
$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : ($branches[0]['id'] ?? null);
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Helper for safe output
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }

// Fetch all report data for the branch and date range
$sales = $expenses = $credits = $purchases = [];
if ($selected_branch_id && $start_date && $end_date) {
    // Sales
    $sql = "SELECT st.*, fd.dispenser_number, ft.name AS fuel_type, u.first_name, u.last_name FROM sales_transactions st JOIN fuel_dispensers fd ON st.dispenser_id = fd.id JOIN fuel_types ft ON st.fuel_type_id = ft.id LEFT JOIN users u ON st.attendant_id = u.id WHERE st.branch_id = $selected_branch_id AND st.transaction_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND st.deleted_at IS NULL ORDER BY st.transaction_date DESC, st.transaction_time DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $sales[] = $row;
    // Expenses
    $sql = "SELECT e.*, u.first_name AS approved_first, u.last_name AS approved_last FROM expenses e LEFT JOIN users u ON e.approved_by = u.id WHERE e.branch_id = $selected_branch_id AND e.expense_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND e.deleted_at IS NULL ORDER BY e.expense_date DESC, e.id DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $expenses[] = $row;
    // Credit Sales
    $sql = "SELECT cs.*, ca.company_name 
            FROM credit_sales cs
            LEFT JOIN customer_accounts ca ON cs.customer_account_id = ca.id
            LEFT JOIN sales_transactions st ON cs.transaction_id = st.id
            WHERE st.branch_id = $selected_branch_id
              AND cs.due_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "'
              AND cs.deleted_at IS NULL
            ORDER BY cs.due_date DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $credits[] = $row;
    // Purchases
    $sql = "SELECT fp.*, s.name AS supplier_name FROM fuel_purchases fp LEFT JOIN suppliers s ON fp.supplier_id = s.id WHERE fp.branch_id = $selected_branch_id AND fp.delivery_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND fp.deleted_at IS NULL ORDER BY fp.delivery_date DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $purchases[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
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
                    <i class="bi bi-list"></i> Menu
                </button>
            </div>
            <h2 class="mb-4">Branch Reports Dashboard</h2>
            <form method="get" class="mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="branch_id" class="form-label">Select Branch:</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php if ($b['id'] == $selected_branch_id) echo 'selected'; ?>><?php echo h($b['branch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo h($start_date); ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo h($end_date); ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View Dashboard</button>
                    </div>
                </div>
            </form>
            <div class="row mb-4 g-3">
                <!-- Summary Cards -->
                <div class="col-md-3 col-6">
                    <div class="card text-bg-success h-100">
                        <div class="card-body">
                            <h6 class="card-title">Total Sales</h6>
                            <h3 class="card-text">UGX <?php echo number_format(array_sum(array_column($sales, 'final_amount')), 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card text-bg-danger h-100">
                        <div class="card-body">
                            <h6 class="card-title">Total Expenses</h6>
                            <h3 class="card-text">UGX <?php echo number_format(array_sum(array_column($expenses, 'amount')), 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card text-bg-warning h-100">
                        <div class="card-body">
                            <h6 class="card-title">Credit Sales</h6>
                            <h3 class="card-text">UGX <?php echo number_format(array_sum(array_column($credits, 'credit_amount')), 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card text-bg-info h-100">
                        <div class="card-body">
                            <h6 class="card-title">Fuel Purchases</h6>
                            <h3 class="card-text">UGX <?php echo number_format(array_sum(array_column($purchases, 'total_cost')), 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Visualization Charts -->
            <div class="row mb-4 g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">Sales vs Expenses</h6>
                            <canvas id="salesExpensesChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">Credit Sales & Purchases</h6>
                            <canvas id="creditPurchasesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Bootstrap Tabs for Tables -->
            <ul class="nav nav-tabs mb-3" id="reportTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab">Expenses</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="credit-tab" data-bs-toggle="tab" data-bs-target="#credit" type="button" role="tab">Credit Sales</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab">Purchases</button></li>
            </ul>
            <div class="tab-content" id="reportTabsContent">
                <div class="tab-pane fade show active" id="sales" role="tabpanel">
                    <?php if ($sales): ?>
                        <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Date</th><th>Time</th><th>Dispenser #</th><th>Fuel Type</th><th>Quantity</th><th>Unit Price</th><th>Final Amount</th><th>Payment</th><th>Attendant</th></tr></thead><tbody>
                            <?php foreach ($sales as $s): ?>
                                <tr><td><?php echo h($s['transaction_date']); ?></td><td><?php echo h($s['transaction_time']); ?></td><td><?php echo h($s['dispenser_number']); ?></td><td><?php echo h($s['fuel_type']); ?></td><td><?php echo h($s['quantity']); ?></td><td><?php echo h($s['unit_price']); ?></td><td><?php echo h($s['final_amount']); ?></td><td><?php echo h($s['payment_method']); ?></td><td><?php echo h(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?><div class="alert alert-info">No sales transactions found for this branch and date range.</div><?php endif; ?>
                </div>
                <div class="tab-pane fade" id="expenses" role="tabpanel">
                    <?php if ($expenses): ?>
                        <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Approved By</th></tr></thead><tbody>
                            <?php foreach ($expenses as $e): ?>
                                <tr><td><?php echo h($e['expense_date']); ?></td><td><?php echo h($e['expense_category']); ?></td><td><?php echo h($e['description']); ?></td><td><?php echo h($e['amount']); ?></td><td><?php echo h(($e['approved_first'] ?? '') . ' ' . ($e['approved_last'] ?? '')); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?><div class="alert alert-info">No expenses found for this branch and date range.</div><?php endif; ?>
                </div>
                <div class="tab-pane fade" id="credit" role="tabpanel">
                    <?php if ($credits): ?>
                        <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Due Date</th><th>Customer</th><th>Credit Amount</th><th>Status</th></tr></thead><tbody>
                            <?php foreach ($credits as $c): ?>
                                <tr><td><?php echo h($c['due_date']); ?></td><td><?php echo h($c['company_name']); ?></td><td><?php echo h($c['credit_amount']); ?></td><td><?php echo h($c['payment_status']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?><div class="alert alert-info">No credit sales found for this branch and date range.</div><?php endif; ?>
                </div>
                <div class="tab-pane fade" id="purchases" role="tabpanel">
                    <?php if ($purchases): ?>
                        <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Date</th><th>Supplier</th><th>Quantity</th><th>Unit Cost</th><th>Total Cost</th><th>Status</th></tr></thead><tbody>
                            <?php foreach ($purchases as $p): ?>
                                <tr><td><?php echo h($p['delivery_date']); ?></td><td><?php echo h($p['supplier_name']); ?></td><td><?php echo h($p['quantity_delivered']); ?></td><td><?php echo h($p['unit_cost']); ?></td><td><?php echo h($p['total_cost']); ?></td><td><?php echo h($p['payment_status']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?><div class="alert alert-info">No purchases found for this branch and date range.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales vs Expenses Chart
const salesExpensesChart = document.getElementById('salesExpensesChart').getContext('2d');
new Chart(salesExpensesChart, {
    type: 'bar',
    data: {
        labels: ['Sales', 'Expenses'],
        datasets: [{
            label: 'UGX',
            data: [<?php echo array_sum(array_column($sales, 'final_amount')); ?>, <?php echo array_sum(array_column($expenses, 'amount')); ?>],
            backgroundColor: ['#198754', '#dc3545']
        }]
    },
    options: {responsive: true, plugins: {legend: {display: false}}}
});
// Credit Sales & Purchases Chart
const creditPurchasesChart = document.getElementById('creditPurchasesChart').getContext('2d');
new Chart(creditPurchasesChart, {
    type: 'bar',
    data: {
        labels: ['Credit Sales', 'Purchases'],
        datasets: [{
            label: 'UGX',
            data: [<?php echo array_sum(array_column($credits, 'credit_amount')); ?>, <?php echo array_sum(array_column($purchases, 'total_cost')); ?>],
            backgroundColor: ['#ffc107', '#0dcaf0']
        }]
    },
    options: {responsive: true, plugins: {legend: {display: false}}}
});
</script>
</body>
</html> 