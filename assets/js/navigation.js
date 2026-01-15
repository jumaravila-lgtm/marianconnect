/**
 * MARIANCONNECT - Navigation JavaScript
 * Handles mobile menu, dropdowns, and navigation interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // MOBILE MENU TOGGLE
    // ==========================================
    
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    const body = document.body;
    
    mobileMenuBtn?.addEventListener('click', function() {
        this.classList.toggle('active');
        mobileMenu?.classList.toggle('active');
        body.classList.toggle('menu-open');
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (mobileMenu?.classList.contains('active')) {
            if (!mobileMenu.contains(e.target) && !mobileMenuBtn?.contains(e.target)) {
                mobileMenu.classList.remove('active');
                mobileMenuBtn?.classList.remove('active');
                body.classList.remove('menu-open');
            }
        }
    });
    
    // Close mobile menu on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu?.classList.contains('active')) {
            mobileMenu.classList.remove('active');
            mobileMenuBtn?.classList.remove('active');
            body.classList.remove('menu-open');
        }
    });
    
    
    // ==========================================
    // DROPDOWN MENUS (Desktop)
    // ==========================================
    
    const dropdownTriggers = document.querySelectorAll('.has-dropdown > a');
    
    dropdownTriggers.forEach(trigger => {
        // Desktop: Show on hover
        const parent = trigger.parentElement;
        
        parent.addEventListener('mouseenter', function() {
            if (window.innerWidth > 992) {
                this.classList.add('open');
            }
        });
        
        parent.addEventListener('mouseleave', function() {
            if (window.innerWidth > 992) {
                this.classList.remove('open');
            }
        });
        
        // Mobile: Toggle on click
        trigger.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                e.preventDefault();
                const parent = this.parentElement;
                const isOpen = parent.classList.contains('open');
                
                // Close all other dropdowns
                document.querySelectorAll('.has-dropdown.open').forEach(item => {
                    if (item !== parent) {
                        item.classList.remove('open');
                    }
                });
                
                // Toggle current dropdown
                parent.classList.toggle('open');
            }
        });
    });
    
    
    // ==========================================
    // MEGA MENU (if you have one)
    // ==========================================
    
    const megaMenuTriggers = document.querySelectorAll('.has-mega-menu');
    
    megaMenuTriggers.forEach(trigger => {
        trigger.addEventListener('mouseenter', function() {
            if (window.innerWidth > 992) {
                this.querySelector('.mega-menu')?.classList.add('active');
            }
        });
        
        trigger.addEventListener('mouseleave', function() {
            if (window.innerWidth > 992) {
                this.querySelector('.mega-menu')?.classList.remove('active');
            }
        });
    });
    
    
    // ==========================================
    // SEARCH TOGGLE
    // ==========================================
    
    const searchToggle = document.querySelector('.search-toggle');
    const searchOverlay = document.querySelector('.search-overlay');
    const searchClose = document.querySelector('.search-close');
    
    searchToggle?.addEventListener('click', function(e) {
        e.preventDefault();
        searchOverlay?.classList.add('active');
        searchOverlay?.querySelector('input')?.focus();
        body.classList.add('search-open');
    });
    
    searchClose?.addEventListener('click', function() {
        searchOverlay?.classList.remove('active');
        body.classList.remove('search-open');
    });
    
    // Close search on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && searchOverlay?.classList.contains('active')) {
            searchOverlay.classList.remove('active');
            body.classList.remove('search-open');
        }
    });
    
    
    // ==========================================
    // ACTIVE NAVIGATION HIGHLIGHT
    // ==========================================
    
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.main-nav a');
    
    navLinks.forEach(link => {
        const linkPath = new URL(link.href).pathname;
        
        if (currentPath === linkPath) {
            link.classList.add('active');
            link.closest('li')?.classList.add('current');
        }
        
        // Also highlight parent menu items
        if (currentPath.includes(linkPath) && linkPath !== '/') {
            link.classList.add('active');
            link.closest('.has-dropdown')?.classList.add('current');
        }
    });
    
    
    // ==========================================
    // BREADCRUMB AUTO-GENERATION (Optional)
    // ==========================================
    
    function generateBreadcrumbs() {
        const breadcrumb = document.querySelector('.breadcrumb');
        if (!breadcrumb) return;
        
        const pathArray = window.location.pathname.split('/').filter(Boolean);
        const homeLink = document.createElement('a');
        homeLink.href = '/';
        homeLink.textContent = 'Home';
        breadcrumb.appendChild(homeLink);
        
        let currentPath = '';
        pathArray.forEach((segment, index) => {
            currentPath += '/' + segment;
            
            const separator = document.createElement('span');
            separator.className = 'separator';
            separator.textContent = '/';
            breadcrumb.appendChild(separator);
            
            if (index === pathArray.length - 1) {
                // Last item - no link
                const span = document.createElement('span');
                span.className = 'current';
                span.textContent = segment.replace(/-/g, ' ').replace('.php', '');
                breadcrumb.appendChild(span);
            } else {
                const link = document.createElement('a');
                link.href = currentPath;
                link.textContent = segment.replace(/-/g, ' ');
                breadcrumb.appendChild(link);
            }
        });
    }
    
    
    // ==========================================
    // STICKY NAVIGATION
    // ==========================================
    
    const navbar = document.querySelector('.navbar');
    let lastScrollTop = 0;
    let scrollTimeout;
    
    window.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        
        scrollTimeout = setTimeout(() => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (navbar) {
                // Add sticky class after scrolling down
                if (scrollTop > 100) {
                    navbar.classList.add('sticky');
                } else {
                    navbar.classList.remove('sticky');
                }
                
                // Hide on scroll down, show on scroll up
                if (scrollTop > lastScrollTop && scrollTop > 300) {
                    navbar.classList.add('hidden');
                } else {
                    navbar.classList.remove('hidden');
                }
            }
            
            lastScrollTop = scrollTop;
        }, 10);
    });
    
    
    // ==========================================
    // SMOOTH SCROLL FOR ANCHOR LINKS IN NAV
    // ==========================================
    
    document.querySelectorAll('.main-nav a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                // Close mobile menu if open
                mobileMenu?.classList.remove('active');
                mobileMenuBtn?.classList.remove('active');
                body.classList.remove('menu-open');
                
                // Calculate offset for sticky header
                const headerHeight = navbar?.offsetHeight || 0;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    
    // ==========================================
    // ACCESSIBILITY: Close dropdowns on TAB
    // ==========================================
    
    document.addEventListener('focusin', function(e) {
        const dropdowns = document.querySelectorAll('.has-dropdown.open');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    });
    
    
    // ==========================================
    // PREVENT BODY SCROLL WHEN MOBILE MENU OPEN
    // ==========================================
    
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (body.classList.contains('menu-open')) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            }
        });
    });
    
    observer.observe(body, {
        attributes: true
    });
    
    
    // ==========================================
    // RESIZE HANDLER (Close mobile menu on resize to desktop)
    // ==========================================
    
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (window.innerWidth > 992) {
                mobileMenu?.classList.remove('active');
                mobileMenuBtn?.classList.remove('active');
                body.classList.remove('menu-open');
                
                // Close all dropdowns
                document.querySelectorAll('.has-dropdown.open').forEach(item => {
                    item.classList.remove('open');
                });
            }
        }, 250);
    });
    
    
    console.log('✅ Navigation.js loaded successfully');
});
