// Improved navbar functionality
function initNavbar() {
    const header = document.querySelector('.main-header');
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const headerRight = document.querySelector('.header-right');
    
    // Scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    
    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            headerRight.classList.toggle('active');
            document.body.classList.toggle('menu-open');
            
            // Toggle aria-expanded attribute for accessibility
            const isExpanded = headerRight.classList.contains('active');
            mobileMenuToggle.setAttribute('aria-expanded', isExpanded);
            
            // Toggle icon between bars and times
            const icon = mobileMenuToggle.querySelector('i');
            if (icon) {
                if (isExpanded) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (headerRight && headerRight.classList.contains('active') && 
            !headerRight.contains(e.target) && 
            !mobileMenuToggle.contains(e.target)) {
            headerRight.classList.remove('active');
            document.body.classList.remove('menu-open');
            
            // Reset icon
            const icon = mobileMenuToggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
            
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
        }
    });
    
    // Completely rewritten active link detection
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.main-nav a');
    
    // First remove active class from all links
    navLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Determine if we're on the home page
    const isHomePage = currentPath === '/' || 
                       currentPath.endsWith('/index.php') || 
                       currentPath === '/service%20project/home-Services/' ||
                       currentPath.endsWith('/service%20project/home-Services/index.php');
    
    // Find the most specific matching link
    let bestMatch = null;
    let bestMatchLength = 0;
    
    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (!linkHref || linkHref === '#') return;
        
        // Check if this is a home link
        const isHomeLink = linkHref === '/' || 
                          linkHref.endsWith('/index.php') || 
                          linkHref === './' ||
                          linkHref === '/service%20project/home-Services/' ||
                          linkHref.endsWith('/service%20project/home-Services/index.php');
        
        // For home page, only match home links
        if (isHomePage) {
            if (isHomeLink) {
                bestMatch = link;
                bestMatchLength = linkHref.length;
            }
        } 
        // For other pages, find the most specific match
        else if (!isHomeLink && currentPath.includes(linkHref)) {
            // Use the longest match as the most specific
            if (linkHref.length > bestMatchLength) {
                bestMatch = link;
                bestMatchLength = linkHref.length;
            }
        }
    });
    
    // Apply active class to the best match only
    if (bestMatch) {
        bestMatch.classList.add('active');
        console.log('Active link set to:', bestMatch.getAttribute('href'));
    } else if (isHomePage) {
        // If no match found but we're on home page, try to find and activate the home link
        const homeLink = document.querySelector('.main-nav a[href="/"], .main-nav a[href="./"], .main-nav a[href="index.php"], .main-nav a[href="/service%20project/home-Services/"], .main-nav a[href="/service%20project/home-Services/index.php"]');
        if (homeLink) {
            homeLink.classList.add('active');
            console.log('Home link activated as fallback');
        }
    }
}

// Initialize navbar when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initNavbar();
    // Other initializations...
});

// Simplified language dropdown functionality
function initLanguageDropdown() {
    const dropdowns = document.querySelectorAll('.language-dropdown');
    
    dropdowns.forEach(dropdown => {
        const button = dropdown.querySelector('.current-lang');
        const menu = dropdown.querySelector('.lang-options');
        
        if (!button || !menu) return;
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle menu visibility
            const isVisible = menu.style.display === 'block';
            menu.style.display = isVisible ? 'none' : 'block';
            button.setAttribute('aria-expanded', !isVisible);
            
            // Log for debugging
            console.log('Language dropdown clicked', {
                isVisible: isVisible,
                menuItems: menu.querySelectorAll('.lang-option').length
            });
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                menu.style.display = 'none';
                button.setAttribute('aria-expanded', 'false');
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing language dropdown');
    initLanguageDropdown();
});

// دالة لتهيئة التبويبات
function initTabs() {
    const tabLinks = document.querySelectorAll('.tab-link');
    if (tabLinks.length === 0) return;
    
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // إزالة الفئة النشطة من جميع الروابط والمحتويات
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // إضافة الفئة النشطة للرابط المحدد
            this.classList.add('active');
            
            // إظهار المحتوى المقابل
            const tabId = this.getAttribute('data-tab');
            const tabContent = document.getElementById(tabId);
            if (tabContent) {
                tabContent.classList.add('active');
            }
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize language dropdown
    initLanguageDropdown();
    
    // Other initializations...
    initNavbar();
});

// Add this at the end of your script.js file
console.log('Script loaded');

// Debug function for language dropdown
function debugLanguageDropdown() {
    const dropdowns = document.querySelectorAll('.language-dropdown');
    console.log('Language dropdowns found:', dropdowns.length);
    
    dropdowns.forEach((dropdown, index) => {
        const button = dropdown.querySelector('.current-lang');
        const options = dropdown.querySelector('.lang-options');
        
        console.log(`Dropdown ${index+1}:`, {
            button: button ? 'Found' : 'Missing',
            options: options ? 'Found' : 'Missing',
            optionsCount: options ? options.querySelectorAll('.lang-option').length : 0
        });
    });
}

// Call debug function when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    debugLanguageDropdown();
});

















