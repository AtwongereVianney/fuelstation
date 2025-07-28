<!DOCTYPE html>
<html lang="en">


<!-- Mirrored from crms.dreamstechnologies.com/html/template/manage-users.html by HTTrack Website Copier/3.x [XR&CO'2014], Sat, 26 Jul 2025 17:01:43 GMT -->
<head>

	<!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Manage Users List | CRMS - Advanced Bootstrap 5 Admin Template for Customer Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Streamline your business with our advanced CRM template. Easily integrate and customize to manage sales, support, and customer interactions efficiently. Perfect for any business size">
	<meta name="keywords" content="Advanced CRM template, customer relationship management, business CRM, sales optimization, customer support software, CRM integration, customizable CRM, business tools, enterprise CRM solutions">
	<meta name="author" content="Dreams Technologies">
	<meta name="robots" content="index, follow">
	
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.png">

    <!-- Apple Icon -->
    <link rel="apple-touch-icon" href="assets/img/apple-icon.png">

    <!-- Theme Config Js -->
    <script src="assets/js/theme-script.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">

    <!-- Daterangepikcer CSS -->
	<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

    <!-- Select2 CSS -->
	<link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">

    <!-- Datatable CSS -->
    <link rel="stylesheet" href="assets/plugins/datatables/css/dataTables.bootstrap5.min.css">

    <!-- Mobile CSS-->
    <link rel="stylesheet" href="assets/plugins/intltelinput/css/intlTelInput.css">
    <link rel="stylesheet" href="assets/plugins/intltelinput/css/demo.css">

    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">

    <!-- Simplebar CSS -->
    <link rel="stylesheet" href="assets/plugins/simplebar/simplebar.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css" id="app-style">

</head>

<body>

    <!-- Begin Wrapper -->
    <div class="main-wrapper">

        <!-- Topbar Start -->
        <header class="navbar-header">
            <div class="page-container topbar-menu">
                <div class="d-flex align-items-center gap-2">

                    <!-- Logo -->
                    <a href="index.html" class="logo">

                        <!-- Logo Normal -->
                        <span class="logo-light">
                            <span class="logo-lg"><img src="assets/img/logo.svg" alt="logo"></span>
                            <span class="logo-sm"><img src="assets/img/logo-small.svg" alt="small logo"></span>
                        </span>

                        <!-- Logo Dark -->
                        <span class="logo-dark">
                            <span class="logo-lg"><img src="assets/img/logo-white.svg" alt="dark logo"></span>
                        </span>
                    </a>

                    <!-- Sidebar Mobile Button -->
                    <a id="mobile_btn" class="mobile-btn" href="#sidebar">
                        <i class="ti ti-menu-deep fs-24"></i>
                    </a>

                    <button class="sidenav-toggle-btn btn border-0 p-0" id="toggle_btn2"> 
                        <i class="ti ti-arrow-bar-to-right"></i>
                    </button> 
					
                    <!-- Search -->
                    <div class="me-auto d-flex align-items-center header-search d-lg-flex d-none">
                        <!-- Search -->
                        <div class="input-icon position-relative me-2">
                           <input type="text" class="form-control" placeholder="Search Keyword">
                           <span class="input-icon-addon d-inline-flex p-0 header-search-icon"><i class="ti ti-command"></i></span>
                        </div>
                        <!-- /Search -->
                    </div>
					
                </div>

                <div class="d-flex align-items-center">
				
                    <!-- Search for Mobile -->
                    <div class="header-item d-flex d-lg-none me-2">
                        <button class="topbar-link btn" data-bs-toggle="modal" data-bs-target="#searchModal" type="button">
                            <i class="ti ti-search fs-16"></i>
                        </button>
                    </div>


                    <!-- Minimize -->
                    <div class="header-item">
                        <div class="dropdown me-2">
                            <a href="javascript:void(0);" class="btn topbar-link btnFullscreen"><i class="ti ti-maximize"></i></a>
                        </div> 
                    </div> 
                    <!-- Minimize --> 

                    <!-- Light/Dark Mode Button -->
                    <div class="header-item d-none d-sm-flex me-2">
                        <button class="topbar-link btn topbar-link" id="light-dark-mode" type="button">
                            <i class="ti ti-moon fs-16"></i>
                        </button>
                    </div>

                    <!-- pages -->
                    <div class="header-item d-none d-sm-flex">
                        <div class="dropdown me-2">
                            <a href="javascript:void(0);" class="btn topbar-link topbar-teal-link" data-bs-toggle="dropdown">
                                <i class="ti ti-layout-grid-add"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-2">

                                <!-- Item-->
                                <a href="contacts.html" class="dropdown-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="d-flex mb-1 fw-semibold text-dark">Contacts</span>
                                            <span class="fs-13">View All the Contacts</span>
                                        </div>
                                        <i class="ti ti-chevron-right-pipe text-dark"></i>
                                    </div>
                                </a>

                                <!-- Item-->
                                <a href="pipeline.html" class="dropdown-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="d-flex mb-1 fw-semibold text-dark">Pipeline</span>
                                            <span class="fs-13">View All the Pipeline</span>
                                        </div>
                                        <i class="ti ti-chevron-right-pipe text-dark"></i>
                                    </div>
                                </a>

                                <!-- Item-->
                                <a href="activities.html" class="dropdown-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="d-flex mb-1 fw-semibold text-dark">Activities</span>
                                            <span class="fs-13">Activities</span>
                                        </div>
                                        <i class="ti ti-chevron-right-pipe text-dark"></i>
                                    </div>
                                </a>

                                <!-- Item-->
                                <a href="analytics.html" class="dropdown-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="d-flex mb-1 fw-semibold text-dark">Analytics</span>
                                            <span class="fs-13">Analytics</span>
                                        </div>
                                        <i class="ti ti-chevron-right-pipe text-dark"></i>
                                    </div>
                                </a>

                            </div>
                        </div>
                    </div>

                    <!-- faq -->
                    <div class="header-item d-none d-sm-flex">
                        <div class="dropdown me-2">
                            <a href="faq.html" class="btn topbar-link topbar-indigo-link"><i class="ti ti-help-hexagon"></i></a>
                        </div> 
                    </div> 

                    <!-- report -->
                    <div class="header-item d-none d-sm-flex">
                        <div class="dropdown me-2">
                            <a href="lead-reports.html" class="btn topbar-link topbar-warning-link"><i class="ti ti-chart-pie"></i></a>
                        </div> 
                    </div> 

					<div class="header-line"></div>

                     <!-- message -->
                    <div class="header-item">
                        <div class="dropdown me-2">
                            <a href="chat.html" class="btn topbar-link">
                                <i class="ti ti-message-circle-exclamation"></i>
                                <span class="badge rounded-pill">14</span>
                            </a>
                        </div> 
                    </div>
                    
					<!-- Notification Dropdown -->
                    <div class="header-item">
						<div class="dropdown me-2">
						
							<button class="topbar-link btn topbar-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" data-bs-offset="0,24" type="button" aria-haspopup="false" aria-expanded="false">
								<i class="ti ti-bell-check fs-16 animate-ring"></i>
								<span class="badge rounded-pill">10</span>
							</button>
							
							<div class="dropdown-menu p-0 dropdown-menu-end dropdown-menu-lg" style="min-height: 300px;">
							
								<div class="p-2 border-bottom">
									<div class="row align-items-center">
										<div class="col">
											<h6 class="m-0 fs-16 fw-semibold"> Notifications</h6>
										</div>
									</div>
								</div>
								
								<!-- Notification Body -->
								<div class="notification-body position-relative z-2 rounded-0" data-simplebar>
								 
									<!-- Item-->
									<div class="dropdown-item notification-item py-3 text-wrap border-bottom" id="notification-1">
										<div class="d-flex">
											<div class="me-2 position-relative flex-shrink-0">
												<img src="assets/img/users/user-01.jpg" class="avatar-md rounded-circle" alt="Img">
											</div>
											<div class="flex-grow-1">
												<p class="mb-0 fw-medium text-dark">John Doe</p>
												<p class="mb-1 text-wrap">
													left 6 comments on <span class="fw-medium text-dark">Isla Nublar SOC2 compliance report</span>
												</p>
												<div class="d-flex justify-content-between align-items-center">
													<span class="fs-12"><i class="ti ti-clock me-1"></i>4 min ago</span>
													<div class="notification-action d-flex align-items-center float-end gap-2">
														<a href="javascript:void(0);" class="notification-read rounded-circle bg-danger" data-bs-toggle="tooltip" title="" data-bs-original-title="Make as Read" aria-label="Make as Read"></a>
														<button class="btn rounded-circle p-0" data-dismissible="#notification-1">
															<i class="ti ti-x"></i>
														</button>
													</div>
												</div>
											</div>
										</div>
									</div>
							
									<!-- Item-->
									<div class="dropdown-item notification-item py-3 text-wrap border-bottom" id="notification-2">
										<div class="d-flex">
											<div class="me-2 position-relative flex-shrink-0">
												<img src="assets/img/users/user-12.jpg" class="avatar-md rounded-circle" alt="Img">
											</div>
											<div class="flex-grow-1">
												<p class="mb-0 fw-medium text-dark">Thomas William</p>
												<p class="mb-1 text-wrap">
                                                    “Oh, I finished de-bugging the phones, but the system's compiling for eighteen minutes, or twenty...”
												</p>
												<div class="d-flex justify-content-between align-items-center">
													<span class="fs-12"><i class="ti ti-clock me-1"></i>8 min ago</span>
													<div class="notification-action d-flex align-items-center float-end gap-2">
														<a href="javascript:void(0);" class="notification-read rounded-circle bg-danger" data-bs-toggle="tooltip" title="" data-bs-original-title="Make as Read" aria-label="Make as Read"></a>
														<button class="btn rounded-circle p-0" data-dismissible="#notification-2">
															<i class="ti ti-x"></i>
														</button>
													</div>
												</div>
											</div>
										</div>
									</div>
									
									<!-- Item-->
									<div class="dropdown-item notification-item py-3 text-wrap border-bottom" id="notification-3">
										<div class="d-flex">
											<div class="me-2 position-relative flex-shrink-0">
												<img src="assets/img/profiles/avatar-12.jpg" class="avatar-md rounded-circle" alt="Img">
											</div>
											<div class="flex-grow-1">
												<p class="mb-0 fw-medium text-dark">Sarah Anderson</p>
												<p class="mb-1 text-wrap">
                                                   attached a file to <span class="fw-medium text-dark">Isla Nublar SOC2 compliance report</span>
												</p>
												<div class="d-flex justify-content-between align-items-center">
													<span class="fs-12"><i class="ti ti-clock me-1"></i>15 min ago</span>
													<div class="notification-action d-flex align-items-center float-end gap-2">
														<a href="javascript:void(0);" class="notification-read rounded-circle bg-danger" data-bs-toggle="tooltip" title="" data-bs-original-title="Make as Read" aria-label="Make as Read"></a>
														<button class="btn rounded-circle p-0" data-dismissible="#notification-3">
															<i class="ti ti-x"></i>
														</button>
													</div>
												</div>
											</div>
										</div>
									</div>
									
									<!-- Item-->
									<div class="dropdown-item notification-item py-3 text-wrap" id="notification-4">
										<div class="d-flex">
											<div class="me-2 position-relative flex-shrink-0">
												<img src="assets/img/profiles/avatar-08.jpg" class="avatar-md rounded-circle" alt="Img">
											</div>
											<div class="flex-grow-1">
												<p class="mb-0 fw-medium text-dark">Ann McClure</p>
												<p class="mb-1 text-wrap">
                                                    mentioned you in <span class="fw-medium text-dark">Bug Fix Review - Task #432</span>
												</p>
												<div class="d-flex justify-content-between align-items-center">
													<span class="fs-12"><i class="ti ti-clock me-1"></i>20 min ago</span>
													<div class="notification-action d-flex align-items-center float-end gap-2">
														<a href="javascript:void(0);" class="notification-read rounded-circle bg-danger" data-bs-toggle="tooltip" title="" data-bs-original-title="Make as Read" aria-label="Make as Read"></a>
														<button class="btn rounded-circle p-0" data-dismissible="#notification-4">
															<i class="ti ti-x"></i>
														</button>
													</div>
												</div>
											</div>
										</div>
									</div>
									 
								</div>
								
								<!-- View All-->
								<div class="p-2 rounded-bottom border-top text-center">
									<a href="notifications.html" class="text-center text-decoration-underline fs-14 mb-0">
										View All Notifications
									</a>
								</div>
								
							</div>
						</div>
					</div> 
					
					<!-- User Dropdown -->
					<div class="dropdown profile-dropdown d-flex align-items-center justify-content-center">
                        <a href="javascript:void(0);" class="topbar-link dropdown-toggle drop-arrow-none position-relative" data-bs-toggle="dropdown" data-bs-offset="0,22" aria-haspopup="false" aria-expanded="false">
                            <img src="assets/img/users/user-40.jpg" width="38" class="rounded-1 d-flex" alt="user-image">
                            <span class="online text-success"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-2">
                        
                            <div class="d-flex align-items-center bg-light rounded-3 p-2 mb-2">
                                <img src="assets/img/users/user-40.jpg" class="rounded-circle" width="42" height="42" alt="Img">
                                <div class="ms-2">
                                    <p class="fw-medium text-dark mb-0">Katherine Brooks</p>
                                    <span class="d-block fs-13">Installer</span>
                                </div>
                            </div>

                            <!-- Item-->
                            <a href="profile-settings.html" class="dropdown-item">
                                <i class="ti ti-user-circle me-1 align-middle"></i>
                                <span class="align-middle">Profile Settings</span>
                            </a>

                            <!-- item -->
                            <div class="form-check form-switch form-check-reverse d-flex align-items-center justify-content-between dropdown-item mb-0">
                                <label class="form-check-label" for="notify"><i class="ti ti-bell"></i>Notifications</label>
                                <input class="form-check-input me-0" type="checkbox" role="switch" id="notify">
                            </div>

                            <!-- Item-->
                            <a href="javascript:void(0);" class="dropdown-item">
                                <i class="ti ti-help-circle me-1 align-middle"></i>
                                <span class="align-middle">Help & Support</span>
                            </a>

                            <!-- Item-->
                            <a href="profile-settings.html" class="dropdown-item">
                                <i class="ti ti-settings me-1 align-middle"></i>
                                <span class="align-middle">Settings</span>
                            </a>            
                            
                            <!-- Item-->
                            <div class="pt-2 mt-2 border-top">
                                <a href="login.html" class="dropdown-item text-danger">
                                    <i class="ti ti-logout me-1 fs-17 align-middle"></i>
                                    <span class="align-middle">Sign Out</span>
                                </a>
                            </div>
                        </div>
                    </div>
						
                </div>
            </div>
        </header>
        <!-- Topbar End -->

        <!-- Search Modal -->
        <div class="modal fade" id="searchModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-transparent">
                    <div class="card shadow-none mb-0">
                        <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                            <i class="ti ti-search fs-22"></i>
                            <input type="search" class="form-control border-0" placeholder="Search">
                            <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x fs-22"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidenav Menu Start -->
        <div class="sidebar" id="sidebar">
            
            <!-- Start Logo -->
            <div class="sidebar-logo">
                <div>
                    <!-- Logo Normal -->
                    <a href="index.html" class="logo logo-normal">
                        <img src="assets/img/logo.svg" alt="Logo">
                    </a>

                    <!-- Logo Small -->
                    <a href="index.html" class="logo-small">
                        <img src="assets/img/logo-small.svg" alt="Logo">
                    </a>

                    <!-- Logo Dark -->
                    <a href="index.html" class="dark-logo">
                        <img src="assets/img/logo-white.svg" alt="Logo">
                    </a>
                </div>
                <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn"> 
                    <i class="ti ti-arrow-bar-to-left"></i>
                </button>

                <!-- Sidebar Menu Close -->
                <button class="sidebar-close">
                    <i class="ti ti-x align-middle"></i>
                </button>                
            </div>
            <!-- End Logo -->

            <!-- Sidenav Menu -->
            <div class="sidebar-inner" data-simplebar>                
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>
                        <li class="menu-title"><span>Main Menu</span></li>
						<li>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-dashboard"></i><span>Dashboard</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="index.html">Deals Dashboard</a></li>
										<li><a href="leads-dashboard.html">Leads Dashboard</a></li>
										<li><a href="project-dashboard.html">Project Dashboard</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);"><i class="ti ti-brand-airtable"></i><span>Applications</span><span class="menu-arrow"></span></a>
									<ul>
										<li><a href="chat.html">Chat</a></li>
										<li class="submenu submenu-two">
											<a href="javascript:void(0);">Call<span class="menu-arrow inside-submenu"></span></a>
											<ul>
												<li><a href="video-call.html">Video Call</a></li>
												<li><a href="audio-call.html">Audio Call</a></li>
												<li><a href="call-history.html">Call History</a></li>
											</ul>
										</li>
										<li><a href="calendar.html">Calendar</a></li>
										<li><a href="email.html">Email</a></li>
										<li><a href="todo.html">To Do</a></li>
										<li><a href="notes.html">Notes</a></li>
										<li><a href="file-manager.html">File Manager</a></li>
										<li><a href="social-feed.html">Social Feed</a></li>
										<li><a href="kanban-view.html">Kanban</a></li>
										<li><a href="invoice.html">Invoices</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="#">
										<i class="ti ti-user-star"></i><span>Super Admin</span>
										<span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="dashboard.html">Dashboard</a></li>
										<li><a href="company.html">Companies</a></li>
										<li><a href="subscription.html">Subscriptions</a></li>
										<li><a href="packages.html">Packages</a></li>
										<li><a href="domain.html">Domain</a></li>
										<li><a href="purchase-transaction.html">Purchase Transaction</a></li>
									</ul>
								</li>
                                <li class="submenu">
									<a href="#">
										<i class="ti ti-layout-grid"></i><span>Layouts</span>
										<span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="layout-mini.html">Mini</a></li>
										<li><a href="layout-hoverview.html">Hover View</a></li>
										<li><a href="layout-hidden.html">Hidden</a></li>
										<li><a href="layout-fullwidth.html">Full Width</a></li>
										<li><a href="layout-rtl.html">RTL</a></li>
										<li><a href="layout-dark.html">Dark</a></li>
									</ul>
								</li>
							</ul>
						</li>
                        <li class="menu-title"><span>CRM</span></li>
						<li>
							<ul>
								<li>
									<a href="contacts.html"><i class="ti ti-user-up"></i><span>Contacts</span></a>
								</li>
								<li>
									<a href="companies.html"><i class="ti ti-building-community"></i><span>Companies</span></a>
								</li>
								<li>
									<a href="deals.html"><i class="ti ti-medal"></i><span>Deals</span></a>
								</li>
								<li>
									<a href="leads.html"><i class="ti ti-chart-arcs"></i><span>Leads</span></a>
								</li>
								<li>
									<a href="pipeline.html"><i class="ti ti-timeline-event-exclamation"></i><span>Pipeline</span></a>
								</li>
								<li>
									<a href="campaign.html"><i class="ti ti-brand-campaignmonitor"></i><span>Campaign</span></a>
								</li>
								<li>
									<a href="projects.html"><i class="ti ti-atom-2"></i><span>Projects</span></a>
								</li>
								<li>
									<a href="tasks.html"><i class="ti ti-list-check"></i><span>Tasks</span></a>
								</li>
								<li>
									<a href="proposals.html"><i class="ti ti-file-star"></i><span>Proposals</span></a>
								</li>
								<li>
									<a href="contracts.html"><i class="ti ti-file-check"></i><span>Contracts</span></a>
								</li>
								<li>
									<a href="estimations.html"><i class="ti ti-file-report"></i><span>Estimations</span></a>
								</li>
								<li>
									<a href="invoices.html"><i class="ti ti-file-invoice"></i><span>Invoices</span></a>
								</li>
								<li>
									<a href="payments.html"><i class="ti ti-report-money"></i><span>Payments</span></a>
								</li>
								<li>
									<a href="analytics.html"><i class="ti ti-chart-bar"></i><span>Analytics</span></a>
								</li>
								<li>
									<a href="activities.html"><i class="ti ti-bounce-right"></i><span>Activities</span></a>
								</li>
							</ul>
						</li>
                        <li class="menu-title"><span>Reports</span></li>
						<li>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-report-analytics"></i><span>Reports</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="lead-reports.html">Lead Reports</a></li>
										<li><a href="deal-reports.html">Deal Reports</a></li>
										<li><a href="contact-reports.html">Contact Reports</a></li>
										<li><a href="company-reports.html">Company Reports</a></li>
										<li><a href="project-reports.html">Project Reports</a></li>
										<li><a href="task-reports.html">Task Reports</a></li>
									</ul>
								</li>
							</ul>
						</li>
                        <li class="menu-title"><span>CRM Settings</span></li>
						<li>
							<ul>
								<li><a href="sources.html"><i class="ti ti-artboard"></i><span>Sources</span></a></li>
								<li><a href="lost-reason.html"><i class="ti ti-message-exclamation"></i><span>Lost Reason</span></a></li>
								<li><a href="contact-stage.html"><i class="ti ti-steam"></i><span>Contact Stage</span></a></li>
								<li><a href="industry.html"><i class="ti ti-building-factory"></i><span>Industry</span></a></li>
								<li><a href="calls.html"><i class="ti ti-phone-check"></i><span>Calls</span></a></li>
							</ul>
						</li>
                        <li class="menu-title"><span>User Management</span></li>
						<li>							
							<ul>
								<li class="active"><a href="manage-users.html"><i class="ti ti-users"></i><span>Manage Users</span></a></li>
								<li><a href="roles-permissions.html"><i class="ti ti-user-shield"></i><span>Roles & Permissions</span></a></li>
								<li><a href="delete-request.html"><i class="ti ti-flag-question"></i><span>Delete Request</span></a></li>
							</ul>
						</li>
                        <li class="menu-title"><span>Membership</span></li>
						<li>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-brand-apple-podcast"></i><span>Membership</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="membership-plans.html">Membership Plans</a></li>
										<li><a href="membership-addons.html">Membership Addons</a></li>
										<li><a href="membership-transactions.html">Transactions</a></li>
									</ul>
								</li>
							</ul>
						</li>
                        <li class="menu-title"><span>Content</span></li>
						<li>
							<ul>
								<li><a href="pages.html"><i class="ti ti-page-break"></i><span>Pages</span></a></li>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-brand-blogger"></i><span>Blog</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="blogs.html">All Blogs</a></li>
										<li><a href="blog-categories.html">Blog Categories</a></li>
										<li><a href="blog-comments.html">Blog Comments</a></li>
                                        <li><a href="blog-tags.html">Blog Tags</a></li>
									</ul>
								</li>
                                <li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-map-pin-pin"></i><span>Location</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="countries.html">Countries</a></li>
										<li><a href="states.html">States</a></li>
										<li><a href="cities.html">Cities</a></li>
									</ul>
								</li>                                
								<li><a href="testimonials.html"><i class="ti ti-quote"></i><span>Testimonials</span></a></li>
								<li><a href="faq.html"><i class="ti ti-question-mark"></i><span>FAQ</span></a></li>
							</ul>
						</li>
                        <li class="menu-title"><span>Support</span></li>
						<li>
							<ul>
								<li><a href="contact-messages.html"><i class="ti ti-message-check"></i><span>Contact Messages</span></a></li>
								<li><a href="tickets.html"><i class="ti ti-ticket"></i><span>Tickets</span></a></li>
							</ul>
						</li>
                        <li class="menu-title"><span>Settings</span></li>
						<li>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-settings-cog"></i><span>General Settings</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="profile-settings.html">Profile</a></li>
										<li><a href="security-settings.html">Security</a></li>
										<li><a href="notifications-settings.html">Notifications</a></li>
										<li><a href="connected-apps.html">Connected Apps</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-world-cog"></i><span>Website Settings</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="company-settings.html">Company Settings</a></li>
										<li><a href="localization-settings.html">Localization</a></li>
										<li><a href="prefixes-settings.html">Prefixes</a></li>
										<li><a href="preference-settings.html">Preference</a></li>
										<li><a href="appearance-settings.html">Appearance</a></li>
										<li><a href="language-settings.html">Language</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-apps"></i><span>App Settings</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="invoice-settings.html">Invoice Settings</a></li>
										<li><a href="printers-settings.html">Printers</a></li>
										<li><a href="custom-fields-setting.html">Custom Fields</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-device-laptop"></i><span>System Settings</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="email-settings.html">Email Settings</a></li>
										<li><a href="sms-gateways.html">SMS Gateways</a></li>
										<li><a href="gdpr-cookies.html">GDPR Cookies</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-moneybag"></i><span>Financial Settings</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="payment-gateways.html">Payment Gateways</a></li>
										<li><a href="bank-accounts.html">Bank Accounts</a></li>
										<li><a href="tax-rates.html">Tax Rates</a></li>
										<li><a href="currencies.html">Currencies</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-settings-2"></i><span>Other Settings</span><span class="menu-arrow"></span>
									</a>
									<ul>
                                        <li><a href="sitemap.html">Sitemap</a></li>
                                        <li><a href="clear-cache.html">Clear Cache</a></li>
                                        <li><a href="storage.html">Storage</a></li>
                                        <li><a href="cronjob.html">Cronjob</a></li>
                                        <li><a href="ban-ip-address.html">Ban IP Address</a></li>
                                        <li><a href="system-backup.html">System Backup</a></li>
                                        <li><a href="database-backup.html">Database Backup</a></li>
                                        <li><a href="system-update.html">System Update</a></li>
									</ul>
								</li>
							</ul>
						</li>
                        <li class="menu-title"><span>Pages</span></li>
						<li>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-lock-square-rounded"></i><span>Authentication</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="login.html">Login</a></li>
										<li><a href="register.html">Register</a></li>
										<li><a href="forgot-password.html">Forgot Password</a></li>
										<li><a href="reset-password.html">Reset Password</a></li>
										<li><a href="email-verification.html">Email Verification</a></li>
										<li><a href="two-step-verification.html">2 Step Verification</a></li>
										<li><a href="lock-screen.html">Lock Screen</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-error-404"></i><span>Error Pages</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="error-404.html">404 Error</a></li>
										<li><a href="error-500.html">500 Error</a></li>
									</ul>
								</li>
								<li><a href="blank-page.html"><i class="ti ti-file"></i><span>Blank Page</span></a></li>
								<li><a href="coming-soon.html"><i class="ti ti-inner-shadow-top-right"></i><span>Coming Soon</span></a></li>
								<li><a href="under-maintenance.html"><i class="ti ti-info-triangle"></i><span>Under Maintenance</span></a></li>
							</ul>
						</li>
						<li class="menu-title"><span>UI Interface</span></li>
                        <li>
                            <ul>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-hierarchy"></i><span>Base UI</span><span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="ui-accordion.html">Accordion</a></li>
                                        <li><a href="ui-alerts.html">Alerts</a></li>
                                        <li><a href="ui-avatar.html">Avatar</a></li>
                                        <li><a href="ui-badges.html">Badges</a></li>
                                        <li><a href="ui-breadcrumb.html">Breadcrumb</a></li>
                                        <li><a href="ui-buttons.html">Buttons</a></li>
                                        <li><a href="ui-buttons-group.html">Button Group</a></li>
                                        <li><a href="ui-cards.html">Card</a></li>
                                        <li><a href="ui-carousel.html">Carousel</a></li>
                                        <li><a href="ui-collapse.html">Collapse</a></li>
                                        <li><a href="ui-dropdowns.html">Dropdowns</a></li>
                                        <li><a href="ui-ratio.html">Ratio</a></li>
                                        <li><a href="ui-grid.html">Grid</a></li>
                                        <li><a href="ui-images.html">Images</a></li>
                                        <li><a href="ui-links.html">Links</a></li>
                                        <li><a href="ui-list-group.html">List Group</a></li>
                                        <li><a href="ui-modals.html">Modals</a></li>
                                        <li><a href="ui-offcanvas.html">Offcanvas</a></li>
                                        <li><a href="ui-pagination.html">Pagination</a></li>
                                        <li><a href="ui-placeholders.html">Placeholders</a></li>
                                        <li><a href="ui-popovers.html">Popovers</a></li>
                                        <li><a href="ui-progress.html">Progress</a></li>
                                        <li><a href="ui-scrollspy.html">Scrollspy</a></li>
                                        <li><a href="ui-spinner.html">Spinner</a></li>
                                        <li><a href="ui-nav-tabs.html">Tabs</a></li>
                                        <li><a href="ui-toasts.html">Toasts</a></li>
                                        <li><a href="ui-tooltips.html">Tooltips</a></li>
                                        <li><a href="ui-typography.html">Typography</a></li>
                                        <li><a href="ui-utilities.html">Utilities</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-whirl"></i><span>Advanced UI</span><span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="ui-dragula.html">Dragula</a></li>
                                        <li><a href="ui-clipboard.html">Clipboard</a></li>
                                        <li><a href="ui-rangeslider.html">Range Slider</a></li>
                                        <li><a href="ui-sweetalerts.html">Sweet Alerts</a></li>
                                        <li><a href="ui-lightbox.html">Lightbox</a></li>
                                        <li><a href="ui-rating.html">Rating</a></li>
                                        <li><a href="ui-scrollbar.html">Scrollbar</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-forms"></i><span>Forms</span><span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li class="submenu submenu-two">
                                            <a href="javascript:void(0);">Form Elements<span class="menu-arrow inside-submenu"></span></a>
                                            <ul>
                                                <li><a href="form-basic-inputs.html">Basic Inputs</a></li>
                                                <li><a href="form-checkbox-radios.html">Checkbox & Radios</a></li>
                                                <li><a href="form-input-groups.html">Input Groups</a></li>
                                                <li><a href="form-grid-gutters.html">Grid & Gutters</a></li>
                                                <li><a href="form-mask.html">Input Masks</a></li>
                                                <li><a href="form-fileupload.html">File Uploads</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu submenu-two">
                                            <a href="javascript:void(0);">Layouts<span class="menu-arrow inside-submenu"></span></a>
                                            <ul>
                                                <li><a href="form-horizontal.html">Horizontal Form</a></li>
                                                <li><a href="form-vertical.html">Vertical Form</a></li>
                                                <li><a href="form-floating-labels.html">Floating Labels</a></li>
                                            </ul>
                                        </li>
                                        <li><a href="form-validation.html">Form Validation</a></li>
                                        <li><a href="form-select.html">Form Select</a></li>
                                        <li><a href="form-wizard.html">Form Wizard</a></li>
                                        <li><a href="form-pickers.html">Form Picker</a></li>
                                        <li><a href="form-editors.html">Form Editors</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-table"></i><span>Tables</span><span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="tables-basic.html">Basic Tables </a></li>
                                        <li><a href="data-tables.html">Data Table </a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-chart-pie-3"></i><span>Charts</span><span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="chart-apex.html">Apex Charts</a></li>
                                        <li><a href="chart-c3.html">Chart C3</a></li>
                                        <li><a href="chart-js.html">Chart Js</a></li>
                                        <li><a href="chart-morris.html">Morris Charts</a></li>
                                        <li><a href="chart-flot.html">Flot Charts</a></li>
                                        <li><a href="chart-peity.html">Peity Charts</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="javascript:void(0);">
                                        <i class="ti ti-icons"></i><span>Icons</span><span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="icon-fontawesome.html">Fontawesome Icons</a></li>
                                        <li><a href="icon-tabler.html">Tabler Icons</a></li>
                                        <li><a href="icon-bootstrap.html">Bootstrap Icons</a></li>
                                        <li><a href="icon-remix.html">Remix Icons</a></li>
                                        <li><a href="icon-feather.html">Feather Icons</a></li>
                                        <li><a href="icon-ionic.html">Ionic Icons</a></li>
                                        <li><a href="icon-material.html">Material Icons</a></li>
                                        <li><a href="icon-pe7.html">Pe7 Icons</a></li>
                                        <li><a href="icon-simpleline.html">Simpleline Icons</a></li>
                                        <li><a href="icon-themify.html">Themify Icons</a></li>
                                        <li><a href="icon-weather.html">Weather Icons</a></li>
                                        <li><a href="icon-typicon.html">Typicon Icons</a></li>
                                        <li><a href="icon-flag.html">Flag Icons</a></li>
                                    </ul>
                                </li>
								<li class="submenu">
									<a href="javascript:void(0);"><i class="ti ti-map-star"></i><span>Maps</span>
										<span class="menu-arrow"></span>
									</a>
									<ul>
										<li>
											<a href="maps-vector.html">Vector</a>
										</li>
										<li>
											<a href="maps-leaflet.html">Leaflet</a>
										</li>
									</ul>
								</li>
                            </ul>
                        </li>
                        <li class="menu-title"><span>Help</span></li>
						<li>
							<ul>
								<li><a href="javascript:void(0);"><i class="ti ti-file-stack"></i><span>Documentation</span></a></li>
								<li><a href="javascript:void(0);"><i class="ti ti-arrow-capsule"></i><span>Changelog v2.2.7</span></a></li>
								<li class="submenu">
									<a href="javascript:void(0);"><i class="ti ti-menu-deep"></i><span>Multi Level</span><span class="menu-arrow"></span></a>
									<ul>
										<li><a href="javascript:void(0);">Level 1.1</a></li>
										<li class="submenu submenu-two"><a href="javascript:void(0);">Level 1.2<span class="menu-arrow inside-submenu"></span></a>
											<ul>
												<li><a href="javascript:void(0);">Level 2.1</a></li>
												<li class="submenu submenu-two submenu-three"><a href="javascript:void(0);">Level 2.2<span class="menu-arrow inside-submenu inside-submenu-two"></span></a>
													<ul>
														<li><a href="javascript:void(0);">Level 3.1</a></li>
														<li><a href="javascript:void(0);">Level 3.2</a></li>
													</ul>
												</li>
											</ul>
										</li>
									</ul>
								</li>
							</ul>
						</li>
					</ul>
                </div>
            </div>

        </div>
        <!-- Sidenav Menu End -->

        <!-- ========================
			Start Page Content
		========================= -->
         
        <div class="page-wrapper">

            <!-- Start Content -->
            <div class="content pb-0">

                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Manage Users<span class="badge badge-soft-primary ms-2">152</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="ti ti-package-export me-2"></i>Export</a>
                            <div class="dropdown-menu  dropdown-menu-end">
                                <ul>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item"><i class="ti ti-file-type-pdf me-1"></i>Export as
                                            PDF</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item"><i class="ti ti-file-type-xls me-1"></i>Export as
                                            Excel </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
                    </div>
                </div>                
				<!-- End Page Header -->
                
                <!-- card start -->
                <div class="card border-0 rounded-0">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="input-icon input-icon-start position-relative">
                            <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search">
                        </div>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add User</a>
                    </div>
                    <div class="card-body">

                        <!-- table header -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-filter me-2"></i>Filter<i class="ti ti-chevron-down ms-2"></i></a>
                                    <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg p-0">
                                        <div class="filter-header d-flex align-items-center justify-content-between border-bottom">
                                            <h6 class="mb-0"><i class="ti ti-filter me-1"></i>Filter</h6>
                                            <button type="button" class="btn-close close-filter-btn" data-bs-dismiss="dropdown-menu" aria-label="Close"></button>
                                        </div>
                                        <div class="filter-set-view p-3">                                            
                                            <div class="accordion" id="accordionExample">
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">Name</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse show" id="collapseTwo" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <div class="mb-2">
                                                                <div class="input-icon-start input-icon position-relative">
                                                                    <span class="input-icon-addon fs-12">
                                                                        <i class="ti ti-search"></i>
                                                                    </span>
                                                                    <input type="text" class="form-control form-control-md" placeholder="Search">
                                                                </div>
                                                            </div>
                                                            <ul class="mb-0">
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-06.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>Elizabeth Morgan
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-40.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>Katherine Brooks
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-05.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia Lopez
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-10.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>John Michael
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-15.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>Natalie Brooks
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-01.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>William Turner
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-13.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>Ava Martinez
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-12.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>Nathan Reed
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-03.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>Lily Anderson
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="assets/img/users/user-18.jpg" class="flex-shrink-0 rounded-circle" alt="img"></span>Ryan Coleman
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 d-flex">Load More</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">Phone</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="collapseThree" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <ul>
                                                                <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        +1 87545 54503
                                                                    </label>
                                                                </li>
                                                                 <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        +1 98975 17485
                                                                    </label>
                                                                </li>
                                                                 <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        +1 54655 25455
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#owner" aria-expanded="false" aria-controls="owner">Email</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="owner" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <ul class="mb-0">
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="a5c0c9ccdfc4c7c0d1cde5c0ddc4c8d5c9c08bc6cac8">[email&#160;protected]</a>
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="fe959f8a969b8c97909bbe9b869f938e929bd09d9193">[email&#160;protected]</a>
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="bac9dbd7dbd4ced2dbfadfc2dbd7cad6df94d9d5d7">[email&#160;protected]</a>
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 pt-0 d-flex">Load More</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>  
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#Status" aria-expanded="false" aria-controls="Status">Status</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="Status" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <ul>
                                                                <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Active
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Inactive
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>                                             
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="javascript:void(0);" class="btn btn-outline-light w-100">Reset</a>
                                                <a href="manage-users.html" class="btn btn-primary w-100">Filter</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                                    <i class="ti ti-calendar-due text-dark fs-14 me-1"></i><span class="reportrange-picker-field">9 Jun 25 - 9 Jun 25</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">    
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort By</a>
                                    <div class="dropdown-menu">
                                        <ul>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item">Newest</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item">Oldest</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>                            
                                <div class="dropdown">
									<a href="javascript:void(0);" class="btn bg-soft-indigo px-2 border-0" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-columns-3 me-2"></i>Manage Columns</a>
									<div class="dropdown-menu dropdown-menu-md dropdown-md p-3">
                                        <ul>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Name</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Phone</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Email</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Created </span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Last Activity</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Status</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Action</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                        </ul>
									</div>
								</div>
                            </div>
                        </div>
                        <!-- table header -->

                        <!-- Contact List -->
						<div class="table-responsive custom-table">
							<table class="table table-nowrap" id="manage-users-list">
								<thead class="table-light">
									<tr>
										<th class="no-sort">
											<div class="form-check form-check-md">
                                                <input class="form-check-input" type="checkbox" id="select-all">
                                            </div>
										</th>
										<th class="no-sort"></th>
										<th>Name</th>
										<th>Phone</th>
										<th>Email</th>
										<th>Created</th>
										<th>Last Activity</th>
										<th>Status</th>
										<th class="text-end no-sort">Action</th>
									</tr>
								</thead>
								<tbody>

								</tbody>
							</table>
						</div>
						<div class="row align-items-center">
							<div class="col-md-6">
								<div class="datatable-length"></div>
							</div>
							<div class="col-md-6">
								<div class="datatable-paginate"></div>
							</div>
						</div>
						<!-- /Contact List -->
                         
                    </div>
                </div>
                <!-- card end -->

            </div>
            <!-- End Content -->            

            <!-- Start Footer -->
            <footer class="footer d-block d-md-flex justify-content-between text-md-start text-center">
               <p class="mb-md-0 mb-1">Copyright &copy; <script data-cfasync="false" src="../../cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script type="8cec8d61e74154354ce5f9fa-text/javascript">document.write(new Date().getFullYear())</script> <a href="javascript:void(0);" class="link-primary text-decoration-underline">CRMS</a></p>
               <div class="d-flex align-items-center gap-2 footer-links justify-content-center justify-content-md-end">
                  <a href="javascript:void(0);">About</a>
                  <a href="javascript:void(0);">Terms</a>
                  <a href="javascript:void(0);">Contact Us</a>
               </div>
            </footer>
            <!-- End Footer -->

        </div>

        <!-- ========================
			End Page Content
		========================= -->

    </div>
    <!-- End Wrapper -->

    <!-- Add User -->
    <div class="offcanvas offcanvas-end offcanvas-large" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="fw-semibold">Add New User</h5>
            <button type="button"
                class="btn-close custom-btn-close border p-1 me-0 d-flex align-items-center justify-content-center rounded-circle"
                data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <form action="https://crms.dreamstechnologies.com/html/template/manage-users.html">
                <div>
                    <!-- Basic Info -->
                    <div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-xxl border border-dashed me-3 flex-shrink-0">
                                        <div class="position-relative d-flex align-items-center">
                                            <i class="ti ti-photo text-dark fs-16"></i>
                                        </div>
                                    </div>
                                    <div class="d-inline-flex flex-column align-items-start">
                                        <div class="drag-upload-btn btn btn-sm btn-primary position-relative mb-2">
                                            <i class="ti ti-file-broken me-1"></i>Upload file
                                            <input type="file" class="form-control image-sign" multiple="">
                                        </div>
                                        <span>JPG, GIF or PNG. Max size of 800K</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">User Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <div class="form-check form-switch form-check-reverse">
                                            <input class="form-check-input" type="checkbox" id="switchCheckReverse">
                                            <label class="form-check-label" for="switchCheckReverse">Email Opt Out</label>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone 1 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone 2</label>
                                    <input type="text" class="form-control phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group input-group-flat pass-group">
                                        <input type="password" class="form-control pass-input">
                                        <span class="input-group-text toggle-password ">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Repeat Password <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-flat pass-group">
                                        <input type="password" class="form-control pass-input">
                                        <span class="input-group-text toggle-password ">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                    <select class="select">
                                        <option>Choose</option>
                                        <option>Germany</option>
                                        <option>USA</option>
                                        <option>Canada</option>
                                        <option>India</option>
                                        <option>China</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Basic Info -->
                </div>
                <div class="d-flex align-items-center justify-content-end">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="offcanvas">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>

    </div>
    <!-- /Add User -->

    <!-- Edit User -->
    <div class="offcanvas offcanvas-end offcanvas-large" tabindex="-1" id="offcanvas_edit">
        <div class="offcanvas-header border-bottom">
            <h5 class="fw-semibold">Edit User</h5>
            <button type="button"
                class="btn-close custom-btn-close border p-1 me-0 d-flex align-items-center justify-content-center rounded-circle"
                data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <form action="https://crms.dreamstechnologies.com/html/template/manage-users.html">
                <div>
                    <!-- Basic Info -->
                    <div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-xxl border border-dashed me-3 flex-shrink-0">
                                        <div class="position-relative d-flex align-items-center">
                                            <i class="ti ti-photo text-dark fs-16"></i>
                                        </div>
                                    </div>
                                    <div class="d-inline-flex flex-column align-items-start">
                                        <div class="drag-upload-btn btn btn-sm btn-primary position-relative mb-2">
                                            <i class="ti ti-file-broken me-1"></i>Upload file
                                            <input type="file" class="form-control image-sign" multiple="">
                                        </div>
                                        <span>JPG, GIF or PNG. Max size of 800K</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="Elizabeth Morgan">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">User Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="Elizabeth@12">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <div class="form-check form-switch form-check-reverse">
                                            <input class="form-check-input" type="checkbox" id="switchCheckReverse2" checked>
                                            <label class="form-check-label" for="switchCheckReverse2">Email Opt Out</label>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control" value="elizabeth@gmail.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="Software">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone 1 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone 2</label>
                                    <input type="text" class="form-control phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group input-group-flat pass-group">
                                        <input type="password" class="form-control pass-input">
                                        <span class="input-group-text toggle-password ">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Repeat Password <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-flat pass-group">
                                        <input type="password" class="form-control pass-input">
                                        <span class="input-group-text toggle-password ">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                    <select class="select">
                                        <option>Choose</option>
                                        <option>Germany</option>
                                        <option selected>USA</option>
                                        <option>Canada</option>
                                        <option>India</option>
                                        <option>China</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Basic Info -->
                </div>
                <div class="d-flex align-items-center justify-content-end">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="offcanvas">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>

    </div>
    <!-- /Edit User -->

    <!-- delete modal -->
    <div class="modal fade" id="delete_contact">
        <div class="modal-dialog modal-dialog-centered modal-sm rounded-0">
            <div class="modal-content rounded-0">
                <div class="modal-body p-4 text-center position-relative">
                    <div class="mb-3 position-relative z-1">
                        <span class="avatar avatar-xl badge-soft-danger border-0 text-danger rounded-circle"><i class="ti ti-trash fs-24"></i></span>
                    </div>
                    <h5 class="mb-1">Delete Confirmation</h5>
                    <p class="mb-3">Are you sure you want to remove user you selected.</p>
                    <div class="d-flex justify-content-center">
                        <a href="#" class="btn btn-light position-relative z-1 me-2 w-100" data-bs-dismiss="modal">Cancel</a>
                        <a href="#" class="btn btn-primary position-relative z-1 w-100" data-bs-dismiss="modal">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- delete modal -->

    <!-- jQuery -->
    <script src="assets/js/jquery-3.7.1.min.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>

    <!-- Bootstrap Core JS -->
    <script src="assets/js/bootstrap.bundle.min.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>  

    <!-- Daterangepikcer JS -->
	<script src="assets/js/moment.min.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>
	<script src="assets/plugins/daterangepicker/daterangepicker.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>

    <!-- Select2 JS -->
    <script src="assets/plugins/select2/js/select2.min.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>
    
    <!-- Datatable JS -->
    <script src="assets/plugins/datatables/js/jquery.dataTables.min.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>
    <script src="assets/plugins/datatables/js/dataTables.bootstrap5.min.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>

    <!-- Mobile Input -->
    <script src="assets/plugins/intltelinput/js/intlTelInput.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>

	<!-- Simplebar JS -->
	<script src="assets/plugins/simplebar/simplebar.min.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>

    <!-- Custom Json Js -->
	<script src="assets/json/manage-users-list.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>

    <!-- Main JS -->
    <script src="assets/js/script.js" type="8cec8d61e74154354ce5f9fa-text/javascript"></script>

<script src="../../cdn-cgi/scripts/7d0fa10a/cloudflare-static/rocket-loader.min.js" data-cf-settings="8cec8d61e74154354ce5f9fa-|49" defer></script><script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"rayId":"965572504a4cc833","version":"2025.7.0","serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"3ca157e612a14eccbb30cf6db6691c29","b":1}' crossorigin="anonymous"></script>
</body>


<!-- Mirrored from crms.dreamstechnologies.com/html/template/manage-users.html by HTTrack Website Copier/3.x [XR&CO'2014], Sat, 26 Jul 2025 17:01:43 GMT -->
</html>