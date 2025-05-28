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
                    <a href="users.php" <?php echo $current_page == 'users.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-users"></i> <?php echo __('users'); ?>
                    </a>
                </li>
                <li>
                    <a href="categories.php" <?php echo $current_page == 'categories.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-th-large"></i> <?php echo __('categories'); ?>
                    </a>
                </li>
                <li>
                    <a href="providers.php" <?php echo $current_page == 'providers.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-user-tie"></i> <?php echo __('service_providers'); ?>
                    </a>
                </li>
                <li>
                    <a href="services.php" <?php echo $current_page == 'services.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-concierge-bell"></i> <?php echo __('services'); ?>
                    </a>
                </li>
                <li>
                    <a href="requests.php" <?php echo $current_page == 'requests.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-clipboard-list"></i> <?php echo __('service_requests'); ?>
                    </a>
                </li>
                <li>
                    <a href="view_message.php" <?php echo $current_page == 'view_message.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-envelope"></i> <?php echo __('messages'); ?>
                    </a>
                </li>
                <li>
                    <a href="settings.php" <?php echo $current_page == 'settings.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-cog"></i> <?php echo __('settings'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_path; ?>logout.php">
                        <i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <main class="admin-main">
        <header class="admin-header">
            <div class="admin-header-left">
                <button class="sidebar-toggle-mobile" id="sidebar-toggle-mobile">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?php echo isset($page_title) ? $page_title : __('admin_panel'); ?></h1>
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


