<?php
require_once 'config/db_connect.php';

// التحقق من وجود العمود
$check_column = "SHOW COLUMNS FROM users LIKE 'is_provider'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // إضافة العمود إذا لم يكن موجودًا
    $add_column = "ALTER TABLE users ADD COLUMN is_provider BOOLEAN DEFAULT 0";
    if ($conn->query($add_column) === TRUE) {
        echo "تم إضافة عمود is_provider إلى جدول users بنجاح";
    } else {
        echo "خطأ في إضافة العمود: " . $conn->error;
    }
} else {
    echo "العمود is_provider موجود بالفعل في جدول users";
}

// إضافة عمود user_id إلى جدول service_providers إذا لم يكن موجودًا
$check_user_id_column = "SHOW COLUMNS FROM service_providers LIKE 'user_id'";
$result = $conn->query($check_user_id_column);

if ($result->num_rows == 0) {
    $add_user_id_column = "ALTER TABLE service_providers ADD COLUMN user_id INT, ADD FOREIGN KEY (user_id) REFERENCES users(id)";
    if ($conn->query($add_user_id_column) === TRUE) {
        echo "<br>تم إضافة عمود user_id إلى جدول service_providers بنجاح";
    } else {
        echo "<br>خطأ في إضافة العمود: " . $conn->error;
    }
} else {
    echo "<br>العمود user_id موجود بالفعل في جدول service_providers";
}

// التحقق من وجود قيم افتراضية للحقول المطلوبة
$check_image_column = "SHOW COLUMNS FROM service_providers LIKE 'image'";
$result = $conn->query($check_image_column);

if ($result->num_rows > 0) {
    $column_info = $result->fetch_assoc();
    if ($column_info['Default'] === NULL) {
        // تعديل العمود لإضافة قيمة افتراضية
        $modify_image_column = "ALTER TABLE service_providers MODIFY COLUMN image VARCHAR(100) DEFAULT 'default.jpg'";
        if ($conn->query($modify_image_column) === TRUE) {
            echo "<br>تم تعديل عمود image لإضافة قيمة افتراضية";
        } else {
            echo "<br>خطأ في تعديل عمود image: " . $conn->error;
        }
    }
}

// لا يمكن إضافة قيمة افتراضية لعمود TEXT في MySQL
// بدلاً من ذلك، سنتأكد من إدخال قيمة فارغة عند إنشاء سجلات جديدة

// التأكد من وجود صورة افتراضية
$default_avatar_path = "images/default.jpg";
if (!file_exists($default_avatar_path)) {
    echo "<br>تنبيه: الصورة الافتراضية غير موجودة في المسار: " . $default_avatar_path;
    echo "<br>يرجى إضافة صورة افتراضية بهذا الاسم.";
}

echo "<br><a href='index.php'>العودة إلى الصفحة الرئيسية</a>";
?>

