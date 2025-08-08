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
        $equipment_type = mysqli_real_escape_string($conn, $_POST['equipment_type']);
        $maintenance_type = mysqli_real_escape_string($conn, $_POST['maintenance_type']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $maintenance_date = mysqli_real_escape_string($conn, $_POST['maintenance_date']);
        $cost = floatval($_POST['estimated_cost']);
        $performed_by = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $service_provider = mysqli_real_escape_string($conn, $_POST['service_provider'] ?? '');
        $technician_name = mysqli_real_escape_string($conn, $_POST['technician_name'] ?? '');
        $status = 'scheduled';

        $sql = "INSERT INTO equipment_maintenance (branch_id, equipment_type, maintenance_type, description, maintenance_date, cost, performed_by, service_provider, technician_name, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'issssdiss', $branch_id, $equipment_type, $maintenance_type, $description, $maintenance_date, $cost, $performed_by, $service_provider, $technician_name, $status);
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
        $actual_cost = null;
        
        if ($status === 'completed') {
            $actual_cost = isset($_POST['actual_cost']) ? floatval($_POST['actual_cost']) : null;
        }

        $sql = "UPDATE equipment_maintenance SET status = ?, cost = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sdi', $status, $actual_cost, $maintenance_id);
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

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT em.*, b.branch_name, u.first_name, u.last_name, u.employee_id
        FROM equipment_maintenance em
        LEFT JOIN branches b ON em.branch_id = b.id
        LEFT JOIN users u ON em.performed_by = u.id
        WHERE $where_clause
        ORDER BY em.maintenance_date DESC, em.id DESC";

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
        html, body { height: 100%; }
        body { min-height: 100vh; margin: 0; padding: 0; }
        .main-flex-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar-fixed { width: 240px; min-width: 200px; max-width: 300px; height: 100vh; position: sticky; top: 0; left: 0; z-index: 1020; background: #f8f9fa; border-right: 1px solid #dee2e6; }
        .main-content-scroll { flex: 1 1 0%; height: 100vh; overflow-y: auto; padding: 32px 24px 24px 24px; background: #fff; }
        @media (max-width: 767.98px) { .main-flex-container { display: block; height: auto; } .sidebar-fixed { display: none; } .main-content-scroll { height: auto; padding: 16px 8px; } }
        .status-scheduled { background-color: #fff3cd; }
        .status-in_progress { background-color: #cce7ff; }
        .status-completed { background-color: #d1e7dd; }
        .status-cancelled { background-color: #f8d7da; }
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
                                <div class="col-md-4">
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
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="scheduled" <?php echo ($status_filter == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                        <option value="in_progress" <?php echo ($status_filter == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="equipment_maintenance.php" class="btn btn-secondary">Clear</a>
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
                            <h5 class="card-title">Equipment Maintenance Records</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="maintenanceTable">
                                    <thead>
                                        <tr>
                                            <th>Equipment Type</th>
                                            <th>Maintenance Type</th>
                                            <th>Branch</th>
                                            <th>Maintenance Date</th>
                                            <th>Status</th>
                                            <th>Performed By</th>
                                            <th>Cost</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenance_records as $record): ?>
                                            <tr class="status-<?php echo $record['status']; ?>">
                                                <td><?php echo ucfirst(str_replace('_', ' ', $record['equipment_type'])); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $record['maintenance_type'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($record['maintenance_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $record['status'] === 'completed' ? 'success' : 
                                                            ($record['status'] === 'in_progress' ? 'primary' : 
                                                            ($record['status'] === 'cancelled' ? 'danger' : 'warning')); 
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
                                                <td><?php echo $record['cost'] ? '$' . number_format($record['cost'], 2) : 'N/A'; ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" data-bs-target="#updateStatusModal" 
                                                                data-id="<?php echo $record['id']; ?>"
                                                                data-status="<?php echo $record['status']; ?>">
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
                                <label for="equipment_type" class="form-label">Equipment Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="equipment_type" name="equipment_type" required>
                                    <option value="">Select Type</option>
                                    <option value="tank">Tank</option>
                                    <option value="dispenser">Dispenser</option>
                                    <option value="generator">Generator</option>
                                    <option value="safety_equipment">Safety Equipment</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                    <option value="">Select Type</option>
                                    <option value="preventive">Preventive</option>
                                    <option value="corrective">Corrective</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="calibration">Calibration</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_date" class="form-label">Maintenance Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="maintenance_date" name="maintenance_date" required>
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
                            <div class="col-md-6">
                                <label for="service_provider" class="form-label">Service Provider</label>
                                <input type="text" class="form-control" id="service_provider" name="service_provider">
                            </div>
                            <div class="col-md-6">
                                <label for="technician_name" class="form-label">Technician Name</label>
                                <input type="text" class="form-control" id="technician_name" name="technician_name">
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
                                <option value="cancelled">Cancelled</option>
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
                order: [[3, 'desc']], // Sort by maintenance date
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
                            <h6>Maintenance Information</h6>
                            <p><strong>Equipment Type:</strong> ${record.equipment_type.charAt(0).toUpperCase() + record.equipment_type.slice(1).replace('_', ' ')}</p>
                            <p><strong>Maintenance Type:</strong> ${record.maintenance_type.charAt(0).toUpperCase() + record.maintenance_type.slice(1).replace('_', ' ')}</p>
                            <p><strong>Maintenance Date:</strong> ${new Date(record.maintenance_date).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${record.status === 'completed' ? 'success' : record.status === 'in_progress' ? 'primary' : record.status === 'cancelled' ? 'danger' : 'warning'}">${record.status.charAt(0).toUpperCase() + record.status.slice(1).replace('_', ' ')}</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Assignment & Cost</h6>
                            <p><strong>Branch:</strong> ${record.branch_name}</p>
                            <p><strong>Performed By:</strong> ${record.first_name ? record.first_name + ' ' + record.last_name + ' (' + record.employee_id + ')' : 'Not assigned'}</p>
                            <p><strong>Cost:</strong> ${record.cost ? '$' + parseFloat(record.cost).toFixed(2) : 'N/A'}</p>
                            <p><strong>Service Provider:</strong> ${record.service_provider || 'N/A'}</p>
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
