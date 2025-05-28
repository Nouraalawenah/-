<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// التحقق من وجود جدول الإعدادات
$check_table = "SHOW TABLES LIKE 'settings'";
$table_result = $conn->query($check_table);
$table_exists = ($table_result->num_rows > 0);

if (!$table_exists) {
    // إذا لم يكن الجدول موجودًا، قم بتوجيه المستخدم إلى صفحة التثبيت
    $error = "جدول الإعدادات غير موجود. يرجى <a href='install_settings.php'>تثبيت الإعدادات</a> أولاً.";
    $settings = [];
} else {
    // استرجاع إعدادات الموقع الحالية
    $settings = [];
    $settings_sql = "SELECT * FROM settings";
    $settings_result = $conn->query($settings_sql);

    if ($settings_result && $settings_result->num_rows > 0) {
        while ($row = $settings_result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    // تحديث الإعدادات
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
        // إعدادات عامة
        $site_name_ar = $_POST['site_name_ar'];
        $site_name_en = $_POST['site_name_en'];
        $site_email = $_POST['site_email'];
        $site_phone = $_POST['site_phone'];
        $default_language = $_POST['default_language'];
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        // إعدادات التسجيل
        $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
        $email_verification = isset($_POST['email_verification']) ? 1 : 0;
        $admin_approve_providers = isset($_POST['admin_approve_providers']) ? 1 : 0;
        
        // إعدادات الخدمات
        $services_per_page = intval($_POST['services_per_page']);
        $allow_ratings = isset($_POST['allow_ratings']) ? 1 : 0;
        $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
        
        // تحديث الإعدادات في قاعدة البيانات
        $update_settings = [
            'site_name_ar' => $site_name_ar,
            'site_name_en' => $site_name_en,
            'site_email' => $site_email,
            'site_phone' => $site_phone,
            'default_language' => $default_language,
            'maintenance_mode' => $maintenance_mode,
            'allow_registration' => $allow_registration,
            'email_verification' => $email_verification,
            'admin_approve_providers' => $admin_approve_providers,
            'services_per_page' => $services_per_page,
            'allow_ratings' => $allow_ratings,
            'allow_comments' => $allow_comments
        ];
        
        foreach ($update_settings as $key => $value) {
            $update_sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $value, $key);
            $update_stmt->execute();
        }
        
        // تحديث الشعار إذا تم تحميله
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
            $target_dir = "../images/";
            
            // إنشاء المجلد إذا لم يكن موجودًا
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
            $new_filename = "site_logo." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // التحقق من نوع الملف
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target_file)) {
                    // تحديث مسار الشعار في قاعدة البيانات
                    $update_logo_sql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'";
                    $update_logo_stmt = $conn->prepare($update_logo_sql);
                    $update_logo_stmt->bind_param("s", $new_filename);
                    $update_logo_stmt->execute();
                    
                    $settings['site_logo'] = $new_filename;
                } else {
                    $error = "حدث خطأ أثناء تحميل الشعار.";
                }
            } else {
                $error = "نوع ملف الشعار غير مسموح به. الأنواع المسموحة هي: " . implode(', ', $allowed_types);
            }
        }
        
        // تحديث الإعدادات المحلية
        $settings = $update_settings;
        $success = "تم تحديث الإعدادات بنجاح.";
    }
}

// استرجاع قائمة اللغات المتاحة
$languages = [];
$languages_sql = "SELECT * FROM languages WHERE is_active = 1";
$languages_result = $conn->query($languages_sql);

if ($languages_result && $languages_result->num_rows > 0) {
    while ($row = $languages_result->fetch_assoc()) {
        $languages[] = $row;
    }
}

$page_title = __('settings');
include '../includes/admin_header.php';
?>

