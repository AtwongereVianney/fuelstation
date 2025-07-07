<?php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shifts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div>
            <h2 class="mb-4">Shifts</h2>
            <form method="get" class="mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="branch_id" class="form-label">Select Branch:</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php if ($b['id'] == $selected_branch_id) echo 'selected'; ?>><?php echo h($b['branch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo h($start_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo h($end_date); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View Shifts</button>
                    </div>
                </div>
            </form>
            <div class="mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Shifts Summary</h5>
                        <div class="row g-3">
                            <div class="col-md-3"><strong>Total Shifts:</strong> <?php echo h($shift_summary['total']); ?></div>
                            <div class="col-md-3"><strong>Active:</strong> <?php echo h($shift_summary['active']); ?></div>
                            <div class="col-md-3"><strong>Inactive:</strong> <?php echo h($shift_summary['inactive']); ?></div>
                            <div class="col-md-3">
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
            <h5 class="mb-3">Shifts</h5>
            <?php if ($shifts): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Description</th>
                                <th>Active</th>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No shifts found for this branch.</div>
            <?php endif; ?>
            <h5 class="mb-3">Shift Assignments</h5>
            <?php if ($assignments): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
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
            <?php else: ?>
                <div class="alert alert-info">No shift assignments found for this branch and date range.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 