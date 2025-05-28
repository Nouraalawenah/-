<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// التحقق من وجود معرف مقدم الخدمة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: providers.php");
    exit;
}

$provider_id = $_GET['id'];

// جلب معلومات مقدم الخدمة
$provider_sql = "SELECT sp.*, c.name as category_name 
                FROM service_providers sp 
                JOIN categories c ON sp.category_id = c.id 
                WHERE sp.id = ?";
$provider_stmt = $conn->prepare($provider_sql);
$provider_stmt->bind_param("i", $provider_id);
$provider_stmt->execute();
$provider_result = $provider_stmt->get_result();

if ($provider_result->num_rows == 0) {
    header("Location: providers.php");
    exit;
}

$provider = $provider_result->fetch_assoc();

// جلب جميع الفئات
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// تحديث مقدم الخدمة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_provider'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $website = $_POST['website'];
    $rating = $_POST['rating'];
    
    // معالجة الصورة
    $image = $provider['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../images/providers/';
        
        // إنشاء المجلد إذا لم يكن موجودًا
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // حذف الصورة القديمة إذا كانت موجودة
            if (!empty($provider['image']) && file_exists('../images/' . $provider['image'])) {
                unlink('../images/' . $provider['image']);
            }
            
            $image = 'providers/' . $file_name;
        }
    }
    
    $update_sql = "UPDATE service_providers SET 
                  name = ?, 
                  description = ?, 
                  category_id = ?, 
                  address = ?, 
                  phone = ?, 
                  email = ?, 
                  website = ?, 
                  rating = ?, 
                  image = ? 
                  WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssissssdsi", $name, $description, $category_id, $address, $phone, $email, $website, $rating, $image, $provider_id);
    
    if ($update_stmt->execute()) {
        $success = "تم تحديث مقدم الخدمة بنجاح.";
        
        // تحديث المعلومات المعروضة
        $provider['name'] = $name;
        $provider['description'] = $description;
        $provider['category_id'] = $category_id;
        $provider['address'] = $address;
        $provider['phone'] = $phone;
        $provider['email'] = $email;
        $provider['website'] = $website;
        $provider['rating'] = $rating;
        $provider['image'] = $image;
        
        // تحديث اسم الفئة
        foreach ($categories as $cat) {
            if ($cat['id'] == $category_id) {
                $provider['category_name'] = $cat['name'];
                break;
            }
        }
    } else {
        $error = "حدث خطأ أثناء تحديث مقدم الخدمة.";
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل مقدم خدمة - بوابة الخدمات المنزلية</title>
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
        
        .provider-form {
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
            margin-bottom: 15px;
        }
        
        .current-image img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container admin-container">
        <div class="admin-header">
            <h1>تعديل مقدم خدمة</h1>
            <a href="manage_providers.php" class="btn">العودة لإدارة مقدمي الخدمة</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admin-section">
            <form method="POST" action="" class="provider-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">اسم مقدم الخدمة</label>
                    <input type="text" id="name" name="name" value="<?php echo $provider['name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">رقم الهاتف</label>
                    <input type="text" id="phone" name="phone" value="<?php echo $provider['phone']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">وصف الخدمة</label>
                    <textarea id="description" name="description" rows="4" required><?php echo $provider['description']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category_id">الفئة</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- اختر الفئة --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($provider['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image"><?php echo __('provider_image'); ?></label>
                    <?php if (!empty($provider['image'])): ?>
                        <div class="current-image">
                            <p><?php echo __('current_image'); ?>:</p>
                            <img src="<?php echo SITE_URL; ?>images/providers/<?php echo $provider['image']; ?>" alt="<?php echo $provider['name']; ?>">
                        </div>
                    <?php else: ?>
                        <div class="current-image">
                            <p><?php echo __('current_image'); ?>:</p>
                            <img src="<?php echo SITE_URL; ?>images/providers/anonymous.jpg" alt="<?php echo __('default_image'); ?>">
                            <p><small><?php echo __('default_image'); ?></small></p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small><?php echo __('leave_empty_to_keep_current'); ?></small>
                </div>
                
                <button type="submit" class="btn">تحديث مقدم الخدمة</button>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>







