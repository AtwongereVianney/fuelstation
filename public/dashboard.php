<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/auth_helpers.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role_name'] ?? '';
$username = $_SESSION['username'] ?? '';
$role_display = $_SESSION['role_display_name'] ?? $role;

// Fetch user permissions
$user_permissions = [];
$sql = "SELECT p.name FROM permissions p
        JOIN role_permissions rp ON rp.permission_id = p.id
        JOIN user_roles ur ON ur.role_id = rp.role_id
        WHERE ur.user_id = ? AND p.deleted_at IS NULL";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $user_permissions[] = $row['name'];
}
mysqli_stmt_close($stmt);

$business_id = $_SESSION['business_id'] ?? null;
$current_year = date('Y');
$income_data = $expense_data = $service_demand_data = $months = [];
if ($role === 'super_admin' && $business_id) {
    // Prepare months
    for ($m = 1; $m <= 12; $m++) {
        $months[] = date('M', mktime(0,0,0,$m,1));
        $income_data[] = 0;
        $expense_data[] = 0;
        $service_demand_data[] = 0;
    }
    // Income (sales)
    $sql = "SELECT MONTH(transaction_date) as m, SUM(final_amount) as total FROM sales_transactions WHERE deleted_at IS NULL AND YEAR(transaction_date) = $current_year AND branch_id IN (SELECT id FROM branches WHERE business_id = $business_id AND deleted_at IS NULL) GROUP BY m";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $income_data[(int)$row['m']-1] = (float)$row['total'];
    }
    // Expenses
    $sql = "SELECT MONTH(expense_date) as m, SUM(amount) as total FROM expenses WHERE deleted_at IS NULL AND YEAR(expense_date) = $current_year AND branch_id IN (SELECT id FROM branches WHERE business_id = $business_id AND deleted_at IS NULL) GROUP BY m";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $expense_data[(int)$row['m']-1] = (float)$row['total'];
    }
    // Service demand: count of sales transactions
    $sql = "SELECT MONTH(transaction_date) as m, COUNT(*) as cnt FROM sales_transactions WHERE deleted_at IS NULL AND YEAR(transaction_date) = $current_year AND branch_id IN (SELECT id FROM branches WHERE business_id = $business_id AND deleted_at IS NULL) GROUP BY m";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $service_demand_data[(int)$row['m']-1] = (int)$row['cnt'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Core Layout */
        html, body { height: 100%; margin: 0; padding: 0; }
        .main-flex-container { display: flex; height: 100vh; overflow: hidden; }
        .sidebar-fixed { 
            width: 240px; 
            min-width: 200px; 
            max-width: 300px; 
            height: 100vh; 
            position: sticky; 
            top: 0; 
            left: 0; 
            z-index: 1020; 
            background: #f8f9fa; 
            border-right: 1px solid #dee2e6; 
        }
        .main-content-scroll { 
            flex: 1 1 0%; 
            height: 100vh; 
            overflow-y: auto; 
            background: #fff; 
        }

        /* Sticky Header within Main Content */
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 0;
        }

        .content-wrapper {
            padding: 2rem 1.5rem;
        }

        /* Card Enhancements */
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        /* Button Enhancements */
        .btn {
            transition: all 0.2s ease-in-out;
            border-radius: 8px;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }

        .btn.w-100.py-3 {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 80px;
        }

        .btn.w-100.py-3 i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* Responsive Typography */
        .responsive-title {
            font-size: clamp(1.25rem, 4vw, 2rem);
        }
        
        .responsive-text {
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        
        /* Mobile Optimizations */
        @media (max-width: 767.98px) { 
            .main-flex-container { 
                display: block; 
                height: auto; 
            } 
            .sidebar-fixed { 
                display: none; 
            } 
            .main-content-scroll { 
                height: auto; 
            }
            
            .content-wrapper {
                padding: 1rem 0.75rem;
            }
            
            .sticky-header {
                margin: 0 -0.75rem 1rem -0.75rem;
            }
            
            .card-body {
                padding: 1rem 0.75rem;
            }
            
            .btn.w-100.py-3 {
                padding: 1rem 0.5rem !important;
                font-size: 0.875rem;
                min-height: 60px;
            }
            
            .btn.w-100.py-3 i {
                font-size: 1.5rem !important;
                margin-bottom: 0.25rem;
            }
            
            .display-6 {
                font-size: 1.5rem !important;
            }
            
            .card-title {
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }
            
            .g-3 > .col-6 {
                padding-right: 0.25rem !important;
                padding-left: 0.25rem !important;
            }
            
            .card-header {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .card-body canvas {
                max-height: 250px;
            }
        }
        
        /* Tablet Optimizations */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .content-wrapper {
                padding: 1.5rem 1rem;
            }
            
            .sticky-header {
                margin: 0 -1rem 1.5rem -1rem;
            }
            
            .btn.w-100.py-3 {
                padding: 1.25rem 0.75rem !important;
            }
            
            .btn.w-100.py-3 i {
                font-size: 2rem !important;
            }
        }

        /* Large Desktop */
        @media (min-width: 992px) {
            .sticky-header {
                margin: 0 -1.5rem 2rem -1.5rem;
            }
        }
        
        /* Small Mobile Optimizations */
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 0.75rem 0.5rem;
            }
            
            .sticky-header {
                margin: 0 -0.5rem 1rem -0.5rem;
            }
            
            .card-body {
                padding: 0.75rem 0.5rem;
            }
            
            .btn.w-100.py-3 {
                padding: 0.75rem 0.25rem !important;
                font-size: 0.8rem;
                min-height: 50px;
            }
            
            .btn.w-100.py-3 i {
                font-size: 1.25rem !important;
            }
            
            .btn.btn-outline-primary {
                width: 100%;
                margin-bottom: 1rem;
                padding: 0.75rem;
            }
            
            .display-6 {
                font-size: 1.25rem !important;
            }
            
            .card-title {
                font-size: 0.8rem;
            }
            
            .col-6.col-md-3 {
                padding-right: 0.125rem !important;
                padding-left: 0.125rem !important;
            }
            
            .card-body canvas {
                max-height: 200px;
            }
            
            .card-header {
                font-size: 0.8rem;
                padding: 0.5rem;
            }
        }
        
        /* Chart Responsiveness */
        @media (max-width: 991.98px) {
            .col-lg-6 {
                margin-bottom: 1.5rem;
            }
        }
        
        /* Touch-friendly interactions */
        @media (max-width: 768px) {
            .btn, .card {
                -webkit-tap-highlight-color: rgba(0,0,0,0.1);
            }
        }
        
        /* Responsive offcanvas */
        @media (max-width: 767.98px) {
            .offcanvas {
                max-width: 280px;
            }
            
            .offcanvas-body {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Sidebar -->
    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title responsive-title" id="mobileSidebarLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
    </div>

    <div class="main-flex-container">
        <!-- Desktop Sidebar -->
        <div class="sidebar-fixed d-none d-md-block p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content-scroll">
            <!-- Sticky Header -->
            <div class="sticky-header">
                <!-- Mobile menu button -->
                <div class="d-md-none p-3 border-bottom">
                    <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                        <i class="bi bi-list me-2"></i> 
                        <span class="responsive-text">Menu</span>
                    </button>
                </div>
                <?php include '../includes/header.php'; ?>
            </div>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <!-- Quick Start Section -->
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-lightning-charge-fill text-warning me-2"></i> 
                                <span class="responsive-text">Quick Start</span>
                            </h5>
                            <div class="row g-3">
                                <?php if ($role === 'super_admin' || in_array('financial.view_sales', $user_permissions)): ?>
                                <div class="col-6 col-md-3">
                                    <a href="daily_sales_summary.php" class="btn btn-outline-primary w-100 py-3">
                                        <i class="bi bi-cash-coin"></i>
                                        <span class="responsive-text">New Sale</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($role === 'super_admin' || in_array('financial.view_purchases', $user_permissions)): ?>
                                <div class="col-6 col-md-3">
                                    <a href="purchases.php" class="btn btn-outline-success w-100 py-3">
                                        <i class="bi bi-bag-plus"></i>
                                        <span class="responsive-text">New Purchase</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($role === 'super_admin' || in_array('employee.manage', $user_permissions)): ?>
                                <div class="col-6 col-md-3">
                                    <a href="employee_management.php" class="btn btn-outline-info w-100 py-3">
                                        <i class="bi bi-person-plus"></i>
                                        <span class="responsive-text">Add Employee</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($role === 'super_admin' || in_array('reports.view', $user_permissions)): ?>
                                <div class="col-6 col-md-3">
                                    <a href="reports.php" class="btn btn-outline-dark w-100 py-3">
                                        <i class="bi bi-bar-chart-line"></i>
                                        <span class="responsive-text">View Reports</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($role === 'super_admin' || in_array('financial.view_expenses', $user_permissions)): ?>
                                <div class="col-6 col-md-3">
                                    <a href="expenses.php" class="btn btn-outline-warning w-100 py-3">
                                        <i class="bi bi-currency-exchange"></i>
                                        <span class="responsive-text">Add Expense</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($role === 'super_admin' || in_array('shift.assign', $user_permissions)): ?>
                                <div class="col-6 col-md-3">
                                    <a href="shift_assignments.php" class="btn btn-outline-secondary w-100 py-3">
                                        <i class="bi bi-clock-history"></i>
                                        <span class="responsive-text">Assign Shift</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($role === 'super_admin' || in_array('inventory.view', $user_permissions)): ?>
                                <div class="col-6 col-md-3">
                                    <a href="fuel_type_info.php" class="btn btn-outline-danger w-100 py-3">
                                        <i class="bi bi-droplet-half"></i>
                                        <span class="responsive-text">Storage Tanks</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($role === 'super_admin' || in_array('notifications.view', $user_permissions)): ?>
                                <div class="col-6 col-md-3">
                                    <a href="notifications.php" class="btn btn-outline-primary w-100 py-3">
                                        <i class="bi bi-bell"></i>
                                        <span class="responsive-text">Notifications</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <?php if ($role === 'super_admin'): ?>
                <div class="row g-4 mb-4">
                    <?php
                    $users_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"))[0];
                    $roles_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM roles WHERE deleted_at IS NULL"))[0];
                    $perms_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM permissions WHERE deleted_at IS NULL"))[0];
                    $branches_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM branches WHERE deleted_at IS NULL"))[0];
                    ?>
                    <div class="col-6 col-md-3">
                        <div class="card text-bg-primary h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="fas fa-users me-2 d-none d-md-inline"></i>
                                    <span class="responsive-text">Users</span>
                                </h5>
                                <p class="card-text display-6 fw-bold"><?php echo $users_count; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-bg-success h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="fas fa-user-shield me-2 d-none d-md-inline"></i>
                                    <span class="responsive-text">Roles</span>
                                </h5>
                                <p class="card-text display-6 fw-bold"><?php echo $roles_count; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-bg-warning h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="fas fa-key me-2 d-none d-md-inline"></i>
                                    <span class="responsive-text">Permissions</span>
                                </h5>
                                <p class="card-text display-6 fw-bold"><?php echo $perms_count; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-bg-info h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="fas fa-building me-2 d-none d-md-inline"></i>
                                    <span class="responsive-text">Branches</span>
                                </h5>
                                <p class="card-text display-6 fw-bold"><?php echo $branches_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Charts -->
                <?php if ($role === 'super_admin' && $business_id): ?>
                <div class="row mb-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <span class="responsive-text">Income vs Expense (<?php echo $current_year; ?>)</span>
                            </div>
                            <div class="card-body">
                                <canvas id="incomeExpenseChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <span class="responsive-text">Service Demand (<?php echo $current_year; ?>)</span>
                            </div>
                            <div class="card-body">
                                <canvas id="serviceDemandChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($role === 'super_admin' && $business_id): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const months = <?php echo json_encode($months); ?>;
    const incomeData = <?php echo json_encode($income_data); ?>;
    const expenseData = <?php echo json_encode($expense_data); ?>;
    const serviceDemandData = <?php echo json_encode($service_demand_data); ?>;
    
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { 
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 10,
                    font: {
                        size: window.innerWidth < 768 ? 10 : 12
                    }
                }
            } 
        },
        scales: { 
            y: { 
                beginAtZero: true,
                ticks: {
                    font: {
                        size: window.innerWidth < 768 ? 10 : 12
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        size: window.innerWidth < 768 ? 10 : 12
                    }
                }
            }
        }
    };
    
    // Income vs Expense Chart
    new Chart(document.getElementById('incomeExpenseChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                { label: 'Income', data: incomeData, backgroundColor: 'rgba(54, 162, 235, 0.7)' },
                { label: 'Expense', data: expenseData, backgroundColor: 'rgba(255, 99, 132, 0.7)' }
            ]
        },
        options: chartOptions
    });
    
    // Service Demand Chart
    new Chart(document.getElementById('serviceDemandChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                { 
                    label: 'Service Demand', 
                    data: serviceDemandData, 
                    borderColor: 'rgba(40,167,69,0.9)', 
                    backgroundColor: 'rgba(40,167,69,0.2)', 
                    fill: true 
                }
            ]
        },
        options: chartOptions
    });
    </script>
    <?php endif; ?>

    <script>
    // Responsive utilities
    function updateResponsiveElements() {
        const screenWidth = window.innerWidth;
        
        if (screenWidth < 768) {
            const charts = document.querySelectorAll('canvas');
            charts.forEach(chart => {
                chart.style.height = '200px';
            });
        }
        
        const buttonTexts = document.querySelectorAll('.responsive-text');
        buttonTexts.forEach(text => {
            if (screenWidth < 576) {
                text.style.fontSize = '0.75rem';
            } else if (screenWidth < 768) {
                text.style.fontSize = '0.875rem';
            } else {
                text.style.fontSize = '1rem';
            }
        });
    }
    
    window.addEventListener('load', updateResponsiveElements);
    window.addEventListener('resize', updateResponsiveElements);
    
    // Touch-friendly hover effects
    if ('ontouchstart' in window) {
        document.addEventListener('touchstart', function() {}, true);
    }
    </script>
</body>
</html>