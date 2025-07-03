<?php
session_start();
require_once 'db_connect.php';
$errors = [];
$success = '';
$token = $_GET['token'] ?? '';
if (!$token) {
    $errors[] = 'Invalid or missing token.';
} else {
    $sql = "SELECT user_id, expires_at FROM password_resets WHERE token = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$row || strtotime($row['expires_at']) < time()) {
        $errors[] = 'This reset link is invalid or has expired.';
    } else {
        $user_id = $row['user_id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'si', $hashed, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Password reset successful! <a href=\'login.php\'>Login here</a>.';
                    // Delete the token
                    $sql = "DELETE FROM password_resets WHERE token=?";
                    $stmt2 = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt2, 's', $token);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                } else {
                    $errors[] = 'Failed to reset password.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">Reset Password</div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error) echo '<div>' . $error . '</div>'; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php elseif (empty($errors)): ?>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 