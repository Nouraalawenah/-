<?php
// تأكد من بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تحديد المسار الأساسي بشكل أكثر دقة
$base_path = '';

// الحصول على المسار النسبي للملف الحالي
$current_script = $_SERVER['SCRIPT_NAME'];
$current_dir = dirname($current_script);

// تحديد عدد المستويات من الجذر
$project_root = '/home-Services';  // تعديل هذا ليطابق مسار المشروع الخاص بك
$depth = substr_count(str_replace($project_root, '', $current_dir), '/');

// بناء المسار الأساسي
if ($depth > 0) {
    $base_path = str_repeat('../', $depth);
}

// تضمين ملفات التكوين
if (file_exists($base_path . 'config/db_connect.php')) {
    require_once $base_path . 'config/db_connect.php';
} else if (file_exists(__DIR__ . '/../config/db_connect.php')) {
    require_once __DIR__ . '/../config/db_connect.php';
} else {
    require_once 'config/db_connect.php';
}

if (file_exists($base_path . 'config/language.php')) {
    require_once $base_path . 'config/language.php';
} else if (file_exists(__DIR__ . '/../config/language.php')) {
    require_once __DIR__ . '/../config/language.php';
} else {
    require_once 'config/language.php';
}

// تعريف متغير اتجاه الصفحة
$page_direction = 'rtl'; // دائمًا RTL للغة العربية

// تحديد المسار الأساسي بشكل ثابت كحل بديل إذا فشلت الطريقة الأولى
if (empty($base_path) || $base_path === './') {
    // تحديد المسار الأساسي بشكل ثابت (يمكن تعديله حسب هيكل المشروع)
    $base_path = '/home-Services/';
}

// التأكد من أن المسار الأساسي ينتهي بـ /
if (substr($base_path, -1) !== '/') {
    $base_path .= '/';
}

// تحديد الصفحة الحالية لتمييز الرابط النشط في القائمة
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . __('site_name') : __('site_name'); ?></title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/rtl.css">
    <?php if (isset($page_specific_css) && !empty($page_specific_css)): ?>
        <link rel="stylesheet" href="<?php echo $base_path . $page_specific_css; ?>">
    <?php endif; ?>
    <script>
        // التحقق من وجود تفضيل السمة المظلمة في التخزين المحلي
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
                const themeToggle = document.getElementById('theme-toggle');
                if (themeToggle) {
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                }
            }
        });
    </script>
</head>

<body>

    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="<?php echo $base_path; ?>">
                    <img src="<?php echo $base_path; ?>images/logo.jpg" alt="<?php echo __('site_name'); ?>" class="logo-image">
                    <span><?php echo __('site_name'); ?></span>
                </a>
            </div>

            <button class="mobile-menu-toggle" aria-label="<?php echo __('toggle_menu'); ?>">
                <i class="fas fa-bars"></i>
            </button>

            <div class="header-right">
                <nav class="main-nav">
                    <ul>
                        <li><a href="<?php echo $base_path; ?>" <?php echo ($current_page == 'index.php') ? 'class="active"' : ''; ?>><i class="fas fa-home"></i> <?php echo __('home'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>services.php" <?php echo ($current_page == 'services.php') ? 'class="active"' : ''; ?>><i class="fas fa-concierge-bell"></i> <?php echo __('services'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>providers.php" <?php echo ($current_page == 'providers.php') ? 'class="active"' : ''; ?>><i class="fas fa-user-tie"></i> <?php echo __('service_providers'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>about.php" <?php echo ($current_page == 'about.php') ? 'class="active"' : ''; ?>><i class="fas fa-info-circle"></i> <?php echo __('about_us'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>contact.php" <?php echo ($current_page == 'contact.php') ? 'class="active"' : ''; ?>><i class="fas fa-envelope"></i> <?php echo __('contact_us'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>advanced_search.php" <?php echo ($current_page == 'advanced_search.php') ? 'class="active"' : ''; ?>><i class="fas fa-search"></i> <?php echo __('advanced_search'); ?></a></li>
                    </ul>
                </nav>

                <div class="user-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="dropdown-toggle">
                                <?php if (!empty($_SESSION['user_image'])): ?>
                                    <img src="<?php echo $base_path; ?>uploads/users/<?php echo $_SESSION['user_image']; ?>" alt="<?php echo $_SESSION['username']; ?>" class="user-avatar">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span><?php echo $_SESSION['username']; ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                    <a href="<?php echo $base_path; ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> <?php echo __('admin_dashboard'); ?></a>
                                <?php elseif (isset($_SESSION['is_provider']) && $_SESSION['is_provider'] == 1): ?>
                                    <a href="<?php echo $base_path; ?>provider/dashboard.php"><i class="fas fa-tachometer-alt"></i> <?php echo __('provider_dashboard'); ?></a>
                                <?php else: ?>
                                    <a href="<?php echo $base_path; ?>dashboard.php"><i class="fas fa-tachometer-alt"></i> <?php echo __('dashboard'); ?></a>
                                <?php endif; ?>
                                <a href="<?php echo $base_path; ?>profile.php"><i class="fas fa-user"></i> <?php echo __('profile'); ?></a>
                                <a href="<?php echo $base_path; ?>logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $base_path; ?>login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?></a>
                        <a href="<?php echo $base_path; ?>register.php" class="btn"><i class="fas fa-user-plus"></i> <?php echo __('register'); ?></a>
                    <?php endif; ?>
                </div>

                <!-- language-dropdown -->
                <!-- <div class="language-dropdown">
                <button class="current-lang">
                    <img src="<?php echo $base_path; ?>images/flags/<?php echo $_SESSION['lang']; ?>.png" alt="<?php echo $_SESSION['lang']; ?>">
                    <span><?php echo strtoupper($_SESSION['lang']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="lang-options">
                    <a href="?lang=ar" <?php echo ($_SESSION['lang'] == 'ar') ? 'class="active"' : ''; ?>>
                        <img src="<?php echo $base_path; ?>images/flags/ar.png" alt="Arabic">
                        <span>العربية</span>
                    </a>
                    <a href="?lang=en" <?php echo ($_SESSION['lang'] == 'en') ? 'class="active"' : ''; ?>>
                        <img src="<?php echo $base_path; ?>images/flags/en.png" alt="English">
                        <span>English</span>
                    </a>
                </div>
            </div> -->

                    <button id="theme-toggle" class="theme-toggle" aria-label="<?php echo __('toggle_theme'); ?>">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>














    <!-- Debug output for language dropdown -->
    <?php if (isset($_GET['debug'])): ?>
        <div style="background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">
            <h4>Debug: Language Information</h4>
            <p>Current Language: <?php echo $_SESSION['lang']; ?></p>
            <p>Available Languages:</p>
            <pre><?php print_r($active_languages); ?></pre>
        </div>
    <?php endif; ?>


