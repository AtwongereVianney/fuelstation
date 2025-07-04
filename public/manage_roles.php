<?php
session_start();
require_once '../config/db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle Add Role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $name = trim($_POST['name'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $level = intval($_POST['level'] ?? 1);
    if ($name && $display_name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO roles (name, display_name, description, level) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $display_name, $description, $level);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: manage_roles.php');
        exit;
    }
}

// Handle Edit Role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_role'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $level = intval($_POST['level'] ?? 1);
    if ($id && $name && $display_name) {
        $stmt = mysqli_prepare($conn, "UPDATE roles SET name=?, display_name=?, description=?, level=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssii', $name, $display_name, $description, $level, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: manage_roles.php');
        exit;
    }
}

// Handle Delete Role (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_role'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = mysqli_prepare($conn, "UPDATE roles SET deleted_at=NOW() WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: manage_roles.php');
        exit;
    }
}

// Fetch roles
$roles = [];
$sql = "SELECT id, name, display_name, description, level FROM roles WHERE deleted_at IS NULL ORDER BY level DESC, display_name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2 class="mb-4">Roles Management</h2>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addRoleModal">Add New Role</button>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Display Name</th>
                            <th>Description</th>
                            <th>Level</th>
                            <th class="text-end" style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($role['id']); ?></td>
                            <td><?php echo htmlspecialchars($role['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($role['display_name']); ?></td>
                            <td><?php echo htmlspecialchars($role['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($role['level'] ?? ''); ?></td>
                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRoleModal<?php echo $role['id']; ?>">View</button>
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo $role['id']; ?>">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal<?php echo $role['id']; ?>">Delete</button>
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

<!-- Modals rendered outside the table for all roles -->
<?php foreach ($roles as $role): ?>
<!-- View Modal -->
<div class="modal fade" id="viewRoleModal<?php echo $role['id']; ?>" tabindex="-1" aria-labelledby="viewRoleModalLabel<?php echo $role['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewRoleModalLabel<?php echo $role['id']; ?>">Role Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <dl class="row">
          <dt class="col-sm-4">ID</dt><dd class="col-sm-8"><?php echo htmlspecialchars($role['id']); ?></dd>
          <dt class="col-sm-4">Name</dt><dd class="col-sm-8"><?php echo htmlspecialchars($role['name'] ?? ''); ?></dd>
          <dt class="col-sm-4">Display Name</dt><dd class="col-sm-8"><?php echo htmlspecialchars($role['display_name']); ?></dd>
          <dt class="col-sm-4">Description</dt><dd class="col-sm-8"><?php echo htmlspecialchars($role['description'] ?? ''); ?></dd>
          <dt class="col-sm-4">Level</dt><dd class="col-sm-8"><?php echo htmlspecialchars($role['level'] ?? ''); ?></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editRoleModal<?php echo $role['id']; ?>" tabindex="-1" aria-labelledby="editRoleModalLabel<?php echo $role['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRoleModalLabel<?php echo $role['id']; ?>">Edit Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" value="<?php echo $role['id']; ?>">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($role['name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Display Name</label>
          <input type="text" name="display_name" class="form-control" value="<?php echo htmlspecialchars($role['display_name']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"><?php echo htmlspecialchars($role['description'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Level</label>
          <input type="number" name="level" class="form-control" value="<?php echo htmlspecialchars($role['level'] ?? ''); ?>" min="1" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="edit_role" class="btn btn-warning">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Modal -->
<div class="modal fade" id="deleteRoleModal<?php echo $role['id']; ?>" tabindex="-1" aria-labelledby="deleteRoleModalLabel<?php echo $role['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteRoleModalLabel<?php echo $role['id']; ?>">Delete Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" value="<?php echo $role['id']; ?>">
        <p>Are you sure you want to delete the role <strong><?php echo htmlspecialchars($role['display_name']); ?></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="delete_role" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<!-- Add Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addRoleModalLabel">Add New Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Display Name</label>
          <input type="text" name="display_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Level</label>
          <input type="number" name="level" class="form-control" min="1" value="1" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_role" class="btn btn-primary">Add Role</button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>