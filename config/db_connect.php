<?php
// معلومات الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";  // اسم المستخدم الافتراضي في Laragon
$password = "";      // كلمة المرور الافتراضية في Laragon فارغة
$dbname = "service_portal";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تعيين ترميز الاتصال إلى UTF-8mb4
$conn->set_charset("utf8mb4");
?>
