<?php
session_start();
require_once '../config/db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role_name'] ?? '';
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
                    <span class="navbar-text">Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)</span>
                </div>
            </nav>
            <div class="card">
                <div class="card-header bg-success text-white">Dashboard</div>
                <div class="card-body">
                    <?php if ($role === 'super_admin'): ?>
                        <h4>Super Admin Dashboard</h4>
                        <p>You have full access to the system.</p>
                    <?php else: ?>
                        <h4><?php echo ucfirst(str_replace('_', ' ', $role)); ?> Dashboard</h4>
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