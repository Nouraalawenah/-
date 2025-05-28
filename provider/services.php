<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات مزود الخدمة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_provider']) {
    header("Location: ../login.php");
    exit;
}

$provider_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// معالجة حذف الخدمة
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $service_id = $_GET['delete'];
    
    // التحقق من أن الخدمة تنتمي لمزود الخدمة
    $check_sql = "SELECT id, image FROM services WHERE id = ? AND provider_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $service_id, $provider_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $service = $check_result->fetch_assoc();
        
        // حذف الخدمة من قاعدة البيانات
        $delete_sql = "DELETE FROM services WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $service_id);
        
        if ($delete_stmt->execute()) {
            // حذف صورة الخدمة إذا كانت موجودة
            if (!empty($service['image'])) {
                $image_path = "../images/" . $service['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            $success_message = __('service_deleted_successfully');
        } else {
            $error_message = __('error_deleting_service');
        }
    } else {
        $error_message = __('service_not_found');
    }
}

// جلب خدمات مزود الخدمة
$services_sql = "SELECT s.*, c.name as category_name 
                FROM services s 
                JOIN categories c ON s.category_id = c.id 
                WHERE s.provider_id = ? 
                ORDER BY s.created_at DESC";
$services_stmt = $conn->prepare($services_sql);
$services_stmt->bind_param("i", $provider_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'services';
$page_title = __('my_services');

// تضمين ملف الهيدر
include 'includes/header.php';
?>

<div class="provider-content">
    <div class="provider-content-header">
        <h1><?php echo __('my_services'); ?></h1>
        <div class="provider-content-actions">
            <a href="add_service.php" class="provider-btn provider-btn-primary">
                <i class="fas fa-plus"></i> <?php echo __('add_service'); ?>
            </a>
        </div>
    </div>
    
    <?php if ($success_message): ?>
        <div class="provider-alert provider-alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="provider-alert provider-alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="provider-card">
        <div class="provider-card-body">
            <?php if ($services_result->num_rows > 0): ?>
                <div class="provider-table-responsive">
                    <table class="provider-table">
                        <thead>
                            <tr>
                                <th><?php echo __('image'); ?></th>
                                <th><?php echo __('name'); ?></th>
                                <th><?php echo __('category'); ?></th>
                                <th><?php echo __('price'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($service = $services_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($service['image'])): ?>
                                            <img src="../images/<?php echo $service['image']; ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="provider-service-thumbnail">
                                        <?php else: ?>
                                            <div class="provider-service-thumbnail-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                                    <td><?php echo htmlspecialchars($service['category_name']); ?></td>
                                    <td><?php echo __('currency_symbol') . number_format($service['price'], 2); ?></td>
                                    <td>
                                        <?php if ($service['is_active']): ?>
                                            <span class="provider-badge provider-badge-success"><?php echo __('active'); ?></span>
                                        <?php else: ?>
                                            <span class="provider-badge provider-badge-danger"><?php echo __('inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_service.php?id=<?php echo $service['id']; ?>" class="provider-btn provider-btn-sm provider-btn-info">
                                            <i class="fas fa-edit"></i> <?php echo __('edit'); ?>
                                        </a>
                                        <a href="services.php?delete=<?php echo $service['id']; ?>" class="provider-btn provider-btn-sm provider-btn-danger" onclick="return confirm('<?php echo __('confirm_delete_service'); ?>')">
                                            <i class="fas fa-trash"></i> <?php echo __('delete'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="provider-empty-state">
                    <i class="fas fa-tools"></i>
                    <p><?php echo __('no_services_yet'); ?></p>
                    <a href="add_service.php" class="provider-btn provider-btn-primary">
                        <i class="fas fa-plus"></i> <?php echo __('add_first_service'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