<div class="admin-content-container">
    <div class="admin-content-header">
        <h1><?php echo __('site_settings'); ?></h1>
        <div class="admin-breadcrumb">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <span class="separator">/</span>
            <span><?php echo __('settings'); ?></span>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($table_exists): ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><?php echo __('edit_settings'); ?></h2>
        </div>
        <div class="admin-card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="admin-tabs" id="settings-tabs">
                    <div class="admin-tabs-nav">
                        <button type="button" class="admin-tabs-link active" data-tab="general-settings">
                            <i class="fas fa-cog"></i> <?php echo __('general_settings'); ?>
                        </button>
                        <button type="button" class="admin-tabs-link" data-tab="registration-settings">
                            <i class="fas fa-user-plus"></i> <?php echo __('registration_settings'); ?>
                        </button>
                        <button type="button" class="admin-tabs-link" data-tab="service-settings">
                            <i class="fas fa-concierge-bell"></i> <?php echo __('service_settings'); ?>
                        </button>
                        <button type="button" class="admin-tabs-link" data-tab="appearance-settings">
                            <i class="fas fa-paint-brush"></i> <?php echo __('appearance_settings'); ?>
                        </button>
                    </div>
                    
                    <div class="admin-tabs-content active" id="general-settings">
                        <div class="admin-form-group">
                            <label for="site_name_ar"><?php echo __('site_name_arabic'); ?></label>
                            <input type="text" id="site_name_ar" name="site_name_ar" value="<?php echo $settings['site_name_ar'] ?? ''; ?>" required>
                        </div>

                        <div class="admin-form-group">
                            <label for="site_email"><?php echo __('site_email'); ?></label>
                            <input type="email" id="site_email" name="site_email" value="<?php echo $settings['site_email'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="admin-form-group">
                            <label for="site_phone"><?php echo __('site_phone'); ?></label>
                            <input type="text" id="site_phone" name="site_phone" value="<?php echo $settings['site_phone'] ?? ''; ?>">
                        </div>
                        
                        <div class="admin-form-group admin-checkbox-group">
                            <label>
                                <input type="checkbox" name="maintenance_mode" <?php echo isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                <?php echo __('maintenance_mode'); ?>
                            </label>
                            <small><?php echo __('maintenance_mode_desc'); ?></small>
                        </div>
                    </div>
                    
                    <div class="admin-tabs-content" id="registration-settings">
                        <div class="admin-form-group admin-checkbox-group">
                            <label>
                                <input type="checkbox" name="allow_registration" <?php echo isset($settings['allow_registration']) && $settings['allow_registration'] ? 'checked' : ''; ?>>
                                <?php echo __('allow_registration'); ?>
                            </label>
                            <small><?php echo __('allow_registration_desc'); ?></small>
                        </div>
                        
                        <div class="admin-form-group admin-checkbox-group">
                            <label>
                                <input type="checkbox" name="email_verification" <?php echo isset($settings['email_verification']) && $settings['email_verification'] ? 'checked' : ''; ?>>
                                <?php echo __('email_verification'); ?>
                            </label>
                            <small><?php echo __('email_verification_desc'); ?></small>
                        </div>
                        
                        <div class="admin-form-group admin-checkbox-group">
                            <label>
                                <input type="checkbox" name="admin_approve_providers" <?php echo isset($settings['admin_approve_providers']) && $settings['admin_approve_providers'] ? 'checked' : ''; ?>>
                                <?php echo __('admin_approve_providers'); ?>
                            </label>
                            <small><?php echo __('admin_approve_providers_desc'); ?></small>
                        </div>
                    </div>
                    
                    <div class="admin-tabs-content" id="service-settings">
                        <div class="admin-form-group">
                            <label for="services_per_page"><?php echo __('services_per_page'); ?></label>
                            <input type="number" id="services_per_page" name="services_per_page" value="<?php echo $settings['services_per_page'] ?? 12; ?>" min="1" max="100" required>
                        </div>
                        
                        <div class="admin-form-group admin-checkbox-group">
                            <label>
                                <input type="checkbox" name="allow_ratings" <?php echo isset($settings['allow_ratings']) && $settings['allow_ratings'] ? 'checked' : ''; ?>>
                                <?php echo __('allow_ratings'); ?>
                            </label>
                            <small><?php echo __('allow_ratings_desc'); ?></small>
                        </div>
                        
                        <div class="admin-form-group admin-checkbox-group">
                            <label>
                                <input type="checkbox" name="allow_comments" <?php echo isset($settings['allow_comments']) && $settings['allow_comments'] ? 'checked' : ''; ?>>
                                <?php echo __('allow_comments'); ?>
                            </label>
                            <small><?php echo __('allow_comments_desc'); ?></small>
                        </div>
                    </div>
                    
                    <div class="admin-tabs-content" id="appearance-settings">
                        <div class="admin-form-group">
                            <label for="site_logo"><?php echo __('site_logo'); ?></label>
                            <?php if (isset($settings['site_logo']) && !empty($settings['site_logo'])): ?>
                                <div class="current-logo">
                                    <img src="../images/<?php echo $settings['site_logo']; ?>" alt="<?php echo __('site_logo'); ?>">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="site_logo" name="site_logo" accept="image/*">
                            <small><?php echo __('site_logo_desc'); ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="admin-form-actions">
                    <button type="submit" name="update_settings" class="admin-btn admin-btn-primary">
                        <i class="fas fa-save"></i> <?php echo __('save_settings'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="admin-card">
        <div class="admin-card-body">
            <p>جدول الإعدادات غير موجود. يرجى <a href="install_settings.php" class="admin-btn admin-btn-primary">تثبيت الإعدادات</a> أولاً.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabLinks = document.querySelectorAll('.admin-tabs-link');
    const tabContents = document.querySelectorAll('.admin-tabs-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Remove active class from all tabs
            tabLinks.forEach(tab => tab.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to current tab
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
            
            // Save active tab to localStorage
            localStorage.setItem('active-settings-tab', tabId);
        });
    });
    
    // Check for saved active tab
    const savedTab = localStorage.getItem('active-settings-tab');
    if (savedTab) {
        const savedTabLink = document.querySelector(`.admin-tabs-link[data-tab="${savedTab}"]`);
        if (savedTabLink) {
            savedTabLink.click();
        }
    }
});
</script>

<?php include '../includes/admin_footer.php'; ?>



