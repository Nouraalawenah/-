<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من تسجيل الدخول ومن أن المستخدم هو مقدم خدمة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_provider']) || $_SESSION['is_provider'] != 1) {
    header("Location: ../login.php");
    exit;
}

// الحصول على معلومات مقدم الخدمة
$user_id = $_SESSION['user_id'];
$provider_query = "SELECT * FROM service_providers WHERE user_id = ?";
$stmt = $conn->prepare($provider_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$provider_result = $stmt->get_result();

if ($provider_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit;
}

$provider = $provider_result->fetch_assoc();
$provider_id = $provider['id'];

// التحقق من وجود معرف الخدمة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: services.php");
    exit;
}

$service_id = intval($_GET['id']);

// التحقق من أن الخدمة تنتمي لمقدم الخدمة الحالي
$service_query = "SELECT s.*, c.name as category_name 
                 FROM services s 
                 JOIN categories c ON s.category_id = c.id 
                 WHERE s.id = ? AND s.provider_id = ?";
$stmt = $conn->prepare($service_query);
$stmt->bind_param("ii", $service_id, $provider_id);
$stmt->execute();
$service_result = $stmt->get_result();

if ($service_result->num_rows == 0) {
    header("Location: services.php");
    exit;
}

$service = $service_result->fetch_assoc();

// جلب عدد الطلبات لهذه الخدمة
$requests_query = "SELECT COUNT(*) as total_requests, 
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                  SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_requests,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests
                  FROM service_requests 
                  WHERE service_id = ?";
