<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// دالة لتحليل ملف اللغة
function parseLanguageFile($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }

    // تحميل ملف اللغة
    $lang = [];
    include $file_path;
    return $lang;
}

// تحميل ملفات اللغة
$ar_file = "../languages/ar.php";
$en_file = "../languages/en.php";

$ar_lang = parseLanguageFile($ar_file);
$en_lang = parseLanguageFile($en_file);

$issues = [];

// التحقق من وجود الملفات
if (!$ar_lang || !$en_lang) {
    $issues[] = [
        'type' => 'error',
        'message' => 'أحد ملفات اللغة غير موجود'
    ];
}

// البحث عن المفاتيح المفقودة
$missing_in_en = array_diff_key($ar_lang, $en_lang);
$missing_in_ar = array_diff_key($en_lang, $ar_lang);

// التحقق من المفاتيح المتطابقة (قد تكون غير مترجمة)
$identical_translations = [];
foreach ($ar_lang as $key => $value) {
    if (isset($en_lang[$key]) && $value === $en_lang[$key] && strlen($value) > 3 && !in_array($key, ['dir', 'lang_code'])) {
        $identical_translations[$key] = $value;
    }
}

// إصلاح المشاكل إذا تم طلب ذلك
if (isset($_POST['fix_issues'])) {
    // إضافة المفاتيح المفقودة في الإنجليزية
    if (!empty($missing_in_en)) {
        $en_content = file_get_contents($en_file);
        $insertion_point = strrpos($en_content, '];');
        
        $new_keys = '';
        foreach ($missing_in_en as $key => $value) {
            $value = str_replace('"', '\"', $value);
            $new_keys .= "    \"$key\" => \"$value\",\n";
        }
        
        $en_content = substr_replace($en_content, $new_keys, $insertion_point, 0);
        file_put_contents($en_file, $en_content);
    }
    
    // إضافة المفاتيح المفقودة في العربية
    if (!empty($missing_in_ar)) {
        $ar_content = file_get_contents($ar_file);
        $insertion_point = strrpos($ar_content, '];');
        
        $new_keys = '';
        foreach ($missing_in_ar as $key => $value) {
            $value = str_replace('"', '\"', $value);
            $new_keys .= "    \"$key\" => \"$value\",\n";
        }
        
        $ar_content = substr_replace($ar_content, $new_keys, $insertion_point, 0);
        file_put_contents($ar_file, $ar_content);
    }
    
    // إعادة تحميل الصفحة لتحديث النتائج
    header("Location: translation_checker.php?fixed=1");
    exit;
}

// عرض رسالة النجاح
$success_message = '';
if (isset($_GET['fixed']) && $_GET['fixed'] == 1) {
    $success_message = 'تم إصلاح مشاكل الترجمة بنجاح';
}

// عنوان الصفحة
$page_title = "فحص الترجمات";
include '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>فحص وإصلاح الترجمات</h1>
    
    <?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h2>ملخص الفحص</h2>
        </div>
        <div class="card-body">
            <ul>
                <li>عدد المفاتيح في ملف اللغة العربية: <?php echo count($ar_lang); ?></li>
                <li>عدد المفاتيح في ملف اللغة الإنجليزية: <?php echo count($en_lang); ?></li>
                <li>المفاتيح المفقودة في الإنجليزية: <?php echo count($missing_in_en); ?></li>
                <li>المفاتيح المفقودة في العربية: <?php echo count($missing_in_ar); ?></li>
                <li>الترجمات المتطابقة (قد تكون غير مترجمة): <?php echo count($identical_translations); ?></li>
            </ul>
            
            <?php if (count($missing_in_en) > 0 || count($missing_in_ar) > 0): ?>
            <form method="post">
                <button type="submit" name="fix_issues" class="btn btn-primary">إصلاح المشاكل تلقائيًا</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (count($missing_in_en) > 0): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h3>المفاتيح المفقودة في ملف اللغة الإنجليزية</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>المفتاح</th>
                        <th>القيمة العربية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missing_in_en as $key => $value): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($key); ?></code></td>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (count($missing_in_ar) > 0): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h3>المفاتيح المفقودة في ملف اللغة العربية</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>المفتاح</th>
                        <th>القيمة الإنجليزية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missing_in_ar as $key => $value): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($key); ?></code></td>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (count($identical_translations) > 0): ?>
    <div class="card mb-4">
        <div class="card-header bg-info">
            <h3>الترجمات المتطابقة (قد تحتاج إلى مراجعة)</h3>
        </div>
        <div class="card-body">
            <p>هذه المفاتيح لها نفس القيمة في كلا اللغتين، مما قد يشير إلى أنها لم تترجم بشكل صحيح:</p>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>المفتاح</th>
                        <th>القيمة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($identical_translations as $key => $value): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($key); ?></code></td>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <a href="manage_languages.php" class="btn btn-secondary">العودة إلى إدارة اللغات</a>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
