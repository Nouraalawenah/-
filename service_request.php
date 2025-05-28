<?php
session_start();
require_once 'config/db_connect.php';
require_once 'config/language.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// تحديد عمود اللغة المناسب
$lang_suffix = $_SESSION['lang'] == 'ar' ? 'ar' : 'en';
$name_column = "name_" . $lang_suffix;
$desc_column = "description_" . $lang_suffix;

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// التحقق من وجود جدول service_requests
$check_table = "SHOW TABLES LIKE 'service_requests'";
$table_result = $conn->query($check_table);
$table_exists = ($table_result->num_rows > 0);

if (!$table_exists) {
    $error = __('service_requests_table_not_found');
}

// التحقق من وجود معرف الخدمة في الرابط
$selected_service_id = 0;
if (isset($_GET['service_id']) && is_numeric($_GET['service_id'])) {
    $selected_service_id = intval($_GET['service_id']);
}

// معالجة إرسال طلب خدمة جديد
if ($table_exists && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $scheduled_date = isset($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
    
    // التحقق من صحة البيانات
    if ($service_id <= 0) {
        $error = __('invalid_service');
    } else {
        // التحقق من وجود الخدمة
        $check_service = "SELECT * FROM services WHERE id = ?";
        $check_stmt = $conn->prepare($check_service);
        $check_stmt->bind_param("i", $service_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $error = __('service_not_found');
        } else {
            // إضافة طلب الخدمة
            $insert_sql = "INSERT INTO service_requests (user_id, service_id, message, status, scheduled_date) 
                          VALUES (?, ?, ?, 'pending', ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiss", $user_id, $service_id, $message, $scheduled_date);
            
            if ($insert_stmt->execute()) {
                $request_id = $conn->insert_id;
                
                // Get provider email
                $provider_email_sql = "SELECT u.email FROM service_providers sp 
                                      JOIN users u ON sp.user_id = u.id 
                                      JOIN services s ON s.provider_id = sp.id 
                                      WHERE s.id = ?";
                $provider_email_stmt = $conn->prepare($provider_email_sql);
                $provider_email_stmt->bind_param("i", $service_id);
                $provider_email_stmt->execute();
                $provider_email_result = $provider_email_stmt->get_result();
                
                if ($provider_email_result->num_rows > 0) {
                    $provider_data = $provider_email_result->fetch_assoc();
                    // Send notification email (implement email sending function)
                    // sendEmail($provider_data['email'], 'New Service Request', 'You have a new service request...');
                }
                
                $success = __('request_sent_success');
            } else {
                $error = __('request_send_error');
            }
        }
    }
}

// جلب طلبات الخدمة للمستخدم الحالي
$requests = [];
if ($table_exists) {
    $requests_sql = "SELECT sr.*, s.{$name_column} as service_name, sp.{$name_column} as provider_name, 
                    sp.phone as provider_phone, sp.email as provider_email
                    FROM service_requests sr
                    JOIN services s ON sr.service_id = s.id
                    JOIN service_providers sp ON s.provider_id = sp.id
                    WHERE sr.user_id = ?
                    ORDER BY sr.created_at DESC";
    $requests_stmt = $conn->prepare($requests_sql);
    $requests_stmt->bind_param("i", $user_id);
    $requests_stmt->execute();
    $requests_result = $requests_stmt->get_result();

    if ($requests_result->num_rows > 0) {
        while ($row = $requests_result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
}

// جلب الخدمات المتاحة للطلب
$services_sql = "SELECT s.id, s.{$name_column} as name, s.price, sp.{$name_column} as provider_name, 
                c.{$name_column} as category_name
                FROM services s
                JOIN service_providers sp ON s.provider_id = sp.id
                JOIN categories c ON s.category_id = c.id
                ORDER BY s.{$name_column}";
$services_result = $conn->query($services_sql);
$services = [];

if ($services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// إذا تم تمرير معرف الخدمة في الرابط، قم بتحديد التبويب الثاني تلقائيًا
$active_tab = 'tab-my-requests';
if ($selected_service_id > 0) {
    $active_tab = 'tab-new-request';
}
?>

<!DOCTYPE html>
<html dir="<?php echo __('dir'); ?>" lang="<?php echo __('lang_code'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('service_requests'); ?> - <?php echo __('site_name'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .tab-container {
            margin: 20px 0;
        }
        
        .tab-links {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .tab-link {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .tab-link.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .request-form {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px var(--shadow-color);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background-color: var(--input-bg);
            color: var(--text-color);
        }
        
        textarea.form-control {
            min-height: 100px;
        }
        
        .request-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px var(--shadow-color);
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .request-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .request-date {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .provider-info {
            background-color: var(--bg-light);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .provider-info h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .provider-info p {
            margin: 5px 0;
        }
        
        .request-message {
            background-color: var(--bg-light);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .request-message h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .request-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .status-completed {
            background-color: #CCE5FF;
            color: #004085;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1><?php echo __('service_requests'); ?></h1>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!$table_exists): ?>
            <div class="alert alert-warning">
                <p><?php echo __('service_requests_table_not_found'); ?></p>
                <p><?php echo __('please_run_setup'); ?> <a href="setup_service_requests.php"><?php echo __('setup_service_requests'); ?></a></p>
            </div>
        <?php else: ?>
            <div class="tab-container">
                <div class="tab-links">
                    <div class="tab-link <?php echo ($active_tab == 'tab-my-requests') ? 'active' : ''; ?>" data-tab="tab-my-requests"><?php echo __('my_requests'); ?></div>
                    <div class="tab-link <?php echo ($active_tab == 'tab-new-request') ? 'active' : ''; ?>" data-tab="tab-new-request"><?php echo __('new_request'); ?></div>
                </div>
                
                <div id="tab-my-requests" class="tab-content active">
                    <?php if (empty($requests)): ?>
                        <p class="no-results"><?php echo __('no_requests_yet'); ?></p>
                    <?php else: ?>
                        <div class="requests-list">
                            <?php foreach ($requests as $request): ?>
                                <div class="request-card">
                                    <div class="request-header">
                                        <div class="request-title"><?php echo htmlspecialchars($request['service_name'] ?? ''); ?></div>
                                        <div class="request-date">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?php echo date('d/m/Y', strtotime($request['created_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="provider-info">
                                        <h4><?php echo __('provider_information'); ?></h4>
                                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($request['provider_name'] ?? ''); ?></p>
                                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['provider_phone'] ?? ''); ?></p>
                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($request['provider_email'] ?? ''); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($request['message'])): ?>
                                    <div class="request-message">
                                        <h4><?php echo __('your_message'); ?></h4>
                                        <p><?php echo nl2br(htmlspecialchars($request['message'] ?? '')); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="request-details">
                                        <div class="request-status-info">
                                            <h4><?php echo __('request_status'); ?></h4>
                                            <span class="request-status status-<?php echo $request['status']; ?>">
                                                <?php echo __('status_' . $request['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="request-scheduled">
                                            <h4><?php echo __('scheduled_for'); ?></h4>
                                            <p>
                                                <?php if (!empty($request['scheduled_date'])): ?>
                                                    <i class="far fa-calendar-check"></i> 
                                                    <?php echo date('d/m/Y', strtotime($request['scheduled_date'])); ?>
                                                <?php else: ?>
                                                    <i class="far fa-calendar-times"></i> 
                                                    <?php echo __('not_scheduled'); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($request['status'] == 'accepted'): ?>
                                    <div class="request-actions">
                                        <button class="btn btn-outline" onclick="contactProvider('<?php echo htmlspecialchars($request['provider_phone']); ?>')">
                                            <i class="fas fa-phone"></i> <?php echo __('contact_provider'); ?>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="tab-new-request" class="tab-content">
                    <div class="request-form">
                        <h3><?php echo __('request_new_service'); ?></h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="service_id"><?php echo __('select_service'); ?></label>
                                <select name="service_id" id="service_id" class="form-control" required>
                                    <option value=""><?php echo __('choose_service'); ?></option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo ($service['id'] == $selected_service_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['name'] ?? ''); ?> - 
                                            <?php echo htmlspecialchars($service['provider_name'] ?? ''); ?> - 
                                            <?php echo htmlspecialchars($service['category_name'] ?? ''); ?> - 
                                            <?php echo number_format($service['price'] ?? 0, 2); ?> <?php echo __('currency'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="message"><?php echo __('message_to_provider'); ?></label>
                                <textarea name="message" id="message" class="form-control" placeholder="<?php echo __('message_placeholder'); ?>"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="scheduled_date"><?php echo __('preferred_date'); ?></label>
                                <input type="date" name="scheduled_date" id="scheduled_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="submit_request" class="btn">
                                    <i class="fas fa-paper-plane"></i> <?php echo __('send_request'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تهيئة التبويبات
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // تعيين التبويب النشط
            tabContents.forEach(c => c.classList.remove('active'));
            document.getElementById('<?php echo $active_tab; ?>').classList.add('active');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // إزالة الفئة النشطة من جميع الروابط والمحتويات
                    tabLinks.forEach(l => l.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // إضافة الفئة النشطة للرابط المحدد
                    this.classList.add('active');
                    
                    // إظهار المحتوى المقابل
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
        
        function contactProvider(phone) {
            window.location.href = 'tel:' + phone;
        }
    </script>
</body>
</html>







