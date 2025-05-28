<?php
// Start session and include required files first
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// Now we can use the __() function
$page_title = __('home');

// Include header file
include 'includes/header.php';

// Query to get all categories
$sql = "SELECT id, name, description, icon, image FROM categories";
$result = $conn->query($sql);
$categories = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
    
<div class="hero">
    <div class="container">
        <h1><?php echo __('hero_title'); ?></h1>
        <p><?php echo __('hero_subtitle'); ?></p>
    </div>
</div>

<div class="container">
    <section class="services">
        <h2><?php echo __('our_services'); ?></h2>
        <div class="services-grid">
            <?php foreach ($categories as $category): ?>
            <div class="service-card">
                <div class="service-image">
                    <img src="images/<?php echo $category['image']; ?>" alt="<?php echo $category['name']; ?>">
                </div>
                <div class="service-icon">
                    <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                </div>
                <h3><?php echo $category['name']; ?></h3>
                <p><?php echo $category['description']; ?></p>
                <a href="service.php?id=<?php echo $category['id']; ?>" class="btn"><?php echo __('view_details'); ?></a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="search-box">
    <form action="advanced_search.php" method="GET">
        <input type="text" name="term" placeholder="<?php echo __('search_placeholder'); ?>">
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>
</div>

<?php
// تضمين ملف التذييل
include 'includes/footer.php';
?>





