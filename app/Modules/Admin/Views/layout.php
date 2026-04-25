<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?> - Admin - <?= config('App')->appName ?? 'CIMembership' ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #4e73df 0%, #224abe 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand h4 {
            margin: 0;
            font-weight: 600;
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .nav-item {
            padding: 0;
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.1);
        }

        .nav-link i {
            width: 24px;
            margin-right: 0.75rem;
            font-size: 0.875rem;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            background: white;
            height: 70px;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .page-content {
            padding: 1.5rem;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }

        .stat-card {
            border-left: 4px solid;
        }

        .stat-card.primary { border-color: #4e73df; }
        .stat-card.success { border-color: #1cc88a; }
        .stat-card.info { border-color: #36b9cc; }
        .stat-card.warning { border-color: #f6c23e; }

        /* Tables */
        .table thead th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 0.5rem;
        }

        /* User Dropdown */
        .user-dropdown .dropdown-toggle::after {
            display: none;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fas fa-shield-alt me-2"></i>CIMembership</h4>
        </div>

        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= url_is('/admin') || url_is('/admin/dashboard') ? 'active' : '' ?>" href="/admin/dashboard">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= url_is('/admin/users*') ? 'active' : '' ?>" href="/admin/users">
                        <i class="fas fa-users"></i>Users
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= url_is('/admin/groups*') ? 'active' : '' ?>" href="/admin/groups">
                        <i class="fas fa-user-shield"></i>User Groups
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= url_is('/admin/settings*') ? 'active' : '' ?>" href="/admin/settings">
                        <i class="fas fa-cog"></i>Settings
                    </a>
                </li>

                <hr class="my-3 mx-3" style="border-color: rgba(255,255,255,0.1);">

                <li class="nav-item">
                    <a class="nav-link" href="/" target="_blank">
                        <i class="fas fa-external-link-alt"></i>View Site
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/auth/logout">
                        <i class="fas fa-sign-out-alt"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h5 class="mb-0"><?= esc($title ?? 'Dashboard') ?></h5>

            <div class="d-flex align-items-center">
                <div class="dropdown user-dropdown">
                    <a class="btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/auth/profile"><i class="fas fa-user me-2"></i>My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/auth/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= is_array(session()->getFlashdata('error'))
                        ? implode('<br>', session()->getFlashdata('error'))
                        : session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
