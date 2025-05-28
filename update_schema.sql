<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// حذف خدمة
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $service_id = $_GET['delete'];
    
    // التحقق من عدم وجود طلبات لهذه الخدمة
    $check_sql = "SELECT COUNT(*) as count FROM service_requests WHERE service_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $service_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $delete_error = "لا يمكن حذف هذه الخدمة لأنها مرتبطة بطلبات.";
    } else {
        // جلب معلومات الصورة قبل الحذف
        $image_sql = "SELECT image FROM services WHERE id = ?";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $service_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image = $image_result->fetch_assoc()['image'];
        
        $delete_sql = "DELETE FROM services WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $service_id);
        
        if ($delete_stmt->execute()) {
            // حذف الصورة إذا كانت موجودة
            if (!empty($image) && file_exists('../images/' . $image)) {
                unlink('../images/' . $image);
            }
            
            $delete_success = "تم حذف الخدمة بنجاح.";
        } else {
            $delete_error = "حدث خطأ أثناء حذف الخدمة.";
        }
    }
}

// إضافة خدمة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $provider_id = $_POST['provider_id'] ?: null;
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    
    // معالجة الصورة
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../images/services/';
        
        // إنشاء المجلد إذا لم يكن موجودًا
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image = 'services/' . $file_name;
        }
    }
    
    $insert_sql = "INSERT INTO services (name, description, provider_id, price, duration, image) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssidss", $name, $description, $provider_id, $price, $duration, $image);
    
    if ($insert_stmt->execute()) {
        $add_success = "تم إضافة الخدمة بنجاح.";
    } else {
        $add_error = "حدث خطأ أثناء إضافة الخدمة.";
    }
}

// جلب جميع الخدمات
$services_sql = "SELECT s.id, s.name, s.description, s.price, s.duration, s.image, 
                p.name as provider_name 
                FROM services s 
                LEFT JOIN service_providers p ON s.provider_id = p.id 
                ORDER BY s.name";
$services_result = $conn->query($services_sql);
$services = [];

if ($services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// جلب جميع مقدمي الخدمة للقائمة المنسدلة
$providers_sql = "SELECT id, name FROM service_providers ORDER BY name";
$providers_result = $conn->query($providers_sql);
$providers = [];

if ($providers_result->num_rows > 0) {
    while ($row = $providers_result->fetch_assoc()) {
        $providers[] = $row;
    }
}
?>

-- تحديث جدول الفئات
ALTER TABLE categories 
ADD COLUMN temp_name VARCHAR(100),
ADD COLUMN temp_description TEXT;

UPDATE categories 
SET temp_name = name_ar, 
    temp_description = description_ar 
WHERE name_ar IS NOT NULL AND name_ar != '';

ALTER TABLE categories 
DROP COLUMN name_en,
DROP COLUMN description_en,
DROP COLUMN name_ar,
DROP COLUMN description_ar;

ALTER TABLE categories 
CHANGE temp_name name VARCHAR(100) NOT NULL,
CHANGE temp_description description TEXT;

-- تحديث جدول مقدمي الخدمة
ALTER TABLE service_providers 
ADD COLUMN temp_name VARCHAR(100),
ADD COLUMN temp_description TEXT;

UPDATE service_providers 
SET temp_name = name_ar, 
    temp_description = description_ar 
WHERE name_ar IS NOT NULL AND name_ar != '';

ALTER TABLE service_providers 
DROP COLUMN name_en,
DROP COLUMN description_en,
DROP COLUMN name_ar,
DROP COLUMN description_ar;

ALTER TABLE service_providers 
CHANGE temp_name name VARCHAR(100) NOT NULL,
CHANGE temp_description description TEXT;

-- تحديث جدول الخدمات
ALTER TABLE services 
ADD COLUMN temp_name VARCHAR(100),
ADD COLUMN temp_description TEXT;

UPDATE services 
SET temp_name = name_ar, 
    temp_description = description_ar 
WHERE name_ar IS NOT NULL AND name_ar != '';

ALTER TABLE services 
DROP COLUMN name_en,
DROP COLUMN description_en,
DROP COLUMN name_ar,
DROP COLUMN description_ar;

ALTER TABLE services 
CHANGE temp_name name VARCHAR(100) NOT NULL,
CHANGE temp_description description TEXT;

-- حذف جميع اللغات باستثناء العربية
DELETE FROM languages WHERE code != 'ar';

-- تعيين اللغة العربية كلغة افتراضية
UPDATE languages SET is_default = 1, is_active = 1 WHERE code = 'ar';

-- تحديث اللغة الافتراضية في الإعدادات (إذا كان الجدول موجودًا)
UPDATE settings SET setting_value = 'ar' WHERE setting_key = 'default_language';


