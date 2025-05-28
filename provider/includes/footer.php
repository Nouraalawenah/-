        </div><!-- /.provider-content -->
    </main><!-- /.provider-main -->
</div><!-- /.provider-container -->

<script src="<?php echo $base_path; ?>js/script.js"></script>
<script src="<?php echo $base_path; ?>js/theme.js"></script>
<script src="<?php echo $base_path; ?>js/provider.js"></script>
<?php if (isset($page_specific_js) && !empty($page_specific_js)): ?>
<script src="<?php echo $base_path . $page_specific_js; ?>"></script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تبديل الشريط الجانبي للجوال
    const sidebarToggleMobile = document.getElementById('sidebar-toggle-mobile');
    const providerSidebar = document.querySelector('.provider-sidebar');
    
    if (sidebarToggleMobile) {
        sidebarToggleMobile.addEventListener('click', function() {
            providerSidebar.classList.toggle('show-sidebar');
        });
    }
    
    // تبديل الشريط الجانبي للشاشات الكبيرة
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const providerContainer = document.querySelector('.provider-container');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            providerContainer.classList.toggle('sidebar-collapsed');
            
            // حفظ حالة الشريط الجانبي في التخزين المحلي
            if (providerContainer.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('provider-sidebar', 'collapsed');
            } else {
                localStorage.setItem('provider-sidebar', 'expanded');
            }
        });
    }
    
    // استعادة حالة الشريط الجانبي من التخزين المحلي
    const savedSidebarState = localStorage.getItem('provider-sidebar');
    if (savedSidebarState === 'collapsed') {
        providerContainer.classList.add('sidebar-collapsed');
    }
});
</script>
</body>
</html>