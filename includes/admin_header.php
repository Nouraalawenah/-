<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo __('site_name'); ?> - <?php echo __('admin_panel'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container">
            <div class="admin-header-left">
                <button id="mobileMenuToggle" class="admin-mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-logo">
                    <a href="dashboard.php"><?php echo __('site_name'); ?> - <?php echo __('admin_panel'); ?></a>
                </div>
            </div>
            <div class="admin-header-right">
                <div class="admin-header-actions">
                    <a href="../index.php" class="admin-header-action" title="<?php echo __('view_site'); ?>">
                        <i class="fas fa-home"></i>
                    </a>
                    <button id="themeToggle" class="admin-header-action" title="<?php echo __('toggle_theme'); ?>">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="language-toggle admin-header-action">
                        <a href="?lang=<?php echo $_SESSION['lang'] == 'ar' ? 'en' : 'ar'; ?>" title="<?php echo __('change_language'); ?>">
                            <i class="fas fa-language"></i>
                            <span><?php echo $_SESSION['lang'] == 'ar' ? 'EN' : 'عربي'; ?></span>
                        </a>
                    </div>
                    <a href="statistics.php" class="admin-header-action" title="<?php echo __('statistics'); ?>">
                        <i class="fas fa-chart-bar"></i>
                    </a>
                </div>
                <div class="admin-user-dropdown">
                    <button class="admin-user-dropdown-toggle">
                        <img src="../images/avatar.png" alt="User Avatar" class="admin-user-avatar">
                        <span><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : __('guest'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="admin-user-dropdown-menu">
                        <a href="../profile.php"><i class="fas fa-user"></i> <?php echo __('profile'); ?></a>
                        <a href="settings.php"><i class="fas fa-cog"></i> <?php echo __('settings'); ?></a>
                        <div class="admin-dropdown-divider"></div>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-sidebar-logo">
                <i class="fas fa-tachometer-alt"></i>
                <span><?php echo __('admin_dashboard'); ?></span>
            </div>
        </div>
        <div class="admin-sidebar-content">
            <ul class="admin-menu">
                <li class="admin-menu-item">
                    <a href="dashboard.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span><?php echo __('dashboard'); ?></span>
                    </a>
                </li>
                
                <li class="admin-menu-header"><?php echo __('content_management'); ?></li>
                
                <li class="admin-menu-item">
                    <a href="manage_categories.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_categories.php' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i>
                        <span><?php echo __('manage_categories'); ?></span>
                    </a>
                </li>
                
                <li class="admin-menu-item">
                    <a href="manage_services.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_services.php' ? 'active' : ''; ?>">
                        <i class="fas fa-concierge-bell"></i>
                        <span><?php echo __('manage_services'); ?></span>
                    </a>
                </li>
                
                <li class="admin-menu-header"><?php echo __('user_management'); ?></li>
                
                <li class="admin-menu-item">
                    <a href="manage_users.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span><?php echo __('manage_users'); ?></span>
                    </a>
                </li>
                
                <li class="admin-menu-item">
                    <a href="manage_providers.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_providers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i>
                        <span><?php echo __('manage_providers'); ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <main class="admin-main">
        <div class="container">
            <div class="admin-content-right">
                <div class="admin-content-main">
                    <div class="admin-content-header">
                        <h1><?php echo isset($page_title) ? $page_title : __('dashboard'); ?></h1>
                    </div>
                    <div class="admin-content-body">
                        <div class="admin-content-section">
                            <div class="admin-content-section-header">
                                <h2><?php echo __('section_title'); ?></h2>
                            </div>
                            <div class="admin-content-section-body">
                                <p><?php echo __('section_description'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="admin-footer">
        <div class="container">
            <div class="admin-footer-left">
                <p>&copy; <?php echo date('Y'); ?> <?php echo __('site_name'); ?>. <?php echo __('all_rights_reserved'); ?></p>
            </div>
            <div class="admin-footer-right">
                <p><?php echo __('version'); ?>: <?php echo __('version_number'); ?></p>
            </div>
        </div>
    </footer>
</body>
</html>







