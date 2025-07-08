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

// Get selected branch and date
$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : ($branches[0]['id'] ?? null);
$selected_date = isset($_GET['business_date']) ? $_GET['business_date'] : date('Y-m-d');

// Helper for safe output
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }

// Fetch daily sales summary
$summary = null;
if ($selected_branch_id && $selected_date) {
    $sql = "SELECT dss.*, u1.first_name AS prepared_first, u1.last_name AS prepared_last, u2.first_name AS approved_first, u2.last_name AS approved_last FROM daily_sales_summary dss LEFT JOIN users u1 ON dss.prepared_by = u1.id LEFT JOIN users u2 ON dss.approved_by = u2.id WHERE dss.branch_id = $selected_branch_id AND dss.business_date = '" . mysqli_real_escape_string($conn, $selected_date) . "' AND dss.deleted_at IS NULL LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res) $summary = mysqli_fetch_assoc($res);
}

// Fetch sales transactions for the day
$sales = [];
if ($selected_branch_id && $selected_date) {
    $sql = "SELECT st.*, fd.dispenser_number, ft.name AS fuel_type FROM sales_transactions st JOIN fuel_dispensers fd ON st.dispenser_id = fd.id JOIN fuel_types ft ON st.fuel_type_id = ft.id WHERE st.branch_id = $selected_branch_id AND st.transaction_date = '" . mysqli_real_escape_string($conn, $selected_date) . "' AND st.deleted_at IS NULL ORDER BY st.transaction_time";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $sales[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Summary</title>
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
            <h2 class="mb-4">Daily Sales Summary</h2>
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
                        <label for="business_date" class="form-label">Select Date:</label>
                        <input type="date" name="business_date" id="business_date" class="form-control" value="<?php echo h($selected_date); ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View Summary</button>
                    </div>
                </div>
            </form>
            <?php if ($summary): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Summary for <?php echo h($selected_date); ?></h5>
                        <div class="row g-3">
                            <div class="col-6 col-md-3"><strong>Total Transactions:</strong> <?php echo h($summary['total_transactions']); ?></div>
                            <div class="col-6 col-md-3"><strong>Total Quantity:</strong> <?php echo h($summary['total_quantity']); ?></div>
                            <div class="col-6 col-md-3"><strong>Total Sales:</strong> <?php echo h($summary['total_sales']); ?></div>
                            <div class="col-6 col-md-3"><strong>Cash Sales:</strong> <?php echo h($summary['cash_sales']); ?></div>
                            <div class="col-6 col-md-3"><strong>Card Sales:</strong> <?php echo h($summary['card_sales']); ?></div>
                            <div class="col-6 col-md-3"><strong>Mobile Money Sales:</strong> <?php echo h($summary['mobile_money_sales']); ?></div>
                            <div class="col-6 col-md-3"><strong>Credit Sales:</strong> <?php echo h($summary['credit_sales']); ?></div>
                            <div class="col-6 col-md-3"><strong>Discounts:</strong> <?php echo h($summary['total_discounts']); ?></div>
                            <div class="col-6 col-md-3"><strong>Taxes:</strong> <?php echo h($summary['total_taxes']); ?></div>
                            <div class="col-6 col-md-3"><strong>Status:</strong> <?php echo h($summary['status']); ?></div>
                            <div class="col-12 col-md-6"><strong>Prepared By:</strong> <?php echo h($summary['prepared_first'] . ' ' . $summary['prepared_last']); ?></div>
                            <div class="col-12 col-md-6"><strong>Approved By:</strong> <?php echo h($summary['approved_first'] . ' ' . $summary['approved_last']); ?></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">No summary found for this branch and date.</div>
            <?php endif; ?>
            <h5 class="mb-3">Sales Transactions</h5>
            <?php if ($sales): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Dispenser #</th>
                                <th>Fuel Type</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Final Amount</th>
                                <th>Payment</th>
                                <th>Attendant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $s): ?>
                                <tr>
                                    <td><?php echo h($s['transaction_time']); ?></td>
                                    <td><?php echo h($s['dispenser_number']); ?></td>
                                    <td><?php echo h($s['fuel_type']); ?></td>
                                    <td><?php echo h($s['quantity']); ?></td>
                                    <td><?php echo h($s['unit_price']); ?></td>
                                    <td><?php echo h($s['final_amount']); ?></td>
                                    <td><?php echo h($s['payment_method']); ?></td>
                                    <td><?php echo h($s['attendant_id']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
            <?php else: ?>
                <div class="alert alert-info">No sales transactions found for this branch and date.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 