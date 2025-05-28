<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$page_title = __('system_maintenance');
include '../includes/admin_header.php';

// تنفيذ عمليات الصيانة
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // تحديد العملية المطلوبة
    $operation = $_POST['operation'] ?? '';
    
    switch ($operation) {
        case 'fix_dates':
            // تصحيح التواريخ المستقبلية
            $current_date = date('Y-m-d H:i:s');
            $affected_rows = 0;
            
            // تصحيح تواريخ الإنشاء المستقبلية
            $fix_created_dates = "UPDATE service_requests 
                                 SET created_at = ? 
                                 WHERE created_at > ?";
            $stmt = $conn->prepare($fix_created_dates);
            $stmt->bind_param("ss", $current_date, $current_date);
            
            if ($stmt->execute()) {
                $affected_rows += $stmt->affected_rows;
            } else {
                $error_message .= __('error_fixing_created_dates') . ": " . $conn->error . "<br>";
            }
            
            // تصحيح تواريخ التحديث المستقبلية
            $fix_updated_dates = "UPDATE service_requests 
                                 SET updated_at = created_at 
                                 WHERE updated_at > ?";
            $stmt = $conn->prepare($fix_updated_dates);
            $stmt->bind_param("s", $current_date);
            
            if ($stmt->execute()) {
                $affected_rows += $stmt->affected_rows;
            } else {
                $error_message .= __('error_fixing_updated_dates') . ": " . $conn->error . "<br>";
            }
            
            // تصحيح تواريخ الإكمال المستقبلية
            $fix_completed_dates = "UPDATE service_requests 
                                   SET completed_at = updated_at 
                                   WHERE status = 'completed' 
                                   AND completed_at > ?";
            $stmt = $conn->prepare($fix_completed_dates);
            $stmt->bind_param("s", $current_date);
            
            if ($stmt->execute()) {
                $affected_rows += $stmt->affected_rows;
            } else {
                $error_message .= __('error_fixing_completed_dates') . ": " . $conn->error . "<br>";
            }
            
            if (empty($error_message)) {
                $success_message = sprintf(__('fixed_dates_success'), $affected_rows);
            }
            break;
            
        case 'update_completed_dates':
            // تحديث حقل completed_at للطلبات المكتملة التي ليس لها قيمة
            $update_sql = "UPDATE service_requests 
                           SET completed_at = updated_at 
                           WHERE status = 'completed' AND completed_at IS NULL";
            
            if ($conn->query($update_sql) === TRUE) {
                $affected_rows = $conn->affected_rows;
                $success_message = sprintf(__('updated_completed_dates_success'), $affected_rows);
            } else {
                $error_message = __('error_updating_completed_dates') . ": " . $conn->error;
            }
            break;
            
        case 'optimize_tables':
            // تحسين جداول قاعدة البيانات
            $tables = ["users", "categories", "service_providers", "services", "service_requests", "languages", "contact_messages"];
            $optimized = 0;
            
            foreach ($tables as $table) {
                $optimize_query = "OPTIMIZE TABLE `$table`";
                if ($conn->query($optimize_query) === TRUE) {
                    $optimized++;
                } else {
                    $error_message .= sprintf(__('error_optimizing_table'), $table) . ": " . $conn->error . "<br>";
                }
            }
            
            if (empty($error_message)) {
                $success_message = sprintf(__('optimized_tables_success'), $optimized);
            }
            break;
            
        case 'sync_language_keys':
            // مزامنة مفاتيح اللغة
            // تحديد مسارات ملفات اللغة
            $ar_file = '../languages/ar.php';
            $en_file = '../languages/en.php';
            
            // التحقق من وجود الملفات
            if (!file_exists($ar_file) || !file_exists($en_file)) {
                $error_message = __('language_files_not_found');
                break;
            }
            
            // تحميل ملف اللغة العربية
            require_once $ar_file;
            $ar_keys = $lang;
            
            // إعادة تعيين متغير $lang قبل تحميل ملف اللغة الإنجليزية
            unset($lang);
            
            // تحميل ملف اللغة الإنجليزية
            require_once $en_file;
            $en_keys = $lang;
            
            // مزامنة المفاتيح بين الملفين
            $missing_in_en = array_diff_key($ar_keys, $en_keys);
            $missing_in_ar = array_diff_key($en_keys, $ar_keys);
            
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
            
            $success_message = sprintf(__('synced_language_keys_success'), count($missing_in_en), count($missing_in_ar));
            break;
    }
}
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><?php echo __('system_maintenance'); ?></h1>
            <nav class="admin-breadcrumb">
                <a href="dashboard.php"><?php echo __('dashboard'); ?></a> /
                <span><?php echo __('system_maintenance'); ?></span>
            </nav>
        </div>
        
        <?php if (!empty($success_message)): ?>
        <div class="admin-alert admin-alert-success">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="admin-alert admin-alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-row">
            <div class="admin-col-md-6">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><?php echo __('database_maintenance'); ?></h3>
                    </div>
                    <div class="admin-card-body">
                        <form method="post" class="admin-form">
                            <input type="hidden" name="operation" value="fix_dates">
                            <p><?php echo __('fix_dates_desc'); ?></p>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-calendar-check"></i> <?php echo __('fix_dates'); ?>
                            </button>
                        </form>
                        
                        <hr class="admin-divider">
                        
                        <form method="post" class="admin-form">
                            <input type="hidden" name="operation" value="update_completed_dates">
                            <p><?php echo __('update_completed_dates_desc'); ?></p>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-check-circle"></i> <?php echo __('update_completed_dates'); ?>
                            </button>
                        </form>
                        
                        <hr class="admin-divider">
                        
                        <form method="post" class="admin-form">
                            <input type="hidden" name="operation" value="optimize_tables">
                            <p><?php echo __('optimize_tables_desc'); ?></p>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-database"></i> <?php echo __('optimize_tables'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="admin-col-md-6">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><?php echo __('language_maintenance'); ?></h3>
                    </div>
                    <div class="admin-card-body">
                        <form method="post" class="admin-form">
                            <input type="hidden" name="operation" value="sync_language_keys">
                            <p><?php echo __('sync_language_keys_desc'); ?></p>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-language"></i> <?php echo __('sync_language_keys'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><?php echo __('system_info'); ?></h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-system-status">
                            <div class="admin-status-item">
                                <div class="admin-status-label"><?php echo __('php_version'); ?></div>
                                <div class="admin-status-value"><?php echo PHP_VERSION; ?></div>
                            </div>
                            <div class="admin-status-item">
                                <div class="admin-status-label"><?php echo __('mysql_version'); ?></div>
                                <div class="admin-status-value"><?php echo $conn->server_info; ?></div>
                            </div>
                            <div class="admin-status-item">
                                <div class="admin-status-label"><?php echo __('server_software'); ?></div>
                                <div class="admin-status-value"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
                            </div>
                            <div class="admin-status-item">
                                <div class="admin-status-label"><?php echo __('memory_usage'); ?></div>
                                <div class="admin-status-value">
                                    <?php 
                                    $memory = memory_get_usage() / 1024 / 1024;
                                    echo round($memory, 2) . ' MB'; 
                                    ?>
                                </div>
                            </div>
                            <div class="admin-status-item">
                                <div class="admin-status-label"><?php echo __('max_upload_size'); ?></div>
                                <div class="admin-status-value"><?php echo ini_get('upload_max_filesize'); ?></div>
                            </div>
                            <div class="admin-status-item">
                                <div class="admin-status-label"><?php echo __('max_execution_time'); ?></div>
                                <div class="admin-status-value"><?php echo ini_get('max_execution_time') . ' ' . __('seconds'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
