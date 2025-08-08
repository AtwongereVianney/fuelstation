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
        $fuel_type_id = intval($_POST['fuel_type_id']);
        $tank_id = isset($_POST['tank_id']) ? intval($_POST['tank_id']) : null;
        $test_date = mysqli_real_escape_string($conn, $_POST['test_date']);
        $test_type = mysqli_real_escape_string($conn, $_POST['test_type']);
        $density = isset($_POST['density']) ? floatval($_POST['density']) : null;
        $octane_rating = isset($_POST['octane_rating']) ? intval($_POST['octane_rating']) : null;
        $water_content = isset($_POST['water_content']) ? floatval($_POST['water_content']) : null;
        $contamination_level = isset($_POST['contamination_level']) ? mysqli_real_escape_string($conn, $_POST['contamination_level']) : null;
        $color_grade = isset($_POST['color_grade']) ? mysqli_real_escape_string($conn, $_POST['color_grade']) : null;
        $test_result = mysqli_real_escape_string($conn, $_POST['test_result']);
        $tested_by = isset($_POST['tested_by']) ? mysqli_real_escape_string($conn, $_POST['tested_by']) : null;
        $lab_reference = isset($_POST['lab_reference']) ? mysqli_real_escape_string($conn, $_POST['lab_reference']) : null;
        $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : null;

        $sql = "INSERT INTO fuel_quality_tests (branch_id, fuel_type_id, tank_id, test_date, test_type, density, octane_rating, water_content, contamination_level, color_grade, test_result, tested_by, lab_reference, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iiisssdssssss', $branch_id, $fuel_type_id, $tank_id, $test_date, $test_type, $density, $octane_rating, $water_content, $contamination_level, $color_grade, $test_result, $tested_by, $lab_reference, $notes);
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
        $test_result = mysqli_real_escape_string($conn, $_POST['test_result']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        $sql = "UPDATE fuel_quality_tests SET test_result = ?, notes = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssi', $test_result, $notes, $test_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Test result updated successfully.'];
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

// Fetch fuel types for dropdown
$fuel_types = [];
$fuel_type_sql = "SELECT id, name, code FROM fuel_types WHERE deleted_at IS NULL ORDER BY name";
$fuel_type_result = mysqli_query($conn, $fuel_type_sql);
while ($row = mysqli_fetch_assoc($fuel_type_result)) {
    $fuel_types[] = $row;
}

// Fetch tanks for dropdown
$tanks = [];
$tank_sql = "SELECT id, tank_number, fuel_type_id FROM storage_tanks WHERE deleted_at IS NULL ORDER BY tank_number";
$tank_result = mysqli_query($conn, $tank_sql);
while ($row = mysqli_fetch_assoc($tank_result)) {
    $tanks[] = $row;
}

// Get selected branch and filters
$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : ($user_branch_id ?? ($branches[0]['id'] ?? null));
$test_result_filter = $_GET['test_result'] ?? '';
$fuel_type_filter = $_GET['fuel_type_id'] ?? '';
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
if ($test_result_filter) {
    $where_conditions[] = "qt.test_result = ?";
    $params[] = $test_result_filter;
    $param_types .= 's';
}
if ($fuel_type_filter) {
    $where_conditions[] = "qt.fuel_type_id = ?";
    $params[] = $fuel_type_filter;
    $param_types .= 'i';
}
if ($test_type_filter) {
    $where_conditions[] = "qt.test_type = ?";
    $params[] = $test_type_filter;
    $param_types .= 's';
}
$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT qt.*, b.branch_name, ft.name as fuel_type_name, st.tank_number 
        FROM fuel_quality_tests qt 
        LEFT JOIN branches b ON qt.branch_id = b.id 
        LEFT JOIN fuel_types ft ON qt.fuel_type_id = ft.id 
        LEFT JOIN storage_tanks st ON qt.tank_id = st.id 
        WHERE $where_clause 
        ORDER BY qt.test_date DESC, qt.id DESC";
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
        html, body { height: 100%; }
        body { min-height: 100vh; margin: 0; padding: 0; }
        .main-flex-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar-fixed { width: 240px; min-width: 200px; max-width: 300px; height: 100vh; position: sticky; top: 0; left: 0; z-index: 1020; background: #f8f9fa; border-right: 1px solid #dee2e6; }
        .main-content-scroll { flex: 1 1 0%; height: 100vh; overflow-y: auto; padding: 32px 24px 24px 24px; background: #fff; }
        @media (max-width: 767.98px) { .main-flex-container { display: block; height: auto; } .sidebar-fixed { display: none; } .main-content-scroll { height: auto; padding: 16px 8px; } }
        .result-pass { background-color: #d1e7dd; }
        .result-fail { background-color: #f8d7da; }
        .result-marginal { background-color: #fff3cd; }
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
                                            <label for="test_result" class="form-label">Test Result</label>
                                            <select class="form-select" id="test_result" name="test_result">
                                                <option value="">All Results</option>
                                                <option value="pass" <?php echo ($test_result_filter == 'pass') ? 'selected' : ''; ?>>Pass</option>
                                                <option value="fail" <?php echo ($test_result_filter == 'fail') ? 'selected' : ''; ?>>Fail</option>
                                                <option value="marginal" <?php echo ($test_result_filter == 'marginal') ? 'selected' : ''; ?>>Marginal</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="fuel_type_id" class="form-label">Fuel Type</label>
                                            <select class="form-select" id="fuel_type_id" name="fuel_type_id">
                                                <option value="">All Types</option>
                                                <?php foreach ($fuel_types as $fuel_type): ?>
                                                    <option value="<?php echo $fuel_type['id']; ?>" <?php echo ($fuel_type_filter == $fuel_type['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($fuel_type['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="test_type" class="form-label">Test Type</label>
                                            <select class="form-select" id="test_type" name="test_type">
                                                <option value="">All Types</option>
                                                <option value="routine" <?php echo ($test_type_filter == 'routine') ? 'selected' : ''; ?>>Routine</option>
                                                <option value="delivery" <?php echo ($test_type_filter == 'delivery') ? 'selected' : ''; ?>>Delivery</option>
                                                <option value="complaint" <?php echo ($test_type_filter == 'complaint') ? 'selected' : ''; ?>>Complaint</option>
                                                <option value="regulatory" <?php echo ($test_type_filter == 'regulatory') ? 'selected' : ''; ?>>Regulatory</option>
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
                                                    <th>Tank</th>
                                                    <th>Test Type</th>
                                                    <th>Result</th>
                                                    <th>Density</th>
                                                    <th>Octane</th>
                                                    <th>Branch</th>
                                                    <th>Tested By</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($test_records as $record): ?>
                                                    <tr class="result-<?php echo $record['test_result']; ?>">
                                                        <td><?php echo date('M d, Y', strtotime($record['test_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($record['fuel_type_name']); ?></td>
                                                        <td><?php echo $record['tank_number'] ? 'Tank ' . $record['tank_number'] : 'N/A'; ?></td>
                                                        <td><?php echo ucfirst($record['test_type']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $record['test_result'] === 'pass' ? 'success' : 
                                                                    ($record['test_result'] === 'fail' ? 'danger' : 'warning'); 
                                                            ?>">
                                                                <?php echo ucfirst($record['test_result']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $record['density'] ? number_format($record['density'], 4) : 'N/A'; ?></td>
                                                        <td><?php echo $record['octane_rating'] ? $record['octane_rating'] : 'N/A'; ?></td>
                                                        <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($record['tested_by']); ?></td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                        data-bs-toggle="modal" data-bs-target="#updateTestModal" 
                                                                        data-id="<?php echo $record['id']; ?>"
                                                                        data-result="<?php echo $record['test_result']; ?>"
                                                                        data-notes="<?php echo htmlspecialchars($record['notes'], ENT_QUOTES); ?>">
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
                                <label for="fuel_type_id" class="form-label">Fuel Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="fuel_type_id" name="fuel_type_id" required>
                                    <option value="">Select Fuel Type</option>
                                    <?php foreach ($fuel_types as $fuel_type): ?>
                                        <option value="<?php echo $fuel_type['id']; ?>"><?php echo htmlspecialchars($fuel_type['name'] . ' (' . $fuel_type['code'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="tank_id" class="form-label">Tank</label>
                                <select class="form-select" id="tank_id" name="tank_id">
                                    <option value="">Select Tank</option>
                                    <?php foreach ($tanks as $tank): ?>
                                        <option value="<?php echo $tank['id']; ?>">Tank <?php echo $tank['tank_number']; ?></option>
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
                                <label for="test_type" class="form-label">Test Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="test_type" name="test_type" required>
                                    <option value="">Select Type</option>
                                    <option value="routine">Routine</option>
                                    <option value="delivery">Delivery</option>
                                    <option value="complaint">Complaint</option>
                                    <option value="regulatory">Regulatory</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="test_result" class="form-label">Test Result <span class="text-danger">*</span></label>
                                <select class="form-select" id="test_result" name="test_result" required>
                                    <option value="">Select Result</option>
                                    <option value="pass">Pass</option>
                                    <option value="fail">Fail</option>
                                    <option value="marginal">Marginal</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label for="density" class="form-label">Density</label>
                                <input type="number" class="form-control" id="density" name="density" step="0.0001" min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="octane_rating" class="form-label">Octane Rating</label>
                                <input type="number" class="form-control" id="octane_rating" name="octane_rating" min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="water_content" class="form-label">Water Content</label>
                                <input type="number" class="form-control" id="water_content" name="water_content" step="0.001" min="0">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="contamination_level" class="form-label">Contamination Level</label>
                                <select class="form-select" id="contamination_level" name="contamination_level">
                                    <option value="">Select Level</option>
                                    <option value="clean">Clean</option>
                                    <option value="slight">Slight</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="heavy">Heavy</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="color_grade" class="form-label">Color Grade</label>
                                <input type="text" class="form-control" id="color_grade" name="color_grade">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="tested_by" class="form-label">Tested By</label>
                                <input type="text" class="form-control" id="tested_by" name="tested_by">
                            </div>
                            <div class="col-md-6">
                                <label for="lab_reference" class="form-label">Lab Reference</label>
                                <input type="text" class="form-control" id="lab_reference" name="lab_reference">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
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
                    <h5 class="modal-title">Update Test Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="test_id" name="test_id">
                        <div class="mb-3">
                            <label for="test_result" class="form-label">Test Result <span class="text-danger">*</span></label>
                            <select class="form-select" id="test_result" name="test_result" required>
                                <option value="pass">Pass</option>
                                <option value="fail">Fail</option>
                                <option value="marginal">Marginal</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="update_notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_test" class="btn btn-primary">Update Result</button>
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
            var result = button.data('result');
            var notes = button.data('notes');
            var modal = $(this);
            modal.find('#test_id').val(id);
            modal.find('#test_result').val(result);
            modal.find('#update_notes').val(notes);
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
                        <p><strong>Fuel Type:</strong> ${record.fuel_type_name}</p>
                        <p><strong>Tank:</strong> ${record.tank_number ? 'Tank ' + record.tank_number : 'N/A'}</p>
                        <p><strong>Test Type:</strong> ${record.test_type.charAt(0).toUpperCase() + record.test_type.slice(1)}</p>
                        <p><strong>Result:</strong> <span class="badge bg-${record.test_result === 'pass' ? 'success' : record.test_result === 'fail' ? 'danger' : 'warning'}">${record.test_result.charAt(0).toUpperCase() + record.test_result.slice(1)}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Test Parameters</h6>
                        <p><strong>Density:</strong> ${record.density ? parseFloat(record.density).toFixed(4) : 'N/A'}</p>
                        <p><strong>Octane Rating:</strong> ${record.octane_rating || 'N/A'}</p>
                        <p><strong>Water Content:</strong> ${record.water_content ? parseFloat(record.water_content).toFixed(3) : 'N/A'}</p>
                        <p><strong>Contamination Level:</strong> ${record.contamination_level ? record.contamination_level.charAt(0).toUpperCase() + record.contamination_level.slice(1) : 'N/A'}</p>
                        <p><strong>Color Grade:</strong> ${record.color_grade || 'N/A'}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Assignment & Reference</h6>
                        <p><strong>Branch:</strong> ${record.branch_name}</p>
                        <p><strong>Tested By:</strong> ${record.tested_by || 'N/A'}</p>
                        <p><strong>Lab Reference:</strong> ${record.lab_reference || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Notes</h6>
                        <p>${record.notes || 'No notes provided.'}</p>
                    </div>
                </div>
            `);
        });
    });
    </script>
</body>
</html>
