<?php
session_start();
require_once '../config/db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle Add Permission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_permission'])) {
    $name = trim($_POST['name'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $module = trim($_POST['module'] ?? '');
    if ($name && $display_name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO permissions (name, display_name, description, module) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssss', $name, $display_name, $description, $module);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: manage_permissions.php');
        exit;
    }
}

// Handle Edit Permission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_permission'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $module = trim($_POST['module'] ?? '');
    if ($id && $name && $display_name) {
        $stmt = mysqli_prepare($conn, "UPDATE permissions SET name=?, display_name=?, description=?, module=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssi', $name, $display_name, $description, $module, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: manage_permissions.php');
        exit;
    }
}

// Handle Delete Permission (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_permission'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = mysqli_prepare($conn, "UPDATE permissions SET deleted_at=NOW() WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: manage_permissions.php');
        exit;
    }
}

// Fetch permissions
$permissions = [];
$sql = "SELECT id, name, display_name, description, module FROM permissions WHERE deleted_at IS NULL ORDER BY module ASC, display_name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $permissions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Permissions</title>
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
            <h2 class="mb-4">Permissions Management</h2>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPermissionModal">Add New Permission</button>
            <?php
            // Group permissions by module
            $modules = [];
            foreach ($permissions as $perm) {
                $module = $perm['module'] ?: 'Other';
                $modules[$module][] = $perm;
            }
            ?>
            <div class="container-fluid px-0">
              <div class="row g-3">
                <?php foreach ($modules as $moduleName => $modulePerms): ?>
                  <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                      <div class="card-header bg-primary text-white">
                        <?php echo htmlspecialchars($moduleName); ?>
                      </div>
                      <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                          <?php foreach ($modulePerms as $perm): ?>
                            <li class="list-group-item">
                              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                                <div>
                                  <div class="fw-semibold"><?php echo htmlspecialchars($perm['display_name']); ?></div>
                                  <?php if (!empty($perm['description'])): ?>
                                    <div class="text-muted small"><?php echo htmlspecialchars($perm['description']); ?></div>
                                  <?php endif; ?>
                                  <div class="text-muted small">Name: <span class="fst-italic"><?php echo htmlspecialchars($perm['name']); ?></span></div>
                                </div>
                                <div class="mt-2 mt-md-0 ms-md-2 text-nowrap">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewPermissionModal<?php echo $perm['id']; ?>">View</button>
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editPermissionModal<?php echo $perm['id']; ?>">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePermissionModal<?php echo $perm['id']; ?>">Delete</button>
                                </div>
                              </div>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                    </div>
                  </div>
                    <?php endforeach; ?>
              </div>
            </div>
            <div class="d-block d-md-none small text-muted mt-2">Cards are scrollable. Tap a card to see more permissions.</div>
        </div>
    </div>
</div>
<!-- Modals rendered outside the table for all permissions -->
<?php foreach ($permissions as $perm): ?>
<!-- View Modal -->
<div class="modal fade" id="viewPermissionModal<?php echo $perm['id']; ?>" tabindex="-1" aria-labelledby="viewPermissionModalLabel<?php echo $perm['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewPermissionModalLabel<?php echo $perm['id']; ?>">Permission Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <dl class="row">
          <dt class="col-sm-4">ID</dt><dd class="col-sm-8"><?php echo htmlspecialchars($perm['id']); ?></dd>
          <dt class="col-sm-4">Name</dt><dd class="col-sm-8"><?php echo htmlspecialchars($perm['name'] ?? ''); ?></dd>
          <dt class="col-sm-4">Display Name</dt><dd class="col-sm-8"><?php echo htmlspecialchars($perm['display_name']); ?></dd>
          <dt class="col-sm-4">Description</dt><dd class="col-sm-8"><?php echo htmlspecialchars($perm['description'] ?? ''); ?></dd>
          <dt class="col-sm-4">Module</dt><dd class="col-sm-8"><?php echo htmlspecialchars($perm['module'] ?? ''); ?></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editPermissionModal<?php echo $perm['id']; ?>" tabindex="-1" aria-labelledby="editPermissionModalLabel<?php echo $perm['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editPermissionModalLabel<?php echo $perm['id']; ?>">Edit Permission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" value="<?php echo $perm['id']; ?>">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($perm['name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Display Name</label>
          <input type="text" name="display_name" class="form-control" value="<?php echo htmlspecialchars($perm['display_name']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"><?php echo htmlspecialchars($perm['description'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Module</label>
          <input type="text" name="module" class="form-control" value="<?php echo htmlspecialchars($perm['module'] ?? ''); ?>">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="edit_permission" class="btn btn-warning">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Modal -->
<div class="modal fade" id="deletePermissionModal<?php echo $perm['id']; ?>" tabindex="-1" aria-labelledby="deletePermissionModalLabel<?php echo $perm['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deletePermissionModalLabel<?php echo $perm['id']; ?>">Delete Permission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" value="<?php echo $perm['id']; ?>">
        <p>Are you sure you want to delete the permission <strong><?php echo htmlspecialchars($perm['display_name']); ?></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="delete_permission" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<!-- Add Modal -->
<div class="modal fade" id="addPermissionModal" tabindex="-1" aria-labelledby="addPermissionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addPermissionModalLabel">Add New Permission</h5>
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
          <label class="form-label">Module</label>
          <input type="text" name="module" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_permission" class="btn btn-primary">Add Permission</button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 