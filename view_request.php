<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// التحقق من وجود الطلب وأنه ينتمي للمستخدم الحالي
$request_sql = "SELECT sr.*, s.name as service_name, 
                s.id as service_id, s.provider_id, s.price,
                sp.name as provider_name, 
                sp.phone as provider_phone, sp.email as provider_email,
                sr.created_at, sr.status, sr.scheduled_date, sr.message,
                sr.updated_at, sr.completed_at
                FROM service_requests sr
                JOIN services s ON sr.service_id = s.id
                JOIN service_providers sp ON s.provider_id = sp.id
                WHERE sr.id = ? AND sr.user_id = ?";

$request_stmt = $conn->prepare($request_sql);
$request_stmt->bind_param("ii", $request_id, $user_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();

if ($request_result->num_rows == 0) {
    header("Location: service_request.php");
    exit;
}

$request = $request_result->fetch_assoc();

// جلب التعليقات والتقييمات إذا كانت موجودة
$has_reviews_table = false;
$review = null;

$check_table = "SHOW TABLES LIKE 'service_reviews'";
$table_result = $conn->query($check_table);
$has_reviews_table = ($table_result->num_rows > 0);

if ($has_reviews_table) {
    $review_sql = "SELECT * FROM service_reviews WHERE request_id = ? AND user_id = ?";
    $review_stmt = $conn->prepare($review_sql);
    $review_stmt->bind_param("ii", $request_id, $user_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    
    if ($review_result->num_rows > 0) {
        $review = $review_result->fetch_assoc();
    }
}

// إضافة تقييم جديد
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    if ($rating < 1 || $rating > 5) {
        $error = __('invalid_rating');
    } elseif (empty($comment)) {
        $error = __('comment_required');
    } else {
        if ($review) {
            // تحديث التقييم الموجود
            $update_sql = "UPDATE service_reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("isi", $rating, $comment, $review['id']);
            
            if ($update_stmt->execute()) {
                $success = __('review_updated_success');
                // تحديث بيانات التقييم
                $review_stmt->execute();
                $review_result = $review_stmt->get_result();
                $review = $review_result->fetch_assoc();
            } else {
                $error = __('review_update_error');
            }
        } else {
            // إضافة تقييم جديد
            $insert_sql = "INSERT INTO service_reviews (user_id, provider_id, service_id, request_id, rating, comment, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiiiss", $user_id, $request['provider_id'], $request['service_id'], $request_id, $rating, $comment);
            
            if ($insert_stmt->execute()) {
                $success = __('review_added_success');
                // جلب التقييم الجديد
                $review_stmt->execute();
                $review_result = $review_stmt->get_result();
                $review = $review_result->fetch_assoc();
            } else {
                $error = __('review_add_error');
            }
        }
    }
}

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

// عنوان الصفحة
$page_title = __('request_details');

// تضمين الهيدر
include 'includes/header.php';
?>

