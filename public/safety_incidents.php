<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';

if (!has_permission('safety_incidents.view')) {
    header('Location: login.php?error=unauthorized');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_incident'])) {
        $branch_id = intval($_POST['branch_id']);
        $incident_type = mysqli_real_escape_string($conn, $_POST['incident_type']);
        $incident_date = mysqli_real_escape_string($conn, $_POST['incident_date']);
        $incident_time = mysqli_real_escape_string($conn, $_POST['incident_time']);
        $location_description = mysqli_real_escape_string($conn, $_POST['location_description']);
        $severity = mysqli_real_escape_string($conn, $_POST['severity']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $immediate_action_taken = mysqli_real_escape_string($conn, $_POST['immediate_action_taken']);
        $reported_by = $_SESSION['user_id'];
        $investigated_by = isset($_POST['investigated_by']) ? intval($_POST['investigated_by']) : null;
        $follow_up_required = isset($_POST['follow_up_required']) ? 1 : 0;
        $follow_up_date = !empty($_POST['follow_up_date']) ? mysqli_real_escape_string($conn, $_POST['follow_up_date']) : null;
        $status = 'reported';

        $sql = "INSERT INTO safety_incidents (branch_id, incident_type, incident_date, incident_time, location_description, severity, description, immediate_action_taken, reported_by, investigated_by, follow_up_required, follow_up_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isssssssiiss', $branch_id, $incident_type, $incident_date, $incident_time, $location_description, $severity, $description, $immediate_action_taken, $reported_by, $investigated_by, $follow_up_required, $follow_up_date, $status);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Safety incident reported successfully.'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'message' => 'Error reporting incident: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: safety_incidents.php');
        exit;
    }

    if (isset($_POST['update_incident'])) {
        $incident_id = intval($_POST['incident_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $investigation_notes = mysqli_real_escape_string($conn, $_POST['investigation_notes']);
        $corrective_measures = mysqli_real_escape_string($conn, $_POST['corrective_measures']);
        $investigated_by = $_SESSION['user_id'];

        $sql = "UPDATE safety_incidents SET status = ?, investigation_notes = ?, corrective_measures = ?, investigated_by = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sssii', $status, $investigation_notes, $corrective_measures, $investigated_by, $incident_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Incident updated successfully.'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'message' => 'Error updating incident: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: safety_incidents.php');
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
$severity_filter = $_GET['severity'] ?? '';
$incident_type_filter = $_GET['incident_type'] ?? '';

// Build query for incident records
$where_conditions = ["si.deleted_at IS NULL"];
$params = [];
$param_types = '';

if ($selected_branch_id) {
    $where_conditions[] = "si.branch_id = ?";
    $params[] = $selected_branch_id;
    $param_types .= 'i';
}

if ($status_filter) {
    $where_conditions[] = "si.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($severity_filter) {
    $where_conditions[] = "si.severity = ?";
    $params[] = $severity_filter;
    $param_types .= 's';
}

if ($incident_type_filter) {
    $where_conditions[] = "si.incident_type = ?";
    $params[] = $incident_type_filter;
    $param_types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT si.*, b.branch_name, 
        reporter.first_name as reporter_first_name, reporter.last_name as reporter_last_name, reporter.employee_id as reporter_employee_id,
        investigator.first_name as investigator_first_name, investigator.last_name as investigator_last_name, investigator.employee_id as investigator_employee_id
        FROM safety_incidents si
        LEFT JOIN branches b ON si.branch_id = b.id
        LEFT JOIN users reporter ON si.reported_by = reporter.id
        LEFT JOIN users investigator ON si.investigated_by = investigator.id
        WHERE $where_clause
        ORDER BY si.incident_date DESC, si.incident_time DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt && !empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$incident_records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $incident_records[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Incidents - Fuel Station Management</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/datatables.min.css" rel="stylesheet">
    <style>
        .severity-critical { background-color: #f8d7da; color: #721c24; }
        .severity-major { background-color: #fff3cd; color: #856404; }
        .severity-minor { background-color: #d1ecf1; color: #0c5460; }
        .status-reported { background-color: #fff3cd; }
        .status-investigating { background-color: #cce7ff; }
        .status-closed { background-color: #d1e7dd; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Safety Incidents</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Safety Incidents</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncidentModal">
                            <i class="bi bi-plus"></i> Report Incident
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
                                        <option value="reported" <?php echo ($status_filter == 'reported') ? 'selected' : ''; ?>>Reported</option>
                                        <option value="investigating" <?php echo ($status_filter == 'investigating') ? 'selected' : ''; ?>>Investigating</option>
                                        <option value="closed" <?php echo ($status_filter == 'closed') ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="severity" class="form-label">Severity</label>
                                    <select class="form-select" id="severity" name="severity">
                                        <option value="">All Severities</option>
                                        <option value="critical" <?php echo ($severity_filter == 'critical') ? 'selected' : ''; ?>>Critical</option>
                                        <option value="major" <?php echo ($severity_filter == 'major') ? 'selected' : ''; ?>>Major</option>
                                        <option value="minor" <?php echo ($severity_filter == 'minor') ? 'selected' : ''; ?>>Minor</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="incident_type" class="form-label">Incident Type</label>
                                    <select class="form-select" id="incident_type" name="incident_type">
                                        <option value="">All Types</option>
                                        <option value="spill" <?php echo ($incident_type_filter == 'spill') ? 'selected' : ''; ?>>Spill</option>
                                        <option value="fire" <?php echo ($incident_type_filter == 'fire') ? 'selected' : ''; ?>>Fire</option>
                                        <option value="injury" <?php echo ($incident_type_filter == 'injury') ? 'selected' : ''; ?>>Injury</option>
                                        <option value="equipment_failure" <?php echo ($incident_type_filter == 'equipment_failure') ? 'selected' : ''; ?>>Equipment Failure</option>
                                        <option value="security" <?php echo ($incident_type_filter == 'security') ? 'selected' : ''; ?>>Security</option>
                                        <option value="other" <?php echo ($incident_type_filter == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="safety_incidents.php" class="btn btn-secondary">Clear</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incident Records -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Safety Incidents</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="incidentTable">
                                    <thead>
                                        <tr>
                                            <th>Incident</th>
                                            <th>Type</th>
                                            <th>Branch</th>
                                            <th>Date & Time</th>
                                            <th>Severity</th>
                                            <th>Status</th>
                                            <th>Reported By</th>
                                            <th>Investigated By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($incident_records as $record): ?>
                                            <tr class="status-<?php echo $record['status']; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($record['description']); ?></strong>
                                                    <?php if ($record['location_description']): ?>
                                                        <br><small class="text-muted">Location: <?php echo htmlspecialchars($record['location_description']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $record['incident_type'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($record['incident_date'])); ?>
                                                    <br><small class="text-muted"><?php echo date('H:i', strtotime($record['incident_time'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $record['severity'] === 'critical' ? 'danger' : 
                                                            ($record['severity'] === 'major' ? 'warning' : 'info'); 
                                                    ?>">
                                                        <?php echo ucfirst($record['severity']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $record['status'] === 'closed' ? 'success' : 
                                                            ($record['status'] === 'investigating' ? 'primary' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($record['reporter_first_name']): ?>
                                                        <?php echo htmlspecialchars($record['reporter_first_name'] . ' ' . $record['reporter_last_name']); ?>
                                                        <br><small class="text-muted"><?php echo $record['reporter_employee_id']; ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Unknown</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($record['investigator_first_name']): ?>
                                                        <?php echo htmlspecialchars($record['investigator_first_name'] . ' ' . $record['investigator_last_name']); ?>
                                                        <br><small class="text-muted"><?php echo $record['investigator_employee_id']; ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" data-bs-target="#updateIncidentModal" 
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

    <!-- Add Incident Modal -->
    <div class="modal fade" id="addIncidentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Report Safety Incident</h5>
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
                                <label for="incident_type" class="form-label">Incident Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="incident_type" name="incident_type" required>
                                    <option value="">Select Type</option>
                                    <option value="spill">Spill</option>
                                    <option value="fire">Fire</option>
                                    <option value="injury">Injury</option>
                                    <option value="equipment_failure">Equipment Failure</option>
                                    <option value="security">Security</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="incident_date" class="form-label">Incident Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="incident_date" name="incident_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="incident_time" class="form-label">Incident Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="incident_time" name="incident_time" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                                <select class="form-select" id="severity" name="severity" required>
                                    <option value="">Select Severity</option>
                                    <option value="critical">Critical</option>
                                    <option value="major">Major</option>
                                    <option value="minor">Minor</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="investigated_by" class="form-label">Assign Investigator</label>
                                <select class="form-select" id="investigated_by" name="investigated_by">
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
                            <div class="col-12">
                                <label for="location_description" class="form-label">Location Description</label>
                                <input type="text" class="form-control" id="location_description" name="location_description">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="description" class="form-label">Incident Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="immediate_action_taken" class="form-label">Immediate Action Taken</label>
                                <textarea class="form-control" id="immediate_action_taken" name="immediate_action_taken" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="follow_up_required" name="follow_up_required">
                                    <label class="form-check-label" for="follow_up_required">
                                        Follow-up Required
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="follow_up_date" class="form-label">Follow-up Date</label>
                                <input type="date" class="form-control" id="follow_up_date" name="follow_up_date">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_incident" class="btn btn-primary">Report Incident</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Incident Modal -->
    <div class="modal fade" id="updateIncidentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="incident_id" name="incident_id">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="reported">Reported</option>
                                    <option value="investigating">Investigating</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="investigation_notes" class="form-label">Investigation Notes</label>
                                <textarea class="form-control" id="investigation_notes" name="investigation_notes" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="corrective_measures" class="form-label">Corrective Measures</label>
                                <textarea class="form-control" id="corrective_measures" name="corrective_measures" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_incident" class="btn btn-primary">Update Incident</button>
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
                    <h5 class="modal-title">Incident Details</h5>
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
            $('#incidentTable').DataTable({
                order: [[3, 'desc']], // Sort by date
                pageLength: 25
            });

            // Handle update incident modal
            $('#updateIncidentModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var status = button.data('status');
                var modal = $(this);
                modal.find('#incident_id').val(id);
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
                            <h6>Incident Information</h6>
                            <p><strong>Type:</strong> ${record.incident_type.replace('_', ' ').charAt(0).toUpperCase() + record.incident_type.replace('_', ' ').slice(1)}</p>
                            <p><strong>Severity:</strong> <span class="badge bg-${record.severity === 'critical' ? 'danger' : record.severity === 'major' ? 'warning' : 'info'}">${record.severity.charAt(0).toUpperCase() + record.severity.slice(1)}</span></p>
                            <p><strong>Branch:</strong> ${record.branch_name}</p>
                            <p><strong>Location:</strong> ${record.location_description || 'Not specified'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Timeline & Status</h6>
                            <p><strong>Date:</strong> ${new Date(record.incident_date).toLocaleDateString()}</p>
                            <p><strong>Time:</strong> ${record.incident_time}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${record.status === 'closed' ? 'success' : record.status === 'investigating' ? 'primary' : 'warning'}">${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</span></p>
                            <p><strong>Follow-up Required:</strong> ${record.follow_up_required ? 'Yes' : 'No'}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Description</h6>
                            <p>${record.description}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Immediate Action Taken</h6>
                            <p>${record.immediate_action_taken || 'No immediate action recorded.'}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>Reported By</h6>
                            <p>${record.reporter_first_name ? record.reporter_first_name + ' ' + record.reporter_last_name + ' (' + record.reporter_employee_id + ')' : 'Unknown'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Investigated By</h6>
                            <p>${record.investigator_first_name ? record.investigator_first_name + ' ' + record.investigator_last_name + ' (' + record.investigator_employee_id + ')' : 'Not assigned'}</p>
                        </div>
                    </div>
                    ${record.investigation_notes || record.corrective_measures ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Investigation & Corrective Measures</h6>
                            ${record.investigation_notes ? `<p><strong>Investigation Notes:</strong> ${record.investigation_notes}</p>` : ''}
                            ${record.corrective_measures ? `<p><strong>Corrective Measures:</strong> ${record.corrective_measures}</p>` : ''}
                        </div>
                    </div>
                    ` : ''}
                `);
            });
        });
    </script>
</body>
</html>
