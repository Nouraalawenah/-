<?php
// تضمين ملفات الإعداد
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// الحصول على معرف الخدمة من الرابط
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($service_id <= 0) {
    header("Location: services.php");
    exit;
}

// استعلام لجلب معلومات الخدمة
$service_sql = "SELECT s.*, 
                c.id as category_id, c.name as category_name, c.icon as category_icon,
                p.id as provider_id, p.name as provider_name, p.image as provider_image,
                p.address as provider_address, p.rating as provider_rating,
                p.phone as provider_phone, p.email as provider_email
                FROM services s
                JOIN categories c ON s.category_id = c.id
                JOIN service_providers p ON s.provider_id = p.id
                WHERE s.id = ?";
$service_stmt = $conn->prepare($service_sql);
$service_stmt->bind_param("i", $service_id);
$service_stmt->execute();
$service_result = $service_stmt->get_result();

if ($service_result->num_rows == 0) {
    header("Location: services.php");
    exit;
}

$service = $service_result->fetch_assoc();

// استعلام لجلب الخدمات المشابهة
$similar_services_sql = "SELECT s.id, s.name, s.price, s.description,
                        p.id as provider_id, p.name as provider_name, p.rating as provider_rating
                        FROM services s
                        JOIN service_providers p ON s.provider_id = p.id
                        WHERE s.category_id = ? AND s.id != ?
                        ORDER BY p.rating DESC
                        LIMIT 4";
$similar_stmt = $conn->prepare($similar_services_sql);
$similar_stmt->bind_param("ii", $service['category_id'], $service_id);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();
$similar_services = [];

if ($similar_result->num_rows > 0) {
    while ($row = $similar_result->fetch_assoc()) {
        $similar_services[] = $row;
    }
}

// استعلام لجلب التقييمات للخدمة - تحقق من هيكل الجدول أولاً
$check_users_table = "DESCRIBE users";
$users_columns_result = $conn->query($check_users_table);
$user_name_column = 'username'; // القيمة الافتراضية
$user_image_column = null; // القيمة الافتراضية لعمود الصورة

if ($users_columns_result) {
    while ($column = $users_columns_result->fetch_assoc()) {
        if ($column['Field'] == 'name') {
            $user_name_column = 'name';
        } else if ($column['Field'] == 'full_name') {
            $user_name_column = 'full_name';
        } else if (in_array($column['Field'], ['profile_image', 'image', 'avatar'])) {
            $user_image_column = $column['Field'];
        }
    }
}

// بناء استعلام SQL ديناميكي بناءً على الأعمدة المتاحة
$reviews_sql = "SELECT sr.rating, sr.comment, sr.created_at,
                u.$user_name_column as user_name";
                
// إضافة عمود الصورة إذا كان موجودًا
if ($user_image_column) {
    $reviews_sql .= ", u.$user_image_column as profile_image";
} else {
    $reviews_sql .= ", NULL as profile_image";
}

$reviews_sql .= " FROM service_reviews sr
                JOIN users u ON sr.user_id = u.id
                WHERE sr.service_id = ?
                ORDER BY sr.created_at DESC
                LIMIT 5";

$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $service_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews = [];

if ($reviews_result->num_rows > 0) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// حساب متوسط التقييم
$avg_rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                  FROM service_reviews 
                  WHERE service_id = ?";
$avg_stmt = $conn->prepare($avg_rating_sql);
$avg_stmt->bind_param("i", $service_id);
$avg_stmt->execute();
$avg_result = $avg_stmt->get_result();
$rating_data = $avg_result->fetch_assoc();

$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$total_reviews = $rating_data['total_reviews'] ? $rating_data['total_reviews'] : 0;

// تعيين عنوان الصفحة
$page_title = $service['name'] ?? __('service_details');

// تضمين ملف الرأس
include 'includes/header.php';
?>

