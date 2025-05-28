<?php
// تضمين ملفات الإعداد
require_once 'config/config.php';
require_once 'config/db_connect.php';
require_once 'config/language.php';

// تعيين عنوان الصفحة
$page_title = __('services');
$page_specific_css = 'css/services.css';

// الحصول على معلمات البحث
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// الحصول على معرف الفئة من الاستعلام إذا كان موجودًا
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// الحصول على معرف مقدم الخدمة من الاستعلام إذا كان موجودًا
$provider_id = isset($_GET['provider']) ? (int)$_GET['provider'] : 0;

// الحصول على معلمات التصفية الأخرى
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 10000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'price_asc';

// الحصول على رقم الصفحة الحالية
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12; // عدد الخدمات في كل صفحة
$offset = ($page - 1) * $per_page;

// بناء استعلام SQL للخدمات
$sql_services = "SELECT s.*, c.name as category_name, c.icon as category_icon, 
                p.name as provider_name, p.id as provider_id, p.rating as provider_rating
                FROM services s
                JOIN categories c ON s.category_id = c.id
                JOIN service_providers p ON s.provider_id = p.id
                WHERE 1=1";

// إضافة شروط التصفية
$params = [];
$param_types = "";

// إضافة شرط البحث إذا كان موجودًا
if (!empty($search_term)) {
    $sql_services .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

if ($category_id > 0) {
    $sql_services .= " AND s.category_id = ?";
    $params[] = $category_id;
    $param_types .= "i";
}

if ($provider_id > 0) {
    $sql_services .= " AND s.provider_id = ?";
    $params[] = $provider_id;
    $param_types .= "i";
}

$sql_services .= " AND s.price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$param_types .= "dd";

// إضافة ترتيب النتائج
switch ($sort_by) {
    case 'price_desc':
        $sql_services .= " ORDER BY s.price DESC";
        break;
    case 'rating_desc':
        $sql_services .= " ORDER BY p.rating DESC";
        break;
    case 'newest':
        $sql_services .= " ORDER BY s.created_at DESC";
        break;
    case 'price_asc':
    default:
        $sql_services .= " ORDER BY s.price ASC";
        break;
}

// إضافة حدود الصفحة
$sql_services .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$param_types .= "ii";

// تنفيذ الاستعلام
$stmt = $conn->prepare($sql_services);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$services_result = $stmt->get_result();

// الحصول على إجمالي عدد الخدمات (للترقيم)
$sql_count = "SELECT COUNT(*) as total FROM services s 
              JOIN categories c ON s.category_id = c.id
              JOIN service_providers p ON s.provider_id = p.id
              WHERE 1=1";
$params_count = [];
$param_types_count = "";

// إضافة شرط البحث إذا كان موجودًا
if (!empty($search_term)) {
    $sql_count .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $search_param = "%{$search_term}%";
    $params_count[] = $search_param;
    $params_count[] = $search_param;
    $param_types_count .= "ss";
}

if ($category_id > 0) {
    $sql_count .= " AND s.category_id = ?";
    $params_count[] = $category_id;
    $param_types_count .= "i";
}

if ($provider_id > 0) {
    $sql_count .= " AND s.provider_id = ?";
    $params_count[] = $provider_id;
    $param_types_count .= "i";
}

$sql_count .= " AND s.price BETWEEN ? AND ?";
$params_count[] = $min_price;
$params_count[] = $max_price;
$param_types_count .= "dd";

$stmt_count = $conn->prepare($sql_count);
if (!empty($params_count)) {
    $stmt_count->bind_param($param_types_count, ...$params_count);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_services = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_services / $per_page);

// الحصول على جميع الفئات للتصفية
$categories_sql = "SELECT id, name, icon FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($category = $categories_result->fetch_assoc()) {
    $categories[] = $category;
}

