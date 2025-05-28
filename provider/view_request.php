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

// التحقق من وجود معرف الطلب
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: requests.php");
    exit;
}

$request_id = $_GET['id'];

// جلب بيانات الطلب
$request_sql = "SELECT sr.*, s.name as service_name, s.price, s.image as service_image,
                u.username as client_name, u.email as client_email, u.phone as client_phone, u.image as client_image
                FROM service_requests sr 
                JOIN services s ON sr.service_id = s.id 
                JOIN users u ON sr.client_id = u.id 
                WHERE sr.id = ? AND sr.provider_id = ?";
$request_stmt = $conn->prepare($request_sql);
$request_stmt->bind_param("ii", $request_id, $provider_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();

// التحقق من وجود الطلب
if ($request_result->num_rows == 0) {
    header("Location: requests.php");
    exit;
}

$request = $request_result->fetch_assoc();

// جلب سجل تغييرات الحالة إذا كان موجودًا
$status_history = [];
$has_status_history_table = false;

$check_history_table = "SHOW TABLES LIKE 'request_status_history'";
$history_table_result = $conn->query($check_history_table);
$has_status_history_table = ($history_table_result->num_rows > 0);

if ($has_status_history_table) {
    $history_sql = "SELECT * FROM request_status_history 
                   WHERE request_id = ? 
                   ORDER BY created_at DESC";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bind_param("i", $request_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    
    if ($history_result->num_rows > 0) {
        while ($row = $history_result->fetch_assoc()) {
            $status_history[] = $row;
        }
    }
}

// جلب التقييم إذا كان موجودًا
$review = null;
$has_reviews_table = false;

$check_reviews_table = "SHOW TABLES LIKE 'service_reviews'";
$reviews_table_result = $conn->query($check_reviews_table);
$has_reviews_table = ($reviews_table_result->num_rows > 0);

if ($has_reviews_table && $request['status'] == 'completed') {
    $review_sql = "SELECT * FROM service_reviews WHERE request_id = ?";
    $review_stmt = $conn->prepare($review_sql);
    $review_stmt->bind_param("i", $request_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    
    if ($review_result->num_rows > 0) {
        $review = $review_result->fetch_assoc();
    }
}

// معالجة تحديث حالة الطلب
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    $notes = $_POST['notes'] ?? '';
    
    // التحقق من صحة الحالة الجديدة
    $allowed_status_changes = ['accepted', 'completed', 'rejected'];
    
    if (in_array($new_status, $allowed_status_changes)) {
        // تحديث حالة الطلب
        $update_sql = "UPDATE service_requests SET status = ?, notes = ? WHERE id = ? AND provider_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssii", $new_status, $notes, $request_id, $provider_id);
        
        if ($update_stmt->execute()) {
            // إضافة سجل تغيير الحالة إذا كان الجدول موجودًا
            if ($has_status_history_table) {
                $add_history_sql = "INSERT INTO request_status_history (request_id, status, notes, created_by, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())";
                $add_history_stmt = $conn->prepare($add_history_sql);
                $created_by = 'provider';
                $add_history_stmt->bind_param("isss", $request_id, $new_status, $notes, $created_by);
                $add_history_stmt->execute();
            }
            
            $success_message = __('request_status_updated');
            
            // تحديث بيانات الطلب
            $request_stmt->execute();
            $request_result = $request_stmt->get_result();
            $request = $request_result->fetch_assoc();
            
            // تحديث سجل تغييرات الحالة
            if ($has_status_history_table) {
                $history_stmt->execute();
                $history_result = $history_stmt->get_result();
                $status_history = [];
                
                if ($history_result->num_rows > 0) {
                    while ($row = $history_result->fetch_assoc()) {
                        $status_history[] = $row;
                    }
                }
            }
        } else {
            $error_message = __('error_updating_request');
        }
    } else {
        $error_message = __('invalid_status');
    }
}

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'requests';
$page_title = __('request_details') . ' #' . $request_id;

