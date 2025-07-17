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

// Get selected branch and date range
$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : ($branches[0]['id'] ?? null);
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Helper for safe output
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }

// Fetch shifts for the branch
$shifts = [];
$shift_summary = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'by_name' => []
];
if ($selected_branch_id) {
    $sql = "SELECT * FROM shifts WHERE branch_id = $selected_branch_id AND deleted_at IS NULL ORDER BY start_time";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $shifts[] = $row;
            $shift_summary['total']++;
            if ($row['is_active']) $shift_summary['active']++; else $shift_summary['inactive']++;
            $name = $row['shift_name'];
            if (!isset($shift_summary['by_name'][$name])) $shift_summary['by_name'][$name] = 0;
            $shift_summary['by_name'][$name]++;
        }
    }
}

// Fetch shift assignments for the branch and date range
$assignments = [];
if ($selected_branch_id && $start_date && $end_date) {
    $sql = "SELECT sa.*, s.shift_name, u.first_name, u.last_name FROM shift_assignments sa JOIN shifts s ON sa.shift_id = s.id JOIN users u ON sa.user_id = u.id WHERE s.branch_id = $selected_branch_id AND sa.assignment_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND sa.deleted_at IS NULL ORDER BY sa.assignment_date DESC, s.start_time";
    $res = mysqli_query($conn, $sql);
    if ($res) while ($row = mysqli_fetch_assoc($res)) $assignments[] = $row;
}

