<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// إذا كان المستخدم مسجل الدخول بالفعل، قم بتوجيهه إلى الصفحة الرئيسية
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// التحقق من نوع التسجيل (مستخدم عادي أو مقدم خدمة)
$register_as_provider = isset($_GET['type']) && $_GET['type'] == 'provider' ? true : false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $is_provider = isset($_POST['is_provider']) ? 1 : 0;
    
    // التحقق من البيانات
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = __('all_fields_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('invalid_email');
    } elseif ($password != $confirm_password) {
        $error = __('passwords_not_match');
    } elseif (strlen($password) < 6) {
        $error = __('password_too_short');
    } else {
        // التحقق من عدم وجود مستخدم بنفس اسم المستخدم أو البريد الإلكتروني
        $check_sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = __('username_email_exists');
        } else {
            // تشفير كلمة المرور
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // إدراج المستخدم الجديد
            $insert_sql = "INSERT INTO users (username, email, password, phone, is_provider) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssi", $username, $email, $hashed_password, $phone, $is_provider);
            
            if ($insert_stmt->execute()) {
                // إذا كان مقدم خدمة، قم بإنشاء سجل في جدول مقدمي الخدمة
                if ($is_provider) {
                    $user_id = $conn->insert_id;
                    $provider_sql = "INSERT INTO service_providers (user_id, name) VALUES (?, ?)";
                    $provider_stmt = $conn->prepare($provider_sql);
                    $provider_stmt->bind_param("is", $user_id, $username);
                    $provider_stmt->execute();
                }
                
                $success = __('account_created_success');
            } else {
                $error = __('account_creation_error') . ": " . $conn->error;
            }
        }
    }
}

// تعيين عنوان الصفحة
$page_title = __('register');
$page_specific_css = 'css/auth.css';

// تضمين ملف الرأس
include 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <h2><?php echo __('register_title'); ?></h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                    <p><?php echo __('you_can_now'); ?> <a href="login.php"><?php echo __('login'); ?></a></p>
                </div>
            <?php else: ?>
                <!-- نوع الحساب -->
                <div class="account-type-selector">
                    <a href="register.php" class="account-type-btn <?php echo !$register_as_provider ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i>
                        <?php echo __('regular_user'); ?>
                    </a>
                    <a href="register.php?type=provider" class="account-type-btn <?php echo $register_as_provider ? 'active' : ''; ?>">
                        <i class="fas fa-briefcase"></i>
                        <?php echo __('service_provider'); ?>
                    </a>
                </div>
                
                <?php if ($register_as_provider): ?>
                <div class="provider-info">
                    <i class="fas fa-info-circle"></i>
                    <p><?php echo __('provider_registration_info'); ?></p>
                </div>
                <?php endif; ?>
                
                <form action="register.php<?php echo $register_as_provider ? '?type=provider' : ''; ?>" method="post">
                    <div class="form-group">
                        <label for="username"><?php echo __('username'); ?></label>
                        <input type="text" id="username" name="username" placeholder="<?php echo __('enter_username'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo __('email'); ?></label>
                        <input type="email" id="email" name="email" placeholder="<?php echo __('enter_email'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php echo __('phone_number'); ?></label>
                        <input type="tel" id="phone" name="phone" placeholder="<?php echo __('enter_phone'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?php echo __('password'); ?></label>
                        <input type="password" id="password" name="password" placeholder="<?php echo __('enter_password'); ?>" required>
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><?php echo __('confirm_password'); ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo __('confirm_your_password'); ?>" required>
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    
                    <!-- حقل مخفي لتحديد نوع المستخدم -->
                    <?php if ($register_as_provider): ?>
                    <input type="hidden" name="is_provider" value="1">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            <?php echo __('register'); ?>
                        </button>
                    </div>
                </form>
                
                <div class="auth-divider">
                    <span><?php echo __('or'); ?></span>
                </div>
                
                <div class="social-login">
                    <a href="#" class="social-btn google">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-btn facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-btn twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                </div>
                
                <div class="auth-links">
                    <p><?php echo __('have_account'); ?> <a href="login.php"><?php echo __('login'); ?></a></p>
                </div>
            <?php endif; ?>
            
            <div class="auth-footer">
                <?php echo __('site_name'); ?> &copy; <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const icon = event.currentTarget.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?>





