<?php
// Start session and include required files first
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// Now we can use the __() function
$page_title = __('about');
$current_page = basename($_SERVER['PHP_SELF']);

// Include header file
include 'includes/header.php';
?>

<div class="container">
    <?php
    // Check if breadcrumb.php exists before including it
    if (file_exists('includes/breadcrumb.php')) {
        include 'includes/breadcrumb.php';
        
        $breadcrumbs = [
            ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
            ['title' => __('about'), 'active' => true]
        ];
        
        // Check if the function exists before calling it
        if (function_exists('display_breadcrumbs')) {
            display_breadcrumbs($breadcrumbs);
        }
    }
    ?>
    
    <div class="about-container">
        <div class="about-header">
            <h1><?php echo __('about_us'); ?></h1>
            <p class="about-subtitle"><?php echo __('about_subtitle'); ?></p>
        </div>
        
        <div class="about-content">
            <div class="about-image">
                <?php if (file_exists('images/about.jpg')): ?>
                    <img src="images/about.jpg" alt="<?php echo __('about_us'); ?>">
                <?php else: ?>
                    <div class="placeholder-image">
                        <i class="fas fa-building"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="about-text">
                <h2><?php echo __('our_story'); ?></h2>
                <p><?php echo __('about_story_1'); ?></p>
                <p><?php echo __('about_story_2'); ?></p>
                
                <h2><?php echo __('our_mission'); ?></h2>
                <p><?php echo __('about_mission'); ?></p>
                
                <h2><?php echo __('our_vision'); ?></h2>
                <p><?php echo __('about_vision'); ?></p>
            </div>
        </div>
        
        <div class="about-features">
            <h2><?php echo __('why_choose_us'); ?></h2>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo __('feature_1_title'); ?></h3>
                    <p><?php echo __('feature_1_desc'); ?></p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3><?php echo __('feature_2_title'); ?></h3>
                    <p><?php echo __('feature_2_desc'); ?></p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3><?php echo __('feature_3_title'); ?></h3>
                    <p><?php echo __('feature_3_desc'); ?></p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3><?php echo __('feature_4_title'); ?></h3>
                    <p><?php echo __('feature_4_desc'); ?></p>
                </div>
            </div>
        </div>
        
        <?php if (__('show_team') === 'true'): ?>
        <div class="team-section">
            <h2><?php echo __('our_team'); ?></h2>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-image">
                        <?php if (file_exists('images/team/team1.jpg')): ?>
                            <img src="images/team/team1.jpg" alt="<?php echo __('team_member_1_name'); ?>">
                        <?php else: ?>
                            <div class="placeholder-image">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo __('team_member_1_name'); ?></h3>
                    <p class="member-position"><?php echo __('team_member_1_position'); ?></p>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <?php if (file_exists('images/team/team2.jpg')): ?>
                            <img src="images/team/team2.jpg" alt="<?php echo __('team_member_2_name'); ?>">
                        <?php else: ?>
                            <div class="placeholder-image">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo __('team_member_2_name'); ?></h3>
                    <p class="member-position"><?php echo __('team_member_2_position'); ?></p>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <?php if (file_exists('images/team/team3.jpg')): ?>
                            <img src="images/team/team3.jpg" alt="<?php echo __('team_member_3_name'); ?>">
                        <?php else: ?>
                            <div class="placeholder-image">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo __('team_member_3_name'); ?></h3>
                    <p class="member-position"><?php echo __('team_member_3_position'); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="cta-section">
            <h2><?php echo __('cta_title'); ?></h2>
            <p><?php echo __('cta_subtitle'); ?></p>
            
            <div class="cta-buttons">
                <a href="<?php echo __('cta_button_1_url'); ?>" class="btn"><?php echo __('cta_button_1_text'); ?></a>
                <a href="<?php echo __('cta_button_2_url'); ?>" class="btn btn-outline"><?php echo __('cta_button_2_text'); ?></a>
            </div>
        </div>
    </div>
</div>

<?php 
// Check if footer.php exists before including it
if (file_exists('includes/footer.php')) {
    include 'includes/footer.php'; 
}
?>

