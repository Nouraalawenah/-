<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// التأكد من أن المستخدم ليس مسؤولاً أو مقدم خدمة
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: admin/dashboard.php");
    exit;
}

if (isset($_SESSION['is_provider']) && $_SESSION['is_provider'] == 1) {
    header("Location: provider/dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// الحصول على معلومات المستخدم
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// الحصول على أحدث طلبات الخدمة
$requests_sql = "SELECT sr.*, s.name as service_name, 
                sp.name as provider_name 
                FROM service_requests sr
                JOIN services s ON sr.service_id = s.id
                JOIN service_providers sp ON s.provider_id = sp.id
                WHERE sr.user_id = ?
                ORDER BY sr.created_at DESC LIMIT 5";
$requests_stmt = $conn->prepare($requests_sql);
$requests_stmt->bind_param("i", $user_id);
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();

// الحصول على الإحصائيات
$total_requests_sql = "SELECT COUNT(*) as total FROM service_requests WHERE user_id = ?";
$total_stmt = $conn->prepare($total_requests_sql);
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_requests = $total_stmt->get_result()->fetch_assoc()['total'];

$completed_sql = "SELECT COUNT(*) as total FROM service_requests WHERE user_id = ? AND status = 'completed'";
$completed_stmt = $conn->prepare($completed_sql);
$completed_stmt->bind_param("i", $user_id);
$completed_stmt->execute();
$completed_requests = $completed_stmt->get_result()->fetch_assoc()['total'];

$pending_sql = "SELECT COUNT(*) as total FROM service_requests WHERE user_id = ? AND status = 'pending'";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("i", $user_id);
$pending_stmt->execute();
$pending_requests = $pending_stmt->get_result()->fetch_assoc()['total'];

// عنوان الصفحة
$page_title = __('dashboard');

// تضمين الهيدر
include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- رأس لوحة التحكم -->
    <div class="dashboard-hero">
        <div class="container">
            <div class="hero-content">
                <h1><?= __('dashboard') ?></h1>
                <p><?= __('welcome_back') ?>, <span class="user-name"><?= htmlspecialchars($user['username']) ?></span></p>
            </div>
        </div>
    </div>
    
    <div class="container dashboard-container">
        <!-- بطاقات الإحصائيات -->
        <div class="stats-cards">
            <div class="stat-card primary-card">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-details">
                    <h3><?= __('total_requests') ?></h3>
                    <div class="stat-value"><?= number_format($total_requests) ?></div>
                </div>
            </div>
            
            <div class="stat-card success-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <h3><?= __('completed_requests') ?></h3>
                    <div class="stat-value"><?= number_format($completed_requests) ?></div>
                </div>
            </div>
            
            <div class="stat-card warning-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h3><?= __('pending_requests') ?></h3>
                    <div class="stat-value"><?= number_format($pending_requests) ?></div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <!-- أحدث طلبات الخدمة -->
            <div class="dashboard-card requests-card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> <?= __('recent_requests') ?></h2>
                    <a href="service_request.php" class="view-all"><?= __('view_all') ?> <i class="fas fa-arrow-left"></i></a>
                </div>
                <div class="card-body">
                    <?php if ($requests_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th><?= __('service') ?></th>
                                        <th><?= __('provider') ?></th>
                                        <th><?= __('status') ?></th>
                                        <th><?= __('date') ?></th>
                                        <th><?= __('actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = $requests_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="service-name">
                                                    <?= htmlspecialchars($request['service_name']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="provider-name">
                                                    <?= htmlspecialchars($request['provider_name']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?= get_status_class($request['status']) ?>">
                                                    <?= __($request['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('Y-m-d', strtotime($request['created_at'])) ?></td>
                                            <td>
                                                <a href="view_request.php?id=<?= $request['id'] ?>" class="action-btn">
                                                    <i class="fas fa-eye"></i> <?= __('view') ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h3><?= __('no_requests_yet') ?></h3>
                            <p><?= __('browse_services_to_start') ?></p>
                            <a href="services.php" class="btn-primary">
                                <i class="fas fa-search"></i> <?= __('browse_services') ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- الإجراءات السريعة -->
            <div class="dashboard-card actions-card">
                <div class="card-header">
                    <h2><i class="fas fa-bolt"></i> <?= __('quick_actions') ?></h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="services.php" class="quick-action">
                            <div class="action-icon">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <div class="action-details">
                                <h3><?= __('browse_services') ?></h3>
                                <p><?= __('explore_available_services') ?></p>
                            </div>
                        </a>
                        
                        <a href="profile.php" class="quick-action">
                            <div class="action-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div class="action-details">
                                <h3><?= __('edit_profile') ?></h3>
                                <p><?= __('update_your_information') ?></p>
                            </div>
                        </a>
                        
                        <a href="service_request.php" class="quick-action">
                            <div class="action-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="action-details">
                                <h3><?= __('my_requests') ?></h3>
                                <p><?= __('manage_your_service_requests') ?></p>
                            </div>
                        </a>
                        
                        <a href="providers.php" class="quick-action">
                            <div class="action-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="action-details">
                                <h3><?= __('find_providers') ?></h3>
                                <p><?= __('discover_service_providers') ?></p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #4e73df;
    --primary-hover: #2e59d9;
    --success-color: #1cc88a;
    --warning-color: #f6c23e;
    --danger-color: #e74a3b;
    --info-color: #36b9cc;
    --dark-color: #5a5c69;
    --light-color: #f8f9fc;
    --card-border-radius: 12px;
    --transition-speed: 0.3s;
}

/* تنسيقات عامة للوحة التحكم */
.dashboard-wrapper {
    background-color: #f8f9fc;
    min-height: calc(100vh - 60px);
}

.dashboard-hero {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    padding: 50px 0;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.dashboard-hero::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
}

.dashboard-hero::after {
    content: '';
    position: absolute;
    bottom: -80px;
    left: -80px;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-content h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.hero-content p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin: 0;
}

.user-name {
    font-weight: 700;
}

.dashboard-container {
    margin-top: -60px;
    position: relative;
    z-index: 10;
    padding-bottom: 50px;
}

/* بطاقات الإحصائيات */
.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: white;
    border-radius: var(--card-border-radius);
    padding: 25px;
    display: flex;
    align-items: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: transform var(--transition-speed), box-shadow var(--transition-speed);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100%;
    opacity: 0.1;
    transform: skewX(-20deg) translateX(70px);
    transition: transform var(--transition-speed);
}

.primary-card::before {
    background-color: var(--primary-color);
}

.success-card::before {
    background-color: var(--success-color);
}

.warning-card::before {
    background-color: var(--warning-color);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.stat-card:hover::before {
    transform: skewX(-20deg) translateX(30px);
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-left: 20px;
    color: white;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.primary-card .stat-icon {
    background-color: var(--primary-color);
}

.success-card .stat-icon {
    background-color: var(--success-color);
}

.warning-card .stat-icon {
    background-color: var(--warning-color);
}

.stat-details {
    flex: 1;
}

.stat-details h3 {
    font-size: 1rem;
    margin: 0 0 8px 0;
    color: var(--dark-color);
    opacity: 0.7;
}

.stat-value {
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1;
}

.primary-card .stat-value {
    color: var(--primary-color);
}

.success-card .stat-value {
    color: var(--success-color);
}

.warning-card .stat-value {
    color: var(--warning-color);
}

/* تخطيط البطاقات */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.dashboard-card {
    background-color: white;
    border-radius: var(--card-border-radius);
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    height: 100%;
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: white;
}

.card-header h2 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--dark-color);
    display: flex;
    align-items: center;
}

.card-header h2 i {
    margin-left: 10px;
    color: var(--primary-color);
}

.view-all {
    color: var(--primary-color);
    font-weight: 500;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: color var(--transition-speed);
}

.view-all i {
    margin-right: 5px;
    font-size: 0.8rem;
}

.view-all:hover {
    color: var(--primary-hover);
    text-decoration: none;
}

.card-body {
    padding: 25px;
}

/* جدول الطلبات */
.dashboard-table {
    width: 100%;
    border-collapse: collapse;
}

.dashboard-table th,
.dashboard-table td {
    padding: 15px;
    text-align: right;
}

.dashboard-table th {
    font-weight: 600;
    color: var(--dark-color);
    opacity: 0.7;
    border-bottom: 2px solid rgba(0, 0, 0, 0.05);
}

.dashboard-table td {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    vertical-align: middle;
}

.dashboard-table tr:last-child td {
    border-bottom: none;
}

.dashboard-table tr {
    transition: background-color var(--transition-speed);
}

.dashboard-table tr:hover {
    background-color: rgba(0, 0, 0, 0.01);
}

.service-name, .provider-name {
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-pending {
    background-color: #fff8e1;
    color: #ffa000;
}

.badge-completed {
    background-color: #e8f5e9;
    color: #4caf50;
}

.badge-rejected {
    background-color: #ffebee;
    color: #f44336;
}

.badge-accepted {
    background-color: #e3f2fd;
    color: #2196f3;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    padding: 8px 15px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    background-color: var(--primary-color);
    color: white;
    transition: all var(--transition-speed);
}

.action-btn:hover {
    background-color: var(--primary-hover);
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

.action-btn i {
    margin-left: 5px;
}

/* حالة فارغة */
.empty-state {
    text-align: center;
    padding: 50px 20px;
}

.empty-icon {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background-color: var(--light-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    color: var(--primary-color);
    margin: 0 auto 20px;
    opacity: 0.7;
}

.empty-state h3 {
    font-size: 1.3rem;
    margin-bottom: 10px;
    color: var(--dark-color);
}
</style>

<?php
// دالة مساعدة للحصول على فئة CSS للحالة
function get_status_class($status) {
    switch ($status) {
        case 'pending':
            return 'badge-pending';
        case 'completed':
            return 'badge-completed';
        case 'rejected':
            return 'badge-rejected';
        case 'accepted':
            return 'badge-accepted';
        default:
            return 'badge-secondary';
    }
}

// تضمين الفوتر
include 'includes/footer.php';
