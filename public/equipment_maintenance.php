<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';

if (!has_permission('maintenance.view')) {
    header('Location: login.php?error=unauthorized');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_maintenance'])) {
        $branch_id = intval($_POST['branch_id']);
        $equipment_name = mysqli_real_escape_string($conn, $_POST['equipment_name']);
        $equipment_type = mysqli_real_escape_string($conn, $_POST['equipment_type']);
        $maintenance_type = mysqli_real_escape_string($conn, $_POST['maintenance_type']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $scheduled_date = mysqli_real_escape_string($conn, $_POST['scheduled_date']);
        $estimated_cost = floatval($_POST['estimated_cost']);
        $assigned_to = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $priority = mysqli_real_escape_string($conn, $_POST['priority']);
        $status = 'scheduled';

        $sql = "INSERT INTO equipment_maintenance (branch_id, equipment_name, equipment_type, maintenance_type, description, scheduled_date, estimated_cost, assigned_to, priority, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isssssdis', $branch_id, $equipment_name, $equipment_type, $maintenance_type, $description, $scheduled_date, $estimated_cost, $assigned_to, $priority, $status);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Maintenance record added successfully.'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'message' => 'Error adding maintenance record: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: equipment_maintenance.php');
        exit;
    }

    if (isset($_POST['update_status'])) {
        $maintenance_id = intval($_POST['maintenance_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $completion_date = null;
        $actual_cost = null;
        
        if ($status === 'completed') {
            $completion_date = date('Y-m-d');
            $actual_cost = isset($_POST['actual_cost']) ? floatval($_POST['actual_cost']) : null;
        }

        $sql = "UPDATE equipment_maintenance SET status = ?, completion_date = ?, actual_cost = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssdi', $status, $completion_date, $actual_cost, $maintenance_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Maintenance status updated successfully.'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'message' => 'Error updating status: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: equipment_maintenance.php');
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
$priority_filter = $_GET['priority'] ?? '';

// Build query for maintenance records
$where_conditions = ["em.deleted_at IS NULL"];
$params = [];
$param_types = '';

if ($selected_branch_id) {
    $where_conditions[] = "em.branch_id = ?";
    $params[] = $selected_branch_id;
    $param_types .= 'i';
}

if ($status_filter) {
    $where_conditions[] = "em.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($priority_filter) {
    $where_conditions[] = "em.priority = ?";
    $params[] = $priority_filter;
    $param_types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT em.*, b.branch_name, u.first_name, u.last_name, u.employee_id
        FROM equipment_maintenance em
        LEFT JOIN branches b ON em.branch_id = b.id
        LEFT JOIN users u ON em.assigned_to = u.id
        WHERE $where_clause
        ORDER BY em.scheduled_date DESC, em.priority DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt && !empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$maintenance_records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $maintenance_records[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Maintenance - Fuel Station Management</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/datatables.min.css" rel="stylesheet">
    <style>
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-medium { color: #fd7e14; font-weight: bold; }
        .priority-low { color: #198754; }
        .status-scheduled { background-color: #fff3cd; }
        .status-in-progress { background-color: #cce7ff; }
        .status-completed { background-color: #d1e7dd; }
        .status-overdue { background-color: #f8d7da; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Equipment Maintenance</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Equipment Maintenance</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                            <i class="bi bi-plus"></i> Add Maintenance
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
                                        <option value="scheduled" <?php echo ($status_filter == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                        <option value="in_progress" <?php echo ($status_filter == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="overdue" <?php echo ($status_filter == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="">All Priorities</option>
                                        <option value="high" <?php echo ($priority_filter == 'high') ? 'selected' : ''; ?>>High</option>
                                        <option value="medium" <?php echo ($priority_filter == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                        <option value="low" <?php echo ($priority_filter == 'low') ? 'selected' : ''; ?>>Low</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="equipment_maintenance.php" class="btn btn-secondary">Clear</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Records -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Maintenance Records</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="maintenanceTable">
                                    <thead>
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Type</th>
                                            <th>Maintenance Type</th>
                                            <th>Branch</th>
                                            <th>Scheduled Date</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Assigned To</th>
                                            <th>Estimated Cost</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenance_records as $record): ?>
                                            <tr class="status-<?php echo $record['status']; ?>">
                                                <td><?php echo htmlspecialchars($record['equipment_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['equipment_type']); ?></td>
                                                <td><?php echo htmlspecialchars($record['maintenance_type']); ?></td>
                                                <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($record['scheduled_date'])); ?></td>
                                                <td>
                                                    <span class="priority-<?php echo $record['priority']; ?>">
                                                        <?php echo ucfirst($record['priority']); ?>
                                                    </span>
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
                                                <td><?php echo number_format($record['estimated_cost'], 2); ?></td>
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

    <!-- Add Maintenance Modal -->
    <div class="modal fade" id="addMaintenanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Maintenance Record</h5>
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
                                <label for="equipment_name" class="form-label">Equipment Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="equipment_name" name="equipment_name" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="equipment_type" class="form-label">Equipment Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="equipment_type" name="equipment_type" required>
                                    <option value="">Select Type</option>
                                    <option value="dispenser">Fuel Dispenser</option>
                                    <option value="tank">Storage Tank</option>
                                    <option value="pump">Fuel Pump</option>
                                    <option value="generator">Generator</option>
                                    <option value="compressor">Air Compressor</option>
                                    <option value="lighting">Lighting System</option>
                                    <option value="security">Security System</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                    <option value="">Select Type</option>
                                    <option value="preventive">Preventive</option>
                                    <option value="corrective">Corrective</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="inspection">Inspection</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="assigned_to" class="form-label">Assign To</label>
                                <select class="form-select" id="assigned_to" name="assigned_to">
                                    <option value="">Select Employee</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['employee_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="estimated_cost" class="form-label">Estimated Cost</label>
                                <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_maintenance" class="btn btn-primary">Add Maintenance</button>
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
                    <h5 class="modal-title">Update Maintenance Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="maintenance_id" name="maintenance_id">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                        <div class="mb-3" id="completionFields" style="display: none;">
                            <label for="actual_cost" class="form-label">Actual Cost</label>
                            <input type="number" class="form-control" id="actual_cost" name="actual_cost" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
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
                    <h5 class="modal-title">Maintenance Details</h5>
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
            $('#maintenanceTable').DataTable({
                order: [[4, 'desc']], // Sort by scheduled date
                pageLength: 25
            });

            // Handle status change in update modal
            $('#status').on('change', function() {
                if ($(this).val() === 'completed') {
                    $('#completionFields').show();
                } else {
                    $('#completionFields').hide();
                }
            });

            // Handle update status modal
            $('#updateStatusModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var status = button.data('status');
                var modal = $(this);
                modal.find('#maintenance_id').val(id);
                modal.find('#status').val(status);
            });

            // Handle view details modal
            $('#viewDetailsModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var record = button.data('record');
                var modal = $(this);
                var content = modal.find('#detailsContent');
                
                content.html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Equipment Information</h6>
                            <p><strong>Name:</strong> ${record.equipment_name}</p>
                            <p><strong>Type:</strong> ${record.equipment_type}</p>
                            <p><strong>Maintenance Type:</strong> ${record.maintenance_type}</p>
                            <p><strong>Branch:</strong> ${record.branch_name}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Maintenance Details</h6>
                            <p><strong>Scheduled Date:</strong> ${new Date(record.scheduled_date).toLocaleDateString()}</p>
                            <p><strong>Priority:</strong> <span class="priority-${record.priority}">${record.priority.charAt(0).toUpperCase() + record.priority.slice(1)}</span></p>
                            <p><strong>Status:</strong> <span class="badge bg-${record.status === 'completed' ? 'success' : record.status === 'in_progress' ? 'primary' : record.status === 'overdue' ? 'danger' : 'warning'}">${record.status.replace('_', ' ').charAt(0).toUpperCase() + record.status.replace('_', ' ').slice(1)}</span></p>
                            <p><strong>Estimated Cost:</strong> ${parseFloat(record.estimated_cost).toLocaleString('en-US', {style: 'currency', currency: 'UGX'})}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Description</h6>
                            <p>${record.description || 'No description provided.'}</p>
                        </div>
                    </div>
                `);
            });
        });
    </script>
</body>
</html>
