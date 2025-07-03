<?php
session_start();
require_once 'db_connect.php';
require_once 'auth_helpers.php';

if (!has_permission('users.update')) {
    die('Access denied.');
}

$success = '';
$errors = [];

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role_id'])) {
    $user_id = (int)$_POST['user_id'];
    $role_id = (int)$_POST['role_id'];
    $assigned_by = $_SESSION['user_id'];
    // Remove old roles
    $sql = "DELETE FROM user_roles WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    // Assign new role
    $sql = "INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $role_id, $assigned_by);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Role updated successfully.';
    } else {
        $errors[] = 'Failed to update role.';
    }
    mysqli_stmt_close($stmt);
}

// Fetch all users (except self)
$sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, r.id as role_id, r.display_name as role_name
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        WHERE u.deleted_at IS NULL AND u.id != ?
        ORDER BY u.id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}
mysqli_stmt_close($stmt);

// Fetch all roles
$roles = [];
$res = mysqli_query($conn, "SELECT id, display_name FROM roles WHERE deleted_at IS NULL ORDER BY level DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $roles[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Roles - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Uganda Fuel Station</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-light me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </div>
</nav>
<div class="container mt-5">
    <h3>Manage User Roles</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error) echo '<div>' . $error . '</div>'; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <table class="table table-bordered table-striped mt-4">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <form method="post" action="">
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <select name="role_id" class="form-select">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php if ($user['role_id'] == $role['id']) echo 'selected'; ?>><?php echo htmlspecialchars($role['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html> 