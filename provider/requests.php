<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات مزود الخدمة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_provider']) {
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// تحديد حالة الطلبات المطلوب عرضها
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['all', 'pending', 'accepted', 'completed', 'rejected', 'cancelled'];

if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// تحديد الصفحة الحالية للتصفح
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// بناء استعلام جلب الطلبات
$requests_sql = "SELECT sr.*, s.name as service_name, u.username as client_name 
                FROM service_requests sr 
                JOIN services s ON sr.service_id = s.id 
                JOIN users u ON sr.client_id = u.id 
                WHERE sr.provider_id = ?";

$count_sql = "SELECT COUNT(*) as total FROM service_requests WHERE provider_id = ?";

// إضافة فلتر الحالة إذا كان محددًا
if ($status_filter != 'all') {
    $requests_sql .= " AND sr.status = ?";
    $count_sql .= " AND status = ?";
}

// إضافة الترتيب والتصفح
$requests_sql .= " ORDER BY sr.created_at DESC LIMIT ?, ?";

// إعداد استعلام العدد
$count_stmt = $conn->prepare($count_sql);
if ($status_filter != 'all') {
    $count_stmt->bind_param("is", $provider_id, $status_filter);
} else {
    $count_stmt->bind_param("i", $provider_id);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_requests = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_requests / $per_page);

// إعداد استعلام جلب الطلبات
$requests_stmt = $conn->prepare($requests_sql);
if ($status_filter != 'all') {
    $requests_stmt->bind_param("isii", $provider_id, $status_filter, $offset, $per_page);
} else {
    $requests_stmt->bind_param("iii", $provider_id, $offset, $per_page);
}
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();

// معالجة تحديث حالة الطلب
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['new_status'];
    $notes = $_POST['notes'] ?? '';
    
    // التحقق من صحة الحالة الجديدة
    $allowed_status_changes = ['accepted', 'completed', 'rejected'];
    
    if (in_array($new_status, $allowed_status_changes)) {
        // التحقق من أن الطلب ينتمي لمزود الخدمة
        $check_sql = "SELECT id FROM service_requests WHERE id = ? AND provider_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $request_id, $provider_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // تحديث حالة الطلب
            $update_sql = "UPDATE service_requests SET status = ?, notes = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $new_status, $notes, $request_id);
            
            if ($update_stmt->execute()) {
                $success_message = __('request_status_updated');
                
                // إعادة تحميل الطلبات
                $requests_stmt->execute();
                $requests_result = $requests_stmt->get_result();
            } else {
                $error_message = __('error_updating_request');
            }
        } else {
            $error_message = __('request_not_found');
        }
    } else {
        $error_message = __('invalid_status');
    }
}

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'requests';
$page_title = __('service_requests');

// تضمين ملف الهيدر
include 'includes/header.php';
?>

<div class="provider-content">
    <div class="provider-content-header">
        <h1><?php echo __('service_requests'); ?></h1>
    </div>
    
    <?php if ($success_message): ?>
        <div class="provider-alert provider-alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="provider-alert provider-alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="provider-card">
        <div class="provider-card-header">
            <div class="provider-card-header-tabs">
                <a href="requests.php" class="provider-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    <?php echo __('all_requests'); ?>
                </a>
                <a href="requests.php?status=pending" class="provider-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    <?php echo __('pending'); ?>
                </a>
                <a href="requests.php?status=accepted" class="provider-tab <?php echo $status_filter == 'accepted' ? 'active' : ''; ?>">
                    <?php echo __('accepted'); ?>
                </a>
                <a href="requests.php?status=completed" class="provider-tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                    <?php echo __('completed'); ?>
                </a>
                <a href="requests.php?status=rejected" class="provider-tab <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">
                    <?php echo __('rejected'); ?>
                </a>
                <a href="requests.php?status=cancelled" class="provider-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                    <?php echo __('cancelled'); ?>
                </a>
            </div>
        </div>
        <div class="provider-card-body">
            <?php if ($requests_result->num_rows > 0): ?>
                <div class="provider-table-responsive">
                    <table class="provider-table">
                        <thead>
                            <tr>
                                <th><?php echo __('id'); ?></th>
                                <th><?php echo __('service'); ?></th>
                                <th><?php echo __('client'); ?></th>
                                <th><?php echo __('date'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $requests_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['client_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <span class="provider-badge provider-badge-<?php echo strtolower($request['status']); ?>">
                                            <?php echo __($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_request.php?id=<?php echo $request['id']; ?>" class="provider-btn provider-btn-sm provider-btn-info">
                                            <i class="fas fa-eye"></i> <?php echo __('view'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="provider-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>" class="provider-pagination-item">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" class="provider-pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>" class="provider-pagination-item">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="provider-empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p><?php echo __('no_requests_found'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>





