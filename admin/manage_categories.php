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
$page_title = __('manage_categories');

// تضمين ملف الهيدر
include 'includes/header.php';

// استعلام لجلب الفئات
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>

<div class="admin-content-header">
    <h2><?php echo __('manage_categories'); ?></h2>
    <div class="admin-content-header-actions">
        <a href="add_category.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo __('add_category'); ?>
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
                        <th><?php echo __('name'); ?></th>
                        <th><?php echo __('description'); ?></th>
                        <th><?php echo __('image'); ?></th>
                        <th><?php echo __('services_count'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($categories_result->num_rows > 0): ?>
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <?php
                            // استعلام لحساب عدد الخدمات في كل فئة
                            $services_count_sql = "SELECT COUNT(*) as count FROM services WHERE category_id = ?";
                            $services_count_stmt = $conn->prepare($services_count_sql);
                            $services_count_stmt->bind_param("i", $category['id']);
                            $services_count_stmt->execute();
                            $services_count_result = $services_count_stmt->get_result();
                            $services_count = $services_count_result->fetch_assoc()['count'];
                            ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo mb_substr(htmlspecialchars($category['description']), 0, 50) . '...'; ?></td>
                                <td>
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="../images/<?php echo $category['image']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="admin-thumbnail">
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?php echo __('no_image'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $services_count; ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo __('edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger delete-category" data-id="<?php echo $category['id']; ?>" title="<?php echo __('delete'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center"><?php echo __('no_categories_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

