<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// تحديد عنوان الصفحة
$page_title = __('manage_services');

// تضمين ملف الهيدر
include 'includes/header.php';

// استعلام لجلب الخدمات مع معلومات مزود الخدمة والفئة
$services_sql = "SELECT s.*, u.username as provider_name, c.name as category_name 
                FROM services s 
                JOIN users u ON s.provider_id = u.id 
                JOIN categories c ON s.category_id = c.id 
                ORDER BY s.created_at DESC";
$services_result = $conn->query($services_sql);
?>

<div class="admin-content-header">
    <h2><?php echo __('manage_services'); ?></h2>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo __('name'); ?></th>
                        <th><?php echo __('provider'); ?></th>
                        <th><?php echo __('category'); ?></th>
                        <th><?php echo __('price'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('created_at'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($services_result->num_rows > 0): ?>
                        <?php while ($service = $services_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $service['id']; ?></td>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td><?php echo htmlspecialchars($service['provider_name']); ?></td>
                                <td><?php echo htmlspecialchars($service['category_name']); ?></td>
                                <td><?php echo $service['price']; ?> <?php echo __('currency_symbol'); ?></td>
                                <td>
                                    <?php if ($service['is_active']): ?>
                                        <span class="badge badge-success"><?php echo __('active'); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?php echo __('inactive'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($service['created_at'])); ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="edit_service.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo __('edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_service.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-info" title="<?php echo __('view'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger delete-service" data-id="<?php echo $service['id']; ?>" title="<?php echo __('delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center"><?php echo __('no_services_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
