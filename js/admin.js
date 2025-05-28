// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.admin-sidebar');

    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 992 && 
                sidebar.classList.contains('active') && 
                !sidebar.contains(event.target) && 
                event.target !== mobileMenuToggle) {
                sidebar.classList.remove('active');
            }
        });
    }
    
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const themeIcon = themeToggle ? themeToggle.querySelector('i') : null;
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('admin-theme');
    if (savedTheme === 'dark') {
        body.classList.add('admin-dark-theme');
        if (themeIcon) themeIcon.classList.replace('fa-moon', 'fa-sun');
    }
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('admin-dark-theme');
            
            // Update icon
            if (body.classList.contains('admin-dark-theme')) {
                themeIcon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('admin-theme', 'dark');
            } else {
                themeIcon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('admin-theme', 'light');
            }
        });
    }
    
    // Initialize tabs
    const tabLinks = document.querySelectorAll('.admin-tabs-link');
    if (tabLinks.length > 0) {
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs
                tabLinks.forEach(tab => tab.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab content
                const tabContents = document.querySelectorAll('.admin-tabs-content');
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Show the corresponding tab content
                const targetId = this.getAttribute('data-tab');
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
                
                // Save active tab to localStorage if id is provided
                const tabsContainer = this.closest('.admin-tabs');
                if (tabsContainer && tabsContainer.id) {
                    localStorage.setItem(`admin-active-tab-${tabsContainer.id}`, targetId);
                }
            });
        });
        
        // Check for saved active tab
        const tabsContainers = document.querySelectorAll('.admin-tabs');
        tabsContainers.forEach(container => {
            if (container.id) {
                const savedTabId = localStorage.getItem(`admin-active-tab-${container.id}`);
                if (savedTabId) {
                    const savedTabLink = container.querySelector(`.admin-tabs-link[data-tab="${savedTabId}"]`);
                    if (savedTabLink) {
                        savedTabLink.click();
                        return;
                    }
                }
            }
            
            // Activate the first tab by default if no saved tab
            const firstTabLink = container.querySelector('.admin-tabs-link');
            if (firstTabLink) {
                firstTabLink.click();
            }
        });
    }
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.admin-delete-confirm');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm') || 'هل أنت متأكد من أنك تريد حذف هذا العنصر؟')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.admin-alert');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    }
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.style.position = 'relative';
        tooltip.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'admin-tooltip';
            tooltipEl.textContent = tooltipText;
            this.appendChild(tooltipEl);
            
            // Remove tooltip on mouse leave
            tooltip.addEventListener('mouseleave', function() {
                if (tooltipEl.parentNode === this) {
                    this.removeChild(tooltipEl);
                }
            });
        });
    });

    // User dropdown menu
    const userDropdown = document.querySelector('.admin-user-dropdown');
    if (userDropdown) {
        const dropdownToggle = userDropdown.querySelector('.admin-user-dropdown-toggle');
        const dropdownMenu = userDropdown.querySelector('.admin-user-dropdown-menu');
        
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
});




