<?php
require_once 'includes/auth_check.php';

// حذف رسالة
$delete_success = '';
$delete_error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $delete_sql = "DELETE FROM contact_messages WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        $delete_success = __('message_deleted_successfully');
    } else {
        $delete_error = __('error_deleting_message');
    }
}

// تحديد الصفحة الحالية للتصفح
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// جلب إجمالي عدد الرسائل
$count_sql = "SELECT COUNT(*) as total FROM contact_messages";
$count_result = $conn->query($count_sql);
$total_messages = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $per_page);

// جلب الرسائل مع التصفح
$messages_sql = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ?, ?";
$messages_stmt = $conn->prepare($messages_sql);
$messages_stmt->bind_param("ii", $offset, $per_page);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();

// عنوان الصفحة
$page_title = __('messages');
include '../includes/admin_header.php';
?>

<div class="admin-content-header">
    <div>
        <h1><?php echo __('messages'); ?></h1>
        <div class="admin-breadcrumb">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <span class="separator">/</span>
            <span><?php echo __('messages'); ?></span>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><?php echo __('contact_messages'); ?></h3>
    </div>
    <div class="admin-card-body">
        <?php if ($delete_success): ?>
            <div class="admin-alert admin-alert-success">
                <?php echo $delete_success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($delete_error): ?>
            <div class="admin-alert admin-alert-danger">
                <?php echo $delete_error; ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?php echo __('id'); ?></th>
                        <th><?php echo __('name'); ?></th>
                        <th><?php echo __('email'); ?></th>
                        <th><?php echo __('subject'); ?></th>
                        <th><?php echo __('date'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($messages_result->num_rows > 0): ?>
                        <?php while ($message = $messages_result->fetch_assoc()): ?>
                            <tr class="<?php echo $message['is_read'] ? '' : 'admin-unread-row'; ?>">
                                <td><?php echo $message['id']; ?></td>
                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($message['created_at'])); ?></td>
                                <td>
                                    <span class="admin-status-badge <?php echo $message['is_read'] ? 'read' : 'unread'; ?>">
                                        <?php echo $message['is_read'] ? __('read') : __('unread'); ?>
                                    </span>
                                </td>
                                <td class="admin-actions">
                                    <a href="view_message.php?id=<?php echo $message['id']; ?>" class="admin-btn admin-btn-sm admin-btn-info admin-btn-icon" data-tooltip="<?php echo __('view'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="manage_messages.php?delete=<?php echo $message['id']; ?>" class="admin-btn admin-btn-sm admin-btn-danger admin-btn-icon" data-tooltip="<?php echo __('delete'); ?>" onclick="return confirm('<?php echo __('confirm_delete_message'); ?>');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="admin-text-center"><?php echo __('no_messages_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="admin-pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="admin-pagination-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="admin-pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="admin-pagination-item">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>


