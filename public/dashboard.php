<?php
session_start();
require_once '../config/db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role_name'] ?? '';
$username = $_SESSION['username'] ?? '';
$role_display = $_SESSION['role_display_name'] ?? $role;

// Fetch user permissions
$user_permissions = [];
$sql = "SELECT p.name FROM permissions p
        JOIN role_permissions rp ON rp.permission_id = p.id
        JOIN user_roles ur ON ur.role_id = rp.role_id
        WHERE ur.user_id = ? AND p.deleted_at IS NULL";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $user_permissions[] = $row['name'];
}
mysqli_stmt_close($stmt);

function has_permission($perm) {
    global $user_permissions;
    return in_array($perm, $user_permissions);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 rounded">
                <div class="container-fluid">
                    <span class="navbar-text">Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role_display); ?>)</span>
                </div>
            </nav>
            <div class="row g-4 mb-4">
                <?php if ($role === 'super_admin'): ?>
                    <?php
                    // Total users
                    $users_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"))[0];
                    // Total roles
                    $roles_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM roles WHERE deleted_at IS NULL"))[0];
                    // Total permissions
                    $perms_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM permissions WHERE deleted_at IS NULL"))[0];
                    // Total branches
                    $branches_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM branches WHERE deleted_at IS NULL"))[0];
                    // Recent activity (last 5 audit logs)
                    $recent_activity = [];
                    $res = mysqli_query($conn, "SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE a.deleted_at IS NULL ORDER BY a.created_at DESC LIMIT 5");
                    while ($row = mysqli_fetch_assoc($res)) {
                        $recent_activity[] = $row;
                    }
                    ?>
                    <div class="col-md-3">
                        <div class="card text-bg-primary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Users</h5>
                                <p class="card-text display-6 fw-bold"><?php echo $users_count; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-bg-success h-100">
                            <div class="card-body">
                                <h5 class="card-title">Roles</h5>
                                <p class="card-text display-6 fw-bold"><?php echo $roles_count; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-bg-warning h-100">
                            <div class="card-body">
                                <h5 class="card-title">Permissions</h5>
                                <p class="card-text display-6 fw-bold"><?php echo $perms_count; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-bg-info h-100">
                            <div class="card-body">
                                <h5 class="card-title">Branches</h5>
                                <p class="card-text display-6 fw-bold"><?php echo $branches_count; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($role === 'super_admin'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">Recent Activity</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['record_id']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">My Profile</h5>
                                <?php
                                $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id AND deleted_at IS NULL");
                                $user = mysqli_fetch_assoc($res);
                                ?>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></li>
                                    <li class="list-group-item"><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></li>
                                    <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                                    <li class="list-group-item"><strong>Role:</strong> <?php echo htmlspecialchars($role_display); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php if (has_permission('branches.view')): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Branch Info</h5>
                                <?php
                                $branch = null;
                                if (!empty($user['branch_id'])) {
                                    $res = mysqli_query($conn, "SELECT * FROM branches WHERE id = " . intval($user['branch_id']) . " AND deleted_at IS NULL");
                                    $branch = mysqli_fetch_assoc($res);
                                }
                                ?>
                                <?php if ($branch): ?>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Branch:</strong> <?php echo htmlspecialchars($branch['branch_name']); ?></li>
                                        <li class="list-group-item"><strong>Type:</strong> <?php echo htmlspecialchars($branch['branch_type']); ?></li>
                                        <li class="list-group-item"><strong>Manager:</strong> <?php echo htmlspecialchars($branch['manager_name']); ?></li>
                                        <li class="list-group-item"><strong>Status:</strong> <?php echo htmlspecialchars($branch['status']); ?></li>
                                    </ul>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">No branch assigned.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">My Permissions</div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($user_permissions as $perm): ?>
                                <li class="list-group-item"><?php echo htmlspecialchars($perm); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>