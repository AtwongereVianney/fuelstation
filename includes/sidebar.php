<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/auth_helpers.php';
$sidebar_branches = [];
$sidebar_branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$sidebar_branch_result = mysqli_query($conn, $sidebar_branch_sql);
if ($sidebar_branch_result) {
    while ($row = mysqli_fetch_assoc($sidebar_branch_result)) {
        $sidebar_branches[] = $row;
    }
}
$sidebarModules = get_accessible_sidebar_modules();
?>
<!--
  Sidebar Component for Petrol Station
  -----------------------------------
  Usage:
  1. Place this file's content where you want the sidebar to appear (e.g., in a layout or as an include/partial).
  2. Ensure Bootstrap 5 CSS, Bootstrap Icons, and Bootstrap JS are loaded on your page.
  3. Place your main content in a sibling div with class 'flex-grow-1 p-4' for proper layout.
-->

<!-- Petrol Station Sidebar (Self-contained) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    min-width: 260px;
    max-width: 260px;
    height: 100vh;
    background: #343a40;
    color: #fff;
    z-index: 1040;
    overflow-y: auto;
    transition: max-width 0.2s, min-width 0.2s;
}
.sidebar.collapsed {
    max-width: 64px;
    min-width: 64px;
}
.main-content {
    position: fixed;
    top: 0;
    left: 260px;
    width: calc(100vw - 260px);
    height: 100vh;
    overflow-y: auto;
    margin-left: 0;
    padding-left: 0;
    padding-top: 0;
    transition: left 0.2s, width 0.2s;
}
.main-content.expanded {
    left: 260px;
    width: calc(100vw - 260px);
}
.main-content.collapsed {
    left: 64px;
    width: calc(100vw - 64px);
  }
  .sidebar .nav-link {
    color: #adb5bd;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75em;
  }
  .sidebar .nav-link.active, .sidebar .nav-link:hover {
    color: #fff;
    background:rgb(87, 79, 73);
  }
  .sidebar .sidebar-heading {
    padding: 1rem 1.5rem 0.5rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    color: #ced4da;
    letter-spacing: 0.05em;
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    gap: 0.75em;
  }
  .sidebar .logout {
    position: absolute;
    bottom: 0;
    width: 100%;
  }
  .sidebar .collapse .nav-link {
    padding-left: 2.5rem;
}
  .sidebar .sidebar-heading .collapse-arrow {
    margin-left: auto;
    font-size: 1em;
    transition: transform 0.2s;
  }
  .sidebar .bi {
    font-size: 1.1em;
  }
  .sidebar-divider {
    border-top-width: 4px !important;
    opacity: 1;
  }
  .sidebar.collapsed .sidebar-heading span {
    display: none !important;
  }
  .sidebar.collapsed .nav-link span {
    display: none !important;
  }
  .sidebar.collapsed .collapse-arrow {
    display: none !important;
  }
  .sidebar.collapsed .sidebar-heading {
    justify-content: center;
  }
  .sidebar.collapsed .nav-link {
    justify-content: center;
  }
  .sidebar.collapsed .collapse,
  .sidebar.collapsed .collapsing {
    display: none !important;
}
</style>