$stmt = $conn->prepare($requests_query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$requests_result = $stmt->get_result();
$requests_stats = $requests_result->fetch_assoc();

// جلب التقييمات إذا كان جدول التقييمات موجودًا
$reviews = [];
$has_reviews_table = false;

$check_reviews_table = "SHOW TABLES LIKE 'service_reviews'";
$reviews_table_result = $conn->query($check_reviews_table);
$has_reviews_table = ($reviews_table_result->num_rows > 0);

if ($has_reviews_table) {
    $reviews_query = "SELECT sr.*, u.username 
                     FROM service_reviews sr 
                     JOIN users u ON sr.user_id = u.id 
                     JOIN service_requests req ON sr.request_id = req.id
                     WHERE req.service_id = ? 
                     ORDER BY sr.created_at DESC 
                     LIMIT 5";
    $stmt = $conn->prepare($reviews_query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $reviews_result = $stmt->get_result();
    
    if ($reviews_result->num_rows > 0) {
        while ($row = $reviews_result->fetch_assoc()) {
            $reviews[] = $row;
        }
    }
}

// تحديد الصفحة النشطة للقائمة الجانبية
$active_page = 'services';
$current_page = 'view_service.php';
$page_title = __('service_details');
?>

<!DOCTYPE html>
<html lang="<?php echo $lang['lang_code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('service_details'); ?> - <?php echo __('provider_dashboard'); ?></title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/provider.css">
    <?php if ($lang['dir'] == 'rtl'): ?>
    <link rel="stylesheet" href="../css/bootstrap-rtl.min.css">
    <?php endif; ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="provider-content">
        <?php include 'includes/header.php'; ?>
        
        <main class="provider-main">
            <div class="container-fluid">
                <div class="provider-page-title">
                    <h1><?php echo __('service_details'); ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><?php echo __('dashboard'); ?></a></li>
                            <li class="breadcrumb-item"><a href="services.php"><?php echo __('my_services'); ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($service['name']); ?></li>
                        </ol>
                    </nav>
                </div>
                
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="provider-card">
                            <div class="provider-card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2><?php echo htmlspecialchars($service['name']); ?></h2>
                                    <span class="badge <?php echo $service['active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $service['active'] ? __('active') : __('inactive'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="provider-card-body">
                                <div class="service-image mb-4">
                                    <?php if (!empty($service['image']) && file_exists('../uploads/services/' . $service['image'])): ?>
                                        <img src="../uploads/services/<?php echo $service['image']; ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="img-fluid rounded">
                                    <?php else: ?>
                                        <img src="../images/service-placeholder.jpg" alt="<?php echo htmlspecialchars($service['name']); ?>" class="img-fluid rounded">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="service-info">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label><?php echo __('category'); ?>:</label>
                                                <p><?php echo htmlspecialchars($service['category_name']); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label><?php echo __('price'); ?>:</label>
                                                <p><?php echo number_format($service['price'], 2); ?> <?php echo __('currency'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-group mb-4">
                                        <label><?php echo __('description'); ?>:</label>
                                        <div class="description-box">
                                            <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="service-actions">
                                        <a href="edit_service.php?id=<?php echo $service_id; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> <?php echo __('edit_service'); ?>
                                        </a>
                                        
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteServiceModal">
                                            <i class="fas fa-trash-alt"></i> <?php echo __('delete'); ?>
                                        </button>
                                        
                                        <a href="services.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-<?php echo $lang['dir'] == 'rtl' ? 'right' : 'left'; ?>"></i> 
                                            <?php echo __('back_to_services'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($has_reviews_table && count($reviews) > 0): ?>
                        <div class="provider-card mt-4">
                            <div class="provider-card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2><?php echo __('recent_reviews'); ?></h2>
                                    <a href="reviews.php?service_id=<?php echo $service_id; ?>" class="btn btn-sm btn-outline-primary">
                                        <?php echo __('view_all_reviews'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="provider-card-body">
                                <div class="reviews-list">
                                    <?php foreach ($reviews as $review): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <div class="review-user">
                                                <i class="fas fa-user-circle"></i>
                                                <span><?php echo htmlspecialchars($review['username']); ?></span>
                                            </div>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'active' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="review-date">
                                            <?php echo date('Y-m-d', strtotime($review['created_at'])); ?>
                                        </div>
                                        <div class="review-content">
                                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="provider-card">
                            <div class="provider-card-header">
                                <h2><?php echo __('service_requests_statistics'); ?></h2>
                            </div>
                            <div class="provider-card-body">
                                <div class="stats-item">
                                    <div class="stats-icon bg-primary">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3><?php echo $requests_stats['total_requests'] ?? 0; ?></h3>
                                        <p><?php echo __('total_requests'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="stats-item">
                                    <div class="stats-icon bg-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3><?php echo $requests_stats['pending_requests'] ?? 0; ?></h3>
                                        <p><?php echo __('pending_requests'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="stats-item">
                                    <div class="stats-icon bg-info">
                                        <i class="fas fa-spinner"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3><?php echo $requests_stats['accepted_requests'] ?? 0; ?></h3>
                                        <p><?php echo __('in_progress'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="stats-item">
                                    <div class="stats-icon bg-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3><?php echo $requests_stats['completed_requests'] ?? 0; ?></h3>
                                        <p><?php echo __('completed'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="requests.php?service_id=<?php echo $service_id; ?>" class="btn btn-outline-primary btn-block">
                                        <i class="fas fa-list"></i> <?php echo __('view_all_requests'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="provider-card mt-4">
                            <div class="provider-card-header">
                                <h2><?php echo __('quick_actions'); ?></h2>
                            </div>
                            <div class="provider-card-body">
                                <div class="quick-actions">
                                    <a href="edit_service.php?id=<?php echo $service_id; ?>" class="btn btn-primary btn-block">
                                        <i class="fas fa-edit"></i> <?php echo __('edit_service'); ?>
                                    </a>
                                    
                                    <a href="../service_details.php?id=<?php echo $service_id; ?>" class="btn btn-info btn-block" target="_blank">
                                        <i class="fas fa-eye"></i> <?php echo __('view_public_page'); ?>
                                    </a>
                                    
                                    <button type="button" class="btn btn-<?php echo $service['active'] ? 'warning' : 'success'; ?> btn-block" id="toggleStatusBtn" data-id="<?php echo $service_id; ?>" data-status="<?php echo $service['active'] ? '0' : '1'; ?>">
                                        <i class="fas fa-<?php echo $service['active'] ? 'pause' : 'play'; ?>"></i> 
                                        <?php echo $service['active'] ? __('deactivate') : __('activate'); ?>
                                    </button>
                                    
                                    <button type="button" class="btn btn-danger btn-block" data-bs-toggle="modal" data-bs-target="#deleteServiceModal">
                                        <i class="fas fa-trash-alt"></i> <?php echo __('delete'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Delete Service Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1" aria-labelledby="deleteServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteServiceModalLabel"><?php echo __('confirm_delete'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo __('confirm_delete_service'); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" data-id="<?php echo $service_id; ?>"><?php echo __('delete'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/provider.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle service status
            $('#toggleStatusBtn').click(function() {
                const serviceId = $(this).data('id');
                const newStatus = $(this).data('status');
                
                $.ajax({
                    url: 'ajax/toggle_service_status.php',
                    type: 'POST',
                    data: {
                        id: serviceId,
                        status: newStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('<?php echo __('error'); ?>');
                    }
                });
            });
            
            // Delete service
            $('#confirmDeleteBtn').click(function() {
                const serviceId = $(this).data('id');
                
                $.ajax({
                    url: 'delete_service.php',
                    type: 'POST',
                    data: {
                        id: serviceId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = 'services.php';
                        } else {
                            alert(response.message);
                            $('#deleteServiceModal').modal('hide');
                        }
                    },
                    error: function() {
                        alert('<?php echo __('error'); ?>');
                        $('#deleteServiceModal').modal('hide');
                    }
                });
            });
        });
    </script>
</body>
</html>
