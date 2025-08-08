<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';

if (!has_permission('compliance.view')) {
    header('Location: login.php?error=unauthorized');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_compliance'])) {
        $branch_id = intval($_POST['branch_id']);
        $compliance_type = mysqli_real_escape_string($conn, $_POST['compliance_type']);
        $requirement_description = mysqli_real_escape_string($conn, $_POST['requirement_description']);
        $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
        $responsible_person = isset($_POST['responsible_person']) ? intval($_POST['responsible_person']) : null;
        $regulatory_body = mysqli_real_escape_string($conn, $_POST['regulatory_body']);
        $certificate_number = mysqli_real_escape_string($conn, $_POST['certificate_number']);
        $renewal_date = !empty($_POST['renewal_date']) ? mysqli_real_escape_string($conn, $_POST['renewal_date']) : null;
        $cost = floatval($_POST['cost']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        $status = 'pending';

        $sql = "INSERT INTO regulatory_compliance (branch_id, compliance_type, requirement_description, due_date, responsible_person, regulatory_body, certificate_number, renewal_date, cost, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isssisssds', $branch_id, $compliance_type, $requirement_description, $due_date, $responsible_person, $regulatory_body, $certificate_number, $renewal_date, $cost, $notes, $status);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Compliance requirement added successfully.'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'message' => 'Error adding compliance requirement: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: regulatory_compliance.php');
        exit;
    }

    if (isset($_POST['update_compliance'])) {
        $compliance_id = intval($_POST['compliance_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $completion_date = null;
        
        if ($status === 'completed') {
            $completion_date = date('Y-m-d');
        }

        $sql = "UPDATE regulatory_compliance SET status = ?, completion_date = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssi', $status, $completion_date, $compliance_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Compliance status updated successfully.'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'message' => 'Error updating status: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: regulatory_compliance.php');
        exit;
    }
}

// Get user's branch
$user_branch_id = $_SESSION['branch_id'] ?? null;
$user_business_id = $_SESSION['business_id'] ?? null;

// Fetch branches for dropdown
$branches = [];
if ($user_branch_id) {
    // User is assigned to specific branch
    $branch_sql = "SELECT id, branch_name FROM branches WHERE id = ? AND deleted_at IS NULL";
    $stmt = mysqli_prepare($conn, $branch_sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_branch_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $branches[] = $row;
    }
} else {
    // User can see all branches
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
$compliance_type_filter = $_GET['compliance_type'] ?? '';

// Build query for compliance records
$where_conditions = ["rc.deleted_at IS NULL"];
$params = [];
$param_types = '';

if ($selected_branch_id) {
    $where_conditions[] = "rc.branch_id = ?";
    $params[] = $selected_branch_id;
    $param_types .= 'i';
}

if ($status_filter) {
    $where_conditions[] = "rc.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($compliance_type_filter) {
    $where_conditions[] = "rc.compliance_type = ?";
    $params[] = $compliance_type_filter;
    $param_types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT rc.*, b.branch_name, u.first_name, u.last_name, u.employee_id
        FROM regulatory_compliance rc
        LEFT JOIN branches b ON rc.branch_id = b.id
        LEFT JOIN users u ON rc.responsible_person = u.id
        WHERE $where_clause
        ORDER BY rc.due_date ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt && !empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$compliance_records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $compliance_records[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regulatory Compliance - Fuel Station Management</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/datatables.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { min-height: 100vh; margin: 0; padding: 0; }
        .main-flex-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar-fixed { width: 240px; min-width: 200px; max-width: 300px; height: 100vh; position: sticky; top: 0; left: 0; z-index: 1020; background: #f8f9fa; border-right: 1px solid #dee2e6; }
        .main-content-scroll { flex: 1 1 0%; height: 100vh; overflow-y: auto; padding: 32px 24px 24px 24px; background: #fff; }
        @media (max-width: 767.98px) { .main-flex-container { display: block; height: auto; } .sidebar-fixed { display: none; } .main-content-scroll { height: auto; padding: 16px 8px; } }
        .status-pending { background-color: #fff3cd; }
        .status-in_progress { background-color: #cce7ff; }
        .status-completed { background-color: #d1e7dd; }
        .status-overdue { background-color: #f8d7da; }
        .overdue { color: #dc3545; font-weight: bold; }
        .due-soon { color: #fd7e14; font-weight: bold; }
    </style>
</head>
<body>
    <!-- Mobile Sidebar -->
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
        <!-- Desktop Sidebar -->
        <div class="sidebar-fixed d-none d-md-block p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content-scroll">
            <!-- Include header -->
            <?php include '../includes/header.php'; ?>
            
            <!-- Mobile menu button -->
            <div class="d-md-none mb-3">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="bi bi-list"></i> Menu
                </button>
            </div>

            <div class="page-wrapper">
                <div class="content container-fluid">
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="page-title">Regulatory Compliance</h3>
                                <ul class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Regulatory Compliance</li>
                                </ul>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addComplianceModal">
                                    <i class="bi bi-plus"></i> Add Compliance Requirement
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
                                                <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                <option value="overdue" <?php echo ($status_filter == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="compliance_type" class="form-label">Compliance Type</label>
                                            <select class="form-select" id="compliance_type" name="compliance_type">
                                                <option value="">All Types</option>
                                                <option value="license" <?php echo ($compliance_type_filter == 'license') ? 'selected' : ''; ?>>License</option>
                                                <option value="permit" <?php echo ($compliance_type_filter == 'permit') ? 'selected' : ''; ?>>Permit</option>
                                                <option value="certification" <?php echo ($compliance_type_filter == 'certification') ? 'selected' : ''; ?>>Certification</option>
                                                <option value="inspection" <?php echo ($compliance_type_filter == 'inspection') ? 'selected' : ''; ?>>Inspection</option>
                                                <option value="audit" <?php echo ($compliance_type_filter == 'audit') ? 'selected' : ''; ?>>Audit</option>
                                                <option value="training" <?php echo ($compliance_type_filter == 'training') ? 'selected' : ''; ?>>Training</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                                <a href="regulatory_compliance.php" class="btn btn-secondary">Clear</a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

            <!-- Compliance Records -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Compliance Requirements</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="complianceTable">
                                    <thead>
                                        <tr>
                                            <th>Requirement</th>
                                            <th>Type</th>
                                            <th>Branch</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Responsible Person</th>
                                            <th>Regulatory Body</th>
                                            <th>Cost</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($compliance_records as $record): ?>
                                            <?php 
                                            $due_date = new DateTime($record['due_date']);
                                            $today = new DateTime();
                                            $days_until_due = $today->diff($due_date)->days;
                                            $is_overdue = $due_date < $today && $record['status'] !== 'completed';
                                            $is_due_soon = $days_until_due <= 30 && $days_until_due > 0 && $record['status'] !== 'completed';
                                            ?>
                                            <tr class="status-<?php echo $record['status']; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($record['requirement_description']); ?></strong>
                                                    <?php if ($record['certificate_number']): ?>
                                                        <br><small class="text-muted">Cert: <?php echo htmlspecialchars($record['certificate_number']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo ucfirst(htmlspecialchars($record['compliance_type'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                                                <td>
                                                    <span class="<?php echo $is_overdue ? 'overdue' : ($is_due_soon ? 'due-soon' : ''); ?>">
                                                        <?php echo date('M d, Y', strtotime($record['due_date'])); ?>
                                                    </span>
                                                    <?php if ($is_overdue): ?>
                                                        <br><small class="text-danger">Overdue by <?php echo $days_until_due; ?> days</small>
                                                    <?php elseif ($is_due_soon): ?>
                                                        <br><small class="text-warning">Due in <?php echo $days_until_due; ?> days</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $record['status'] === 'completed' ? 'success' : 
                                                            ($record['status'] === 'in_progress' ? 'primary' : 
                                                            ($record['status'] === 'overdue' ? 'danger' : 'warning')); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($record['first_name']): ?>
                                                        <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                        <br><small class="text-muted"><?php echo $record['employee_id']; ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['regulatory_body']); ?></td>
                                                <td><?php echo number_format($record['cost'], 2); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" data-bs-target="#updateStatusModal" 
                                                                data-id="<?php echo $record['id']; ?>"
                                                                data-status="<?php echo $record['status']; ?>">
                                                            Update Status
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

    <!-- Add Compliance Modal -->
    <div class="modal fade" id="addComplianceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Compliance Requirement</h5>
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
                                <label for="compliance_type" class="form-label">Compliance Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="compliance_type" name="compliance_type" required>
                                    <option value="">Select Type</option>
                                    <option value="license">License</option>
                                    <option value="permit">Permit</option>
                                    <option value="certification">Certification</option>
                                    <option value="inspection">Inspection</option>
                                    <option value="audit">Audit</option>
                                    <option value="training">Training</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="requirement_description" class="form-label">Requirement Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="requirement_description" name="requirement_description" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="due_date" name="due_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="responsible_person" class="form-label">Responsible Person</label>
                                <select class="form-select" id="responsible_person" name="responsible_person">
                                    <option value="">Select Employee</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['employee_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="regulatory_body" class="form-label">Regulatory Body</label>
                                <input type="text" class="form-control" id="regulatory_body" name="regulatory_body">
                            </div>
                            <div class="col-md-6">
                                <label for="certificate_number" class="form-label">Certificate Number</label>
                                <input type="text" class="form-control" id="certificate_number" name="certificate_number">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="renewal_date" class="form-label">Renewal Date</label>
                                <input type="date" class="form-control" id="renewal_date" name="renewal_date">
                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Estimated Cost</label>
                                <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_compliance" class="btn btn-primary">Add Requirement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Compliance Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="compliance_id" name="compliance_id">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_compliance" class="btn btn-primary">Update Status</button>
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
                    <h5 class="modal-title">Compliance Details</h5>
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
            // Initialize DataTable
            $('#complianceTable').DataTable({
                order: [[3, 'asc']], // Sort by due date
                pageLength: 25
            });

            // Handle update status modal
            $('#updateStatusModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var status = button.data('status');
                var modal = $(this);
                modal.find('#compliance_id').val(id);
                modal.find('#status').val(status);
            });

            // Handle view details modal
            $('#viewDetailsModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var record = button.data('record');
                var modal = $(this);
                var content = modal.find('#detailsContent');
                
                var dueDate = new Date(record.due_date);
                var today = new Date();
                var daysUntilDue = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
                var isOverdue = dueDate < today && record.status !== 'completed';
                var isDueSoon = daysUntilDue <= 30 && daysUntilDue > 0 && record.status !== 'completed';
                
                content.html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Requirement Information</h6>
                            <p><strong>Description:</strong> ${record.requirement_description}</p>
                            <p><strong>Type:</strong> ${record.compliance_type.charAt(0).toUpperCase() + record.compliance_type.slice(1)}</p>
                            <p><strong>Branch:</strong> ${record.branch_name}</p>
                            <p><strong>Regulatory Body:</strong> ${record.regulatory_body || 'Not specified'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Timeline & Status</h6>
                            <p><strong>Due Date:</strong> <span class="${isOverdue ? 'overdue' : isDueSoon ? 'due-soon' : ''}">${new Date(record.due_date).toLocaleDateString()}</span></p>
                            <p><strong>Status:</strong> <span class="badge bg-${record.status === 'completed' ? 'success' : record.status === 'in_progress' ? 'primary' : record.status === 'overdue' ? 'danger' : 'warning'}">${record.status.replace('_', ' ').charAt(0).toUpperCase() + record.status.replace('_', ' ').slice(1)}</span></p>
                            <p><strong>Estimated Cost:</strong> ${parseFloat(record.cost).toLocaleString('en-US', {style: 'currency', currency: 'UGX'})}</p>
                            <p><strong>Responsible Person:</strong> ${record.first_name ? record.first_name + ' ' + record.last_name + ' (' + record.employee_id + ')' : 'Not assigned'}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Additional Information</h6>
                            <p><strong>Certificate Number:</strong> ${record.certificate_number || 'Not specified'}</p>
                            <p><strong>Renewal Date:</strong> ${record.renewal_date ? new Date(record.renewal_date).toLocaleDateString() : 'Not specified'}</p>
                            <p><strong>Notes:</strong> ${record.notes || 'No notes provided.'}</p>
                        </div>
                    </div>
                `);
            });
        });
    </script>
</body>
</html>