<div id="sidebar-wrapper" class="d-flex">
  <nav class="sidebar d-flex flex-column position-relative">
    <!-- Petrol Station Brand/Header -->
    <div class="text-center py-3 border-bottom border-secondary bg-dark">
      <i class="bi bi-fuel-pump-fill" style="font-size:2rem;"></i>
      <div class="fw-bold mt-1" style="font-size:1.1rem; letter-spacing:0.05em;">Petrol Station</div>
    </div>
    <div class="flex-grow-1 d-flex flex-column">
      <div class="sidebar-heading d-flex align-items-center">
        <i class="bi bi-speedometer2 me-2"></i>
        <a href="../public/dashboard.php" class="text-white text-decoration-none flex-grow-1">Dashboard</a>
        <button id="sidebarToggle" class="btn btn-outline-secondary btn-sm ms-2 py-0 px-2 d-inline-flex align-items-center" style="line-height:1.1; height:1.8em;">
            <i class="bi bi-list"></i>
        </button>
                </div>
      <hr class="sidebar-divider border-secondary m-0">

      <?php $collapseId = 0; ?>
      <?php foreach ($sidebarModules as $module): ?>
        <?php $collapseId++; $collapseTarget = 'collapse' . $collapseId; ?>
        <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseTarget; ?>" aria-expanded="false" aria-controls="<?php echo $collapseTarget; ?>">
          <i class="bi <?php echo htmlspecialchars($module['icon']); ?>"></i><span><?php echo htmlspecialchars($module['section']); ?></span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
        <div class="collapse" id="<?php echo $collapseTarget; ?>" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
            <?php if ($module['section'] === 'Business & Branches'): ?>
          <?php
            $is_super_admin = false;
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $super_sql = "SELECT 1 FROM user_roles WHERE user_id = $user_id AND role_id = 1 AND deleted_at IS NULL LIMIT 1";
                $super_result = mysqli_query($conn, $super_sql);
                $is_super_admin = mysqli_num_rows($super_result) > 0;
            }
            if ($is_super_admin) {
                // Fetch all businesses and their branches
                $sidebar_businesses = [];
                $biz_res = mysqli_query($conn, "SELECT id, business_name FROM businesses WHERE deleted_at IS NULL ORDER BY business_name");
                while ($biz_row = mysqli_fetch_assoc($biz_res)) {
                    $sidebar_businesses[] = $biz_row;
                }
                $branches_by_business = [];
                $branch_res = mysqli_query($conn, "SELECT id, branch_name, business_id FROM branches WHERE deleted_at IS NULL ORDER BY branch_name");
                while ($branch_row = mysqli_fetch_assoc($branch_res)) {
                    $branches_by_business[$branch_row['business_id']][] = $branch_row;
                }
                echo '<li><a class="nav-link" href="../public/manage_businesses.php"><i class="bi bi-diagram-3"></i><span>Manage Businesses</span></a></li>';
                foreach ($sidebar_businesses as $biz) {
                    $collapseId = 'bizBranches' . $biz['id'];
                    echo '<li>';
                    echo '<a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#' . $collapseId . '" role="button" aria-expanded="false" aria-controls="' . $collapseId . '"><span><i class="bi bi-building"></i> ' . htmlspecialchars($biz['business_name']) . '</span><i class="bi bi-caret-down-fill small"></i></a>';
                    echo '<ul class="collapse list-unstyled ps-3" id="' . $collapseId . '">';
                    if (!empty($branches_by_business[$biz['id']])) {
                        foreach ($branches_by_business[$biz['id']] as $branch) {
                            echo '<li><a class="nav-link" href="../public/branch_dashboard.php?branch_id=' . $branch['id'] . '"><i class="bi bi-chevron-right"></i><span>' . htmlspecialchars($branch['branch_name']) . '</span></a></li>';
                        }
                    } else {
                        echo '<li><span class="nav-link text-muted">No branches</span></li>';
                    }
                    echo '</ul>';
                    echo '</li>';
                }
            } else if (isset($_SESSION['business_id'], $_SESSION['branch_id'])) {
                $user_business_id = $_SESSION['business_id'];
                $user_branch_id = $_SESSION['branch_id'];
                // Query the branch with both business_id and branch_id
                $branch_sql = "SELECT id, branch_name FROM branches WHERE id = $user_branch_id AND business_id = $user_business_id AND deleted_at IS NULL LIMIT 1";
                $branch_result = mysqli_query($conn, $branch_sql);
                if ($branch_result && $branch = mysqli_fetch_assoc($branch_result)) {
                    echo '<li><a class="nav-link" href="../public/branch_dashboard.php?branch_id=' . $branch['id'] . '"><i class="bi bi-building"></i><span>' . htmlspecialchars($branch['branch_name']) . '</span></a></li>';
                }
            }
          ?>
            <?php else: ?>
              <?php foreach ($module['links'] as $link): ?>
                <li><a class="nav-link" href="<?php echo htmlspecialchars($link['url']); ?>">
                  <i class="bi <?php echo htmlspecialchars($link['icon']); ?>"></i><span><?php echo htmlspecialchars($link['label']); ?></span></a></li>
          <?php endforeach; ?>
            <?php endif; ?>
        </ul>
                </div>
      <hr class="sidebar-divider border-secondary m-0">
      <?php endforeach; ?>
    </div>
    <div class="flex-shrink-0 mt-auto mb-2">
      <ul class="nav flex-column mb-2">
        <li><a class="nav-link text-danger" href="../public/logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
      </ul>
    </div>
</nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Accordion behavior: only one section open at a time
  document.querySelectorAll('.sidebar-heading[data-bs-toggle="collapse"]').forEach(function(heading) {
    heading.addEventListener('click', function() {
      document.querySelectorAll('.sidebar-heading[data-bs-toggle="collapse"]').forEach(function(other) {
        if (other !== heading) {
          let target = document.querySelector(other.getAttribute('data-bs-target'));
          if (target.classList.contains('show')) {
            new bootstrap.Collapse(target, {toggle: true}).hide();
            other.classList.add('collapsed');
            // Set arrow to down
            let arrow = other.querySelector('.collapse-arrow');
            if (arrow) {
              arrow.classList.remove('bi-caret-up-fill');
              arrow.classList.add('bi-caret-down-fill');
            }
          }
        }
      });
      heading.classList.toggle('collapsed');
      // Toggle arrow direction
      let arrow = heading.querySelector('.collapse-arrow');
      if (arrow) {
        arrow.classList.toggle('bi-caret-down-fill');
        arrow.classList.toggle('bi-caret-up-fill');
            }
        });
    });
    
  // Sidebar expand/collapse toggle
  document.getElementById('sidebarToggle').addEventListener('click', function(e) {
    e.stopPropagation();
    var sidebar = document.querySelector('.sidebar');
    var mainContent = document.querySelector('.main-content');
    sidebar.classList.toggle('collapsed');
    if (sidebar.classList.contains('collapsed')) {
      mainContent.classList.remove('expanded');
      mainContent.classList.add('collapsed');
    } else {
      mainContent.classList.remove('collapsed');
      mainContent.classList.add('expanded');
    }
  });
  // On page load, set main-content initial state
  window.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.sidebar');
    var mainContent = document.querySelector('.main-content');
    if (sidebar.classList.contains('collapsed')) {
      mainContent.classList.add('collapsed');
    } else {
      mainContent.classList.add('expanded');
    }
  });
</script>