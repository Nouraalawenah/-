    </main>
    </div><!-- End of admin-main -->
    </div><!-- End of admin-wrapper -->
    
    <footer class="admin-footer">
        <div class="admin-footer-content">
            <div class="admin-footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php echo __('site_name'); ?> - <?php echo __('all_rights_reserved'); ?>
            </div>
            <div class="admin-footer-version">
                <?php echo __('version'); ?>: 1.0.0
            </div>
        </div>
    </footer>

    <script src="../js/admin.js"></script>
    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('admin-theme');
        if (savedTheme) {
            htmlElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        }
        
        // Toggle theme when button is clicked
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                htmlElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('admin-theme', newTheme);
                
                updateThemeIcon(newTheme);
            });
        }
        
        function updateThemeIcon(theme) {
            if (themeToggle) {
                const icon = themeToggle.querySelector('i');
                if (icon) {
                    if (theme === 'dark') {
                        icon.classList.remove('fa-moon');
                        icon.classList.add('fa-sun');
                    } else {
                        icon.classList.remove('fa-sun');
                        icon.classList.add('fa-moon');
                    }
                }
            }
        }
        
        // User dropdown functionality
        const userDropdown = document.querySelector('.admin-user-dropdown-toggle');
        const dropdownMenu = document.querySelector('.admin-user-dropdown-menu');
        
        if (userDropdown && dropdownMenu) {
            userDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                dropdownMenu.classList.remove('active');
            });
        }
    </script>
</body>
</html>



