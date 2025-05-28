<?php
// تحديد اللغة الافتراضية
$default_language = 'en';  // تغيير من 'ar' إلى 'en'

// التحقق من وجود لغة محددة في الجلسة
if (!isset($_SESSION['lang'])) {
    // محاولة تحديد اللغة من متصفح المستخدم
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
    if ($browser_lang == 'ar') {
        $_SESSION['lang'] = 'ar';
    } else {
        $_SESSION['lang'] = $default_language;
    }
}

// تغيير اللغة إذا تم طلب ذلك
if (isset($_GET['lang'])) {
    // التحقق من أن اللغة المطلوبة موجودة ونشطة في قاعدة البيانات
    require_once __DIR__ . '/db_connect.php';
    $lang_code = $_GET['lang'];
    
    $check_lang_sql = "SELECT code FROM languages WHERE code = ? AND is_active = 1";
    $check_lang_stmt = $conn->prepare($check_lang_sql);
    $check_lang_stmt->bind_param("s", $lang_code);
    $check_lang_stmt->execute();
    $check_lang_result = $check_lang_stmt->get_result();
    
    if ($check_lang_result->num_rows > 0) {
        $_SESSION['lang'] = $lang_code;
    }
    
    // استخدام الصفحة المرجعية (HTTP_REFERER) للعودة إلى نفس الصفحة
    if (isset($_SERVER['HTTP_REFERER'])) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
        
        // إزالة معلمة اللغة من الرابط إذا كانت موجودة
        $redirect_url = preg_replace('/([&?])lang=[^&]+(&|$)/', '$1', $redirect_url);
        $redirect_url = rtrim($redirect_url, '&?');
        
        // إضافة علامة الاستفهام إذا لم تكن موجودة وكان هناك معلمات
        if (strpos($redirect_url, '?') === false && !empty($query_params)) {
            $redirect_url .= '?';
        }
    } else {
        // إذا لم يكن هناك HTTP_REFERER، استخدم الصفحة الحالية
        $redirect_url = $_SERVER['REQUEST_URI'];
        $redirect_url = strtok($redirect_url, '?'); // إزالة جميع المعلمات
        
        // إعادة إضافة المعلمات باستثناء lang
        $query_params = $_GET;
        unset($query_params['lang']);
        
        if (!empty($query_params)) {
            $redirect_url .= '?' . http_build_query($query_params);
        }
    }
    
    header("Location: $redirect_url");
    exit;
}

// تحميل ملف اللغة المناسب
$current_lang = $_SESSION['lang'];
$lang_file = __DIR__ . "/../languages/$current_lang.php";

if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    // إذا لم يكن ملف اللغة موجودًا، استخدم اللغة الافتراضية
    require_once __DIR__ . "/../languages/$default_language.php";
    $_SESSION['lang'] = $default_language;
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

// الحصول على معلومات اللغة من قاعدة البيانات
function get_language_info($lang_code) {
    // تضمين ملف الاتصال بقاعدة البيانات إذا لم يكن متاحًا
    if (!isset($GLOBALS['conn']) || $GLOBALS['conn'] === null) {
        require_once __DIR__ . '/db_connect.php';
    }
    
    // استخدام المتغير العام $conn
    global $conn;
    
    // التحقق من أن الاتصال بقاعدة البيانات متاح
    if ($conn === null) {
        return ['name' => $lang_code, 'code' => $lang_code];
    }
    
    $lang_sql = "SELECT * FROM languages WHERE code = ?";
    $lang_stmt = $conn->prepare($lang_sql);
    $lang_stmt->bind_param("s", $lang_code);
    $lang_stmt->execute();
    $lang_result = $lang_stmt->get_result();
    
    if ($lang_result->num_rows > 0) {
        return $lang_result->fetch_assoc();
    }
    
    return ['name' => $lang_code, 'code' => $lang_code];
}

// الحصول على جميع اللغات النشطة
function get_active_languages() {
    // تضمين ملف الاتصال بقاعدة البيانات إذا لم يكن متاحًا
    if (!isset($GLOBALS['conn']) || $GLOBALS['conn'] === null) {
        require_once __DIR__ . '/db_connect.php';
    }
    
    // استخدام المتغير العام $conn
    global $conn;
    
    // التحقق من أن الاتصال بقاعدة البيانات متاح
    if ($conn === null) {
        return [
            ['code' => 'ar', 'name' => 'العربية'],
            ['code' => 'en', 'name' => 'English']
        ];
    }
    
    $lang_sql = "SELECT * FROM languages WHERE is_active = 1 ORDER BY name";
    $result = $conn->query($lang_sql);
    
    $languages = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $languages[] = $row;
        }
    } else {
        // إذا لم تكن هناك لغات في قاعدة البيانات، أعد القيم الافتراضية
        $languages = [
            ['code' => 'ar', 'name' => 'العربية'],
            ['code' => 'en', 'name' => 'English']
        ];
    }
    
    return $languages;
}
?>






