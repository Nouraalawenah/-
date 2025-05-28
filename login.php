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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // التحقق من البيانات
    if (empty($username) || empty($password)) {
        $error = __('all_fields_required');
    } else {
        // التحقق من وجود المستخدم
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = isset($user['is_admin']) ? $user['is_admin'] : 0;
                
                // التحقق من وجود عمود is_provider
                $_SESSION['is_provider'] = isset($user['is_provider']) ? $user['is_provider'] : 0;
                
                // توجيه المستخدم بناءً على نوعه
                if ($_SESSION['is_admin']) {
                    header("Location: admin/dashboard.php");
                } elseif ($_SESSION['is_provider']) {
                    header("Location: provider/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = __('incorrect_password');
            }
        } else {
            $error = __('username_email_not_found');
        }
    }
}

// تعيين عنوان الصفحة
$page_title = __('login');
$page_specific_css = 'css/auth.css';

// تضمين ملف الرأس
include 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <h2><?php echo __('login_title'); ?></h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username"><?php echo __('username_or_email'); ?></label>
                    <input type="text" id="username" name="username" placeholder="<?php echo __('enter_username_or_email'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php echo __('password'); ?></label>
                    <input type="password" id="password" name="password" placeholder="<?php echo __('enter_password'); ?>" required>
                    <span class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        <?php echo __('login'); ?>
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
                <p><?php echo __('no_account'); ?> <a href="register.php"><?php echo __('create_account'); ?></a></p>
                <p><a href="forgot_password.php"><?php echo __('forgot_password'); ?></a></p>
            </div>
            
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