// تضمين ملف الهيدر
include 'includes/header.php';
?>

<div class="provider-content">
    <div class="provider-content-header">
        <h1><?php echo __('request_details'); ?> #<?php echo $request_id; ?></h1>
        <div class="provider-content-actions">
            <a href="requests.php" class="provider-btn provider-btn-secondary">
                <i class="fas fa-arrow-right"></i> <?php echo __('back_to_requests'); ?>
            </a>
        </div>
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
    
    <div class="provider-row">
        <div class="provider-col-md-8">
            <div class="provider-card">
                <div class="provider-card-header">
                    <h3><?php echo __('request_information'); ?></h3>
                    <span class="status-badge <?php echo strtolower($request['status']); ?>">
                        <?php echo __($request['status']); ?>
                    </span>
                </div>
                <div class="provider-card-body">
                    <div class="provider-row">
                        <div class="provider-col-md-6">
                            <div class="info-group">
                                <label><?php echo __('service'); ?></label>
                                <p><?php echo htmlspecialchars($request['service_name']); ?></p>
                            </div>
                        </div>
                        <div class="provider-col-md-6">
                            <div class="info-group">
                                <label><?php echo __('price'); ?></label>
                                <p><?php echo __('currency_symbol') . number_format($request['price'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="provider-row">
                        <div class="provider-col-md-6">
                            <div class="info-group">
                                <label><?php echo __('request_date'); ?></label>
                                <p><?php echo date('Y-m-d', strtotime($request['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="provider-col-md-6">
                            <div class="info-group">
                                <label><?php echo __('preferred_date'); ?></label>
                                <p><?php echo !empty($request['preferred_date']) ? date('Y-m-d', strtotime($request['preferred_date'])) : __('not_specified'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <label><?php echo __('client_message'); ?></label>
                        <div class="message-box">
                            <?php echo !empty($request['message']) ? nl2br(htmlspecialchars($request['message'])) : __('no_message'); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($request['notes'])): ?>
                        <div class="info-group">
                            <label><?php echo __('provider_notes'); ?></label>
                            <div class="message-box">
                                <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] == 'pending'): ?>
                        <div class="provider-form-actions mt-4">
                            <button type="button" class="provider-btn provider-btn-success" data-toggle="modal" data-target="#acceptModal">
                                <i class="fas fa-check"></i> <?php echo __('accept_request'); ?>
                            </button>
                            <button type="button" class="provider-btn provider-btn-danger" data-toggle="modal" data-target="#rejectModal">
                                <i class="fas fa-times"></i> <?php echo __('reject_request'); ?>
                            </button>
                        </div>
                    <?php elseif ($request['status'] == 'accepted'): ?>
                        <div class="provider-form-actions mt-4">
                            <button type="button" class="provider-btn provider-btn-primary" data-toggle="modal" data-target="#completeModal">
                                <i class="fas fa-check-double"></i> <?php echo __('mark_as_completed'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($has_status_history_table && !empty($status_history)): ?>
                <div class="provider-card mt-4">
                    <div class="provider-card-header">
                        <h3><?php echo __('status_history'); ?></h3>
                    </div>
                    <div class="provider-card-body">
                        <div class="status-timeline">
                            <?php foreach ($status_history as $history): ?>
                                <div class="timeline-item">
                                    <div class="timeline-badge <?php echo strtolower($history['status']); ?>">
                                        <?php
                                        switch ($history['status']) {
                                            case 'pending':
                                                echo '<i class="fas fa-clock"></i>';
                                                break;
                                            case 'accepted':
                                                echo '<i class="fas fa-check"></i>';
                                                break;
                                            case 'completed':
                                                echo '<i class="fas fa-check-double"></i>';
                                                break;
                                            case 'rejected':
                                                echo '<i class="fas fa-times"></i>';
                                                break;
                                            case 'cancelled':
                                                echo '<i class="fas fa-ban"></i>';
                                                break;
                                            default:
                                                echo '<i class="fas fa-info"></i>';
                                        }
                                        ?>
                                    </div>
                                    <div class="timeline-content">
                                        <h4>
                                            <?php echo __($history['status']); ?>
                                            <small><?php echo date('Y-m-d H:i', strtotime($history['created_at'])); ?></small>
                                        </h4>
                                        <?php if (!empty($history['notes'])): ?>
                                            <p><?php echo nl2br(htmlspecialchars($history['notes'])); ?></p>
                                        <?php endif; ?>
                                        <p class="timeline-by">
                                            <?php echo __('by') . ' ' . __($history['created_by']); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($has_reviews_table && $review): ?>
                <div class="provider-card mt-4">
                    <div class="provider-card-header">
                        <h3><?php echo __('client_review'); ?></h3>
                    </div>
                    <div class="provider-card-body">
                        <div class="provider-review">
                            <div class="provider-review-rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $review['rating']) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span class="provider-review-date"><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div class="provider-review-comment">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="provider-col-md-4">
            <div class="provider-card">
                <div class="provider-card-header">
                    <h3><?php echo __('client_information'); ?></h3>
                </div>
                <div class="provider-card-body">
                    <div class="client-info">
                        <?php if (!empty($request['client_image'])): ?>
                            <img src="../images/users/<?php echo $request['client_image']; ?>" alt="<?php echo htmlspecialchars($request['client_name']); ?>" class="client-avatar">
                        <?php else: ?>
                            <div class="client-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h3><?php echo htmlspecialchars($request['client_name']); ?></h3>
                        
                        <div class="info-group">
                            <label><?php echo __('email'); ?></label>
                            <p><?php echo htmlspecialchars($request['client_email']); ?></p>
                        </div>
                        
                        <?php if (!empty($request['client_phone'])): ?>
                            <div class="info-group">
                                <label><?php echo __('phone'); ?></label>
                                <p><?php echo htmlspecialchars($request['client_phone']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="client-actions">
                            <a href="mailto:<?php echo $request['client_email']; ?>" class="provider-btn provider-btn-primary provider-btn-block">
                                <i class="fas fa-envelope"></i> <?php echo __('send_email'); ?>
                            </a>
                            
                            <?php if (!empty($request['client_phone'])): ?>
                                <a href="tel:<?php echo $request['client_phone']; ?>" class="provider-btn provider-btn-success provider-btn-block">
                                    <i class="fas fa-phone"></i> <?php echo __('call'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Accept Request -->
<div class="modal fade" id="acceptModal" tabindex="-1" role="dialog" aria-labelledby="acceptModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="acceptModalLabel"><?php echo __('accept_request'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?php echo __('accept_request_confirm'); ?></p>
                    
                    <div class="form-group">
                        <label for="accept-notes"><?php echo __('notes'); ?> (<?php echo __('optional'); ?>)</label>
                        <textarea id="accept-notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <input type="hidden" name="new_status" value="accepted">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" name="update_status" class="btn btn-success"><?php echo __('accept'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reject Request -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel"><?php echo __('reject_request'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?php echo __('reject_request_confirm'); ?></p>
                    
                    <div class="form-group">
                        <label for="reject-notes"><?php echo __('rejection_reason'); ?></label>
                        <textarea id="reject-notes" name="notes" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <input type="hidden" name="new_status" value="rejected">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" name="update_status" class="btn btn-danger"><?php echo __('reject'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Complete Request -->
<div class="modal fade" id="completeModal" tabindex="-1" role="dialog" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="completeModalLabel"><?php echo __('mark_as_completed'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?php echo __('complete_request_confirm'); ?></p>
                    
                    <div class="form-group">
                        <label for="complete-notes"><?php echo __('completion_notes'); ?> (<?php echo __('optional'); ?>)</label>
                        <textarea id="complete-notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <input type="hidden" name="new_status" value="completed">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" name="update_status" class="btn btn-primary"><?php echo __('complete'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

