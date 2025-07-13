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

// Fetch all permissions, grouped by module
$permissions_by_module = [];
$sql = "SELECT id, display_name, module FROM permissions WHERE deleted_at IS NULL ORDER BY module ASC, display_name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $module = $row['module'] ?: 'Other';
        $permissions_by_module[$module][] = $row;
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
                <div class="accordion mb-3" id="permissionsAccordion">
                    <?php foreach ($permissions_by_module as $module => $perms): ?>
                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header d-flex align-items-center justify-content-between" id="heading-<?php echo htmlspecialchars($module); ?>">
                                <button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo htmlspecialchars($module); ?>" aria-expanded="false" aria-controls="collapse-<?php echo htmlspecialchars($module); ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $module)); ?>
                                </button>
                                <div class="form-check ms-3 me-3">
                                    <input class="form-check-input module-checkbox" type="checkbox" id="module-check-<?php echo htmlspecialchars($module); ?>" data-module="<?php echo htmlspecialchars($module); ?>">
                                    <label class="form-check-label small" for="module-check-<?php echo htmlspecialchars($module); ?>"></label>
                                </div>
                            </h2>
                            <div id="collapse-<?php echo htmlspecialchars($module); ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo htmlspecialchars($module); ?>" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body p-2">
                                    <div class="row">
                                        <?php foreach ($perms as $perm): ?>
                                            <div class="col-12 col-sm-6 col-lg-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input perm-checkbox perm-checkbox-<?php echo htmlspecialchars($module); ?>" type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>" id="perm-<?php echo $perm['id']; ?>" <?php if (in_array($perm['id'], $current_permissions)) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="perm-<?php echo $perm['id']; ?>">
                                                        <?php echo htmlspecialchars($perm['display_name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-success mt-3">Save Permissions</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Module checkbox toggles all permissions in that module
    document.querySelectorAll('.module-checkbox').forEach(function(moduleCheckbox) {
        moduleCheckbox.addEventListener('change', function() {
            var module = this.getAttribute('data-module');
            var checkboxes = document.querySelectorAll('.perm-checkbox-' + CSS.escape(module));
            checkboxes.forEach(function(cb) {
                cb.checked = moduleCheckbox.checked;
            });
        });
        // Set module checkbox checked if all children are checked
        var module = moduleCheckbox.getAttribute('data-module');
        var checkboxes = document.querySelectorAll('.perm-checkbox-' + CSS.escape(module));
        var allChecked = Array.from(checkboxes).every(cb => cb.checked);
        moduleCheckbox.checked = allChecked;
        // Update module checkbox if any child is changed
        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                var allChecked = Array.from(checkboxes).every(cb => cb.checked);
                moduleCheckbox.checked = allChecked;
            });
        });
    });
    // Accordion behavior: only one section open at a time
    document.querySelectorAll('.sidebar-heading[data-bs-toggle="collapse"]').forEach(function(heading) {
        heading.addEventListener('click', function() {
            document.querySelectorAll('.sidebar-heading[data-bs-toggle="collapse"]').forEach(function(other) {
                if (other !== heading) {
                    let target = document.querySelector(other.getAttribute('data-bs-target'));
                    if (target && target.classList.contains('show')) {
                        new bootstrap.Collapse(target, {toggle: true}).hide();
                        other.classList.add('collapsed');
                        // Set arrow to down
                        let arrow = other.querySelector('.collapse-arrow');
                        if (arrow) {
                            arrow.classList.remove('bi-caret-up-fill');
                            arrow.classList.add('bi-caret-down-fill');
                        }
                    }
                }
            });
            heading.classList.toggle('collapsed');
            // Toggle arrow direction
            let arrow = heading.querySelector('.collapse-arrow');
            if (arrow) {
                arrow.classList.toggle('bi-caret-down-fill');
                arrow.classList.toggle('bi-caret-up-fill');
            }
        });
    });
    // Sidebar expand/collapse toggle
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
    }
});
</script>
</body>
</html> 