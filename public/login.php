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
    <title>FuelMaster | Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-image: url('../images/image.png'); /* Add your image path here */
            background-size: cover; /* Ensures the image covers the whole page */
            background-position: center; /* Centers the image */
            background-repeat: no-repeat; /* Prevents the image from repeating */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .login-container h2 {
            margin-bottom: 20px;
        }
        .login-container .form-group {
            margin-bottom: 15px;
        }
        .login-container .btn {
            width: 100%;
        }
        .login-container .error {
            color: red;
            margin-bottom: 15px;
        }
        .login-container .register-link {
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center">Login</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" style="background-color: #213053; color: white;" class="btn">Login</button>
        </form>
        <div class="register-link">
            <p>Forgot Password? <a href="#">Recover your password</a></p>
        </div>
        <div class="text-center mt-3">
            <a href="../index.php" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>