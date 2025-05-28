<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// تحديد عمود اللغة المناسب
$lang_suffix = $_SESSION['lang'] == 'ar' ? 'ar' : 'en';
$name_column = "name_" . $lang_suffix;

// جلب جميع الفئات
$categories_sql = "SELECT id, $name_column AS name FROM categories ORDER BY $name_column";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name_ar = $_POST['name_ar'];
    $name_en = $_POST['name_en'];
    $phone = $_POST['phone'];
    $description_ar = $_POST['description_ar'];
    $description_en = $_POST['description_en'];
    $category_id = $_POST['category_id'];
    
    // التحقق من تحميل الصورة
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../images/";
        $image = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image;
        
        // تحميل الصورة
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // تم تحميل الصورة بنجاح
        } else {
            $error = "حدث خطأ أثناء تحميل الصورة.";
        }
    }
    
    // إضافة مقدم الخدمة إلى قاعدة البيانات
    $insert_sql = "INSERT INTO service_providers (name_ar, name_en, phone, description_ar, description_en, image, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssssssi", $name_ar, $name_en, $phone, $description_ar, $description_en, $image, $category_id);
    
    if ($insert_stmt->execute()) {
        $success = "تمت إضافة مقدم الخدمة بنجاح.";
    } else {
        $error = "حدث خطأ أثناء إضافة مقدم الخدمة: " . $insert_stmt->error;
    }
}
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة مقدم خدمة - لوحة التحكم</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <h1>إضافة مقدم خدمة جديد</h1>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admin-form">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name_ar">الاسم (عربي):</label>
                    <input type="text" id="name_ar" name="name_ar" required>
                </div>
                
                <div class="form-group">
                    <label for="name_en">الاسم (إنجليزي):</label>
                    <input type="text" id="name_en" name="name_en" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">رقم الهاتف:</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="description_ar">الوصف (عربي):</label>
                    <textarea id="description_ar" name="description_ar" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="description_en">الوصف (إنجليزي):</label>
                    <textarea id="description_en" name="description_en" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category_id">الفئة:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">اختر الفئة</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">صورة:</label>
                    <input type="file" id="image" name="image">
                </div>
                
                <button type="submit" class="btn">إضافة مقدم الخدمة</button>
                <a href="manage_providers.php" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
    
    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>

