<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
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
    
    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['success_message'] = "Role added successfully!";
    } else {
      $_SESSION['error_message'] = "Error adding role: " . mysqli_error($conn);
    }
    
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
    
    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['success_message'] = "Role updated successfully!";
    } else {
      $_SESSION['error_message'] = "Error updating role: " . mysqli_error($conn);
    }
    
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
    
    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['success_message'] = "Role deleted successfully!";
    } else {
      $_SESSION['error_message'] = "Error deleting role: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
    header('Location: manage_roles.php');
    exit;
  }
}

// Fetch roles (only non-deleted ones)
$sql = "SELECT * FROM roles WHERE deleted_at IS NULL ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
  die("Error fetching roles: " . mysqli_error($conn));
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
<div class="d-flex" style="min-height:100vh;">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <h2 class="mb-4">Roles Management</h2>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addRoleModal">Add New Role</button>
        
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>S/N</th>
                <th>Name</th>
                <th>Display Name</th>
                <th>Description</th>
                <th>Level</th>
                <th class="text-end" style="width: 200px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sn = 1;
              $roles_array = []; // Store roles for modals
              if (mysqli_num_rows($result) > 0) {
                while ($role = mysqli_fetch_assoc($result)) {
                  $roles_array[] = $role; // Store role for modal generation
              ?>
                <tr>
                  <td><?php echo $sn++; ?></td>
                  <td><?php echo htmlspecialchars($role['name']); ?></td>
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
              <?php
                }
              } else {
              ?>
                <tr>
                  <td colspan="6" class="text-center">No roles found.</td>
                </tr>
              <?php
              }
              ?>
            </tbody>
          </table>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
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

<!-- Modals for each role (View, Edit, Delete) -->
<?php
if (!empty($roles_array)) {
  foreach ($roles_array as $role) {
?>
  <!-- View Modal -->
  <div class="modal fade" id="viewRoleModal<?php echo $role['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">View Role Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p><strong>Name:</strong> <?php echo htmlspecialchars($role['name']); ?></p>
          <p><strong>Display Name:</strong> <?php echo htmlspecialchars($role['display_name']); ?></p>
          <p><strong>Description:</strong> <?php echo htmlspecialchars($role['description'] ?? 'No description'); ?></p>
          <p><strong>Level:</strong> <?php echo htmlspecialchars($role['level']); ?></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editRoleModal<?php echo $role['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo $role['id']; ?>">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($role['name']); ?>" required>
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
            <input type="number" name="level" class="form-control" min="1" value="<?php echo htmlspecialchars($role['level']); ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_role" class="btn btn-warning">Update Role</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Modal -->
  <div class="modal fade" id="deleteRoleModal<?php echo $role['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the role: <strong><?php echo htmlspecialchars($role['display_name']); ?></strong>?</p>
          <p class="text-danger"><small>This action cannot be undone.</small></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <form method="post" style="display: inline;">
            <input type="hidden" name="id" value="<?php echo $role['id']; ?>">
            <button type="submit" name="delete_role" class="btn btn-danger">Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php
  }
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>