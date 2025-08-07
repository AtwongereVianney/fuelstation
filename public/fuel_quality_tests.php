<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';

if (!has_permission('quality_tests.view')) {
    header('Location: login.php?error=unauthorized');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_test'])) {
        $branch_id = intval($_POST['branch_id']);
        $test_date = mysqli_real_escape_string($conn, $_POST['test_date']);
        $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type']);
        $test_type = mysqli_real_escape_string($conn, $_POST['test_type']);
        $result = mysqli_real_escape_string($conn, $_POST['result']);
        $tested_by = isset($_POST['tested_by']) ? intval($_POST['tested_by']) : null;
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
        $status = 'pending';

        $sql = "INSERT INTO fuel_quality_tests (branch_id, test_date, fuel_type, test_type, result, tested_by, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'issssiss', $branch_id, $test_date, $fuel_type, $test_type, $result, $tested_by, $remarks, $status);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Fuel quality test added successfully.'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'message' => 'Error adding test: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: fuel_quality_tests.php');
        exit;
    }

    if (isset($_POST['update_test'])) {
        $test_id = intval($_POST['test_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

        $sql = "UPDATE fuel_quality_tests SET status = ?, remarks = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssi', $status, $remarks, $test_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Test status updated successfully.'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'message' => 'Error updating test: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: fuel_quality_tests.php');
        exit;
    }
}

// Get user's branch
$user_branch_id = $_SESSION['branch_id'] ?? null;
$user_business_id = $_SESSION['business_id'] ?? null;

// Fetch branches for dropdown
$branches = [];
if ($user_branch_id) {
    $branch_sql = "SELECT id, branch_name FROM branches WHERE id = ? AND deleted_at IS NULL";
    $stmt = mysqli_prepare($conn, $branch_sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_branch_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $branches[] = $row;
    }
} else {
    $branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
    $result = mysqli_query($conn, $branch_sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $branches[] = $row;
    }
}

// Fetch users for assignment dropdown
$users = [];
$user_sql = "SELECT id, first_name, last_name, employee_id FROM users WHERE deleted_at IS NULL ORDER BY first_name, last_name";
$user_result = mysqli_query($conn, $user_sql);
while ($row = mysqli_fetch_assoc($user_result)) {
    $users[] = $row;
}

// Get selected branch and filters
$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : ($user_branch_id ?? ($branches[0]['id'] ?? null));
$status_filter = $_GET['status'] ?? '';
$fuel_type_filter = $_GET['fuel_type'] ?? '';
$test_type_filter = $_GET['test_type'] ?? '';

// Build query for test records
$where_conditions = ["qt.deleted_at IS NULL"];
$params = [];
$param_types = '';

if ($selected_branch_id) {
    $where_conditions[] = "qt.branch_id = ?";
    $params[] = $selected_branch_id;
    $param_types .= 'i';
}
if ($status_filter) {
    $where_conditions[] = "qt.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}
if ($fuel_type_filter) {
    $where_conditions[] = "qt.fuel_type = ?";
    $params[] = $fuel_type_filter;
    $param_types .= 's';
}
if ($test_type_filter) {
    $where_conditions[] = "qt.test_type = ?";
    $params[] = $test_type_filter;
    $param_types .= 's';
}
$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT qt.*, b.branch_name, u.first_name, u.last_name, u.employee_id FROM fuel_quality_tests qt LEFT JOIN branches b ON qt.branch_id = b.id LEFT JOIN users u ON qt.tested_by = u.id WHERE $where_clause ORDER BY qt.test_date DESC, qt.id DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt && !empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$test_records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $test_records[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Quality Tests - Fuel Station Management</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/datatables.min.css" rel="stylesheet">
    <style>
        .status-pending { background-color: #fff3cd; }
        .status-in_progress { background-color: #cce7ff; }
        .status-passed { background-color: #d1e7dd; }
        .status-failed { background-color: #f8d7da; }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Fuel Quality Tests</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Fuel Quality Tests</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTestModal">
                        <i class="bi bi-plus"></i> Add Test
                    </button>
                </div>
            </div>
        </div>
        <?php if (isset($_SESSION['feedback'])): ?>
            <div class="alert alert-<?php echo $_SESSION['feedback']['type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['feedback']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['feedback']); ?>
        <?php endif; ?>
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select class="form-select" id="branch_id" name="branch_id">
                                    <option value="">All Branches</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>" <?php echo ($selected_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo ($status_filter == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="passed" <?php echo ($status_filter == 'passed') ? 'selected' : ''; ?>>Passed</option>
                                    <option value="failed" <?php echo ($status_filter == 'failed') ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="fuel_type" class="form-label">Fuel Type</label>
                                <select class="form-select" id="fuel_type" name="fuel_type">
                                    <option value="">All Types</option>
                                    <option value="petrol" <?php echo ($fuel_type_filter == 'petrol') ? 'selected' : ''; ?>>Petrol</option>
                                    <option value="diesel" <?php echo ($fuel_type_filter == 'diesel') ? 'selected' : ''; ?>>Diesel</option>
                                    <option value="kerosene" <?php echo ($fuel_type_filter == 'kerosene') ? 'selected' : ''; ?>>Kerosene</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="test_type" class="form-label">Test Type</label>
                                <select class="form-select" id="test_type" name="test_type">
                                    <option value="">All Types</option>
                                    <option value="density" <?php echo ($test_type_filter == 'density') ? 'selected' : ''; ?>>Density</option>
                                    <option value="water" <?php echo ($test_type_filter == 'water') ? 'selected' : ''; ?>>Water</option>
                                    <option value="color" <?php echo ($test_type_filter == 'color') ? 'selected' : ''; ?>>Color</option>
                                    <option value="sulphur" <?php echo ($test_type_filter == 'sulphur') ? 'selected' : ''; ?>>Sulphur</option>
                                    <option value="other" <?php echo ($test_type_filter == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="fuel_quality_tests.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Test Records -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Fuel Quality Tests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="testTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Fuel Type</th>
                                        <th>Test Type</th>
                                        <th>Result</th>
                                        <th>Status</th>
                                        <th>Branch</th>
                                        <th>Tested By</th>
                                        <th>Remarks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($test_records as $record): ?>
                                        <tr class="status-<?php echo $record['status']; ?>">
                                            <td><?php echo date('M d, Y', strtotime($record['test_date'])); ?></td>
                                            <td><?php echo ucfirst($record['fuel_type']); ?></td>
                                            <td><?php echo ucfirst($record['test_type']); ?></td>
                                            <td><?php echo htmlspecialchars($record['result']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $record['status'] === 'passed' ? 'success' : 
                                                        ($record['status'] === 'failed' ? 'danger' : 
                                                        ($record['status'] === 'in_progress' ? 'primary' : 'warning')); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                                            <td>
                                                <?php if ($record['first_name']): ?>
                                                    <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                    <br><small class="text-muted"><?php echo $record['employee_id']; ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['remarks']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#updateTestModal" 
                                                            data-id="<?php echo $record['id']; ?>"
                                                            data-status="<?php echo $record['status']; ?>"
                                                            data-remarks="<?php echo htmlspecialchars($record['remarks'], ENT_QUOTES); ?>">
                                                        Update
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#viewDetailsModal"
                                                            data-record='<?php echo json_encode($record); ?>'>
                                                        View Details
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Add Test Modal -->
<div class="modal fade" id="addTestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Fuel Quality Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select class="form-select" id="branch_id" name="branch_id" required>
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="test_date" class="form-label">Test Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="test_date" name="test_date" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="fuel_type" class="form-label">Fuel Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="fuel_type" name="fuel_type" required>
                                <option value="">Select Type</option>
                                <option value="petrol">Petrol</option>
                                <option value="diesel">Diesel</option>
                                <option value="kerosene">Kerosene</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="test_type" class="form-label">Test Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="test_type" name="test_type" required>
                                <option value="">Select Type</option>
                                <option value="density">Density</option>
                                <option value="water">Water</option>
                                <option value="color">Color</option>
                                <option value="sulphur">Sulphur</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="result" class="form-label">Result <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="result" name="result" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tested_by" class="form-label">Tested By</label>
                            <select class="form-select" id="tested_by" name="tested_by">
                                <option value="">Select Employee</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['employee_id'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_test" class="btn btn-primary">Add Test</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Update Test Modal -->
<div class="modal fade" id="updateTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Test Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" id="test_id" name="test_id">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="update_remarks" name="remarks" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_test" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/datatables.min.js"></script>
<script>
$(document).ready(function() {
    $('#testTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
    });
    $('#updateTestModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var status = button.data('status');
        var remarks = button.data('remarks');
        var modal = $(this);
        modal.find('#test_id').val(id);
        modal.find('#status').val(status);
        modal.find('#update_remarks').val(remarks);
    });
    $('#viewDetailsModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var record = button.data('record');
        var modal = $(this);
        var content = modal.find('#detailsContent');
        content.html(`
            <div class="row">
                <div class="col-md-6">
                    <h6>Test Information</h6>
                    <p><strong>Date:</strong> ${new Date(record.test_date).toLocaleDateString()}</p>
                    <p><strong>Fuel Type:</strong> ${record.fuel_type.charAt(0).toUpperCase() + record.fuel_type.slice(1)}</p>
                    <p><strong>Test Type:</strong> ${record.test_type.charAt(0).toUpperCase() + record.test_type.slice(1)}</p>
                    <p><strong>Result:</strong> ${record.result}</p>
                </div>
                <div class="col-md-6">
                    <h6>Status & Assignment</h6>
                    <p><strong>Status:</strong> <span class="badge bg-${record.status === 'passed' ? 'success' : record.status === 'failed' ? 'danger' : record.status === 'in_progress' ? 'primary' : 'warning'}">${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</span></p>
                    <p><strong>Branch:</strong> ${record.branch_name}</p>
                    <p><strong>Tested By:</strong> ${record.first_name ? record.first_name + ' ' + record.last_name + ' (' + record.employee_id + ')' : 'Not assigned'}</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Remarks</h6>
                    <p>${record.remarks || 'No remarks provided.'}</p>
                </div>
            </div>
        `);
    });
});
</script>
</body>
</html>
