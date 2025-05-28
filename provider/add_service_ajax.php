<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول ومن أن المستخدم هو مقدم خدمة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_provider']) || $_SESSION['is_provider'] != 1) {
    echo json_encode(['success' => false, 'message' => __('not_authorized')]);
    exit;
}

// التحقق من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => __('invalid_request')]);
    exit;
}

// الحصول على معلومات مقدم الخدمة
$user_id = $_SESSION['user_id'];
$provider_query = "SELECT * FROM service_providers WHERE user_id = ?";
$stmt = $conn->prepare($provider_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$provider_result = $stmt->get_result();

if ($provider_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => __('provider_not_found')]);
    exit;
}

$provider = $provider_result->fetch_assoc();
$provider_id = $provider['id'];

// التحقق من البيانات المطلوبة
$required_fields = ['title_ar', 'title_en', 'description_ar', 'description_en', 'price', 'category_id'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => __('all_fields_required')]);
        exit;
    }
}

$title_ar = $_POST['title_ar'];
$title_en = $_POST['title_en'];
$description_ar = $_POST['description_ar'];
$description_en = $_POST['description_en'];
$price = $_POST['price'];
$category_id = $_POST['category_id'];
$is_active = isset($_POST['is_active']) ? 1 : 0;

// التحقق من صحة السعر
if (!is_numeric($price) || $price < 0) {
    echo json_encode(['success' => false, 'message' => __('invalid_price')]);
    exit;
}

// التحقق من صحة الفئة
$category_check = "SELECT id FROM categories WHERE id = ?";
$category_stmt = $conn->prepare($category_check);
$category_stmt->bind_param("i", $category_id);
$category_stmt->execute();
$category_result = $category_stmt->get_result();

if ($category_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => __('invalid_category')]);
    exit;
}

// معالجة الصورة
$image = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5 ميجابايت
    
    // التحقق من نوع الملف وحجمه
    if (!in_array($_FILES['image']['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => __('invalid_image_type')]);
        exit;
    }
    
    if ($_FILES['image']['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => __('image_too_large')]);
        exit;
    }
    
    $target_dir = "../images/services/";
    
    // إنشاء المجلد إذا لم يكن موجودًا
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    // تحميل الصورة
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image = 'services/' . $file_name;
    } else {
        echo json_encode(['success' => false, 'message' => __('error_uploading_image')]);
        exit;
    }
}

// إضافة الخدمة إلى قاعدة البيانات
$insert_sql = "INSERT INTO services (title_ar, title_en, description_ar, description_en, price, image, category_id, provider_id, is_active, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ssssdsiis", $title_ar, $title_en, $description_ar, $description_en, $price, $image, $category_id, $provider_id, $is_active);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => __('service_added_successfully')]);
} else {
    echo json_encode(['success' => false, 'message' => __('error_adding_service') . ': ' . $insert_stmt->error]);
}
?>
