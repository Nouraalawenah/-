<?php
session_start();
require_once '../config/db_connect.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// تحديد المجلدات المطلوبة
$required_dirs = [
    '../images/',
    '../images/users/',
    '../images/providers/',
    '../images/categories/',
    '../images/flags/'
];

$results = [];

// إنشاء المجلدات إذا لم تكن موجودة
foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            $results[] = "تم إنشاء المجلد: " . $dir;
        } else {
            $results[] = "فشل في إنشاء المجلد: " . $dir;
        }
    } else {
        $results[] = "المجلد موجود بالفعل: " . $dir;
    }
    
    // التحقق من صلاحيات الكتابة
    if (is_writable($dir)) {
        $results[] = "المجلد قابل للكتابة: " . $dir;
    } else {
        $results[] = "تحذير: المجلد غير قابل للكتابة: " . $dir;
        // محاولة تعديل الصلاحيات
        if (chmod($dir, 0777)) {
            $results[] = "تم تعديل صلاحيات المجلد: " . $dir;
        } else {
            $results[] = "فشل في تعديل صلاحيات المجلد: " . $dir;
        }
    }
}

// إنشاء صور افتراضية إذا لم تكن موجودة
$default_images = [
    '../images/users/default-avatar.jpg' => 'https://via.placeholder.com/150x150.jpg?text=User',
    '../images/providers/anonymous.jpg' => 'https://via.placeholder.com/200x200.jpg?text=Provider'
];

foreach ($default_images as $path => $url) {
    if (!file_exists($path)) {
        $image_data = @file_get_contents($url);
        if ($image_data !== false) {
            if (file_put_contents($path, $image_data)) {
                $results[] = "تم إنشاء الصورة الافتراضية: " . $path;
            } else {
                $results[] = "فشل في إنشاء الصورة الافتراضية: " . $path;
            }
        } else {
            $results[] = "فشل في تحميل الصورة الافتراضية من: " . $url;
        }
    } else {
        $results[] = "الصورة الافتراضية موجودة بالفعل: " . $path;
    }
}

?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء مجلدات الصور</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .results-container {
            margin: 20px 0;
        }
        .result-item {
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .result-success {
            background-color: #d4edda;
            color: #155724;
        }
        .result-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .result-error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>إنشاء مجلدات الصور</h2>
            </div>
            <div class="admin-card-body">
                <div class="results-container">
                    <?php foreach ($results as $result): ?>
                        <?php 
                        $class = 'result-success';
                        if (strpos($result, 'فشل') !== false) {
                            $class = 'result-error';
                        } elseif (strpos($result, 'تحذير') !== false) {
                            $class = 'result-warning';
                        }
                        ?>
                        <div class="result-item <?php echo $class; ?>">
                            <?php echo $result; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="admin-form-actions">
                    <a href="dashboard.php" class="admin-btn admin-btn-primary">العودة إلى لوحة التحكم</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>