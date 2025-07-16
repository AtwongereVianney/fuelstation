<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';

// Fetch all branches
$branches = [];
$branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$branch_result = mysqli_query($conn, $branch_sql);
if ($branch_result) {
    while ($row = mysqli_fetch_assoc($branch_result)) {
        $branches[] = $row;
    }
}

// Fetch all fuel types
$fuel_types = [];
$fuel_sql = "SELECT id, name FROM fuel_types WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name";
$fuel_result = mysqli_query($conn, $fuel_sql);
if ($fuel_result) {
    while ($row = mysqli_fetch_assoc($fuel_result)) {
        $fuel_types[] = $row;
    }
}

// Fetch all suppliers
$suppliers = [];
$supplier_sql = "SELECT id, name FROM suppliers WHERE deleted_at IS NULL ORDER BY name";
$supplier_result = mysqli_query($conn, $supplier_sql);
if ($supplier_result) {
    while ($row = mysqli_fetch_assoc($supplier_result)) {
        $suppliers[] = $row;
    }
}

// Get selected filters
$selected_branch = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : ($branches[0]['id'] ?? null);
$selected_fuel = isset($_GET['fuel_type_id']) ? intval($_GET['fuel_type_id']) : ($fuel_types[0]['id'] ?? null);

// Fetch purchases
$purchases = [];
$where = "WHERE fp.deleted_at IS NULL";
if ($selected_branch) {
    $where .= " AND fp.branch_id = $selected_branch";
}
if ($selected_fuel) {
    $where .= " AND fp.fuel_type_id = $selected_fuel";
}
$sql = "SELECT fp.*, b.branch_name, ft.name AS fuel_type_name, s.name AS supplier_name FROM fuel_purchases fp JOIN branches b ON fp.branch_id = b.id JOIN fuel_types ft ON fp.fuel_type_id = ft.id JOIN suppliers s ON fp.supplier_id = s.id $where ORDER BY fp.delivery_date DESC LIMIT 50";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $purchases[] = $row;
    }
}

