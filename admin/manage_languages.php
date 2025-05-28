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

// تفعيل/تعطيل لغة
if (isset($_GET['toggle_id']) && is_numeric($_GET['toggle_id'])) {
    $toggle_id = $_GET['toggle_id'];

    // التحقق من حالة اللغة الحالية
    $check_sql = "SELECT is_active, code, is_default FROM languages WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $toggle_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $lang_data = $check_result->fetch_assoc();
        $new_status = $lang_data['is_active'] ? 0 : 1;

        // لا يمكن تعطيل اللغة الافتراضية
        if ($lang_data['is_default'] && $new_status == 0) {
            $error = "لا يمكن تعطيل اللغة الافتراضية.";
        } else {
            $update_sql = "UPDATE languages SET is_active = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_status, $toggle_id);

            if ($update_stmt->execute()) {
                $status_text = $new_status ? "تفعيل" : "تعطيل";
                $success = "تم $status_text اللغة بنجاح.";
            } else {
                $error = "حدث خطأ أثناء تحديث حالة اللغة.";
            }
        }
    }
}

// تعيين اللغة الافتراضية
if (isset($_GET['set_default_id']) && is_numeric($_GET['set_default_id'])) {
    $default_id = $_GET['set_default_id'];

    // التحقق من أن اللغة نشطة
    $check_sql = "SELECT is_active FROM languages WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $default_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $lang_data = $check_result->fetch_assoc();

        if (!$lang_data['is_active']) {
            $error = "لا يمكن تعيين لغة غير نشطة كلغة افتراضية.";
        } else {
            // تحديث جميع اللغات لإزالة الإعداد الافتراضي
            $update_all_sql = "UPDATE languages SET is_default = 0";
            $conn->query($update_all_sql);

            // تعيين اللغة المحددة كافتراضية
            $update_default_sql = "UPDATE languages SET is_default = 1 WHERE id = ?";
            $update_default_stmt = $conn->prepare($update_default_sql);
            $update_default_stmt->bind_param("i", $default_id);

            if ($update_default_stmt->execute()) {
                $success = "تم تعيين اللغة كلغة افتراضية بنجاح.";

                // تحديث ملف التكوين
                $get_code_sql = "SELECT code FROM languages WHERE id = ?";
                $get_code_stmt = $conn->prepare($get_code_sql);
                $get_code_stmt->bind_param("i", $default_id);
                $get_code_stmt->execute();
                $get_code_result = $get_code_stmt->get_result();

                if ($get_code_result->num_rows > 0) {
                    $lang_code = $get_code_result->fetch_assoc()['code'];

                    // تحديث ملف التكوين
                    $config_file = "../config/language.php";
                    $config_content = file_get_contents($config_file);
                    $config_content = preg_replace('/\$default_language = \'[a-z]+\';/', "\$default_language = '$lang_code';", $config_content);
                    file_put_contents($config_file, $config_content);
                }
            } else {
                $error = "حدث خطأ أثناء تعيين اللغة الافتراضية.";
            }
        }
    }
}

// حذف لغة
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // التحقق من أن اللغة ليست العربية أو الإنجليزية
    $check_sql = "SELECT code FROM languages WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $lang_data = $check_result->fetch_assoc();

        if ($lang_data['code'] == 'ar' || $lang_data['code'] == 'en') {
            $error = "لا يمكن حذف اللغات الأساسية (العربية والإنجليزية).";
        } else {
            $delete_sql = "DELETE FROM languages WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $delete_id);

            if ($delete_stmt->execute()) {
                $success = "تم حذف اللغة بنجاح.";

                // حذف ملف اللغة إذا كان موجودًا
                $lang_file = "../languages/{$lang_data['code']}.php";
                if (file_exists($lang_file)) {
                    unlink($lang_file);
                }
            } else {
                $error = "حدث خطأ أثناء حذف اللغة.";
            }
        }
    }
}

