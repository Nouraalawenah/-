<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات مزود الخدمة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_provider']) {
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// جلب قائمة الفئات
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// معالجة النموذج عند الإرسال
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // معالجة الصورة
    $image = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($file_ext), $allowed)) {
            $new_name = uniqid('service_') . '.' . $file_ext;
            $upload_dir = '../images/services/';
            
            // إنشاء المجلد إذا لم يكن موجودًا
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $upload_path = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = 'services/' . $new_name;
            } else {
                $error_message = __('error_uploading_image');
            }
        } else {
            $error_message = __('invalid_image_format');
        }
    }
    
    if (empty($error_message)) {
        // إضافة الخدمة إلى قاعدة البيانات
        $insert_sql = "INSERT INTO services (provider_id, category_id, name, description, price, image, is_active, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iissdsi", $provider_id, $category_id, $name, $description, $price, $image, $is_active);
        
        if ($insert_stmt->execute()) {
            $success_message = __('service_added_successfully');
            
            // إعادة تعيين النموذج
            $name = '';
            $description = '';
            $price = '';
            $category_id = '';
            $is_active = 1;
        } else {
            $error_message = __('error_adding_service');
        }
    }
}

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'services';
$page_title = __('add_service');

// تضمين ملف الهيدر
include 'includes/header.php';
?>

<div class="provider-content">
    <div class="provider-content-header">
        <h1><?php echo __('add_service'); ?></h1>
        <div class="provider-content-actions">
            <a href="services.php" class="provider-btn provider-btn-secondary">
                <i class="fas fa-arrow-right"></i> <?php echo __('back_to_services'); ?>
            </a>
        </div>
    </div>
    
    <?php if ($success_message): ?>
        <div class="provider-alert provider-alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="provider-alert provider-alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="provider-card">
        <div class="provider-card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="provider-form-group">
                    <label for="name"><?php echo __('service_name'); ?> <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="provider-form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                </div>
                
                <div class="provider-form-group">
                    <label for="description"><?php echo __('description'); ?> <span class="required">*</span></label>
                    <textarea id="description" name="description" class="provider-form-control" rows="5" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
                
                <div class="provider-row">
                    <div class="provider-col-md-6">
                        <div class="provider-form-group">
                            <label for="price"><?php echo __('price'); ?> (<?php echo __('currency_symbol'); ?>) <span class="required">*</span></label>
                            <input type="number" id="price" name="price" class="provider-form-control" value="<?php echo isset($price) ? $price : ''; ?>" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="provider-col-md-6">
                        <div class="provider-form-group">
                            <label for="category_id"><?php echo __('category'); ?> <span class="required">*</span></label>
                            <select id="category_id" name="category_id" class="provider-form-control" required>
                                <option value=""><?php echo __('select_category'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="provider-form-group">
                    <label for="image"><?php echo __('service_image'); ?></label>
                    <input type="file" id="image" name="image" class="provider-form-control-file">
                    <small class="provider-form-text"><?php echo __('allowed_image_formats'); ?>: JPG, JPEG, PNG, GIF</small>
                </div>
                
                <div class="provider-form-group">
                    <div class="provider-form-check">
                        <input type="checkbox" id="is_active" name="is_active" class="provider-form-check-input" <?php echo (!isset($is_active) || $is_active) ? 'checked' : ''; ?>>
                        <label for="is_active" class="provider-form-check-label"><?php echo __('service_active'); ?></label>
                    </div>
                    <small class="provider-form-text"><?php echo __('service_active_help'); ?></small>
                </div>
                
                <div class="provider-form-actions">
                    <button type="submit" class="provider-btn provider-btn-primary">
                        <i class="fas fa-plus"></i> <?php echo __('add_service'); ?>
                    </button>
                    <a href="services.php" class="provider-btn provider-btn-secondary">
                        <?php echo __('cancel'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


