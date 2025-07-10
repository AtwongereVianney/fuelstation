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
    min-width: 260px;
    max-width: 260px;
    min-height: 100vh;
    background: #343a40;
    color: #fff;
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
    background: #495057;
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
  .sidebar.collapsed {
    max-width: 64px;
    min-width: 64px;
    transition: max-width 0.2s, min-width 0.2s;
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

      <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#userMgmt" aria-expanded="false" aria-controls="userMgmt">
        <i class="bi bi-people"></i><span>User Management</span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
      <div class="collapse" id="userMgmt" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
          <li><a class="nav-link" href="../public/user.php"><i class="bi bi-person"></i><span>Users</span></a></li>
          <li><a class="nav-link" href="../public/manage_roles.php"><i class="bi bi-person-badge"></i><span>Roles</span></a></li>
          <li><a class="nav-link" href="../public/manage_permissions.php"><i class="bi bi-shield-lock"></i><span>Permissions</span></a></li>
          <li><a class="nav-link" href="../public/manage_role_permissions.php"><i class="bi bi-shield-check"></i><span>Role Permissions</span></a></li>
        </ul>
                </div>
      <hr class="sidebar-divider border-secondary m-0">

      <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#businessBranches" aria-expanded="false" aria-controls="businessBranches">
        <i class="bi bi-building"></i><span>Business & Branches</span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
      <div class="collapse" id="businessBranches" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
          <li><a class="nav-link" href="../public/branch_dashboard.php"><i class="bi bi-diagram-3"></i><span>Branches</span></a></li>
        </ul>
                </div>
      <hr class="sidebar-divider border-secondary m-0">

      <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#inventory" aria-expanded="false" aria-controls="inventory">
        <i class="bi bi-box-seam"></i><span>Inventory</span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
      <div class="collapse" id="inventory" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
          <li><a class="nav-link" href="../public/fuel_type_info.php"><i class="bi bi-droplet-half"></i><span>Fuel Types</span></a></li>
        </ul>
                </div>
      <hr class="sidebar-divider border-secondary m-0">

      <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#financial" aria-expanded="false" aria-controls="financial">
        <i class="bi bi-cash-stack"></i><span>Financial</span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
      <div class="collapse" id="financial" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
          <li><a class="nav-link" href="../public/daily_sales_summary.php"><i class="bi bi-graph-up"></i><span>Daily Sales Summary</span></a></li>
          <li><a class="nav-link" href="../public/expenses.php"><i class="bi bi-receipt"></i><span>Expenses</span></a></li>
          <li><a class="nav-link" href="#"><i class="bi bi-cash"></i><span>Cash Float</span></a></li>
          <li><a class="nav-link" href="#"><i class="bi bi-bank"></i><span>Bank Reconciliation</span></a></li>
        </ul>
                </div>
      <hr class="sidebar-divider border-secondary m-0">

      <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#shiftMgmt" aria-expanded="false" aria-controls="shiftMgmt">
        <i class="bi bi-clock-history"></i><span>Shift Management</span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
      <div class="collapse" id="shiftMgmt" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
          <li><a class="nav-link" href="../public/shifts.php"><i class="bi bi-clock"></i><span>Shifts</span></a></li>
          <li><a class="nav-link" href="../public/shift_assignments.php"><i class="bi bi-person-lines-fill"></i><span>Shift Assignments</span></a></li>
        </ul>
                </div>
      <hr class="sidebar-divider border-secondary m-0">

      <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#maintenance" aria-expanded="false" aria-controls="maintenance">
        <i class="bi bi-tools"></i><span>Maintenance & Compliance</span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
      <div class="collapse" id="maintenance" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
          <li><a class="nav-link" href="#"><i class="bi bi-gear"></i><span>Equipment Maintenance</span></a></li>
          <li><a class="nav-link" href="#"><i class="bi bi-clipboard-check"></i><span>Regulatory Compliance</span></a></li>
          <li><a class="nav-link" href="#"><i class="bi bi-droplet"></i><span>Fuel Quality Tests</span></a></li>
          <li><a class="nav-link" href="#"><i class="bi bi-exclamation-triangle"></i><span>Safety Incidents</span></a></li>
        </ul>
                </div>
      <hr class="sidebar-divider border-secondary m-0">

      <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#reporting" aria-expanded="false" aria-controls="reporting">
        <i class="bi bi-bar-chart"></i><span>Reporting & Analytics</span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
      <div class="collapse" id="reporting" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
          <li><a class="nav-link" href="#"><i class="bi bi-file-earmark-bar-graph"></i><span>Reports</span></a></li>
          <li><a class="nav-link" href="#"><i class="bi bi-credit-card"></i><span>Outstanding Credit</span></a></li>
        </ul>
                </div>
      <hr class="sidebar-divider border-secondary m-0">

      <div class="sidebar-heading collapsed" data-bs-toggle="collapse" data-bs-target="#system" aria-expanded="false" aria-controls="system">
        <i class="bi bi-gear-wide-connected"></i><span>System</span>
        <span class="collapse-arrow bi bi-caret-down-fill"></span>
                    </div>
      <div class="collapse" id="system" data-bs-parent=".sidebar">
        <ul class="nav flex-column mb-2">
          <li><a class="nav-link" href="#"><i class="bi bi-sliders"></i><span>System Settings</span></a></li>
          <li><a class="nav-link" href="#"><i class="bi bi-journal-text"></i><span>Audit Logs</span></a></li>
          <li><a class="nav-link" href="../public/notifications.php"><i class="bi bi-bell"></i><span>Notifications</span></a></li>
        </ul>
      </div>
      <hr class="sidebar-divider border-secondary m-0">
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
    document.querySelector('.sidebar').classList.toggle('collapsed');
  });
</script>