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

// Fetch shift assignments for the branch and date range
$assignments = [];
$summary = [
    'total' => 0,
    'by_status' => [],
    'by_shift' => [],
    'by_user' => []
];
if ($selected_branch_id && $start_date && $end_date) {
    $sql = "SELECT sa.*, s.shift_name, u.first_name, u.last_name FROM shift_assignments sa JOIN shifts s ON sa.shift_id = s.id JOIN users u ON sa.user_id = u.id WHERE s.branch_id = $selected_branch_id AND sa.assignment_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "' AND sa.deleted_at IS NULL ORDER BY sa.assignment_date DESC, s.start_time";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $assignments[] = $row;
            $summary['total']++;
            $status = $row['status'];
            $shift = $row['shift_name'];
            $user = $row['first_name'] . ' ' . $row['last_name'];
            if (!isset($summary['by_status'][$status])) $summary['by_status'][$status] = 0;
            $summary['by_status'][$status]++;
            if (!isset($summary['by_shift'][$shift])) $summary['by_shift'][$shift] = 0;
            $summary['by_shift'][$shift]++;
            if (!isset($summary['by_user'][$user])) $summary['by_user'][$user] = 0;
            $summary['by_user'][$user]++;
        }
    }
}

// Fetch shifts and users for dropdowns
$shifts = [];
$res = mysqli_query($conn, "SELECT id, shift_name, start_time, end_time FROM shifts WHERE branch_id = $selected_branch_id AND deleted_at IS NULL ORDER BY start_time");
if ($res) while ($row = mysqli_fetch_assoc($res)) $shifts[] = $row;
$users = [];
$res = mysqli_query($conn, "SELECT id, first_name, last_name FROM users WHERE branch_id = $selected_branch_id AND deleted_at IS NULL ORDER BY first_name, last_name");
if ($res) while ($row = mysqli_fetch_assoc($res)) $users[] = $row;

