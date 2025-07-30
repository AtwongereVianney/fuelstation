<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/auth_helpers.php';
require_once '../includes/email_helper.php';

// Test email configuration (uncomment to test)
// if (isset($_GET['test_email'])) {
//     $test_result = test_email_config();
//     if ($test_result) {
//         echo "Email test successful! Check your inbox.";
//     } else {
//         echo "Email test failed. Check error logs.";
//     }
//     exit;
// }

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

$business_id = $_SESSION['business_id'] ?? null;

// Fetch all users with their roles, business, and branch names
$users_sql = "SELECT u.id, u.username, u.email, u.status, u.created_at, u.updated_at, u.branch_id,
              r.id as role_id, r.name as role_name, r.display_name as role_display_name, 
              b.business_name, br.branch_name 
              FROM users u 
              LEFT JOIN user_roles ur ON u.id = ur.user_id 
              LEFT JOIN roles r ON ur.role_id = r.id 
              LEFT JOIN businesses b ON u.business_id = b.id 
              LEFT JOIN branches br ON u.branch_id = br.id 
              WHERE u.deleted_at IS NULL 
              ORDER BY u.created_at DESC";
$users_result = mysqli_query($conn, $users_sql);
$users = [];
if ($users_result) {
    while ($row = mysqli_fetch_assoc($users_result)) {
        $users[] = $row;
    }
}

// Get total count for badge
$total_users = count($users);

// Handle Add New User form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $response = array();
    
    // Validate required fields
    $required_fields = ['username', 'email', 'role'];
    $errors = array();
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }
    
    // Check if username already exists
    $check_username = "SELECT id FROM users WHERE username = ? AND deleted_at IS NULL";
    $stmt = mysqli_prepare($conn, $check_username);
    mysqli_stmt_bind_param($stmt, 's', $_POST['username']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Username already exists.";
    }
    mysqli_stmt_close($stmt);
    
    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL";
    $stmt = mysqli_prepare($conn, $check_email);
    mysqli_stmt_bind_param($stmt, 's', $_POST['email']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email already exists.";
    }
    mysqli_stmt_close($stmt);
    
    if (empty($errors)) {
        // Generate a random password
        $generated_password = generateRandomPassword();
        
        // Hash the password
        $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
        
        // Get role ID
        $role_sql = "SELECT id FROM roles WHERE name = ? AND deleted_at IS NULL";
        $stmt = mysqli_prepare($conn, $role_sql);
        mysqli_stmt_bind_param($stmt, 's', $_POST['role']);
        mysqli_stmt_execute($stmt);
        $role_result = mysqli_stmt_get_result($stmt);
        $role_data = mysqli_fetch_assoc($role_result);
        mysqli_stmt_close($stmt);
        
        if ($role_data) {
            $role_id = $role_data['id'];
            
            // Insert new user
            $insert_user = "INSERT INTO users (username, email, password, status, business_id, branch_id, created_at) 
                           VALUES (?, ?, ?, 'active', ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $insert_user);
            mysqli_stmt_bind_param($stmt, 'sssii', 
                $_POST['username'], 
                $_POST['email'], 
                $hashed_password,
                $business_id,
                $_POST['branch_id'] ?? null
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $new_user_id = mysqli_insert_id($conn);
                
                // Assign role to user
                $assign_role = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
                $stmt2 = mysqli_prepare($conn, $assign_role);
                mysqli_stmt_bind_param($stmt2, 'ii', $new_user_id, $role_id);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
                
                // Send email with credentials
                $email_sent = sendUserCredentials($_POST['email'], $_POST['username'], $generated_password);
                
                $response['success'] = true;
                $response['message'] = "User added successfully! " . ($email_sent ? "Login credentials have been sent to the user's email." : "Note: Email notification failed.");
                
                // Redirect to refresh the page and show the new user
                header('Location: manage_users.php?success=user_added');
                exit;
            } else {
                $response['success'] = false;
                $response['message'] = "Error adding user: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['success'] = false;
            $response['message'] = "Invalid role selected.";
        }
    } else {
        $response['success'] = false;
        $response['message'] = implode(" ", $errors);
    }
}

