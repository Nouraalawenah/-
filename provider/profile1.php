<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول ومن أن المستخدم هو مقدم خدمة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_provider']) || $_SESSION['is_provider'] != 1) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// جلب معلومات المستخدم
$user_sql = "SELECT u.*, sp.* FROM users u 
             LEFT JOIN service_providers sp ON u.id = sp.user_id 
             WHERE u.id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows == 0) {
    header("Location: ../index.php");
    exit;
}

$user = $user_result->fetch_assoc();

// تحديث معلومات المستخدم
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_info'])) {
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $address = $_POST['address'];
        
        // التحقق من البريد الإلكتروني
        if ($email != $user['email']) {
            $check_email_sql = "SELECT * FROM users WHERE email = ? AND id != ?";
            $check_email_stmt = $conn->prepare($check_email_sql);
            $check_email_stmt->bind_param("si", $email, $user_id);
            $check_email_stmt->execute();
            $check_email_result = $check_email_stmt->get_result();
            
            if ($check_email_result->num_rows > 0) {
                $error = __('email_already_used');
            }
        }
        
        // معالجة تحميل الصورة
        $image = $user['image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $target_dir = "../images/providers/";
            
            // إنشاء المجلد إذا لم يكن موجودًا
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "provider_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // التحقق من نوع الملف
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = $new_filename;
                } else {
                    $error = __('image_upload_failed');
                }
            } else {
                $error = __('invalid_image_type');
            }
        }
        
        if (empty($error)) {
            // بدء المعاملة
            $conn->begin_transaction();
            
            try {
                // تحديث معلومات المستخدم
                $update_user_sql = "UPDATE users SET email = ?, phone = ? WHERE id = ?";
                $update_user_stmt = $conn->prepare($update_user_sql);
                $update_user_stmt->bind_param("ssi", $email, $phone, $user_id);
                $update_user_stmt->execute();
                
                // تحديث معلومات مقدم الخدمة
                $name_ar = $name;
                $name_en = $name;
                $description_ar = $description;
                $description_en = $description;
                
                $update_provider_sql = "UPDATE service_providers SET 
                                       name = ?, name_ar = ?, name_en = ?, 
                                       description = ?, description_ar = ?, description_en = ?, 
                                       address = ?, phone = ?, email = ?, image = ? 
                                       WHERE user_id = ?";
                $update_provider_stmt = $conn->prepare($update_provider_sql);
                $update_provider_stmt->bind_param("ssssssssssi", 
                                               $name, $name_ar, $name_en, 
                                               $description, $description_ar, $description_en, 
                                               $address, $phone, $email, $image, $user_id);
                $update_provider_stmt->execute();
                
                // تأكيد المعاملة
                $conn->commit();
                
                $success = __('profile_updated_success');
                
                // تحديث معلومات المستخدم
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user = $user_result->fetch_assoc();
                
            } catch (Exception $e) {
                // التراجع عن المعاملة في حالة حدوث خطأ
                $conn->rollback();
                $error = __('profile_update_error') . ': ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // إذا تم إدخال كلمة المرور الحالية، فتحقق منها
        if (!empty($current_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $error = __('current_password_incorrect');
            } elseif (empty($new_password)) {
                $error = __('new_password_required');
            } elseif ($new_password != $confirm_password) {
                $error = __('passwords_not_match');
            }
        }
        
        if (empty($error)) {
            // تحديث كلمة المرور
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $success = __('password_updated_success');
            } else {
                $error = __('password_update_error');
            }
        }
    }
}

