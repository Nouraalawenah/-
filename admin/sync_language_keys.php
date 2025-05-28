<?php
session_start();
require_once 'config/db_connect.php';

// تحديد مسارات ملفات اللغة
$ar_file = 'languages/ar.php';
$en_file = 'languages/en.php';

// التحقق من وجود الملفات
if (!file_exists($ar_file) || !file_exists($en_file)) {
    die("ملفات اللغة غير موجودة. يرجى التأكد من وجود الملفات في المجلد languages.");
}

// تحميل ملف اللغة العربية
require_once $ar_file;
$ar_keys = $lang;

// إعادة تعيين متغير $lang قبل تحميل ملف اللغة الإنجليزية
unset($lang);

// تحميل ملف اللغة الإنجليزية
require_once $en_file;
$en_keys = $lang;

// إصلاح ملف اللغة الإنجليزية
$fixed_en_keys = [
    "dir" => "ltr",
    "lang_code" => "en",
    "site_name" => "Home Services Portal",
    "home" => "Home",
    "about" => "About Us",
    "contact" => "Contact Us",
    "login" => "Login",
    "register" => "Register",
    "logout" => "Logout",
    "profile" => "Profile",
    "dashboard" => "Dashboard",
    "search" => "Search",
    "advanced_search" => "Advanced Search",
    "welcome" => "Welcome, ",
    "services" => "Our Services",
    "all_services" => "All Services",
    "service_providers" => "Service Providers",
    "contact_us" => "Contact Us",
    "footer_text" => "All Rights Reserved © 2023 Home Services Portal",
    "toggle_theme" => "Toggle Theme",
    "hero_title" => "Reliable Home Services",
    "hero_subtitle" => "We provide the best home service providers in one place",
    "our_services" => "Our Services",
    "view_details" => "View Details",
    "phone" => "Phone:",
    "call_now" => "Call Now",
    "back_to_home" => "Back to Home",
    "no_providers" => "No service providers currently available in this category.",
    "quick_links" => "Quick Links",
    "email_address" => "Email:",
    "phone_footer" => "Phone:",
    "copyright" => "All Rights Reserved.",
    "search_results" => "Search Results",
    "back_to_language_management" => "Back to Language Management"
];

// دمج المفاتيح الموجودة مع المفاتيح المصححة
$merged_en_keys = array_merge($en_keys, $fixed_en_keys);

// كتابة ملف اللغة الإنجليزية المصحح
$en_content = "<?php\n\$lang = [\n";
foreach ($merged_en_keys as $key => $value) {
    $value = str_replace('"', '\"', $value);
    $en_content .= "    \"$key\" => \"$value\",\n";
}
$en_content .= "];\n?>";

file_put_contents($en_file, $en_content);

// إصلاح ملف اللغة العربية
$fixed_ar_keys = [
    "dir" => "rtl",
    "lang_code" => "ar",
    "site_name" => "بوابة الخدمات المنزلية",
    "home" => "الرئيسية",
    "about" => "من نحن",
    "contact" => "اتصل بنا",
    "login" => "تسجيل الدخول",
    "register" => "إنشاء حساب",
    "logout" => "تسجيل الخروج",
    "profile" => "الملف الشخصي",
    "dashboard" => "لوحة التحكم",
    "search" => "بحث",
    "advanced_search" => "بحث متقدم",
    "welcome" => "مرحباً، ",
    "services" => "خدماتنا",
    "all_services" => "جميع الخدمات",
    "service_providers" => "مقدمو الخدمة",
    "contact_us" => "تواصل معنا",
    "footer_text" => "جميع الحقوق محفوظة © 2023 بوابة الخدمات المنزلية",
    "toggle_theme" => "تبديل المظهر",
    "hero_title" => "خدمات منزلية موثوقة",
    "hero_subtitle" => "نقدم أفضل مزودي الخدمات المنزلية في مكان واحد",
    "our_services" => "خدماتنا",
    "view_details" => "عرض التفاصيل",
    "phone" => "الهاتف:",
    "call_now" => "اتصل الآن",
    "back_to_home" => "العودة للرئيسية",
    "no_providers" => "لا يوجد مقدمي خدمة متاحين حال<|im_start|> في هذه الفئة.",
    "quick_links" => "روابط سريعة",
    "email_address" => "البريد الإلكتروني:",
    "phone_footer" => "الهاتف:",
    "copyright" => "جميع الحقوق محفوظة.",
    "search_results" => "نتائج البحث",
    "back_to_language_management" => "العودة إلى إدارة اللغات"
];

// دمج المفاتيح الموجودة مع المفاتيح المصححة
$merged_ar_keys = array_merge($ar_keys, $fixed_ar_keys);

// كتابة ملف اللغة العربية المصحح
$ar_content = "<?php\n\$lang = [\n";
foreach ($merged_ar_keys as $key => $value) {
    $value = str_replace('"', '\"', $value);
    $ar_content .= "    \"$key\" => \"$value\",\n";
}
$ar_content .= "];\n?>";

file_put_contents($ar_file, $ar_content);

// مزامنة المفاتيح بين الملفين
$missing_in_en = array_diff_key($merged_ar_keys, $merged_en_keys);
$missing_in_ar = array_diff_key($merged_en_keys, $merged_ar_keys);

// إضافة المفاتيح المفقودة في الإنجليزية
if (!empty($missing_in_en)) {
    $en_content = file_get_contents($en_file);
    $insertion_point = strrpos($en_content, '];');
    
    $new_keys = '';
    foreach ($missing_in_en as $key => $value) {
        $value = str_replace('"', '\"', $value);
        $new_keys .= "    \"$key\" => \"$value\",\n";
    }
    
    $en_content = substr_replace($en_content, $new_keys, $insertion_point, 0);
    file_put_contents($en_file, $en_content);
}

// إضافة المفاتيح المفقودة في العربية
if (!empty($missing_in_ar)) {
    $ar_content = file_get_contents($ar_file);
    $insertion_point = strrpos($ar_content, '];');
    
    $new_keys = '';
    foreach ($missing_in_ar as $key => $value) {
        $value = str_replace('"', '\"', $value);
        $new_keys .= "    \"$key\" => \"$value\",\n";
    }
    
    $ar_content = substr_replace($ar_content, $new_keys, $insertion_point, 0);
    file_put_contents($ar_file, $ar_content);
}

echo "تم إصلاح ملفات اللغة بنجاح!";
echo "<br><a href='index.php'>العودة إلى الصفحة الرئيسية</a>";
?>





