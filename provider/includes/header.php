<?php
// تأكد من بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول وصلاحيات مزود الخدمة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_provider']) || !$_SESSION['is_provider']) {
    header("Location: ../login.php");
    exit;
}

// تضمين ملفات التكوين
require_once '../config/db_connect.php';
require_once '../config/language.php';

// تحديد المسار الأساسي
$base_path = '../';

// الحصول على معلومات مزود الخدمة
$provider_id = 0;
$provider_sql = "SELECT id FROM service_providers WHERE user_id = ?";
$provider_stmt = $conn->prepare($provider_sql);
$provider_stmt->bind_param("i", $_SESSION['user_id']);
$provider_stmt->execute();
$provider_result = $provider_stmt->get_result();

if ($provider_result->num_rows > 0) {
    $provider_row = $provider_result->fetch_assoc();
    $provider_id = $provider_row['id'];
}

// تحديد الصفحة الحالية لتمييز الرابط النشط في القائمة
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . __('provider_panel') : __('provider_panel'); ?></title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/provider.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/rtl.css">
    <?php if (isset($page_specific_css) && !empty($page_specific_css)): ?>
    <link rel="stylesheet" href="<?php echo $base_path . $page_specific_css; ?>">
    <?php endif; ?>
</head>
<body class="provider-body">

<div class="provider-container">
    <aside class="provider-sidebar">
        <div class="provider-sidebar-header">
            <h2><?php echo __('provider_panel'); ?></h2>
            <button class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="provider-user-info">
            <div class="provider-user-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="provider-user-details">
                <h3><?php echo $_SESSION['username']; ?></h3>
                <p><?php echo __('service_provider'); ?></p>
            </div>
        </div>
        
        <nav class="provider-nav">
            <ul>
                <li>
                    <a href="dashboard.php" <?php echo $current_page == 'dashboard.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-tachometer-alt"></i> <?php echo __('dashboard'); ?>
                    </a>
                </li>
                <li>
                    <a href="profile.php" <?php echo $current_page == 'profile.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-user"></i> <?php echo __('profile'); ?>
                    </a>
                </li>
                <li>
                    <a href="services.php" <?php echo $current_page == 'services.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-concierge-bell"></i> <?php echo __('my_services'); ?>
                    </a>
                </li>
                <li>
                    <a href="requests.php" <?php echo $current_page == 'requests.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-clipboard-list"></i> <?php echo __('service_requests'); ?>
                    </a>
                </li>
                <li>
                    <a href="reviews.php" <?php echo $current_page == 'reviews.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-star"></i> <?php echo __('reviews'); ?>
                    </a>
                </li>
                <li>
                    <a href="earnings.php" <?php echo $current_page == 'earnings.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-money-bill-wave"></i> <?php echo __('earnings'); ?>
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
    
    <main class="provider-main">
        <header class="provider-header">
            <div class="provider-header-left">
                <button class="sidebar-toggle-mobile" id="sidebar-toggle-mobile">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?php echo isset($page_title) ? $page_title : __('provider_dashboard'); ?></h1>
            </div>
            
            <div class="provider-header-right">
                <div class="provider-actions">
                    <!-- <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-globe"></i>
                            <span class="d-none d-md-inline-block"><?php echo __('language'); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                            <li><a class="dropdown-item <?php echo $_SESSION['lang'] == 'ar' ? 'active' : ''; ?>" href="?lang=ar">العربية</a></li>
                            <li><a class="dropdown-item <?php echo $_SESSION['lang'] == 'en' ? 'active' : ''; ?>" href="?lang=en">English</a></li>
                        </ul>
                    </div> -->
                    
                    <button class="theme-toggle" id="theme-toggle" title="<?php echo __('toggle_theme'); ?>">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <a href="../index.php" class="btn btn-outline-primary" title="<?php echo __('view_site'); ?>">
                        <i class="fas fa-home"></i>
                        <span class="d-none d-md-inline-block"><?php echo __('view_site'); ?></span>
                    </a>
                </div>
            </div>
        </header>
        
        <div class="provider-content">



