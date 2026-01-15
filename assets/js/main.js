/**
 * MARIANCONNECT - Main JavaScript
 * Handles all public website interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ==========================================
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    
    // ==========================================
    // BACK TO TOP BUTTON
    // ==========================================
    
    const backToTop = document.createElement('button');
    backToTop.id = 'backToTop';
    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTop.className = 'back-to-top';
    backToTop.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: var(--color-primary, #003f87);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: all 0.3s;
        z-index: 999;
    `;
    document.body.appendChild(backToTop);
    
    // Show/hide on scroll
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 500) {
            backToTop.style.display = 'flex';
        } else {
            backToTop.style.display = 'none';
        }
    });
    
    // Scroll to top
    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    backToTop.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.1)';
    });
    
    backToTop.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
    
    
    // ==========================================
    // STICKY HEADER ON SCROLL
    // ==========================================
    
    const header = document.querySelector('.site-header');
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (header) {
            if (currentScroll > 100) {
                header.classList.add('sticky');
            } else {
                header.classList.remove('sticky');
            }
            
            // Hide header on scroll down, show on scroll up
            if (currentScroll > lastScroll && currentScroll > 500) {
                header.classList.add('hidden');
            } else {
                header.classList.remove('hidden');
            }
        }
        
        lastScroll = currentScroll;
    });
    
    
    // ==========================================
    // IMAGE LAZY LOADING
    // ==========================================
    
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
    
    
    // ==========================================
    // READ MORE / LESS TOGGLE
    // ==========================================
    
    document.querySelectorAll('.read-more-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const content = this.previousElementSibling;
            const isExpanded = content.classList.contains('expanded');
            
            if (isExpanded) {
                content.classList.remove('expanded');
                this.textContent = 'Read More';
            } else {
                content.classList.add('expanded');
                this.textContent = 'Read Less';
            }
        });
    });
    
    
    // ==========================================
    // TABS FUNCTIONALITY
    // ==========================================
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabGroup = this.closest('.tabs');
            const targetId = this.dataset.tab;
            
            // Remove active from all tabs and contents
            tabGroup.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            tabGroup.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active to clicked tab and content
            this.classList.add('active');
            document.getElementById(targetId)?.classList.add('active');
        });
    });
    
    
    // ==========================================
    // ACCORDION FUNCTIONALITY
    // ==========================================
    
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', function() {
            const item = this.parentElement;
            const content = item.querySelector('.accordion-content');
            const isActive = item.classList.contains('active');
            
            // Close all accordions in the same group
            const group = item.closest('.accordion-group');
            if (group) {
                group.querySelectorAll('.accordion-item').forEach(i => {
                    i.classList.remove('active');
                    i.querySelector('.accordion-content').style.maxHeight = null;
                });
            }
            
            // Toggle current accordion
            if (!isActive) {
                item.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });
    });
    
    
    // ==========================================
    // MODAL/LIGHTBOX FUNCTIONALITY
    // ==========================================
    
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            modal?.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(closer => {
        closer.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal?.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
    
    // Close modal on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    });
    
    
    // ==========================================
    // GALLERY/LIGHTBOX FOR IMAGES
    // ==========================================
    
    document.querySelectorAll('.gallery-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const imgSrc = this.querySelector('img')?.src || this.dataset.image;
            if (!imgSrc) return;
            
            const lightbox = document.createElement('div');
            lightbox.className = 'image-lightbox';
            lightbox.innerHTML = `
                <div class="lightbox-overlay"></div>
                <div class="lightbox-content">
                    <button class="lightbox-close">&times;</button>
                    <img src="${imgSrc}" alt="">
                </div>
            `;
            lightbox.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            
            document.body.appendChild(lightbox);
            document.body.style.overflow = 'hidden';
            
            lightbox.addEventListener('click', function(e) {
                if (e.target.classList.contains('lightbox-overlay') || 
                    e.target.classList.contains('lightbox-close')) {
                    this.remove();
                    document.body.style.overflow = '';
                }
            });
        });
    });
    
    
    // ==========================================
    // FORM VALIDATION ENHANCEMENT
    // ==========================================
    
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Check required fields
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Show error message
                    let error = field.nextElementSibling;
                    if (!error || !error.classList.contains('error-message')) {
                        error = document.createElement('span');
                        error.className = 'error-message';
                        error.textContent = 'This field is required';
                        error.style.color = '#dc3545';
                        error.style.fontSize = '0.875rem';
                        field.parentNode.insertBefore(error, field.nextSibling);
                    }
                } else {
                    field.classList.remove('error');
                    const error = field.nextElementSibling;
                    if (error?.classList.contains('error-message')) {
                        error.remove();
                    }
                }
            });
            
            // Email validation
            this.querySelectorAll('input[type="email"]').forEach(field => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.value && !emailRegex.test(field.value)) {
                    isValid = false;
                    field.classList.add('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = this.querySelector('.error');
                firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        // Remove error on input
        form.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('input', function() {
                this.classList.remove('error');
                const error = this.nextElementSibling;
                if (error?.classList.contains('error-message')) {
                    error.remove();
                }
            });
        });
    });
    
    
    // ==========================================
    // ANNOUNCEMENT/ALERT BAR AUTO-HIDE
    // ==========================================
    
    const announcementBar = document.querySelector('.announcement-bar');
    if (announcementBar) {
        const closeBtn = announcementBar.querySelector('.close-announcement');
        closeBtn?.addEventListener('click', () => {
            announcementBar.style.transform = 'translateY(-100%)';
            setTimeout(() => announcementBar.remove(), 300);
            localStorage.setItem('announcementClosed', 'true');
        });
        
        // Don't show if previously closed
        if (localStorage.getItem('announcementClosed') === 'true') {
            announcementBar.style.display = 'none';
        }
    }
    
    
    // ==========================================
    // COUNTER ANIMATION (for stats)
    // ==========================================
    
    const animateCounters = () => {
        document.querySelectorAll('.counter').forEach(counter => {
            const target = parseInt(counter.dataset.target);
            const duration = parseInt(counter.dataset.duration) || 2000;
            const increment = target / (duration / 16);
            let current = 0;
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.floor(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target;
                }
            };
            
            updateCounter();
        });
    };
    
    // Trigger counter animation when in view
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                counterObserver.disconnect();
            }
        });
    });
    
    document.querySelectorAll('.counter')?.forEach(counter => {
        counterObserver.observe(counter);
    });
    
    
    // ==========================================
    // COPY TEXT TO CLIPBOARD
    // ==========================================
    
    document.querySelectorAll('[data-copy]').forEach(element => {
        element.addEventListener('click', function() {
            const text = this.dataset.copy;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            });
        });
    });
    
    
    console.log('✅ Main.js loaded successfully');
});
