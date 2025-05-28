<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// الحصول على إحصائيات لوحة التحكم
$stats = [];

// عدد المستخدمين
$users_query = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($users_query);
$stats['users'] = $result->fetch_assoc()['count'];

// عدد مقدمي الخدمات
$providers_query = "SELECT COUNT(*) as count FROM service_providers";
$result = $conn->query($providers_query);
$stats['providers'] = $result->fetch_assoc()['count'];

// عدد الفئات
$categories_query = "SELECT COUNT(*) as count FROM categories";
$result = $conn->query($categories_query);
$stats['categories'] = $result->fetch_assoc()['count'];

// عدد الرسائل غير المقروءة
$messages_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
$result = $conn->query($messages_query);
$stats['unread_messages'] = $result->fetch_assoc()['count'];

// الحصول على المستخدمين الجدد
$recent_users_query = "SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($recent_users_query);
$recent_users = [];
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}

// الحصول على طلبات الخدمة الأخيرة
$recent_requests_query = "SELECT sr.id, sr.status, sr.created_at, 
                         s.name as service_title, u.username 
                         FROM service_requests sr 
                         JOIN services s ON sr.service_id = s.id 
                         JOIN users u ON sr.user_id = u.id 
                         ORDER BY sr.created_at DESC LIMIT 5";
$result = $conn->query($recent_requests_query);
$recent_requests = [];
while ($row = $result->fetch_assoc()) {
    $recent_requests[] = $row;
}

// عنوان الصفحة
$page_title = __('dashboard');

// تضمين الهيدر
include 'includes/header.php';
?>

<div class="admin-content-header">
    <div>
        <h1><?php echo __('admin_dashboard'); ?></h1>
        <div class="admin-breadcrumb">
            <a href="../index.php"><?php echo __('home'); ?></a>
            <span class="separator">/</span>
            <span><?php echo __('admin_dashboard'); ?></span>
        </div>
    </div>
    <div class="admin-header-actions">
        <a href="settings.php" class="admin-btn admin-btn-light">
            <i class="fas fa-cog"></i> <?php echo __('settings'); ?>
        </a>
        <a href="reports.php" class="admin-btn admin-btn-primary">
            <i class="fas fa-chart-line"></i> <?php echo __('reports'); ?>
        </a>
    </div>
</div>

<!-- Stats Summary Cards -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-header">
            <h3 class="admin-stat-title"><?php echo __('total_users'); ?></h3>
            <div class="admin-stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="admin-stat-value"><?php echo number_format($stats['users']); ?></div>
        <div class="admin-stat-description"><?php echo __('registered_users'); ?></div>
        <div class="admin-stat-footer">
            <a href="users.php" class="admin-stat-link"><?php echo __('view_all'); ?> <i class="fas fa-arrow-left"></i></a>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-header">
            <h3 class="admin-stat-title"><?php echo __('service_providers'); ?></h3>
            <div class="admin-stat-icon success">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>
        <div class="admin-stat-value"><?php echo number_format($stats['providers']); ?></div>
        <div class="admin-stat-description"><?php echo __('active_providers'); ?></div>
        <div class="admin-stat-footer">
            <a href="providers.php" class="admin-stat-link"><?php echo __('view_all'); ?> <i class="fas fa-arrow-left"></i></a>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-header">
            <h3 class="admin-stat-title"><?php echo __('categories'); ?></h3>
            <div class="admin-stat-icon warning">
                <i class="fas fa-th-large"></i>
            </div>
        </div>
        <div class="admin-stat-value"><?php echo number_format($stats['categories']); ?></div>
        <div class="admin-stat-description"><?php echo __('service_categories'); ?></div>
        <div class="admin-stat-footer">
            <a href="categories.php" class="admin-stat-link"><?php echo __('view_all'); ?> <i class="fas fa-arrow-left"></i></a>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-header">
            <h3 class="admin-stat-title"><?php echo __('unread_messages'); ?></h3>
            <div class="admin-stat-icon danger">
                <i class="fas fa-envelope"></i>
            </div>
        </div>
        <div class="admin-stat-value"><?php echo number_format($stats['unread_messages']); ?></div>
        <div class="admin-stat-description"><?php echo __('pending_messages'); ?></div>
        <div class="admin-stat-footer">
            <a href="view_message.php" class="admin-stat-link"><?php echo __('view_all'); ?> <i class="fas fa-arrow-left"></i></a>
        </div>
    </div>
