<?php
session_start();
require_once '../includes/auth_helpers.php';
require_once '../config/db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch user info
$sql = "SELECT username, email, first_name, last_name, phone, profile_photo FROM users WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Add a safe helper for htmlspecialchars
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }

// Handle profile update
if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $profile_photo = $user['profile_photo'];
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $errors[] = 'First name, last name, and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } else {
        // Check for email conflict
        $sql = "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Email already in use.';
        }
        mysqli_stmt_close($stmt);
    }
    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image type. Only JPG, PNG, GIF allowed.';
        } elseif ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image size must be less than 2MB.';
        } else {
            $upload_dir = __DIR__ . '/../public/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                $profile_photo = 'uploads/' . $new_name;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }
    if (empty($errors)) {
        $sql = "UPDATE users SET first_name=?, last_name=?, phone=?, email=?, profile_photo=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssssi', $first_name, $last_name, $phone, $email, $profile_photo, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Profile updated successfully.';
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['phone'] = $phone;
            $user['email'] = $email;
            $user['profile_photo'] = $profile_photo;
        } else {
            $errors[] = 'Failed to update profile.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    // Fetch current hash
    $sql = "SELECT password FROM users WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$row || !password_verify($current_password, $row['password'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new_password) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'New passwords do not match.';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $hashed, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Password changed successfully.';
        } else {
            $errors[] = 'Failed to change password.';
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
    <title>Profile - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <?php include '../includes/header.php'; ?>
        <!-- Mobile menu button -->
        <div class="d-md-none mb-3">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                <i class="bi bi-list"></i> Menu
            </button>
        </div>
        <h2 class="mb-4">My Profile</h2>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white text-center">Profile Information</div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($errors as $error) echo '<div>' . $error . '</div>'; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="mb-3 text-center">
                                    <?php if (!empty($user['profile_photo'])): ?>
                                        <img src="<?php echo h($user['profile_photo']); ?>" alt="Profile Photo" class="rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover; border: 2px solid #ccc;">
                                    <?php else: ?>
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'] . ' ' . $user['last_name']); ?>&background=6c757d&color=fff&size=120" alt="Profile Photo" class="rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover; border: 2px solid #ccc;">
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_photo" class="form-label">Profile Photo (JPG, PNG, GIF, max 2MB)</label>
                                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo h($user['username']); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo h($user['first_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo h($user['last_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo h($user['phone']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo h($user['email']); ?>" required>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-info">Update Profile</button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">Change Password</div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 