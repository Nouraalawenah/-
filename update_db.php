<?php
require_once 'config/db_connect.php';

// إضافة عمود is_read إلى جدول contact_messages إذا لم يكن موجودًا
$check_column = "SHOW COLUMNS FROM contact_messages LIKE 'is_read'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // العمود غير موجود، قم بإضافته
    $add_column = "ALTER TABLE contact_messages ADD COLUMN is_read BOOLEAN DEFAULT 0";
    
    if ($conn->query($add_column) === TRUE) {
        echo "تم إضافة عمود is_read إلى جدول contact_messages بنجاح";
    } else {
        echo "خطأ في إضافة العمود: " . $conn->error;
    }
} else {
    echo "العمود is_read موجود بالفعل في جدول contact_messages";
}

echo "<br><a href='admin/dashboard.php'>العودة إلى لوحة التحكم</a>";
?>