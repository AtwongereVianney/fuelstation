<?php
require_once '../config/db_connect.php';

function has_permission($permission_name) {
    global $conn;
    if (!isset($_SESSION['user_id'])) return false;
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT 1 FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            JOIN role_permissions rp ON r.id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? AND p.name = ? AND (ur.deleted_at IS NULL AND r.deleted_at IS NULL AND p.deleted_at IS NULL) LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $user_id, $permission_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $has_perm = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $has_perm;
}
?> 