<?php
require_once 'includes/auth_check.php';

// التحقق من وجود معرف الرسالة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_messages.php");
    exit;
}

$message_id = $_GET['id'];

// جلب معلومات الرسالة
$sql = "SELECT * FROM contact_messages WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $message_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_messages.php");
    exit;
}

$message = $result->fetch_assoc();

// تحديث حالة الرسالة إلى مقروءة
if ($message['is_read'] == 0) {
    $update_sql = "UPDATE contact_messages SET is_read = 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $message_id);
    $update_stmt->execute();
}

// عنوان الصفحة
$page_title = __('view_message');
include '../includes/admin_header.php';
?>

<div class="admin-content-header">
    <div>
        <h1><?php echo __('view_message'); ?></h1>
        <div class="admin-breadcrumb">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <span class="separator">/</span>
            <a href="manage_messages.php"><?php echo __('messages'); ?></a>
            <span class="separator">/</span>
            <span><?php echo __('view_message'); ?></span>
        </div>
    </div>
    <div class="admin-header-actions">
        <a href="manage_messages.php" class="admin-btn admin-btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo __('back_to_messages'); ?>
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><?php echo __('message_details'); ?></h3>
    </div>
    <div class="admin-card-body">
        <div class="admin-message-details">
            <div class="admin-message-header">
                <div class="admin-message-info">
                    <div class="admin-message-sender">
                        <strong><?php echo __('from'); ?>:</strong> <?php echo htmlspecialchars($message['name']); ?> (<?php echo htmlspecialchars($message['email']); ?>)
                    </div>
                    <div class="admin-message-date">
                        <strong><?php echo __('date'); ?>:</strong> <?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?>
                    </div>
                </div>
                <div class="admin-message-subject">
                    <strong><?php echo __('subject'); ?>:</strong> <?php echo htmlspecialchars($message['subject']); ?>
                </div>
            </div>
            <div class="admin-message-content">
                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
            </div>
        </div>
    </div>
    <div class="admin-card-footer">
        <div class="admin-form-actions">
            <a href="manage_messages.php?delete=<?php echo $message['id']; ?>" class="admin-btn admin-btn-danger" onclick="return confirm('<?php echo __('confirm_delete_message'); ?>');">
                <i class="fas fa-trash"></i> <?php echo __('delete'); ?>
            </a>
            <a href="manage_messages.php" class="admin-btn admin-btn-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo __('back'); ?>
            </a>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
