<?php
require_once 'config/db_connect.php';

// تحديث الفئات
$update_categories = "UPDATE categories SET 
                     name_ar = name, 
                     name_en = name, 
                     description_ar = description, 
                     description_en = description 
                     WHERE name_ar = '' OR name_en = ''";
                     
if ($conn->query($update_categories) === TRUE) {
    echo "تم تحديث الفئات بنجاح للدعم متعدد اللغات<br>";
} else {
    echo "خطأ في تحديث الفئات: " . $conn->error . "<br>";
}

// تحديث مقدمي الخدمة
$update_providers = "UPDATE service_providers SET 
                    name_ar = name, 
                    name_en = name, 
                    description_ar = description, 
                    description_en = description 
                    WHERE name_ar = '' OR name_en = ''";
                    
if ($conn->query($update_providers) === TRUE) {
    echo "تم تحديث مقدمي الخدمة بنجاح للدعم متعدد اللغات<br>";
} else {
    echo "خطأ في تحديث مقدمي الخدمة: " . $conn->error . "<br>";
}

// تحديث الخدمات
$update_services = "UPDATE services SET 
                   name_ar = name, 
                   name_en = name, 
                   description_ar = description, 
                   description_en = description 
                   WHERE name_ar = '' OR name_en = ''";
                   
if ($conn->query($update_services) === TRUE) {
    echo "تم تحديث الخدمات بنجاح للدعم متعدد اللغات<br>";
} else {
    echo "خطأ في تحديث الخدمات: " . $conn->error . "<br>";
}

// تحديث ملفات اللغة
$ar_file = "languages/ar.php";
$en_file = "languages/en.php";

// التأكد من وجود ملفات اللغة
if (!file_exists($ar_file) || !file_exists($en_file)) {
    echo "خطأ: ملفات اللغة غير موجودة<br>";
} else {
    // تحميل ملفات اللغة
    require_once $ar_file;
    $ar_keys = $lang;
    
    // إعادة تعيين متغير $lang قبل تحميل ملف اللغة الإنجليزية
    unset($lang);
    
    require_once $en_file;
    $en_keys = $lang;
    
    // البحث عن المفاتيح الموجودة في العربية وغير موجودة في الإنجليزية
    $missing_in_en = array_diff_key($ar_keys, $en_keys);
    
    // البحث عن المفاتيح الموجودة في الإنجليزية وغير موجودة في العربية
    $missing_in_ar = array_diff_key($en_keys, $ar_keys);
    
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
        echo "تم إضافة " . count($missing_in_en) . " مفتاح مفقود إلى ملف اللغة الإنجليزية<br>";
    } else {
        echo "لا توجد مفاتيح مفقودة في ملف اللغة الإنجليزية<br>";
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
        echo "تم إضافة " . count($missing_in_ar) . " مفتاح مفقود إلى ملف اللغة العربية<br>";
    } else {
        echo "لا توجد مفاتيح مفقودة في ملف اللغة العربية<br>";
    }
    
    // إضافة زر للعودة إلى صفحة إدارة اللغات للمدير
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        echo "<br><a href='admin/manage_languages.php'>العودة إلى إدارة اللغات</a><br>";
    }
}

echo "<br><a href='index.php'>العودة إلى الصفحة الرئيسية</a>";
?>


