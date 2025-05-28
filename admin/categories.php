<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// حذف فئة
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // التحقق من عدم وجود مقدمي خدمة في هذه الفئة
    $check_sql = "SELECT COUNT(*) as count FROM service_providers WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $category_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $delete_error = "لا يمكن حذف هذه الفئة لأنها تحتوي على مقدمي خدمة.";
    } else {
        $delete_sql = "DELETE FROM categories WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $category_id);
        
        if ($delete_stmt->execute()) {
            $delete_success = "تم حذف الفئة بنجاح.";
        } else {
            $delete_error = "حدث خطأ أثناء حذف الفئة.";
        }
    }
}

// إضافة فئة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $icon = $_POST['icon'];
    
    // معالجة الصورة
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../images/categories/';
        
        // إنشاء المجلد إذا لم يكن موجودًا
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image = 'categories/' . $file_name;
        }
    }
    
    $insert_sql = "INSERT INTO categories (name, description, icon, image) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssss", $name, $description, $icon, $image);
    
    if ($insert_stmt->execute()) {
        $add_success = "تم إضافة الفئة بنجاح.";
    } else {
        $add_error = "حدث خطأ أثناء إضافة الفئة.";
    }
}

// جلب جميع الفئات
$categories_sql = "SELECT id, name, description, icon, image FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>