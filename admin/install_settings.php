<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// إنشاء جدول الإعدادات إذا لم يكن موجودًا
$create_table_sql = "CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text DEFAULT NULL,
    `setting_description` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($create_table_sql) === TRUE) {
    echo "تم إنشاء جدول الإعدادات بنجاح.<br>";
} else {
    echo "خطأ في إنشاء جدول الإعدادات: " . $conn->error . "<br>";
    exit;
}

// التحقق من وجود الإعدادات الافتراضية
$check_settings_sql = "SELECT COUNT(*) as count FROM settings";
$result = $conn->query($check_settings_sql);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // إضافة الإعدادات الافتراضية
    $default_settings = [
        [
            'site_name_ar', 
            'بوابة الخدمات المنزلية', 
            'اسم الموقع باللغة العربية'
        ],
        [
            'site_name_en', 
            'Home Services Portal', 
            'اسم الموقع باللغة الإنجليزية'
        ],
        [
            'site_email', 
            'info@homeservices.com', 
            'البريد الإلكتروني الرسمي للموقع'
        ],
        [
            'site_phone', 
            '+966500000000', 
            'رقم الهاتف الرسمي للموقع'
        ],
        [
            'default_language', 
            'ar', 
            'اللغة الافتراضية للموقع'
        ],
        [
            'maintenance_mode', 
            '0', 
            'تفعيل وضع الصيانة (1 = مفعل، 0 = غير مفعل)'
        ],
        [
            'allow_registration', 
            '1', 
            'السماح بتسجيل مستخدمين جدد (1 = مسموح، 0 = غير مسموح)'
        ],
        [
            'email_verification', 
            '0', 
            'طلب تأكيد البريد الإلكتروني عند التسجيل (1 = مطلوب، 0 = غير مطلوب)'
        ],
        [
            'admin_approve_providers', 
            '1', 
            'طلب موافقة المدير على مقدمي الخدمة الجدد (1 = مطلوب، 0 = غير مطلوب)'
        ],
        [
            'services_per_page', 
            '12', 
            'عدد الخدمات المعروضة في كل صفحة'
        ],
        [
            'allow_ratings', 
            '1', 
            'السماح بتقييم الخدمات (1 = مسموح، 0 = غير مسموح)'
        ],
        [
            'allow_comments', 
            '1', 
            'السماح بالتعليقات على الخدمات (1 = مسموح، 0 = غير مسموح)'
        ],
        [
            'site_logo', 
            'logo.jpg', 
            'مسار شعار الموقع'
        ]
    ];
    
    // إعداد استعلام الإدخال
    $insert_sql = "INSERT INTO settings (setting_key, setting_value, setting_description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    
    // إدخال كل إعداد
    foreach ($default_settings as $setting) {
        $stmt->bind_param("sss", $setting[0], $setting[1], $setting[2]);
        if ($stmt->execute()) {
            echo "تم إضافة الإعداد: " . $setting[0] . "<br>";
        } else {
            echo "خطأ في إضافة الإعداد " . $setting[0] . ": " . $stmt->error . "<br>";
        }
    }
    
    echo "تم إضافة جميع الإعدادات الافتراضية بنجاح.<br>";
} else {
    echo "الإعدادات موجودة بالفعل في قاعدة البيانات.<br>";
}

// إنشاء ملف CSS للمتغيرات
$css_variables = ":root {
    --primary-color: #4e73df;
    --secondary-color: #858796;
    --success-color: #1cc88a;
    --info-color: #36b9cc;
    --warning-color: #f6c23e;
    --danger-color: #e74a3b;
    --light-color: #f8f9fc;
    --dark-color: #5a5c69;
    --bg-color: #f8f9fc;
    --text-color: #333;
    --border-color: #e3e6f0;
    --header-bg: #fff;
    --footer-bg: #fff;
    --card-bg: #fff;
    --input-bg: #fff;
    --input-border: #d1d3e2;
    --input-focus-border: #bac8f3;
    --font-family: 'Tajawal', Arial, sans-serif;
}

[data-theme='dark'] {
    --primary-color: #4e73df;
    --secondary-color: #858796;
    --success-color: #1cc88a;
    --info-color: #36b9cc;
    --warning-color: #f6c23e;
    --danger-color: #e74a3b;
    --light-color: #f8f9fc;
    --dark-color: #5a5c69;
    --bg-color: #1a1a1a;
    --text-color: #f8f9fc;
    --border-color: #333;
    --header-bg: #222;
    --footer-bg: #222;
    --card-bg: #2a2a2a;
    --input-bg: #333;
    --input-border: #444;
    --input-focus-border: #4e73df;
}";

$css_file_path = "../css/variables.css";
if (file_put_contents($css_file_path, $css_variables)) {
    echo "تم إنشاء ملف متغيرات CSS بنجاح.<br>";
} else {
    echo "خطأ في إنشاء ملف متغيرات CSS.<br>";
}

echo "<br><a href='dashboard.php' class='btn btn-primary'>العودة إلى لوحة التحكم</a>";
?>

