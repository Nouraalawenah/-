<?php
// تضمين ملفات اللغة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/language.php';
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('error_page'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .error-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .error-icon {
            font-size: 5rem;
            color: var(--danger-color);
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 2rem;
            color: var(--danger-color);
            margin-bottom: 20px;
        }
        
        .error-message {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: var(--text-color);
        }
        
        .error-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="error-title"><?php echo __('error_occurred'); ?></h1>
            <p class="error-message"><?php echo __('error_message'); ?></p>
            <div class="error-actions">
                <a href="index.php" class="btn">
                    <i class="fas fa-home"></i> <?php echo __('back_to_home'); ?>
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo __('go_back'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/theme.js"></script>
</body>
</html>