// إضافة لغة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_language'])) {
    $code = $_POST['code'];
    $name = $_POST['name'];
    $is_rtl = isset($_POST['is_rtl']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // التحقق من عدم وجود اللغة بالفعل
    $check_sql = "SELECT id FROM languages WHERE code = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $code);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "رمز اللغة موجود بالفعل.";
    } else {
        // إضافة اللغة الجديدة
        $insert_sql = "INSERT INTO languages (code, name, is_rtl, is_active) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssii", $code, $name, $is_rtl, $is_active);

        if ($insert_stmt->execute()) {
            $success = "تمت إضافة اللغة بنجاح.";

            // إنشاء ملف اللغة إذا لم يكن موجودًا
            $lang_file_path = "../languages/$code.php";
            if (!file_exists($lang_file_path)) {
                // نسخ محتوى ملف اللغة الإنجليزية كقالب
                $template_content = file_get_contents("../languages/en.php");
                file_put_contents($lang_file_path, $template_content);
            }
        } else {
            $error = "حدث خطأ أثناء إضافة اللغة.";
        }
    }
}

// جلب جميع اللغات
$languages_sql = "SELECT * FROM languages ORDER BY is_default DESC, name";
$languages_result = $conn->query($languages_sql);
$languages = [];

if ($languages_result->num_rows > 0) {
    while ($row = $languages_result->fetch_assoc()) {
        $languages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة اللغات - لوحة التحكم</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="container">
        <div class="admin-header">
            <h1>إدارة اللغات</h1>
            <div class="admin-actions">
                <a href="check_translation_registry.php" class="btn">فحص سجل الترجمة</a>
                <a href="sync_language_keys.php" class="btn">مزامنة مفاتيح اللغة</a>
                <a href="translation_checker.php" class="btn btn-info">فحص وإصلاح الترجمات</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-form">
            <h2>إضافة لغة جديدة</h2>

            <form method="POST">
                <div class="form-group">
                    <label for="code">رمز اللغة (مثال: fr):</label>
                    <input type="text" id="code" name="code" required pattern="[a-z]{2}" maxlength="2">
                    <small>يجب أن يكون رمز اللغة حرفين صغيرين فقط (مثل: ar, en, fr)</small>
                </div>

                <div class="form-group">
                    <label for="name">اسم اللغة:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_rtl"> اتجاه النص من اليمين إلى اليسار (RTL)
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_active" checked> نشطة
                    </label>
                </div>

                <button type="submit" name="add_language" class="btn">إضافة اللغة</button>
            </form>
        </div>

        <div class="admin-table">
            <h2>اللغات المتاحة</h2>

            <table>
                <thead>
                    <tr>
                        <th>الرمز</th>
                        <th>الاسم</th>
                        <th>RTL</th>
                        <th>الحالة</th>
                        <th>افتراضية</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languages as $language): ?>
                    <tr>
                        <td><?php echo $language['code']; ?></td>
                        <td><?php echo $language['name']; ?></td>
                        <td><?php echo $language['is_rtl'] ? 'نعم' : 'لا'; ?></td>
                        <td>
                            <span class="status-badge <?php echo $language['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $language['is_active'] ? 'نشطة' : 'غير نشطة'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($language['is_default']): ?>
                                <span class="status-badge default">افتراضية</span>
                            <?php else: ?>
                                <span class="status-badge">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="edit_language.php?id=<?php echo $language['id']; ?>" class="btn-small">تعديل</a>

                            <?php if (!$language['is_default']): ?>
                                <a href="?toggle_id=<?php echo $language['id']; ?>" class="btn-small <?php echo $language['is_active'] ? 'warning' : 'success'; ?>">
                                    <?php echo $language['is_active'] ? 'تعطيل' : 'تفعيل'; ?>
                                </a>

                                <?php if ($language['is_active']): ?>
                                    <a href="?set_default_id=<?php echo $language['id']; ?>" class="btn-small primary">
                                        تعيين كافتراضية
                                    </a>
                                <?php endif; ?>

                                <?php if ($language['code'] != 'ar' && $language['code'] != 'en'): ?>
                                    <a href="?delete_id=<?php echo $language['id']; ?>" class="btn-small danger" onclick="return confirm('هل أنت متأكد من حذف هذه اللغة؟');">
                                        حذف
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>








