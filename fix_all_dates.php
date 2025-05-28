<?php
require_once 'config/db_connect.php';

// الحصول على التاريخ الحالي
$current_date = date('Y-m-d H:i:s');
$affected_rows = 0;

// تصحيح تواريخ الإنشاء المستقبلية
$fix_created_dates = "UPDATE service_requests 
                     SET created_at = ? 
                     WHERE created_at > ?";
$stmt = $conn->prepare($fix_created_dates);
$stmt->bind_param("ss", $current_date, $current_date);

if ($stmt->execute()) {
    $affected_rows += $stmt->affected_rows;
    echo "تم تصحيح " . $stmt->affected_rows . " طلب كان تاريخ إنشائه في المستقبل.<br>";
} else {
    echo "خطأ في تصحيح تواريخ الإنشاء: " . $conn->error . "<br>";
}

// تصحيح تواريخ التحديث المستقبلية
$fix_updated_dates = "UPDATE service_requests 
                     SET updated_at = created_at 
                     WHERE updated_at > ?";
$stmt = $conn->prepare($fix_updated_dates);
$stmt->bind_param("s", $current_date);

if ($stmt->execute()) {
    $affected_rows += $stmt->affected_rows;
    echo "تم تصحيح " . $stmt->affected_rows . " طلب كان تاريخ تحديثه في المستقبل.<br>";
} else {
    echo "خطأ في تصحيح تواريخ التحديث: " . $conn->error . "<br>";
}

// تصحيح تواريخ الإكمال المستقبلية
$fix_completed_dates = "UPDATE service_requests 
                       SET completed_at = updated_at 
                       WHERE status = 'completed' 
                       AND completed_at > ?";
$stmt = $conn->prepare($fix_completed_dates);
$stmt->bind_param("s", $current_date);

if ($stmt->execute()) {
    $affected_rows += $stmt->affected_rows;
    echo "تم تصحيح " . $stmt->affected_rows . " طلب مكتمل كان تاريخ إكماله في المستقبل.<br>";
} else {
    echo "خطأ في تصحيح تواريخ الإكمال: " . $conn->error . "<br>";
}

// تصحيح تواريخ المواعيد المجدولة المستقبلية للطلبات المكتملة
$fix_scheduled_dates = "UPDATE service_requests 
                       SET scheduled_date = created_at 
                       WHERE status = 'completed' 
                       AND scheduled_date > ?";
$stmt = $conn->prepare($fix_scheduled_dates);
$stmt->bind_param("s", $current_date);

if ($stmt->execute()) {
    $affected_rows += $stmt->affected_rows;
    echo "تم تصحيح " . $stmt->affected_rows . " طلب مكتمل كان تاريخ موعده في المستقبل.<br>";
} else {
    echo "خطأ في تصحيح تواريخ المواعيد: " . $conn->error . "<br>";
}

echo "<br>تم تصحيح إجمالي " . $affected_rows . " سجل.<br>";
echo "<br><a href='admin/dashboard.php'>العودة إلى لوحة التحكم</a>";
?>