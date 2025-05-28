<?php
// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تعريف الثوابت الأساسية للموقع
define('SITE_NAME', 'بوابة الخدمات المنزلية');
define('SITE_URL', 'http://localhost/service%20project/home-Services/');
define('ADMIN_EMAIL', 'admin@example.com');

// إعدادات المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');

// إعدادات عرض الأخطاء (يمكن تغييرها في الإنتاج)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// مسارات المجلدات الهامة
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('IMAGES_PATH', ROOT_PATH . 'images/');
define('ADMIN_PATH', ROOT_PATH . 'admin/');
define('CSS_PATH', ROOT_PATH . 'css/');
define('JS_PATH', ROOT_PATH . 'js/');

// تحديد المسار الأساسي للموقع
$script_name = $_SERVER['SCRIPT_NAME'];
$project_folder = '/service project/home-Services/';
$base_url = '';

if (strpos($script_name, $project_folder) !== false) {
    $base_url = substr($script_name, 0, strpos($script_name, $project_folder) + strlen($project_folder));
} else {
    $base_url = '/';
}

define('BASE_URL', $base_url);

// التأكد من وجود المجلدات الضرورية
$required_dirs = [
    UPLOADS_PATH,
    IMAGES_PATH,
    IMAGES_PATH . 'providers/',
    IMAGES_PATH . 'services/',
    IMAGES_PATH . 'categories/'
];

foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// دالة للتعامل مع الأخطاء
function handleError($errno, $errstr, $errfile, $errline) {
    $error_message = "خطأ [$errno]: $errstr في $errfile على السطر $errline";
    
    // تسجيل الخطأ في ملف
    error_log($error_message, 3, ROOT_PATH . 'error.log');
    
    // إذا كان الخطأ خطيرًا، قم بإعادة توجيه المستخدم إلى صفحة خطأ
    if ($errno == E_USER_ERROR) {
        header("Location: " . SITE_URL . "error.php");
        exit;
    }
    
    // السماح بمعالجة الأخطاء الافتراضية
    return false;
}

// تعيين معالج الأخطاء المخصص
set_error_handler("handleError");

// دالة مساعدة للحصول على المسار الأساسي
function getBasePath() {
    return BASE_URL;
}

