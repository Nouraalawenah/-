<?php
session_start();
require_once 'config/db_connect.php';

if (!isset($_GET['category']) || !is_numeric($_GET['category'])) {
    header("Location: index.php");
    exit;
}

$category_id = $_GET['category'];

// استعلام لجلب معلومات الفئة
$cat_sql = "SELECT * FROM categories WHERE id = ?";
$cat_stmt = $conn->prepare($cat_sql);
$cat_stmt->bind_param("i", $category_id);
$cat_stmt->execute();
$category = $cat_stmt->get_result()->fetch_assoc();

if (!$category) {
    header("Location: index.php");
    exit;
}

// استعلام لجلب مقدمي الخدمة في هذه الفئة
$providers_sql = "SELECT * FROM service_providers WHERE category_id = ?";
$providers_stmt = $conn->prepare($providers_sql);
$providers_stmt->bind_param("i", $category_id);
$providers_stmt->execute();
$result = $providers_stmt->get_result();
$providers = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقدمي خدمة <?php echo $category['name']; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>مقدمي خدمة <?php echo $category['name']; ?></h2>
        
        <?php if (empty($providers)): ?>
            <p class="no-results">لا يوجد مقدمي خدمة متاحين حاليًا في هذه الفئة.</p>
        <?php else: ?>
            <div class="providers-grid">
                <?php foreach ($providers as $provider): ?>
                <div class="provider-card">
                    <?php if (!empty($provider['image'])): ?>
                    <div class="provider-image">
                        <img src="images/providers/<?php echo $provider['image']; ?>" alt="<?php echo $provider['name']; ?>">
                    </div>
                    <?php else: ?>
                    <div class="provider-image">
                        <img src="images/providers/anonymous.jpg" alt="<?php echo $provider['name']; ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="provider-content">
                        <div class="provider-header">
                            <div class="provider-title">
                                <h3><?php echo $provider['name']; ?></h3>
                                <div class="provider-category">
                                    <i class="fas fa-tag"></i> <?php echo $category['name']; ?>
                                </div>
                            </div>
                            <?php if (isset($provider['rating'])): ?>
                            <div class="provider-rating">
                                <?php
                                $rating = $provider['rating'];
                                echo '<div class="rating-stars">';
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= floor($rating)) {
                                        // Full star
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == ceil($rating) && $rating != floor($rating)) {
                                        // Half star
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        // Empty star
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                echo '<span class="rating-value">' . number_format($rating, 1) . '</span>';
                                echo '</div>';
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="provider-description">
                            <p><?php echo mb_substr($provider['description'], 0, 100) . (mb_strlen($provider['description']) > 100 ? '...' : ''); ?></p>
                        </div>
                        
                        <div class="provider-actions">
                            <a href="provider.php?id=<?php echo $provider['id']; ?>" class="btn"><?php echo __('view_details'); ?></a>
                            <?php if (!empty($provider['phone'])): ?>
                            <a href="tel:<?php echo $provider['phone']; ?>" class="btn btn-outline"><i class="fas fa-phone"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <a href="index.php" class="back-link">العودة للصفحة الرئيسية</a>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
