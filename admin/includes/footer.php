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
?>
<div class="admin-container">
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2>لوحة التحكم</h2>
            <button id="sidebar-toggle" class="btn btn-primary">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="admin-sidebar-content">
            <ul class="admin-sidebar-menu">
                <li class="admin-sidebar-menu-item">
                    <a href="dashboard.php" class="admin-sidebar-menu-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        لوحة التحكم
                    </a>
                </li>
                <li class="admin-sidebar-menu-item">
                    <a href="users.php" class="admin-sidebar-menu-link">
                        <i class="fas fa-users"></i>
                        المستخدمين
                    </a>
                </li>
                <li class="admin-sidebar-menu-item">
                    <a href="categories.php" class="admin-sidebar-menu-link">
                        <i class="fas fa-list"></i>
                        الأقسام
                    </a>
                </li>
                <li class="admin-sidebar-menu-item">
                    <a href="products.php" class="admin-sidebar-menu-link">
                        <i class="fas fa-box"></i>
                        المنتجات
                    </a>
                </li>
                <li class="admin-sidebar-menu-item">
                    <a href="orders.php" class="admin-sidebar-menu-link">
                        <i class="fas fa-file-invoice-dollar"></i>
                        الطلبات
                    </a>
                </li>
                <li class="admin-sidebar-menu-item">
                    <a href="settings.php" class="admin-sidebar-menu-link">
                        <i class="fas fa-cog"></i>
                        الإعدادات
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <main class="admin-main">
        <div class="admin-content">
            <div class="admin-content-header">
                <h2>لوحة التحكم</h2>
            </div>
            <div class="admin-content-body">
                <div class="admin-dashboard">
                    <div class="admin-dashboard-item">
                        <div class="admin-dashboard-item-content">
                            <h3>عدد المستخدمين</h3>
                            <p>100</p>
                        </div>
                    </div>
                    <div class="admin-dashboard-item">
                        <div class="admin-dashboard-item-content">
                            <h3>عدد المنتجات</h3>
                            <p>500</p>
                        </div>
                    </div>
                    <div class="admin-dashboard-item">
                        <div class="admin-dashboard-item-content">
                            <h3>عدد الطلبات</h3>
                            <p>200</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>
<script>
    // تبديل الشريط الجانبي للشاشات الكبيرة
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const adminContainer = document.querySelector('.admin-container');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            adminContainer.classList.toggle('sidebar-collapsed');
            
            // حفظ حالة الشريط الجانبي في التخزين المحلي
            if (adminContainer.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('admin-sidebar', 'collapsed');
            } else {
                localStorage.setItem('admin-sidebar', 'expanded');
            }
        });
    }
    
    // استعادة حالة الشريط الجانبي من التخزين المحلي
    const savedSidebarState = localStorage.getItem('admin-sidebar');
    if (savedSidebarState === 'collapsed') {
        adminContainer.classList.add('sidebar-collapsed');
    }
</script>
</body>
</html>


