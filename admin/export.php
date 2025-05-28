<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// التحقق من وجود نوع التصدير
if (!isset($_GET['type'])) {
    header("Location: statistics.php");
    exit;
}

$type = $_GET['type'];
$allowed_types = ['users', 'providers', 'services', 'requests', 'categories', 'messages'];

if (!in_array($type, $allowed_types)) {
    header("Location: statistics.php");
    exit;
}

// تحديد عمود اللغة المناسب
$lang_suffix = $_SESSION['lang'] == 'ar' ? 'ar' : 'en';
$name_column = "name_" . $lang_suffix;
$desc_column = "description_" . $lang_suffix;

// تعيين اسم الملف وترويسة CSV
$filename = $type . '_' . date('Y-m-d') . '.csv';
$csv_headers = [];
$query = "";

switch ($type) {
    case 'users':
        $csv_headers = ['ID', 'Username', 'Email', 'Phone', 'Is Admin', 'Is Provider', 'Created At'];
        $query = "SELECT id, username, email, phone, is_admin, is_provider, created_at FROM users";
        break;
        
    case 'providers':
        $csv_headers = ['ID', 'User ID', 'Name', 'Description', 'Category', 'Address', 'Phone', 'Rating', 'Created At'];
        $query = "SELECT p.id, p.user_id, p.{$name_column} as name, p.{$desc_column} as description, 
                 c.{$name_column} as category, p.address, p.phone, p.rating, p.created_at 
                 FROM service_providers p 
                 LEFT JOIN categories c ON p.category_id = c.id";
        break;
        
    case 'services':
        $csv_headers = ['ID', 'Provider ID', 'Category ID', 'Name', 'Description', 'Price', 'Duration', 'Created At'];
        $query = "SELECT id, provider_id, category_id, {$name_column} as name, {$desc_column} as description, 
                 price, duration, created_at FROM services";
        break;
        
    case 'requests':
        $csv_headers = ['ID', 'User ID', 'Service ID', 'Status', 'Message', 'Notes', 'Scheduled Date', 'Rating', 'Created At', 'Updated At', 'Completed At'];
        $query = "SELECT id, user_id, service_id, status, message, notes, scheduled_date, rating, created_at, updated_at, completed_at 
                 FROM service_requests";
        break;
        
    case 'categories':
        $csv_headers = ['ID', 'Name', 'Description', 'Icon', 'Created At'];
        $query = "SELECT id, {$name_column} as name, {$desc_column} as description, icon, created_at FROM categories";
        break;
        
    case 'messages':
        $csv_headers = ['ID', 'Name', 'Email', 'Subject', 'Message', 'Is Read', 'Created At'];
        $query = "SELECT id, name, email, subject, message, is_read, created_at FROM contact_messages";
        break;
}

// تنفيذ الاستعلام
$result = $conn->query($query);

// إعداد ملف CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// إنشاء مخرج CSV
$output = fopen('php://output', 'w');

// إضافة BOM للتعامل مع الأحرف العربية
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// كتابة ترويسة CSV
fputcsv($output, $csv_headers);

// كتابة البيانات
while ($row = $result->fetch_assoc()) {
    // تنظيف البيانات
    foreach ($row as $key => $value) {
        // إزالة علامات التبويب والأسطر الجديدة
        $row[$key] = str_replace(["\r", "\n", "\t"], ' ', $value);
    }
    
    fputcsv($output, $row);
}

fclose($output);
exit;
