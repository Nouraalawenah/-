<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// التحقق من وجود أعمدة إضافية
$check_columns_sql = "SHOW COLUMNS FROM service_providers";
$columns_result = $conn->query($check_columns_sql);
$has_email_column = false;
$has_address_column = false;
$has_rating_column = false;

while ($column = $columns_result->fetch_assoc()) {
    if ($column['Field'] == 'email') {
        $has_email_column = true;
    } elseif ($column['Field'] == 'address') {
        $has_address_column = true;
    } elseif ($column['Field'] == 'rating') {
        $has_rating_column = true;
    }
}

// إذا لم يكن عمود التقييم موجودًا، قم بإضافته
if (!$has_rating_column) {
    $add_rating_column = "ALTER TABLE service_providers ADD COLUMN rating DECIMAL(3,1) DEFAULT 0.0";
    if ($conn->query($add_rating_column) === TRUE) {
        // تحديث التقييمات بقيم افتراضية للاختبار
        $update_ratings = "UPDATE service_providers SET rating = ROUND(RAND() * 3 + 2, 1)";
        $conn->query($update_ratings);
        $has_rating_column = true;
    }
}

// الحصول على قائمة الفئات
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Helper function to display star rating
function displayStarRating($rating) {
    $rating = round($rating * 2) / 2; // Round to nearest 0.5
    $output = '<div class="rating-stars">';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            // Full star
            $output .= '<i class="fas fa-star"></i>';
        } elseif ($i == ceil($rating) && $rating != floor($rating)) {
            // Half star
            $output .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            // Empty star
            $output .= '<i class="far fa-star"></i>';
        }
    }
    
    $output .= '<span class="rating-value">' . number_format($rating, 1) . '</span>';
    $output .= '</div>';
    
    return $output;
}

$providers = [];
$search_performed = false;
$search_criteria = [];

// Add sort parameter to the search
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'rating';

// إذا تم إرسال نموذج البحث
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['submit'])) {
    $search_performed = true;
    $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $rating = isset($_GET['rating']) ? $_GET['rating'] : '';
    
    // تخزين معايير البحث للعرض
    if (!empty($term)) {
        $search_criteria[] = __('search_term') . ': ' . htmlspecialchars($term);
    }
    
    if (!empty($category_id)) {
        // الحصول على اسم الفئة
        foreach ($categories as $cat) {
            if ($cat['id'] == $category_id) {
                $search_criteria[] = __('category') . ': ' . $cat['name'];
                break;
            }
        }
    }
    
    if (!empty($rating)) {
        $search_criteria[] = __('minimum_rating') . ': ' . $rating . '+ ' . __('stars');
    }
    
    // بناء استعلام البحث
    $query = "SELECT p.id, p.name, p.description, 
             p.phone, p.image, p.rating, c.name AS category_name";
    
    // إضافة الأعمدة الاختيارية إذا كانت موجودة
    if ($has_email_column) {
        $query .= ", p.email";
    }
    
    if ($has_address_column) {
        $query .= ", p.address";
    }
    
    $query .= " FROM service_providers p 
             JOIN categories c ON p.category_id = c.id 
             WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // إضافة شرط البحث بالنص
    if (!empty($term)) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
        $search_term = "%$term%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sss";
    }
    
    // إضافة شرط الفئة
    if (!empty($category_id)) {
        $query .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    // إضافة شرط التقييم
    if (!empty($rating)) {
        $query .= " AND p.rating >= ?";
        $params[] = $rating;
        $types .= "d";
    }
    
    // ترتيب النتائج
    if ($sort === 'rating') {
        $query .= " ORDER BY p.rating DESC, p.name";
    } elseif ($sort === 'name') {
        $query .= " ORDER BY p.name";
    } else {
        $query .= " ORDER BY p.rating DESC, p.name";
    }
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $providers[] = $row;
        }
    }
}

// Set page title
$page_title = __('advanced_search');

// Include header
include 'includes/header.php';
?>

