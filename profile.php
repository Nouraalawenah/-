<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// جلب معلومات المستخدم
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$user = $user_result->fetch_assoc();

// جلب طلبات الخدمة للمستخدم
$requests = [];
$requests_sql = "SELECT sr.*, s.name_" . $_SESSION['lang'] . " as service_name, 
                s.id as service_id, s.provider_id,
                sp.name_" . $_SESSION['lang'] . " as provider_name, 
                sp.phone as provider_phone, sp.email as provider_email,
                sr.created_at, sr.status, sr.scheduled_date
                FROM service_requests sr
                JOIN services s ON sr.service_id = s.id
                JOIN service_providers sp ON s.provider_id = sp.id
                WHERE sr.user_id = ?
                ORDER BY sr.created_at DESC
                LIMIT 10";

$requests_stmt = $conn->prepare($requests_sql);
if ($requests_stmt) {
    $requests_stmt->bind_param("i", $user_id);
    $requests_stmt->execute();
    $requests_result = $requests_stmt->get_result();

    if ($requests_result && $requests_result->num_rows > 0) {
        while ($row = $requests_result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
}

// تحديث معلومات المستخدم
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_info'])) {
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
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
            $target_dir = "images/users/";
            
            // إنشاء المجلد إذا لم يكن موجودًا
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // التحقق من نوع الملف
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = $new_filename;
                    
                    // تحديث صورة المستخدم في قاعدة البيانات
                    $update_image_sql = "UPDATE users SET image = ? WHERE id = ?";
                    $update_image_stmt = $conn->prepare($update_image_sql);
                    $update_image_stmt->bind_param("si", $new_filename, $user_id);
                    $update_image_stmt->execute();
                } else {
                    $error = __('image_upload_failed');
                }
            } else {
                $error = __('invalid_image_type');
            }
        }
        
        if (empty($error)) {
            // التحقق من وجود عمود image في جدول users
            $check_column = "SHOW COLUMNS FROM users LIKE 'image'";
            $column_result = $conn->query($check_column);
            
            if ($column_result->num_rows > 0) {
                // تحديث المعلومات مع الصورة
                $update_sql = "UPDATE users SET email = ?, phone = ?, image = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssi", $email, $phone, $image, $user_id);
            } else {
                // تحديث المعلومات بدون الصورة
                $update_sql = "UPDATE users SET email = ?, phone = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssi", $email, $phone, $user_id);
            }
            
            if ($update_stmt->execute()) {
                $success = __('profile_updated_success');
                // تحديث معلومات المستخدم
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user = $user_result->fetch_assoc();
            } else {
                $error = __('profile_update_error');
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
$user_image_path = "images/users/";
$image_src = "images/users/default-avatar.jpg";

// التحقق من وجود صورة المستخدم
if (!empty($user['image'])) {
    $full_image_path = $user_image_path . $user['image'];
    if (file_exists($full_image_path)) {
        $image_src = $full_image_path;
    }
}

// Add user profile completeness indicator
$profile_completeness = 0;
if (!empty($user['email'])) $profile_completeness += 25;
if (!empty($user['phone'])) $profile_completeness += 25;
if (!empty($user['image'])) $profile_completeness += 25;
if (!empty($user['address'])) $profile_completeness += 25;

?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('profile'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .profile-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            padding: 30px;
            margin: 40px 0;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
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
        
        .profile-info h1 {
            margin-bottom: 5px;
        }
        
        .profile-info p {
            color: var(--text-muted);
        }
        
        .profile-tabs {
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .profile-tabs ul {
            display: flex;
            list-style: none;
            padding: 0;
        }
        
        .profile-tabs li {
            margin-left: 20px;
            margin-right: 20px;
        }
        
        .profile-tabs a {
            display: block;
            padding: 10px 0;
            color: var(--text-muted);
            text-decoration: none;
            border-bottom: 2px solid transparent;
        }
        
        .profile-tabs a.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .activity-list {
            margin-top: 20px;
        }
        
        .activity-item {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px var(--shadow-color);
            transition: transform 0.2s;
        }
        
        .activity-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px var(--shadow-color);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .activity-title {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .activity-date {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .activity-content {
            margin-bottom: 10px;
        }
        
        .activity-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .activity-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }
        
        .activity-provider {
            display: flex;
            align-items: center;
        }
        
        .provider-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-left: 10px;
            margin-right: 10px;
        }
        
        .no-activities {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
        }
        
        .activity-actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.9rem;
        }
        
        @media (min-width: 768px) {
            .activity-actions {
                flex-direction: row;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <?php
        include 'includes/breadcrumb.php';
        
        $breadcrumbs = [
            ['title' => __('home'), 'url' => 'index.php', 'icon' => 'fa-home'],
            ['title' => __('profile'), 'active' => true]
        ];
        
        display_breadcrumbs($breadcrumbs);
        ?>
        
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar" id="profile-avatar">
                    <?php if (!empty($user['image']) && file_exists($user_image_path . $user['image'])): ?>
                        <img src="<?php echo $image_src; ?>" alt="<?php echo $user['username']; ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?php echo mb_substr($user['username'], 0, 1, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <div class="change-avatar" id="change-avatar"><?php echo __('change_image'); ?></div>
                </div>
                <div class="profile-info">
                    <h1><?php echo $user['username']; ?></h1>
                    <p><?php echo __('member_since'); ?> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="profile-tabs">
                <ul>
                    <li><a href="#" class="tab-link active" data-tab="info"><?php echo __('account_info'); ?></a></li>
                    <li><a href="#" class="tab-link" data-tab="password"><?php echo __('change_password'); ?></a></li>
                    <li><a href="#" class="tab-link" data-tab="activities"><?php echo __('my_activities'); ?></a></li>
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
                        <label for="email"><?php echo __('email'); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php echo __('phone_number'); ?></label>
                        <input type="text" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="image"><?php echo __('profile_image'); ?></label>
                        <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                        <div class="btn btn-outline" id="select-image"><?php echo __('select_image'); ?></div>
                        <small><?php echo __('allowed_image_types'); ?></small>
                        
                        <div class="image-preview" id="image-preview">
                            <?php if (!empty($user['image']) && file_exists($user_image_path . $user['image'])): ?>
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
            
            <div class="tab-content" id="activities">
                <h3><?php echo __('recent_service_requests'); ?></h3>
                
                <?php if (empty($requests)): ?>
                    <div class="no-activities">
                        <i class="fas fa-clipboard-list fa-3x"></i>
                        <p><?php echo __('no_activities_yet'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($requests as $request): ?>
                            <div class="activity-item">
                                <div class="activity-header">
                                    <div class="activity-title">
                                        <?php echo htmlspecialchars($request['service_name']); ?>
                                    </div>
                                    <div class="activity-date">
                                        <i class="far fa-calendar-alt"></i> 
                                        <?php echo date('d/m/Y', strtotime($request['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="activity-content">
                                    <p>
                                        <strong><?php echo __('status'); ?>:</strong>
                                        <span class="activity-status status-<?php echo $request['status']; ?>">
                                            <?php echo __('status_' . $request['status']); ?>
                                        </span>
                                    </p>
                                    
                                    <?php if (!empty($request['scheduled_date'])): ?>
                                    <p>
                                        <strong><?php echo __('scheduled_date'); ?>:</strong>
                                        <?php echo date('d/m/Y', strtotime($request['scheduled_date'])); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($request['message'])): ?>
                                    <p>
                                        <strong><?php echo __('your_message'); ?>:</strong>
                                        <?php echo nl2br(htmlspecialchars($request['message'])); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="activity-footer">
                                    <div class="activity-provider">
                                        <div class="provider-avatar">
                                            <?php echo mb_substr($request['provider_name'], 0, 1, 'UTF-8'); ?>
                                        </div>
                                        <div>
                                            <div><strong><?php echo htmlspecialchars($request['provider_name']); ?></strong></div>
                                            <div><?php echo htmlspecialchars($request['provider_phone']); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="activity-actions">
                                        <a href="request_details.php?id=<?php echo $request['id']; ?>" class="btn btn-sm">
                                            <?php echo __('view_request_details'); ?>
                                        </a>
                                        <a href="provider.php?id=<?php echo $request['provider_id']; ?>" class="btn btn-sm btn-outline">
                                            <?php echo __('view_provider'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center">
                        <a href="service_request.php" class="btn">
                            <?php echo __('view_all_requests'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- إضافة قسم للطلبات المكتملة التي تحتاج إلى تقييم -->
    <div class="profile-section">
        <h3><?php echo __('completed_requests_to_review'); ?></h3>
        
        <?php
        // جلب الطلبات المكتملة التي لم يتم تقييمها بعد
        // إضافة فحص للتأكد من أن تاريخ الإكمال ليس في المستقبل
        $completed_requests_sql = "SELECT sr.id, sr.service_id, s.name_" . $_SESSION['lang'] . " as service_name, 
                                  sp.name_" . $_SESSION['lang'] . " as provider_name, 
                                  sr.completed_at, sr.updated_at, sr.created_at
                                  FROM service_requests sr
                                  JOIN services s ON sr.service_id = s.id
                                  JOIN service_providers sp ON s.provider_id = sp.id
                                  LEFT JOIN service_reviews r ON sr.id = r.request_id AND r.user_id = sr.user_id
                                  WHERE sr.user_id = ? AND sr.status = 'completed' AND r.id IS NULL
                                  ORDER BY COALESCE(sr.completed_at, sr.updated_at) DESC
                                  LIMIT 5";
        
        $completed_stmt = $conn->prepare($completed_requests_sql);
        $completed_stmt->bind_param("i", $user_id);
        $completed_stmt->execute();
        $completed_result = $completed_stmt->get_result();
        
        if ($completed_result->num_rows > 0):
        ?>
        <div class="alert alert-info">
            <?php echo __('pending_reviews_message'); ?>
        </div>
        
        <div class="requests-list">
            <?php while ($request = $completed_result->fetch_assoc()): ?>
            <div class="request-item">
                <div class="request-info">
                    <h4><?php echo htmlspecialchars($request['service_name']); ?></h4>
                    <p><?php echo __('provider'); ?>: <?php echo htmlspecialchars($request['provider_name']); ?></p>
                    <p><?php echo __('completed_on'); ?>: 
                        <?php 
                        // التحقق من صحة تاريخ الإكمال
                        $completed_date = null;
                        
                        if (!empty($request['completed_at'])) {
                            $completed_timestamp = strtotime($request['completed_at']);
                            // التحقق من أن التاريخ ليس في المستقبل
                            if ($completed_timestamp <= time()) {
                                $completed_date = $completed_timestamp;
                            }
                        }
                        
                        if ($completed_date === null && !empty($request['updated_at'])) {
                            $updated_timestamp = strtotime($request['updated_at']);
                            if ($updated_timestamp <= time()) {
                                $completed_date = $updated_timestamp;
                            }
                        }
                        
                        // إذا كانت جميع التواريخ غير صالحة، استخدم تاريخ الإنشاء
                        if ($completed_date === null) {
                            $completed_date = strtotime($request['created_at']);
                        }
                        
                        echo date('d/m/Y', $completed_date);
                        ?>
                    </p>
                </div>
                <div class="request-actions">
                    <a href="request_details.php?id=<?php echo $request['id']; ?>#review" class="btn btn-primary">
                        <?php echo __('add_review'); ?>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <p><?php echo __('no_pending_reviews'); ?></p>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
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
                    
                    // تعيين مصدر الصورة
                    imagePreview.querySelector('img').src = e.target.result;
                    imagePreview.style.display = 'block';
                    
                    // تحديث الصورة الشخصية في الهيدر
                    if (profileAvatar.querySelector('img') === null) {
                        const avatarImg = document.createElement('img');
                        profileAvatar.innerHTML = '';
                        profileAvatar.appendChild(avatarImg);
                        profileAvatar.appendChild(changeAvatarBtn);
                    }
                    
                    profileAvatar.querySelector('img').src = e.target.result;
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>















