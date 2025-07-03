<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: #f8fafc;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -280px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
        }
        
        .nav-link {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 0;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .dropdown-toggle::after {
            transition: transform 0.3s ease;
        }
        
        .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }
        
        .collapse {
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .menu-section {
            margin-bottom: 0.5rem;
        }
        
        .menu-icon {
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        
        .submenu-item {
            position: relative;
            padding-left: 2rem;
        }
        
        .submenu-item::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 50%;
            width: 6px;
            height: 6px;
            background: #64748b;
            border-radius: 50%;
            transform: translateY(-50%);
            transition: all 0.3s ease;
        }
        
        .submenu-item:hover::before {
            background: #3b82f6;
            transform: translateY(-50%) scale(1.3);
        }
        
        .user-profile {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .brand-logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #64748b 50%, transparent 100%);
            margin: 1rem 0;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column p-3" style="min-width: 280px; max-width: 280px; min-height: 100vh;">
            <!-- Header -->
            <div class="sidebar-header text-center">
                <div class="brand-logo d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-gas-pump me-2"></i>
                    Fuel Station
                </div>
                <small class="text-slate-400">Management System</small>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="flex-grow-1">
                <ul class="nav nav-pills flex-column mb-auto">
                    
                    <!-- User Management -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#userMgmt" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="userMgmt">
                            <i class="fas fa-users menu-icon text-blue-400"></i>
                            User Management
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="userMgmt">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Users</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Roles</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Permissions</a></li>
                        </ul>
                    </li>
                    
                    <!-- Business & Branches -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#bizMgmt" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="bizMgmt">
                            <i class="fas fa-building menu-icon text-green-400"></i>
                            Business & Branches
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="bizMgmt">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Businesses</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Branches</a></li>
                        </ul>
                    </li>
                    
                    <!-- Sales & Transactions -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#salesMgmt" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="salesMgmt">
                            <i class="fas fa-cash-register menu-icon text-yellow-400"></i>
                            Sales & Transactions
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="salesMgmt">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Sales Transactions</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Credit Sales</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Customer Accounts</a></li>
                        </ul>
                    </li>
                    
                    <!-- Inventory -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#inventoryMgmt" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="inventoryMgmt">
                            <i class="fas fa-boxes menu-icon text-purple-400"></i>
                            Inventory
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="inventoryMgmt">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Fuel Types</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Suppliers</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Storage Tanks</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Fuel Dispensers</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Fuel Purchases</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Tank Readings</a></li>
                        </ul>
                    </li>
                    
                    <!-- Financial -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#financeMgmt" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="financeMgmt">
                            <i class="fas fa-chart-line menu-icon text-emerald-400"></i>
                            Financial
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="financeMgmt">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Daily Sales Summary</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Expenses</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Cash Float</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Bank Reconciliation</a></li>
                        </ul>
                    </li>
                    
                    <!-- Shift Management -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#shiftMgmt" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="shiftMgmt">
                            <i class="fas fa-clock menu-icon text-orange-400"></i>
                            Shift Management
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="shiftMgmt">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Shifts</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Shift Assignments</a></li>
                        </ul>
                    </li>
                    
                    <!-- Maintenance & Compliance -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#maintMgmt" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="maintMgmt">
                            <i class="fas fa-tools menu-icon text-red-400"></i>
                            Maintenance & Compliance
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="maintMgmt">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Equipment Maintenance</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Regulatory Compliance</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Fuel Quality Tests</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Safety Incidents</a></li>
                        </ul>
                    </li>
                    
                    <!-- Reporting & Analytics -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#reporting" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="reporting">
                            <i class="fas fa-chart-pie menu-icon text-indigo-400"></i>
                            Reporting & Analytics
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="reporting">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Reports</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Outstanding Credit</a></li>
                        </ul>
                    </li>
                    
                    <!-- System -->
                    <li class="nav-item dropdown menu-section">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center py-2" 
                           href="#systemMgmt" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="systemMgmt">
                            <i class="fas fa-cog menu-icon text-gray-400"></i>
                            System
                        </a>
                        <ul class="collapse list-unstyled ps-3" id="systemMgmt">
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">System Settings</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Audit Logs</a></li>
                            <li><a class="nav-link text-slate-300 submenu-item py-2" href="#">Notifications</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            
            <!-- Divider -->
            <div class="divider"></div>
            
            <!-- User Profile Section -->
            <div class="user-profile">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-full p-2 me-3">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div>
                        <div class="text-white fw-semibold">John Doe</div>
                        <small class="text-slate-400">Administrator</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="profile.php" class="nav-link text-slate-300 d-flex align-items-center py-2 px-3 rounded flex-grow-1">
                        <i class="fas fa-user-circle me-2"></i>
                        Profile
                    </a>
                    <a href="logout.php" class="nav-link text-slate-300 d-flex align-items-center py-2 px-3 rounded">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>