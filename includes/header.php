<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth_helpers.php';

$user_name = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role_display = isset($_SESSION['role_display_name']) ? $_SESSION['role_display_name'] : (isset($_SESSION['role_name']) ? $_SESSION['role_name'] : '');

// Check if super admin
$is_super_admin = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $super_sql = "SELECT 1 FROM user_roles WHERE user_id = $user_id AND role_id = 1 AND deleted_at IS NULL LIMIT 1";
    $super_result = mysqli_query($conn, $super_sql);
    $is_super_admin = mysqli_num_rows($super_result) > 0;
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom mb-3">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="/public/dashboard.php"><i class="bi bi-house"></i> Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../public/profile.php"><i class="bi bi-person"></i> My Profile</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#"><i class="bi bi-question-circle"></i> Help</a>
        </li>
        <?php if ($is_super_admin): ?>
        <li class="nav-item">
          <a class="nav-link text-danger" href="/public/manage_roles.php"><i class="bi bi-shield-lock"></i> Admin Panel</a>
        </li>
        <?php endif; ?>
      </ul>
      <form class="d-flex me-3" role="search" method="get" action="../public/search.php">
        <input class="form-control me-2" type="search" name="q" placeholder="Search..." aria-label="Search" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : (isset($_REQUEST['q']) ? htmlspecialchars($_REQUEST['q']) : ''); ?>">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
      </form>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user_name); ?> <span class="text-muted small">(<?php echo htmlspecialchars($role_display); ?>)</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="../public/profile.php"><i class="bi bi-person"></i> My Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/public/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.querySelector('form[role="search"] input[name="q"]');
    if (searchInput) {
      searchInput.focus();
      // Move cursor to end
      var val = searchInput.value;
      searchInput.value = '';
      searchInput.value = val;
    }
  });
</script> 