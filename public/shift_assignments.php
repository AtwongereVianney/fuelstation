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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Assignments</title>
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
                                <th>Opening Cash</th>
                                <th>Closing Cash</th>
                                <th>Cash Difference</th>
                                <th>Notes</th>
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
                                    <td><?php echo h($a['opening_cash']); ?></td>
                                    <td><?php echo h($a['closing_cash']); ?></td>
                                    <td><?php echo h($a['cash_difference']); ?></td>
                                    <td><?php echo h($a['notes']); ?></td>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 