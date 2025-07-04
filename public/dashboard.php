<?php
session_start();
require_once '../config/db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle role properly - ensure it's always a string
$role_data = $_SESSION['role_name'] ?? 'guest';

// Check if role_data is an array and extract the appropriate value
if (is_array($role_data)) {
    $role = $role_data['display_name'] ?? $role_data['name'] ?? 'guest';
} else {
    $role = $role_data;
}

// Ensure role is always a string
$role = (string)$role;

// For display purposes, use role_display_name if available
$role_display = $_SESSION['role_display_name'] ?? $role;
if (is_array($role_display)) {
    $role_display = $role_display['display_name'] ?? $role_display['name'] ?? $role;
}
$role_display = (string)$role_display;

$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 rounded">
                <div class="container-fluid">
                    <span class="navbar-text">Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role_display); ?>)</span>
                </div>
            </nav>
            <div class="card">
                <div class="card-header bg-success text-white">Dashboard</div>
                <div class="card-body">
                    <?php if ($role === 'super_admin'): ?>
                        <h4>Super Admin Dashboard</h4>
                        <p>You have full access to the system.</p>
                    <?php else: ?>
                        <h4><?php //echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role))); ?> Dashboard</h4>
                        <p>Welcome to your dashboard. Your access is based on your role and permissions.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>