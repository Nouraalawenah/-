<?php
require_once 'config/db_connect.php';

// تصحيح تواريخ الإكمال التي تكون في المستقبل
$current_date = date('Y-m-d H:i:s');

// تحديث تواريخ الإكمال التي تكون في المستقبل لتكون مساوية لتاريخ التحديث
$update_future_dates_sql = "UPDATE service_requests 
                           SET completed_at = updated_at 
                           WHERE status = 'completed' 
                           AND completed_at > ?";

$stmt = $conn->prepare($update_future_dates_sql);
$stmt->bind_param("s", $current_date);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo "تم تصحيح {$affected_rows} طلب مكتمل كان تاريخ إكماله في المستقبل.<br>";
} else {
    echo "خطأ في تصحيح تواريخ الإكمال: " . $conn->error . "<br>";
}

// تحديث تواريخ التحديث التي تكون في المستقبل لتكون مساوية لتاريخ الإنشاء
$update_future_updated_sql = "UPDATE service_requests 
                             SET updated_at = created_at 
                             WHERE updated_at > ?";

$stmt = $conn->prepare($update_future_updated_sql);
$stmt->bind_param("s", $current_date);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo "تم تصحيح {$affected_rows} طلب كان تاريخ تحديثه في المستقبل.<br>";
} else {
    echo "خطأ في تصحيح تواريخ التحديث: " . $conn->error . "<br>";
}

echo "<br><a href='admin/dashboard.php'>العودة إلى لوحة التحكم</a>";
?>