// Handle Assign/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assign or Edit
    if (isset($_POST['assign_shift']) || isset($_POST['edit_assignment'])) {
        $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
        $shift_id = intval($_POST['shift_id']);
        $user_id = intval($_POST['user_id']);
        $assignment_date = mysqli_real_escape_string($conn, $_POST['assignment_date']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        $opening_cash = floatval($_POST['opening_cash']);
        $closing_cash = floatval($_POST['closing_cash']);
        $cash_difference = floatval($_POST['cash_difference']);
        $clock_in_time = mysqli_real_escape_string($conn, $_POST['clock_in_time']);
        $clock_out_time = mysqli_real_escape_string($conn, $_POST['clock_out_time']);
        $total_hours = floatval($_POST['total_hours']);
        $total_sales = floatval($_POST['total_sales']);
        if (isset($_POST['assign_shift'])) {
            $sql = "INSERT INTO shift_assignments (shift_id, user_id, assignment_date, status, notes, opening_cash, closing_cash, cash_difference, clock_in_time, clock_out_time, total_hours, total_sales) VALUES ($shift_id, $user_id, '$assignment_date', '$status', '$notes', $opening_cash, $closing_cash, $cash_difference, '$clock_in_time', '$clock_out_time', $total_hours, $total_sales)";
            mysqli_query($conn, $sql);

            // Fetch user email and shift details
            $user_email = '';
            $user_name = '';
            $shift_name = '';
            $shift_time = '';
            $user_res = mysqli_query($conn, "SELECT email, first_name, last_name FROM users WHERE id = $user_id LIMIT 1");
            if ($user_row = mysqli_fetch_assoc($user_res)) {
                $user_email = $user_row['email'];
                $user_name = $user_row['first_name'] . ' ' . $user_row['last_name'];
            }
            $shift_res = mysqli_query($conn, "SELECT shift_name, start_time, end_time FROM shifts WHERE id = $shift_id LIMIT 1");
            if ($shift_row = mysqli_fetch_assoc($shift_res)) {
                $shift_name = $shift_row['shift_name'];
                $shift_time = $shift_row['start_time'] . ' - ' . $shift_row['end_time'];
            }

            // Send email notification
            if ($user_email) {
                $subject = "Shift Assignment Notification";
                $message = "Dear $user_name,\n\nYou have been assigned to the shift: $shift_name on $assignment_date ($shift_time).\n\nPlease report accordingly.\n\nThank you.";
                $headers = "From: no-reply@yourdomain.com\r\n";
                mail($user_email, $subject, $message, $headers);
            }
        } elseif (isset($_POST['edit_assignment'])) {
            $sql = "UPDATE shift_assignments SET shift_id=$shift_id, user_id=$user_id, assignment_date='$assignment_date', status='$status', notes='$notes', opening_cash=$opening_cash, closing_cash=$closing_cash, cash_difference=$cash_difference, clock_in_time='$clock_in_time', clock_out_time='$clock_out_time', total_hours=$total_hours, total_sales=$total_sales WHERE id=$assignment_id";
            mysqli_query($conn, $sql);
        }
        header('Location: shift_assignments.php?branch_id=' . $selected_branch_id . '&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date));
        exit;
    }
    // Delete
    if (isset($_POST['delete_assignment'])) {
        $assignment_id = intval($_POST['assignment_id']);
        $sql = "UPDATE shift_assignments SET deleted_at=NOW() WHERE id=$assignment_id";
        mysqli_query($conn, $sql);
        header('Location: shift_assignments.php?branch_id=' . $selected_branch_id . '&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Assignments</title>
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
            <h2 class="mb-4">Shift Assignments</h2>
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
                        <button type="submit" class="btn btn-primary w-100">View Assignments</button>
                    </div>
                </div>
            </form>
            <div class="mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Assignments Summary</h5>
                        <div class="row g-3">
                            <div class="col-12 col-md-3"><strong>Total Assignments:</strong> <?php echo h($summary['total']); ?></div>
                            <div class="col-12 col-md-3">
                                <strong>By Status:</strong>
                                <ul class="mb-0">
                                    <?php foreach ($summary['by_status'] as $status => $count): ?>
                                        <li><?php echo h($status); ?>: <?php echo h($count); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (!$summary['by_status']): ?><li>None</li><?php endif; ?>
                                </ul>
                            </div>
                            <div class="col-12 col-md-3">
                                <strong>By Shift:</strong>
                                <ul class="mb-0">
                                    <?php foreach ($summary['by_shift'] as $shift => $count): ?>
                                        <li><?php echo h($shift); ?>: <?php echo h($count); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (!$summary['by_shift']): ?><li>None</li><?php endif; ?>
                                </ul>
                            </div>
                            <div class="col-12 col-md-3">
                                <strong>By User:</strong>
                                <ul class="mb-0">
                                    <?php foreach ($summary['by_user'] as $user => $count): ?>
                                        <li><?php echo h($user); ?>: <?php echo h($count); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (!$summary['by_user']): ?><li>None</li><?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <h5 class="mb-3 d-flex justify-content-between align-items-center">Shift Assignments
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#assignmentModal" id="assignShiftBtn"><i class="bi bi-plus"></i> Assign Shift</button>
            </h5>
            <?php if ($assignments): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Shift</th>
                                <th>User</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Total Hours</th>
                                <th>Total Sales</th>
                                <th>Opening Cash</th>
                                <th>Closing Cash</th>
                                <th>Cash Difference</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
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
                                    <td><?php echo h($a['opening_cash']); ?></td>
                                    <td><?php echo h($a['closing_cash']); ?></td>
                                    <td><?php echo h($a['cash_difference']); ?></td>
                                    <td><?php echo h($a['notes']); ?></td>
                                    <td><?php echo h($a['status']); ?></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <button class="btn btn-sm btn-primary editAssignmentBtn"
                                                data-id="<?php echo $a['id']; ?>"
                                                data-shift_id="<?php echo h($a['shift_id']); ?>"
                                                data-user_id="<?php echo h($a['user_id']); ?>"
                                                data-assignment_date="<?php echo h($a['assignment_date']); ?>"
                                                data-status="<?php echo h($a['status']); ?>"
                                                data-notes="<?php echo h($a['notes']); ?>"
                                                data-opening_cash="<?php echo h($a['opening_cash']); ?>"
                                                data-closing_cash="<?php echo h($a['closing_cash']); ?>"
                                                data-cash_difference="<?php echo h($a['cash_difference']); ?>"
                                                data-clock_in_time="<?php echo h($a['clock_in_time']); ?>"
                                                data-clock_out_time="<?php echo h($a['clock_out_time']); ?>"
                                                data-total_hours="<?php echo h($a['total_hours']); ?>"
                                                data-total_sales="<?php echo h($a['total_sales']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#assignmentModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger deleteAssignmentBtn"
                                                data-id="<?php echo $a['id']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#deleteAssignmentModal">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
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
<!-- Assign/Edit Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignmentModalLabel">Assign/Edit Shift</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="assignment_id" id="assignment_id">
        <div class="mb-2">
          <label class="form-label">Shift</label>
          <select class="form-select" name="shift_id" id="shift_id" required>
            <?php foreach ($shifts as $s): ?>
              <option 
                value="<?php echo $s['id']; ?>"
                data-start_time="<?php echo h($s['start_time']); ?>"
                data-end_time="<?php echo h($s['end_time']); ?>"
              >
                <?php echo h($s['shift_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">User</label>
          <select class="form-select" name="user_id" id="user_id" required>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo $u['id']; ?>"><?php echo h($u['first_name'] . ' ' . $u['last_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Date</label>
          <input type="date" class="form-control" name="assignment_date" id="assignment_date" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Status</label>
          <input type="text" class="form-control" name="status" id="status">
        </div>
        <div class="mb-2">
          <label class="form-label">Notes</label>
          <input type="text" class="form-control" name="notes" id="notes">
        </div>
        <div class="mb-2">
          <label class="form-label">Opening Cash</label>
          <input type="number" step="0.01" class="form-control" name="opening_cash" id="opening_cash">
        </div>
        <div class="mb-2">
          <label class="form-label">Closing Cash</label>
          <input type="number" step="0.01" class="form-control" name="closing_cash" id="closing_cash">
        </div>
        <div class="mb-2">
          <label class="form-label">Cash Difference</label>
          <input type="number" step="0.01" class="form-control" name="cash_difference" id="cash_difference">
        </div>
        <div class="mb-2">
          <label class="form-label">Clock In</label>
          <input type="time" class="form-control" name="clock_in_time" id="clock_in_time">
        </div>
        <div class="mb-2">
          <label class="form-label">Clock Out</label>
          <input type="time" class="form-control" name="clock_out_time" id="clock_out_time">
        </div>
        <div class="mb-2">
          <label class="form-label">Total Hours</label>
          <input type="number" step="0.01" class="form-control" name="total_hours" id="total_hours">
        </div>
        <div class="mb-2">
          <label class="form-label">Total Sales</label>
          <input type="number" step="0.01" class="form-control" name="total_sales" id="total_sales">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" name="assign_shift" id="assignShiftSubmit">Assign Shift</button>
        <button type="submit" class="btn btn-primary d-none" name="edit_assignment" id="editAssignmentSubmit">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Assignment Modal -->
<div class="modal fade" id="deleteAssignmentModal" tabindex="-1" aria-labelledby="deleteAssignmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteAssignmentModalLabel">Delete Assignment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="assignment_id" id="delete_assignment_id">
        <p>Are you sure you want to delete this assignment?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" name="delete_assignment">Delete</button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Assign/Edit Modal logic
const assignmentModal = document.getElementById('assignmentModal');
const assignShiftBtn = document.getElementById('assignShiftBtn');
const assignmentModalLabel = document.getElementById('assignmentModalLabel');
const assignShiftSubmit = document.getElementById('assignShiftSubmit');
const editAssignmentSubmit = document.getElementById('editAssignmentSubmit');
if (assignShiftBtn) {
  assignShiftBtn.addEventListener('click', function() {
    assignmentModalLabel.textContent = 'Assign Shift';
    assignShiftSubmit.classList.remove('d-none');
    editAssignmentSubmit.classList.add('d-none');
    document.getElementById('assignment_id').value = '';
    document.getElementById('shift_id').selectedIndex = 0;
    document.getElementById('user_id').selectedIndex = 0;
    document.getElementById('assignment_date').value = '';
    document.getElementById('status').value = '';
    document.getElementById('notes').value = '';
    document.getElementById('opening_cash').value = '';
    document.getElementById('closing_cash').value = '';
    document.getElementById('cash_difference').value = '';
    document.getElementById('clock_in_time').value = '';
    document.getElementById('clock_out_time').value = '';
    document.getElementById('total_hours').value = '';
    document.getElementById('total_sales').value = '';
  });
}
document.querySelectorAll('.editAssignmentBtn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    assignmentModalLabel.textContent = 'Edit Assignment';
    assignShiftSubmit.classList.add('d-none');
    editAssignmentSubmit.classList.remove('d-none');
    document.getElementById('assignment_id').value = btn.getAttribute('data-id');
    document.getElementById('shift_id').value = btn.getAttribute('data-shift_id');
    document.getElementById('user_id').value = btn.getAttribute('data-user_id');
    document.getElementById('assignment_date').value = btn.getAttribute('data-assignment_date');
    document.getElementById('status').value = btn.getAttribute('data-status');
    document.getElementById('notes').value = btn.getAttribute('data-notes');
    document.getElementById('opening_cash').value = btn.getAttribute('data-opening_cash');
    document.getElementById('closing_cash').value = btn.getAttribute('data-closing_cash');
    document.getElementById('cash_difference').value = btn.getAttribute('data-cash_difference');
    document.getElementById('clock_in_time').value = btn.getAttribute('data-clock_in_time');
    document.getElementById('clock_out_time').value = btn.getAttribute('data-clock_out_time');
    document.getElementById('total_hours').value = btn.getAttribute('data-total_hours');
    document.getElementById('total_sales').value = btn.getAttribute('data-total_sales');
  });
});
// Delete Modal logic
const deleteAssignmentModal = document.getElementById('deleteAssignmentModal');
document.querySelectorAll('.deleteAssignmentBtn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById('delete_assignment_id').value = btn.getAttribute('data-id');
  });
});

