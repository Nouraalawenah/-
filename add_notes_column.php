<?php
require_once 'config/db_connect.php';

// إضافة عمود notes إلى جدول service_requests
$check_column = "SHOW COLUMNS FROM service_requests LIKE 'notes'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // العمود غير موجود، قم بإضافته
    $add_column = "ALTER TABLE service_requests ADD COLUMN notes TEXT AFTER message";
    
    if ($conn->query($add_column) === TRUE) {
        echo "تم إضافة عمود notes إلى جدول service_requests بنجاح";
    } else {
        echo "خطأ في إضافة العمود: " . $conn->error;
    }
} else {
    echo "العمود notes موجود بالفعل في جدول service_requests";
}

echo "<br><a href='admin/dashboard.php'>العودة إلى لوحة التحكم</a>";
?>