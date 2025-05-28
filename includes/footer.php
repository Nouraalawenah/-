<footer class="main-footer">
    <div class="footer-wave">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path class="wave-1" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            <path class="wave-2" d="M0,224L48,213.3C96,203,192,181,288,154.7C384,128,480,96,576,106.7C672,117,768,171,864,208C960,245,1056,267,1152,261.3C1248,256,1344,224,1392,208L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            <path class="wave-3" d="M0,256L48,261.3C96,267,192,277,288,261.3C384,245,480,203,576,197.3C672,192,768,224,864,213.3C960,203,1056,149,1152,138.7C1248,128,1344,160,1392,176L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>
    
    <div class="footer-main">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about-section">
                    <div class="footer-logo">
                        <img src="<?php echo $base_path; ?>images/logo.png" alt="<?php echo __('site_name'); ?>">
                        <h3><?php echo __('site_name'); ?></h3>
                    </div>
                    <p class="footer-about-text"><?php echo __('footer_text'); ?></p>
                    <div class="footer-social">
                        <a href="#" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-section links-section">
                    <h3><?php echo __('quick_links'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo $base_path; ?>index.php"><i class="fas fa-home"></i> <?php echo __('home'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>about.php"><i class="fas fa-info-circle"></i> <?php echo __('about'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>services.php"><i class="fas fa-tools"></i> <?php echo __('services'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>providers.php"><i class="fas fa-user-tie"></i> <?php echo __('service_providers'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>contact.php"><i class="fas fa-envelope"></i> <?php echo __('contact'); ?></a></li>
                        <li><a href="<?php echo $base_path; ?>advanced_search.php"><i class="fas fa-search"></i> <?php echo __('advanced_search'); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section contact-section">
                    <h3><?php echo __('contact'); ?></h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <div class="icon-container">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <p><?php echo __('address'); ?>
                                    <a><?php echo __('location'); ?></a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="icon-container">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <p><?php echo __('email_address'); ?> <a href="mailto:info@homeservices.com">info@homeservices.com</a></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="icon-container">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-text">
                                <p><?php echo __('phone_footer'); ?> <a href="tel:+966123456789">+966 123 456 789</a></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="footer-section newsletter-section">
                    <h3><?php echo __('newsletter'); ?></h3>
                    <p><?php echo __('newsletter_text'); ?></p>
                    <form class="newsletter-form" action="#" method="post">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="<?php echo __('your_email'); ?>" required>
                            <button type="submit" class="btn-subscribe">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <p class="copyright">&copy; <?php echo date('Y'); ?> <?php echo __('copyright'); ?></p>
                <div class="footer-bottom-links">
                    <a href="<?php echo $base_path; ?>privacy.php"><?php echo __('privacy_policy'); ?></a>
                    <a href="<?php echo $base_path; ?>terms.php"><?php echo __('terms_of_service'); ?></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<a href="#" class="back-to-top" aria-label="<?php echo __('back_to_top'); ?>">
    <i class="fas fa-arrow-up"></i>
</a>

<script src="<?php echo $base_path; ?>js/script.js"></script>
<script src="<?php echo $base_path; ?>js/theme.js"></script>
<?php if (isset($page_specific_js) && !empty($page_specific_js)): ?>
<script src="<?php echo $base_path . $page_specific_js; ?>"></script>
<?php endif; ?>

<script>
// Back to top button functionality
document.addEventListener('DOMContentLoaded', function() {
    const backToTopButton = document.querySelector('.back-to-top');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('show');
        } else {
            backToTopButton.classList.remove('show');
        }
    });
    
    backToTopButton.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
</script>
</body>
</html>




