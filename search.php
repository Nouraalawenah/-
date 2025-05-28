<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$results = [];

if (!empty($search_query) || $category_id > 0) {
    // بناء استعلام البحث
    $sql = "SELECT sp.id, sp.name, sp.description, sp.image, sp.address, sp.rating, 
           c.name AS category_name, c.id AS category_id
           FROM service_providers sp
           JOIN categories c ON sp.category_id = c.id
           WHERE 1=1";
    
    $params = [];
    $param_types = "";
    
    if (!empty($search_query)) {
        $sql .= " AND (sp.name LIKE ? OR sp.description LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= "ss";
    }
    
    if ($category_id > 0) {
        $sql .= " AND sp.category_id = ?";
        $params[] = $category_id;
        $param_types .= "i";
    }
    
    $sql .= " ORDER BY sp.rating DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }
}

// استعلام لجلب جميع الفئات للتصفية
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

