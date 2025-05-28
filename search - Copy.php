<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

$search_term = '';
$providers = [];

// تحديد عمود اللغة المناسب
$lang_suffix = $_SESSION['lang'] == 'ar' ? 'ar' : 'en';
$name_column = "name_" . $lang_suffix;
$desc_column = "description_" . $lang_suffix;
$cat_name_column = "c.{$name_column}";

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = $_GET['q'];

    // البحث في مقدمي الخدمة باللغة المناسبة
    $search_sql = "SELECT p.id, p.{$name_column} as name, p.{$desc_column} as description, 
                  p.phone, p.image, p.category_id, c.{$name_column} as category_name 
                  FROM service_providers p 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE p.{$name_column} LIKE ? OR p.{$desc_column} LIKE ? OR c.{$name_column} LIKE ?";
    $search_stmt = $conn->prepare($search_sql);
    $search_param = "%$search_term%";
    $search_stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $search_stmt->execute();
    $result = $search_stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $providers[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>" lang="<?php echo $_SESSION['lang']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('search_results'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .search-container {
            margin: 40px 0;
        }

        .search-form {
            display: flex;
            max-width: 600px;
            margin: 0 auto 40px;
        }

        .search-form input[type="text"] {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
        }

        .search-form button {
            padding: 12px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }

        .search-results {
            margin-top: 30px;
        }

        .search-results h2 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .providers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(222px, 1fr));
            gap: 15px;
        }

        .provider-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s;
        }

        .provider-card:hover {
            transform: translateY(-5px);
        }

        .provider-card h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .provider-category {
            display: inline-block;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .provider-phone {
            margin-bottom: 15px;
            color: #007bff;
        }

        .provider-description {
            margin-bottom: 15px;
            color: #666;
            line-height: 1.6;
            height: 80px;
            overflow: hidden;
        }

        .provider-actions {
            display: flex;
            justify-content: space-between;
        }

        .no-results {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <?php
        include 'includes/breadcrumb.php';

        $breadcrumbs = [
            ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
            ['title' => __('search_results'), 'active' => true]
        ];

        display_breadcrumbs($breadcrumbs);
        ?>

        <div class="search-container">
            <form class="search-form" method="GET" action="search.php">
                <input type="text" name="q" placeholder="<?php echo __('search_placeholder'); ?>" value="<?php echo htmlspecialchars($search_term); ?>" required>
                <button type="submit"><i class="fas fa-search"></i> <?php echo __('search'); ?></button>
            </form>

            <div class="search-results">
                <?php if (!empty($search_term)): ?>
                    <h2><?php echo __('search_results'); ?>: <?php echo htmlspecialchars($search_term); ?></h2>

                    <?php if (empty($providers)): ?>
                        <div class="no-results">
                            <p><?php echo __('no_results_found'); ?></p>
                            <p><?php echo __('try_different_keywords'); ?> <a href="advanced_search.php"><?php echo __('advanced_search'); ?></a>.</p>
                        </div>
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
                                                    <i class="fas fa-tag"></i> <?php echo $provider['category_name']; ?>
                                                </div>
                                            </div>
                                            <?php if (isset($provider['rating'])): ?>
                                                <div class="provider-rating">
                                                    <?php
                                                    $rating = $provider['rating'];
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } elseif ($i - 0.5 <= $rating) {
                                                            echo '<i class="fas fa-star-half-alt"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="provider-description">
                                            <p><?php echo mb_substr($provider['description'], 0, 100) . (mb_strlen($provider['description']) > 100 ? '...' : ''); ?></p>
                                        </div>

                                        <div class="provider-actions">
                                            <a href="provider.php?id=<?php echo $provider['id']; ?>&from=search&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="btn"><?php echo __('view_details'); ?></a>
                                            <?php if (!empty($provider['phone'])): ?>
                                                <a href="tel:<?php echo $provider['phone']; ?>" class="btn btn-outline"><i class="fas fa-phone"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="search-intro">
                        <h2><?php echo __('search_providers'); ?></h2>
                        <p><?php echo __('search_intro_text'); ?></p>
                        <p><?php echo __('for_advanced_search'); ?> <a href="advanced_search.php"><?php echo __('advanced_search'); ?></a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