// Function to generate random password
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Function to send user credentials via email
function sendUserCredentials($email, $username, $password) {
    $subject = "Your Account Credentials - Fuel Station Management System";
    $body = "Dear $username,\n\n";
    $body .= "Your account has been created successfully in the Fuel Station Management System.\n\n";
    $body .= "Here are your login credentials:\n";
    $body .= "Username: $username\n";
    $body .= "Password: $password\n\n";
    $body .= "Please login at: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/login.php\n\n";
    $body .= "For security reasons, we recommend changing your password after your first login.\n\n";
    $body .= "Best regards,\nFuel Station Management Team";
    
    return send_email($email, $subject, $body);
}

// Handle Edit User form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $response = array();
    
    if (empty($_POST['user_id'])) {
        $response['success'] = false;
        $response['message'] = "User ID is required.";
    } else {
        $user_id = $_POST['user_id'];
        
        // Validate required fields
        if (empty($_POST['username']) || empty($_POST['email'])) {
            $response['success'] = false;
            $response['message'] = "Username and email are required.";
        } else {
            // Check if username already exists (excluding current user)
            $check_username = "SELECT id FROM users WHERE username = ? AND id != ? AND deleted_at IS NULL";
            $stmt = mysqli_prepare($conn, $check_username);
            mysqli_stmt_bind_param($stmt, 'si', $_POST['username'], $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $response['success'] = false;
                $response['message'] = "Username already exists.";
            } else {
                // Check if email already exists (excluding current user)
                $check_email = "SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL";
                $stmt = mysqli_prepare($conn, $check_email);
                mysqli_stmt_bind_param($stmt, 'si', $_POST['email'], $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($result) > 0) {
                    $response['success'] = false;
                    $response['message'] = "Email already exists.";
                } else {
                    // Start transaction
                    mysqli_begin_transaction($conn);
                    
                    try {
                        // Update user basic info
                        $update_user = "UPDATE users SET username = ?, email = ?, status = ?, branch_id = ?, updated_at = NOW() WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $update_user);
                        mysqli_stmt_bind_param($stmt, 'sssii', 
                            $_POST['username'], 
                            $_POST['email'], 
                            $_POST['status'],
                            $_POST['branch_id'] ?: null,
                            $user_id
                        );
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error updating user: " . mysqli_error($conn));
                        }
                        mysqli_stmt_close($stmt);
                        
                        // Update password if provided
                        if (!empty($_POST['new_password'])) {
                            if (strlen($_POST['new_password']) < 6) {
                                throw new Exception("Password must be at least 6 characters long.");
                            }
                            
                            $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                            $update_password = "UPDATE users SET password = ? WHERE id = ?";
                            $stmt = mysqli_prepare($conn, $update_password);
                            mysqli_stmt_bind_param($stmt, 'si', $hashed_password, $user_id);
                            
                            if (!mysqli_stmt_execute($stmt)) {
                                throw new Exception("Error updating password: " . mysqli_error($conn));
                            }
                            mysqli_stmt_close($stmt);
                        }
                        
                        // Update role if changed
                        if (!empty($_POST['role'])) {
                            // Get role ID
                            $role_sql = "SELECT id FROM roles WHERE name = ? AND deleted_at IS NULL";
                            $stmt = mysqli_prepare($conn, $role_sql);
                            mysqli_stmt_bind_param($stmt, 's', $_POST['role']);
                            mysqli_stmt_execute($stmt);
                            $role_result = mysqli_stmt_get_result($stmt);
                            $role_data = mysqli_fetch_assoc($role_result);
                            mysqli_stmt_close($stmt);
                            
                            if ($role_data) {
                                $role_id = $role_data['id'];
                                
                                // Remove existing role assignments
                                $delete_roles = "DELETE FROM user_roles WHERE user_id = ?";
                                $stmt = mysqli_prepare($conn, $delete_roles);
                                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_close($stmt);
                                
                                // Assign new role
                                $assign_role = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
                                $stmt = mysqli_prepare($conn, $assign_role);
                                mysqli_stmt_bind_param($stmt, 'ii', $user_id, $role_id);
                                
                                if (!mysqli_stmt_execute($stmt)) {
                                    throw new Exception("Error updating role: " . mysqli_error($conn));
                                }
                                mysqli_stmt_close($stmt);
                            }
                        }
                        
                        // Commit transaction
                        mysqli_commit($conn);
                        
                        $response['success'] = true;
                        $response['message'] = "User updated successfully!";
                        header('Location: manage_users.php?success=user_updated');
                        exit;
                        
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        mysqli_rollback($conn);
                        $response['success'] = false;
                        $response['message'] = $e->getMessage();
                    }
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    
    // Soft delete user
    $delete_user = "UPDATE users SET deleted_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_user);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        header('Location: manage_users.php?success=user_deleted');
        exit;
    } else {
        header('Location: manage_users.php?error=delete_failed');
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Fetch roles for dropdown
$roles_sql = "SELECT id, name, display_name FROM roles WHERE deleted_at IS NULL ORDER BY display_name";
$roles_result = mysqli_query($conn, $roles_sql);
$roles = [];
if ($roles_result) {
    while ($row = mysqli_fetch_assoc($roles_result)) {
        $roles[] = $row;
    }
}

// Fetch branches for dropdown
$branches_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$branches_result = mysqli_query($conn, $branches_sql);
$branches = [];
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
    <title>Manage Users - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Core Layout */
        html, body { height: 100%; margin: 0; padding: 0; }
        .main-flex-container { display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar-fixed { 
            width: 260px; 
            background: #2c3e50; 
            color: white; 
            overflow-y: auto; 
            transition: width 0.3s ease;
            z-index: 1000;
        }
        .sidebar-fixed.collapsed { width: 64px; }
        
        /* Main Content */
        .main-content-scroll { 
            flex: 1; 
            overflow-y: auto; 
            background: #f8f9fa;
            padding: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar-fixed { display: none; }
            .main-content-scroll { margin-left: 0; }
        }
        
        /* Custom styles for manage users */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6c757d;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.25rem;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Table responsive improvements */
        .table-responsive {
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        
        /* Modal improvements */
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        
        /* Modal and Offcanvas improvements */
        .offcanvas-backdrop {
            z-index: 1040;
        }
        
        .offcanvas {
            z-index: 1045;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal {
            z-index: 1055;
        }
        
        /* Ensure body remains scrollable when modal is closed */
        body:not(.modal-open) {
            overflow: auto !important;
        }
        
        /* Prevent backdrop from blocking interactions */
        .offcanvas-backdrop.show {
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Mobile Sidebar -->
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
                    <i class="bi bi-list"></i> Menu
                </button>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    switch ($_GET['success']) {
                        case 'user_added':
                            echo 'User added successfully! Login credentials have been sent to the user\'s email address.';
                            break;
                        case 'user_updated':
                            echo 'User updated successfully!';
                            break;
                        case 'user_deleted':
                            echo 'User deleted successfully!';
                            break;
                        default:
                            echo 'Operation completed successfully!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    switch ($_GET['error']) {
                        case 'delete_failed':
                            echo 'Failed to delete user. Please try again.';
                            break;
                        default:
                            echo 'An error occurred. Please try again.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-1">Manage Users<span class="badge badge-soft-primary ms-2"><?php echo $total_users; ?></span></h4>
                </div>
                <div class="gap-2 d-flex align-items-center flex-wrap">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-secondary px-2 shadow" data-bs-toggle="dropdown"><i class="bi bi-download me-2"></i>Export</a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <ul>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item"><i class="bi bi-file-pdf me-1"></i>Export as PDF</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item"><i class="bi bi-file-excel me-1"></i>Export as Excel</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <a href="test_email.php" class="btn btn-outline-info btn-sm" title="Test Email Configuration"><i class="bi bi-envelope me-1"></i>Test Email</a>
                    <a href="javascript:void(0);" class="btn btn-icon btn-outline-secondary shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="bi bi-arrow-clockwise"></i></a>
                    <a href="javascript:void(0);" class="btn btn-icon btn-outline-secondary shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="bi bi-chevron-up"></i></a>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="card border-0 rounded-0">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div class="position-relative">
                        <input type="text" class="form-control ps-4" placeholder="Search">
                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                    </div>
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvas_add"><i class="bi bi-plus-circle me-1"></i>Add User</a>
                </div>
                <div class="card-body">
                    <!-- Table header -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn btn-outline-secondary shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="bi bi-funnel me-2"></i>Filter<i class="bi bi-chevron-down ms-2"></i></a>
                                <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg p-0">
                                    <div class="filter-header d-flex align-items-center justify-content-between border-bottom">
                                        <h6 class="mb-0"><i class="bi bi-funnel me-1"></i>Filter</h6>
                                        <button type="button" class="btn-close close-filter-btn" data-bs-dismiss="dropdown-menu" aria-label="Close"></button>
                                    </div>
                                    <div class="filter-set-view p-3">
                                        <div class="accordion" id="accordionExample">
                                            <div class="filter-set-content">
                                                <div class="filter-set-content-head">
                                                    <a href="#" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">Name</a>
                                                </div>
                                                <div class="filter-set-contents accordion-collapse collapse show" id="collapseTwo" data-bs-parent="#accordionExample">
                                                    <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                        <div class="mb-2">
                                                            <div class="position-relative">
                                                                <input type="text" class="form-control form-control-md ps-4" placeholder="Search">
                                                                <span class="position-absolute top-50 start-0 translate-middle-y ms-2 text-muted">
                                                                    <i class="bi bi-search"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <ul class="mb-0">
                                                            <li class="mb-1">
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    <span class="user-avatar me-2">EM</span>Elizabeth Morgan
                                                                </label>
                                                            </li>
                                                            <li class="mb-1">
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    <span class="user-avatar me-2">KB</span>Katherine Brooks
                                                                </label>
                                                            </li>
                                                            <li class="mb-1">
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    <span class="user-avatar me-2">SL</span>Sophia Lopez
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 d-flex">Load More</a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="filter-set-content">
                                                <div class="filter-set-content-head">
                                                    <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#Status" aria-expanded="false" aria-controls="Status">Status</a>
                                                </div>
                                                <div class="filter-set-contents accordion-collapse collapse" id="Status" data-bs-parent="#accordionExample">
                                                    <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                        <ul>
                                                            <li>
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    Active
                                                                </label>
                                                            </li>
                                                            <li>
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    Inactive
                                                                </label>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="javascript:void(0);" class="btn btn-outline-secondary w-100">Reset</a>
                                            <a href="manage_users.php" class="btn btn-primary w-100">Filter</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                                <i class="bi bi-calendar text-dark fs-14 me-1"></i><span class="reportrange-picker-field">9 Jun 25 - 9 Jun 25</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-secondary px-2 shadow" data-bs-toggle="dropdown"><i class="bi bi-sort-down me-2"></i>Sort By</a>
                                <div class="dropdown-menu">
                                    <ul>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Newest</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Oldest</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn bg-soft-indigo px-2 border-0" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="bi bi-columns-gap me-2"></i>Manage Columns</a>
                                <div class="dropdown-menu dropdown-menu-md dropdown-md p-3">
                                    <ul>
                                        <li class="gap-1 d-flex align-items-center mb-2">
                                            <i class="bi bi-columns me-1"></i>
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Name</span>
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">
                                            <i class="bi bi-columns me-1"></i>
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Phone</span>
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">
                                            <i class="bi bi-columns me-1"></i>
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Email</span>
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">
                                            <i class="bi bi-columns me-1"></i>
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Created</span>
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">
                                            <i class="bi bi-columns me-1"></i>
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Last Activity</span>
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">
                                            <i class="bi bi-columns me-1"></i>
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Status</span>
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center">
                                            <i class="bi bi-columns me-1"></i>
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Action</span>
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                                </label>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact List -->
                    <div class="table-responsive custom-table">
                        <table class="table table-nowrap" id="manage-users-list">
                            <thead class="table-light">
                                <tr>
                                    <th class="no-sort">
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox" id="select-all">
                                        </div>
                                    </th>
                                    <th class="no-sort"></th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                    <th class="text-end no-sort">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="user-avatar"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
                                            </td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['business_name'] ?? 'N/A'); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role_display_name'] ?? $user['role_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($user['updated_at'] ?? 'Never'); ?></td>
                                            <td>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <span class="badge bg-success status-badge">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary status-badge">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewUserModal<?php echo $user['id']; ?>">
                                                                <i class="bi bi-eye me-2"></i>View
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                                <i class="bi bi-pencil me-2"></i>Edit
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                                                <i class="bi bi-trash me-2"></i>Delete
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                                <p class="mb-0">No users found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <label class="me-2">Show</label>
                                <select class="form-select form-select-sm" style="width: auto;" id="entries-per-page">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <label class="ms-2">entries</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                        </li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item">
                                            <a class="page-link" href="#">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add User Offcanvas -->
            <div class="offcanvas offcanvas-end offcanvas-large" tabindex="-1" id="offcanvas_add">
                <div class="offcanvas-header border-bottom">
                    <h5 class="fw-semibold">Add New User</h5>
                    <button type="button" class="btn-close custom-btn-close border p-1 me-0 d-flex align-items-center justify-content-center rounded-circle" data-bs-dismiss="offcanvas" aria-label="Close">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="offcanvas-body">
                    <form action="manage_users.php" method="POST">
                        <input type="hidden" name="action" value="add_user">
                        <div>
                            <!-- Basic Info -->
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-xxl border border-dashed me-3 flex-shrink-0">
                                                <div class="position-relative d-flex align-items-center">
                                                    <i class="bi bi-person text-dark fs-16"></i>
                                                </div>
                                            </div>
                                            <div class="d-inline-flex flex-column align-items-start">
                                                <div class="drag-upload-btn btn btn-sm btn-primary position-relative mb-2">
                                                    <i class="bi bi-upload me-1"></i>Upload file
                                                    <input type="file" class="form-control image-sign" multiple="">
                                                </div>
                                                <span>JPG, GIF or PNG. Max size of 800K</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required-field">Username</label>
                                            <input type="text" class="form-control" name="username" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="form-label required-field">Email</label>
                                                <div class="form-check form-switch form-check-reverse">
                                                    <input class="form-check-input" type="checkbox" id="switchCheckReverse">
                                                    <label class="form-check-label" for="switchCheckReverse">Email Opt Out</label>
                                                </div>
                                            </div>
                                            <input type="email" class="form-control" name="email" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required-field">Role</label>
                                            <select class="form-select" name="role" required>
                                                <option value="">Choose Role</option>
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo htmlspecialchars($role['name']); ?>">
                                                        <?php echo htmlspecialchars($role['display_name'] ?? $role['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Branch</label>
                                            <select class="form-select" name="branch_id">
                                                <option value="">Choose Branch (Optional)</option>
                                                <?php foreach ($branches as $branch): ?>
                                                    <option value="<?php echo htmlspecialchars($branch['id']); ?>">
                                                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>Note:</strong> A secure password will be automatically generated and sent to the user's email address.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end">
                            <a href="#" class="btn btn-light me-2" data-bs-dismiss="offcanvas">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit User Offcanvas -->
            <div class="offcanvas offcanvas-end offcanvas-large" tabindex="-1" id="offcanvas_edit">
                <div class="offcanvas-header border-bottom">
                    <h5 class="fw-semibold">Edit User</h5>
                    <button type="button" class="btn-close custom-btn-close border p-1 me-0 d-flex align-items-center justify-content-center rounded-circle" data-bs-dismiss="offcanvas" aria-label="Close">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="offcanvas-body">
                    <form action="manage_users.php" method="POST">
                        <div>
                            <!-- Basic Info -->
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-xxl border border-dashed me-3 flex-shrink-0">
                                                <div class="position-relative d-flex align-items-center">
                                                    <i class="bi bi-person text-dark fs-16"></i>
                                                </div>
                                            </div>
                                            <div class="d-inline-flex flex-column align-items-start">
                                                <div class="drag-upload-btn btn btn-sm btn-primary position-relative mb-2">
                                                    <i class="bi bi-upload me-1"></i>Upload file
                                                    <input type="file" class="form-control image-sign" multiple="">
                                                </div>
                                                <span>JPG, GIF or PNG. Max size of 800K</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required-field">First Name</label>
                                            <input type="text" class="form-control" name="first_name" value="Elizabeth Morgan" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required-field">User Name</label>
                                            <input type="text" class="form-control" name="username" value="Elizabeth@12" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="form-label required-field">Email</label>
                                                <div class="form-check form-switch form-check-reverse">
                                                    <input class="form-check-input" type="checkbox" id="switchCheckReverse2" checked>
                                                    <label class="form-check-label" for="switchCheckReverse2">Email Opt Out</label>
                                                </div>
                                            </div>
                                            <input type="email" class="form-control" name="email" value="elizabeth@gmail.com" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required-field">Role</label>
                                            <input type="text" class="form-control" name="role" value="Software" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required-field">Phone 1</label>
                                            <input type="tel" class="form-control phone" name="phone" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Phone 2</label>
                                            <input type="tel" class="form-control phone" name="phone2">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <div class="input-group input-group-flat pass-group">
                                                <input type="password" class="form-control pass-input" name="password">
                                                <span class="input-group-text toggle-password">
                                                    <i class="bi bi-eye-off"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label required-field">Repeat Password</label>
                                            <div class="input-group input-group-flat pass-group">
                                                <input type="password" class="form-control pass-input" name="password_confirm" required>
                                                <span class="input-group-text toggle-password">
                                                    <i class="bi bi-eye-off"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label required-field">Location</label>
                                            <select class="form-select" name="location" required>
                                                <option value="">Choose</option>
                                                <option value="germany">Germany</option>
                                                <option value="usa" selected>USA</option>
                                                <option value="canada">Canada</option>
                                                <option value="india">India</option>
                                                <option value="china">China</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end">
                            <a href="#" class="btn btn-light me-2" data-bs-dismiss="offcanvas">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Modal -->
            <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm rounded-0">
                    <div class="modal-content rounded-0">
                        <div class="modal-body p-4 text-center position-relative">
                            <div class="mb-3 position-relative z-1">
                                <span class="avatar avatar-xl badge-soft-danger border-0 text-danger rounded-circle"><i class="bi bi-trash fs-24"></i></span>
                            </div>
                            <h5 class="mb-1">Delete Confirmation</h5>
                            <p class="mb-3">Are you sure you want to remove user you selected.</p>
                            <div class="d-flex justify-content-center">
                                <a href="#" class="btn btn-light position-relative z-1 me-2 w-100" data-bs-dismiss="modal">Cancel</a>
                                <a href="#" class="btn btn-primary position-relative z-1 w-100" data-bs-dismiss="modal">Yes, Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View User Modal -->
            <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">EM</div>
                                    <h6>Elizabeth Morgan</h6>
                                    <p class="text-muted">Software Developer</p>
                                </div>
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Email:</strong> elizabeth@gmail.com</p>
                                            <p><strong>Phone:</strong> +1 87545 54503</p>
                                            <p><strong>Location:</strong> USA</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                                            <p><strong>Created:</strong> 9 Jun 2025</p>
                                            <p><strong>Last Activity:</strong> 2 hours ago</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-bs-dismiss="modal">Edit User</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Modals -->
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <!-- View User Modal -->
                    <div class="modal fade" id="viewUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="viewUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="viewUserModalLabel<?php echo $user['id']; ?>">User Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-3 text-center">
                                            <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
                                            <h6><?php echo htmlspecialchars($user['username']); ?></h6>
                                            <p class="text-muted"><?php echo htmlspecialchars($user['role_display_name'] ?? $user['role_name'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                                    <p><strong>Business:</strong> <?php echo htmlspecialchars($user['business_name'] ?? 'N/A'); ?></p>
                                                    <p><strong>Branch:</strong> <?php echo htmlspecialchars($user['branch_name'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Status:</strong> 
                                                        <?php if ($user['status'] === 'active'): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p><strong>Created:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
                                                    <p><strong>Last Updated:</strong> <?php echo htmlspecialchars($user['updated_at'] ?? 'Never'); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>" data-bs-dismiss="modal">Edit User</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit User Modal -->
                    <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">Edit User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="manage_users.php" method="POST">
                                        <input type="hidden" name="action" value="edit_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field">Username</label>
                                                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field">Email</label>
                                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status">
                                                        <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Role</label>
                                                    <select class="form-select" name="role">
                                                        <?php foreach ($roles as $role): ?>
                                                            <option value="<?php echo htmlspecialchars($role['name']); ?>" 
                                                                    <?php echo ($user['role_name'] === $role['name']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($role['display_name'] ?? $role['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Branch</label>
                                                    <select class="form-select" name="branch_id">
                                                        <option value="">No Branch</option>
                                                        <?php foreach ($branches as $branch): ?>
                                                            <option value="<?php echo htmlspecialchars($branch['id']); ?>" 
                                                                    <?php echo ($user['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">New Password (Optional)</label>
                                                    <div class="input-group input-group-flat pass-group">
                                                        <input type="password" class="form-control pass-input" name="new_password" placeholder="Leave blank to keep current password">
                                                        <span class="input-group-text toggle-password">
                                                            <i class="bi bi-eye-off"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delete User Modal -->
                    <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-sm">
                            <div class="modal-content">
                                <div class="modal-body p-4 text-center">
                                    <div class="mb-3">
                                        <span class="avatar avatar-xl badge-soft-danger border-0 text-danger rounded-circle">
                                            <i class="bi bi-trash fs-24"></i>
                                        </span>
                                    </div>
                                    <h5 class="mb-1">Delete Confirmation</h5>
                                    <p class="mb-3">Are you sure you want to delete user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                                    <div class="d-flex justify-content-center">
                                        <button type="button" class="btn btn-light me-2 w-100" data-bs-dismiss="modal">Cancel</button>
                                        <a href="manage_users.php?delete_user=<?php echo $user['id']; ?>" class="btn btn-danger w-100">Yes, Delete</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap Core JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.pass-input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye-off');
                    icon.classList.add('bi-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-off');
                }
            });
        });

        // Select all functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar-fixed');
            const header = document.querySelector('.main-header');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    if (header) {
                        header.classList.toggle('collapsed');
                    }
                });
            }

            // Entries per page functionality
            const entriesSelect = document.getElementById('entries-per-page');
            const tableBody = document.querySelector('#manage-users-list tbody');
            const allRows = Array.from(tableBody.querySelectorAll('tr'));
            
            if (entriesSelect) {
                entriesSelect.addEventListener('change', function() {
                    const selectedValue = parseInt(this.value);
                    showEntries(selectedValue);
                });
            }

            function showEntries(entriesPerPage) {
                // Hide all rows first
                allRows.forEach(row => {
                    row.style.display = 'none';
                });

                // Show only the specified number of entries
                const rowsToShow = allRows.slice(0, entriesPerPage);
                rowsToShow.forEach(row => {
                    row.style.display = '';
                });

                // Update pagination if needed
                updatePagination(entriesPerPage);
            }

            function updatePagination(entriesPerPage) {
                const totalPages = Math.ceil(allRows.length / entriesPerPage);
                const paginationContainer = document.querySelector('.pagination');
                
                if (paginationContainer) {
                    // Clear existing pagination
                    paginationContainer.innerHTML = '';
                    
                    // Add Previous button
                    const prevLi = document.createElement('li');
                    prevLi.className = 'page-item disabled';
                    prevLi.innerHTML = '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>';
                    paginationContainer.appendChild(prevLi);

                    // Add page numbers
                    for (let i = 1; i <= totalPages; i++) {
                        const li = document.createElement('li');
                        li.className = i === 1 ? 'page-item active' : 'page-item';
                        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
                        paginationContainer.appendChild(li);
                    }

                    // Add Next button
                    const nextLi = document.createElement('li');
                    nextLi.className = 'page-item';
                    nextLi.innerHTML = '<a class="page-link" href="#">Next</a>';
                    paginationContainer.appendChild(nextLi);
                }
            }

            // Initialize with default value (10 entries)
            if (entriesSelect) {
                showEntries(10);
            }

                    // Modal backdrop and close handling
        const addUserModal = document.getElementById('offcanvas_add');
        if (addUserModal) {
            addUserModal.addEventListener('hidden.bs.offcanvas', function () {
                // Reset form when modal is closed
                const form = addUserModal.querySelector('form');
                if (form) {
                    form.reset();
                    // Clear any validation messages
                    const alerts = form.querySelectorAll('.alert-danger');
                    alerts.forEach(alert => alert.remove());
                }
                // Remove any backdrop issues
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.offcanvas-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            });

            addUserModal.addEventListener('show.bs.offcanvas', function () {
                // Ensure proper backdrop handling
                document.body.classList.add('modal-open');
            });

            // Form validation
            const addUserForm = addUserModal.querySelector('form');
            if (addUserForm) {
                addUserForm.addEventListener('submit', function(e) {
                    const username = this.querySelector('input[name="username"]').value.trim();
                    const email = this.querySelector('input[name="email"]').value.trim();
                    const role = this.querySelector('select[name="role"]').value;
                    
                    let hasErrors = false;
                    
                    // Clear previous error messages
                    const existingAlerts = this.querySelectorAll('.alert-danger');
                    existingAlerts.forEach(alert => alert.remove());
                    
                    if (!username) {
                        showFormError(this, 'Username is required.');
                        hasErrors = true;
                    }
                    
                    if (!email) {
                        showFormError(this, 'Email is required.');
                        hasErrors = true;
                    } else if (!isValidEmail(email)) {
                        showFormError(this, 'Please enter a valid email address.');
                        hasErrors = true;
                    }
                    
                    if (!role) {
                        showFormError(this, 'Please select a role.');
                        hasErrors = true;
                    }
                    
                    if (hasErrors) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating User...';
                    submitBtn.disabled = true;
                    
                    // Re-enable after a delay in case of errors
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 5000);
                });
            }
        }

        // Function to show form errors
        function showFormError(form, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            form.insertBefore(alertDiv, form.firstChild);
        }

        // Function to validate email
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

            // Handle all offcanvas modals
            const allOffcanvas = document.querySelectorAll('.offcanvas');
            allOffcanvas.forEach(offcanvas => {
                offcanvas.addEventListener('hidden.bs.offcanvas', function () {
                    // Clean up backdrop and body classes
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.offcanvas-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                });
            });

            // Handle regular modals as well
            const allModals = document.querySelectorAll('.modal');
            allModals.forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function () {
                    // Clean up backdrop and body classes
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                });
            });
        });
    </script>
</body>
</html>