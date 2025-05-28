<?php
// تحديد الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="provider-sidebar">
    <div class="provider-sidebar-header">
        <h2><?php echo __('provider_panel'); ?></h2>
        <button class="sidebar-toggle" id="sidebar-toggle">
            <i class="fas fa-chevron-<?php echo $lang['dir'] == 'rtl' ? 'right' : 'left'; ?>"></i>
        </button>
    </div>
    
    <div class="provider-user-info">
        <div class="provider-user-avatar">
            <?php if (!empty($_SESSION['user_image'])): ?>
                <img src="../images/users/<?php echo $_SESSION['user_image']; ?>" alt="<?php echo $_SESSION['username']; ?>">
            <?php else: ?>
                <i class="fas fa-user-tie"></i>
            <?php endif; ?>
        </div>
        <div class="provider-user-details">
            <h3><?php echo $_SESSION['username']; ?></h3>
            <p><?php echo __('service_provider'); ?></p>
        </div>
    </div>
    
    <nav class="provider-nav">
        <div class="provider-nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php" class="provider-nav-link">
                <i class="fas fa-tachometer-alt"></i>
                <span><?php echo __('dashboard'); ?></span>
            </a>
        </div>
        <div class="provider-nav-item <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>">
            <a href="services.php" class="provider-nav-link">
                <i class="fas fa-tools"></i>
                <span><?php echo __('my_services'); ?></span>
            </a>
        </div>
        <div class="provider-nav-item <?php echo ($current_page == 'requests.php' || $current_page == 'view_request.php' || $current_page == 'update_request.php') ? 'active' : ''; ?>">
            <a href="requests.php" class="provider-nav-link">
                <i class="fas fa-clipboard-list"></i>
                <span><?php echo __('service_requests'); ?></span>
            </a>
        </div>
        <div class="provider-nav-item <?php echo ($current_page == 'reviews.php') ? 'active' : ''; ?>">
            <a href="reviews.php" class="provider-nav-link">
                <i class="fas fa-star"></i>
                <span><?php echo __('reviews'); ?></span>
            </a>
        </div>
        <div class="provider-nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <a href="profile.php" class="provider-nav-link">
                <i class="fas fa-user-cog"></i>
                <span><?php echo __('profile'); ?></span>
            </a>
        </div>
        <div class="provider-nav-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <a href="settings.php" class="provider-nav-link">
                <i class="fas fa-cog"></i>
                <span><?php echo __('settings'); ?></span>
            </a>
        </div>
        <div class="provider-nav-item">
            <a href="../provider/index.php" class="provider-nav-link">
                <i class="fas fa-home"></i>
                <span><?php echo __('home'); ?></span>
            </a>
        </div>
        <div class="provider-nav-item">
            <a href="../logout.php" class="provider-nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span><?php echo __('logout'); ?></span>
            </a>
        </div>
    </nav>
</aside>


