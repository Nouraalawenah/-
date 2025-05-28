<?php
// تضمين ملفات الإعداد
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db_connect.php';
require_once 'config/language.php';

// الحصول على معرف الفئة من الرابط
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($category_id <= 0) {
    header("Location: index.php");
    exit;
}

// استعلام لجلب معلومات الفئة
$category_sql = "SELECT id, name, description, icon, image FROM categories WHERE id = ?";
$category_stmt = $conn->prepare($category_sql);
$category_stmt->bind_param("i", $category_id);
$category_stmt->execute();
$category_result = $category_stmt->get_result();

if ($category_result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$category = $category_result->fetch_assoc();

// استعلام لجلب مقدمي الخدمة في هذه الفئة
$providers_sql = "SELECT id, name, description, image, address, rating FROM service_providers WHERE category_id = ? ORDER BY rating DESC";
$providers_stmt = $conn->prepare($providers_sql);
$providers_stmt->bind_param("i", $category_id);
$providers_stmt->execute();
$providers_result = $providers_stmt->get_result();
$providers = [];

if ($providers_result->num_rows > 0) {
    while ($row = $providers_result->fetch_assoc()) {
        $providers[] = $row;
    }
}

// استعلام لجلب الخدمات في هذه الفئة
$services_sql = "SELECT s.id, s.name, s.description, s.price, s.provider_id, 
                p.name as provider_name 
                FROM services s 
                JOIN service_providers p ON s.provider_id = p.id 
                WHERE p.category_id = ? 
                ORDER BY s.price ASC";
$services_stmt = $conn->prepare($services_sql);
$services_stmt->bind_param("i", $category_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();
$services = [];

if ($services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// تعيين عنوان الصفحة
$page_title = $category['name'];

// تضمين ملف الرأس
include 'includes/header.php';
?>

<div class="container">
    <?php
    include 'includes/breadcrumb.php';
    
    $breadcrumbs = [
        ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
        ['title' => $category['name'], 'active' => true]
    ];
    
    display_breadcrumbs($breadcrumbs);
    ?>
    
    <div class="service-header">
        <?php if (!empty($category['image'])): ?>
        <div class="service-image">
            <img src="images/<?php echo $category['image']; ?>" alt="<?php echo $category['name']; ?>">
        </div>
        <?php endif; ?>
        
        <div class="service-info">
            <div class="service-title">
                <?php if (!empty($category['icon'])): ?>
                <div class="service-icon">
                    <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                </div>
                <?php endif; ?>
                <h1><?php echo $category['name']; ?></h1>
            </div>
            <p><?php echo $category['description']; ?></p>
        </div>
    </div>
    
    <h2><?php echo __('service_providers'); ?></h2>
    
    <?php if (count($providers) > 0): ?>
        <div class="providers-grid">
            <?php foreach ($providers as $provider): ?>
                <div class="provider-card">
                    <?php if (!empty($provider['image'])): ?>
                    <div class="provider-image">
                        <img src="images/providers/<?php echo $provider['image']; ?>" alt="<?php echo $provider['name']; ?>">
                    </div>
                    <?php else: ?>
                    <div class="provider-image">
                        <img src="images/providers/default.jpg" alt="<?php echo $provider['name']; ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="provider-content">
                        <h3><?php echo $provider['name']; ?></h3>
                        <div class="provider-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $provider['rating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i - 0.5 <= $provider['rating']): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span>(<?php echo number_format($provider['rating'], 1); ?>)</span>
                        </div>
                        <p><?php echo mb_substr($provider['description'], 0, 100) . (mb_strlen($provider['description']) > 100 ? '...' : ''); ?></p>
                        <a href="provider.php?id=<?php echo $provider['id']; ?>" class="btn"><?php echo __('view_details'); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-results"><?php echo __('no_providers'); ?></p>
    <?php endif; ?>
    
    <!-- إضافة قسم الخدمات -->
    <h2><?php echo __('services'); ?></h2>
    
    <?php if (count($services) > 0): ?>
        <div class="services-list">
            <?php foreach ($services as $service): ?>
                <div class="service-item">
                    <div class="service-item-header">
                        <h3><?php echo $service['name']; ?></h3>
                        <div class="service-price"><?php echo number_format($service['price'], 2); ?> <?php echo __('currency'); ?></div>
                    </div>
                    <div class="service-provider">
                        <i class="fas fa-user"></i> <?php echo __('provided_by'); ?>: 
                        <a href="provider.php?id=<?php echo $service['provider_id']; ?>"><?php echo $service['provider_name']; ?></a>
                    </div>
                    <div class="service-description">
                        <p><?php echo $service['description']; ?></p>
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="service-actions">
                        <a href="service_request.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> <?php echo __('request_service'); ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="service-actions">
                        <a href="login.php" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> <?php echo __('login_to_request'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-results"><?php echo __('no_services'); ?></p>
    <?php endif; ?>
</div>
    
<?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
</body>
</html>




