<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/language.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// تحديد نوع التقرير
$report_type = isset($_GET['type']) ? $_GET['type'] : 'users';
$valid_types = ['users', 'providers', 'services', 'requests'];

if (!in_array($report_type, $valid_types)) {
    $report_type = 'users';
}

// تحديد الفترة الزمنية
$time_period = isset($_GET['period']) ? $_GET['period'] : 'month';
$valid_periods = ['week', 'month', 'quarter', 'year', 'all'];

if (!in_array($time_period, $valid_periods)) {
    $time_period = 'month';
}

// تحديد تاريخ البداية بناءً على الفترة الزمنية
$start_date = '';
switch ($time_period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-1 week'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-1 month'));
        break;
    case 'quarter':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $start_date = '1970-01-01'; // كل البيانات
}

// استعلامات التقارير
$report_data = [];
$chart_data = [];

switch ($report_type) {
    case 'users':
        // تقرير المستخدمين
        $query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM users 
                 WHERE created_at >= '$start_date' 
                 GROUP BY DATE(created_at) 
                 ORDER BY date";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $chart_data[] = [
                'date' => $row['date'],
                'count' => (int)$row['count']
            ];
        }
        
        // إجمالي المستخدمين
        $total_query = "SELECT COUNT(*) as total FROM users";
        $total_result = $conn->query($total_query);
        $total = $total_result->fetch_assoc()['total'];
        
        // المستخدمين في الفترة المحددة
        $period_query = "SELECT COUNT(*) as period_total FROM users WHERE created_at >= '$start_date'";
        $period_result = $conn->query($period_query);
        $period_total = $period_result->fetch_assoc()['period_total'];
        
        $report_data = [
            'total' => $total,
            'period_total' => $period_total,
            'title' => __('users_report')
        ];
        break;
        
    case 'providers':
        // تقرير مقدمي الخدمات
        $query = "SELECT DATE(sp.created_at) as date, COUNT(*) as count 
                 FROM service_providers sp 
                 WHERE sp.created_at >= '$start_date' 
                 GROUP BY DATE(sp.created_at) 
                 ORDER BY date";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $chart_data[] = [
                'date' => $row['date'],
                'count' => (int)$row['count']
            ];
        }
        
        // إجمالي مقدمي الخدمات
        $total_query = "SELECT COUNT(*) as total FROM service_providers";
        $total_result = $conn->query($total_query);
        $total = $total_result->fetch_assoc()['total'];
        
        // مقدمي الخدمات في الفترة المحددة
        $period_query = "SELECT COUNT(*) as period_total FROM service_providers WHERE created_at >= '$start_date'";
        $period_result = $conn->query($period_query);
        $period_total = $period_result->fetch_assoc()['period_total'];
        
        $report_data = [
            'total' => $total,
            'period_total' => $period_total,
            'title' => __('providers_report')
        ];
        break;
        
    case 'services':
        // تقرير الخدمات
        $query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM services 
                 WHERE created_at >= '$start_date' 
                 GROUP BY DATE(created_at) 
                 ORDER BY date";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $chart_data[] = [
                'date' => $row['date'],
                'count' => (int)$row['count']
            ];
        }
        
        // إجمالي الخدمات
        $total_query = "SELECT COUNT(*) as total FROM services";
        $total_result = $conn->query($total_query);
        $total = $total_result->fetch_assoc()['total'];
        
        // الخدمات في الفترة المحددة
        $period_query = "SELECT COUNT(*) as period_total FROM services WHERE created_at >= '$start_date'";
        $period_result = $conn->query($period_query);
        $period_total = $period_result->fetch_assoc()['period_total'];
        
        $report_data = [
            'total' => $total,
            'period_total' => $period_total,
            'title' => __('services_report')
        ];
        break;
        
    case 'requests':
        // تقرير طلبات الخدمة
        $query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM service_requests 
                 WHERE created_at >= '$start_date' 
                 GROUP BY DATE(created_at) 
                 ORDER BY date";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $chart_data[] = [
                'date' => $row['date'],
                'count' => (int)$row['count']
            ];
        }
        
        // إجمالي الطلبات
        $total_query = "SELECT COUNT(*) as total FROM service_requests";
        $total_result = $conn->query($total_query);
        $total = $total_result->fetch_assoc()['total'];
        
        // الطلبات في الفترة المحددة
        $period_query = "SELECT COUNT(*) as period_total FROM service_requests WHERE created_at >= '$start_date'";
        $period_result = $conn->query($period_query);
        $period_total = $period_result->fetch_assoc()['period_total'];
        
        // توزيع الطلبات حسب الحالة
        $status_query = "SELECT status, COUNT(*) as count FROM service_requests WHERE created_at >= '$start_date' GROUP BY status";
        $status_result = $conn->query($status_query);
        $status_data = [];
        
        while ($row = $status_result->fetch_assoc()) {
            $status_data[$row['status']] = (int)$row['count'];
        }
        
        $report_data = [
            'total' => $total,
            'period_total' => $period_total,
            'status_data' => $status_data,
            'title' => __('requests_report')
        ];
        break;
}

// عنوان الصفحة
$page_title = __('reports');
include '../includes/admin_header.php';
?>

