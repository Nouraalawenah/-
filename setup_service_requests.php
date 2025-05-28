<?php
require_once 'config/db_connect.php';

// التحقق مما إذا كان جدول طلبات الخدمة موجودًا
$check_table = "SHOW TABLES LIKE 'service_requests'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    // إنشاء جدول طلبات الخدمة
    $create_requests_table = "
    CREATE TABLE service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_id INT NOT NULL,
        message TEXT,
        status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
        scheduled_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_requests_table) === TRUE) {
        echo "تم إنشاء جدول طلبات الخدمة بنجاح<br>";
    } else {
        echo "خطأ في إنشاء جدول طلبات الخدمة: " . $conn->error . "<br>";
    }
} else {
    echo "جدول طلبات الخدمة موجود بالفعل<br>";
}

// إضافة بعض البيانات التجريبية إذا كان الجدول فارغًا
$check_data = "SELECT COUNT(*) as count FROM service_requests";
$result = $conn->query($check_data);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // جلب بعض المستخدمين وبعض الخدمات
    $users_sql = "SELECT id FROM users WHERE is_provider = 0 LIMIT 5";
    $users_result = $conn->query($users_sql);
    $users = [];
    
    if ($users_result->num_rows > 0) {
        while ($user = $users_result->fetch_assoc()) {
            $users[] = $user['id'];
        }
    }
    
    $services_sql = "SELECT id FROM services LIMIT 10";
    $services_result = $conn->query($services_sql);
    $services = [];
    
    if ($services_result->num_rows > 0) {
        while ($service = $services_result->fetch_assoc()) {
            $services[] = $service['id'];
        }
    }
    
    // إذا كان هناك مستخدمين وخدمات، أضف بعض الطلبات التجريبية
    if (!empty($users) && !empty($services)) {
        $statuses = ['pending', 'accepted', 'rejected', 'completed'];
        $messages = [
            'أحتاج هذه الخدمة في أقرب وقت ممكن.',
            'هل يمكنك تقديم هذه الخدمة يوم السبت القادم؟',
            'أرغب في الحصول على تفاصيل أكثر حول هذه الخدمة.',
            'هل السعر قابل للتفاوض؟',
            'أحتاج هذه الخدمة بشكل منتظم، هل هناك خصم للعملاء المنتظمين؟'
        ];
        
        $insert_sql = "INSERT INTO service_requests (user_id, service_id, message, status, scheduled_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        
        for ($i = 0; $i < 15; $i++) {
            $user_id = $users[array_rand($users)];
            $service_id = $services[array_rand($services)];
            $message = $messages[array_rand($messages)];
            $status = $statuses[array_rand($statuses)];
            
            // إنشاء تاريخ عشوائي خلال الشهر القادم
            $days = rand(1, 30);
            $scheduled_date = date('Y-m-d', strtotime("+$days days"));
            
            $stmt->bind_param("iisss", $user_id, $service_id, $message, $status, $scheduled_date);
            $stmt->execute();
        }
        
        echo "تم إضافة بيانات تجريبية لجدول طلبات الخدمة<br>";
    }
}

echo "تم إعداد جدول طلبات الخدمة بنجاح!";
?>
