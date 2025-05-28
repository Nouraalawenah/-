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
    $name = $_POST['name'];
    $description = $_POST['description'];
    $icon = $_POST['icon'];
    
    // معالجة الصورة
    $image = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($file_ext), $allowed)) {
            $new_name = uniqid('category_') . '.' . $file_ext;
            $upload_dir = '../images/';
            $upload_path = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = $new_name;
            } else {
                $error_message = "حدث خطأ أثناء رفع الصورة.";
            }
        } else {
            $error_message = "نوع الملف غير مسموح به. الأنواع المسموح بها هي: " . implode(', ', $allowed);
        }
    }
    
    if (empty($error_message)) {
        $insert_sql = "INSERT INTO categories (name, description, icon, image) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $name, $description, $icon, $image);
        
        if ($insert_stmt->execute()) {
            $success_message = "تم إضافة الفئة بنجاح.";
            // إعادة تعيين النموذج
            $name = $description = $icon = $image = '';
        } else {
            $error_message = "حدث خطأ أثناء إضافة الفئة: " . $conn->error;
        }
    }
}

// عنوان الصفحة
$page_title = __('add_category');
include '../includes/admin_header.php';
?>

<div class="admin-content-header">
    <div>
        <h1><?php echo __('add_category'); ?></h1>
        <div class="admin-breadcrumb">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <span class="separator">/</span>
            <a href="manage_categories.php"><?php echo __('categories'); ?></a>
            <span class="separator">/</span>
            <span><?php echo __('add_category'); ?></span>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><?php echo __('category_information'); ?></h3>
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
        
        <form method="post" class="admin-form" enctype="multipart/form-data">
            <div class="admin-form-group">
                <label for="name"><?php echo __('category_name'); ?> <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="admin-form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>
            
            <div class="admin-form-group">
                <label for="description"><?php echo __('category_description'); ?> <span class="required">*</span></label>
                <textarea id="description" name="description" class="admin-form-control" rows="4" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            
            <div class="admin-form-group">
                <label for="icon"><?php echo __('category_icon'); ?> <span class="required">*</span></label>
                <input type="text" id="icon" name="icon" class="admin-form-control" value="<?php echo isset($icon) ? htmlspecialchars($icon) : ''; ?>" required>
                <small class="admin-form-text"><?php echo __('icon_help_text'); ?></small>
                <div class="icon-preview">
                    <i class="fas fa-<?php echo isset($icon) ? htmlspecialchars($icon) : 'question'; ?>"></i>
                    <span><?php echo __('icon_preview'); ?></span>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="image"><?php echo __('category_image'); ?></label>
                <input type="file" id="image" name="image" class="admin-form-control" accept="image/*">
                <small class="admin-form-text"><?php echo __('image_help_text'); ?></small>
            </div>
            
            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save'); ?>
                </button>
                <a href="manage_categories.php" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-times"></i> <?php echo __('cancel'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    // معاينة الأيقونة عند الكتابة
    document.getElementById('icon').addEventListener('input', function() {
        const iconPreview = document.querySelector('.icon-preview i');
        iconPreview.className = 'fas fa-' + (this.value || 'question');
    });
</script>

<?php include '../includes/admin_footer.php'; ?>





