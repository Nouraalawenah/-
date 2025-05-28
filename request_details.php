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
// تعديل الاستعلام لإزالة عمود sr.notes غير موجود
$request_sql = "SELECT sr.*, s.name_" . $_SESSION['lang'] . " as service_name, 
                s.id as service_id, s.provider_id, s.price,
                sp.name_" . $_SESSION['lang'] . " as provider_name, 
                sp.phone as provider_phone, sp.email as provider_email,
                sr.created_at, sr.status, sr.scheduled_date, sr.message,
                sr.updated_at, sr.completed_at
                FROM service_requests sr
                JOIN services s ON sr.service_id = s.id
                JOIN service_providers sp ON s.provider_id = sp.id
                LEFT JOIN users u ON sp.user_id = u.id
                WHERE sr.id = ? AND sr.user_id = ?";

$request_stmt = $conn->prepare($request_sql);
$request_stmt->bind_param("ii", $request_id, $user_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();

if ($request_result->num_rows == 0) {
    header("Location: profile.php");
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review']) && $has_reviews_table) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
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

// تعديل طريقة عرض التواريخ في صفحة تفاصيل الطلب
// بعد استرجاع بيانات الطلب من قاعدة البيانات

// التحقق من صحة تواريخ الطلب وتصحيحها إذا كانت في المستقبل
$current_time = time();

// تصحيح تاريخ الإنشاء
$created_timestamp = strtotime($request['created_at']);
if ($created_timestamp > $current_time) {
    $request['created_at'] = date('Y-m-d H:i:s', $current_time);
}

// تصحيح تاريخ التحديث
$updated_timestamp = strtotime($request['updated_at']);
if ($updated_timestamp > $current_time) {
    $request['updated_at'] = date('Y-m-d H:i:s', $created_timestamp);
}

// تصحيح تاريخ الإكمال إذا كان موجودًا
if (!empty($request['completed_at'])) {
    $completed_timestamp = strtotime($request['completed_at']);
    if ($completed_timestamp > $current_time) {
        $request['completed_at'] = $request['updated_at'];
    }
}

// تصحيح تاريخ الموعد المجدول إذا كان موجودًا
if (!empty($request['scheduled_date'])) {
    $scheduled_timestamp = strtotime($request['scheduled_date']);
    if ($scheduled_timestamp > $current_time && $request['status'] == 'completed') {
        $request['scheduled_date'] = $request['created_at'];
    }
}
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('request_details'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .request-details-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            padding: 30px;
            margin: 40px 0;
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .request-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .request-date {
            color: var(--text-muted);
        }
        
        .request-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .request-info {
                grid-template-columns: 1fr;
            }
        }
        
        .request-section {
            margin-bottom: 20px;
        }
        
        .request-section h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
            padding-bottom: 5px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .request-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .provider-info {
            background-color: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
        }
        
        .provider-info p {
            margin-bottom: 10px;
        }
        
        .provider-info i {
            width: 20px;
            color: var(--primary-color);
        }
        
        .request-message {
            background-color: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .request-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .review-form {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            margin-bottom: 15px;
        }
        
        .rating-input input {
            display: none;
        }
        
        .rating-input label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            padding: 0 5px;
        }
        
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: #ffc107;
        }
        
        .existing-review {
            background-color: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .review-rating {
            margin-bottom: 10px;
        }
        
        .review-rating i {
            color: #ffc107;
        }
        
        .review-date {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .status-history {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .status-history-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--border-color);
        }
        
        .status-history-item:last-child {
            border-bottom: none;
        }
        
        .status-history-status {
            font-weight: bold;
        }
        
        .status-history-date {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .request-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-top: 10px;
        }
        
        .request-notes {
            background-color: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <?php
        include 'includes/breadcrumb.php';
        
        $breadcrumbs = [
            ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
            ['title' => __('profile'), 'url' => 'profile.php', 'icon' => 'fa-user'],
            ['title' => __('request_details'), 'active' => true]
        ];
        
        display_breadcrumbs($breadcrumbs);
        ?>
        
        <div class="request-details-container">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="request-header">
                <div class="request-title">
                    <?php echo htmlspecialchars($request['service_name'] ?? ''); ?>
                    <div class="request-id"><?php echo __('request_id'); ?>: #<?php echo $request_id; ?></div>
                </div>
                <div class="request-date">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('d/m/Y', strtotime($request['created_at'])); ?>
                </div>
            </div>
            
            <div class="request-info">
                <div>
                    <div class="request-section">
                        <h3><?php echo __('request_status'); ?></h3>
                        <p>
                            <span class="request-status status-<?php echo $request['status']; ?>">
                                <?php echo __('status_' . $request['status']); ?>
                            </span>
                        </p>
                        
                        <?php if (!empty($request['scheduled_date'])): ?>
                        <p>
                            <strong><?php echo __('scheduled_date'); ?>:</strong>
                            <?php echo date('d/m/Y', strtotime($request['scheduled_date'])); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($request['price'])): ?>
                        <p class="request-price">
                            <strong><?php echo __('service_price'); ?>:</strong>
                            <?php echo number_format($request['price'], 2); ?> <?php echo __('currency'); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($request['updated_at']) && $request['updated_at'] != $request['created_at']): ?>
                        <p>
                            <strong><?php echo __('last_updated'); ?>:</strong>
                            <?php echo date('d/m/Y H:i', strtotime($request['updated_at'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($request['message'])): ?>
                    <div class="request-section">
                        <h3><?php echo __('your_message'); ?></h3>
                        <div class="request-message">
                            <?php echo nl2br(htmlspecialchars($request['message'] ?? '')); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($has_status_history_table && !empty($status_history)): ?>
                    <div class="request-section">
                        <h3><?php echo __('status_history'); ?></h3>
                        <div class="status-history">
                            <?php foreach ($status_history as $history): ?>
                            <div class="status-history-item">
                                <div class="status-history-status">
                                    <span class="request-status status-<?php echo $history['status']; ?>">
                                        <?php echo __('status_' . $history['status']); ?>
                                    </span>
                                </div>
                                <div class="status-history-date">
                                    <?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <div class="request-section">
                        <h3><?php echo __('provider_information'); ?></h3>
                        <div class="provider-info">
                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($request['provider_name'] ?? ''); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['provider_phone'] ?? ''); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($request['provider_email'] ?? ''); ?></p>
                        </div>
                        
                        <div class="request-actions">
                            <a href="provider.php?id=<?php echo $request['provider_id']; ?>" class="btn">
                                <?php echo __('view_provider_profile'); ?>
                            </a>
                            
                            <?php if ($request['status'] == 'accepted'): ?>
                            <a href="tel:<?php echo $request['provider_phone']; ?>" class="btn btn-outline">
                                <i class="fas fa-phone"></i> <?php echo __('call_provider'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="request-section">
                        <h3><?php echo __('service_information'); ?></h3>
                        <div class="service-info">
                            <p><strong><?php echo __('service_name'); ?>:</strong> <?php echo htmlspecialchars($request['service_name'] ?? ''); ?></p>
                            <p><strong><?php echo __('service_price'); ?>:</strong> <?php echo number_format($request['price'], 2); ?> <?php echo __('currency'); ?></p>
                            <div class="request-actions">
                                <a href="service_details.php?id=<?php echo $request['service_id']; ?>" class="btn btn-outline">
                                    <?php echo __('view_service_details'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="request-section" id="review">
                <h3><?php echo __('rate_this_service'); ?></h3>
                
                <div class="review-prompt">
                    <p><?php echo __('please_rate_service'); ?></p>
                    <div class="review-benefits">
                        <p><i class="fas fa-check-circle"></i> <?php echo __('review_benefit_1'); ?></p>
                        <p><i class="fas fa-check-circle"></i> <?php echo __('review_benefit_2'); ?></p>
                    </div>
                </div>
                
                <form method="post" action="" class="review-form">
                    <div class="form-group">
                        <label for="rating"><?php echo __('rating'); ?> <span class="required">*</span></label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="rating-<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                            <label for="rating-<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-help"><?php echo __('click_to_rate'); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment"><?php echo __('your_feedback'); ?> <span class="required">*</span></label>
                        <textarea name="comment" id="comment" rows="4" class="form-control" required></textarea>
                        <div class="form-help"><?php echo __('review_help_text'); ?></div>
                    </div>
                    
                    <button type="submit" name="submit_review" class="btn btn-primary">
                        <?php echo __('submit_review'); ?>
                    </button>
                </form>
            </div>
            
            <div class="request-actions">
                <a href="profile.php" class="btn btn-outline">
                    <i class="fas fa-arrow-<?php echo $_SESSION['lang'] == 'ar' ? 'right' : 'left'; ?>"></i> 
                    <?php echo __('back_to_profile'); ?>
                </a>
                
                <?php if ($request['status'] == 'pending'): ?>
                <a href="cancel_request.php?id=<?php echo $request_id; ?>" class="btn btn-danger" onclick="return confirm('<?php echo __('confirm_cancel_request'); ?>');">
                    <?php echo __('cancel_request'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // إضافة تأثيرات للتقييم
        const ratingInputs = document.querySelectorAll('.rating-input input');
        const ratingLabels = document.querySelectorAll('.rating-input label');
        
        ratingLabels.forEach(label => {
            label.addEventListener('mouseover', function() {
                const currentId = this.getAttribute('for');
                const currentRating = currentId.split('-')[1];
                
                ratingLabels.forEach(l => {
                    const id = l.getAttribute('for');
                    const rating = id.split('-')[1];
                    
                    if (rating <= currentRating) {
                        l.classList.add('hover');
                    } else {
                        l.classList.remove('hover');
                    }
                });
            });
            
            label.addEventListener('mouseout', function() {
                ratingLabels.forEach(l => {
                    l.classList.remove('hover');
                });
            });
        });
        
        // إضافة وظائف لأزرار تحرير التقييم
        const editReviewBtn = document.getElementById('edit-review-btn');
        const reviewForm = document.getElementById('review-form');
        const cancelEditBtn = document.getElementById('cancel-edit');
        
        if (editReviewBtn && reviewForm) {
            editReviewBtn.addEventListener('click', function() {
                const existingReview = editReviewBtn.closest('.existing-review');
                if (existingReview) {
                    existingReview.style.display = 'none';
                }
                reviewForm.style.display = 'block';
            });
        }
        
        if (cancelEditBtn && reviewForm) {
            cancelEditBtn.addEventListener('click', function() {
                const existingReview = document.querySelector('.existing-review');
                if (existingReview) {
                    existingReview.style.display = 'block';
                }
                reviewForm.style.display = 'none';
            });
        }
    });
    </script>
</body>
</html>