<div class="service-details-wrapper">
    <div class="container">
        <?php
        // عرض مسار التنقل
        $breadcrumbs = [
            ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
            ['title' => __('services'), 'url' => 'services.php', 'icon' => 'fa-list'],
            ['title' => $service['category_name'], 'url' => 'services.php?category=' . $service['category_id'], 'icon' => 'fa-' . $service['category_icon']],
            ['title' => $service['name'], 'active' => true]
        ];
        
        include 'includes/breadcrumb.php';
        if (function_exists('display_breadcrumbs')) {
            display_breadcrumbs($breadcrumbs);
        }
        ?>
        
        <div class="service-details-container">
            <div class="service-main">
                <div class="service-card">
                    <div class="service-header">
                        <div class="service-category">
                            <a href="services.php?category=<?= $service['category_id'] ?>">
                                <i class="fas fa-<?= $service['category_icon'] ?? 'tag' ?>"></i>
                                <?= htmlspecialchars($service['category_name'] ?? '') ?>
                            </a>
                        </div>
                        <div class="service-price">
                            <span class="price-value"><?= number_format($service['price'] ?? 0, 2) ?></span>
                            <span class="price-currency"><?= __('currency') ?></span>
                        </div>
                    </div>
                    
                    <div class="service-content">
                        <h1 class="service-title"><?= htmlspecialchars($service['name'] ?? '') ?></h1>
                        
                        <div class="service-provider">
                            <div class="provider-info">
                                <i class="fas fa-user"></i>
                                <?= __('provided_by') ?>:
                                <a href="provider.php?id=<?= $service['provider_id'] ?>">
                                    <?= htmlspecialchars($service['provider_name'] ?? '') ?>
                                </a>
                            </div>
                            
                            <?php if ($service['provider_rating'] > 0): ?>
                            <div class="provider-rating">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $service['provider_rating']) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $service['provider_rating']) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span>(<?= number_format($service['provider_rating'], 1) ?>)</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="service-description">
                            <?= nl2br(htmlspecialchars($service['description'] ?? '')) ?>
                        </div>
                        
                        <?php if (!empty($service['features'])): ?>
                        <div class="service-features">
                            <h3><?= __('service_features') ?></h3>
                            <ul>
                                <?php 
                                $features = explode("\n", $service['features']);
                                foreach ($features as $feature):
                                    if (!empty(trim($feature))):
                                ?>
                                <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars(trim($feature)) ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="service-actions">
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="service_request.php?service_id=<?= $service_id ?>" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> <?= __('request_service') ?>
                            </a>
                            <?php else: ?>
                            <a href="login.php?redirect=service_details.php?id=<?= $service_id ?>" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> <?= __('login_to_request') ?>
                            </a>
                            <?php endif; ?>
                            
                            <a href="provider.php?id=<?= $service['provider_id'] ?>" class="btn btn-outline">
                                <i class="fas fa-user"></i> <?= __('view_provider_profile') ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($reviews)): ?>
                <div class="service-card">
                    <div class="card-header">
                        <h2><?= __('customer_reviews') ?></h2>
                        <div class="rating-summary">
                            <div class="average-rating">
                                <span class="rating-value"><?= number_format($avg_rating, 1) ?></span>
                                <div class="rating-stars">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $avg_rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i - 0.5 <= $avg_rating) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="total-reviews"><?= sprintf(__('based_on_reviews'), $total_reviews) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <div class="reviewer-avatar">
                                            <?php if (!empty($review['profile_image'])): ?>
                                            <img src="images/users/<?= $review['profile_image'] ?>" alt="<?= htmlspecialchars($review['user_name'] ?? '') ?>">
                                            <?php else: ?>
                                            <i class="fas fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="reviewer-details">
                                            <div class="reviewer-name"><?= htmlspecialchars($review['user_name'] ?? '') ?></div>
                                            <div class="review-date"><?= date('d M Y', strtotime($review['created_at'])) ?></div>
                                        </div>
                                    </div>
                                    <div class="review-rating">
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
                                <div class="review-content">
                                    <?= nl2br(htmlspecialchars($review['comment'] ?? '')) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($total_reviews > 5): ?>
                        <div class="view-all-reviews">
                            <a href="service_reviews.php?id=<?= $service_id ?>" class="btn btn-outline">
                                <?= __('view_all_reviews') ?> (<?= $total_reviews ?>)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($similar_services)): ?>
                <div class="service-card">
                    <div class="card-header">
                        <h2><?= __('similar_services') ?></h2>
                    </div>
                    
                    <div class="card-body">
                        <div class="similar-services">
                            <?php foreach ($similar_services as $similar): ?>
                            <div class="similar-service-item">
                                <div class="similar-service-content">
                                    <h3>
                                        <a href="service_details.php?id=<?= $similar['id'] ?>">
                                            <?= htmlspecialchars($similar['name'] ?? '') ?>
                                        </a>
                                    </h3>
                                    <div class="similar-service-provider">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($similar['provider_name'] ?? '') ?>
                                        
                                        <?php if ($similar['provider_rating'] > 0): ?>
                                        <div class="provider-rating">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $similar['provider_rating']) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } elseif ($i - 0.5 <= $similar['provider_rating']) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="similar-service-price">
                                        <?= number_format($similar['price'], 2) ?> <?= __('currency') ?>
                                    </div>
                                    <div class="similar-service-description">
                                        <?php 
                                        $desc = $similar['description'] ?? '';
                                        echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                        ?>
                                    </div>
                                </div>
                                <div class="similar-service-actions">
                                    <a href="service_details.php?id=<?= $similar['id'] ?>" class="btn btn-sm">
                                        <?= __('view_details') ?>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="service-sidebar">
                <div class="provider-card">
                    <div class="provider-header">
                        <h2><?= __('service_provider') ?></h2>
                    </div>
                    
                    <div class="provider-body">
                        <div class="provider-profile">
                            <div class="provider-avatar">
                                <?php if (!empty($service['provider_image'])): ?>
                                <img src="images/providers/<?= $service['provider_image'] ?>" alt="<?= htmlspecialchars($service['provider_name'] ?? '') ?>">
                                <?php else: ?>
                                <i class="fas fa-user-tie"></i>
                                <?php endif; ?>
                            </div>
                            
                            <h3><?= htmlspecialchars($service['provider_name'] ?? '') ?></h3>
                            
                            <?php if ($service['provider_rating'] > 0): ?>
                            <div class="provider-rating">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $service['provider_rating']) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $service['provider_rating']) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span>(<?= number_format($service['provider_rating'], 1) ?>)</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($service['provider_address'])): ?>
                            <div class="provider-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($service['provider_address'] ?? '') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="provider-actions">
                            <a href="provider.php?id=<?= $service['provider_id'] ?>" class="btn btn-block">
                                <i class="fas fa-user"></i> <?= __('view_profile') ?>
                            </a>
                            
                            <a href="services.php?provider=<?= $service['provider_id'] ?>" class="btn btn-outline btn-block">
                                <i class="fas fa-list"></i> <?= __('all_provider_services') ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="request-card">
                    <div class="request-header">
                        <h2><?= __('request_this_service') ?></h2>
                    </div>
                    
                    <div class="request-body">
                        <div class="price-box">
                            <div class="price-label"><?= __('service_price') ?>:</div>
                            <div class="price-value">
                                <?= number_format($service['price'], 2) ?> <?= __('currency') ?>
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="service_request.php?service_id=<?= $service_id ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-shopping-cart"></i> <?= __('request_now') ?>
                        </a>
                        <?php else: ?>
                        <a href="login.php?redirect=service_details.php?id=<?= $service_id ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> <?= __('login_to_request') ?>
                        </a>
                        <?php endif; ?>
                        
                        <div class="request-note">
                            <i class="fas fa-info-circle"></i>
                            <?= __('request_note') ?>
                        </div>
                    </div>
                </div>
                
                <div class="share-card">
                    <div class="share-header">
                        <h2><?= __('share_service') ?></h2>
                    </div>
                    
                    <div class="share-body">
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(getCurrentUrl()) ?>" target="_blank" class="share-btn facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?= urlencode(getCurrentUrl()) ?>&text=<?= urlencode($service['name']) ?>" target="_blank" class="share-btn twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://api.whatsapp.com/send?text=<?= urlencode($service['name'] . ' - ' . getCurrentUrl()) ?>" target="_blank" class="share-btn whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="mailto:?subject=<?= urlencode($service['name']) ?>&body=<?= urlencode(__('check_out_service') . ': ' . getCurrentUrl()) ?>" class="share-btn email">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
                    </div>
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