<div class="view-request-wrapper">
    <div class="container">
        <div class="request-header">
            <h1><?= __('request_details') ?></h1>
            <div class="request-id">
                <?= __('request_id') ?>: <span>#<?= $request_id ?></span>
            </div>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="request-container">
            <div class="request-main">
                <div class="request-card">
                    <div class="request-card-header">
                        <h2><?= __('service_information') ?></h2>
                    </div>
                    <div class="request-card-body">
                        <div class="service-info">
                            <div class="info-row">
                                <div class="info-label"><?= __('service_name') ?>:</div>
                                <div class="info-value"><?= htmlspecialchars($request['service_name'] ?? '') ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><?= __('service_price') ?>:</div>
                                <div class="info-value"><?= number_format($request['price'] ?? 0, 2) ?> <?= __('currency') ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><?= __('request_date') ?>:</div>
                                <div class="info-value"><?= date('d/m/Y', strtotime($request['created_at'])) ?></div>
                            </div>
                            <?php if (!empty($request['scheduled_date'])): ?>
                            <div class="info-row">
                                <div class="info-label"><?= __('scheduled_date') ?>:</div>
                                <div class="info-value"><?= date('d/m/Y', strtotime($request['scheduled_date'])) ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <div class="info-label"><?= __('status') ?>:</div>
                                <div class="info-value">
                                    <span class="status-badge status-<?= $request['status'] ?>">
                                        <?= __('status_' . $request['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($request['message'])): ?>
                        <div class="message-section">
                            <h3><?= __('your_message') ?></h3>
                            <div class="message-content">
                                <?= nl2br(htmlspecialchars($request['message'] ?? '')) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="request-actions">
                            <a href="service_details.php?id=<?= $request['service_id'] ?>" class="btn btn-outline">
                                <i class="fas fa-info-circle"></i> <?= __('view_service_details') ?>
                            </a>
                            
                            <?php if ($request['status'] == 'pending'): ?>
                            <a href="cancel_request.php?id=<?= $request_id ?>" class="btn btn-danger" onclick="return confirm('<?= __('confirm_cancel_request') ?>');">
                                <i class="fas fa-times-circle"></i> <?= __('cancel_request') ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($request['status'] == 'completed' && $has_reviews_table): ?>
                <div class="request-card">
                    <div class="request-card-header">
                        <h2><?= __('rate_service') ?></h2>
                    </div>
                    <div class="request-card-body">
                        <?php if ($review): ?>
                        <div class="review-submitted">
                            <div class="review-header">
                                <h3><?= __('your_review') ?></h3>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= ($i <= $review['rating']) ? 'active' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="review-comment">
                                <?= nl2br(htmlspecialchars($review['comment'] ?? '')) ?>
                            </div>
                            <div class="review-date">
                                <?= __('submitted_on') ?>: <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                            </div>
                            <button class="btn btn-sm btn-outline edit-review-btn">
                                <i class="fas fa-edit"></i> <?= __('edit_review') ?>
                            </button>
                        </div>
                        
                        <div class="review-form" style="display: none;">
                        <?php else: ?>
                        <div class="review-form">
                        <?php endif; ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label><?= __('your_rating') ?></label>
                                    <div class="rating-input">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star-<?= $i ?>" name="rating" value="<?= $i ?>" <?= ($review && $review['rating'] == $i) ? 'checked' : '' ?>>
                                        <label for="star-<?= $i ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comment"><?= __('your_comment') ?></label>
                                    <textarea id="comment" name="comment" class="form-control" required><?= ($review) ? htmlspecialchars($review['comment'] ?? '') : '' ?></textarea>
                                </div>
                                
                                <button type="submit" name="submit_review" class="btn btn-primary">
                                    <?= ($review) ? __('update_review') : __('submit_review') ?>
                                </button>
                                
                                <?php if ($review): ?>
                                <button type="button" class="btn btn-outline cancel-edit-btn">
                                    <?= __('cancel') ?>
                                </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($has_status_history_table && !empty($status_history)): ?>
                <div class="request-card">
                    <div class="request-card-header">
                        <h2><?= __('status_history') ?></h2>
                    </div>
                    <div class="request-card-body">
                        <div class="status-timeline">
                            <?php foreach ($status_history as $index => $history): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon status-<?= $history['status'] ?>">
                                    <i class="fas fa-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-date">
                                        <?= date('d/m/Y H:i', strtotime($history['created_at'])) ?>
                                    </div>
                                    <div class="timeline-title">
                                        <?= __('status_changed_to') ?> 
                                        <span class="status-badge status-<?= $history['status'] ?>">
                                            <?= __('status_' . $history['status']) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($history['notes'])): ?>
                                    <div class="timeline-notes">
                                        <?= nl2br(htmlspecialchars($history['notes'] ?? '')) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="request-sidebar">
                <div class="request-card">
                    <div class="request-card-header">
                        <h2><?= __('provider_information') ?></h2>
                    </div>
                    <div class="request-card-body">
                        <div class="provider-info">
                            <div class="provider-avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h3><?= htmlspecialchars($request['provider_name'] ?? '') ?></h3>
                            
                            <?php if ($request['status'] == 'accepted' || $request['status'] == 'completed'): ?>
                            <div class="provider-contact">
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:<?= htmlspecialchars($request['provider_phone'] ?? '') ?>">
                                        <?= htmlspecialchars($request['provider_phone'] ?? '') ?>
                                    </a>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <a href="mailto:<?= htmlspecialchars($request['provider_email'] ?? '') ?>">
                                        <?= htmlspecialchars($request['provider_email'] ?? '') ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="provider-actions">
                                <a href="provider.php?id=<?= $request['provider_id'] ?>" class="btn btn-block">
                                    <i class="fas fa-user"></i> <?= __('view_provider_profile') ?>
                                </a>
                                
                                <?php if ($request['status'] == 'accepted'): ?>
                                <a href="tel:<?= htmlspecialchars($request['provider_phone'] ?? '') ?>" class="btn btn-outline btn-block">
                                    <i class="fas fa-phone"></i> <?= __('call_provider') ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="back-actions">
                    <a href="service_request.php" class="btn btn-outline btn-block">
                        <i class="fas fa-arrow-<?= $_SESSION['lang'] == 'ar' ? 'right' : 'left' ?>"></i> 
                        <?= __('back_to_requests') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #4e73df;
    --primary-hover: #2e59d9;
    --success-color: #1cc88a;
    --warning-color: #f6c23e;
    --danger-color: #e74a3b;
    --info-color: #36b9cc;
    --dark-color: #5a5c69;
    --light-color: #f8f9fc;
    --card-border-radius: 12px;
    --transition-speed: 0.3s;
}

.view-request-wrapper {
    background-color: #f8f9fc;
    padding: 40px 0;
    min-height: calc(100vh - 60px);
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.request-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-color);
}

.request-id {
    background-color: var(--primary-color);
    color: white;
    padding: 8px 15px;
    border-radius: 30px;
    font-weight: 500;
}

.request-id span {
    font-weight: 700;
}

.request-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.request-card {
    background-color: white;
    border-radius: var(--card-border-radius);
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.request-card-header {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    background-color: white;
}

.request-card-header h2 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--dark-color);
}

