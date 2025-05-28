<?php
// تأكد من بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// تضمين ملفات التكوين
require_once '../config/db_connect.php';
require_once '../config/language.php';
?>