.service-details-wrapper {
    background-color: #f8f9fc;
    padding: 40px 0;
    min-height: calc(100vh - 60px);
}

.service-details-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-top: 30px;
}

.service-card {
    background-color: #fff;
    border-radius: var(--card-border-radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 30px;
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.service-category a {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    font-weight: 500;
    text-decoration: none;
    transition: color var(--transition-speed);
}

.service-category a:hover {
    color: var(--primary-hover);
}

.service-category i {
    margin-left: 8px;
    font-size: 0.9rem;
}

.service-price {
    background-color: var(--primary-color);
    color: white;
    padding: 8px 15px;
    border-radius: 30px;
    font-weight: 700;
    display: flex;
    align-items: center;
}

.price-value {
    font-size: 1.2rem;
    margin-left: 5px;
}

.price-currency {
    font-size: 0.9rem;
}

.service-content {
    padding: 30px;
}

.service-title {
    margin: 0 0 20px 0;
    font-size: 1.8rem;
    color: var(--dark-color);
}

.service-provider {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.provider-info {
    display: flex;
    align-items: center;
}

.provider-info i {
    margin-left: 8px;
    color: var(--primary-color);
}

.provider-info a {
    color: var(--dark-color);
    font-weight: 500;
    text-decoration: none;
    transition: color var(--transition-speed);
}

.provider-info a:hover {
    color: var(--primary-color);
}

.provider-rating {
    display: flex;
    align-items: center;
}

.provider-rating i {
    color: #ffc107;
    margin-left: 2px;
}

.provider-rating span {
    margin-right: 5px;
    color: var(--dark-color);
}

.service-description {
    margin-bottom: 30px;
    line-height: 1.8;
    color: #666;
}

.service-features {
    margin-bottom: 30px;
}

.service-features h3 {
    margin-bottom: 15px;
    font-size: 1.2rem;
    color: var(--dark-color);
}

.service-features ul {
    list-style: none;
    padding: 0;
}

.service-features li {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #eee;
}

.service-features li:last-child {
    border-bottom: none;
}

.service-features li i {
    color: var(--success-color);
    margin-left: 10px;
}

.service-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
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

.card-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    margin: 0;
    font-size: 1.3rem;
    color: var(--dark-color);
}

.card-body {
    padding: 20px;
}

.rating-summary {
    display: flex;
    align-items: center;
    margin-top: 10px;
}

.average-rating {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.rating-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-left: 10px;
}

.rating-stars {
    display: flex;
    margin: 0 10px;
}

.rating-stars i {
    color: #ffc107;
    margin-left: 2px;
}

.total-reviews {
    font-size: 0.9rem;
    color: #777;
}

.reviews-list {
    margin-bottom: 20px;
}

.review-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.review-item:last-child {
    border-bottom: none;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.reviewer-info {
    display: flex;
    align-items: center;
}

.reviewer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--light-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 10px;
    overflow: hidden;
}

.reviewer-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.reviewer-avatar i {
    color: #aaa;
    font-size: 1.2rem;
}

.reviewer-details {
    display: flex;
    flex-direction: column;
}

.reviewer-name {
    font-weight: 500;
    color: var(--dark-color);
}

.review-date {
    font-size: 0.8rem;
    color: #777;
}

.review-rating i {
    color: #ffc107;
    margin-right: 2px;
}

.review-content {
    line-height: 1.6;
    color: #666;
}

.view-all-reviews {
    text-align: center;
    margin-top: 20px;
}

.similar-services {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.similar-service-item {
    background-color: var(--light-color);
    border-radius: 10px;
    padding: 15px;
    transition: transform var(--transition-speed), box-shadow var(--transition-speed);
}

.similar-service-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.similar-service-content h3 {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
}

.similar-service-content h3 a {
    color: var(--dark-color);
    text-decoration: none;
    transition: color var(--transition-speed);
}

.similar-service-content h3 a:hover {
    color: var(--primary-color);
}

.similar-service-provider {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 0.9rem;
    color: #666;
}

.similar-service-provider i {
    margin-left: 5px;
    color: var(--primary-color);
}

.similar-service-provider .provider-rating {
    margin-right: 5px;
}

.similar-service-provider .provider-rating i {
    font-size: 0.8rem;
    color: #ffc107;
}

.similar-service-price {
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.similar-service-description {
    font-size: 0.9rem;
    color: #777;
    margin-bottom: 15px;
    line-height: 1.5;
}

.similar-service-actions {
    text-align: left;
}

.provider-card, .request-card, .share-card {
    background-color: #fff;
    border-radius: var(--card-border-radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 30px;
}

.provider-header, .request-header, .share-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.provider-header h2, .request-header h2, .share-header h2 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--dark-color);
}

.provider-body, .request-body, .share-body {
    padding: 20px;
}

.provider-profile {
    text-align: center;
    margin-bottom: 20px;
}

.provider-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: var(--light-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    overflow: hidden;
}

.provider-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.provider-avatar i {
    font-size: 2.5rem;
    color: #aaa;
}

.provider-profile h3 {
    margin: 0 0 10px 0;
    font-size: 1.3rem;
    color: var(--dark-color);
}

.provider-profile .provider-rating {
    justify-content: center;
    margin-bottom: 15px;
}

.provider-address {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 0.9rem;
}

.provider-address i {
    margin-left: 5px;
    color: var(--primary-color);
}

.price-box {
    background-color: var(--light-color);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price-label {
    font-weight: 500;
    color: var(--dark-color);
}

.price-box .price-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--primary-color);
}

.request-note {
    margin-top: 15px;
    padding: 10px;
    background-color: #fff3cd;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #856404;
    display: flex;
    align-items: flex-start;
}

.request-note i {
    margin-left: 8px;
    margin-top: 3px;
}

.share-buttons {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.share-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 40px;
    border-radius: 8px;
    color: white;
    transition: opacity var(--transition-speed);
}

.share-btn:hover {
    opacity: 0.9;
    color: white;
    text-decoration: none;
}

.share-btn.facebook {
    background-color: #3b5998;
}

.share-btn.twitter {
    background-color: #1da1f2;
}

.share-btn.whatsapp {
    background-color: #25d366;
}

.share-btn.email {
    background-color: #ea4335;
}

@media (max-width: 992px) {
    .service-details-container {
        grid-template-columns: 1fr;
    }
    
    .service-sidebar {
        order: -1;
    }
    
    .provider-card, .request-card, .share-card {
        margin-bottom: 20px;
    }
}

@media (max-width: 768px) {
    .service-details-wrapper {
        padding: 20px 0;
    }
    
    .service-header, .service-provider, .service-actions {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .service-price {
        margin-top: 10px;
    }
    
    .provider-rating {
        margin-top: 10px;
    }
    
    .service-actions {
        gap: 10px;
    }
    
    .btn {
        width: 100%;
    }
    
    .similar-services {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// دالة للحصول على الرابط الحالي
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . "://" . $host . $uri;
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحريك الصفحة إلى الأعلى عند التحميل
    window.scrollTo(0, 0);
    
    // تفعيل أزرار المشاركة
    const shareButtons = document.querySelectorAll('.share-btn');
    shareButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            window.open(this.href, 'share-dialog', 'width=800,height=600');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>


