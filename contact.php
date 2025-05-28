<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate input data
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = __('all_fields_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('invalid_email');
    } else {
        // Check if table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'contact_messages'");
        if ($table_check->num_rows == 0) {
            // Create table if it doesn't exist
            $create_table = "CREATE TABLE contact_messages (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($create_table);
        }
        
        // Insert message into database
        $sql = "INSERT INTO contact_messages (name, email, subject, message, is_read) VALUES (?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = __('message_sent_success');
            // Clear form data after successful submission
            $name = $email = $subject = $message = '';
        } else {
            $error = __('message_sent_error');
        }
    }
}

// Set page title
$page_title = __('contact');

// Include header file
include 'includes/header.php';
?>

<div class="container">
    <?php
    include 'includes/breadcrumb.php';
    
    $breadcrumbs = [
        ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
        ['title' => __('contact'), 'active' => true]
    ];
    
    display_breadcrumbs($breadcrumbs);
    ?>
    
    <div class="contact-header">
        <h1><?php echo __('get_in_touch'); ?></h1>
        <p><?php echo __('contact_subtitle'); ?></p>
    </div>
    
    <div class="contact-info-cards">
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <h3><?php echo __('our_location'); ?></h3>
            <p><?php echo __('company_address'); ?></p>
            <a href="https://maps.google.com/?q=<?php echo urlencode(__('company_address')); ?>" target="_blank" class="info-card-link">
                <i class="fas fa-directions"></i> <?php echo __('get_directions'); ?>
            </a>
        </div>
        
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-phone-alt"></i>
            </div>
            <h3><?php echo __('call_us'); ?></h3>
            <p><?php echo __('company_phone'); ?></p>
            <a href="tel:<?php echo str_replace(' ', '', __('company_phone')); ?>" class="info-card-link">
                <i class="fas fa-phone"></i> <?php echo __('call_now'); ?>
            </a>
        </div>
        
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3><?php echo __('mail_us'); ?></h3>
            <p><?php echo __('company_email'); ?></p>
            <a href="mailto:<?php echo __('company_email'); ?>" class="info-card-link">
                <i class="fas fa-envelope"></i> <?php echo __('send_email'); ?>
            </a>
        </div>
        
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h3><?php echo __('working_hours'); ?></h3>
            <p><?php echo __('working_hours_value'); ?></p>
            <p class="weekend"><?php echo __('weekend'); ?>: <?php echo __('weekend_value'); ?></p>
        </div>
    </div>
    
    <div class="contact-container">
        <div class="contact-form-wrapper">
            <div class="contact-form-header">
                <h2><?php echo __('send_message'); ?></h2>
                <p><?php echo __('contact_intro'); ?></p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form action="contact.php" method="post" class="modern-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><?php echo __('name'); ?></label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="<?php echo __('enter_name'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo __('email'); ?></label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" placeholder="<?php echo __('enter_email'); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subject"><?php echo __('subject'); ?></label>
                    <div class="input-with-icon">
                        <i class="fas fa-heading"></i>
                        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" placeholder="<?php echo __('enter_subject'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="message"><?php echo __('message'); ?></label>
                    <div class="input-with-icon textarea-icon">
                        <i class="fas fa-comment-alt"></i>
                        <textarea id="message" name="message" rows="5" placeholder="<?php echo __('enter_message'); ?>" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> <?php echo __('send'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="contact-map">
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d217772.0513541755!2d35.76882784863282!3d31.83589625!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x151b5fb85d7981af%3A0x631c30c0f8dc65e8!2sAmman%2C%20Jordan!5e0!3m2!1sen!2sus!4v1623825647043!5m2!1sen!2sus" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
            
            <div class="social-connect">
                <h3><?php echo __('follow_us'); ?></h3>
                <div class="social-icons">
                    <a href="#" class="social-icon" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-icon" aria-label="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-icon" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-icon" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>





