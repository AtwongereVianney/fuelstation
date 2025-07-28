<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/auth_helpers.php';

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

            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-1">Manage Users<span class="badge badge-soft-primary ms-2">152</span></h4>
                </div>
                <div class="gap-2 d-flex align-items-center flex-wrap">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="bi bi-download me-2"></i>Export</a>
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
                    <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="bi bi-arrow-clockwise"></i></a>
                    <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="bi bi-chevron-up"></i></a>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="card border-0 rounded-0">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search">
                    </div>
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvas_add"><i class="bi bi-plus-circle me-1"></i>Add User</a>
                </div>
                <div class="card-body">
                    <!-- Table header -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="bi bi-funnel me-2"></i>Filter<i class="bi bi-chevron-down ms-2"></i></a>
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
                                                            <div class="input-icon-start input-icon position-relative">
                                                                <span class="input-icon-addon fs-12">
                                                                    <i class="bi bi-search"></i>
                                                                </span>
                                                                <input type="text" class="form-control form-control-md" placeholder="Search">
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
                                            <a href="javascript:void(0);" class="btn btn-outline-light w-100">Reset</a>
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
                                <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="bi bi-sort-down me-2"></i>Sort By</a>
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
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Created</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                    <th class="text-end no-sort">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample data - replace with actual PHP loop -->
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-avatar">EM</div>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">Elizabeth Morgan</h6>
                                            <small class="text-muted">Software Developer</small>
                                        </div>
                                    </td>
                                    <td>+1 87545 54503</td>
                                    <td>elizabeth@gmail.com</td>
                                    <td>9 Jun 2025</td>
                                    <td>2 hours ago</td>
                                    <td><span class="badge bg-success status-badge">Active</span></td>
                                    <td class="text-end">
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewUserModal"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-avatar">KB</div>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">Katherine Brooks</h6>
                                            <small class="text-muted">Project Manager</small>
                                        </div>
                                    </td>
                                    <td>+1 98975 17485</td>
                                    <td>katherine@gmail.com</td>
                                    <td>8 Jun 2025</td>
                                    <td>1 day ago</td>
                                    <td><span class="badge bg-success status-badge">Active</span></td>
                                    <td class="text-end">
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewUserModal"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="datatable-length"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="datatable-paginate"></div>
                        </div>
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
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required-field">User Name</label>
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
                                        <option value="admin">Administrator</option>
                                        <option value="manager">Manager</option>
                                        <option value="user">User</option>
                                    </select>
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
                                        <option value="usa">USA</option>
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
                    <button type="submit" class="btn btn-primary">Create</button>
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
        });
    </script>
</body>
</html>