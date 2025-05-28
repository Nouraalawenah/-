<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// معالجة النموذج عند الإرسال
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // التحقق من البيانات المدخلة
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $is_provider = isset($_POST['is_provider']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // التحقق من تطابق كلمات المرور
    if ($password !== $confirm_password) {
        $error_message = __('passwords_not_match');
    } else {
        // التحقق من وجود المستخدم
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = __('user_already_exists');
        } else {
            // تشفير كلمة المرور
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // إضافة المستخدم الجديد
            $insert_sql = "INSERT INTO users (username, email, password, is_admin, is_provider, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssiii", $username, $email, $hashed_password, $is_admin, $is_provider, $is_active);
            
            if ($insert_stmt->execute()) {
                $success_message = __('user_added_successfully');
                // إعادة تعيين النموذج
                $username = $email = $password = $confirm_password = '';
                $is_admin = $is_provider = $is_active = 0;
            } else {
                $error_message = __('error_adding_user') . ': ' . $conn->error;
            }
        }
    }
}

// عنوان الصفحة
$page_title = __('add_user');
include '../includes/admin_header.php';
?>

<div class="admin-content-header">
    <div>
        <h1><?php echo __('add_user'); ?></h1>
        <div class="admin-breadcrumb">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <span class="separator">/</span>
            <a href="users.php"><?php echo __('users'); ?></a>
            <span class="separator">/</span>
            <span><?php echo __('add_user'); ?></span>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><?php echo __('user_information'); ?></h3>
    </div>
    <div class="admin-card-body">
        <?php if ($success_message): ?>
            <div class="admin-alert admin-alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="admin-alert admin-alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="admin-form">
            <div class="admin-row">
                <div class="admin-col-md-6">
                    <div class="admin-form-group">
                        <label for="username"><?php echo __('username'); ?> <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="admin-form-control" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                    </div>
                </div>
                <div class="admin-col-md-6">
                    <div class="admin-form-group">
                        <label for="email"><?php echo __('email'); ?> <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="admin-form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="admin-row">
                <div class="admin-col-md-6">
                    <div class="admin-form-group">
                        <label for="password"><?php echo __('password'); ?> <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="admin-form-control" required>
                    </div>
                </div>
                <div class="admin-col-md-6">
                    <div class="admin-form-group">
                        <label for="confirm_password"><?php echo __('confirm_password'); ?> <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="admin-form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="admin-row">
                <div class="admin-col-md-4">
                    <div class="admin-form-group">
                        <div class="admin-checkbox">
                            <input type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo isset($is_admin) && $is_admin ? 'checked' : ''; ?>>
                            <label for="is_admin"><?php echo __('is_admin'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-4">
                    <div class="admin-form-group">
                        <div class="admin-checkbox">
                            <input type="checkbox" id="is_provider" name="is_provider" value="1" <?php echo isset($is_provider) && $is_provider ? 'checked' : ''; ?>>
                            <label for="is_provider"><?php echo __('is_provider'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-4">
                    <div class="admin-form-group">
                        <div class="admin-checkbox">
                            <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo !isset($is_active) || $is_active ? 'checked' : ''; ?>>
                            <label for="is_active"><?php echo __('is_active'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save'); ?>
                </button>
                <a href="users.php" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-times"></i> <?php echo __('cancel'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
