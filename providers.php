<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// تحديد الصفحة الحالية
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, $page);
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// تحديد الفئة المختارة (إن وجدت)
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;

// استعلام لجلب إجمالي عدد مقدمي الخدمة
$count_sql = "SELECT COUNT(*) as total FROM service_providers";
$count_params = [];
$count_types = "";

// إضافة فلتر الفئة إذا تم اختيار فئة
if ($selected_category > 0) {
    $count_sql .= " WHERE category_id = ?";
    $count_params[] = $selected_category;
    $count_types .= "i";
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// استعلام لجلب مقدمي الخدمة مع معلومات الفئة
$providers_sql = "SELECT sp.id, sp.name, sp.description, sp.image, sp.address, sp.rating, 
                 c.name AS category_name, c.id AS category_id
                 FROM service_providers sp
                 JOIN categories c ON sp.category_id = c.id";

$params = [];
$types = "";

// إضافة فلتر الفئة إذا تم اختيار فئة
if ($selected_category > 0) {
    $providers_sql .= " WHERE sp.category_id = ?";
    $params[] = $selected_category;
    $types .= "i";
}

$providers_sql .= " ORDER BY sp.rating DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

$providers_stmt = $conn->prepare($providers_sql);
$providers_stmt->bind_param($types, ...$params);
$providers_stmt->execute();
$providers_result = $providers_stmt->get_result();
$providers = [];

if ($providers_result->num_rows > 0) {
    while ($row = $providers_result->fetch_assoc()) {
        $providers[] = $row;
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

// تحديد عنوان الصفحة
$page_title = __('service_providers');
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/providers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php
        // عرض شريط التنقل الفرعي
        include 'includes/breadcrumb.php';
        
        $breadcrumbs = [
            ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
            ['title' => __('service_providers'), 'active' => true]
        ];
        
        display_breadcrumbs($breadcrumbs);
        ?>
        
        <div class="page-header">
            <h1><?php echo __('service_providers'); ?></h1>
            <p class="lead"><?php echo __('providers_description'); ?></p>
        </div>
        
        <div class="providers-page-layout">
            <!-- Sidebar for filters -->
            <div class="providers-sidebar">
                <div class="filter-section">
                    <h3><?php echo __('filter_by_category'); ?></h3>
                    <ul class="category-filter">
                        <li>
                            <a href="providers.php" class="<?php echo $selected_category == 0 ? 'active' : ''; ?>">
                                <i class="fas fa-th-large"></i> <?php echo __('all_categories'); ?>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="providers.php?category=<?php echo $category['id']; ?>" class="<?php echo $selected_category == $category['id'] ? 'active' : ''; ?>">
                                <i class="fas fa-tag"></i> <?php echo $category['name']; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="search-section">
                    <h3><?php echo __('search'); ?></h3>
                    <form action="search.php" method="GET" class="sidebar-search-form">
                        <div class="form-group">
                            <input type="text" name="q" placeholder="<?php echo __('search_placeholder'); ?>" required>
                            <button type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    <div class="advanced-search-link">
                        <a href="advanced_search.php">
                            <i class="fas fa-sliders-h"></i> <?php echo __('advanced_search'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main content area -->
            <div class="providers-main">
                <?php if ($selected_category > 0): 
                    // Find the selected category name
                    $category_name = "";
                    foreach ($categories as $cat) {
                        if ($cat['id'] == $selected_category) {
                            $category_name = $cat['name'];
                            break;
                        }
                    }
                ?>
                <div class="category-header">
                    <h2><?php echo sprintf(__('providers_in_category'), $category_name); ?></h2>
                </div>
                <?php endif; ?>
                
                <div class="providers-count">
                    <?php echo sprintf(__('found_providers'), $total_items); ?>
                </div>
                
                <?php if (empty($providers)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3><?php echo __('no_providers_found'); ?></h3>
                    <p><?php echo __('no_providers_message'); ?></p>
                </div>
                <?php else: ?>
                <div class="providers-grid">
                    <?php foreach ($providers as $index => $provider): ?>
                    <div class="provider-card" style="--animation-order: <?php echo $index; ?>">
                        <div class="provider-image">
                            <?php if (!empty($provider['image'])): ?>
                            <img src="images/providers/<?php echo $provider['image']; ?>" alt="<?php echo $provider['name']; ?>">
                            <?php else: ?>
                            <img src="images/providers/default.jpg" alt="<?php echo $provider['name']; ?>">
                            <?php endif; ?>
                            <div class="provider-category-badge">
                                <a href="service.php?id=<?php echo $provider['category_id']; ?>">
                                    <?php echo $provider['category_name']; ?>
                                </a>
                            </div>
                        </div>
                        <div class="provider-content">
                            <div class="provider-header">
                                <div class="provider-title">
                                    <h3>
                                        <a href="provider.php?id=<?php echo $provider['id']; ?>">
                                            <?php echo $provider['name']; ?>
                                        </a>
                                    </h3>
                                    <div class="provider-category">
                                        <i class="fas fa-tag"></i> <?php echo $provider['category_name']; ?>
                                    </div>
                                </div>
                                <div class="provider-rating">
                                    <div class="rating-stars">
                                        <?php
                                        $rating = round($provider['rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                        <span class="rating-value"><?php echo number_format($provider['rating'], 1); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="provider-description">
                                <?php 
                                // Limit description to 100 characters
                                echo strlen($provider['description']) > 100 ? 
                                    substr($provider['description'], 0, 100) . '...' : 
                                    $provider['description']; 
                                ?>
                            </div>
                            <div class="provider-address" title="<?php echo $provider['address']; ?>">
                                <i class="fas fa-map-marker-alt"></i> <?php echo $provider['address']; ?>
                            </div>
                            <div class="provider-actions">
                                <a href="provider.php?id=<?php echo $provider['id']; ?>" class="btn">
                                    <?php echo __('view_profile'); ?>
                                </a>
                                <a href="request_service.php?provider=<?php echo $provider['id']; ?>" class="btn btn-outline">
                                    <?php echo __('request_service'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="providers.php?page=<?php echo ($page - 1); ?><?php echo $selected_category > 0 ? '&category=' . $selected_category : ''; ?>" class="pagination-arrow">
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
                        echo '<a href="providers.php?page=1' . ($selected_category > 0 ? '&category=' . $selected_category : '') . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                    }
                    
                    // Show page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo '<span class="current-page">' . $i . '</span>';
                        } else {
                            echo '<a href="providers.php?page=' . $i . ($selected_category > 0 ? '&category=' . $selected_category : '') . '">' . $i . '</a>';
                        }
                    }
                    
                    // Always show last page
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                        echo '<a href="providers.php?page=' . $total_pages . ($selected_category > 0 ? '&category=' . $selected_category : '') . '">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="providers.php?page=<?php echo ($page + 1); ?><?php echo $selected_category > 0 ? '&category=' . $selected_category : ''; ?>" class="pagination-arrow">
                        <i class="fas fa-chevron-<?php echo __('dir') === 'rtl' ? 'left' : 'right'; ?>"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate provider cards on scroll
        const providerCards = document.querySelectorAll('.provider-card');
        
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
        
        providerCards.forEach(card => {
            observer.observe(card);
        });
    });
    </script>
</body>
</html>

