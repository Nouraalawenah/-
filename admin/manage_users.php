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
$page_title = __('manage_users');

// تضمين ملف الهيدر
include 'includes/header.php';

// استعلام لجلب المستخدمين
$users_sql = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_sql);
?>

<div class="admin-content-header">
    <h2><?php echo __('manage_users'); ?></h2>
    <div class="admin-content-header-actions">
        <a href="add_user.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo __('add_user'); ?>
        </a>
    </div>
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
                        <th><?php echo __('role'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('created_at'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result->num_rows > 0): ?>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="badge badge-primary"><?php echo __('admin'); ?></span>
                                    <?php elseif ($user['is_provider']): ?>
                                        <span class="badge badge-success"><?php echo __('provider'); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?php echo __('user'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge badge-success"><?php echo __('active'); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?php echo __('inactive'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo __('edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="<?php echo __('view'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger delete-user" data-id="<?php echo $user['id']; ?>" title="<?php echo __('delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center"><?php echo __('no_users_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