<!-- Add specific styles to fix the search sidebar -->
<style>
    /* Search page layout */
    .search-page-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 30px;
        margin: 30px 0;
    }
    
    @media (max-width: 992px) {
        .search-page-layout {
            grid-template-columns: 1fr;
        }
    }
    
    /* Search sidebar styles - Fixed */
    .search-sidebar {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        position: sticky;
        top: 100px;
        height: fit-content;
        margin-bottom: 30px;
    }
    
    .search-container {
        width: 100%;
    }
    
    .search-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }
    
    .form-group input[type="text"] {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #f9f9f9;
    }
    
    .custom-dropdown {
        position: relative;
    }
    
    .custom-dropdown select {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #f9f9f9;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="6"><path d="M0 0l6 6 6-6z" fill="%23666"/></svg>');
        background-repeat: no-repeat;
        background-position: right 15px center;
        cursor: pointer;
    }
    
    [dir="rtl"] .custom-dropdown select {
        background-position: left 15px center;
    }
    
    .search-form button {
        padding: 12px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .search-form button:hover {
        background-color: #0056b3;
        transform: translateY(-2px);
    }
    
    /* Search intro */
    .search-intro {
        margin-top: 30px;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #007bff;
    }
    
    [dir="rtl"] .search-intro {
        border-left: none;
        border-right: 4px solid #007bff;
    }
    
    .search-intro h3 {
        margin-top: 0;
        color: #007bff;
    }
    
    .search-intro ul {
        padding-left: 20px;
        margin-bottom: 0;
    }
    
    [dir="rtl"] .search-intro ul {
        padding-left: 0;
        padding-right: 20px;
    }
    
    .search-intro li {
        margin-bottom: 8px;
    }
    
    /* Search results styles */
    .search-results-container {
        width: 100%;
    }
    
    .search-results {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 25px;
    }
    
    .search-criteria {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 20px 0;
    }
    
    .search-tag {
        background-color: #f0f7ff;
        color: #007bff;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .search-count {
        margin-bottom: 20px;
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .no-results {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }
    
    .no-results i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    /* Provider cards */
    .providers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .provider-card {
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: calc(var(--animation-order) * 0.1s);
        opacity: 0;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .provider-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    
    .provider-image {
        /* height: 180px; */
        overflow: hidden;
    }
    
    .provider-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .provider-card:hover .provider-image img {
        transform: scale(1.05);
    }
    
    .provider-content {
        padding: 20px;
    }
    
    .provider-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .provider-title {
        flex: 1;
    }
    
    .provider-title h3 {
        margin: 0 0 5px;
        font-size: 1.2rem;
    }
    
    .provider-category {
        font-size: 0.9rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .provider-rating {
        margin-left: 10px;
    }
    
    [dir="rtl"] .provider-rating {
        margin-left: 0;
        margin-right: 10px;
    }
    
    .rating-stars {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .rating-stars i {
        color: #ffc107;
    }
    
    .rating-value {
        font-weight: bold;
        margin-left: 5px;
    }
    
    [dir="rtl"] .rating-value {
        margin-left: 0;
        margin-right: 5px;
    }
    
    .provider-description {
        margin-bottom: 15px;
        color: #333;
        line-height: 1.5;
    }
    
    .provider-actions {
        display: flex;
        gap: 10px;
    }
    
    .provider-actions .btn {
        flex: 1;
        text-align: center;
        padding: 8px 15px;
        background-color: #007bff;
        color: white;
        border-radius: 4px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .provider-actions .btn:hover {
        background-color: #0056b3;
    }
    
    .provider-actions .btn-outline {
        background-color: transparent;
        border: 1px solid #007bff;
        color: #007bff;
    }
    
    .provider-actions .btn-outline:hover {
        background-color: #007bff;
        color: white;
    }
</style>

<div class="container">
    <?php
    include 'includes/breadcrumb.php';
    
    $breadcrumbs = [
        ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
        ['title' => __('advanced_search'), 'active' => true]
    ];
    
    display_breadcrumbs($breadcrumbs);
    ?>
    
    <h1><?php echo __('advanced_search'); ?></h1>
    
    <div class="search-page-layout">
        <!-- Search Form Sidebar -->
        <div class="search-sidebar">
            <div class="search-container" role="search">
                <form method="GET" action="" class="search-form">
                    <div class="form-group">
                        <label for="term" id="term-label"><i class="fas fa-search" aria-hidden="true"></i> <?php echo __('search_term'); ?></label>
                        <input type="text" id="term" name="term" value="<?php echo isset($_GET['term']) ? htmlspecialchars($_GET['term']) : ''; ?>" placeholder="<?php echo __('search_placeholder'); ?>" aria-labelledby="term-label">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id"><i class="fas fa-tag" aria-hidden="true"></i> <?php echo __('category'); ?></label>
                        <div class="custom-dropdown">
                            <select id="category_id" name="category_id">
                                <option value="" <?php echo (!isset($_GET['category_id']) || $_GET['category_id'] === '') ? 'selected' : ''; ?>><?php echo __('all_categories'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category_id']) && $_GET['category_id'] == $category['id']) ? 'selected' : ''; ?>><?php echo $category['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating"><i class="fas fa-star" aria-hidden="true"></i> <?php echo __('minimum_rating'); ?></label>
                        <div class="custom-dropdown">
                            <select id="rating" name="rating">
                                <option value="" <?php echo (!isset($_GET['rating']) || $_GET['rating'] === '') ? 'selected' : ''; ?>><?php echo __('any_rating'); ?></option>
                                <option value="5" <?php echo (isset($_GET['rating']) && $_GET['rating'] == 5) ? 'selected' : ''; ?>>5 <?php echo __('stars'); ?> ★★★★★</option>
                                <option value="4" <?php echo (isset($_GET['rating']) && $_GET['rating'] == 4) ? 'selected' : ''; ?>>4+ <?php echo __('stars'); ?> ★★★★☆</option>
                                <option value="3" <?php echo (isset($_GET['rating']) && $_GET['rating'] == 3) ? 'selected' : ''; ?>>3+ <?php echo __('stars'); ?> ★★★☆☆</option>
                                <option value="2" <?php echo (isset($_GET['rating']) && $_GET['rating'] == 2) ? 'selected' : ''; ?>>2+ <?php echo __('stars'); ?> ★★☆☆☆</option>
                                <option value="1" <?php echo (isset($_GET['rating']) && $_GET['rating'] == 1) ? 'selected' : ''; ?>>1+ <?php echo __('stars'); ?> ★☆☆☆☆</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort"><i class="fas fa-sort" aria-hidden="true"></i> <?php echo __('sort_by'); ?></label>
                        <div class="custom-dropdown">
                            <select id="sort" name="sort">
                                <option value="rating" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'rating') ? 'selected' : ''; ?>><?php echo __('highest_rated'); ?></option>
                                <option value="name" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name') ? 'selected' : ''; ?>><?php echo __('alphabetical'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="submit" value="1">
                            <i class="fas fa-search"></i> <?php echo __('search'); ?>
                        </button>
                    </div>
                </form>
                
                <?php if (!$search_performed): ?>
                <div class="search-intro">
                    <h3><?php echo __('advanced_search_intro_title'); ?></h3>
                    <p><?php echo __('advanced_search_intro_text'); ?></p>
                    <ul>
                        <li><?php echo __('search_by_name_desc'); ?></li>
                        <li><?php echo __('search_by_category_desc'); ?></li>
                        <li><?php echo __('search_by_rating_desc'); ?></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Search Results Container -->
        <div class="search-results-container">
            <?php if ($search_performed): ?>
                <div class="search-results" role="region" aria-label="<?php echo __('search_results'); ?>">
                    <h2><?php echo __('search_results'); ?></h2>
                    
                    <?php if (!empty($search_criteria)): ?>
                    <div class="search-criteria">
                        <?php foreach ($search_criteria as $criteria): ?>
                            <div class="search-tag">
                                <i class="fas fa-search"></i> <?php echo $criteria; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($providers)): ?>
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <p><?php echo __('no_results'); ?></p>
                            <p><?php echo __('try_different'); ?> <?php echo __('search_criteria'); ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="search-count">
                            <i class="fas fa-check-circle"></i> <?php echo sprintf(__('found_providers'), count($providers)); ?>
                        </div>
                        
                        <div class="providers-grid">
                            <?php foreach ($providers as $index => $provider): ?>
                                <div class="provider-card" style="--animation-order: <?php echo $index; ?>">
                                    <?php if (!empty($provider['image'])): ?>
                                    <div class="provider-image">
                                        <img src="images/providers/<?php echo $provider['image']; ?>" alt="<?php echo htmlspecialchars($provider['name']); ?>">
                                    </div>
                                    <?php else: ?>
                                    <div class="provider-image">
                                        <img src="images/providers/anonymous.jpg" alt="<?php echo htmlspecialchars($provider['name']); ?>">
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="provider-content">
                                        <div class="provider-header">
                                            <div class="provider-title">
                                                <h3><?php echo htmlspecialchars($provider['name']); ?></h3>
                                                <div class="provider-category">
                                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($provider['category_name']); ?>
                                                </div>
                                            </div>
                                            <div class="provider-rating">
                                                <?php echo displayStarRating($provider['rating']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="provider-description">
                                            <p><?php echo mb_substr(htmlspecialchars($provider['description']), 0, 100) . (mb_strlen($provider['description']) > 100 ? '...' : ''); ?></p>
                                        </div>
                                        
                                        <div class="provider-actions">
                                            <a href="provider.php?id=<?php echo $provider['id']; ?>&from=search&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="btn"><?php echo __('view_details'); ?></a>
                                            <?php if (!empty($provider['phone'])): ?>
                                            <a href="tel:<?php echo $provider['phone']; ?>" class="btn btn-outline"><i class="fas fa-phone"></i> <?php echo __('call'); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation for provider cards
    document.querySelectorAll('.provider-card').forEach(function(card, index) {
        setTimeout(function() {
            card.style.opacity = '1';
        }, index * 100);
    });
});
</script>





