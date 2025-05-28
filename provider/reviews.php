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

// تحديد الصفحة الحالية للتصفح
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// جلب إجمالي عدد التقييمات
$count_sql = "SELECT COUNT(*) as total FROM reviews WHERE provider_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $provider_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_reviews = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $per_page);

// جلب متوسط التقييم
$avg_sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE provider_id = ?";
$avg_stmt = $conn->prepare($avg_sql);
$avg_stmt->bind_param("i", $provider_id);
$avg_stmt->execute();
$avg_result = $avg_stmt->get_result();
$avg_rating = $avg_result->fetch_assoc()['avg_rating'] ?: 0;

// جلب التقييمات مع التصفح
$reviews_sql = "SELECT r.*, u.username, u.image as user_image, s.name as service_name 
               FROM reviews r 
               JOIN users u ON r.user_id = u.id 
               JOIN services s ON r.service_id = s.id 
               WHERE r.provider_id = ? 
               ORDER BY r.created_at DESC LIMIT ?, ?";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("iii", $provider_id, $offset, $per_page);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'reviews';
$page_title = __('reviews');

// تضمين ملف الهيدر
include 'includes/header.php';
?>

<div class="provider-content">
    <div class="provider-content-header">
        <h1><?php echo __('reviews'); ?></h1>
    </div>
    
    <div class="provider-card">
        <div class="provider-card-header">
            <h3><?php echo __('rating_summary'); ?></h3>
        </div>
        <div class="provider-card-body">
            <div class="provider-rating-summary">
                <div class="provider-rating-average">
                    <div class="provider-rating-number"><?php echo number_format($avg_rating, 1); ?></div>
                    <div class="provider-rating-stars">
                        <?php
                        $full_stars = floor($avg_rating);
                        $half_star = $avg_rating - $full_stars >= 0.5;
                        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                        
                        for ($i = 0; $i < $full_stars; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        
                        if ($half_star) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        }
                        
                        for ($i = 0; $i < $empty_stars; $i++) {
                            echo '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <div class="provider-rating-count">
                        <?php echo sprintf(__('based_on_reviews'), $total_reviews); ?>
                    </div>
                </div>
                
                <div class="provider-rating-bars">
                    <?php
                    // جلب توزيع التقييمات
                    for ($i = 5; $i >= 1; $i--) {
                        $rating_count_sql = "SELECT COUNT(*) as count FROM reviews WHERE provider_id = ? AND rating = ?";
                        $rating_count_stmt = $conn->prepare($rating_count_sql);
                        $rating_count_stmt->bind_param("ii", $provider_id, $i);
                        $rating_count_stmt->execute();
                        $rating_count_result = $rating_count_stmt->get_result();
                        $rating_count = $rating_count_result->fetch_assoc()['count'];
                        
                        $percentage = $total_reviews > 0 ? ($rating_count / $total_reviews) * 100 : 0;
                    ?>
                        <div class="provider-rating-bar">
                            <div class="provider-rating-label"><?php echo $i; ?> <i class="fas fa-star"></i></div>
                            <div class="provider-rating-progress">
                                <div class="provider-rating-progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="provider-rating-count"><?php echo $rating_count; ?></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="provider-card mt-4">
        <div class="provider-card-header">
            <h3><?php echo __('all_reviews'); ?></h3>
        </div>
        <div class="provider-card-body">
            <?php if ($reviews_result->num_rows > 0): ?>
                <div class="provider-reviews-list">
                    <?php while ($review = $reviews_result->fetch_assoc()): ?>
                        <div class="provider-review-item">
                            <div class="provider-review-header">
                                <div class="provider-review-user">
                                    <?php if (!empty($review['user_image'])): ?>
                                        <img src="../images/users/<?php echo $review['user_image']; ?>" alt="<?php echo htmlspecialchars($review['username']); ?>" class="provider-review-avatar">
                                    <?php else: ?>
                                        <div class="provider-review-avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="provider-review-user-info">
                                        <h4 class="provider-review-username"><?php echo htmlspecialchars($review['username']); ?></h4>
                                        <div class="provider-review-date"><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></div>
                                    </div>
                                </div>
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
                                </div>
                            </div>
                            <div class="provider-review-service">
                                <?php echo __('service'); ?>: <strong><?php echo htmlspecialchars($review['service_name']); ?></strong>
                            </div>
                            <div class="provider-review-content">
                                <?php echo htmlspecialchars($review['comment']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="provider-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="provider-pagination-item">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="provider-pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="provider-pagination-item">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="provider-empty-state">
                    <i class="fas fa-star"></i>
                    <p><?php echo __('no_reviews_yet'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

