<?php
session_start();
include '../config/db_connect.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $errors[] = 'Username and password are required.';
    } else {
        $sql = "SELECT u.id, u.username, u.password, u.status, u.business_id, u.branch_id, ur.role_id, r.name as role_name, r.display_name as role_display_name FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.username = ? OR u.email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
            // Set session variables with proper type handling
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['business_id'] = $user['business_id'];
            $_SESSION['branch_id'] = $user['branch_id'];
            
            // Ensure role_name is always a string, never null or array
            $role_name = $user['role_name'] ?? 'guest';
            $role_display_name = $user['role_display_name'] ?? $role_name;
            
            // Store role as string, not array
            $_SESSION['role_name'] = (string)$role_name;
            $_SESSION['role_display_name'] = (string)$role_display_name;
            
            // Fetch all permissions for this user
            $permissions = [];
            $perm_sql = "SELECT p.name FROM permissions p
                        JOIN role_permissions rp ON rp.permission_id = p.id
                        JOIN user_roles ur ON ur.role_id = rp.role_id
                        WHERE ur.user_id = ? AND p.deleted_at IS NULL";
            $perm_stmt = mysqli_prepare($conn, $perm_sql);
            mysqli_stmt_bind_param($perm_stmt, 'i', $user['id']);
            mysqli_stmt_execute($perm_stmt);
            $perm_result = mysqli_stmt_get_result($perm_stmt);
            while ($perm_row = mysqli_fetch_assoc($perm_result)) {
                $permissions[] = $perm_row['name'];
            }
            mysqli_stmt_close($perm_stmt);
            $_SESSION['permissions'] = $permissions;
            
            // Redirect based on role
            if ($role_name === 'super_admin') {
                header('Location: dashboard.php?role=super_admin');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $errors[] = 'Invalid credentials or inactive account.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">Uganda Fuel Station Login</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error) echo '<div>' . htmlspecialchars($error) . '</div>'; ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="" autocomplete="on">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="forgot_password.php">Forgot Password?</a>
                        </div>
                        <div class="mt-2 text-center">
                            <span>Don't have an account? <a href="register.php">Register</a></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>