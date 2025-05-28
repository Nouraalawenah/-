<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// التحقق من وجود معرف الفئة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: categories.php");
    exit;
}

$category_id = $_GET['id'];

// جلب معلومات الفئة
$category_sql = "SELECT id, name, description, icon, image FROM categories WHERE id = ?";
$category_stmt = $conn->prepare($category_sql);
$category_stmt->bind_param("i", $category_id);
$category_stmt->execute();
$category_result = $category_stmt->get_result();

if ($category_result->num_rows == 0) {
    header("Location: categories.php");
    exit;
}

$category = $category_result->fetch_assoc();

// تحديث الفئة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $icon = $_POST['icon'];
    
    // معالجة الصورة
    $image = $category['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../images/categories/';
        
        // إنشاء المجلد إذا لم يكن موجودًا
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // حذف الصورة القديمة إذا كانت موجودة
            if (!empty($category['image']) && file_exists('../images/' . $category['image'])) {
                unlink('../images/' . $category['image']);
            }
            
            $image = 'categories/' . $file_name;
        }
    }
    
    $update_sql = "UPDATE categories SET name = ?, description = ?, icon = ?, image = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssi", $name, $description, $icon, $image, $category_id);
    
    if ($update_stmt->execute()) {
        $success = "تم تحديث الفئة بنجاح.";
        
        // تحديث المعلومات المعروضة
        $category['name'] = $name;
        $category['description'] = $description;
        $category['icon'] = $icon;
        $category['image'] = $image;
    } else {
        $error = "حدث خطأ أثناء تحديث الفئة.";
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الفئة - بوابة الخدمات المنزلية</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .admin-container {
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-header h1 {
            margin: 0;
        }
        
        .admin-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .category-form {
            max-width: 600px;
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
        
        .current-image {
            margin-top: 10px;
            margin-bottom: 20px;
        }
        
        .current-image img {
            max-width: 200px;
            height: auto;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container admin-container">
        <div class="admin-header">
            <h1>تعديل الفئة: <?php echo $category['name']; ?></h1>
            <a href="categories.php" class="btn">العودة لإدارة الفئات</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admin-section">
            <form method="POST" action="" class="category-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">اسم الفئة</label>
                    <input type="text" id="name" name="name" value="<?php echo $category['name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">وصف الفئة</label>
                    <textarea id="description" name="description" rows="4" required><?php echo $category['description']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="icon">أيقونة الفئة (اسم أيقونة Font Awesome)</label>
                    <input type="text" id="icon" name="icon" value="<?php echo $category['icon']; ?>" placeholder="مثال: fas fa-leaf" required>
                </div>
                
                <div class="form-group">
                    <label for="image">صورة الفئة</label>
                    <?php if ($category['image']): ?>
                        <div class="current-image">
                            <p>الصورة الحالية:</p>
                            <img src="../images/<?php echo $category['image']; ?>" alt="<?php echo $category['name']; ?>">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small>اترك هذا الحقل فارغًا للاحتفاظ بالصورة الحالية.</small>
                </div>
                
                <button type="submit" class="btn" name="update_category">تحديث الفئة</button>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
