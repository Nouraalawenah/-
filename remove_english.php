<?php
require_once 'config/db_connect.php';

echo "<h1>إزالة اللغة الإنجليزية من النظام</h1>";

// 1. تحديث هيكل قاعدة البيانات لإزالة الأعمدة الإنجليزية

// تحديث جدول الفئات
try {
    // التحقق من وجود الأعمدة الإنجليزية
    $check_columns = "SHOW COLUMNS FROM categories LIKE 'name_en'";
    $result = $conn->query($check_columns);
    
    if ($result->num_rows > 0) {
        // نقل البيانات العربية إلى الأعمدة الرئيسية
        $update_categories = "UPDATE categories SET 
                             name = name_ar, 
                             description = description_ar 
                             WHERE name_ar IS NOT NULL AND name_ar != ''";
                             
        if ($conn->query($update_categories) === TRUE) {
            echo "<p>تم تحديث بيانات الفئات بنجاح</p>";
        } else {
            echo "<p>خطأ في تحديث بيانات الفئات: " . $conn->error . "</p>";
        }
        
        // حذف الأعمدة الإنجليزية
        $drop_columns = "ALTER TABLE categories 
                        DROP COLUMN name_en,
                        DROP COLUMN description_en";
                        
        if ($conn->query($drop_columns) === TRUE) {
            echo "<p>تم حذف الأعمدة الإنجليزية من جدول الفئات بنجاح</p>";
        } else {
            echo "<p>خطأ في حذف الأعمدة الإنجليزية من جدول الفئات: " . $conn->error . "</p>";
        }
        
        // إعادة تسمية الأعمدة العربية
        $rename_columns = "ALTER TABLE categories 
                          CHANGE name_ar name VARCHAR(100) NOT NULL,
                          CHANGE description_ar description TEXT";
                          
        if ($conn->query($rename_columns) === TRUE) {
            echo "<p>تم إعادة تسمية الأعمدة العربية في جدول الفئات بنجاح</p>";
        } else {
            echo "<p>خطأ في إعادة تسمية الأعمدة العربية في جدول الفئات: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>جدول الفئات محدث بالفعل (لا توجد أعمدة إنجليزية)</p>";
    }
} catch (Exception $e) {
    echo "<p>خطأ في تحديث جدول الفئات: " . $e->getMessage() . "</p>";
}

// تحديث جدول مقدمي الخدمة
try {
    // التحقق من وجود الأعمدة الإنجليزية
    $check_columns = "SHOW COLUMNS FROM service_providers LIKE 'name_en'";
    $result = $conn->query($check_columns);
    
    if ($result->num_rows > 0) {
        // نقل البيانات العربية إلى الأعمدة الرئيسية
        $update_providers = "UPDATE service_providers SET 
                            name = name_ar, 
                            description = description_ar 
                            WHERE name_ar IS NOT NULL AND name_ar != ''";
                            
        if ($conn->query($update_providers) === TRUE) {
            echo "<p>تم تحديث بيانات مقدمي الخدمة بنجاح</p>";
        } else {
            echo "<p>خطأ في تحديث بيانات مقدمي الخدمة: " . $conn->error . "</p>";
        }
        
        // حذف الأعمدة الإنجليزية
        $drop_columns = "ALTER TABLE service_providers 
                        DROP COLUMN name_en,
                        DROP COLUMN description_en";
                        
        if ($conn->query($drop_columns) === TRUE) {
            echo "<p>تم حذف الأعمدة الإنجليزية من جدول مقدمي الخدمة بنجاح</p>";
        } else {
            echo "<p>خطأ في حذف الأعمدة الإنجليزية من جدول مقدمي الخدمة: " . $conn->error . "</p>";
        }
        
        // إعادة تسمية الأعمدة العربية
        $rename_columns = "ALTER TABLE service_providers 
                          CHANGE name_ar name VARCHAR(100) NOT NULL,
                          CHANGE description_ar description TEXT";
                          
        if ($conn->query($rename_columns) === TRUE) {
            echo "<p>تم إعادة تسمية الأعمدة العربية في جدول مقدمي الخدمة بنجاح</p>";
        } else {
            echo "<p>خطأ في إعادة تسمية الأعمدة العربية في جدول مقدمي الخدمة: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>جدول مقدمي الخدمة محدث بالفعل (لا توجد أعمدة إنجليزية)</p>";
    }
} catch (Exception $e) {
    echo "<p>خطأ في تحديث جدول مقدمي الخدمة: " . $e->getMessage() . "</p>";
}

// تحديث جدول الخدمات
try {
    // التحقق من وجود الأعمدة الإنجليزية
    $check_columns = "SHOW COLUMNS FROM services LIKE 'name_en'";
    $result = $conn->query($check_columns);
    
    if ($result->num_rows > 0) {
        // نقل البيانات العربية إلى الأعمدة الرئيسية
        $update_services = "UPDATE services SET 
                           name = name_ar, 
                           description = description_ar 
                           WHERE name_ar IS NOT NULL AND name_ar != ''";
                           
        if ($conn->query($update_services) === TRUE) {
            echo "<p>تم تحديث بيانات الخدمات بنجاح</p>";
        } else {
            echo "<p>خطأ في تحديث بيانات الخدمات: " . $conn->error . "</p>";
        }
        
        // حذف الأعمدة الإنجليزية
        $drop_columns = "ALTER TABLE services 
                        DROP COLUMN name_en,
                        DROP COLUMN description_en";
                        
        if ($conn->query($drop_columns) === TRUE) {
            echo "<p>تم حذف الأعمدة الإنجليزية من جدول الخدمات بنجاح</p>";
        } else {
            echo "<p>خطأ في حذف الأعمدة الإنجليزية من جدول الخدمات: " . $conn->error . "</p>";
        }
        
        // إعادة تسمية الأعمدة العربية
        $rename_columns = "ALTER TABLE services 
                          CHANGE name_ar name VARCHAR(100) NOT NULL,
                          CHANGE description_ar description TEXT";
                          
        if ($conn->query($rename_columns) === TRUE) {
            echo "<p>تم إعادة تسمية الأعمدة العربية في جدول الخدمات بنجاح</p>";
        } else {
            echo "<p>خطأ في إعادة تسمية الأعمدة العربية في جدول الخدمات: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>جدول الخدمات محدث بالفعل (لا توجد أعمدة إنجليزية)</p>";
    }
} catch (Exception $e) {
    echo "<p>خطأ في تحديث جدول الخدمات: " . $e->getMessage() . "</p>";
}

// 2. تحديث جدول اللغات
try {
    // حذف جميع اللغات باستثناء العربية
    $delete_languages = "DELETE FROM languages WHERE code != 'ar'";
    if ($conn->query($delete_languages) === TRUE) {
        echo "<p>تم حذف جميع اللغات باستثناء العربية بنجاح</p>";
    } else {
        echo "<p>خطأ في حذف اللغات: " . $conn->error . "</p>";
    }
    
    // تعيين اللغة العربية كلغة افتراضية
    $update_arabic = "UPDATE languages SET is_default = 1, is_active = 1 WHERE code = 'ar'";
    if ($conn->query($update_arabic) === TRUE) {
        echo "<p>تم تعيين اللغة العربية كلغة افتراضية بنجاح</p>";
    } else {
        echo "<p>خطأ في تعيين اللغة العربية كلغة افتراضية: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p>خطأ في تحديث جدول اللغات: " . $e->getMessage() . "</p>";
}

// 3. تحديث ملفات اللغة
try {
    // حذف ملف اللغة الإنجليزية
    $en_file = "languages/en.php";
    if (file_exists($en_file)) {
        if (unlink($en_file)) {
            echo "<p>تم حذف ملف اللغة الإنجليزية بنجاح</p>";
        } else {
            echo "<p>خطأ في حذف ملف اللغة الإنجليزية</p>";
        }
    } else {
        echo "<p>ملف اللغة الإنجليزية غير موجود بالفعل</p>";
    }
    
    // حذف نسخ ملفات اللغة الإنجليزية
    $en_copies = glob("languages/en*.php");
    foreach ($en_copies as $file) {
        if (unlink($file)) {
            echo "<p>تم حذف ملف " . basename($file) . " بنجاح</p>";
        } else {
            echo "<p>خطأ في حذف ملف " . basename($file) . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>خطأ في تحديث ملفات اللغة: " . $e->getMessage() . "</p>";
}

// 4. تحديث ملف تكوين اللغة
try {
    // تحديث ملف language.php
    $language_file = "config/language.php";
    $language_content = '<?php
// تعيين اللغة الافتراضية
$default_language = \'ar\';

// تعيين اللغة الحالية
if (!isset($_SESSION[\'lang\'])) {
    $_SESSION[\'lang\'] = $default_language;
}

// تغيير اللغة إذا تم طلب ذلك (نحتفظ بهذا للمستقبل إذا أردت إضافة لغات أخرى)
if (isset($_GET[\'lang\'])) {
    $lang_code = $_GET[\'lang\'];
    
    // التحقق من أن رمز اللغة صالح (نقبل فقط العربية)
    if ($lang_code == \'ar\') {
        $_SESSION[\'lang\'] = $lang_code;
    }
    
    // إعادة التوجيه إلى الصفحة نفسها بدون معلمة اللغة
    $redirect_url = $_SERVER[\'HTTP_REFERER\'] ?? $_SERVER[\'REQUEST_URI\'];
    $redirect_url = strtok($redirect_url, \'?\'); // إزالة جميع المعلمات
    
    header("Location: $redirect_url");
    exit;
}

// تحميل ملف اللغة العربية فقط
$current_lang = \'ar\';
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
        [\'code\' => \'ar\', \'name\' => \'العربية\', \'is_rtl\' => 1, \'is_active\' => 1, \'is_default\' => true]
    ];
}
?>';

    if (file_put_contents($language_file, $language_content)) {
        echo "<p>تم تحديث ملف تكوين اللغة بنجاح</p>";
    } else {
        echo "<p>خطأ في تحديث ملف تكوين اللغة</p>";
    }
} catch (Exception $e) {
    echo "<p>خطأ في تحديث ملف تكوين اللغة: " . $e->getMessage() . "</p>";
}

// 5. حذف صور الأعلام الإنجليزية
try {
    $en_flag = "images/flags/en.png";
    if (file_exists($en_flag)) {
        if (unlink($en_flag)) {
            echo "<p>تم حذف صورة العلم الإنجليزي بنجاح</p>";
        } else {
            echo "<p>خطأ في حذف صورة العلم الإنجليزي</p>";
        }
    } else {
        echo "<p>صورة العلم الإنجليزي غير موجودة بالفعل</p>";
    }
} catch (Exception $e) {
    echo "<p>خطأ في حذف صور الأعلام: " . $e->getMessage() . "</p>";
}

// 6. تحديث جدول الإعدادات (إذا كان موجودًا)
try {
    // التحقق من وجود جدول الإعدادات
    $check_table = "SHOW TABLES LIKE 'settings'";
    $result = $conn->query($check_table);
    
    if ($result->num_rows > 0) {
        // تحديث اللغة الافتراضية في الإعدادات
        $update_settings = "UPDATE settings SET setting_value = 'ar' WHERE setting_key = 'default_language'";
        if ($conn->query($update_settings) === TRUE) {
            echo "<p>تم تحديث اللغة الافتراضية في الإعدادات بنجاح</p>";
        } else {
            echo "<p>خطأ في تحديث اللغة الافتراضية في الإعدادات: " . $conn->error . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>خطأ في تحديث جدول الإعدادات: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php' class='btn'>العودة إلى الصفحة الرئيسية</a></p>";
?>
