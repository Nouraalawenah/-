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
$page_title = __('manage_providers');

// تضمين ملف الهيدر
include 'includes/header.php';

// استعلام لجلب مزودي الخدمة
$providers_sql = "SELECT u.*, p.bio, p.phone, p.address, p.image as provider_image 
                 FROM users u 
                 LEFT JOIN providers p ON u.id = p.user_id 
                 WHERE u.is_provider = 1 
                 ORDER BY u.created_at DESC";
$providers_result = $conn->query($providers_sql);
?>

<div class="admin-content-header">
    <h2><?php echo __('manage_providers'); ?></h2>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo __('username'); ?></th>
                        <th><?php echo __('email'); ?></th>
                        <th><?php echo __('phone'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('services_count'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($providers_result->num_rows > 0): ?>
                        <?php while ($provider = $providers_result->fetch_assoc()): ?>
                            <?php
                            // استعلام لحساب عدد الخدمات لكل مزود
                            $services_count_sql = "SELECT COUNT(*) as count FROM services WHERE provider_id = ?";
                            $services_count_stmt = $conn->prepare($services_count_sql);
                            $services_count_stmt->bind_param("i", $provider['id']);
                            $services_count_stmt->execute();
                            $services_count_result = $services_count_stmt->get_result();
                            $services_count = $services_count_result->fetch_assoc()['count'];
                            ?>
                            <tr>
                                <td><?php echo $provider['id']; ?></td>
                                <td>
                                    <?php if (!empty($provider['provider_image'])): ?>
                                        <img src="../images/<?php echo $provider['provider_image']; ?>" alt="<?php echo htmlspecialchars($provider['username']); ?>" class="admin-avatar-small">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($provider['username']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($provider['email']); ?></td>
                                <td><?php echo !empty($provider['phone']) ? htmlspecialchars($provider['phone']) : '-'; ?></td>
                                <td>
                                    <?php if ($provider['is_active']): ?>
                                        <span class="badge badge-success"><?php echo __('active'); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?php echo __('inactive'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $services_count; ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="edit_provider.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo __('edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_provider.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-info" title="<?php echo __('view'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="provider_services.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-success" title="<?php echo __('services'); ?>">
                                            <i class="fas fa-concierge-bell"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center"><?php echo __('no_providers_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>





