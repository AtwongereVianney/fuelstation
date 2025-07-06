<?php
require_once '../config/db_connect.php';
// Fetch all roles
$roles = [];
$sql = "SELECT id, display_name FROM roles WHERE deleted_at IS NULL ORDER BY display_name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $roles[] = $row;
    }
}

// Fetch all permissions
$permissions = [];
$sql = "SELECT id, display_name FROM permissions WHERE deleted_at IS NULL ORDER BY display_name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $permissions[] = $row;
    }
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'], $_POST['permissions'])) {
    $role_id = intval($_POST['role_id']);
    $selected_permissions = array_map('intval', $_POST['permissions']);
    // Remove all current permissions for this role
    $stmt = mysqli_prepare($conn, "DELETE FROM role_permissions WHERE role_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $role_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    // Insert new permissions
    $stmt = mysqli_prepare($conn, "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    foreach ($selected_permissions as $perm_id) {
        mysqli_stmt_bind_param($stmt, 'ii', $role_id, $perm_id);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
    header('Location: manage_role_permissions.php?role_id=' . $role_id);
    exit;
}

// Determine selected role
$selected_role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : ($roles[0]['id'] ?? 0);

// Fetch current permissions for selected role
$current_permissions = [];
if ($selected_role_id) {
    $sql = "SELECT permission_id FROM role_permissions WHERE role_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $selected_role_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $current_permissions[] = $row['permission_id'];
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Role Permissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <div id="sidebar" class="sidebar ...">
                <?php include '../includes/sidebar.php'; ?>
            </div>
        </div>
        <div>
            <h2 class="mb-4">Role Permissions Management</h2>
            <form method="get" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="role_id" class="col-form-label">Select Role:</label>
                    </div>
                    <div class="col-auto">
                        <select name="role_id" id="role_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php if ($role['id'] == $selected_role_id) echo 'selected'; ?>><?php echo htmlspecialchars($role['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
            <form method="post">
                <input type="hidden" name="role_id" value="<?php echo $selected_role_id; ?>">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Permission</th>
                                <th class="text-center">Assigned</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($permissions as $perm): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($perm['display_name']); ?></td>
                                <td class="text-center">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>" <?php if (in_array($perm['id'], $current_permissions)) echo 'checked'; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-success">Save Permissions</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').onclick = function() {
    document.getElementById('sidebar').classList.toggle('collapsed');
};
</script>
</body>
</html> 