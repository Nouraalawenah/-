// Theme toggle functionality - Single implementation
(function() {
    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Set theme based on saved preference or system preference
    const theme = savedTheme || (prefersDark ? 'dark' : 'light');
    
    // Apply theme
    applyTheme(theme);
    
    // Setup DOM-loaded handlers once
    document.addEventListener('DOMContentLoaded', function() {
        updateThemeIcon(theme);
        setupThemeToggle();
        if (typeof debugThemeToggle === 'function') {
            debugThemeToggle();
        }
    });

    // Function to apply theme to document
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.classList.remove('light', 'dark');
        document.documentElement.classList.add(theme);
        localStorage.setItem('theme', theme);
        
        // Update meta theme-color for mobile browsers
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#121212' : '#ffffff');
        }
        
        // Dispatch event for other scripts that might need to react to theme changes
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    // Function to update theme toggle icon
    function updateThemeIcon(theme) {
        const themeToggle = document.getElementById('theme-toggle');
        if (!themeToggle) return;
        
        const themeIcon = themeToggle.querySelector('i');
        if (!themeIcon) return;
        
        if (theme === 'dark') {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
    }

    // Function to setup theme toggle button
    function setupThemeToggle() {
        const themeToggle = document.getElementById('theme-toggle');
        if (!themeToggle) return;
        
        // Prevent multiple event listeners
        if (themeToggle._hasClickListener) return;
        
        // Optional: Add smooth transition between themes
        const style = document.createElement('style');
        style.textContent = `
            html {
                transition: background-color 0.3s ease, color 0.3s ease;
            }
        `;
        document.head.appendChild(style);
        
        // Toggle theme when the button is clicked
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            applyTheme(newTheme);
            updateThemeIcon(newTheme);
            console.log('Theme toggled to:', newTheme);
        });
        
        // Mark that the click listener is added
        themeToggle._hasClickListener = true;
    }

    // Debug function for theme toggle
    function debugThemeToggle() {
        const themeToggle = document.getElementById('theme-toggle');
        const currentTheme = document.documentElement.getAttribute('data-theme');
        
        console.log('Theme Debug:', {
            currentTheme: currentTheme,
            themeToggleExists: !!themeToggle,
            localStorageTheme: localStorage.getItem('theme'),
            prefersDark: window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
        });
        
        if (themeToggle) {
            console.log('Theme Toggle Element:', {
                hasClickListener: !!themeToggle._hasClickListener,
                icon: themeToggle.querySelector('i') ? themeToggle.querySelector('i').className : 'No icon found'
            });
        }
    }

    // Add smooth transition between themes (already included in setupThemeToggle)
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = `
            html {
                transition: background-color 0.3s ease, color 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    });
})();
