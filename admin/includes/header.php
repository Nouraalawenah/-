<?php
// تأكد من بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول وصلاحيات المسؤول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// تضمين ملفات التكوين
require_once '../config/db_connect.php';
require_once '../config/language.php';

// تحديد المسار الأساسي
$base_path = '../';

// تحديد الصفحة الحالية لتمييز الرابط النشط في القائمة
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo __('site_name'); ?> - <?php echo __('admin_panel'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <?php if (isset($page_specific_css) && !empty($page_specific_css)): ?>
    <link rel="stylesheet" href="<?php echo '../' . $page_specific_css; ?>">
    <?php endif; ?>
</head>
<body class="admin-body">

<div class="admin-container">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2><?php echo __('admin_panel'); ?></h2>
            <button class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="admin-user-info">
            <div class="admin-user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="admin-user-details">
                <h3><?php echo $_SESSION['username']; ?></h3>
                <p><?php echo __('admin'); ?></p>
            </div>
        </div>
        
        <nav class="admin-nav">
            <ul>
                <li>
                    <a href="dashboard.php" <?php echo $current_page == 'dashboard.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-tachometer-alt"></i> <?php echo __('dashboard'); ?>
                    </a>
                </li>
                <li>
                    <a href="manage_users.php" <?php echo $current_page == 'manage_users.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-users"></i> <?php echo __('manage_users'); ?>
                    </a>
                </li>
                <li>
                    <a href="manage_categories.php" <?php echo $current_page == 'manage_categories.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-th-large"></i> <?php echo __('manage_categories'); ?>
                    </a>
                </li>
                <li>
                    <a href="manage_providers.php" <?php echo $current_page == 'manage_providers.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-user-tie"></i> <?php echo __('manage_providers'); ?>
                    </a>
                </li>
                <li>
                    <a href="manage_services.php" <?php echo $current_page == 'manage_services.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-concierge-bell"></i> <?php echo __('manage_services'); ?>
                    </a>
                </li>
                <li>
                    <a href="manage_requests.php" <?php echo $current_page == 'manage_requests.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-clipboard-list"></i> <?php echo __('manage_requests'); ?>
                    </a>
                </li>
                <li>
                    <a href="manage_messages.php" <?php echo $current_page == 'manage_messages.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-envelope"></i> <?php echo __('messages'); ?>
                    </a>
                </li>
                <li>
                    <a href="manage_languages.php" <?php echo $current_page == 'manage_languages.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-language"></i> <?php echo __('manage_languages'); ?>
                    </a>
                </li>
                <li class="admin-sidebar-dropdown">
                    <a href="#" class="admin-sidebar-dropdown-toggle <?php echo in_array($current_page, ['system_maintenance.php', 'settings.php', 'create_image_dirs.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-tools"></i> <?php echo __('system'); ?>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="admin-sidebar-dropdown-menu">
                        <li>
                            <a href="system_maintenance.php" <?php echo $current_page == 'system_maintenance.php' ? 'class="active"' : ''; ?>>
                                <i class="fas fa-wrench"></i> <?php echo __('system_maintenance'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="settings.php" <?php echo $current_page == 'settings.php' ? 'class="active"' : ''; ?>>
                                <i class="fas fa-cog"></i> <?php echo __('settings'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="create_image_dirs.php" <?php echo $current_page == 'create_image_dirs.php' ? 'class="active"' : ''; ?>>
                                <i class="fas fa-folder-plus"></i> <?php echo __('create_image_dirs'); ?>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="<?php echo $base_path; ?>logout.php">
                        <i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <div class="admin-main-content">
        <header class="admin-header">
            <div class="admin-header-left">
                <button class="sidebar-toggle-mobile" id="sidebar-toggle-mobile">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="admin-page-title"><?php echo isset($page_title) ? $page_title : __('dashboard'); ?></h1>
            </div>
            
            <div class="admin-header-right">
                <div class="admin-search">
                    <form action="search.php" method="get">
                        <input type="text" name="q" placeholder="<?php echo __('search'); ?>...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="admin-actions">
                    <a href="<?php echo $base_path; ?>index.php" class="btn btn-outline" title="<?php echo __('view_site'); ?>">
                        <i class="fas fa-home"></i>
                    </a>
                    
                    <button class="theme-toggle" id="theme-toggle" title="<?php echo __('toggle_theme'); ?>">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <div class="admin-content">

