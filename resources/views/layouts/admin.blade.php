<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>@yield('title', 'Admin Dashboard') - BDB News Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="BDB News - Premium Admin Dashboard" name="description" />
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset('admin-assets/images/favicon.ico') }}">

        <!-- App css -->
        <link href="{{ asset('admin-assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('admin-assets/css/icons.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('admin-assets/css/metismenu.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('admin-assets/css/style.css') }}" rel="stylesheet" type="text/css" />

        @stack('css')
        <!-- Toastify CSS -->
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
        <style>
            .page-wrapper-img {
                position: relative;
                z-index: 2;
                pointer-events: none;
            }
            .sidebar-user, .page-title-box {
                pointer-events: auto;
            }
            .left-sidenav {
                position: relative;
                z-index: 1;
                padding-top: 90px !important;
            }
            .page-content {
                position: relative;
                z-index: 3;
                overflow-x: auto;
                min-width: 0;
            }
            /* Ensure DataTables responsive container is always scrollable */
            .dataTables_wrapper {
                overflow-x: auto;
                width: 100%;
            }
            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
        </style>
    </head>

    <body>

        <!-- Top Bar Start -->
        <div class="topbar">
             <!-- Navbar -->
             <nav class="navbar-custom">

                <!-- LOGO -->
                <div class="topbar-left">
                    <a href="{{ route('admin.dashboard') }}" class="logo">
                        <span>
                            <img src="{{ asset('admin-assets/images/logo-sm.png') }}" alt="logo-small" class="logo-sm">
                        </span>
                        <span>
                            <span class="text-white font-weight-bold logo-lg" style="font-size: 20px; letter-spacing: 1px;">BDB NEWS</span>
                        </span>
                    </a>
                </div>
    
                <ul class="list-unstyled topbar-nav float-right mb-0">

                    <li class="dropdown">
                        <a class="nav-link dropdown-toggle arrow-none waves-light waves-effect" data-toggle="dropdown" href="#" role="button"
                            aria-haspopup="false" aria-expanded="false">
                            <i class="mdi mdi-bell-outline nav-icon"></i>
                            <span class="badge badge-danger badge-pill noti-icon-badge">1</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right dropdown-lg">
                            <h6 class="dropdown-item-text">
                                Notifications
                            </h6>
                            <div class="slimscroll notification-list">
                                <a href="javascript:void(0);" class="dropdown-item notify-item active">
                                    <div class="notify-icon bg-success"><i class="mdi mdi-account-plus"></i></div>
                                    <p class="notify-details">New Admin Registered<small class="text-muted">Welcome to the control panel.</small></p>
                                </a>
                            </div>
                        </div>
                    </li>

                    <li class="dropdown">
                        <a class="nav-link dropdown-toggle waves-effect waves-light nav-user" data-toggle="dropdown" href="#" role="button"
                            aria-haspopup="false" aria-expanded="false">
                            <img src="{{ asset('admin-assets/images/users/user-1.jpg') }}" alt="profile-user" class="rounded-circle" /> 
                            <span class="ml-1 nav-user-name hidden-sm"> {{ Auth::user()->name }} <i class="mdi mdi-chevron-down"></i> </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="dripicons-user text-muted mr-2"></i> Profile Settings</a>
                            <div class="dropdown-divider"></div>
                            
                            <!-- Logout action -->
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="dripicons-exit text-muted mr-2"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
    
                <ul class="list-unstyled topbar-nav mb-0">
                    <li>
                        <button class="button-menu-mobile nav-link waves-effect waves-light">
                            <i class="mdi mdi-menu nav-icon"></i>
                        </button>
                    </li>
                    <li class="hide-phone app-search">
                        <form role="search" class="">
                            <input type="text" placeholder="Search news databases..." class="form-control">
                            <a href=""><i class="fas fa-search"></i></a>
                        </form>
                    </li>
                </ul>

             </nav>
             <!-- end navbar-->
        </div>
        <!-- Top Bar End -->

        <div class="page-wrapper-img">
            <div class="page-wrapper-img-inner">
                <div class="sidebar-user media">                    
                    <img src="{{ asset('admin-assets/images/users/user-1.jpg') }}" alt="user" class="rounded-circle img-thumbnail mb-1">
                    <span class="online-icon"><i class="mdi mdi-record text-success"></i></span>
                    <div class="media-body">
                        <h5 class="text-light">{{ Auth::user()->name }} </h5>
                        <p class="text-white-50 m-0" style="font-size: 11px;">{{ Auth::user()->email }}</p>
                        <ul class="list-unstyled list-inline mb-0 mt-2">
                            <li class="list-inline-item">
                                <a href="{{ route('profile.edit') }}" class=""><i class="mdi mdi-account text-light"></i></a>
                            </li>
                            <li class="list-inline-item">
                                <a href="javascript: void(0);" class=""><i class="mdi mdi-settings text-light"></i></a>
                            </li>
                            <li class="list-inline-item">
                                <a href="{{ route('logout') }}" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                   class=""><i class="mdi mdi-power text-danger"></i></a>
                            </li>
                        </ul>
                    </div>                    
                </div>
                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title mb-2"><i class="mdi mdi-monitor mr-2"></i>@yield('page_header', 'Dashboard')</h4>  
                            <div class="">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">BDB News</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
                                    <li class="breadcrumb-item active">@yield('page_title', 'Control Panel')</li>
                                </ol>
                            </div>                                      
                        </div>
                    </div>
                </div>
                <!-- end page title end breadcrumb -->
            </div>
        </div>
        
        <div class="page-wrapper">
            <div class="page-wrapper-inner">

                <!-- Left Sidenav -->
                <div class="left-sidenav">
                    <ul class="metismenu left-sidenav-menu" id="side-nav">
                        <li class="menu-title">Control Center</li>
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="mdi mdi-monitor"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="javascript: void(0);"><i class="mdi mdi-book-open-page-variant"></i><span>Articles</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                            <ul class="nav-second-level" aria-expanded="false">
                                <li><a href="{{ route('admin.articles.create') }}">Write Article</a></li>
                                <li><a href="{{ route('admin.articles.index') }}">Manage Articles</a></li>
                                <li><a href="#">Categories</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="javascript: void(0);"><i class="mdi mdi-clipboard-outline"></i><span>Users Database</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                            <ul class="nav-second-level" aria-expanded="false">
                                <li><a href="#">All Accounts</a></li>
                                <li><a href="#">Permissions</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="javascript: void(0);"><i class="mdi mdi-lock-outline"></i><span>Authentication Info</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                            <ul class="nav-second-level" aria-expanded="false">
                                <li><a href="{{ route('profile.edit') }}">Admin Profile</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="javascript: void(0);"><i class="mdi mdi-settings-outline"></i><span>Settings</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                            <ul class="nav-second-level" aria-expanded="false">
                                <li><a href="{{ route('admin.settings.general') }}">General Settings</a></li>
                                <li><a href="{{ route('admin.settings.ai') }}">AI Integration</a></li>
                                <li><a href="{{ route('admin.settings.n8n') }}">n8n Setup</a></li>
                                <li><a href="{{ route('admin.ai-sources.index') }}">AI Sources</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <!-- end left-sidenav-->

                <!-- Page Content-->
                <div class="page-content">
                    <div class="container-fluid"> 
                        @yield('content')
                    </div><!-- container -->

                    <footer class="footer text-center text-sm-left">
                        &copy; 2026 BDB News <span class="text-muted d-none d-sm-inline-block float-right">Crafted with <i class="mdi mdi-heart text-danger"></i> for XAMPP Localhost</span>
                    </footer>
                </div>
                <!-- end page content -->
            </div>
        </div>
        <!-- end page-wrapper -->

        @stack('modals')

        <!-- Logout Form -->
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>

        <!-- jQuery  -->
        <script src="{{ asset('admin-assets/js/jquery.min.js') }}"></script>
        <script src="{{ asset('admin-assets/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('admin-assets/js/metisMenu.min.js') }}"></script>
        <script src="{{ asset('admin-assets/js/waves.min.js') }}"></script>
        <script src="{{ asset('admin-assets/js/jquery.slimscroll.min.js') }}"></script>

        @stack('js')

        <!-- App js -->
        <script src="{{ asset('admin-assets/js/app.js') }}"></script>
        
        <!-- Toastify JS -->
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

        <!-- Real-time AI Generation Polling Script -->
        <script>
            $(document).ready(function() {
                // Initial load time
                let lastCheckedAt = new Date().toISOString();
                
                // Base64 short sounds to avoid external dependencies
                const successSound = new Audio('data:audio/mp3;base64,//NExAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq//NExAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq'); // Fallback tiny silent mp3, we will use a better beep below
                const beepSound = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                const errorSound = new Audio('https://actions.google.com/sounds/v1/alarms/bugle_tune.ogg'); // Using a distinct sound for error

                function fetchLatestLogs() {
                    $.ajax({
                        url: "{{ route('admin.api.latest-logs') }}",
                        method: "GET",
                        data: { last_checked_at: lastCheckedAt },
                        success: function(response) {
                            if(response.logs && response.logs.length > 0) {
                                // Update last checked time to the latest log's created_at
                                lastCheckedAt = response.logs[response.logs.length - 1].created_at;
                                
                                let hasError = false;
                                let hasSuccess = false;

                                response.logs.forEach(log => {
                                    if(log.status === 'success') {
                                        hasSuccess = true;
                                        Toastify({
                                            text: "✅ New Article Generated: " + (log.article ? log.article.title : "Unknown Title"),
                                            duration: 5000,
                                            close: true,
                                            gravity: "top", 
                                            position: "right",
                                            style: { background: "linear-gradient(to right, #00b09b, #96c93d)" },
                                            onClick: function(){} 
                                        }).showToast();
                                        
                                        // Update notification bell (basic increment)
                                        let badge = $('.noti-icon-badge');
                                        badge.text(parseInt(badge.text() || 0) + 1);
                                    } 
                                    else if (log.status === 'failed') {
                                        hasError = true;
                                        let errorText = log.error_message || "Unknown error occurred.";
                                        
                                        Toastify({
                                            text: `❌ AI Generation Failed! Click to copy error.\nSource: ${log.source_name}`,
                                            duration: 10000,
                                            close: true,
                                            gravity: "top", 
                                            position: "right",
                                            style: { background: "linear-gradient(to right, #ff5f6d, #ffc371)", color: "#000" },
                                            onClick: function(){
                                                navigator.clipboard.writeText(errorText);
                                                alert("Error copied to clipboard!");
                                            }
                                        }).showToast();
                                    }
                                });

                                // Play sounds
                                if (hasError) {
                                    errorSound.play().catch(e => console.log("Audio play blocked by browser"));
                                } else if (hasSuccess) {
                                    beepSound.play().catch(e => console.log("Audio play blocked by browser"));
                                }
                            }
                        }
                    });
                }

                // Poll every 15 seconds
                setInterval(fetchLatestLogs, 15000);
            });
        </script>
    </body>
</html>
