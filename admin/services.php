<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// تحديد الصفحة الحالية
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, $page);
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// تحديد الفئة المختارة (إن وجدت)
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
// تحديد مقدم الخدمة المختار (إن وجد)
$selected_provider = isset($_GET['provider']) ? intval($_GET['provider']) : 0;

// استعلام لجلب إجمالي عدد الخدمات
$count_sql = "SELECT COUNT(*) as total FROM services s";
$count_params = [];
$count_types = "";

// إضافة الشروط للاستعلام
$where_conditions = [];

if ($selected_category > 0) {
    $where_conditions[] = "s.category_id = ?";
    $count_params[] = $selected_category;
    $count_types .= "i";
}

if ($selected_provider > 0) {
    $where_conditions[] = "s.provider_id = ?";
    $count_params[] = $selected_provider;
    $count_types .= "i";
}

// إضافة شروط البحث إلى الاستعلام
if (!empty($where_conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// استعلام لجلب الخدمات مع معلومات الفئة ومقدم الخدمة
$services_sql = "SELECT s.id, s.name, s.description, s.price, 
                 c.id AS category_id, c.name AS category_name, c.icon AS category_icon,
                 p.id AS provider_id, p.name AS provider_name, 
                 IFNULL(p.rating, 0) AS provider_rating
                 FROM services s
                 JOIN categories c ON s.category_id = c.id
                 JOIN service_providers p ON s.provider_id = p.id";

$params = [];
$types = "";

// إضافة الشروط للاستعلام
if (!empty($where_conditions)) {
    $services_sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// ترتيب النتائج
$services_sql .= " ORDER BY s.price ASC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

$services_stmt = $conn->prepare($services_sql);
if (!empty($params)) {
    $services_stmt->bind_param($types, ...$params);
}
$services_stmt->execute();
$services_result = $services_stmt->get_result();
$services = [];

if ($services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// استعلام لجلب جميع الفئات للتصفية
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// استعلام لجلب مقدمي الخدمة للتصفية
$providers_sql = "SELECT id, name FROM service_providers ORDER BY name";
$providers_result = $conn->query($providers_sql);
$providers = [];

if ($providers_result->num_rows > 0) {
    while ($row = $providers_result->fetch_assoc()) {
        $providers[] = $row;
    }
}

// تحديد عنوان الصفحة
$page_title = __('services');

// الحصول على معلومات الفئة أو مقدم الخدمة المحدد (إن وجد)
$category_name = "";
$provider_name = "";

if ($selected_category > 0) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $selected_category) {
            $category_name = $cat['name'];
            break;
        }
    }
}

if ($selected_provider > 0) {
    foreach ($providers as $prov) {
        if ($prov['id'] == $selected_provider) {
            $provider_name = $prov['name'];
            break;
        }
    }
}

// تضمين ملف الرأس
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?php echo __('services'); ?></h1>
        <p class="lead"><?php echo __('services_description'); ?></p>
    </div>
    
    <div class="services-page-layout">
        <!-- Sidebar for filters -->
        <div class="services-sidebar">
            <div class="filter-section">
                <h3><?php echo __('filter_by_category'); ?></h3>
                <ul class="category-filter">
                    <li>
                        <a href="services.php<?php echo $selected_provider > 0 ? '?provider=' . $selected_provider : ''; ?>" class="<?php echo $selected_category == 0 ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i> <?php echo __('all_categories'); ?>
                        </a>
                    </li>
                    <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="services.php?category=<?php echo $category['id']; ?><?php echo $selected_provider > 0 ? '&provider=' . $selected_provider : ''; ?>" class="<?php echo $selected_category == $category['id'] ? 'active' : ''; ?>">
                            <i class="fas fa-tag"></i> <?php echo $category['name']; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="filter-section">
                <h3><?php echo __('filter_by_provider'); ?></h3>
                <ul class="provider-filter">
                    <li>
                        <a href="services.php<?php echo $selected_category > 0 ? '?category=' . $selected_category : ''; ?>" class="<?php echo $selected_provider == 0 ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> <?php echo __('all_providers'); ?>
                        </a>
                    </li>
                    <?php foreach ($providers as $provider): ?>
                    <li>
                        <a href="services.php?provider=<?php echo $provider['id']; ?><?php echo $selected_category > 0 ? '&category=' . $selected_category : ''; ?>" class="<?php echo $selected_provider == $provider['id'] ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i> <?php echo $provider['name']; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="search-section">
                <h3><?php echo __('search'); ?></h3>
                <form action="search.php" method="GET" class="sidebar-search-form">
                    <input type="hidden" name="type" value="services">
                    <div class="form-group">
                        <input type="text" name="q" placeholder="<?php echo __('search_services_placeholder'); ?>" required>
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Main content area -->
        <div class="services-main">
            <?php if ($selected_category > 0 || $selected_provider > 0): ?>
            <div class="filter-header">
                <?php if ($selected_category > 0 && $selected_provider > 0): ?>
                    <h2><?php echo sprintf(__('services_in_category_by_provider'), $category_name, $provider_name); ?></h2>
                <?php elseif ($selected_category > 0): ?>
                    <h2><?php echo sprintf(__('services_in_category'), $category_name); ?></h2>
                <?php elseif ($selected_provider > 0): ?>
                    <h2><?php echo sprintf(__('services_by_provider'), $provider_name); ?></h2>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="services-count">
                <?php echo sprintf(__('found_services'), $total_items); ?>
            </div>
            
            <?php if (empty($services)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3><?php echo __('no_services_found'); ?></h3>
                <p><?php echo __('no_services_message'); ?></p>
            </div>
            <?php else: ?>
            <div class="services-list">
                <?php foreach ($services as $index => $service): ?>
                <div class="service-card" style="--animation-order: <?php echo $index; ?>">
                    <div class="service-header">
                        <div class="service-category">
                            <a href="services.php?category=<?php echo $service['category_id']; ?>">
                                <i class="fas fa-<?php echo $service['category_icon'] ?: 'tag'; ?>"></i>
                                <?php echo $service['category_name']; ?>
                            </a>
                        </div>
                        <div class="service-price">
                            <span class="price-value"><?php echo number_format($service['price'], 2); ?></span>
                            <span class="price-currency"><?php echo __('currency'); ?></span>
                        </div>
                    </div>
                    
                    <div class="service-content">
                        <h3 class="service-title">
                            <a href="service_details.php?id=<?php echo $service['id']; ?>">
                                <?php echo $service['name']; ?>
                            </a>
                        </h3>
                        
                        <div class="service-provider">
                            <i class="fas fa-user"></i>
                            <?php echo __('provided_by'); ?>:
                            <a href="services.php?provider=<?php echo $service['provider_id']; ?>">
                                <?php echo $service['provider_name']; ?>
                            </a>
                            <div class="provider-rating">
                                <?php
                                $rating = round($service['provider_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="service-description">
                            <?php 
                            // Limit description to 150 characters
                            echo strlen($service['description']) > 150 ? 
                                substr($service['description'], 0, 150) . '...' : 
                                $service['description']; 
                            ?>
                        </div>
                    </div>
                    
                    <div class="service-actions">
                        <a href="service_details.php?id=<?php echo $service['id']; ?>" class="btn btn-outline">
                            <i class="fas fa-info-circle"></i> <?php echo __('view_details'); ?>
                        </a>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="service_request.php?service_id=<?php echo $service['id']; ?>" class="btn">
                            <i class="fas fa-shopping-cart"></i> <?php echo __('request_service'); ?>
                        </a>
                        <?php else: ?>
                        <a href="login.php?redirect=service_request.php?service_id=<?php echo $service['id']; ?>" class="btn">
                            <i class="fas fa-sign-in-alt"></i> <?php echo __('login_to_request'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="services.php?page=<?php echo ($page - 1); ?><?php echo $selected_category > 0 ? '&category=' . $selected_category : ''; ?><?php echo $selected_provider > 0 ? '&provider=' . $selected_provider : ''; ?>" class="pagination-arrow">
                    <i class="fas fa-chevron-<?php echo __('dir') === 'rtl' ? 'right' : 'left'; ?>"></i>
                </a>
                <?php endif; ?>
                
                <?php
                // Calculate range of page numbers to show
                $range = 2; // Show 2 pages before and after current page
                $start_page = max(1, $page - $range);
                $end_page = min($total_pages, $page + $range);
                
                // Always show first page
                if ($start_page > 1) {
                    echo '<a href="services.php?page=1' . 
                        ($selected_category > 0 ? '&category=' . $selected_category : '') . 
                        ($selected_provider > 0 ? '&provider=' . $selected_provider : '') . 
                        '">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                }
                
                // Show page numbers
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                        echo '<span class="current-page">' . $i . '</span>';
                    } else {
                        echo '<a href="services.php?page=' . $i . 
                            ($selected_category > 0 ? '&category=' . $selected_category : '') . 
                            ($selected_provider > 0 ? '&provider=' . $selected_provider : '') . 
                            '">' . $i . '</a>';
                    }
                }
                
                // Always show last page
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    echo '<a href="services.php?page=' . $total_pages . 
                        ($selected_category > 0 ? '&category=' . $selected_category : '') . 
                        ($selected_provider > 0 ? '&provider=' . $selected_provider : '') . 
                        '">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="services.php?page=<?php echo ($page + 1); ?><?php echo $selected_category > 0 ? '&category=' . $selected_category : ''; ?><?php echo $selected_provider > 0 ? '&provider=' . $selected_provider : ''; ?>" class="pagination-arrow">
                    <i class="fas fa-chevron-<?php echo __('dir') === 'rtl' ? 'left' : 'right'; ?>"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Services Page Layout */
.services-page-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
    margin: 30px 0;
}

@media (max-width: 992px) {
    .services-page-layout {
        grid-template-columns: 1fr;
    }
}

/* Services Sidebar */
.services-sidebar {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
    position: sticky;
    top: 100px;
    height: fit-content;
}

.filter-section, .search-section {
    margin-bottom: 25px;
}

.filter-section h3, .search-section h3 {
    font-size: 1.2rem;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #333;
}

.category-filter, .provider-filter {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 200px;
    overflow-y: auto;
}

.category-filter li, .provider-filter li {
    margin-bottom: 8px;
}

.category-filter a, .provider-filter a {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 4px;
    color: #555;
    text-decoration: none;
    transition: all 0.2s ease;
}

.category-filter a:hover, .provider-filter a:hover {
    background-color: #f5f5f5;
    color: #007bff;
}

.category-filter a.active, .provider-filter a.active {
    background-color: #007bff;
    color: white;
}

.category-filter a i, .provider-filter a i {
    margin-right: 8px;
    width: 16px;
    text-align: center;
}

[dir="rtl"] .category-filter a i, [dir="rtl"] .provider-filter a i {
    margin-right: 0;
    margin-left: 8px;
}

.sidebar-search-form .form-group {
    position: relative;
}

.sidebar-search-form input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 1px solid #ddd;
    border-radius: 25px;
    font-size: 0.9rem;
}

[dir="rtl"] .sidebar-search-form input {
    padding: 10px 15px 10px 40px;
}

.sidebar-search-form button {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    padding: 5px;
}

[dir="rtl"] .sidebar-search-form button {
    right: auto;
    left: 5px;
}

/* Services Main Content */
.services-main {
    min-height: 500px;
}

.filter-header {
    margin-bottom: 20px;
}

.filter-header h2 {
    font-size: 1.5rem;
    color: #333;
    margin: 0;
}

.services-count {
    margin-bottom: 20px;
    color: #6c757d
}
</style>
</body>
</html>


