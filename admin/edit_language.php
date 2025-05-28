<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// التحقق من وجود معرف اللغة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_languages.php");
    exit;
}

$language_id = $_GET['id'];

// جلب معلومات اللغة
$lang_sql = "SELECT * FROM languages WHERE id = ?";
$lang_stmt = $conn->prepare($lang_sql);
$lang_stmt->bind_param("i", $language_id);
$lang_stmt->execute();
$lang_result = $lang_stmt->get_result();

if ($lang_result->num_rows == 0) {
    header("Location: manage_languages.php");
    exit;
}

$language = $lang_result->fetch_assoc();

// التحقق من وجود ملف اللغة
$lang_file_path = "../languages/{$language['code']}.php";
if (!file_exists($lang_file_path)) {
    // إنشاء ملف اللغة إذا لم يكن موجودًا
    $template_content = file_get_contents("../languages/en.php");
    file_put_contents($lang_file_path, $template_content);
}

// تحميل ملف الترجمة
require_once $lang_file_path;
$translations = $lang;

// تحديث بيانات اللغة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_language'])) {
    $name = $_POST['name'];
    $is_rtl = isset($_POST['is_rtl']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // تحديث بيانات اللغة في قاعدة البيانات
    $update_sql = "UPDATE languages SET name = ?, is_rtl = ?, is_active = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("siii", $name, $is_rtl, $is_active, $language_id);
    
    if ($update_stmt->execute()) {
        $success = "تم تحديث بيانات اللغة بنجاح.";
        
        // تحديث بيانات اللغة المعروضة
        $language['name'] = $name;
        $language['is_rtl'] = $is_rtl;
        $language['is_active'] = $is_active;
    } else {
        $error = "حدث خطأ أثناء تحديث بيانات اللغة.";
    }
}

// تحديث ملف الترجمة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_translations'])) {
    $new_translations = $_POST['translations'];
    
    // إنشاء محتوى ملف الترجمة الجديد
    $file_content = "<?php\n";
    $file_content .= "\$lang = [\n";
    
    foreach ($new_translations as $key => $value) {
        $value = str_replace('"', '\"', $value);
        $file_content .= "    '$key' => \"$value\",\n";
    }
    
    $file_content .= "];\n";
    $file_content .= "?>";
    
    // حفظ الملف
    if (file_put_contents($lang_file_path, $file_content)) {
        $success = "تم تحديث ملف الترجمة بنجاح.";
        $translations = $new_translations;
    } else {
        $error = "حدث خطأ أثناء تحديث ملف الترجمة.";
    }
}
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل اللغة - لوحة التحكم</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container">
        <h1>تعديل اللغة: <?php echo $language['name']; ?> (<?php echo $language['code']; ?>)</h1>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admin-form">
            <h2>معلومات اللغة</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="code">رمز اللغة:</label>
                    <input type="text" id="code" value="<?php echo $language['code']; ?>" readonly>
                    <small>لا يمكن تغيير رمز اللغة</small>
                </div>
                
                <div class="form-group">
                    <label for="name">اسم اللغة:</label>
                    <input type="text" id="name" name="name" value="<?php echo $language['name']; ?>" required>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_rtl" <?php echo $language['is_rtl'] ? 'checked' : ''; ?>> اتجاه النص من اليمين إلى اليسار (RTL)
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_active" <?php echo $language['is_active'] ? 'checked' : ''; ?> <?php echo $language['code'] == 'ar' ? 'disabled' : ''; ?>> نشطة
                    </label>
                    <?php if ($language['code'] == 'ar'): ?>
                        <small>لا يمكن تعطيل اللغة العربية لأنها اللغة الافتراضية</small>
                    <?php endif; ?>
                </div>
                
                <button type="submit" name="update_language" class="btn">تحديث معلومات اللغة</button>
            </form>
        </div>
        
        <div class="admin-form">
            <h2>ترجمات اللغة</h2>
            
            <form method="POST">
                <div class="translations-container">
                    <?php foreach ($translations as $key => $value): ?>
                    <div class="form-group">
                        <label for="trans_<?php echo $key; ?>"><?php echo $key; ?>:</label>
                        <input type="text" id="trans_<?php echo $key; ?>" name="translations[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" name="update_translations" class="btn">تحديث الترجمات</button>
            </form>
        </div>
        
        <div class="back-link">
            <a href="manage_languages.php">العودة إلى إدارة اللغات</a>
        </div>
    </div>
    
    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>
