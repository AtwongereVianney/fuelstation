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

// Handle Add/Edit Summary
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Edit Summary
    if (isset($_POST['add_summary']) || isset($_POST['edit_summary'])) {
        $branch_id = intval($_POST['branch_id']);
        $business_date = mysqli_real_escape_string($conn, $_POST['business_date']);
        $total_transactions = intval($_POST['total_transactions']);
        $total_quantity = floatval($_POST['total_quantity']);
        $total_sales = floatval($_POST['total_sales']);
        $cash_sales = floatval($_POST['cash_sales']);
        $card_sales = floatval($_POST['card_sales']);
        $mobile_money_sales = floatval($_POST['mobile_money_sales']);
        $credit_sales = floatval($_POST['credit_sales']);
        $total_discounts = floatval($_POST['total_discounts']);
        $total_taxes = floatval($_POST['total_taxes']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $prepared_by = intval($_POST['prepared_by']);
        $approved_by = intval($_POST['approved_by']);
        if (isset($_POST['add_summary'])) {
            $sql = "INSERT INTO daily_sales_summary (branch_id, business_date, total_transactions, total_quantity, total_sales, cash_sales, card_sales, mobile_money_sales, credit_sales, total_discounts, total_taxes, status, prepared_by, approved_by) VALUES ($branch_id, '$business_date', $total_transactions, $total_quantity, $total_sales, $cash_sales, $card_sales, $mobile_money_sales, $credit_sales, $total_discounts, $total_taxes, '$status', $prepared_by, $approved_by) ON DUPLICATE KEY UPDATE total_transactions=VALUES(total_transactions), total_quantity=VALUES(total_quantity), total_sales=VALUES(total_sales), cash_sales=VALUES(cash_sales), card_sales=VALUES(card_sales), mobile_money_sales=VALUES(mobile_money_sales), credit_sales=VALUES(credit_sales), total_discounts=VALUES(total_discounts), total_taxes=VALUES(total_taxes), status=VALUES(status), prepared_by=VALUES(prepared_by), approved_by=VALUES(approved_by), deleted_at=NULL";
            mysqli_query($conn, $sql);
        } elseif (isset($_POST['edit_summary'])) {
            $id = intval($_POST['summary_id']);
            $sql = "UPDATE daily_sales_summary SET total_transactions=$total_transactions, total_quantity=$total_quantity, total_sales=$total_sales, cash_sales=$cash_sales, card_sales=$card_sales, mobile_money_sales=$mobile_money_sales, credit_sales=$credit_sales, total_discounts=$total_discounts, total_taxes=$total_taxes, status='$status', prepared_by=$prepared_by, approved_by=$approved_by WHERE id=$id";
            mysqli_query($conn, $sql);
        }
        header('Location: daily_sales_summary.php?branch_id=' . $branch_id . '&business_date=' . urlencode($business_date));
        exit;
    }
    // Delete Summary
    if (isset($_POST['delete_summary'])) {
        $id = intval($_POST['summary_id']);
        $sql = "UPDATE daily_sales_summary SET deleted_at=NOW() WHERE id=$id";
        mysqli_query($conn, $sql);
        header('Location: daily_sales_summary.php?branch_id=' . $selected_branch_id . '&business_date=' . urlencode($selected_date));
        exit;
    }
    // Add/Edit Sales Transaction
    if (isset($_POST['add_sale']) || isset($_POST['edit_sale'])) {
        $branch_id = intval($_POST['branch_id']);
        $dispenser_id = isset($_POST['dispenser_id']) ? intval($_POST['dispenser_id']) : 0;
        if ($dispenser_id === 0) {
            // Handle error: show message or redirect with error
        }
        $fuel_type_id = intval($_POST['fuel_type_id']);
        $transaction_date = mysqli_real_escape_string($conn, $_POST['transaction_date']);
        $transaction_time = mysqli_real_escape_string($conn, $_POST['transaction_time']);
        $quantity = floatval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $final_amount = floatval($_POST['final_amount']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $attendant_id = intval($_POST['attendant_id']);
        $transaction_number = uniqid('TXN-');
        if (isset($_POST['add_sale'])) {
            $sql = "INSERT INTO sales_transactions (branch_id, dispenser_id, fuel_type_id, transaction_date, transaction_time, quantity, unit_price, final_amount, payment_method, attendant_id, transaction_number) VALUES ($branch_id, $dispenser_id, $fuel_type_id, '$transaction_date', '$transaction_time', $quantity, $unit_price, $final_amount, '$payment_method', $attendant_id, '$transaction_number')";
            mysqli_query($conn, $sql);
        } elseif (isset($_POST['edit_sale'])) {
            $id = intval($_POST['sale_id']);
            $sql = "UPDATE sales_transactions SET dispenser_id=$dispenser_id, fuel_type_id=$fuel_type_id, transaction_time='$transaction_time', quantity=$quantity, unit_price=$unit_price, final_amount=$final_amount, payment_method='$payment_method', attendant_id=$attendant_id WHERE id=$id";
            mysqli_query($conn, $sql);
        }
        header('Location: daily_sales_summary.php?branch_id=' . $branch_id . '&business_date=' . urlencode($transaction_date));
        exit;
    }
    // Delete Sale
    if (isset($_POST['delete_sale'])) {
        $id = intval($_POST['sale_id']);
        $sql = "UPDATE sales_transactions SET deleted_at=NOW() WHERE id=$id";
        mysqli_query($conn, $sql);
        header('Location: daily_sales_summary.php?branch_id=' . $selected_branch_id . '&business_date=' . urlencode($selected_date));
        exit;
    }
}

// Fetch dropdowns for modals
$fuel_types = [];
$res = mysqli_query($conn, "SELECT id, name FROM fuel_types WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name");
if ($res) while ($row = mysqli_fetch_assoc($res)) $fuel_types[] = $row;
$dispensers = [];
$res = mysqli_query($conn, "SELECT id, dispenser_number FROM fuel_dispensers WHERE branch_id = $selected_branch_id AND deleted_at IS NULL ORDER BY dispenser_number");
if ($res) while ($row = mysqli_fetch_assoc($res)) $dispensers[] = $row;
$attendants = [];
$res = mysqli_query($conn, "SELECT id, first_name, last_name FROM users WHERE branch_id = $selected_branch_id AND deleted_at IS NULL ORDER BY first_name, last_name");
if ($res) while ($row = mysqli_fetch_assoc($res)) $attendants[] = $row;
$users = [];
$res = mysqli_query($conn, "SELECT id, first_name, last_name FROM users WHERE deleted_at IS NULL ORDER BY first_name, last_name");
if ($res) while ($row = mysqli_fetch_assoc($res)) $users[] = $row;
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
            <h2 class="mb-4 d-flex justify-content-between align-items-center">Daily Sales Summary
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#summaryModal" id="addSummaryBtn"><i class="bi bi-plus"></i> Add Summary</button>
            </h2>
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
                        <h5 class="card-title mb-3">Summary for <?php echo h($selected_date); ?>
                            <button class="btn btn-sm btn-primary ms-2 editSummaryBtn" data-id="<?php echo $summary['id']; ?>" data-bs-toggle="modal" data-bs-target="#summaryModal"
                                data-total_transactions="<?php echo h($summary['total_transactions']); ?>"
                                data-total_quantity="<?php echo h($summary['total_quantity']); ?>"
                                data-total_sales="<?php echo h($summary['total_sales']); ?>"
                                data-cash_sales="<?php echo h($summary['cash_sales']); ?>"
                                data-card_sales="<?php echo h($summary['card_sales']); ?>"
                                data-mobile_money_sales="<?php echo h($summary['mobile_money_sales']); ?>"
                                data-credit_sales="<?php echo h($summary['credit_sales']); ?>"
                                data-total_discounts="<?php echo h($summary['total_discounts']); ?>"
                                data-total_taxes="<?php echo h($summary['total_taxes']); ?>"
                                data-status="<?php echo h($summary['status']); ?>"
                                data-prepared_by="<?php echo h($summary['prepared_by']); ?>"
                                data-approved_by="<?php echo h($summary['approved_by']); ?>"
                            ><i class="bi bi-pencil"></i> Edit</button>
                            <button class="btn btn-sm btn-danger ms-1 deleteSummaryBtn" data-id="<?php echo $summary['id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteSummaryModal"><i class="bi bi-trash"></i> Delete</button>
                        </h5>
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
            <h5 class="mb-3 d-flex justify-content-between align-items-center">Sales Transactions
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#saleModal" id="addSaleBtn"><i class="bi bi-plus"></i> Add Sale</button>
            </h5>
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
                                <th class="text-end">Actions</th>
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
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary me-1 editSaleBtn"
                                            data-id="<?php echo $s['id']; ?>"
                                            data-dispenser_id="<?php echo h($s['dispenser_id']); ?>"
                                            data-fuel_type_id="<?php echo h($s['fuel_type_id']); ?>"
                                            data-transaction_time="<?php echo h($s['transaction_time']); ?>"
                                            data-quantity="<?php echo h($s['quantity']); ?>"
                                            data-unit_price="<?php echo h($s['unit_price']); ?>"
                                            data-final_amount="<?php echo h($s['final_amount']); ?>"
                                            data-payment_method="<?php echo h($s['payment_method']); ?>"
                                            data-attendant_id="<?php echo h($s['attendant_id']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#saleModal">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger deleteSaleBtn"
                                            data-id="<?php echo $s['id']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteSaleModal">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
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
<!-- Add/Edit Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="summaryModalLabel">Add/Edit Summary</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="summary_id" id="summary_id">
        <input type="hidden" name="branch_id" value="<?php echo $selected_branch_id; ?>">
        <input type="hidden" name="business_date" value="<?php echo h($selected_date); ?>">
        <div class="row g-2">
          <div class="col-md-6 mb-2"><label class="form-label">Total Transactions</label><input type="number" class="form-control" name="total_transactions" id="total_transactions" required></div>
          <div class="col-md-6 mb-2"><label class="form-label">Total Quantity</label><input type="number" step="0.01" class="form-control" name="total_quantity" id="total_quantity" required></div>
          <div class="col-md-6 mb-2"><label class="form-label">Total Sales</label><input type="number" step="0.01" class="form-control" name="total_sales" id="total_sales" required></div>
          <div class="col-md-6 mb-2"><label class="form-label">Cash Sales</label><input type="number" step="0.01" class="form-control" name="cash_sales" id="cash_sales"></div>
          <div class="col-md-6 mb-2"><label class="form-label">Card Sales</label><input type="number" step="0.01" class="form-control" name="card_sales" id="card_sales"></div>
          <div class="col-md-6 mb-2"><label class="form-label">Mobile Money Sales</label><input type="number" step="0.01" class="form-control" name="mobile_money_sales" id="mobile_money_sales"></div>
          <div class="col-md-6 mb-2"><label class="form-label">Credit Sales</label><input type="number" step="0.01" class="form-control" name="credit_sales" id="credit_sales"></div>
          <div class="col-md-6 mb-2"><label class="form-label">Discounts</label><input type="number" step="0.01" class="form-control" name="total_discounts" id="total_discounts"></div>
          <div class="col-md-6 mb-2"><label class="form-label">Taxes</label><input type="number" step="0.01" class="form-control" name="total_taxes" id="total_taxes"></div>
          <div class="col-md-6 mb-2"><label class="form-label">Status</label><input type="text" class="form-control" name="status" id="status"></div>
          <div class="col-md-6 mb-2"><label class="form-label">Prepared By</label><select class="form-select" name="prepared_by" id="prepared_by"><?php foreach ($users as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6 mb-2"><label class="form-label">Approved By</label><select class="form-select" name="approved_by" id="approved_by"><?php foreach ($users as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></option><?php endforeach; ?></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" name="add_summary" id="addSummarySubmit">Add Summary</button>
        <button type="submit" class="btn btn-primary d-none" name="edit_summary" id="editSummarySubmit">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Summary Modal -->
<div class="modal fade" id="deleteSummaryModal" tabindex="-1" aria-labelledby="deleteSummaryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteSummaryModalLabel">Delete Summary</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="summary_id" id="delete_summary_id">
        <p>Are you sure you want to delete this summary?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" name="delete_summary">Delete</button>
      </div>
    </form>
  </div>
</div>
<!-- Add/Edit Sale Modal -->
<div class="modal fade" id="saleModal" tabindex="-1" aria-labelledby="saleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="saleModalLabel">Add/Edit Sale</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="sale_id" id="sale_id">
        <input type="hidden" name="branch_id" value="<?php echo $selected_branch_id; ?>">
        <input type="hidden" name="transaction_date" value="<?php echo h($selected_date); ?>">
        <div class="row g-2">
          <div class="col-md-6 mb-2"><label class="form-label">Dispenser</label><select class="form-select" name="dispenser_id" id="dispenser_id" required><?php foreach ($dispensers as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo h($d['dispenser_number']); ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6 mb-2"><label class="form-label">Fuel Type</label><select class="form-select" name="fuel_type_id" id="fuel_type_id"><?php foreach ($fuel_types as $ft): ?><option value="<?php echo $ft['id']; ?>"><?php echo h($ft['name']); ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6 mb-2"><label class="form-label">Time</label><input type="time" class="form-control" name="transaction_time" id="transaction_time" required></div>
          <div class="col-md-6 mb-2"><label class="form-label">Quantity</label><input type="number" step="0.01" class="form-control" name="quantity" id="quantity" required></div>
          <div class="col-md-6 mb-2"><label class="form-label">Unit Price</label><input type="number" step="0.01" class="form-control" name="unit_price" id="unit_price" required></div>
          <div class="col-md-6 mb-2"><label class="form-label">Final Amount</label><input type="number" step="0.01" class="form-control" name="final_amount" id="final_amount" required></div>
          <div class="col-md-6 mb-2"><label class="form-label">Payment Method</label><input type="text" class="form-control" name="payment_method" id="payment_method" required></div>
          <div class="col-md-6 mb-2"><label class="form-label">Attendant</label><select class="form-select" name="attendant_id" id="attendant_id"><?php foreach ($attendants as $a): ?><option value="<?php echo $a['id']; ?>"><?php echo h($a['first_name'] . ' ' . $a['last_name']); ?></option><?php endforeach; ?></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" name="add_sale" id="addSaleSubmit">Add Sale</button>
        <button type="submit" class="btn btn-primary d-none" name="edit_sale" id="editSaleSubmit">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Sale Modal -->
<div class="modal fade" id="deleteSaleModal" tabindex="-1" aria-labelledby="deleteSaleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteSaleModalLabel">Delete Sale</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="sale_id" id="delete_sale_id">
        <p>Are you sure you want to delete this sale?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" name="delete_sale">Delete</button>
      </div>
    </form>
  </div>
</div>
<script>
// Summary Modal logic
const summaryModal = document.getElementById('summaryModal');
const addSummaryBtn = document.getElementById('addSummaryBtn');
const summaryModalLabel = document.getElementById('summaryModalLabel');
const addSummarySubmit = document.getElementById('addSummarySubmit');
const editSummarySubmit = document.getElementById('editSummarySubmit');
if (addSummaryBtn) {
  addSummaryBtn.addEventListener('click', function() {
    summaryModalLabel.textContent = 'Add Summary';
    addSummarySubmit.classList.remove('d-none');
    editSummarySubmit.classList.add('d-none');
    document.getElementById('summary_id').value = '';
    document.getElementById('total_transactions').value = '';
    document.getElementById('total_quantity').value = '';
    document.getElementById('total_sales').value = '';
    document.getElementById('cash_sales').value = '';
    document.getElementById('card_sales').value = '';
    document.getElementById('mobile_money_sales').value = '';
    document.getElementById('credit_sales').value = '';
    document.getElementById('total_discounts').value = '';
    document.getElementById('total_taxes').value = '';
    document.getElementById('status').value = '';
    document.getElementById('prepared_by').selectedIndex = 0;
    document.getElementById('approved_by').selectedIndex = 0;
  });
}
document.querySelectorAll('.editSummaryBtn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    summaryModalLabel.textContent = 'Edit Summary';
    addSummarySubmit.classList.add('d-none');
    editSummarySubmit.classList.remove('d-none');
    document.getElementById('summary_id').value = btn.getAttribute('data-id');
    document.getElementById('total_transactions').value = btn.getAttribute('data-total_transactions');
    document.getElementById('total_quantity').value = btn.getAttribute('data-total_quantity');
    document.getElementById('total_sales').value = btn.getAttribute('data-total_sales');
    document.getElementById('cash_sales').value = btn.getAttribute('data-cash_sales');
    document.getElementById('card_sales').value = btn.getAttribute('data-card_sales');
    document.getElementById('mobile_money_sales').value = btn.getAttribute('data-mobile_money_sales');
    document.getElementById('credit_sales').value = btn.getAttribute('data-credit_sales');
    document.getElementById('total_discounts').value = btn.getAttribute('data-total_discounts');
    document.getElementById('total_taxes').value = btn.getAttribute('data-total_taxes');
    document.getElementById('status').value = btn.getAttribute('data-status');
    document.getElementById('prepared_by').value = btn.getAttribute('data-prepared_by');
    document.getElementById('approved_by').value = btn.getAttribute('data-approved_by');
  });
});
// Delete Summary Modal logic
const deleteSummaryModal = document.getElementById('deleteSummaryModal');
document.querySelectorAll('.deleteSummaryBtn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById('delete_summary_id').value = btn.getAttribute('data-id');
  });
});
// Sale Modal logic
const saleModal = document.getElementById('saleModal');
const addSaleBtn = document.getElementById('addSaleBtn');
const saleModalLabel = document.getElementById('saleModalLabel');
const addSaleSubmit = document.getElementById('addSaleSubmit');
const editSaleSubmit = document.getElementById('editSaleSubmit');
if (addSaleBtn) {
  addSaleBtn.addEventListener('click', function() {
    saleModalLabel.textContent = 'Add Sale';
    addSaleSubmit.classList.remove('d-none');
    editSaleSubmit.classList.add('d-none');
    document.getElementById('sale_id').value = '';
    document.getElementById('dispenser_id').selectedIndex = 0;
    document.getElementById('fuel_type_id').selectedIndex = 0;
    document.getElementById('transaction_time').value = '';
    document.getElementById('quantity').value = '';
    document.getElementById('unit_price').value = '';
    document.getElementById('final_amount').value = '';
    document.getElementById('payment_method').value = '';
    document.getElementById('attendant_id').selectedIndex = 0;
  });
}
document.querySelectorAll('.editSaleBtn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    saleModalLabel.textContent = 'Edit Sale';
    addSaleSubmit.classList.add('d-none');
    editSaleSubmit.classList.remove('d-none');
    document.getElementById('sale_id').value = btn.getAttribute('data-id');
    document.getElementById('dispenser_id').value = btn.getAttribute('data-dispenser_id');
    document.getElementById('fuel_type_id').value = btn.getAttribute('data-fuel_type_id');
    document.getElementById('transaction_time').value = btn.getAttribute('data-transaction_time');
    document.getElementById('quantity').value = btn.getAttribute('data-quantity');
    document.getElementById('unit_price').value = btn.getAttribute('data-unit_price');
    document.getElementById('final_amount').value = btn.getAttribute('data-final_amount');
    document.getElementById('payment_method').value = btn.getAttribute('data-payment_method');
    document.getElementById('attendant_id').value = btn.getAttribute('data-attendant_id');
  });
});
// Delete Sale Modal logic
const deleteSaleModal = document.getElementById('deleteSaleModal');
document.querySelectorAll('.deleteSaleBtn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById('delete_sale_id').value = btn.getAttribute('data-id');
  });
});
// Auto-calculate Final Amount in Sale Modal
function updateFinalAmount() {
  var qty = parseFloat(document.getElementById('quantity').value) || 0;
  var price = parseFloat(document.getElementById('unit_price').value) || 0;
  document.getElementById('final_amount').value = (qty * price).toFixed(2);
}
if (document.getElementById('quantity')) {
  document.getElementById('quantity').addEventListener('input', updateFinalAmount);
}
if (document.getElementById('unit_price')) {
  document.getElementById('unit_price').addEventListener('input', updateFinalAmount);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 