.request-card-body {
    padding: 25px;
}

.service-info {
    margin-bottom: 25px;
}

.info-row {
    display: flex;
    margin-bottom: 15px;
}

.info-label {
    width: 150px;
    font-weight: 600;
    color: var(--dark-color);
}

.info-value {
    flex: 1;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-pending {
    background-color: #fff8e1;
    color: #ffa000;
}

.status-completed {
    background-color: #e8f5e9;
    color: #4caf50;
}

.status-rejected {
    background-color: #ffebee;
    color: #f44336;
}

.status-accepted {
    background-color: #e3f2fd;
    color: #2196f3;
}

.message-section {
    background-color: #f8f9fc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.message-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.1rem;
    color: var(--dark-color);
}

.message-content {
    white-space: pre-line;
    line-height: 1.6;
}

.request-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 500;
    text-decoration: none;
    transition: all var(--transition-speed);
    border: none;
    cursor: pointer;
}

.btn i {
    margin-left: 8px;
}

.btn-block {
    display: flex;
    width: 100%;
    margin-bottom: 10px;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-hover);
    color: white;
    text-decoration: none;
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #c0392b;
    color: white;
    text-decoration: none;
}

.btn-outline {
    background-color: transparent;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.btn-outline:hover {
    background-color: var(--primary-color);
    color: white;
    text-decoration: none;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
}

.provider-info {
    text-align: center;
}

.provider-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: var(--light-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--primary-color);
    margin: 0 auto 15px;
}

.provider-info h3 {
    margin: 0 0 15px 0;
    font-size: 1.3rem;
    color: var(--dark-color);
}

.provider-contact {
    margin-bottom: 20px;
}

.contact-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    justify-content: center;
}

.contact-item i {
    margin-left: 10px;
    color: var(--primary-color);
}

.contact-item a {
    color: var(--dark-color);
    text-decoration: none;
    transition: color var(--transition-speed);
}

.contact-item a:hover {
    color: var(--primary-color);
    text-decoration: none;
}

.provider-actions {
    margin-top: 20px;
}

.back-actions {
    margin-top: 20px;
}

/* تنسيقات التقييم */
.review-submitted {
    margin-bottom: 20px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.review-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--dark-color);
}

.review-rating {
    display: flex;
    gap: 5px;
}

.review-rating i {
    color: #ddd;
}

.review-rating i.active {
    color: #ffc107;
}

.review-comment {
    background-color: #f8f9fc;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    line-height: 1.6;
    white-space: pre-line;
}

.review-date {
    font-size: 0.85rem;
    color: #777;
    margin-bottom: 15px;
}

.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.rating-input input {
    display: none;
}

.rating-input label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
    transition: color var(--transition-speed);
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input:checked ~ label {
    color: #ffc107;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark-color);
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: inherit;
    font-size: 1rem;
    transition: border-color var(--transition-speed);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

/* تنسيقات سجل الحالة */
.status-timeline {
    position: relative;
    padding-right: 30px;
}

.status-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    right: 10px;
    width: 2px;
    height: 100%;
    background-color: #ddd;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
    display: flex;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    top: 0;
    right: -30px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.timeline-icon i {
    font-size: 0.8rem;
}

.timeline-icon.status-pending i {
    color: #ffa000;
}

.timeline-icon.status-completed i {
    color: #4caf50;
}

.timeline-icon.status-rejected i {
    color: #f44336;
}

.timeline-icon.status-accepted i {
    color: #2196f3;
}

.timeline-content {
    background-color: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    flex: 1;
}

.timeline-date {
    font-size: 0.85rem;
    color: #777;
    margin-bottom: 5px;
}

.timeline-title {
    margin-bottom: 10px;
    font-weight: 500;
}

.timeline-notes {
    background-color: #f8f9fc;
    padding: 10px;
    border-radius: 8px;
    font-size: 0.9rem;
    white-space: pre-line;
}

/* تنسيقات التنبيهات */
.alert {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: #e8f5e9;
    color: #4caf50;
    border: 1px solid #c8e6c9;
}

.alert-danger {
    background-color: #ffebee;
    color: #f44336;
    border: 1px solid #ffcdd2;
}

/* تنسيقات متجاوبة */
@media (max-width: 992px) {
    .request-container {
        grid-template-columns: 1fr;
    }
    
    .request-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .view-request-wrapper {
        padding: 20px 0;
    }
    
    .request-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .request-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-label {
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تبديل نموذج التقييم
    const editReviewBtn = document.querySelector('.edit-review-btn');
    const cancelEditBtn = document.querySelector('.cancel-edit-btn');
    const reviewSubmitted = document.querySelector('.review-submitted');
    const reviewForm = document.querySelector('.review-form');
    
    if (editReviewBtn) {
        editReviewBtn.addEventListener('click', function() {
            reviewSubmitted.style.display = 'none';
            reviewForm.style.display = 'block';
        });
    }
    
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            reviewForm.style.display = 'none';
            reviewSubmitted.style.display = 'block';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