// Handle Add/Edit Shift
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_shift']) || isset($_POST['edit_shift'])) {
        $shift_name = mysqli_real_escape_string($conn, $_POST['shift_name']);
        $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
        $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $branch_id = $selected_branch_id;
        if (isset($_POST['add_shift'])) {
            $sql = "INSERT INTO shifts (branch_id, shift_name, start_time, end_time, description, is_active) VALUES ($branch_id, '$shift_name', '$start_time', '$end_time', '$description', $is_active)";
            mysqli_query($conn, $sql);
        } elseif (isset($_POST['edit_shift'])) {
            $shift_id = intval($_POST['shift_id']);
            $sql = "UPDATE shifts SET shift_name='$shift_name', start_time='$start_time', end_time='$end_time', description='$description', is_active=$is_active WHERE id=$shift_id AND branch_id=$branch_id";
            mysqli_query($conn, $sql);
        }
        header('Location: shifts.php?branch_id=' . $branch_id . '&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date));
        exit;
    }
    // Handle Delete
    if (isset($_POST['delete_shift'])) {
        $shift_id = intval($_POST['shift_id']);
        $sql = "UPDATE shifts SET deleted_at=NOW() WHERE id=$shift_id AND branch_id=$selected_branch_id";
        mysqli_query($conn, $sql);
        header('Location: shifts.php?branch_id=' . $selected_branch_id . '&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shifts</title>
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
            <!-- Mobile menu button -->
            <div class="d-md-none mb-3">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="fas fa-bars"></i> Menu
                </button>
            </div>
            <h2 class="mb-4">Shifts</h2>
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
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo h($start_date); ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo h($end_date); ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View Shifts</button>
                    </div>
                </div>
            </form>
            <div class="mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Shifts Summary</h5>
                        <div class="row g-3">
                            <div class="col-6 col-md-3"><strong>Total Shifts:</strong> <?php echo h($shift_summary['total']); ?></div>
                            <div class="col-6 col-md-3"><strong>Active:</strong> <?php echo h($shift_summary['active']); ?></div>
                            <div class="col-6 col-md-3"><strong>Inactive:</strong> <?php echo h($shift_summary['inactive']); ?></div>
                            <div class="col-12 col-md-3">
                                <strong>By Name:</strong>
                                <ul class="mb-0">
                                    <?php foreach ($shift_summary['by_name'] as $name => $count): ?>
                                        <li><?php echo h($name); ?>: <?php echo h($count); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (!$shift_summary['by_name']): ?><li>None</li><?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <h5 class="mb-3 d-flex justify-content-between align-items-center">Shifts
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#shiftModal" id="addShiftBtn"><i class="bi bi-plus"></i> Add Shift</button>
            </h5>
            <?php if ($shifts): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Description</th>
                                <th>Active</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shifts as $s): ?>
                                <tr>
                                    <td><?php echo h($s['shift_name']); ?></td>
                                    <td><?php echo h($s['start_time']); ?></td>
                                    <td><?php echo h($s['end_time']); ?></td>
                                    <td><?php echo h($s['description']); ?></td>
                                    <td><?php echo $s['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary me-1 editShiftBtn" 
                                            data-id="<?php echo $s['id']; ?>"
                                            data-name="<?php echo h($s['shift_name']); ?>"
                                            data-start="<?php echo h($s['start_time']); ?>"
                                            data-end="<?php echo h($s['end_time']); ?>"
                                            data-desc="<?php echo h($s['description']); ?>"
                                            data-active="<?php echo $s['is_active']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#shiftModal">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger deleteShiftBtn" 
                                            data-id="<?php echo $s['id']; ?>" 
                                            data-name="<?php echo h($s['shift_name']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal">
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
                <div class="alert alert-info">No shifts found for this branch.</div>
            <?php endif; ?>
            <h5 class="mb-3">Shift Assignments</h5>
            <?php if ($assignments): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Shift</th>
                                <th>User</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Total Hours</th>
                                <th>Total Sales</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><?php echo h($a['assignment_date']); ?></td>
                                    <td><?php echo h($a['shift_name']); ?></td>
                                    <td><?php echo h($a['first_name'] . ' ' . $a['last_name']); ?></td>
                                    <td><?php echo h($a['clock_in_time']); ?></td>
                                    <td><?php echo h($a['clock_out_time']); ?></td>
                                    <td><?php echo h($a['total_hours']); ?></td>
                                    <td><?php echo h($a['total_sales']); ?></td>
                                    <td><?php echo h($a['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
            <?php else: ?>
                <div class="alert alert-info">No shift assignments found for this branch and date range.</div>
            <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Shift Modal -->
<div class="modal fade" id="shiftModal" tabindex="-1" aria-labelledby="shiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shiftModalLabel">Add/Edit Shift</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="shift_id" id="shift_id">
        <div class="mb-3">
          <label for="shift_name" class="form-label">Shift Name</label>
          <input type="text" class="form-control" name="shift_name" id="shift_name" required>
        </div>
        <div class="mb-3">
          <label for="start_time" class="form-label">Start Time</label>
          <input type="time" class="form-control" name="start_time" id="start_time" required>
        </div>
        <div class="mb-3">
          <label for="end_time" class="form-label">End Time</label>
          <input type="time" class="form-control" name="end_time" id="end_time" required>
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" name="description" id="description"></textarea>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="is_active" id="is_active">
          <label class="form-check-label" for="is_active">Active</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" name="add_shift" id="addShiftSubmit">Add Shift</button>
        <button type="submit" class="btn btn-primary d-none" name="edit_shift" id="editShiftSubmit">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Delete Shift</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="shift_id" id="delete_shift_id">
        <p>Are you sure you want to delete <strong id="delete_shift_name"></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" name="delete_shift">Delete</button>
      </div>
    </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Add/Edit Modal logic
const shiftModal = document.getElementById('shiftModal');
const addShiftBtn = document.getElementById('addShiftBtn');
const shiftModalLabel = document.getElementById('shiftModalLabel');
const addShiftSubmit = document.getElementById('addShiftSubmit');
const editShiftSubmit = document.getElementById('editShiftSubmit');

if (addShiftBtn) {
  addShiftBtn.addEventListener('click', function() {
    shiftModalLabel.textContent = 'Add Shift';
    addShiftSubmit.classList.remove('d-none');
    editShiftSubmit.classList.add('d-none');
    document.getElementById('shift_id').value = '';
    document.getElementById('shift_name').value = '';
    document.getElementById('start_time').value = '';
    document.getElementById('end_time').value = '';
    document.getElementById('description').value = '';
    document.getElementById('is_active').checked = true;
  });
}
document.querySelectorAll('.editShiftBtn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    shiftModalLabel.textContent = 'Edit Shift';
    addShiftSubmit.classList.add('d-none');
    editShiftSubmit.classList.remove('d-none');
    document.getElementById('shift_id').value = btn.getAttribute('data-id');
    document.getElementById('shift_name').value = btn.getAttribute('data-name');
    document.getElementById('start_time').value = btn.getAttribute('data-start');
    document.getElementById('end_time').value = btn.getAttribute('data-end');
    document.getElementById('description').value = btn.getAttribute('data-desc');
    document.getElementById('is_active').checked = btn.getAttribute('data-active') == '1';
  });
});
// Delete Modal logic
const deleteModal = document.getElementById('deleteModal');
document.querySelectorAll('.deleteShiftBtn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById('delete_shift_id').value = btn.getAttribute('data-id');
    document.getElementById('delete_shift_name').textContent = btn.getAttribute('data-name');
  });
});
</script>
</body>
</html> 