</div>

<div class="admin-row">
    <!-- Recent Users -->
    <div class="admin-col-md-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><?php echo __('recent_users'); ?></h3>
                <div class="admin-card-header-actions">
                    <a href="users.php" class="admin-btn admin-btn-sm admin-btn-primary">
                        <i class="fas fa-users"></i> <?php echo __('view_all'); ?>
                    </a>
                </div>
            </div>
            <div class="admin-card-body">
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?php echo __('username'); ?></th>
                                <th><?php echo __('email'); ?></th>
                                <th><?php echo __('joined_date'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_users) > 0): ?>
                                <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="admin-btn admin-btn-sm admin-btn-info admin-btn-icon" data-tooltip="<?php echo __('edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_user.php?id=<?php echo $user['id']; ?>" class="admin-btn admin-btn-sm admin-btn-primary admin-btn-icon" data-tooltip="<?php echo __('view'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="admin-text-center"><?php echo __('no_users_found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Service Requests -->
    <div class="admin-col-md-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><?php echo __('recent_requests'); ?></h3>
                <div class="admin-card-header-actions">
                    <a href="requests.php" class="admin-btn admin-btn-sm admin-btn-primary">
                        <i class="fas fa-clipboard-list"></i> <?php echo __('view_all'); ?>
                    </a>
                </div>
            </div>
            <div class="admin-card-body">
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?php echo __('service'); ?></th>
                                <th><?php echo __('user'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('date'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_requests) > 0): ?>
                                <?php foreach ($recent_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['service_title']); ?></td>
                                        <td><?php echo htmlspecialchars($request['username']); ?></td>
                                        <td>
                                            <span class="admin-status-badge <?php echo strtolower($request['status']); ?>">
                                                <?php echo __($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($request['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="edit_request.php?id=<?php echo $request['id']; ?>" class="admin-btn admin-btn-sm admin-btn-info admin-btn-icon" data-tooltip="<?php echo __('edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_request.php?id=<?php echo $request['id']; ?>" class="admin-btn admin-btn-sm admin-btn-primary admin-btn-icon" data-tooltip="<?php echo __('view'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="admin-text-center"><?php echo __('no_requests_found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><?php echo __('quick_actions'); ?></h3>
    </div>
    <div class="admin-card-body">
        <div class="admin-quick-actions">
            <a href="add_user.php" class="admin-quick-action">
                <i class="fas fa-user-plus"></i>
                <span><?php echo __('add_user'); ?></span>
            </a>
            <a href="add_category.php" class="admin-quick-action">
                <i class="fas fa-folder-plus"></i>
                <span><?php echo __('add_category'); ?></span>
            </a>
            <a href="add_service.php" class="admin-quick-action">
                <i class="fas fa-plus-circle"></i>
                <span><?php echo __('add_service'); ?></span>
            </a>
            <a href="view_message.php" class="admin-quick-action">
                <i class="fas fa-envelope-open-text"></i>
                <span><?php echo __('view_messages'); ?></span>
            </a>
            <a href="settings.php" class="admin-quick-action">
                <i class="fas fa-cog"></i>
                <span><?php echo __('settings'); ?></span>
            </a>
            <a href="backup.php" class="admin-quick-action">
                <i class="fas fa-database"></i>
                <span><?php echo __('backup_data'); ?></span>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>