<div class="admin-content-header">
    <div>
        <h1><?php echo __('reports'); ?></h1>
        <div class="admin-breadcrumb">
            <a href="dashboard.php"><?php echo __('dashboard'); ?></a>
            <span class="separator">/</span>
            <span><?php echo __('reports'); ?></span>
        </div>
    </div>
    <div class="admin-header-actions">
        <a href="export.php?type=<?php echo $report_type; ?>" class="admin-btn admin-btn-primary">
            <i class="fas fa-download"></i> <?php echo __('export_data'); ?>
        </a>
    </div>
</div>

<div class="admin-reports-container">
    <!-- فلاتر التقارير -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><?php echo __('report_filters'); ?></h3>
        </div>
        <div class="admin-card-body">
            <form method="get" class="admin-report-filters">
                <div class="admin-row">
                    <div class="admin-col-md-6">
                        <div class="admin-form-group">
                            <label for="report-type"><?php echo __('report_type'); ?></label>
                            <select name="type" id="report-type" class="admin-form-control">
                                <option value="users" <?php echo $report_type == 'users' ? 'selected' : ''; ?>><?php echo __('users_report'); ?></option>
                                <option value="providers" <?php echo $report_type == 'providers' ? 'selected' : ''; ?>><?php echo __('providers_report'); ?></option>
                                <option value="services" <?php echo $report_type == 'services' ? 'selected' : ''; ?>><?php echo __('services_report'); ?></option>
                                <option value="requests" <?php echo $report_type == 'requests' ? 'selected' : ''; ?>><?php echo __('requests_report'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="admin-col-md-6">
                        <div class="admin-form-group">
                            <label for="time-period"><?php echo __('time_period'); ?></label>
                            <select name="period" id="time-period" class="admin-form-control">
                                <option value="week" <?php echo $time_period == 'week' ? 'selected' : ''; ?>><?php echo __('last_week'); ?></option>
                                <option value="month" <?php echo $time_period == 'month' ? 'selected' : ''; ?>><?php echo __('last_month'); ?></option>
                                <option value="quarter" <?php echo $time_period == 'quarter' ? 'selected' : ''; ?>><?php echo __('last_quarter'); ?></option>
                                <option value="year" <?php echo $time_period == 'year' ? 'selected' : ''; ?>><?php echo __('last_year'); ?></option>
                                <option value="all" <?php echo $time_period == 'all' ? 'selected' : ''; ?>><?php echo __('all_time'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="admin-form-group">
                    <button type="submit" class="admin-btn admin-btn-primary"><?php echo __('apply_filters'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- تقرير المستخدمين -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><?php echo $report_data['title']; ?></h3>
        </div>
        <div class="admin-card-body">
            <div class="admin-row">
                <div class="admin-col-md-6">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label">
                            <i class="fas fa-users"></i> <?php echo __('total_users'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo $report_data['total']; ?>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-6">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label">
                            <i class="fas fa-user-plus"></i> <?php echo __('new_users'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo $report_data['period_total']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- تقرير طلبات الخدمة -->
    <?php if ($report_type == 'requests'): ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><?php echo __('requests_by_status'); ?></h3>
        </div>
        <div class="admin-card-body">
            <div class="admin-row">
                <div class="admin-col-md-4">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label admin-text-warning">
                            <i class="fas fa-clock"></i> <?php echo __('pending'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo isset($report_data['status_data']['pending']) ? $report_data['status_data']['pending'] : 0; ?>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-4">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label admin-text-success">
                            <i class="fas fa-check"></i> <?php echo __('approved'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo isset($report_data['status_data']['approved']) ? $report_data['status_data']['approved'] : 0; ?>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-4">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label admin-text-primary">
                            <i class="fas fa-check-circle"></i> <?php echo __('completed'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo isset($report_data['status_data']['completed']) ? $report_data['status_data']['completed'] : 0; ?>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-4">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label admin-text-danger">
                            <i class="fas fa-times"></i> <?php echo __('rejected'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo isset($report_data['status_data']['rejected']) ? $report_data['status_data']['rejected'] : 0; ?>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-4">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label admin-text-secondary">
                            <i class="fas fa-ban"></i> <?php echo __('cancelled'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo isset($report_data['status_data']['cancelled']) ? $report_data['status_data']['cancelled'] : 0; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- تقرير الخدمات -->
    <?php if ($report_type == 'services'): ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><?php echo __('services_report'); ?></h3>
        </div>
        <div class="admin-card-body">
            <div class="admin-row">
                <div class="admin-col-md-6">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label">
                            <i class="fas fa-concierge-bell"></i> <?php echo __('total_services'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo $report_data['total']; ?>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-6">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label">
                            <i class="fas fa-plus"></i> <?php echo __('new_services'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo $report_data['period_total']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- تقرير مقدمي الخدمات -->
    <?php if ($report_type == 'providers'): ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><?php echo __('providers_report'); ?></h3>
        </div>
        <div class="admin-card-body">
            <div class="admin-row">
                <div class="admin-col-md-6">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label">
                            <i class="fas fa-user-tie"></i> <?php echo __('total_providers'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo $report_data['total']; ?>
                        </div>
                    </div>
                </div>
                <div class="admin-col-md-6">
                    <div class="admin-stat-item">
                        <div class="admin-stat-label">
                            <i class="fas fa-user-plus"></i> <?php echo __('new_providers'); ?>
                        </div>
                        <div class="admin-stat-value">
                            <?php echo $report_data['period_total']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>





