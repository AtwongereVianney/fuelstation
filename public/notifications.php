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

// Notification types
$notification_types = [
    '' => 'All',
    'info' => 'Info',
    'warning' => 'Warning',
    'error' => 'Error',
    'success' => 'Success'
];

// Get filters
$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : '';
$selected_type = isset($_GET['notification_type']) ? $_GET['notification_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Helper for safe output
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }

// Build query
$where = ["n.deleted_at IS NULL"];
if ($selected_branch_id) $where[] = "n.branch_id = $selected_branch_id";
if ($selected_type) $where[] = "n.notification_type = '" . mysqli_real_escape_string($conn, $selected_type) . "'";
if ($start_date && $end_date) $where[] = "DATE(n.created_at) BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "'";
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Fetch notifications
$notifications = [];
$summary = [
    'total' => 0,
    'by_type' => [],
    'by_status' => []
];
$sql = "SELECT n.*, u.first_name, u.last_name, b.branch_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id LEFT JOIN branches b ON n.branch_id = b.id $where_sql ORDER BY n.created_at DESC, n.id DESC LIMIT 100";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $notifications[] = $row;
        $summary['total']++;
        $type = $row['notification_type'];
        $status = $row['is_read'] ? 'Read' : 'Unread';
        if (!isset($summary['by_type'][$type])) $summary['by_type'][$type] = 0;
        $summary['by_type'][$type]++;
        if (!isset($summary['by_status'][$status])) $summary['by_status'][$status] = 0;
        $summary['by_status'][$status]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex" style="min-height:100vh;">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container py-4">
            <div class="row">
                
                <div>
                    <h2 class="mb-4">Notifications</h2>
                    <form method="get" class="mb-4">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label for="branch_id" class="form-label">Branch:</label>
                                <select name="branch_id" id="branch_id" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($branches as $b): ?>
                                        <option value="<?php echo $b['id']; ?>" <?php if ($b['id'] == $selected_branch_id) echo 'selected'; ?>><?php echo h($b['branch_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="notification_type" class="form-label">Type:</label>
                                <select name="notification_type" id="notification_type" class="form-select">
                                    <?php foreach ($notification_types as $val => $label): ?>
                                        <option value="<?php echo h($val); ?>" <?php if ($val === $selected_type) echo 'selected'; ?>><?php echo h($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Start Date:</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo h($start_date); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">End Date:</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo h($end_date); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </div>
                    </form>
                    <div class="mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Notifications Summary</h5>
                                <div class="row g-3">
                                    <div class="col-md-3"><strong>Total:</strong> <?php echo h($summary['total']); ?></div>
                                    <div class="col-md-3">
                                        <strong>By Type:</strong>
                                        <ul class="mb-0">
                                            <?php foreach ($summary['by_type'] as $type => $count): ?>
                                                <li><?php echo h(ucfirst($type)); ?>: <?php echo h($count); ?></li>
                                            <?php endforeach; ?>
                                            <?php if (!$summary['by_type']): ?><li>None</li><?php endif; ?>
                                        </ul>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>By Status:</strong>
                                        <ul class="mb-0">
                                            <?php foreach ($summary['by_status'] as $status => $count): ?>
                                                <li><?php echo h($status); ?>: <?php echo h($count); ?></li>
                                            <?php endforeach; ?>
                                            <?php if (!$summary['by_status']): ?><li>None</li><?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h5 class="mb-3">Notifications</h5>
                    <?php if ($notifications): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>User</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                        <th>Action URL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $n): ?>
                                        <tr>
                                            <td><?php echo h($n['created_at']); ?></td>
                                            <td><?php echo h(ucfirst($n['notification_type'])); ?></td>
                                            <td><?php echo h($n['title']); ?></td>
                                            <td><?php echo h($n['message']); ?></td>
                                            <td><?php echo h(trim($n['first_name'] . ' ' . $n['last_name'])); ?></td>
                                            <td><?php echo h($n['branch_name']); ?></td>
                                            <td><?php echo $n['is_read'] ? '<span class="badge bg-success">Read</span>' : '<span class="badge bg-warning text-dark">Unread</span>'; ?></td>
                                            <td><?php if ($n['action_url']): ?><a href="<?php echo h($n['action_url']); ?>" target="_blank">Link</a><?php endif; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No notifications found for the selected filters.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 