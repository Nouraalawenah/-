<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات مزود الخدمة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_provider']) {
    header("Location: ../login.php");
    exit;
}

// جلب إحصائيات مزود الخدمة
$provider_id = $_SESSION['user_id'];

// عدد الخدمات
$services_sql = "SELECT COUNT(*) as count FROM services WHERE provider_id = ?";
$services_stmt = $conn->prepare($services_sql);
$services_stmt->bind_param("i", $provider_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();
$services_count = $services_result->fetch_assoc()['count'];

// عدد الطلبات
$requests_sql = "SELECT COUNT(*) as count FROM service_requests WHERE provider_id = ?";
$requests_stmt = $conn->prepare($requests_sql);
$requests_stmt->bind_param("i", $provider_id);
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();
$requests_count = $requests_result->fetch_assoc()['count'];

// عدد التقييمات
$reviews_sql = "SELECT COUNT(*) as count FROM reviews WHERE provider_id = ?";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $provider_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews_count = $reviews_result->fetch_assoc()['count'];

// متوسط التقييم
$rating_sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE provider_id = ?";
$rating_stmt = $conn->prepare($rating_sql);
$rating_stmt->bind_param("i", $provider_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$avg_rating = $rating_result->fetch_assoc()['avg_rating'] ?: 0;

// آخر الطلبات
$recent_requests_sql = "SELECT sr.*, s.name as service_name, u.username as client_name 
                       FROM service_requests sr 
                       JOIN services s ON sr.service_id = s.id 
                       JOIN users u ON sr.client_id = u.id 
                       WHERE sr.provider_id = ? 
                       ORDER BY sr.created_at DESC LIMIT 5";
$recent_requests_stmt = $conn->prepare($recent_requests_sql);
$recent_requests_stmt->bind_param("i", $provider_id);
$recent_requests_stmt->execute();
$recent_requests_result = $recent_requests_stmt->get_result();

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'dashboard';
$page_title = __('dashboard');

// تضمين ملف الهيدر الذي يحتوي على الشريط الجانبي
include 'includes/header.php';
?>

<div class="provider-content">
    <div class="provider-content-header">
        <h1><?php echo __('dashboard'); ?></h1>
    </div>
    
    <div class="provider-stats-grid">
        <div class="provider-stat-card">
            <div class="provider-stat-icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="provider-stat-info">
                <h3><?php echo $services_count; ?></h3>
                <p><?php echo __('services'); ?></p>
            </div>
        </div>
        
        <div class="provider-stat-card">
            <div class="provider-stat-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="provider-stat-info">
                <h3><?php echo $requests_count; ?></h3>
                <p><?php echo __('requests'); ?></p>
            </div>
        </div>
        
        <div class="provider-stat-card">
            <div class="provider-stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="provider-stat-info">
                <h3><?php echo $reviews_count; ?></h3>
                <p><?php echo __('reviews'); ?></p>
            </div>
        </div>
        
        <div class="provider-stat-card">
            <div class="provider-stat-icon">
                <i class="fas fa-star-half-alt"></i>
            </div>
            <div class="provider-stat-info">
                <h3><?php echo number_format($avg_rating, 1); ?></h3>
                <p><?php echo __('average_rating'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="provider-row">
        <div class="provider-col-lg-8">
            <div class="provider-card">
                <div class="provider-card-header">
                    <h2><?php echo __('recent_requests'); ?></h2>
                </div>
                <div class="provider-card-body">
                    <?php if ($recent_requests_result->num_rows > 0): ?>
                        <div class="provider-table-responsive">
                            <table class="provider-table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('id'); ?></th>
                                        <th><?php echo __('service'); ?></th>
                                        <th><?php echo __('client'); ?></th>
                                        <th><?php echo __('status'); ?></th>
                                        <th><?php echo __('date'); ?></th>
                                        <th><?php echo __('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = $recent_requests_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $request['id']; ?></td>
                                            <td><?php echo htmlspecialchars($request['service_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['client_name']); ?></td>
                                            <td>
                                                <span class="provider-badge provider-badge-<?php echo strtolower($request['status']); ?>">
                                                    <?php echo __($request['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <a href="view_request.php?id=<?php echo $request['id']; ?>" class="provider-btn provider-btn-sm provider-btn-primary">
                                                    <i class="fas fa-eye"></i> <?php echo __('view'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="provider-empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <p><?php echo __('no_requests_yet'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="provider-card-footer">
                        <a href="requests.php" class="provider-btn provider-btn-outline">
                            <?php echo __('view_all_requests'); ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="provider-col-lg-4">
            <div class="provider-card">
                <div class="provider-card-header">
                    <h2><?php echo __('quick_actions'); ?></h2>
                </div>
                <div class="provider-card-body">
                    <div class="provider-quick-actions">
                        <a href="add_service.php" class="provider-quick-action">
                            <i class="fas fa-plus-circle"></i>
                            <span><?php echo __('add_service'); ?></span>
                        </a>
                        <a href="profile.php" class="provider-quick-action">
                            <i class="fas fa-user-edit"></i>
                            <span><?php echo __('edit_profile'); ?></span>
                        </a>
                        <a href="requests.php?status=pending" class="provider-quick-action">
                            <i class="fas fa-clock"></i>
                            <span><?php echo __('pending_requests'); ?></span>
                        </a>
                        <a href="reviews.php" class="provider-quick-action">
                            <i class="fas fa-star"></i>
                            <span><?php echo __('view_reviews'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="provider-card mt-4">
                <div class="provider-card-header">
                    <h2><?php echo __('tips'); ?></h2>
                </div>
                <div class="provider-card-body">
                    <div class="provider-tips">
                        <div class="provider-tip">
                            <i class="fas fa-lightbulb"></i>
                            <p><?php echo __('tip_complete_profile'); ?></p>
                        </div>
                        <div class="provider-tip">
                            <i class="fas fa-lightbulb"></i>
                            <p><?php echo __('tip_respond_quickly'); ?></p>
                        </div>
                        <div class="provider-tip">
                            <i class="fas fa-lightbulb"></i>
                            <p><?php echo __('tip_quality_photos'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

