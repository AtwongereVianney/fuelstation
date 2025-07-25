<?php
session_start();
require_once '../includes/auth_helpers.php';
include '../config/db_connect.php';
// Handle Edit User
$edit_success = false;
$edit_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $role_id = intval($_POST['role_id'] ?? 0);
    $business_id = intval($_POST['business_id'] ?? 0);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    if ($user_id && $username && $email && $role_id && $business_id && $branch_id) {
        $update_sql = "UPDATE users SET username=?, email=?, status=?, business_id=?, branch_id=?, updated_at=NOW() WHERE id=?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, 'ssssii', $username, $email, $status, $business_id, $branch_id, $user_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        $role_update_sql = "UPDATE user_roles SET role_id=? WHERE user_id=?";
        $role_update_stmt = mysqli_prepare($conn, $role_update_sql);
        mysqli_stmt_bind_param($role_update_stmt, 'ii', $role_id, $user_id);
        mysqli_stmt_execute($role_update_stmt);
        mysqli_stmt_close($role_update_stmt);
        $edit_success = true;
    } else {
        $edit_errors[] = 'All fields are required.';
    }
}

// Handle Delete User (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['id'] ?? 0);
    if ($user_id) {
        $sql = "UPDATE users SET deleted_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Handle Add User
$add_success = false;
$add_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $role_id = intval($_POST['role_id'] ?? 0);
    $business_id = intval($_POST['business_id'] ?? 0);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    if ($username && $email && $password && $role_id && $business_id && $branch_id) {
        // Check for duplicate username/email
        $check_sql = "SELECT id FROM users WHERE (username=? OR email=?) AND deleted_at IS NULL LIMIT 1";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'ss', $username, $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        if (mysqli_fetch_assoc($check_result)) {
            $add_errors[] = 'Username or email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (username, email, password, status, business_id, branch_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, 'ssssii', $username, $email, $hashed_password, $status, $business_id, $branch_id);
            if (mysqli_stmt_execute($insert_stmt)) {
                $new_user_id = mysqli_insert_id($conn);
                $role_insert_sql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
                $role_insert_stmt = mysqli_prepare($conn, $role_insert_sql);
                mysqli_stmt_bind_param($role_insert_stmt, 'ii', $new_user_id, $role_id);
                mysqli_stmt_execute($role_insert_stmt);
                mysqli_stmt_close($role_insert_stmt);
                $add_success = true;
                require_once '../includes/email_helper.php';
                $welcome_subject = 'Welcome to Fuel Station Management System';
                $welcome_body = "Dear $username,\n\nYour account has been created.\n\nLogin Email: $email\nPassword: $password\n\nFor your security, please log in and change your password as soon as possible.\n\nRegards,\nFuel Station Management Team";
                send_email($email, $welcome_subject, $welcome_body);
                // Get role name for redirect
                $role_name = '';
                $role_name_sql = "SELECT name FROM roles WHERE id=? LIMIT 1";
                $role_name_stmt = mysqli_prepare($conn, $role_name_sql);
                mysqli_stmt_bind_param($role_name_stmt, 'i', $role_id);
                mysqli_stmt_execute($role_name_stmt);
                mysqli_stmt_bind_result($role_name_stmt, $role_name);
                mysqli_stmt_fetch($role_name_stmt);
                mysqli_stmt_close($role_name_stmt);
                // Redirect based on role
                if ($role_name === 'business_owner') {
                    header("Location: manage_businesses.php?user_id=$new_user_id");
                    exit;
                } else {
                    header("Location: branch_dashboard.php?branch_id=$branch_id");
                    exit;
                }
            } else {
                $add_errors[] = 'Failed to add user.';
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($check_stmt);
    } else {
        $add_errors[] = 'All fields are required.';
    }
}

// Fetch all users with their roles, business, and branch names
$sql = "SELECT u.id, u.username, u.email, u.status, u.created_at, u.updated_at, r.id as role_id, r.name as role_name, r.display_name as role_display_name, b.business_name, br.branch_name FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id LEFT JOIN businesses b ON u.business_id = b.id LEFT JOIN branches br ON u.branch_id = br.id WHERE u.deleted_at IS NULL ORDER BY u.id ASC";
$result = mysqli_query($conn, $sql);
$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

// Fetch all roles for edit modal
$roles = [];
$role_sql = "SELECT id, display_name FROM roles";
$role_result = mysqli_query($conn, $role_sql);
while ($row = mysqli_fetch_assoc($role_result)) {
    $roles[] = $row;
}

// Fetch businesses and branches for dropdowns
$businesses = [];
$businesses_sql = "SELECT id, business_name FROM businesses WHERE deleted_at IS NULL ORDER BY business_name";
$businesses_result = mysqli_query($conn, $businesses_sql);
if ($businesses_result) {
    while ($row = mysqli_fetch_assoc($businesses_result)) {
        $businesses[] = $row;
    }
}
$branches = [];
$branches_sql = "SELECT id, branch_name, business_id FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$branches_result = mysqli_query($conn, $branches_sql);
if ($branches_result) {
    while ($row = mysqli_fetch_assoc($branches_result)) {
        $branches[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    <div class="main-content-scroll mt-5">
            <!-- Include header -->
        <?php include '../includes/header.php'; ?>
            <!-- Mobile menu button -->
            <div class="d-md-none mb-3">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="fas fa-bars"></i> Menu
                </button>
            </div>
            <h2 class="mb-4">Users Management</h2>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-user-plus me-1"></i> Add New User</button>
            <?php if ($edit_success): ?>
                <div class="alert alert-success">User updated successfully.</div>
            <?php endif; ?>
            <?php if (!empty($edit_errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($edit_errors as $error) echo '<div>' . htmlspecialchars($error) . '</div>'; ?>
                </div>
            <?php endif; ?>
            <?php if ($add_success): ?>
                <div class="alert alert-success">User added successfully.</div>
            <?php endif; ?>
            <?php if (!empty($add_errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($add_errors as $error) echo '<div>' . htmlspecialchars($error) . '</div>'; ?>
                </div>
            <?php endif; ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">All Users</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Business</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $serial = 1; ?>
                                <?php foreach (
                                    $users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($serial++); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['role_display_name'] ?? $user['role_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($user['business_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($user['branch_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewUserModal<?php echo $user['id']; ?>" title="View"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>" title="Edit"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $user['id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                </div>
            </div>
            <!-- Modals for each user -->
            <?php foreach ($users as $user): ?>
            <!-- View Modal -->
            <div class="modal fade" id="viewUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="viewUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="viewUserModalLabel<?php echo $user['id']; ?>">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="table-responsive">
                      <table class="table table-bordered">
                          <tr><th>ID</th><td><?php echo htmlspecialchars($user['id']); ?></td></tr>
                          <tr><th>Username</th><td><?php echo htmlspecialchars($user['username']); ?></td></tr>
                          <tr><th>Email</th><td><?php echo htmlspecialchars($user['email']); ?></td></tr>
                          <tr><th>Role</th><td><?php echo htmlspecialchars($user['role_display_name'] ?? $user['role_name'] ?? 'N/A'); ?></td></tr>
                          <tr><th>Status</th><td><?php echo htmlspecialchars($user['status']); ?></td></tr>
                          <tr><th>Created At</th><td><?php echo htmlspecialchars($user['created_at']); ?></td></tr>
                          <tr><th>Updated At</th><td><?php echo htmlspecialchars($user['updated_at']); ?></td></tr>
                          <tr><th>Business</th><td><?php echo htmlspecialchars($user['business_name'] ?? 'N/A'); ?></td></tr>
                          <tr><th>Branch</th><td><?php echo htmlspecialchars($user['branch_name'] ?? 'N/A'); ?></td></tr>
                      </table>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Edit Modal -->
            <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form method="post" class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    <div class="mb-3">
                        <label for="username<?php echo $user['id']; ?>" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username<?php echo $user['id']; ?>" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email<?php echo $user['id']; ?>" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email<?php echo $user['id']; ?>" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="status<?php echo $user['id']; ?>" class="form-label">Status</label>
                        <select class="form-select" id="status<?php echo $user['id']; ?>" name="status">
                            <option value="active" <?php if ($user['status'] === 'active') echo 'selected'; ?>>Active</option>
                            <option value="inactive" <?php if ($user['status'] === 'inactive') echo 'selected'; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="role_id<?php echo $user['id']; ?>" class="form-label">Role</label>
                        <select class="form-select" id="role_id<?php echo $user['id']; ?>" name="role_id">
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php if ($user['role_id'] == $role['id']) echo 'selected'; ?>><?php echo htmlspecialchars($role['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="business_id<?php echo $user['id']; ?>" class="form-label">Business</label>
                        <select class="form-select business-select-edit" id="business_id<?php echo $user['id']; ?>" name="business_id" required data-user-id="<?php echo $user['id']; ?>">
                            <option value="">Select Business</option>
                            <?php foreach ($businesses as $business): ?>
                                <option value="<?php echo $business['id']; ?>" <?php if ($user['business_name'] == $business['business_name']) echo 'selected'; ?>><?php echo htmlspecialchars($business['business_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="branch_id<?php echo $user['id']; ?>" class="form-label">Branch</label>
                        <select class="form-select branch-select-edit" id="branch_id<?php echo $user['id']; ?>" name="branch_id" required data-user-id="<?php echo $user['id']; ?>">
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" data-business="<?php echo $branch['business_id']; ?>" <?php if ($user['branch_name'] == $branch['branch_name']) echo 'selected'; ?>><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-warning">Update User</button>
                  </div>
                </form>
              </div>
            </div>
            <!-- Delete Modal -->
            <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form method="post" class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel<?php echo $user['id']; ?>">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    <p>Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                  </div>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
            <!-- Add User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form method="post" class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="add_username" class="form-label">Username</label>
                      <input type="text" class="form-control" id="add_username" name="username" required>
                    </div>
                    <div class="mb-3">
                      <label for="add_email" class="form-label">Email</label>
                      <input type="email" class="form-control" id="add_email" name="email" required>
                    </div>
                    <div class="mb-3">
                      <label for="add_password" class="form-label">Password</label>
                      <input type="password" class="form-control" id="add_password" name="password" required>
                    </div>
                    <div class="mb-3">
                      <label for="add_status" class="form-label">Status</label>
                      <select class="form-select" id="add_status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="add_role_id" class="form-label">Role</label>
                      <select class="form-select" id="add_role_id" name="role_id" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                          <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['display_name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="add_business_id" class="form-label">Business</label>
                      <select class="form-select" id="add_business_id" name="business_id" required>
                        <option value="">Select Business</option>
                        <?php foreach ($businesses as $business): ?>
                          <option value="<?php echo $business['id']; ?>"><?php echo htmlspecialchars($business['business_name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="add_branch_id" class="form-label">Branch</label>
                      <select class="form-select" id="add_branch_id" name="branch_id" required>
                        <option value="">Select Branch</option>
                        <?php foreach ($branches as $branch): ?>
                          <option value="<?php echo $branch['id']; ?>" data-business="<?php echo $branch['business_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                  </div>
                </form>
              </div>
            </div>
            <script>
            // Filter branches by selected business in Add User modal
            const businessSelect = document.getElementById('add_business_id');
            const branchSelect = document.getElementById('add_branch_id');
            if (businessSelect && branchSelect) {
              businessSelect.addEventListener('change', function() {
                const businessId = this.value;
                for (let i = 0; i < branchSelect.options.length; i++) {
                  const option = branchSelect.options[i];
                  if (!option.value) continue;
                  option.style.display = option.getAttribute('data-business') === businessId ? '' : 'none';
                }
                branchSelect.value = '';
              });
              // Trigger on page load to hide all branches until business is selected
              businessSelect.dispatchEvent(new Event('change'));
            }
            </script>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 