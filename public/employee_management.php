<?php
session_start();
include '../config/db_connect.php';

// Handle Add Employee
$add_success = false;
$add_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $branch_id = intval($_POST['branch_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $hire_date = $_POST['hire_date'] ?? null;
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $business_id = $_SESSION['business_id'] ?? 0;
    // Generate employee_code
    $employee_code = '';
    $code_sql = "SELECT employee_code FROM employees WHERE employee_code LIKE 'EMP%' ORDER BY employee_code DESC LIMIT 1";
    $code_result = mysqli_query($conn, $code_sql);
    if ($code_result && mysqli_num_rows($code_result) > 0) {
        $row = mysqli_fetch_assoc($code_result);
        $last_code = $row['employee_code'];
        $number = intval(substr($last_code, 3)) + 1;
        $employee_code = 'EMP' . str_pad($number, 4, '0', STR_PAD_LEFT);
    } else {
        $employee_code = 'EMP0001';
    }
    if ($first_name && $last_name && $email && $business_id && $branch_id) {
        $check_sql = "SELECT id FROM employees WHERE email=? AND deleted_at IS NULL LIMIT 1";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 's', $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        if (mysqli_fetch_assoc($check_result)) {
            $add_errors[] = 'Email already exists.';
        } else {
            $insert_sql = "INSERT INTO employees (business_id, branch_id, employee_code, first_name, last_name, email, phone, position, hire_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, 'iissssssss', $business_id, $branch_id, $employee_code, $first_name, $last_name, $email, $phone, $position, $hire_date, $status);
            if (mysqli_stmt_execute($insert_stmt)) {
                $add_success = true;

                // Send congratulatory email to the new employee
                require_once '../includes/email_helper.php';
                $congrats_subject = 'Congratulations! You are now part of our team';
                $congrats_body = "Dear $first_name $last_name,\n\nCongratulations! You have been added as an employee at our fuel station. We are excited to have you as part of our team.\n\nIf you have any questions, please contact your supervisor.\n\nBest regards,\nFuel Station Management Team";
                send_email($email, $congrats_subject, $congrats_body);
            } else {
                $add_errors[] = 'Failed to add employee.';
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($check_stmt);
    } else {
        $add_errors[] = 'First name, last name, email, business, and branch are required.';
    }
}

// Handle Edit Employee
$edit_success = false;
$edit_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $id = intval($_POST['id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $branch_id = intval($_POST['branch_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $hire_date = $_POST['hire_date'] ?? null;
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    if ($id && $first_name && $last_name && $email) {
        $update_sql = "UPDATE employees SET first_name=?, last_name=?, email=?, phone=?, branch_id=?, position=?, hire_date=?, status=?, updated_at=NOW() WHERE id=?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, 'ssssssssi', $first_name, $last_name, $email, $phone, $branch_id, $position, $hire_date, $status, $id);
        if (mysqli_stmt_execute($update_stmt)) {
            $edit_success = true;
        } else {
            $edit_errors[] = 'Failed to update employee.';
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $edit_errors[] = 'First name, last name, and email are required.';
    }
}

// Handle Delete Employee (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $sql = "UPDATE employees SET deleted_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Handle Convert Employee to User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert_to_user'])) {
    $emp_id = intval($_POST['emp_id'] ?? 0);
    if ($emp_id) {
        // Fetch employee details
        $emp_sql = "SELECT * FROM employees WHERE id = ? AND deleted_at IS NULL LIMIT 1";
        $emp_stmt = mysqli_prepare($conn, $emp_sql);
        mysqli_stmt_bind_param($emp_stmt, 'i', $emp_id);
        mysqli_stmt_execute($emp_stmt);
        $emp_result = mysqli_stmt_get_result($emp_stmt);
        $emp = mysqli_fetch_assoc($emp_result);
        mysqli_stmt_close($emp_stmt);
        if ($emp) {
            // Check if already a user
            $check_sql = "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, 's', $emp['email']);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $already_user = mysqli_fetch_assoc($check_result);
            mysqli_stmt_close($check_stmt);
            if ($already_user) {
                $add_errors[] = 'Employee is already a user.';
            } else {
                // Generate username and password
                $username = strtolower(preg_replace('/\s+/', '', $emp['first_name'] . $emp['last_name']));
                $password = substr(bin2hex(random_bytes(4)), 0, 8); // 8-char random password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $status = 'active';
                // Insert into users
                $insert_sql = "INSERT INTO users (business_id, branch_id, username, email, phone, password, first_name, last_name, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, 'iisssssss', $emp['business_id'], $emp['branch_id'], $username, $emp['email'], $emp['phone'], $hashed_password, $emp['first_name'], $emp['last_name'], $status);
                if (mysqli_stmt_execute($insert_stmt)) {
                    $new_user_id = mysqli_insert_id($conn);
                    // Assign default role (fuel_attendant, id=5)
                    $role_id = 5;
                    $role_insert_sql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
                    $role_insert_stmt = mysqli_prepare($conn, $role_insert_sql);
                    mysqli_stmt_bind_param($role_insert_stmt, 'ii', $new_user_id, $role_id);
                    mysqli_stmt_execute($role_insert_stmt);
                    mysqli_stmt_close($role_insert_stmt);
                    // Email credentials
                    require_once '../includes/email_helper.php';
                    $subject = 'Your User Account Has Been Created';
                    $body = "Dear {$emp['first_name']} {$emp['last_name']},\n\nYou have been granted access to the Fuel Station Management System.\n\nLogin Username: $username\nLogin Email: {$emp['email']}\nPassword: $password\n\nPlease log in and change your password as soon as possible.\n\nBest regards,\nFuel Station Management Team";
                    send_email($emp['email'], $subject, $body);
                    $add_success = true;
                } else {
                    $add_errors[] = 'Failed to convert employee to user.';
                }
                mysqli_stmt_close($insert_stmt);
            }
        } else {
            $add_errors[] = 'Employee not found.';
        }
    }
}

// Fetch all employees (not deleted)
$sql = "SELECT e.*, b.branch_name FROM employees e LEFT JOIN branches b ON e.branch_id = b.id WHERE e.deleted_at IS NULL ORDER BY e.id ASC";
$result = mysqli_query($conn, $sql);
$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = $row;
}

// Fetch all branches for dropdown
$branches = [];
$branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name ASC";
$branch_result = mysqli_query($conn, $branch_sql);
while ($row = mysqli_fetch_assoc($branch_result)) {
    $branches[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
    <div class="main-content-scroll">
            <?php include '../includes/header.php'; ?>
            <!-- Mobile menu button -->
            <div class="d-md-none mb-3">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="bi bi-list"></i> Menu
                </button>
            </div>
            <h2 class="mb-4">Employee Management</h2>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="bi bi-person-plus me-1"></i> Add New Employee
        </button>
            <?php if ($edit_success): ?>
                <div class="alert alert-success">Employee updated successfully.</div>
            <?php endif; ?>
            <?php if (!empty($edit_errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($edit_errors as $error) echo '<div>' . htmlspecialchars($error) . '</div>'; ?>
                </div>
            <?php endif; ?>
            <?php if ($add_success): ?>
                <div class="alert alert-success">Employee added successfully.</div>
            <?php endif; ?>
            <?php if (!empty($add_errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($add_errors as $error) echo '<div>' . htmlspecialchars($error) . '</div>'; ?>
                </div>
            <?php endif; ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">All Employees</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Branch</th>
                                <th>Position</th>
                                <th>Hire Date</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $serial = 1; ?>
                                <?php foreach ($employees as $emp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($serial++); ?></td>
                                        <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['branch_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['hire_date']); ?></td>
                                        <td>
                                            <?php if ($emp['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($emp['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewEmployeeModal<?php echo $emp['id']; ?>" title="View"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editEmployeeModal<?php echo $emp['id']; ?>" title="Edit"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteEmployeeModal<?php echo $emp['id']; ?>" title="Delete"><i class="bi bi-trash"></i></button>
                                            </div>
                                            <!-- Convert to User button -->
                                            <?php
                                            // Check if employee is already a user
                                            $is_user = false;
                                            $check_user_sql = "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1";
                                            $check_user_stmt = mysqli_prepare($conn, $check_user_sql);
                                            mysqli_stmt_bind_param($check_user_stmt, 's', $emp['email']);
                                            mysqli_stmt_execute($check_user_stmt);
                                            mysqli_stmt_store_result($check_user_stmt);
                                            if (mysqli_stmt_num_rows($check_user_stmt) > 0) $is_user = true;
                                            mysqli_stmt_close($check_user_stmt);
                                            if (!$is_user): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="emp_id" value="<?php echo $emp['id']; ?>">
                                                <button type="submit" name="convert_to_user" class="btn btn-sm btn-success" onclick="return confirm('Convert this employee to a user?');">Convert to User</button>
                                            </form>
                                            <?php else: ?>
                                            <span class="text-muted">User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
                </div>
            </div>
    </div>
</div>
<!-- All Employee Modals (moved to end of body) -->
            <?php foreach ($employees as $emp): ?>
            <!-- View Modal -->
            <div class="modal fade" id="viewEmployeeModal<?php echo $emp['id']; ?>" tabindex="-1" aria-labelledby="viewEmployeeModalLabel<?php echo $emp['id']; ?>" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="viewEmployeeModalLabel<?php echo $emp['id']; ?>">Employee Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <dl class="row">
                      <dt class="col-sm-4">Name</dt><dd class="col-sm-8"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></dd>
                      <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?php echo htmlspecialchars($emp['email']); ?></dd>
                      <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?php echo htmlspecialchars($emp['phone']); ?></dd>
                      <dt class="col-sm-4">Branch</dt><dd class="col-sm-8"><?php echo htmlspecialchars($emp['branch_name'] ?? ''); ?></dd>
          <dt class="col-sm-4">Position</dt><dd class="col-sm-8"><?php echo htmlspecialchars($emp['position']); ?></dd>
          <dt class="col-sm-4">Hire Date</dt><dd class="col-sm-8"><?php echo htmlspecialchars($emp['hire_date']); ?></dd>
                      <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?php echo htmlspecialchars($emp['status']); ?></dd>
                      <dt class="col-sm-4">Created At</dt><dd class="col-sm-8"><?php echo htmlspecialchars($emp['created_at']); ?></dd>
                    </dl>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Edit Modal -->
            <div class="modal fade" id="editEmployeeModal<?php echo $emp['id']; ?>" tabindex="-1" aria-labelledby="editEmployeeModalLabel<?php echo $emp['id']; ?>" aria-hidden="true">
              <div class="modal-dialog">
                <form method="post" class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel<?php echo $emp['id']; ?>">Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                    <div class="mb-3">
                      <label class="form-label">First Name</label>
                      <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($emp['first_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Last Name</label>
                      <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($emp['last_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Email</label>
                      <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($emp['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Phone</label>
                      <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($emp['phone']); ?>">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Branch</label>
                      <select name="branch_id" class="form-select">
                        <option value="">Select Branch</option>
                        <?php foreach ($branches as $branch): ?>
                          <option value="<?php echo $branch['id']; ?>" <?php if ($emp['branch_id'] == $branch['id']) echo 'selected'; ?>><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
        <div class="mb-3">
          <label class="form-label">Position</label>
          <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($emp['position']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Hire Date</label>
          <input type="date" name="hire_date" class="form-control" value="<?php echo htmlspecialchars($emp['hire_date']); ?>">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Status</label>
                      <select name="status" class="form-select">
                        <option value="active" <?php if ($emp['status'] === 'active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if ($emp['status'] === 'inactive') echo 'selected'; ?>>Inactive</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_employee" class="btn btn-warning">Save Changes</button>
                  </div>
                </form>
              </div>
            </div>
            <!-- Delete Modal -->
            <div class="modal fade" id="deleteEmployeeModal<?php echo $emp['id']; ?>" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel<?php echo $emp['id']; ?>" aria-hidden="true">
              <div class="modal-dialog">
                <form method="post" class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="deleteEmployeeModalLabel<?php echo $emp['id']; ?>">Delete Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                    <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong>?</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_employee" class="btn btn-danger">Delete</button>
                  </div>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
<!-- Add Employee Modal (already at end of body) -->
            <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="post" class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">First Name</label>
                      <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Last Name</label>
                      <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Email</label>
                      <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Phone</label>
                      <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Branch</label>
                      <select name="branch_id" class="form-select">
                        <option value="">Select Branch</option>
                        <?php foreach ($branches as $branch): ?>
                          <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
        <div class="mb-3">
          <label class="form-label">Position</label>
          <input type="text" name="position" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Hire Date</label>
          <input type="date" name="hire_date" class="form-control">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Status</label>
                      <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
                  </div>
                </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 