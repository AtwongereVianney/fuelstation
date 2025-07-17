<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';

// Fetch all branches for dropdowns
$all_branches = [];
$branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$branch_result = mysqli_query($conn, $branch_sql);
if ($branch_result) {
    while ($row = mysqli_fetch_assoc($branch_result)) {
        $all_branches[] = $row;
    }
}

$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : ($all_branches[0]['id'] ?? null);

// Fetch all fuel types for dropdown (optionally, you can filter by branch if needed)
$fuel_types = [];
$fuel_sql = "SELECT id, name, code, description, octane_rating, unit_of_measure FROM fuel_types WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name";
$fuel_result = mysqli_query($conn, $fuel_sql);
if ($fuel_result) {
    while ($row = mysqli_fetch_assoc($fuel_result)) {
        $fuel_types[] = $row;
    }
}
// Fetch all suppliers for dropdown
$suppliers = [];
$supplier_sql = "SELECT id, name FROM suppliers WHERE deleted_at IS NULL ORDER BY name";
$supplier_result = mysqli_query($conn, $supplier_sql);
if ($supplier_result) {
    while ($row = mysqli_fetch_assoc($supplier_result)) {
        $suppliers[] = $row;
    }
}

$selected_fuel_id = isset($_GET['fuel_type_id']) ? intval($_GET['fuel_type_id']) : ($fuel_types[0]['id'] ?? null);
$selected_fuel = null;
foreach ($fuel_types as $ft) {
    if ($ft['id'] == $selected_fuel_id) {
        $selected_fuel = $ft;
        break;
    }
}

// Helper function for safe output
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }

