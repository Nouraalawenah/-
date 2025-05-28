<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// التحقق من وجود جدول reviews
$check_table = "SHOW TABLES LIKE 'service_reviews'";
$table_result = $conn->query($check_table);
$table_exists = $table_result->num_rows > 0;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['setup_reviews'])) {
    if ($table_exists) {
        $error = __('reviews_table_already_exists');
    } else {
        // إنشاء جدول التقييمات
        $create_reviews_table = "
        CREATE TABLE service_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            provider_id INT NOT NULL,
            service_id INT NOT NULL,
            request_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT NOT NULL,
            provider_reply TEXT NULL,
            reply_date DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
            UNIQUE KEY (user_id, request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($create_reviews_table) === TRUE) {
            // إضافة عمود completed_at إلى جدول service_requests إذا لم يكن موجودًا
            $check_completed_column = "SHOW COLUMNS FROM service_requests LIKE 'completed_at'";
            $completed_column_result = $conn->query($check_completed_column);
            
            if ($completed_column_result->num_rows == 0) {
                $add_completed_column = "ALTER TABLE service_requests ADD COLUMN completed_at TIMESTAMP NULL";
                $conn->query($add_completed_column);
            }
            
            $success = __('reviews_setup_success');
        } else {
            $error = __('reviews_setup_error') . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('setup_reviews'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1><?php echo __('setup_reviews'); ?></h1>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="setup-container">
            <?php if ($table_exists): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <p><?php echo __('reviews_table_exists'); ?></p>
                </div>
                <p><?php echo __('you_can_now_use_reviews'); ?></p>
                <div class="setup-actions">
                    <a href="index.php" class="btn"><?php echo __('back_to_home'); ?></a>
                    <?php if (isset($_SESSION['is_provider']) && $_SESSION['is_provider'] == 1): ?>
                        <a href="provider/reviews.php" class="btn btn-primary"><?php echo __('go_to_reviews'); ?></a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><?php echo __('reviews_table_not_found'); ?></p>
                </div>
                <p><?php echo __('create_reviews_table_description'); ?></p>
                <form method="post" class="setup-form">
                    <button type="submit" name="setup_reviews" class="btn btn-primary">
                        <i class="fas fa-cog"></i> <?php echo __('create_reviews_table'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- إضافة ملف JavaScript الخاص بالسمة -->
    <script src="js/theme.js"></script>
</body>
</html>
