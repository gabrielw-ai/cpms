<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_nik'])) {
    header('Location: ../view/login.php');
    exit;
}

// Get user's role and check if they are an agent
$userRole = $_SESSION['user_role'] ?? '';
$isAgent = ($userRole === 'Agent');

// Temporarily comment out UAC checks
/*
require_once 'controller/c_uac.php';

// Get user's menu access
$menuAccess = getUserMenuAccess($conn, $userRole);

// Helper function to check menu access
function canAccess($menuName, $type = 'read') {
    global $menuAccess, $userRole;
    
    // Super_User has access to everything
    if ($userRole === 'Super_User') {
        return true;
    }
    
    // For other roles, check UAC permissions
    return hasMenuAccess($menuAccess, $menuName, $type);
}
*/

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../adminlte/plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../adminlte/dist/css/adminlte.min.css">
    <?php if (isset($additional_css)) echo $additional_css; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>

        <!-- Add this to your navbar for logout -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="../controller/c_logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="../index.php" class="brand-link">
            <img src="../adminlte/dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">CPMS</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <?php if (!$isAgent): // Show menus for non-agents (including Team Leader) ?>
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a href="../index.php" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <!-- KPI Management -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>
                                    KPI Management
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../view/tbl_metrics.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>KPI Metrics</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../view/kpi_viewer.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>KPI Viewer</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../view/chart_generator.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Chart Generator</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Employee List -->
                        <li class="nav-item">
                            <a href="../view/employee_list.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Employee List</p>
                            </a>
                        </li>

                        <!-- CCS Rules Management -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>
                                    CCS Rules Management
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../view/ccs_rules_mgmt.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Add CCS Rules</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../view/ccs_viewer.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>CCS Rules Viewer</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Project Namelist -->
                        <li class="nav-item">
                            <a href="../view/project_namelist.php" class="nav-link">
                                <i class="nav-icon fas fa-project-diagram"></i>
                                <p>Project Namelist</p>
                            </a>
                        </li>

                        <!-- Role Management -->
                        <li class="nav-item">
                            <a href="../view/role_mgmt.php" class="nav-link">
                                <i class="nav-icon fas fa-user-tag"></i>
                                <p>Role Management</p>
                            </a>
                        </li>

                        <?php if ($userRole === 'Super_User'): ?>
                        <!-- UAC - Only for Super_User -->
                        <li class="nav-item">
                            <a href="../view/uac.php" class="nav-link">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>User Access Control</p>
                            </a>
                        </li>
                        <?php endif; ?>

                    <?php else: // Show only CCS Viewer for agents ?>
                        <li class="nav-item">
                            <a href="../view/ccs_viewer.php" class="nav-link">
                                <i class="nav-icon fas fa-eye"></i>
                                <p>CCS Rules Viewer</p>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $page_title; ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo $content; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2024 <a href="#">CPMS</a>.</strong>
        All rights reserved.
    </footer>
</div>

<!-- REQUIRED SCRIPTS -->
<script src="../adminlte/plugins/jquery/jquery.min.js"></script>
<script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../adminlte/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize AdminLTE sidebar
    if (typeof $.fn.overlayScrollbars !== "undefined") {
        $(".sidebar").overlayScrollbars({ 
            className: "os-theme-light",
            scrollbars: {
                autoHide: "l",
                clickScrolling: true
            }
        });
    }
    
    // Initialize active menu item
    $('a.nav-link').each(function() {
        if (this.href === window.location.href) {
            $(this).addClass('active');
            $(this).parents('.nav-item').addClass('menu-open');
            $(this).parents('.nav-treeview').prev().addClass('active');
        }
    });
});
</script>
<?php if (isset($additional_js)) echo $additional_js; ?>
</body>
</html>