// Auto-fill Clock In/Out when shift is selected
document.getElementById('shift_id').addEventListener('change', function() {
  var selected = this.options[this.selectedIndex];
  var start = selected.getAttribute('data-start_time');
  var end = selected.getAttribute('data-end_time');
  if (start) document.getElementById('clock_in_time').value = start;
  if (end) document.getElementById('clock_out_time').value = end;
});

// Also trigger when opening the modal for Assign Shift
if (assignShiftBtn) {
  assignShiftBtn.addEventListener('click', function() {
    var shiftSelect = document.getElementById('shift_id');
    var selected = shiftSelect.options[shiftSelect.selectedIndex];
    var start = selected.getAttribute('data-start_time');
    var end = selected.getAttribute('data-end_time');
    if (start) document.getElementById('clock_in_time').value = start;
    if (end) document.getElementById('clock_out_time').value = end;
  });
}

function calculateTotalHours() {
  var inTime = document.getElementById('clock_in_time').value;
  var outTime = document.getElementById('clock_out_time').value;
  var totalHoursField = document.getElementById('total_hours');
  if (inTime && outTime) {
    // Parse times as Date objects on the same day
    var today = new Date().toISOString().split('T')[0];
    var inDate = new Date(today + 'T' + inTime);
    var outDate = new Date(today + 'T' + outTime);
    var diffMs = outDate - inDate;
    var diffHrs = diffMs / (1000 * 60 * 60);
    // If negative (overnight shift), add 24 hours
    if (diffHrs < 0) diffHrs += 24;
    totalHoursField.value = diffHrs.toFixed(2);
  } else {
    totalHoursField.value = '';
  }
}

document.getElementById('clock_in_time').addEventListener('change', calculateTotalHours);
document.getElementById('clock_out_time').addEventListener('change', calculateTotalHours);

// Also call after autofill (when shift is selected or modal is opened)
document.getElementById('shift_id').addEventListener('change', calculateTotalHours);
if (assignShiftBtn) {
  assignShiftBtn.addEventListener('click', calculateTotalHours);
}
</script>
</body>
</html> 