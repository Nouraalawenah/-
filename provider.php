<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// التحقق من وجود معرف مقدم الخدمة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$provider_id = $_GET['id'];

// تحديد عمود اللغة المناسب
$lang_suffix = $_SESSION['lang'] == 'ar' ? 'ar' : 'en';
$name_column = "name_" . $lang_suffix;
$desc_column = "description_" . $lang_suffix;

// استعلام لجلب معلومات مقدم الخدمة
$provider_sql = "SELECT sp.id, sp.$name_column AS name, sp.$desc_column AS description, 
                sp.image, sp.category_id, sp.address,
                u.phone, u.email,
                c.$name_column AS category_name
                FROM service_providers sp
                JOIN users u ON sp.user_id = u.id
                JOIN categories c ON sp.category_id = c.id
                WHERE sp.id = ?";
$provider_stmt = $conn->prepare($provider_sql);
$provider_stmt->bind_param("i", $provider_id);
$provider_stmt->execute();
$provider_result = $provider_stmt->get_result();

if ($provider_result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$provider = $provider_result->fetch_assoc();
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $provider['name']; ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .provider-profile-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--primary-color);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .provider-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 3px 10px var(--shadow-color);
        }
        
        .provider-header-info {
            flex: 1;
        }
        
        .provider-details {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .provider-contact, .provider-description {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px var(--shadow-color);
            overflow: visible; /* Ensure content isn't hidden */
            min-height: 200px; /* Minimum height to ensure visibility */
        }
        
        .provider-contact h2, .provider-description h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .provider-contact p {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .provider-contact p i {
            margin-right: 10px;
            color: var(--primary-color);
            width: 20px;
        }
        
        .provider-description p {
            line-height: 1.6;
            color: var(--text-color);
            margin-bottom: 15px;
            word-wrap: break-word; /* Handle long text */
        }
        
        .provider-rating {
            margin: 10px 0;
        }
        
        .provider-rating i {
            color: #ffc107;
            margin-right: 2px;
        }
        
        .provider-category a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .provider-category a:hover {
            text-decoration: underline;
        }
        
        .back-link {
            margin-top: 20px;
        }
        
        .back-link a {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        /* Breadcrumb Navigation Styles */
        .breadcrumb-navigation {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-color);
        }
        
        .breadcrumb-navigation a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .breadcrumb-navigation a:hover {
            color: var(--secondary-color);
        }
        
        .breadcrumb-navigation .separator {
            margin: 0 10px;
            color: var(--text-muted);
        }
        
        .breadcrumb-navigation .current-page {
            color: var(--text-muted);
            font-weight: 500;
        }
        
        /* RTL support for breadcrumbs */
        [dir="rtl"] .breadcrumb-navigation .separator i {
            transform: rotate(180deg);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .breadcrumb-navigation {
                padding: 8px 12px;
                margin-bottom: 15px;
                font-size: 0.9rem;
            }
            
            .breadcrumb-navigation .separator {
                margin: 0 5px;
            }
        }
        
        @media (max-width: 768px) {
            .provider-header {
                flex-direction: column;
                text-align: center;
            }
            
            .provider-profile-image {
                width: 150px;
                height: 150px;
            }
            
            .provider-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="breadcrumb-navigation">
            <a href="index.php"><?php echo __('home'); ?></a>
            <span class="separator"><i class="fas fa-angle-right"></i></span>
            <?php if (isset($_GET['from']) && $_GET['from'] == 'search'): ?>
                <a href="advanced_search.php?<?php echo http_build_query(array_diff_key($_GET, ['id' => '', 'from' => ''])); ?>"><?php echo __('search_results'); ?></a>
                <span class="separator"><i class="fas fa-angle-right"></i></span>
            <?php elseif (!empty($provider['category_id'])): ?>
                <a href="service.php?id=<?php echo $provider['category_id']; ?>"><?php echo $provider['category_name']; ?></a>
                <span class="separator"><i class="fas fa-angle-right"></i></span>
            <?php endif; ?>
            <span class="current"><?php echo $provider['name']; ?></span>
        </div>
        
        <div class="provider-profile">
            <?php if (isset($_GET['from']) && $_GET['from'] == 'search'): ?>
                <div class="back-to-search">
                    <a href="advanced_search.php?<?php echo http_build_query(array_diff_key($_GET, ['id' => '', 'from' => ''])); ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-<?php echo $_SESSION['lang'] == 'ar' ? 'right' : 'left'; ?>"></i> 
                        <?php echo __('back_to_search_results'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="provider-header">
                <?php 
                // تحديد مسار الصورة بشكل صحيح
                if (!empty($provider['image'])) {
                    // التحقق مما إذا كان المسار يحتوي على 'providers/' بالفعل
                    $image_path = strpos($provider['image'], 'providers/') === 0 ? 
                        $provider['image'] : 'providers/' . $provider['image'];
                } else {
                    $image_path = 'providers/anonymous.jpg';
                }
                
                // التحقق من وجود الملف
                if (!file_exists("images/" . $image_path)) {
                    $image_path = 'providers/anonymous.jpg';
                }
                ?>
                <img src="images/<?php echo $image_path; ?>" alt="<?php echo $provider['name']; ?>" class="provider-profile-image">
                <div class="provider-header-info">
                    <h1><?php echo $provider['name']; ?></h1>
                    <p class="provider-category"><a href="service.php?id=<?php echo $provider['category_id']; ?>"><?php echo $provider['category_name']; ?></a></p>
                    <?php if (isset($provider['rating'])): ?>
                    <div class="provider-rating">
                        <?php
                        $rating = $provider['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                        <span class="rating-value"><?php echo $provider['rating']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="provider-details">
                <div class="provider-contact">
                    <h2><?php echo __('contact_info'); ?></h2>
                    <p><i class="fas fa-phone"></i> <?php echo $provider['phone']; ?></p>
                    <?php if (isset($provider['email']) && !empty($provider['email'])): ?>
                        <p><i class="fas fa-envelope"></i> <?php echo $provider['email']; ?></p>
                    <?php endif; ?>
                    <?php if (isset($provider['address']) && !empty($provider['address'])): ?>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo $provider['address']; ?></p>
                    <?php endif; ?>
                    <a href="tel:<?php echo $provider['phone']; ?>" class="btn"><?php echo __('call_now'); ?></a>
                    
                    <?php if (isset($_SESSION['user_id']) && !isset($_SESSION['is_provider'])): ?>
                        <a href="request_service.php?provider_id=<?php echo $provider_id; ?>" class="btn btn-secondary"><?php echo __('request_service'); ?></a>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php?redirect=provider.php?id=<?php echo $provider_id; ?>" class="btn btn-secondary"><?php echo __('login_to_request'); ?></a>
                    <?php endif; ?>
                </div>
                
                <div class="provider-description">
                    <h2><?php echo __('about_provider'); ?></h2>
                    <?php if (!empty($provider['description'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($provider['description'])); ?></p>
                    <?php else: ?>
                        <p><?php echo __('no_description_available'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($provider['category_id'])): ?>
                <div class="back-link">
                    <a href="service.php?id=<?php echo $provider['category_id']; ?>">
                        <i class="fas fa-arrow-<?php echo $_SESSION['lang'] == 'ar' ? 'right' : 'left'; ?>"></i> 
                        <?php echo __('back_to_category'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
</body>
</html>
























