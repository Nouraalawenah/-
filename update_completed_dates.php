<?php
require_once 'config/db_connect.php';

// تحديث حقل completed_at للطلبات المكتملة التي ليس لها قيمة
$update_sql = "UPDATE service_requests 
               SET completed_at = updated_at 
               WHERE status = 'completed' AND completed_at IS NULL";

if ($conn->query($update_sql) === TRUE) {
    $affected_rows = $conn->affected_rows;
    echo "تم تحديث {$affected_rows} طلب مكتمل بتاريخ الإكمال.";
} else {
    echo "خطأ في تحديث تواريخ الإكمال: " . $conn->error;
}

echo "<br><a href='admin/dashboard.php'>العودة إلى لوحة التحكم</a>";
?>