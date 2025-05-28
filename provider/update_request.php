<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول ومن أن المستخدم هو مقدم خدمة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_provider']) || $_SESSION['is_provider'] != 1) {
    header("Location: ../login.php");
    exit;
}

// الحصول على معلومات مقدم الخدمة
$user_id = $_SESSION['user_id'];
$provider_query = "SELECT * FROM service_providers WHERE user_id = ?";
$stmt = $conn->prepare($provider_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$provider_result = $stmt->get_result();

if ($provider_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit;
}

$provider = $provider_result->fetch_assoc();
$provider_id = $provider['id'];

// التحقق من وجود معرف الطلب
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: requests.php");
    exit;
}

$request_id = intval($_GET['id']);

// التحقق من أن الطلب ينتمي لمقدم الخدمة الحالي
$request_query = "SELECT sr.*, u.username, u.email, u.phone, s.name as service_name, s.provider_id 
                 FROM service_requests sr 
                 JOIN users u ON sr.user_id = u.id 
                 JOIN services s ON sr.service_id = s.id 
                 WHERE sr.id = ? AND s.provider_id = ?";
$stmt = $conn->prepare($request_query);
$stmt->bind_param("ii", $request_id, $provider_id);
$stmt->execute();
$request_result = $stmt->get_result();

if ($request_result->num_rows == 0) {
    header("Location: requests.php");
    exit;
}

$request = $request_result->fetch_assoc();

// التحقق من الإجراء المطلوب من خلال معلمة action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// معالجة الإجراءات السريعة
if (!empty($action) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $new_status = '';
    
    switch ($action) {
        case 'accept':
            if ($request['status'] == 'pending') {
                $new_status = 'approved';
            }
            break;
        case 'reject':
            if ($request['status'] == 'pending') {
                $new_status = 'rejected';
            }
            break;
        case 'complete':
            if ($request['status'] == 'approved') {
                $new_status = 'completed';
            }
            break;
        case 'cancel':
            if (in_array($request['status'], ['pending', 'approved'])) {
                $new_status = 'cancelled';
            }
            break;
    }
    
    if (!empty($new_status)) {
        // تحديث حالة الطلب
        $update_sql = "UPDATE service_requests SET status = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $request_id);
        
        if ($update_stmt->execute()) {
            // إضافة سجل تغيير الحالة إذا كان جدول سجل الحالة موجودًا
            $has_status_history_table = false;
            $check_history_table = "SHOW TABLES LIKE 'request_status_history'";
            $history_table_result = $conn->query($check_history_table);
            $has_status_history_table = ($history_table_result->num_rows > 0);
            
            if ($has_status_history_table) {
                $notes = __('status_changed_via_quick_action');
                $history_sql = "INSERT INTO request_status_history (request_id, status, notes, created_by, created_at) 
                               VALUES (?, ?, ?, ?, NOW())";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("issi", $request_id, $new_status, $notes, $user_id);
                $history_stmt->execute();
            }
            
            // إعادة التوجيه إلى صفحة عرض الطلب مع رسالة نجاح
            header("Location: view_request.php?id=" . $request_id . "&status_updated=1");
            exit;
        }
    }
}

// التحقق من أن الطلب في حالة معلقة للتحديث اليدوي
if ($request['status'] != 'pending' && empty($action)) {
    header("Location: view_request.php?id=" . $request_id);
    exit;
}

// معالجة النموذج عند الإرسال
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
    // التحقق من صحة الحالة
    $valid_statuses = ['approved', 'rejected', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $error_message = __('invalid_status');
    } else {
        // تحديث حالة الطلب
        $update_sql = "UPDATE service_requests SET status = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $status, $request_id);
        
        if ($update_stmt->execute()) {
            // إضافة سجل تغيير الحالة إذا كان جدول سجل الحالة موجودًا
            $has_status_history_table = false;
            $check_history_table = "SHOW TABLES LIKE 'request_status_history'";
            $history_table_result = $conn->query($check_history_table);
            $has_status_history_table = ($history_table_result->num_rows > 0);
            
            if ($has_status_history_table) {
                $history_sql = "INSERT INTO request_status_history (request_id, status, notes, created_by, created_at) 
                               VALUES (?, ?, ?, ?, NOW())";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("issi", $request_id, $status, $notes, $user_id);
                $history_stmt->execute();
            }
            
            // إرسال إشعار للمستخدم (يمكن تنفيذ هذا لاحقًا)
            
            $success_message = __('request_updated_successfully');
            
            // تحديث بيانات الطلب المعروضة
            $request['status'] = $status;
        } else {
            $error_message = __('error_updating_request');
        }
    }
}

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'requests';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang['lang_code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('update_request'); ?> - <?php echo __('provider_dashboard'); ?></title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/provider.css">
    <?php if ($lang['dir'] == 'rtl'): ?>
    <link rel="stylesheet" href="../css/bootstrap-rtl.min.css">
    <?php endif; ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="provider-content">
        <?php include 'includes/header.php'; ?>
        
        <main class="provider-main">
            <div class="container-fluid">
                <div class="provider-page-title">
                    <h1><?php echo __('update_request'); ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><?php echo __('dashboard'); ?></a></li>
                            <li class="breadcrumb-item"><a href="requests.php"><?php echo __('service_requests'); ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo __('update_request'); ?></li>
                        </ol>
                    </nav>
                </div>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="provider-card">
                            <div class="provider-card-header">
                                <h2><?php echo __('request_details'); ?></h2>
                            </div>
                            <div class="provider-card-body">
                                <div class="request-details">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h5><?php echo __('request_information'); ?></h5>
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th><?php echo __('request_id'); ?>:</th>
                                                    <td>#<?php echo $request['id']; ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php echo __('service'); ?>:</th>
                                                    <td><?php echo htmlspecialchars($request['service_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php echo __('status'); ?>:</th>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                        switch($request['status']) {
                                                            case 'pending': echo 'warning'; break;
                                                            case 'approved': echo 'success'; break;
                                                            case 'completed': echo 'primary'; break;
                                                            case 'rejected': echo 'danger'; break;
                                                            case 'cancelled': echo 'secondary'; break;
                                                            default: echo 'info';
                                                        }
                                                        ?>">
                                                            <?php echo __($request['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th><?php echo __('scheduled_date'); ?>:</th>
                                                    <td>
                                                        <?php 
                                                        if (!empty($request['scheduled_date'])) {
                                                            echo date('Y-m-d', strtotime($request['scheduled_date']));
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th><?php echo __('created_at'); ?>:</th>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($request['created_at'])); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h5><?php echo __('customer_information'); ?></h5>
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th><?php echo __('name'); ?>:</th>
                                                    <td><?php echo htmlspecialchars($request['username']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php echo __('email'); ?>:</th>
                                                    <td><?php echo htmlspecialchars($request['email']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php echo __('phone'); ?>:</th>
                                                    <td><?php echo !empty($request['phone']) ? htmlspecialchars($request['phone']) : '-'; ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div class="request-message mb-4">
                                        <h5><?php echo __('customer_message'); ?></h5>
                                        <div class="message-box p-3 bg-light rounded">
                                            <?php echo !empty($request['message']) ? nl2br(htmlspecialchars($request['message'])) : __('no_message_provided'); ?>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" action


