<?php
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

// Fetch all fuel types for dropdown
$fuel_types = [];
$fuel_sql = "SELECT id, name, code, description, octane_rating, unit_of_measure FROM fuel_types WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name";
$fuel_result = mysqli_query($conn, $fuel_sql);
if ($fuel_result) {
    while ($row = mysqli_fetch_assoc($fuel_result)) {
        $fuel_types[] = $row;
    }
}

// Get selected fuel type
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
if ($selected_fuel_id) {
    // Storage Tanks
    $sql = "SELECT st.*, b.branch_name FROM storage_tanks st JOIN branches b ON st.branch_id = b.id WHERE st.fuel_type_id = $selected_fuel_id AND st.deleted_at IS NULL ORDER BY b.branch_name, st.tank_number";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $storage_tanks[] = $row;

    // Dispensers
    $sql = "SELECT fd.*, b.branch_name, st.tank_number FROM fuel_dispensers fd JOIN branches b ON fd.branch_id = b.id JOIN storage_tanks st ON fd.tank_id = st.id WHERE st.fuel_type_id = $selected_fuel_id AND fd.deleted_at IS NULL ORDER BY b.branch_name, fd.dispenser_number";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $dispensers[] = $row;

    // Purchases
    $sql = "SELECT fp.*, b.branch_name, s.name AS supplier_name FROM fuel_purchases fp JOIN branches b ON fp.branch_id = b.id JOIN suppliers s ON fp.supplier_id = s.id WHERE fp.fuel_type_id = $selected_fuel_id AND fp.deleted_at IS NULL ORDER BY fp.delivery_date DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $purchases[] = $row;

    // Sales
    $sql = "SELECT st.*, b.branch_name, fd.dispenser_number FROM sales_transactions st JOIN branches b ON st.branch_id = b.id JOIN fuel_dispensers fd ON st.dispenser_id = fd.id WHERE st.fuel_type_id = $selected_fuel_id AND st.deleted_at IS NULL ORDER BY st.transaction_date DESC, st.transaction_time DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $sales[] = $row;

    // Price History
    $sql = "SELECT fph.*, b.branch_name FROM fuel_price_history fph JOIN branches b ON fph.branch_id = b.id WHERE fph.fuel_type_id = $selected_fuel_id ORDER BY fph.effective_date DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $price_history[] = $row;

    // Quality Tests
    $sql = "SELECT fqt.*, b.branch_name, st.tank_number FROM fuel_quality_tests fqt JOIN branches b ON fqt.branch_id = b.id LEFT JOIN storage_tanks st ON fqt.tank_id = st.id WHERE fqt.fuel_type_id = $selected_fuel_id AND fqt.deleted_at IS NULL ORDER BY fqt.test_date DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $quality_tests[] = $row;

    // Variances
    $sql = "SELECT fv.*, b.branch_name, st.tank_number FROM fuel_variances fv JOIN branches b ON fv.branch_id = b.id JOIN storage_tanks st ON fv.tank_id = st.id WHERE st.fuel_type_id = $selected_fuel_id AND fv.deleted_at IS NULL ORDER BY fv.variance_date DESC LIMIT 20";
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
    header("Location: fuel_type_info.php?fuel_type_id=$fuel_type_id");
    exit;
}

// Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_tank') {
    $tank_id = intval($_POST['tank_id']);
    $fuel_type_id = intval($_POST['fuel_type_id']);
    // Get and sanitize other fields...
    // $branch_id = ... (see Add)
    // $tank_number = ...
    // $capacity = ...
    // $current_level = ...
    // $status = ...
    // $sql = \"UPDATE storage_tanks SET ... WHERE id = $tank_id\";
    // mysqli_query($conn, $sql);
    header("Location: fuel_type_info.php?fuel_type_id=$fuel_type_id");
    exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_tank') {
    $tank_id = intval($_POST['tank_id']);
    $fuel_type_id = intval($_POST['fuel_type_id']);
    $sql = "UPDATE storage_tanks SET deleted_at = NOW() WHERE id = $tank_id";
    mysqli_query($conn, $sql);
    header("Location: fuel_type_info.php?fuel_type_id=$fuel_type_id");
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
            <h2 class="mb-4">Fuel Type Information</h2>
            <form method="get" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="fuel_type_id" class="form-label">Select Fuel Type:</label>
                    </div>
                    <div class="col-auto">
                        <select name="fuel_type_id" id="fuel_type_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($fuel_types as $ft): ?>
                                <option value="<?php echo $ft['id']; ?>" <?php if ($ft['id'] == $selected_fuel_id) echo 'selected'; ?>><?php echo h($ft['name']); ?></option>
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
                            <button class="btn btn-success btn-sm"><i class="bi bi-plus"></i> Add Dispenser</button>
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
                            <button class="btn btn-success btn-sm"><i class="bi bi-plus"></i> Add Purchase</button>
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
                            <button class="btn btn-success btn-sm"><i class="bi bi-plus"></i> Add Sale</button>
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
                            <button class="btn btn-success btn-sm"><i class="bi bi-plus"></i> Add Price</button>
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
                            <button class="btn btn-success btn-sm"><i class="bi bi-plus"></i> Add Test</button>
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
                            <button class="btn btn-success btn-sm"><i class="bi bi-plus"></i> Add Variance</button>
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
</div>
<!-- Add Tank Modal -->
<div class="modal fade" id="addTankModal" tabindex="-1" aria-labelledby="addTankModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="">
      <input type="hidden" name="action" value="add_tank">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel_id); ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addTankModalLabel">Add Storage Tank</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Branch</label>
            <select name="branch_id" class="form-select" required>
              <option value="">Select Branch</option>
              <?php foreach ($all_branches as $branch): ?>
                <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
              <?php endforeach; ?>
            </select>
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
    <form method="post" action="">
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
            <input type="text" name="branch_name" class="form-control" value="<?php echo h($t['branch_name']); ?>" required>
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
    <form method="post" action="">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 