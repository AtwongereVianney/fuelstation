<?php
session_start();
require_once '../config/db_connect.php';

// Handle form submission
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name)) {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }


    // Check for existing username/email
    $sql = "SELECT id FROM users WHERE username=? OR email=? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = 'Username or email already exists.';
    }
    mysqli_stmt_close($stmt);

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $status = 'active';
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssssss', $username, $email, $hashed_password, $first_name, $last_name, $phone, $status);
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            // Assign default role (fuel_attendant, id=5)
            $role_id = 5;
            $assigned_by = $user_id;
            $sql_role = "INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)";
            $stmt_role = mysqli_prepare($conn, $sql_role);
            mysqli_stmt_bind_param($stmt_role, 'iii', $user_id, $role_id, $assigned_by);
            mysqli_stmt_execute($stmt_role);
            mysqli_stmt_close($stmt_role);
            $success = 'Registration successful! You can now <a href=\'login.php\'>login</a>.';
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">Register</div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error) echo '<div>' . $error . '</div>'; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    <div class="mt-3 text-center">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 