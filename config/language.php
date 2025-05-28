<?php
// تعيين اللغة الافتراضية
$default_language = 'ar';

// تعيين اللغة الحالية
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = $default_language;
}

// تغيير اللغة إذا تم طلب ذلك (نحتفظ بهذا للمستقبل إذا أردت إضافة لغات أخرى)
if (isset($_GET['lang'])) {
    $lang_code = $_GET['lang'];
    
    // التحقق من أن رمز اللغة صالح (نقبل فقط العربية)
    if ($lang_code == 'ar') {
        $_SESSION['lang'] = $lang_code;
    }
    
    // إعادة التوجيه إلى الصفحة نفسها بدون معلمة اللغة
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? $_SERVER['REQUEST_URI'];
    $redirect_url = strtok($redirect_url, '?'); // إزالة جميع المعلمات
    
    header("Location: $redirect_url");
    exit;
}

// تحميل ملف اللغة العربية فقط
$current_lang = 'ar';
$page_direction = 'rtl'; // تعريف متغير اتجاه الصفحة هنا
$lang_file = __DIR__ . "/../languages/ar.php";

if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    die("ملف اللغة العربية غير موجود!");
}

// دالة للحصول على ترجمة نص
function __($key) {
    global $lang;
    
    if (isset($lang[$key])) {
        return $lang[$key];
    }
    
    // إذا لم يكن المفتاح موجودًا، أعد المفتاح نفسه
    return $key;
}

// الحصول على قائمة اللغات المتاحة (نعيد فقط العربية)
function get_languages() {
    // نعيد فقط اللغة العربية
    return [
        ['code' => 'ar', 'name' => 'العربية', 'is_rtl' => 1, 'is_active' => 1, 'is_default' => true]
    ];
}
?>
