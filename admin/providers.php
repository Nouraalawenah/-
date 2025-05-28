<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// حذف مقدم خدمة
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $provider_id = $_GET['delete'];
    
    // التحقق من عدم وجود خدمات لهذا المقدم
    $check_sql = "SELECT COUNT(*) as count FROM services WHERE provider_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $provider_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $delete_error = "لا يمكن حذف مقدم الخدمة هذا لأنه يقدم خدمات.";
    } else {
        // جلب معلومات الصورة قبل الحذف
        $image_sql = "SELECT image FROM service_providers WHERE id = ?";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $provider_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image = $image_result->fetch_assoc()['image'];
        
        $delete_sql = "DELETE FROM service_providers WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $provider_id);
        
        if ($delete_stmt->execute()) {
            // حذف الصورة إذا كانت موجودة
            if (!empty($image) && file_exists('../images/' . $image)) {
                unlink('../images/' . $image);
            }
            
            $delete_success = "تم حذف مقدم الخدمة بنجاح.";
        } else {
            $delete_error = "حدث خطأ أثناء حذف مقدم الخدمة.";
        }
    }
}

// إضافة مقدم خدمة جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_provider'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $website = $_POST['website'];
    $rating = $_POST['rating'];
    
    // معالجة الصورة
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../images/providers/';
        
        // إنشاء المجلد إذا لم يكن موجودًا
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image = 'providers/' . $file_name;
        }
    }
    
    $insert_sql = "INSERT INTO service_providers (name, description, category_id, address, phone, email, website, rating, image) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssissssds", $name, $description, $category_id, $address, $phone, $email, $website, $rating, $image);
    
    if ($insert_stmt->execute()) {
        $add_success = "تم إضافة مقدم الخدمة بنجاح.";
    } else {
        $add_error = "حدث خطأ أثناء إضافة مقدم الخدمة.";
    }
}

// جلب جميع مقدمي الخدمة
$providers_sql = "SELECT sp.id, sp.name, sp.description, sp.address, sp.phone, sp.email, sp.rating, sp.image, 
                 c.name as category_name 
                 FROM service_providers sp 
                 JOIN categories c ON sp.category_id = c.id 
                 ORDER BY sp.name";
$providers_result = $conn->query($providers_sql);
$providers = [];

if ($providers_result->num_rows > 0) {
    while ($row = $providers_result->fetch_assoc()) {
        $providers[] = $row;
    }
}

// جلب جميع الفئات للقائمة المنسدلة
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>