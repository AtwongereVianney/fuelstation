<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';

// Helper for safe output
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }

// Fetch all branches
$branches = [];
$branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$branch_result = mysqli_query($conn, $branch_sql);
if ($branch_result) {
    while ($row = mysqli_fetch_assoc($branch_result)) {
        $branches[] = $row;
    }
}

// Date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch all data for all branches
$sales = $expenses = $credits = $purchases = $tanks = $dispensers = $price_history = $quality_tests = $variances = [];

// Sales
$sql = "SELECT st.*, b.branch_name, fd.dispenser_number, ft.name AS fuel_type, u.first_name, u.last_name FROM sales_transactions st JOIN branches b ON st.branch_id = b.id JOIN fuel_dispensers fd ON st.dispenser_id = fd.id JOIN fuel_types ft ON st.fuel_type_id = ft.id LEFT JOIN users u ON st.attendant_id = u.id WHERE st.transaction_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND st.deleted_at IS NULL ORDER BY st.transaction_date DESC, st.transaction_time DESC";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $sales[] = $row;
// Expenses
$sql = "SELECT e.*, b.branch_name, u.first_name AS approved_first, u.last_name AS approved_last FROM expenses e JOIN branches b ON e.branch_id = b.id LEFT JOIN users u ON e.approved_by = u.id WHERE e.expense_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND e.deleted_at IS NULL ORDER BY e.expense_date DESC, e.id DESC";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $expenses[] = $row;
// Credit Sales
$sql = "SELECT cs.*, b.branch_name, ca.company_name FROM credit_sales cs LEFT JOIN customer_accounts ca ON cs.customer_account_id = ca.id LEFT JOIN sales_transactions st ON cs.transaction_id = st.id JOIN branches b ON st.branch_id = b.id WHERE cs.due_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND cs.deleted_at IS NULL ORDER BY cs.due_date DESC";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $credits[] = $row;
// Purchases
$sql = "SELECT fp.*, b.branch_name, s.name AS supplier_name, ft.name AS fuel_type_name FROM fuel_purchases fp JOIN branches b ON fp.branch_id = b.id JOIN suppliers s ON fp.supplier_id = s.id JOIN fuel_types ft ON fp.fuel_type_id = ft.id WHERE fp.delivery_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND fp.deleted_at IS NULL ORDER BY fp.delivery_date DESC";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $purchases[] = $row;
// Storage Tanks
$sql = "SELECT st.*, b.branch_name, ft.name AS fuel_type_name FROM storage_tanks st JOIN branches b ON st.branch_id = b.id JOIN fuel_types ft ON st.fuel_type_id = ft.id WHERE st.deleted_at IS NULL ORDER BY b.branch_name, st.tank_number";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $tanks[] = $row;
// Dispensers
$sql = "SELECT fd.*, b.branch_name, st.tank_number, ft.name AS fuel_type_name FROM fuel_dispensers fd JOIN branches b ON fd.branch_id = b.id JOIN storage_tanks st ON fd.tank_id = st.id JOIN fuel_types ft ON st.fuel_type_id = ft.id WHERE fd.deleted_at IS NULL ORDER BY b.branch_name, fd.dispenser_number";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $dispensers[] = $row;
// Price History
$sql = "SELECT fph.*, b.branch_name, ft.name AS fuel_type_name FROM fuel_price_history fph JOIN branches b ON fph.branch_id = b.id JOIN fuel_types ft ON fph.fuel_type_id = ft.id WHERE fph.effective_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' ORDER BY fph.effective_date DESC";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $price_history[] = $row;
// Quality Tests
$sql = "SELECT fqt.*, b.branch_name, ft.name AS fuel_type_name, st.tank_number FROM fuel_quality_tests fqt JOIN branches b ON fqt.branch_id = b.id JOIN fuel_types ft ON fqt.fuel_type_id = ft.id LEFT JOIN storage_tanks st ON fqt.tank_id = st.id WHERE fqt.test_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND fqt.deleted_at IS NULL ORDER BY fqt.test_date DESC";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $quality_tests[] = $row;
// Variances
$sql = "SELECT fv.*, b.branch_name, st.tank_number, ft.name AS fuel_type_name FROM fuel_variances fv JOIN branches b ON fv.branch_id = b.id JOIN storage_tanks st ON fv.tank_id = st.id JOIN fuel_types ft ON st.fuel_type_id = ft.id WHERE fv.variance_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND fv.deleted_at IS NULL ORDER BY fv.variance_date DESC";
$res = mysqli_query($conn, $sql);
if ($res) while ($row = mysqli_fetch_assoc($res)) $variances[] = $row;

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="overall_report_' . date('Ymd_His') . '.xls"');
    echo "<html><head><meta charset='UTF-8'></head><body>";
    echo "<h2>Overall Business Report (All Branches)</h2>";
    echo "<p>Date Range: " . h($start_date) . " to " . h($end_date) . "</p>";
    // Output each section as a table (reuse HTML below, but no Bootstrap classes)
    // ... (for brevity, see HTML below for table structure)
    // You can copy the HTML tables below and remove Bootstrap classes for Excel export
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overall Business Report (All Branches)</title>
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
                <i class="bi bi-list"></i> Menu
            </button>
        </div>
        <h2 class="mb-4">Overall Business Report (All Branches)</h2>
        <form method="get" class="mb-4 row g-2 align-items-end">
            <div class="col-auto">
                <label for="start_date" class="form-label">Start Date:</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo h($start_date); ?>">
            </div>
            <div class="col-auto">
                <label for="end_date" class="form-label">End Date:</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo h($end_date); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">View Report</button>
            </div>
            <div class="col-auto">
                <a href="?start_date=<?php echo h($start_date); ?>&end_date=<?php echo h($end_date); ?>&export=excel" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Export to Excel
                </a>
            </div>
        </form>
        <!-- Sales Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Sales Transactions</div>
            <div class="card-body p-0">
                <div class="px-3 py-2">
                    <span class="fw-bold">Total Sales:</span> <span class="badge bg-success">UGX <?php echo number_format(array_sum(array_column($sales, 'final_amount')), 2); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Date</th><th>Time</th><th>Branch</th><th>Dispenser #</th><th>Fuel Type</th><th>Quantity</th><th>Unit Price</th><th>Final Amount</th><th>Payment</th><th>Attendant</th></tr></thead>
                        <tbody>
                            <?php foreach ($sales as $s): ?>
                                <tr><td><?php echo h($s['transaction_date']); ?></td><td><?php echo h($s['transaction_time']); ?></td><td><?php echo h($s['branch_name']); ?></td><td><?php echo h($s['dispenser_number']); ?></td><td><?php echo h($s['fuel_type']); ?></td><td><?php echo h($s['quantity']); ?></td><td><?php echo h($s['unit_price']); ?></td><td><?php echo h($s['final_amount']); ?></td><td><?php echo h($s['payment_method']); ?></td><td><?php echo h(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Expenses Section -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">Expenses</div>
            <div class="card-body p-0">
                <div class="px-3 py-2">
                    <span class="fw-bold">Total Expenses:</span> <span class="badge bg-danger">UGX <?php echo number_format(array_sum(array_column($expenses, 'amount')), 2); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Date</th><th>Branch</th><th>Category</th><th>Description</th><th>Amount</th><th>Payment Method</th><th>Status</th><th>Approved By</th></tr></thead>
                        <tbody>
                            <?php foreach ($expenses as $e): ?>
                                <tr><td><?php echo h($e['expense_date']); ?></td><td><?php echo h($e['branch_name']); ?></td><td><?php echo h($e['expense_category']); ?></td><td><?php echo h($e['description']); ?></td><td><?php echo h($e['amount']); ?></td><td><?php echo h($e['payment_method']); ?></td><td><?php echo h($e['status']); ?></td><td><?php echo h(($e['approved_first'] ?? '') . ' ' . ($e['approved_last'] ?? '')); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Credit Sales Section -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">Credit Sales</div>
            <div class="card-body p-0">
                <div class="px-3 py-2">
                    <span class="fw-bold">Total Credit Sales:</span> <span class="badge bg-warning text-dark">UGX <?php echo number_format(array_sum(array_column($credits, 'credit_amount')), 2); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Due Date</th><th>Branch</th><th>Customer</th><th>Credit Amount</th><th>Paid</th><th>Remaining</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($credits as $c): ?>
                                <tr><td><?php echo h($c['due_date']); ?></td><td><?php echo h($c['branch_name']); ?></td><td><?php echo h($c['company_name']); ?></td><td><?php echo h($c['credit_amount']); ?></td><td><?php echo h($c['paid_amount']); ?></td><td><?php echo h($c['remaining_balance']); ?></td><td><?php echo h($c['payment_status']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Purchases Section -->
        <div class="card mb-4">
            <div class="card-header bg-info text-dark">Fuel Purchases</div>
            <div class="card-body p-0">
                <div class="px-3 py-2">
                    <span class="fw-bold">Total Purchases:</span> <span class="badge bg-info text-dark">UGX <?php echo number_format(array_sum(array_column($purchases, 'total_cost')), 2); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Date</th><th>Branch</th><th>Fuel Type</th><th>Supplier</th><th>Quantity</th><th>Unit Cost</th><th>Total Cost</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($purchases as $p): ?>
                                <tr><td><?php echo h($p['delivery_date']); ?></td><td><?php echo h($p['branch_name']); ?></td><td><?php echo h($p['fuel_type_name']); ?></td><td><?php echo h($p['supplier_name']); ?></td><td><?php echo h($p['quantity_delivered']); ?></td><td><?php echo h($p['unit_cost']); ?></td><td><?php echo h($p['total_cost']); ?></td><td><?php echo h($p['payment_status']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Storage Tanks Section -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">Storage Tanks</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Branch</th><th>Tank #</th><th>Fuel Type</th><th>Capacity</th><th>Current Level</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($tanks as $t): ?>
                                <tr><td><?php echo h($t['branch_name']); ?></td><td><?php echo h($t['tank_number']); ?></td><td><?php echo h($t['fuel_type_name']); ?></td><td><?php echo h($t['capacity']); ?></td><td><?php echo h($t['current_level']); ?></td><td><?php echo h($t['status']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Dispensers Section -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">Dispensers</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Branch</th><th>Dispenser #</th><th>Tank #</th><th>Fuel Type</th><th>Pump Price</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($dispensers as $d): ?>
                                <tr><td><?php echo h($d['branch_name']); ?></td><td><?php echo h($d['dispenser_number']); ?></td><td><?php echo h($d['tank_number']); ?></td><td><?php echo h($d['fuel_type_name']); ?></td><td><?php echo h($d['pump_price']); ?></td><td><?php echo h($d['status']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Price History Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Price History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Date</th><th>Branch</th><th>Fuel Type</th><th>Old Price</th><th>New Price</th><th>Changed By</th><th>Reason</th></tr></thead>
                        <tbody>
                            <?php foreach ($price_history as $ph): ?>
                                <tr><td><?php echo h($ph['effective_date']); ?></td><td><?php echo h($ph['branch_name']); ?></td><td><?php echo h($ph['fuel_type_name']); ?></td><td><?php echo h($ph['old_price']); ?></td><td><?php echo h($ph['new_price']); ?></td><td><?php echo h($ph['changed_by']); ?></td><td><?php echo h($ph['reason']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Quality Tests Section -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">Quality Tests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Date</th><th>Branch</th><th>Fuel Type</th><th>Tank #</th><th>Test Type</th><th>Density</th><th>Octane</th><th>Water</th><th>Contamination</th><th>Result</th></tr></thead>
                        <tbody>
                            <?php foreach ($quality_tests as $qt): ?>
                                <tr><td><?php echo h($qt['test_date']); ?></td><td><?php echo h($qt['branch_name']); ?></td><td><?php echo h($qt['fuel_type_name']); ?></td><td><?php echo h($qt['tank_number']); ?></td><td><?php echo h($qt['test_type']); ?></td><td><?php echo h($qt['density']); ?></td><td><?php echo h($qt['octane_rating']); ?></td><td><?php echo h($qt['water_content']); ?></td><td><?php echo h($qt['contamination_level']); ?></td><td><?php echo h($qt['test_result']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Variances Section -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">Fuel Variances</div>
            <div class="card-body p-0">
                <div class="px-3 py-2">
                    <span class="fw-bold">Total Variance Value:</span> <span class="badge bg-warning text-dark">UGX <?php echo number_format(array_sum(array_column($variances, 'variance_value')), 2); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr><th>Date</th><th>Branch</th><th>Tank #</th><th>Fuel Type</th><th>Expected Qty</th><th>Actual Qty</th><th>Variance</th><th>Type</th><th>Reason</th><th>Value</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($variances as $v): ?>
                                <tr><td><?php echo h($v['variance_date']); ?></td><td><?php echo h($v['branch_name']); ?></td><td><?php echo h($v['tank_number']); ?></td><td><?php echo h($v['fuel_type_name']); ?></td><td><?php echo h($v['expected_quantity']); ?></td><td><?php echo h($v['actual_quantity']); ?></td><td><?php echo h($v['variance_quantity']); ?></td><td><?php echo h($v['variance_type']); ?></td><td><?php echo h($v['variance_reason']); ?></td><td><?php echo h($v['variance_value']); ?></td><td><?php echo h($v['status']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 