// تحديد مسار الصورة الشخصية
$provider_image_path = "../images/providers/";
if (!empty($user['image']) && file_exists($provider_image_path . $user['image'])) {
    $image_src = $provider_image_path . $user['image'];
} else {
    $image_src = "../images/providers/default-avatar.jpg";
}
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('profile'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/provider.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-left: 20px;
            margin-right: 20px;
            overflow: hidden;
            position: relative;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-avatar .change-avatar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            text-align: center;
            padding: 5px 0;
            font-size: 0.8rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .profile-avatar:hover .change-avatar {
            opacity: 1;
        }
        
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <?php include '../includes/provider_header.php'; ?>

    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar" id="profile-avatar">
                    <?php if (!empty($user['image']) && file_exists($provider_image_path . $user['image'])): ?>
                        <img src="<?php echo $image_src; ?>" alt="<?php echo $user['name']; ?>">
                    <?php else: ?>
                        <?php echo mb_substr($user['name'] ?? $user['username'], 0, 1, 'UTF-8'); ?>
                    <?php endif; ?>
                    <div class="change-avatar" id="change-avatar"><?php echo __('change_image'); ?></div>
                </div>
                <div class="profile-info">
                    <h1><?php echo $user['name'] ?? $user['username']; ?></h1>
                    <p><?php echo __('member_since'); ?> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="profile-tabs">
                <ul>
                    <li><a href="#" class="tab-link active" data-tab="info"><?php echo __('account_info'); ?></a></li>
                    <li><a href="#" class="tab-link" data-tab="password"><?php echo __('change_password'); ?></a></li>
                </ul>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="tab-content active" id="info">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="username"><?php echo __('username'); ?></label>
                        <input type="text" id="username" value="<?php echo $user['username']; ?>" disabled>
                        <small><?php echo __('username_cannot_change'); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="name"><?php echo __('provider_name'); ?></label>
                        <input type="text" id="name" name="name" value="<?php echo $user['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo __('email'); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php echo __('phone_number'); ?></label>
                        <input type="text" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><?php echo __('address'); ?></label>
                        <input type="text" id="address" name="address" value="<?php echo $user['address'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description"><?php echo __('description'); ?></label>
                        <textarea id="description" name="description" rows="5"><?php echo $user['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image"><?php echo __('profile_image'); ?></label>
                        <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                        <div class="btn btn-outline" id="select-image"><?php echo __('select_image'); ?></div>
                        <small><?php echo __('allowed_image_types'); ?></small>
                        
                        <div class="image-preview" id="image-preview">
                            <?php if (!empty($user['image']) && file_exists($provider_image_path . $user['image'])): ?>
                                <img src="<?php echo $image_src; ?>" alt="<?php echo __('profile_image_preview'); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_info" class="btn"><?php echo __('save_changes'); ?></button>
                </form>
            </div>
            
            <div class="tab-content" id="password">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password"><?php echo __('current_password'); ?></label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password"><?php echo __('new_password'); ?></label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><?php echo __('confirm_password'); ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="update_password" class="btn"><?php echo __('change_password'); ?></button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // التبديل بين علامات التبويب
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // إزالة الفئة النشطة من جميع الروابط والمحتويات
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // إضافة الفئة النشطة للرابط المحدد
                this.classList.add('active');
                
                // إظهار المحتوى المقابل
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // معاينة الصورة قبل الرفع
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');
        const selectImageBtn = document.getElementById('select-image');
        const changeAvatarBtn = document.getElementById('change-avatar');
        const profileAvatar = document.getElementById('profile-avatar');
        
        // فتح مربع حوار اختيار الملف عند النقر على زر اختيار الصورة
        selectImageBtn.addEventListener('click', function() {
            imageInput.click();
        });
        
        // فتح مربع حوار اختيار الملف عند النقر على زر تغيير الصورة الشخصية
        changeAvatarBtn.addEventListener('click', function() {
            imageInput.click();
        });
        
        // عرض معاينة الصورة عند اختيارها
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // إنشاء عنصر img إذا لم يكن موجودًا
                    if (imagePreview.querySelector('img') === null) {
                        const img = document.createElement('img');
                        img.alt = '<?php echo __('profile_image_preview'); ?>';
                        imagePreview.appendChild(img);
                    }
                    
                   