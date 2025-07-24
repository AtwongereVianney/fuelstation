<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/auth_helpers.php';

// Fetch all branches for dropdown
$branches = [];
$branch_sql = "SELECT id, branch_name FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$branch_result = mysqli_query($conn, $branch_sql);
if ($branch_result) {
    while ($row = mysqli_fetch_assoc($branch_result)) {
        $branches[] = $row;
    }
}

// Notification types
$notification_types = [
    '' => 'All',
    'info' => 'Info',
    'warning' => 'Warning',
    'error' => 'Error',
    'success' => 'Success'
];

// Get filters (for form default values)
$selected_branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : '';
$selected_type = isset($_GET['notification_type']) ? $_GET['notification_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Helper for safe output
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
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
                    <i class="fas fa-bars"></i> Menu
                </button>
            </div>
            <h2 class="mb-4">Notifications</h2>
            <form method="get" class="mb-4" id="filterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="branch_id" class="form-label">Branch:</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php if ($b['id'] == $selected_branch_id) echo 'selected'; ?>><?php echo h($b['branch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="notification_type" class="form-label">Type:</label>
                        <select name="notification_type" id="notification_type" class="form-select">
                            <?php foreach ($notification_types as $val => $label): ?>
                                <option value="<?php echo h($val); ?>" <?php if ($val === $selected_type) echo 'selected'; ?>><?php echo h($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo h($start_date); ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo h($end_date); ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
            <div class="mb-4" id="notifications-summary"></div>
            <h5 class="mb-3">Notifications</h5>
            <div id="notifications-table"></div>
            <nav>
                <ul class="pagination" id="pagination"></ul>
            </nav>
            <div class="d-block d-md-none small text-muted mt-2">Swipe left/right to see more columns.</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const summaryDiv = document.getElementById('notifications-summary');
const tableDiv = document.getElementById('notifications-table');
const paginationUl = document.getElementById('pagination');
const filterForm = document.getElementById('filterForm');
let currentPage = 1;
let perPage = 20;

function getFilters() {
    const params = new URLSearchParams(new FormData(filterForm));
    params.set('page', currentPage);
    params.set('per_page', perPage);
    return params;
}

function fetchNotifications() {
    const params = getFilters();
    fetch('notifications_data.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            renderSummary(data.summary);
            renderTable(data.notifications);
            renderPagination(data.pagination);
        });
}

function renderSummary(summary) {
    let html = `<div class="card"><div class="card-body">
        <h5 class="card-title mb-3">Notifications Summary</h5>
        <div class="row g-3">
            <div class="col-12 col-md-3"><strong>Total:</strong> ${summary.total}</div>
            <div class="col-12 col-md-3"><strong>By Type:</strong><ul class="mb-0">`;
    if (Object.keys(summary.by_type).length) {
        for (const [type, count] of Object.entries(summary.by_type)) {
            html += `<li>${type.charAt(0).toUpperCase() + type.slice(1)}: ${count}</li>`;
        }
    } else {
        html += '<li>None</li>';
    }
    html += `</ul></div><div class="col-12 col-md-3"><strong>By Status:</strong><ul class="mb-0">`;
    if (Object.keys(summary.by_status).length) {
        for (const [status, count] of Object.entries(summary.by_status)) {
            html += `<li>${status}: ${count}</li>`;
        }
    } else {
        html += '<li>None</li>';
    }
    html += '</ul></div></div></div></div>';
    summaryDiv.innerHTML = html;
}

function renderTable(notifications) {
    if (!notifications.length) {
        tableDiv.innerHTML = '<div class="alert alert-info">No notifications found for the selected filters.</div>';
        return;
    }
    let html = `<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead class="table-light"><tr>
        <th>Date</th><th>Type</th><th>Title</th><th>Message</th><th>User</th><th>Branch</th><th>Status</th><th>Action URL</th><th>Action</th></tr></thead><tbody>`;
    for (const n of notifications) {
        html += `<tr>
            <td>${escapeHtml(n.created_at)}</td>
            <td>${escapeHtml(capitalize(n.notification_type))}</td>
            <td>${escapeHtml(n.title)}</td>
            <td>${escapeHtml(n.message)}</td>
            <td>${escapeHtml((n.first_name || '') + ' ' + (n.last_name || ''))}</td>
            <td>${escapeHtml(n.branch_name)}</td>
            <td>${n.is_read ? '<span class="badge bg-success">Read</span>' : '<span class="badge bg-warning text-dark">Unread</span>'}</td>
            <td>${n.action_url ? `<a href="${escapeHtml(n.action_url)}" target="_blank">Link</a>` : ''}</td>
            <td>${!n.is_read ? `<button class="btn btn-sm btn-success mark-read-btn" data-id="${n.id}">Mark as Read</button>` : ''}</td>
        </tr>`;
    }
    html += '</tbody></table></div>';
    tableDiv.innerHTML = html;
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            markAsRead(this.dataset.id);
        });
    });
}

function renderPagination(pagination) {
    let html = '';
    for (let i = 1; i <= pagination.total_pages; i++) {
        html += `<li class="page-item${i === pagination.page ? ' active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    paginationUl.innerHTML = html;
    document.querySelectorAll('#pagination .page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            currentPage = parseInt(this.dataset.page);
            fetchNotifications();
        });
    });
}

function markAsRead(id) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'notification_id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) fetchNotifications();
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function(m) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]);
    });
}
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

filterForm.addEventListener('submit', function(e) {
    e.preventDefault();
    currentPage = 1;
    fetchNotifications();
});

document.addEventListener('DOMContentLoaded', function() {
    fetchNotifications();
});
</script>
</body>
</html> 