// Fetch related data if a fuel type is selected
$storage_tanks = $dispensers = $purchases = $sales = $price_history = $quality_tests = $variances = [];
if ($selected_fuel_id && $selected_branch_id) {
    // Storage Tanks
    $sql = "SELECT st.*, b.branch_name FROM storage_tanks st JOIN branches b ON st.branch_id = b.id WHERE st.fuel_type_id = $selected_fuel_id AND st.branch_id = $selected_branch_id AND st.deleted_at IS NULL ORDER BY st.tank_number";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $storage_tanks[] = $row;

    // Dispensers
    $sql = "SELECT fd.*, b.branch_name, st.tank_number FROM fuel_dispensers fd JOIN branches b ON fd.branch_id = b.id JOIN storage_tanks st ON fd.tank_id = st.id WHERE st.fuel_type_id = $selected_fuel_id AND fd.branch_id = $selected_branch_id AND fd.deleted_at IS NULL ORDER BY b.branch_name, fd.dispenser_number";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $dispensers[] = $row;

    // Purchases
    $sql = "SELECT fp.*, b.branch_name, s.name AS supplier_name FROM fuel_purchases fp JOIN branches b ON fp.branch_id = b.id JOIN suppliers s ON fp.supplier_id = s.id WHERE fp.fuel_type_id = $selected_fuel_id AND fp.branch_id = $selected_branch_id AND fp.deleted_at IS NULL ORDER BY fp.delivery_date DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $purchases[] = $row;

    // Sales
    $sql = "SELECT st.*, b.branch_name, fd.dispenser_number FROM sales_transactions st JOIN branches b ON st.branch_id = b.id JOIN fuel_dispensers fd ON st.dispenser_id = fd.id WHERE st.fuel_type_id = $selected_fuel_id AND st.branch_id = $selected_branch_id AND st.deleted_at IS NULL ORDER BY st.transaction_date DESC, st.transaction_time DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $sales[] = $row;

    // Price History
    $sql = "SELECT fph.*, b.branch_name FROM fuel_price_history fph JOIN branches b ON fph.branch_id = b.id WHERE fph.fuel_type_id = $selected_fuel_id AND fph.branch_id = $selected_branch_id ORDER BY fph.effective_date DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $price_history[] = $row;

    // Quality Tests
    $sql = "SELECT fqt.*, b.branch_name, st.tank_number FROM fuel_quality_tests fqt JOIN branches b ON fqt.branch_id = b.id LEFT JOIN storage_tanks st ON fqt.tank_id = st.id WHERE fqt.fuel_type_id = $selected_fuel_id AND fqt.branch_id = $selected_branch_id AND fqt.deleted_at IS NULL ORDER BY fqt.test_date DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $quality_tests[] = $row;

    // Variances
    $sql = "SELECT fv.*, b.branch_name, st.tank_number FROM fuel_variances fv JOIN branches b ON fv.branch_id = b.id JOIN storage_tanks st ON fv.tank_id = st.id WHERE st.fuel_type_id = $selected_fuel_id AND fv.branch_id = $selected_branch_id AND fv.deleted_at IS NULL ORDER BY fv.variance_date DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $variances[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_tank') {
    $fuel_type_id = intval($_POST['fuel_type_id']);
    $branch_id = intval($_POST['branch_id']);
    $tank_number = mysqli_real_escape_string($conn, $_POST['tank_number']);
    $capacity = floatval($_POST['capacity']);
    $current_level = floatval($_POST['current_level']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "INSERT INTO storage_tanks (branch_id, fuel_type_id, tank_number, capacity, current_level, status) VALUES ($branch_id, $fuel_type_id, '$tank_number', $capacity, $current_level, '$status')";
    mysqli_query($conn, $sql);
    header("Location: fuel_type_info.php?fuel_type_id=$fuel_type_id&branch_id=$branch_id&active_tab=tanks");
    exit;
}

// Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_tank') {
    $tank_id = intval($_POST['tank_id']);
    $fuel_type_id = intval($_POST['fuel_type_id']);
    $branch_id = intval($_POST['branch_id']);
    $tank_number = mysqli_real_escape_string($conn, $_POST['tank_number']);
    $capacity = floatval($_POST['capacity']);
    $current_level = floatval($_POST['current_level']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "UPDATE storage_tanks SET branch_id = $branch_id, tank_number = '$tank_number', capacity = $capacity, current_level = $current_level, status = '$status' WHERE id = $tank_id";
    mysqli_query($conn, $sql);
    header("Location: fuel_type_info.php?fuel_type_id=$fuel_type_id&branch_id=$branch_id&active_tab=tanks");
    exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_tank') {
    $tank_id = intval($_POST['tank_id']);
    $fuel_type_id = intval($_POST['fuel_type_id']);
    $sql = "UPDATE storage_tanks SET deleted_at = NOW() WHERE id = $tank_id";
    mysqli_query($conn, $sql);
    header("Location: fuel_type_info.php?fuel_type_id=$fuel_type_id&branch_id=$branch_id&active_tab=tanks");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Type Information</title>
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
+       <?php include '../includes/header.php'; ?>
            <!-- Mobile menu button -->
            <div class="d-md-none mb-3">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="fas fa-bars"></i> Menu
                </button>
            </div>
            <h2 class="mb-4">Fuel Type Information</h2>
            <form method="get" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="branch_id" class="form-label">Select Branch:</label>
                    </div>
                    <div class="col-auto">
                        <select name="branch_id" id="branch_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($all_branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php if ($b['id'] == ($_GET['branch_id'] ?? '')) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($b['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label for="fuel_type_id" class="form-label">Select Fuel Type:</label>
                    </div>
                    <div class="col-auto">
                        <select name="fuel_type_id" id="fuel_type_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($fuel_types as $ft): ?>
                                <option value="<?php echo $ft['id']; ?>" <?php if ($ft['id'] == ($_GET['fuel_type_id'] ?? '')) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($ft['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
            <?php if ($selected_fuel): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-2"><?php echo h($selected_fuel['name']); ?> (<?php echo h($selected_fuel['code']); ?>)</h4>
                        <p class="mb-1"><strong>Description:</strong> <?php echo h($selected_fuel['description']); ?></p>
                        <p class="mb-1"><strong>Octane Rating:</strong> <?php echo h($selected_fuel['octane_rating']); ?></p>
                        <p class="mb-1"><strong>Unit of Measure:</strong> <?php echo h($selected_fuel['unit_of_measure']); ?></p>
                    </div>
                </div>
                <ul class="nav nav-tabs mb-3" id="fuelTabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="tanks-tab" data-bs-toggle="tab" data-bs-target="#tanks" type="button" role="tab">Storage Tanks</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="dispensers-tab" data-bs-toggle="tab" data-bs-target="#dispensers" type="button" role="tab">Dispensers</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab">Purchases</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="price-tab" data-bs-toggle="tab" data-bs-target="#price" type="button" role="tab">Price History</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="quality-tab" data-bs-toggle="tab" data-bs-target="#quality" type="button" role="tab">Quality Tests</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="variance-tab" data-bs-toggle="tab" data-bs-target="#variance" type="button" role="tab">Variances</button></li>
                </ul>
                <div class="tab-content" id="fuelTabsContent">
                    <div class="tab-pane fade show active" id="tanks" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addTankModal">
                                <i class="bi bi-plus"></i> Add Tank
                            </button>
                        </div>
                        <?php if ($storage_tanks): ?>
                            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Branch</th><th>Tank #</th><th>Capacity</th><th>Current Level</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
                                <?php foreach ($storage_tanks as $t): ?>
                                    <tr><td><?php echo h($t['branch_name']); ?></td><td><?php echo h($t['tank_number']); ?></td><td><?php echo h($t['capacity']); ?></td><td><?php echo h($t['current_level']); ?></td><td><?php echo h($t['status']); ?></td><td class="text-end"><button class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editTankModal<?php echo $t['id']; ?>"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteTankModal<?php echo $t['id']; ?>"><i class="bi bi-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                        <?php else: ?><div class="alert alert-info">No storage tanks found for this fuel type.</div><?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="dispensers" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addDispenserModal"><i class="bi bi-plus"></i> Add Dispenser</button>
                        </div>
                        <?php if ($dispensers): ?>
                            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Branch</th><th>Dispenser #</th><th>Tank #</th><th>Pump Price</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
                                <?php foreach ($dispensers as $d): ?>
                                    <tr><td><?php echo h($d['branch_name']); ?></td><td><?php echo h($d['dispenser_number']); ?></td><td><?php echo h($d['tank_number']); ?></td><td><?php echo h($d['pump_price']); ?></td><td><?php echo h($d['status']); ?></td><td class="text-end"><button class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                        <?php else: ?><div class="alert alert-info">No dispensers found for this fuel type.</div><?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="purchases" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPurchaseModal"><i class="bi bi-plus"></i> Add Purchase</button>
                        </div>
                        <?php if ($purchases): ?>
                            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Date</th><th>Branch</th><th>Supplier</th><th>Quantity</th><th>Unit Cost</th><th>Total Cost</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
                                <?php foreach ($purchases as $p): ?>
                                    <tr><td><?php echo h($p['delivery_date']); ?></td><td><?php echo h($p['branch_name']); ?></td><td><?php echo h($p['supplier_name']); ?></td><td><?php echo h($p['quantity_delivered']); ?></td><td><?php echo h($p['unit_cost']); ?></td><td><?php echo h($p['total_cost']); ?></td><td><?php echo h($p['payment_status']); ?></td><td class="text-end"><button class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                        <?php else: ?><div class="alert alert-info">No purchases found for this fuel type.</div><?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="sales" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSaleModal"><i class="bi bi-plus"></i> Add Sale</button>
                        </div>
                        <?php if ($sales): ?>
                            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Date</th><th>Time</th><th>Branch</th><th>Dispenser #</th><th>Quantity</th><th>Unit Price</th><th>Final Amount</th><th>Payment</th><th class="text-end">Actions</th></tr></thead><tbody>
                                <?php foreach ($sales as $s): ?>
                                    <tr><td><?php echo h($s['transaction_date']); ?></td><td><?php echo h($s['transaction_time']); ?></td><td><?php echo h($s['branch_name']); ?></td><td><?php echo h($s['dispenser_number']); ?></td><td><?php echo h($s['quantity']); ?></td><td><?php echo h($s['unit_price']); ?></td><td><?php echo h($s['final_amount']); ?></td><td><?php echo h($s['payment_method']); ?></td><td class="text-end"><button class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                        <?php else: ?><div class="alert alert-info">No sales found for this fuel type.</div><?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="price" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPriceModal"><i class="bi bi-plus"></i> Add Price</button>
                        </div>
                        <?php if ($price_history): ?>
                            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Branch</th><th>Old Price</th><th>New Price</th><th>Effective Date</th><th>Changed By</th><th>Reason</th><th class="text-end">Actions</th></tr></thead><tbody>
                                <?php foreach ($price_history as $ph): ?>
                                    <tr><td><?php echo h($ph['branch_name']); ?></td><td><?php echo h($ph['old_price']); ?></td><td><?php echo h($ph['new_price']); ?></td><td><?php echo h($ph['effective_date']); ?></td><td><?php echo h($ph['changed_by']); ?></td><td><?php echo h($ph['reason']); ?></td><td class="text-end"><button class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                        <?php else: ?><div class="alert alert-info">No price history found for this fuel type.</div><?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="quality" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addQualityTestModal"><i class="bi bi-plus"></i> Add Test</button>
                        </div>
                        <?php if ($quality_tests): ?>
                            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Date</th><th>Branch</th><th>Tank #</th><th>Type</th><th>Density</th><th>Octane</th><th>Result</th><th>Tested By</th><th class="text-end">Actions</th></tr></thead><tbody>
                                <?php foreach ($quality_tests as $qt): ?>
                                    <tr><td><?php echo h($qt['test_date']); ?></td><td><?php echo h($qt['branch_name']); ?></td><td><?php echo h($qt['tank_number']); ?></td><td><?php echo h($qt['test_type']); ?></td><td><?php echo h($qt['density']); ?></td><td><?php echo h($qt['octane_rating']); ?></td><td><?php echo h($qt['test_result']); ?></td><td><?php echo h($qt['tested_by']); ?></td><td class="text-end"><button class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                        <?php else: ?><div class="alert alert-info">No quality tests found for this fuel type.</div><?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="variance" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addVarianceModal"><i class="bi bi-plus"></i> Add Variance</button>
                        </div>
                        <?php if ($variances): ?>
                            <div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead><tr><th>Date</th><th>Branch</th><th>Tank #</th><th>Expected Qty</th><th>Actual Qty</th><th>Variance</th><th>Type</th><th>Reason</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
                                <?php foreach ($variances as $v): ?>
                                    <tr><td><?php echo h($v['variance_date']); ?></td><td><?php echo h($v['branch_name']); ?></td><td><?php echo h($v['tank_number']); ?></td><td><?php echo h($v['expected_quantity']); ?></td><td><?php echo h($v['actual_quantity']); ?></td><td><?php echo h($v['variance_quantity']); ?></td><td><?php echo h($v['variance_type']); ?></td><td><?php echo h($v['variance_reason']); ?></td><td><?php echo h($v['status']); ?></td><td class="text-end"><button class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil"></i></button><button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                        <?php else: ?><div class="alert alert-info">No variances found for this fuel type.</div><?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">No fuel type selected or available.</div>
            <?php endif; ?>
    </div>
</div>
<!-- Add Tank Modal -->
<div class="modal fade" id="addTankModal" tabindex="-1" aria-labelledby="addTankModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="storage_tanks/handle_action.php">
      <input type="hidden" name="action" value="add_tank">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addTankModalLabel">Add Storage Tank</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Branch</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($all_branches[array_search($selected_branch_id, array_column($all_branches, 'id'))]['branch_name']); ?>" disabled>
          </div>
          <div class="mb-2">
            <label class="form-label">Tank Number</label>
            <input type="text" name="tank_number" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Capacity</label>
            <input type="number" name="capacity" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Current Level</label>
            <input type="number" name="current_level" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Status</label>
            <input type="text" name="status" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Tank</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php foreach ($storage_tanks as $t): ?>
<div class="modal fade" id="editTankModal<?php echo $t['id']; ?>" tabindex="-1" aria-labelledby="editTankModalLabel<?php echo $t['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="storage_tanks/handle_action.php">
      <input type="hidden" name="action" value="edit_tank">
      <input type="hidden" name="tank_id" value="<?php echo $t['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editTankModalLabel<?php echo $t['id']; ?>">Edit Storage Tank</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Branch</label>
            <select name="branch_id" class="form-select" required>
              <option value="">Select Branch</option>
              <?php foreach ($all_branches as $branch): ?>
                <option value="<?php echo $branch['id']; ?>" <?php if ($branch['id'] == $t['branch_id']) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($branch['branch_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Tank Number</label>
            <input type="text" name="tank_number" class="form-control" value="<?php echo h($t['tank_number']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Capacity</label>
            <input type="number" name="capacity" class="form-control" value="<?php echo h($t['capacity']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Current Level</label>
            <input type="number" name="current_level" class="form-control" value="<?php echo h($t['current_level']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Status</label>
            <input type="text" name="status" class="form-control" value="<?php echo h($t['status']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="deleteTankModal<?php echo $t['id']; ?>" tabindex="-1" aria-labelledby="deleteTankModalLabel<?php echo $t['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="storage_tanks/handle_action.php">
      <input type="hidden" name="action" value="delete_tank">
      <input type="hidden" name="tank_id" value="<?php echo $t['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteTankModalLabel<?php echo $t['id']; ?>">Delete Storage Tank</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete tank <strong><?php echo h($t['tank_number']); ?></strong>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<!-- Add Dispenser Modal -->
<div class="modal fade" id="addDispenserModal" tabindex="-1" aria-labelledby="addDispenserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="dispensers/handle_action.php">
      <input type="hidden" name="action" value="add_dispenser">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addDispenserModalLabel">Add Dispenser</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Tank</label>
            <select name="tank_id" class="form-select" required>
              <option value="">Select Tank</option>
              <?php foreach ($storage_tanks as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo h($t['tank_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Dispenser Number</label>
            <input type="text" name="dispenser_number" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Pump Price</label>
            <input type="number" step="0.01" name="pump_price" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Status</label>
            <input type="text" name="status" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Dispenser</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php foreach ($dispensers as $d): ?>
<div class="modal fade" id="editDispenserModal<?php echo $d['id']; ?>" tabindex="-1" aria-labelledby="editDispenserModalLabel<?php echo $d['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="dispensers/handle_action.php">
      <input type="hidden" name="action" value="edit_dispenser">
      <input type="hidden" name="dispenser_id" value="<?php echo $d['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editDispenserModalLabel<?php echo $d['id']; ?>">Edit Dispenser</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Tank</label>
            <select name="tank_id" class="form-select" required>
              <option value="">Select Tank</option>
              <?php foreach ($storage_tanks as $t): ?>
                <option value="<?php echo $t['id']; ?>" <?php if ($t['id'] == $d['tank_id']) echo 'selected'; ?>><?php echo h($t['tank_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Dispenser Number</label>
            <input type="text" name="dispenser_number" class="form-control" value="<?php echo h($d['dispenser_number']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Pump Price</label>
            <input type="number" step="0.01" name="pump_price" class="form-control" value="<?php echo h($d['pump_price']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Status</label>
            <input type="text" name="status" class="form-control" value="<?php echo h($d['status']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="deleteDispenserModal<?php echo $d['id']; ?>" tabindex="-1" aria-labelledby="deleteDispenserModalLabel<?php echo $d['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="dispensers/handle_action.php">
      <input type="hidden" name="action" value="delete_dispenser">
      <input type="hidden" name="dispenser_id" value="<?php echo $d['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteDispenserModalLabel<?php echo $d['id']; ?>">Delete Dispenser</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete dispenser <strong><?php echo h($d['dispenser_number']); ?></strong>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="addPurchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="purchases/handle_action.php">
      <input type="hidden" name="action" value="add_purchase">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addPurchaseModalLabel">Add Purchase</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-select" required>
              <option value="">Select Supplier</option>
              <?php foreach ($suppliers as $s): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Delivery Date</label>
            <input type="date" name="delivery_date" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Quantity Delivered</label>
            <input type="number" step="0.01" name="quantity_delivered" id="quantity_delivered" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Unit Cost</label>
            <input type="number" step="0.01" name="unit_cost" id="unit_cost" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Total Cost</label>
            <input type="number" step="0.01" name="total_cost" id="total_cost" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Payment Status</label>
            <input type="text" name="payment_status" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Purchase</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php foreach ($purchases as $p): ?>
<div class="modal fade" id="editPurchaseModal<?php echo $p['id']; ?>" tabindex="-1" aria-labelledby="editPurchaseModalLabel<?php echo $p['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="purchases/handle_action.php">
      <input type="hidden" name="action" value="edit_purchase">
      <input type="hidden" name="purchase_id" value="<?php echo $p['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editPurchaseModalLabel<?php echo $p['id']; ?>">Edit Purchase</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-select" required>
              <option value="">Select Supplier</option>
              <?php foreach ($suppliers as $s): ?>
                <option value="<?php echo $s['id']; ?>" <?php if ($s['id'] == $p['supplier_id']) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($s['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Delivery Date</label>
            <input type="date" name="delivery_date" class="form-control" value="<?php echo h($p['delivery_date']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Quantity Delivered</label>
            <input type="number" step="0.01" name="quantity_delivered" id="quantity_delivered<?php echo $p['id']; ?>" class="form-control" value="<?php echo h($p['quantity_delivered']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Unit Cost</label>
            <input type="number" step="0.01" name="unit_cost" id="unit_cost<?php echo $p['id']; ?>" class="form-control" value="<?php echo h($p['unit_cost']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Total Cost</label>
            <input type="number" step="0.01" name="total_cost" id="total_cost<?php echo $p['id']; ?>" class="form-control" value="<?php echo h($p['total_cost']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Payment Status</label>
            <input type="text" name="payment_status" class="form-control" value="<?php echo h($p['payment_status']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="deletePurchaseModal<?php echo $p['id']; ?>" tabindex="-1" aria-labelledby="deletePurchaseModalLabel<?php echo $p['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="purchases/handle_action.php">
      <input type="hidden" name="action" value="delete_purchase">
      <input type="hidden" name="purchase_id" value="<?php echo $p['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deletePurchaseModalLabel<?php echo $p['id']; ?>">Delete Purchase</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this purchase?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="sales/handle_action.php">
      <input type="hidden" name="action" value="add_sale">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addSaleModalLabel">Add Sale</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Dispenser</label>
            <select name="dispenser_id" class="form-select" required>
              <option value="">Select Dispenser</option>
              <?php foreach ($dispensers as $d): ?>
                <option value="<?php echo $d['id']; ?>"><?php echo h($d['dispenser_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Transaction Date</label>
            <input type="date" name="transaction_date" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Transaction Time</label>
            <input type="time" name="transaction_time" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.01" name="quantity" id="sale_quantity" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Unit Price</label>
            <input type="number" step="0.01" name="unit_price" id="sale_unit_price" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Final Amount</label>
            <input type="number" step="0.01" name="final_amount" id="sale_final_amount" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Payment Method</label>
            <input type="text" name="payment_method" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Attendant ID</label>
            <input type="number" name="attendant_id" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Sale</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php foreach ($sales as $s): ?>
<div class="modal fade" id="editSaleModal<?php echo $s['id']; ?>" tabindex="-1" aria-labelledby="editSaleModalLabel<?php echo $s['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="sales/handle_action.php">
      <input type="hidden" name="action" value="edit_sale">
      <input type="hidden" name="sale_id" value="<?php echo $s['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editSaleModalLabel<?php echo $s['id']; ?>">Edit Sale</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Dispenser</label>
            <select name="dispenser_id" class="form-select" required>
              <option value="">Select Dispenser</option>
              <?php foreach ($dispensers as $d): ?>
                <option value="<?php echo $d['id']; ?>" <?php if ($d['id'] == $s['dispenser_id']) echo 'selected'; ?>><?php echo h($d['dispenser_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Transaction Time</label>
            <input type="time" name="transaction_time" class="form-control" value="<?php echo h($s['transaction_time']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.01" name="quantity" id="sale_quantity<?php echo $s['id']; ?>" class="form-control" value="<?php echo h($s['quantity']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Unit Price</label>
            <input type="number" step="0.01" name="unit_price" id="sale_unit_price<?php echo $s['id']; ?>" class="form-control" value="<?php echo h($s['unit_price']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Final Amount</label>
            <input type="number" step="0.01" name="final_amount" id="sale_final_amount<?php echo $s['id']; ?>" class="form-control" value="<?php echo h($s['final_amount']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Payment Method</label>
            <input type="text" name="payment_method" class="form-control" value="<?php echo h($s['payment_method']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Attendant ID</label>
            <input type="number" name="attendant_id" class="form-control" value="<?php echo h($s['attendant_id']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="deleteSaleModal<?php echo $s['id']; ?>" tabindex="-1" aria-labelledby="deleteSaleModalLabel<?php echo $s['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="sales/handle_action.php">
      <input type="hidden" name="action" value="delete_sale">
      <input type="hidden" name="sale_id" value="<?php echo $s['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteSaleModalLabel<?php echo $s['id']; ?>">Delete Sale</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this sale?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<!-- Add Price History Modal -->
<div class="modal fade" id="addPriceModal" tabindex="-1" aria-labelledby="addPriceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="price_history/handle_action.php">
      <input type="hidden" name="action" value="add_price">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addPriceModalLabel">Add Price History</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Old Price</label>
            <input type="number" step="0.01" name="old_price" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">New Price</label>
            <input type="number" step="0.01" name="new_price" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Effective Date</label>
            <input type="date" name="effective_date" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Changed By</label>
            <input type="text" name="changed_by" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Reason</label>
            <input type="text" name="reason" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Price</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php foreach ($price_history as $ph): ?>
<div class="modal fade" id="editPriceModal<?php echo $ph['id']; ?>" tabindex="-1" aria-labelledby="editPriceModalLabel<?php echo $ph['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="price_history/handle_action.php">
      <input type="hidden" name="action" value="edit_price">
      <input type="hidden" name="price_id" value="<?php echo $ph['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editPriceModalLabel<?php echo $ph['id']; ?>">Edit Price History</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Old Price</label>
            <input type="number" step="0.01" name="old_price" class="form-control" value="<?php echo h($ph['old_price']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">New Price</label>
            <input type="number" step="0.01" name="new_price" class="form-control" value="<?php echo h($ph['new_price']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Effective Date</label>
            <input type="date" name="effective_date" class="form-control" value="<?php echo h($ph['effective_date']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Changed By</label>
            <input type="text" name="changed_by" class="form-control" value="<?php echo h($ph['changed_by']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Reason</label>
            <input type="text" name="reason" class="form-control" value="<?php echo h($ph['reason']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="deletePriceModal<?php echo $ph['id']; ?>" tabindex="-1" aria-labelledby="deletePriceModalLabel<?php echo $ph['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="price_history/handle_action.php">
      <input type="hidden" name="action" value="delete_price">
      <input type="hidden" name="price_id" value="<?php echo $ph['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deletePriceModalLabel<?php echo $ph['id']; ?>">Delete Price History</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this price history record?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<!-- Add Quality Test Modal -->
<div class="modal fade" id="addQualityTestModal" tabindex="-1" aria-labelledby="addQualityTestModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="quality_tests/handle_action.php">
      <input type="hidden" name="action" value="add_quality_test">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addQualityTestModalLabel">Add Quality Test</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Tank</label>
            <select name="tank_id" class="form-select" required>
              <option value="">Select Tank</option>
              <?php foreach ($storage_tanks as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo h($t['tank_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Test Date</label>
            <input type="date" name="test_date" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Test Type</label>
            <input type="text" name="test_type" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Density</label>
            <input type="number" step="0.01" name="density" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Octane Rating</label>
            <input type="number" step="0.01" name="octane_rating" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Test Result</label>
            <input type="text" name="test_result" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Tested By</label>
            <input type="text" name="tested_by" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Test</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php foreach ($quality_tests as $qt): ?>
<div class="modal fade" id="editQualityTestModal<?php echo $qt['id']; ?>" tabindex="-1" aria-labelledby="editQualityTestModalLabel<?php echo $qt['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="quality_tests/handle_action.php">
      <input type="hidden" name="action" value="edit_quality_test">
      <input type="hidden" name="quality_test_id" value="<?php echo $qt['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editQualityTestModalLabel<?php echo $qt['id']; ?>">Edit Quality Test</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Tank</label>
            <select name="tank_id" class="form-select" required>
              <option value="">Select Tank</option>
              <?php foreach ($storage_tanks as $t): ?>
                <option value="<?php echo $t['id']; ?>" <?php if ($t['id'] == $qt['tank_id']) echo 'selected'; ?>><?php echo h($t['tank_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Test Date</label>
            <input type="date" name="test_date" class="form-control" value="<?php echo h($qt['test_date']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Test Type</label>
            <input type="text" name="test_type" class="form-control" value="<?php echo h($qt['test_type']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Density</label>
            <input type="number" step="0.01" name="density" class="form-control" value="<?php echo h($qt['density']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Octane Rating</label>
            <input type="number" step="0.01" name="octane_rating" class="form-control" value="<?php echo h($qt['octane_rating']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Test Result</label>
            <input type="text" name="test_result" class="form-control" value="<?php echo h($qt['test_result']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Tested By</label>
            <input type="text" name="tested_by" class="form-control" value="<?php echo h($qt['tested_by']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="deleteQualityTestModal<?php echo $qt['id']; ?>" tabindex="-1" aria-labelledby="deleteQualityTestModalLabel<?php echo $qt['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="quality_tests/handle_action.php">
      <input type="hidden" name="action" value="delete_quality_test">
      <input type="hidden" name="quality_test_id" value="<?php echo $qt['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteQualityTestModalLabel<?php echo $qt['id']; ?>">Delete Quality Test</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this quality test?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<!-- Add Variance Modal -->
<div class="modal fade" id="addVarianceModal" tabindex="-1" aria-labelledby="addVarianceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="variance/handle_action.php">
      <input type="hidden" name="action" value="add_variance">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addVarianceModalLabel">Add Variance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Tank</label>
            <select name="tank_id" class="form-select" required>
              <option value="">Select Tank</option>
              <?php foreach ($storage_tanks as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo h($t['tank_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Variance Date</label>
            <input type="date" name="variance_date" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Expected Quantity</label>
            <input type="number" step="0.01" name="expected_quantity" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Actual Quantity</label>
            <input type="number" step="0.01" name="actual_quantity" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Variance Quantity</label>
            <input type="number" step="0.01" name="variance_quantity" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Variance Type</label>
            <input type="text" name="variance_type" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Variance Reason</label>
            <input type="text" name="variance_reason" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Status</label>
            <input type="text" name="status" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Variance</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php foreach ($variances as $v): ?>
<div class="modal fade" id="editVarianceModal<?php echo $v['id']; ?>" tabindex="-1" aria-labelledby="editVarianceModalLabel<?php echo $v['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="variance/handle_action.php">
      <input type="hidden" name="action" value="edit_variance">
      <input type="hidden" name="variance_id" value="<?php echo $v['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editVarianceModalLabel<?php echo $v['id']; ?>">Edit Variance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Tank</label>
            <select name="tank_id" class="form-select" required>
              <option value="">Select Tank</option>
              <?php foreach ($storage_tanks as $t): ?>
                <option value="<?php echo $t['id']; ?>" <?php if ($t['id'] == $v['tank_id']) echo 'selected'; ?>><?php echo h($t['tank_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Variance Date</label>
            <input type="date" name="variance_date" class="form-control" value="<?php echo h($v['variance_date']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Expected Quantity</label>
            <input type="number" step="0.01" name="expected_quantity" class="form-control" value="<?php echo h($v['expected_quantity']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Actual Quantity</label>
            <input type="number" step="0.01" name="actual_quantity" class="form-control" value="<?php echo h($v['actual_quantity']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Variance Quantity</label>
            <input type="number" step="0.01" name="variance_quantity" class="form-control" value="<?php echo h($v['variance_quantity']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Variance Type</label>
            <input type="text" name="variance_type" class="form-control" value="<?php echo h($v['variance_type']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Variance Reason</label>
            <input type="text" name="variance_reason" class="form-control" value="<?php echo h($v['variance_reason']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Status</label>
            <input type="text" name="status" class="form-control" value="<?php echo h($v['status']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="deleteVarianceModal<?php echo $v['id']; ?>" tabindex="-1" aria-labelledby="deleteVarianceModalLabel<?php echo $v['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="variance/handle_action.php">
      <input type="hidden" name="action" value="delete_variance">
      <input type="hidden" name="variance_id" value="<?php echo $v['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <input type="hidden" name="branch_id" value="<?php echo (int)$selected_branch_id; ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteVarianceModalLabel<?php echo $v['id']; ?>">Delete Variance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this variance?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ... existing code ...
// Auto-calculate Total Cost in Purchase Modals
function updateTotalCost(prefix = '') {
  var qty = parseFloat(document.getElementById(prefix + 'quantity_delivered')?.value) || 0;
  var price = parseFloat(document.getElementById(prefix + 'unit_cost')?.value) || 0;
  if(document.getElementById(prefix + 'total_cost')) {
    document.getElementById(prefix + 'total_cost').value = (qty * price).toFixed(2);
  }
}
// Add Purchase Modal
if (document.getElementById('quantity_delivered')) {
  document.getElementById('quantity_delivered').addEventListener('input', function(){updateTotalCost('');});
}
if (document.getElementById('unit_cost')) {
  document.getElementById('unit_cost').addEventListener('input', function(){updateTotalCost('');});
}
// Edit Purchase Modals (for each purchase row)
<?php foreach ($purchases as $p): ?>
if (document.getElementById('quantity_delivered<?php echo $p['id']; ?>')) {
  document.getElementById('quantity_delivered<?php echo $p['id']; ?>').addEventListener('input', function(){updateTotalCost('<?php echo $p['id']; ?>');});
}
if (document.getElementById('unit_cost<?php echo $p['id']; ?>')) {
  document.getElementById('unit_cost<?php echo $p['id']; ?>').addEventListener('input', function(){updateTotalCost('<?php echo $p['id']; ?>');});
}
<?php endforeach; ?>
// ... existing code ...
// Auto-calculate Final Amount in Sale Modals
function updateSaleFinalAmount(prefix = '') {
  var qty = parseFloat(document.getElementById(prefix + 'sale_quantity')?.value) || 0;
  var price = parseFloat(document.getElementById(prefix + 'sale_unit_price')?.value) || 0;
  if(document.getElementById(prefix + 'sale_final_amount')) {
    document.getElementById(prefix + 'sale_final_amount').value = (qty * price).toFixed(2);
  }
}
// Add Sale Modal
if (document.getElementById('sale_quantity')) {
  document.getElementById('sale_quantity').addEventListener('input', function(){updateSaleFinalAmount('');});
}
if (document.getElementById('sale_unit_price')) {
  document.getElementById('sale_unit_price').addEventListener('input', function(){updateSaleFinalAmount('');});
}
// Edit Sale Modals (for each sale row)
<?php foreach ($sales as $s): ?>
if (document.getElementById('sale_quantity<?php echo $s['id']; ?>')) {
  document.getElementById('sale_quantity<?php echo $s['id']; ?>').addEventListener('input', function(){updateSaleFinalAmount('<?php echo $s['id']; ?>');});
}
if (document.getElementById('sale_unit_price<?php echo $s['id']; ?>')) {
  document.getElementById('sale_unit_price<?php echo $s['id']; ?>').addEventListener('input', function(){updateSaleFinalAmount('<?php echo $s['id']; ?>');});
}
<?php endforeach; ?>
// ... existing code ...
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const activeTab = urlParams.get('active_tab');
  if (activeTab) {
    const tabTrigger = document.querySelector(`[data-bs-target="#${activeTab}"]`);
    if (tabTrigger) {
      var tab = new bootstrap.Tab(tabTrigger);
      tab.show();
    }
  }
});
</script>
</body>
</html> 