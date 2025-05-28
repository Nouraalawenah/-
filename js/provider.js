function openPopupWindow(url, title, width = 800, height = 600) {
    const left = (window.innerWidth - width) / 2;
    const top = (window.innerHeight - height) / 2;
    
    window.open(url, title, 
        `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
    
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on desktop
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarToggleMobile = document.getElementById('sidebar-toggle-mobile');
    const sidebar = document.querySelector('.provider-sidebar');
    const body = document.body;
    
    // Check if sidebar state is saved in localStorage
    const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    // Apply saved state on page load
    if (isSidebarCollapsed) {
        body.classList.add('sidebar-collapsed');
        
        // Update toggle icon if RTL
        if (document.dir === 'rtl') {
            const toggleIcon = sidebarToggle.querySelector('i');
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
            }
        } else {
            const toggleIcon = sidebarToggle.querySelector('i');
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            }
        }
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            body.classList.toggle('sidebar-collapsed');
            
            // Save state to localStorage
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            // Update toggle icon if RTL
            if (document.dir === 'rtl') {
                const toggleIcon = sidebarToggle.querySelector('i');
                if (toggleIcon) {
                    toggleIcon.classList.toggle('fa-chevron-right');
                    toggleIcon.classList.toggle('fa-chevron-left');
                }
            } else {
                const toggleIcon = sidebarToggle.querySelector('i');
                if (toggleIcon) {
                    toggleIcon.classList.toggle('fa-chevron-left');
                    toggleIcon.classList.toggle('fa-chevron-right');
                }
            }
        });
    }
    
    if (sidebarToggleMobile) {
        sidebarToggleMobile.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            body.classList.toggle('sidebar-mobile-open');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 992 && 
            sidebar.classList.contains('active') && 
            !sidebar.contains(event.target) && 
            event.target !== sidebarToggleMobile) {
            sidebar.classList.remove('active');
            body.classList.remove('sidebar-mobile-open');
        }
    });
    
    // Toggle theme
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle ? themeToggle.querySelector('i') : null;
    
    // Check if dark mode is enabled in localStorage
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    
    // Apply dark mode if enabled
    if (isDarkMode) {
        body.classList.add('dark-mode');
        if (themeIcon) {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }
    }
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            
            // Update localStorage
            const isDarkModeNow = body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkModeNow);
            
            // Toggle icon
            if (themeIcon) {
                if (isDarkModeNow) {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                } else {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                }
            }
        });
    }
    
    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            
            if (href !== "#") {
                e.preventDefault();
                
                const targetElement = document.querySelector(href);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // إضافة معالجة للروابط التي تحتاج لفتح نوافذ منبثقة
    const popupLinks = document.querySelectorAll('[data-popup="true"]');
    
    popupLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const title = this.getAttribute('data-title') || 'Popup';
            const width = parseInt(this.getAttribute('data-width') || 800);
            const height = parseInt(this.getAttribute('data-height') || 600);
            
            openPopupWindow(url + (url.includes('?') ? '&' : '?') + 'popup=true', title, width, height);
        });
    });
});


