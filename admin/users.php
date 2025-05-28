<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// حذف مستخدم
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // التحقق من أن المستخدم ليس المدير الحالي
    if ($user_id != $_SESSION['user_id']) {
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        
        // إعادة توجيه لتجنب إعادة الحذف عند تحديث الصفحة
        header("Location: users.php?deleted=1");
        exit;
    } else {
        $error_message = __('cannot_delete_yourself');
    }
}

// تحديد الصفحة الحالية للتصفح
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// البحث
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$search_params = [];
$param_types = '';

if (!empty($search)) {
    $search_condition = " WHERE username LIKE ? OR email LIKE ?";
    $search_params = ["%$search%", "%$search%"];
    $param_types = "ss";
}

// جلب إجمالي عدد المستخدمين
$count_sql = "SELECT COUNT(*) as total FROM users" . $search_condition;
$count_stmt = $conn->prepare($count_sql);

if (!empty($search_params)) {
    $count_stmt->bind_param($param_types, ...$search_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

// جلب المستخدمين مع التصفح
$users_sql = "SELECT * FROM users" . $search_condition . " ORDER BY created_at DESC LIMIT ?, ?";
$users_stmt = $conn->prepare($users_sql);

if (!empty($search_params)) {
    $param_types .= "ii";
    $users_stmt->bind_param($param_types, ...[...$search_params, $offset, $per_page]);
} else {
    $users_stmt->bind_param("ii", $offset, $per_page);
}

$users_stmt->execute();
$users_result = $users_stmt->get_result();

// عنوان الصفحة
$page_title = __('users');
include '../includes/admin_header.php';
?>

<div class="admin-content-header">
    <div>
        <h1><?php echo __('users'); ?></h1>
        <div class="admin-breadcrumb">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <span class="separator">/</span>
            <span><?php echo __('users'); ?></span>
        </div>
    </div>
    <div class="admin-header-actions">
        <a href="add_user.php" class="admin-btn admin-btn-primary">
            <i class="fas fa-user-plus"></i> <?php echo __('add_user'); ?>
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><?php echo __('users_list'); ?></h3>
        <div class="admin-card-header-actions">
            <form method="get" class="admin-search-form">
                <input type="text" name="search" placeholder="<?php echo __('search_users'); ?>" value="<?php echo htmlspecialchars($search); ?>" class="admin-form-control">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="admin-card-body">
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <div class="admin-alert admin-alert-success">
                <?php echo __('user_deleted_successfully'); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="admin-alert admin-alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?php echo __('id'); ?></th>
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
                                        <span class="admin-badge admin-badge-primary"><?php echo __('admin'); ?></span>
                                    <?php elseif ($user['is_provider']): ?>
                                        <span class="admin-badge admin-badge-success"><?php echo __('provider'); ?></span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge-secondary"><?php echo __('user'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="admin-badge <?php echo $user['is_active'] ? 'admin-badge-success' : 'admin-badge-danger'; ?>">
                                        <?php echo $user['is_active'] ? __('active') : __('inactive'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td class="admin-actions">
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="admin-btn admin-btn-sm admin-btn-info admin-btn-icon" data-tooltip="<?php echo __('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?php echo $user['id']; ?>" class="admin-btn admin-btn-sm admin-btn-danger admin-btn-icon" data-tooltip="<?php echo __('delete'); ?>" onclick="return confirm('<?php echo __('confirm_delete_user'); ?>');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="admin-text-center"><?php echo __('no_users_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="admin-pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="admin-pagination-item">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="admin-pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="admin-pagination-item">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>