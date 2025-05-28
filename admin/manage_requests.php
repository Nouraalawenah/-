<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحية المستخدم
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// تعديل كود تحديث حالة الطلب في لوحة تحكم المسؤول
if (isset($_POST['update_status']) && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];
    $current_date = date('Y-m-d H:i:s');
    
    // إضافة تاريخ الاكتمال إذا كانت الحالة "مكتملة"
    if ($status == 'completed') {
        $update_sql = "UPDATE service_requests SET status = ?, updated_at = ?, completed_at = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $status, $current_date, $current_date, $request_id);
    } else {
        $update_sql = "UPDATE service_requests SET status = ?, updated_at = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $status, $current_date, $request_id);
    }
    
    if ($update_stmt->execute()) {
        // إضافة سجل تغيير الحالة إذا كان جدول سجل الحالة موجودًا
        $check_history_table = "SHOW TABLES LIKE 'request_status_history'";
        $history_table_result = $conn->query($check_history_table);
        
        if ($history_table_result->num_rows > 0) {
            $history_sql = "INSERT INTO request_status_history (request_id, status, created_at, created_by) 
                           VALUES (?, ?, ?, ?)";
            $history_stmt = $conn->prepare($history_sql);
            $admin_id = $_SESSION['user_id'];
            $history_stmt->bind_param("issi", $request_id, $status, $current_date, $admin_id);
            $history_stmt->execute();
        }
        
        $success = "تم تحديث حالة الطلب بنجاح.";
    } else {
        $error = "حدث خطأ أثناء تحديث حالة الطلب.";
    }
}
?>


