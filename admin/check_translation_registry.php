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

// دالة لمقارنة مفاتيح اللغات
function compareLanguageKeys($lang1, $lang2) {
    $keys1 = array_keys($lang1);
    $keys2 = array_keys($lang2);

    return [
        'missing_in_lang2' => array_diff($keys1, $keys2),
        'missing_in_lang1' => array_diff($keys2, $keys1),
        'common_keys' => array_intersect($keys1, $keys2)
    ];
}

// بدء التحليل
$report = [
    'database_status' => 'unknown',
    'languages_in_db' => [],
    'language_files' => [],
    'file_analysis' => [],
    'key_comparison' => [],
    'recommendations' => []
];

// فحص قاعدة البيانات
try {
    $result = $conn->query("SHOW TABLES LIKE 'languages'");
    if ($result->num_rows > 0) {
        $report['database_status'] = 'exists';

        // جلب اللغات من قاعدة البيانات
        $lang_result = $conn->query("SELECT * FROM languages ORDER BY is_default DESC, code");
        while ($row = $lang_result->fetch_assoc()) {
            $report['languages_in_db'][] = $row;
        }
    } else {
        $report['database_status'] = 'missing';
        $report['recommendations'][] = 'جدول اللغات غير موجود في قاعدة البيانات. يرجى تشغيل setup.php';
    }
} catch (Exception $e) {
    $report['database_status'] = 'error';
    $report['recommendations'][] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// فحص ملفات اللغة
$languages_dir = '../languages/';
$language_files = glob($languages_dir . '*.php');

foreach ($language_files as $file) {
    $filename = basename($file);
    $lang_code = str_replace('.php', '', $filename);

    $file_info = [
        'code' => $lang_code,
        'file' => $filename,
        'path' => $file,
        'exists' => file_exists($file),
        'readable' => is_readable($file),
        'size' => file_exists($file) ? filesize($file) : 0,
        'modified' => file_exists($file) ? date('Y-m-d H:i:s', filemtime($file)) : null,
        'keys_count' => 0,
        'has_required_keys' => false
    ];

    if ($file_info['exists'] && $file_info['readable']) {
        $lang_data = parseLanguageFile($file);
        if ($lang_data !== false) {
            $file_info['keys_count'] = count($lang_data);
            $file_info['has_required_keys'] = isset($lang_data['dir']) && isset($lang_data['lang_code']);
            $file_info['lang_data'] = $lang_data;
        }
    }

    $report['language_files'][$lang_code] = $file_info;
}

// مقارنة مفاتيح اللغات
if (isset($report['language_files']['ar']) && isset($report['language_files']['en'])) {
    $ar_data = $report['language_files']['ar']['lang_data'] ?? [];
    $en_data = $report['language_files']['en']['lang_data'] ?? [];

    if (!empty($ar_data) && !empty($en_data)) {
        $comparison = compareLanguageKeys($ar_data, $en_data);
        $report['key_comparison'] = $comparison;

        if (!empty($comparison['missing_in_lang2'])) {
            $report['recommendations'][] = 'يوجد ' . count($comparison['missing_in_lang2']) . ' مفتاح مفقود في ملف اللغة الإنجليزية';
        }

        if (!empty($comparison['missing_in_lang1'])) {
            $report['recommendations'][] = 'يوجد ' . count($comparison['missing_in_lang1']) . ' مفتاح مفقود في ملف اللغة العربية';
        }
    }
}

// فحص تطابق اللغات في قاعدة البيانات مع الملفات
foreach ($report['languages_in_db'] as $db_lang) {
    $code = $db_lang['code'];
    if (!isset($report['language_files'][$code])) {
        $report['recommendations'][] = "ملف اللغة $code.php غير موجود رغم وجود اللغة في قاعدة البيانات";
    }
}

foreach ($report['language_files'] as $code => $file_info) {
    $found_in_db = false;
    foreach ($report['languages_in_db'] as $db_lang) {
        if ($db_lang['code'] === $code) {
            $found_in_db = true;
            break;
        }
    }
    if (!$found_in_db) {
        $report['recommendations'][] = "ملف اللغة $code.php موجود لكن اللغة غير مسجلة في قاعدة البيانات";
    }
}

// تنظيف الملف المؤقت
if (file_exists('check_db.php')) {
    unlink('check_db.php');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فحص سجل الترجمة</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .report-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .status-good { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .status-warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .status-error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .status-info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .key-list { max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007cba; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: right; border: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .file-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .file-exists { background-color: #d4edda; color: #155724; }
        .file-missing { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="container">
        <div class="admin-header">
            <h1>تقرير فحص سجل الترجمة</h1>
            <div class="admin-actions">
                <a href="manage_languages.php" class="btn">إدارة اللغات</a>
                <a href="sync_language_keys.php" class="btn">مزامنة المفاتيح</a>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($report['languages_in_db']); ?></div>
                <div>لغات في قاعدة البيانات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($report['language_files']); ?></div>
                <div>ملفات اللغة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($report['recommendations']); ?></div>
                <div>توصيات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $report['database_status'] === 'exists' ? '✓' : '✗'; ?></div>
                <div>حالة قاعدة البيانات</div>
            </div>
        </div>

        <!-- حالة قاعدة البيانات -->
        <div class="report-section <?php echo $report['database_status'] === 'exists' ? 'status-good' : 'status-error'; ?>">
            <h2>حالة قاعدة البيانات</h2>
            <?php if ($report['database_status'] === 'exists'): ?>
                <p>✓ جدول اللغات موجود ويعمل بشكل صحيح</p>
                <table>
                    <thead>
                        <tr>
                            <th>الرمز</th>
                            <th>الاسم</th>
                            <th>RTL</th>
                            <th>نشطة</th>
                            <th>افتراضية</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['languages_in_db'] as $lang): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lang['code']); ?></td>
                            <td><?php echo htmlspecialchars($lang['name']); ?></td>
                            <td><?php echo $lang['is_rtl'] ? 'نعم' : 'لا'; ?></td>
                            <td><?php echo $lang['is_active'] ? 'نعم' : 'لا'; ?></td>
                            <td><?php echo $lang['is_default'] ? 'نعم' : 'لا'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>✗ مشكلة في جدول اللغات: <?php echo $report['database_status']; ?></p>
            <?php endif; ?>
        </div>

        <!-- حالة ملفات اللغة -->
        <div class="report-section status-info">
            <h2>حالة ملفات اللغة</h2>
            <table>
                <thead>
                    <tr>
                        <th>رمز اللغة</th>
                        <th>اسم الملف</th>
                        <th>الحالة</th>
                        <th>عدد المفاتيح</th>
                        <th>حجم الملف</th>
                        <th>آخر تعديل</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['language_files'] as $code => $file): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($code); ?></td>
                        <td><?php echo htmlspecialchars($file['file']); ?></td>
                        <td>
                            <span class="file-status <?php echo $file['exists'] && $file['readable'] ? 'file-exists' : 'file-missing'; ?>">
                                <?php echo $file['exists'] && $file['readable'] ? 'موجود' : 'مفقود/غير قابل للقراءة'; ?>
                            </span>
                        </td>
                        <td><?php echo $file['keys_count']; ?></td>
                        <td><?php echo $file['size'] > 0 ? number_format($file['size']) . ' بايت' : '-'; ?></td>
                        <td><?php echo $file['modified'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- مقارنة مفاتيح اللغات -->
        <?php if (!empty($report['key_comparison'])): ?>
        <div class="report-section <?php echo empty($report['key_comparison']['missing_in_lang1']) && empty($report['key_comparison']['missing_in_lang2']) ? 'status-good' : 'status-warning'; ?>">
            <h2>مقارنة مفاتيح اللغات (العربية vs الإنجليزية)</h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($report['key_comparison']['common_keys']); ?></div>
                    <div>مفاتيح مشتركة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($report['key_comparison']['missing_in_lang2']); ?></div>
                    <div>مفقودة في الإنجليزية</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($report['key_comparison']['missing_in_lang1']); ?></div>
                    <div>مفقودة في العربية</div>
                </div>
            </div>

            <?php if (!empty($report['key_comparison']['missing_in_lang2'])): ?>
            <h3>مفاتيح مفقودة في ملف اللغة الإنجليزية:</h3>
            <div class="key-list">
                <?php foreach ($report['key_comparison']['missing_in_lang2'] as $key): ?>
                    <div>• <?php echo htmlspecialchars($key); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($report['key_comparison']['missing_in_lang1'])): ?>
            <h3>مفاتيح مفقودة في ملف اللغة العربية:</h3>
            <div class="key-list">
                <?php foreach ($report['key_comparison']['missing_in_lang1'] as $key): ?>
                    <div>• <?php echo htmlspecialchars($key); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- التوصيات -->
        <?php if (!empty($report['recommendations'])): ?>
        <div class="report-section status-warning">
            <h2>التوصيات والإجراءات المطلوبة</h2>
            <ul>
                <?php foreach ($report['recommendations'] as $recommendation): ?>
                    <li><?php echo htmlspecialchars($recommendation); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
        <div class="report-section status-good">
            <h2>✓ نظام الترجمة يعمل بشكل مثالي</h2>
            <p>لا توجد مشاكل أو توصيات في الوقت الحالي.</p>
        </div>
        <?php endif; ?>

        <!-- اختبار وظائف الترجمة -->
        <div class="report-section status-info">
            <h2>اختبار وظائف الترجمة</h2>
            <p><strong>اللغة الحالية:</strong> <?php echo $_SESSION['lang'] ?? 'غير محددة'; ?></p>
            <p><strong>اختبار دالة __():</strong> <?php echo __('site_name'); ?></p>
            <p><strong>اتجاه الصفحة:</strong> <?php echo $page_direction ?? 'غير محدد'; ?></p>

            <h3>اختبار مفاتيح أساسية:</h3>
            <table>
                <thead>
                    <tr>
                        <th>المفتاح</th>
                        <th>القيمة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $test_keys = ['site_name', 'home', 'about', 'contact', 'login', 'register'];
                    foreach ($test_keys as $key):
                        $value = __($key);
                        $status = ($value !== $key) ? 'موجود' : 'مفقود';
                        $status_class = ($value !== $key) ? 'file-exists' : 'file-missing';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td><?php echo htmlspecialchars($value); ?></td>
                        <td><span class="file-status <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-actions" style="margin-top: 30px;">
            <a href="manage_languages.php" class="btn">العودة إلى إدارة اللغات</a>
            <a href="sync_language_keys.php" class="btn">مزامنة مفاتيح اللغة</a>
            <a href="dashboard.php" class="btn">لوحة التحكم</a>
        </div>
    </div>

    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>