// الحصول على مقدمي الخدمة للتصفية
$providers_sql = "SELECT id, name FROM service_providers ORDER BY name";
$providers_result = $conn->query($providers_sql);
$providers = [];
while ($provider = $providers_result->fetch_assoc()) {
    $providers[] = $provider;
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
        <!-- Sidebar with filters -->
        <aside class="services-sidebar">
            <form action="services.php" method="get" id="filter-form">
                <div class="search-section">
                    <h3><?php echo __('search'); ?></h3>
                    <div class="search-box">
                        <input type="text" name="search" placeholder="<?php echo __('search_services'); ?>" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3><?php echo __('categories'); ?></h3>
                    <ul class="category-filter">
                        <li>
                            <a href="services.php<?php echo !empty($search_term) ? '?search=' . urlencode($search_term) : ''; ?>" <?php echo $category_id == 0 ? 'class="active"' : ''; ?>>
                                <i class="fas fa-th-large"></i>
                                <?php echo __('all_categories'); ?>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="services.php?category=<?php echo $category['id']; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" <?php echo $category_id == $category['id'] ? 'class="active"' : ''; ?>>
                                <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                                <?php echo $category['name']; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="filter-section">
                    <h3><?php echo __('price_range'); ?></h3>
                    <div class="price-filter">
                        <div class="price-inputs">
                            <div class="price-input">
                                <label for="min-price"><?php echo __('min'); ?></label>
                                <input type="number" id="min-price" name="min_price" min="0" value="<?php echo $min_price; ?>">
                            </div>
                            <div class="price-input">
                                <label for="max-price"><?php echo __('max'); ?></label>
                                <input type="number" id="max-price" name="max_price" min="0" value="<?php echo $max_price; ?>">
                            </div>
                        </div>
                        <button type="submit" class="filter-btn"><?php echo __('apply'); ?></button>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3><?php echo __('sort_by'); ?></h3>
                    <div class="sort-options">
                        <select name="sort" id="sort-select" onchange="this.form.submit()">
                            <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>><?php echo __('price_low_to_high'); ?></option>
                            <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>><?php echo __('price_high_to_low'); ?></option>
                            <option value="rating_desc" <?php echo $sort_by == 'rating_desc' ? 'selected' : ''; ?>><?php echo __('highest_rated'); ?></option>
                            <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>><?php echo __('newest'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- حفظ معلمات التصفية الأخرى -->
                <?php if ($category_id > 0): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                
                <?php if ($provider_id > 0): ?>
                    <input type="hidden" name="provider" value="<?php echo $provider_id; ?>">
                <?php endif; ?>
            </form>
        </aside>
        
        <!-- Main content -->
        <div class="services-content">
            <?php if ($services_result->num_rows > 0): ?>
                <div class="services-count">
                    <?php echo sprintf(__('showing_services'), $services_result->num_rows, $total_services); ?>
                </div>
                
                <div class="services-list">
                    <?php 
                    $counter = 0;
                    while ($service = $services_result->fetch_assoc()): 
                        $counter++;
                    ?>
                    <div class="service-card" style="--animation-order: <?php echo $counter; ?>">
                        <div class="service-header">
                            <div class="service-category">
                                <a href="services.php?category=<?php echo $service['category_id']; ?>">
                                    <i class="fas fa-<?php echo $service['category_icon']; ?>"></i>
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
                                <a href="service.php?id=<?php echo $service['id']; ?>">
                                    <?php echo $service['name']; ?>
                                </a>
                            </h3>
                            
                            <div class="service-provider">
                                <i class="fas fa-user"></i>
                                <?php echo __('provided_by'); ?>
                                <a href="provider.php?id=<?php echo $service['provider_id']; ?>">
                                    <?php echo $service['provider_name']; ?>
                                </a>
                                
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
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-description">
                                <?php 
                                // عرض وصف مختصر
                                $description = $service['description'];
                                echo strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description;
                                ?>
                            </div>
                        </div>
                        
                        <div class="service-actions">
                            <a href="service.php?id=<?php echo $service['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> <?php echo __('view_details'); ?>
                            </a>
                            
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="service_request.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> <?php echo __('request_service'); ?>
                            </a>
                            <?php else: ?>
                            <a href="login.php?redirect=services.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> <?php echo __('login_to_request'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="services.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-arrow">
                        <i class="fas fa-chevron-left"></i> <?php echo __('previous'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="services.php?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo '<span class="current-page">' . $i . '</span>';
                        } else {
                            echo '<a href="services.php?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a>';
                        }
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                        echo '<a href="services.php?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="services.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-arrow">
                        <?php echo __('next'); ?> <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3><?php echo __('no_services_found'); ?></h3>
                    <p><?php echo __('no_services_message'); ?></p>
                    <a href="services.php" class="btn btn-primary"><?php echo __('clear_filters'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحريك بطاقات الخدمات عند التمرير
    const serviceCards = document.querySelectorAll('.service-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    serviceCards.forEach(card => {
        observer.observe(card);
    });
    
    // تحسين تجربة المستخدم في نموذج التصفية
    const priceInputs = document.querySelectorAll('.price-input input');
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            // التحقق من أن الحد الأدنى أقل من الحد الأقصى
            const minPrice = parseFloat(document.getElementById('min-price').value) || 0;
            const maxPrice = parseFloat(document.getElementById('max-price').value) || 10000;
            
            if (minPrice > maxPrice) {
                if (this.id === 'min-price') {
                    document.getElementById('max-price').value = minPrice;
                } else {
                    document.getElementById('min-price').value = maxPrice;
                }
            }
        });
    });
});
</script>

