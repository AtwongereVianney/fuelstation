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

<style>
.main-header {
    position: fixed;
    top: 0;
    left: 260px;
    width: calc(100vw - 260px);
    z-index: 1040; /* Increased for safety */
    transition: left 0.2s, width 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    background: #fff;
}

.main-header.collapsed {
    left: 64px;
    width: calc(100vw - 64px);
}

/* Ensure content doesn't overlap with fixed header and add responsive spacing */
.main-content {
    margin-top: calc(70px + 2rem); /* Dynamic spacing: header height + responsive gap */
    transition: margin-top 0.2s;
}

/* Responsive spacing adjustments */
@media (max-width: 768px) {
    .main-header {
        left: 0;
        width: 100vw;
    }
    
    .main-header.collapsed {
        left: 0;
        width: 100vw;
    }
    
    .main-content {
        margin-top: calc(70px + 1.5rem); /* Slightly less spacing on mobile */
    }
}

@media (max-width: 576px) {
    .main-content {
        margin-top: calc(70px + 1rem); /* Even less spacing on smaller screens */
    }
}

@media (min-width: 1200px) {
    .main-content {
        margin-top: calc(70px + 2.5rem); /* More spacing on larger screens */
    }
}

@media (max-width: 991.98px) {
    .main-header,
    .main-header.collapsed {
        left: 0 !important;
        width: 100vw !important;
        z-index: 1040;
    }
}
@media (max-width: 991.98px) {
    #headerToggle {
        display: inline-flex;
        align-items: center;
        margin-bottom: 0.25rem;
    }
}
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom main-header" >
  <div class="container-fluid position-relative">

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="..//public/dashboard.php"><i class="bi bi-house"></i> Home</a>
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
            <li><a class="dropdown-item text-danger" href="../public/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Handle search input focus
    var searchInput = document.querySelector('form[role="search"] input[name="q"]');
    if (searchInput) {
      searchInput.focus();
      // Move cursor to end
      var val = searchInput.value;
      searchInput.value = '';
      searchInput.value = val;
    }

    // Sync header with sidebar toggle state
    function syncHeaderWithSidebar() {
      var sidebar = document.querySelector('.sidebar');
      var header = document.querySelector('.main-header');
      
      if (sidebar && header) {
        if (sidebar.classList.contains('collapsed')) {
          header.classList.add('collapsed');
        } else {
          header.classList.remove('collapsed');
        }
      }
    }

    // Initial sync
    syncHeaderWithSidebar();

    // Listen for sidebar toggle events
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function() {
        // Small delay to allow sidebar animation to start
        setTimeout(syncHeaderWithSidebar, 10);
      });
    }

    // Fallback: periodically check sidebar state
    setInterval(syncHeaderWithSidebar, 500);
  });
</script>