function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 260px; }
        @media (max-width: 991.98px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row flex-nowrap">
        <!-- Sidebar -->
        <div class="col-auto d-none d-md-block p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <!-- Main content -->
        <div class="col ps-md-4 pt-3 main-content">
            <div class="d-md-none mb-3">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="bi bi-list"></i> Menu
                </button>
            </div>
            <h2 class="mb-4">Purchases Management</h2>
            <form method="get" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="branch_id" class="form-label">Branch:</label>
                    </div>
                    <div class="col-auto">
                        <select name="branch_id" id="branch_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php if ($b['id'] == $selected_branch) echo 'selected'; ?>><?php echo h($b['branch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label for="fuel_type_id" class="form-label">Fuel Type:</label>
                    </div>
                    <div class="col-auto">
                        <select name="fuel_type_id" id="fuel_type_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($fuel_types as $ft): ?>
                                <option value="<?php echo $ft['id']; ?>" <?php if ($ft['id'] == $selected_fuel) echo 'selected'; ?>><?php echo h($ft['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
            <div class="d-flex justify-content-end mb-2">
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPurchaseModal"><i class="bi bi-plus"></i> Add Purchase</button>
            </div>
            <?php if ($purchases): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Branch</th>
                                <th>Fuel Type</th>
                                <th>Supplier</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $p): ?>
                                <tr>
                                    <td><?php echo h($p['delivery_date']); ?></td>
                                    <td><?php echo h($p['branch_name']); ?></td>
                                    <td><?php echo h($p['fuel_type_name']); ?></td>
                                    <td><?php echo h($p['supplier_name']); ?></td>
                                    <td><?php echo h($p['quantity_delivered']); ?></td>
                                    <td><?php echo h($p['unit_cost']); ?></td>
                                    <td><?php echo h($p['total_cost']); ?></td>
                                    <td><?php echo h($p['payment_status']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editPurchaseModal<?php echo $p['id']; ?>"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deletePurchaseModal<?php echo $p['id']; ?>"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
            <?php else: ?>
                <div class="alert alert-info">No purchases found for the selected filters.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="addPurchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="purchases/handle_action.php">
      <input type="hidden" name="action" value="add_purchase">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel); ?>">
      <input type="hidden" name="branch_id" value="<?php echo h($selected_branch); ?>">
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
                <option value="<?php echo $s['id']; ?>"><?php echo h($s['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Delivery Date</label>
            <input type="date" name="delivery_date" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Quantity Delivered</label>
            <input type="number" step="0.01" name="quantity_delivered" class="form-control" id="add_quantity_delivered" required oninput="updateAddTotalCost()">
          </div>
          <div class="mb-2">
            <label class="form-label">Unit Cost</label>
            <input type="number" step="0.01" name="unit_cost" class="form-control" id="add_unit_cost" required oninput="updateAddTotalCost()">
          </div>
          <div class="mb-2">
            <label class="form-label">Total Cost</label>
            <input type="number" step="0.01" name="total_cost" class="form-control" id="add_total_cost" readonly required>
          </div>
          <div class="mb-2">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" class="form-select" required>
              <option value="pending">Pending</option>
              <option value="partial">Partial</option>
              <option value="paid">Paid</option>
              <option value="overdue">Overdue</option>
            </select>
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

<!-- Edit & Delete Modals for each purchase -->
<?php foreach ($purchases as $p): ?>
<!-- Edit Modal -->
<div class="modal fade" id="editPurchaseModal<?php echo $p['id']; ?>" tabindex="-1" aria-labelledby="editPurchaseModalLabel<?php echo $p['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="purchases/handle_action.php">
      <input type="hidden" name="action" value="edit_purchase">
      <input type="hidden" name="purchase_id" value="<?php echo $p['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel); ?>">
      <input type="hidden" name="branch_id" value="<?php echo h($selected_branch); ?>">
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
                <option value="<?php echo $s['id']; ?>" <?php if ($s['id'] == $p['supplier_id']) echo 'selected'; ?>><?php echo h($s['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Delivery Date</label>
            <input type="date" name="delivery_date" class="form-control" value="<?php echo h($p['delivery_date']); ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Quantity Delivered</label>
            <input type="number" step="0.01" name="quantity_delivered" class="form-control" id="edit_quantity_delivered_<?php echo $p['id']; ?>" value="<?php echo h($p['quantity_delivered']); ?>" required oninput="updateEditTotalCost(<?php echo $p['id']; ?>)">
          </div>
          <div class="mb-2">
            <label class="form-label">Unit Cost</label>
            <input type="number" step="0.01" name="unit_cost" class="form-control" id="edit_unit_cost_<?php echo $p['id']; ?>" value="<?php echo h($p['unit_cost']); ?>" required oninput="updateEditTotalCost(<?php echo $p['id']; ?>)">
          </div>
          <div class="mb-2">
            <label class="form-label">Total Cost</label>
            <input type="number" step="0.01" name="total_cost" class="form-control" id="edit_total_cost_<?php echo $p['id']; ?>" value="<?php echo h($p['total_cost']); ?>" readonly required>
          </div>
          <div class="mb-2">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" class="form-select" required>
              <option value="pending" <?php if ($p['payment_status'] == 'pending') echo 'selected'; ?>>Pending</option>
              <option value="partial" <?php if ($p['payment_status'] == 'partial') echo 'selected'; ?>>Partial</option>
              <option value="paid" <?php if ($p['payment_status'] == 'paid') echo 'selected'; ?>>Paid</option>
              <option value="overdue" <?php if ($p['payment_status'] == 'overdue') echo 'selected'; ?>>Overdue</option>
            </select>
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
<!-- Delete Modal -->
<div class="modal fade" id="deletePurchaseModal<?php echo $p['id']; ?>" tabindex="-1" aria-labelledby="deletePurchaseModalLabel<?php echo $p['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="purchases/handle_action.php">
      <input type="hidden" name="action" value="delete_purchase">
      <input type="hidden" name="purchase_id" value="<?php echo $p['id']; ?>">
      <input type="hidden" name="fuel_type_id" value="<?php echo h($selected_fuel); ?>">
      <input type="hidden" name="branch_id" value="<?php echo h($selected_branch); ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deletePurchaseModalLabel<?php echo $p['id']; ?>">Delete Purchase</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this purchase record?</p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateAddTotalCost() {
    var qty = parseFloat(document.getElementById('add_quantity_delivered').value) || 0;
    var unit = parseFloat(document.getElementById('add_unit_cost').value) || 0;
    document.getElementById('add_total_cost').value = (qty * unit).toFixed(2);
}
<?php foreach ($purchases as $p): ?>
function updateEditTotalCost(id) {
    var qty = parseFloat(document.getElementById('edit_quantity_delivered_' + id).value) || 0;
    var unit = parseFloat(document.getElementById('edit_unit_cost_' + id).value) || 0;
    document.getElementById('edit_total_cost_' + id).value = (qty * unit).toFixed(2);
}
<?php endforeach; ?>
</script>
</body>
</html> 