<?php
require_once 'config/db_connect.php';

// التحقق من وجود العمود
$check_column = "SHOW COLUMNS FROM users LIKE 'image'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // إضافة العمود إذا لم يكن موجودًا
    $add_column = "ALTER TABLE users ADD COLUMN image VARCHAR(100) DEFAULT NULL";
    if ($conn->query($add_column) === TRUE) {
        echo "تم إضافة عمود image إلى جدول users بنجاح";
    } else {
        echo "خطأ في إضافة العمود: " . $conn->error;
    }
} else {
    echo "العمود image موجود بالفعل في جدول users";
}

echo "<br><a href='profile.php'>العودة إلى الملف الشخصي</a>";
?>