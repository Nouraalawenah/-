<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../config/language.php';

// التحقق من تسجيل الدخول ومن أن المستخدم هو مقدم خدمة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_provider']) || $_SESSION['is_provider'] != 1) {
    echo json_encode(['success' => false, 'message' => __('not_authorized')]);
    exit;
}

// التحقق من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => __('invalid_request')]);
    exit;
}

// التحقق من وجود معرف الخدمة والحالة
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => __('service_id_required')]);
    exit;
}

$service_id = $_POST['id'];
$new_status = $_POST['status'];
$user_id = $_SESSION['user_id'];

// التحقق من أن الخدمة تنتمي لمقدم الخدمة الحالي
$check_sql = "SELECT s.* FROM services s 
              JOIN service_providers sp ON s.provider_id = sp.id 
              WHERE s.id = ? AND sp.user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $service_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => __('service_not_found')]);
    exit;
}

// تحديث حالة الخدمة
$update_sql = "UPDATE services SET active = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $new_status, $service_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => __('success')]);
} else {
    echo json_encode(['success' => false, 'message' => __('error')]);
}

