/**
 * MARIANCONNECT - Animations JavaScript
 * Handles scroll animations, transitions, and visual effects
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // SCROLL ANIMATIONS (Fade In on Scroll)
    // ==========================================
    
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll, [data-aos]');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    // Optionally unobserve after animation
                    if (!entry.target.dataset.repeat) {
                        observer.unobserve(entry.target);
                    }
                } else if (entry.target.dataset.repeat) {
                    entry.target.classList.remove('animated');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        });
        
        elements.forEach(el => observer.observe(el));
    };
    
    animateOnScroll();
    
    
    // ==========================================
    // COUNTER ANIMATION
    // ==========================================
    
    function animateCounter(element) {
        const target = parseInt(element.dataset.target || element.textContent);
        const duration = parseInt(element.dataset.duration) || 2000;
        const step = target / (duration / 16); // 60fps
        let current = 0;
        
        const updateCounter = () => {
            current += step;
            if (current < target) {
                element.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target.toLocaleString();
            }
        };
        
        updateCounter();
    }
    
    // Observe counters
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.dataset.animated) {
                animateCounter(entry.target);
                entry.target.dataset.animated = 'true';
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.counter, [data-counter]').forEach(counter => {
        counterObserver.observe(counter);
    });
    
    
    // ==========================================
    // PROGRESS BAR ANIMATION
    // ==========================================
    
    const progressBars = document.querySelectorAll('.progress-bar');
    
    const progressObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bar = entry.target;
                const width = bar.dataset.width || bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
                progressObserver.unobserve(bar);
            }
        });
    }, { threshold: 0.5 });
    
    progressBars.forEach(bar => progressObserver.observe(bar));
    
    
    // ==========================================
    // STAGGER ANIMATION (Children appear one by one)
    // ==========================================
    
    const staggerContainers = document.querySelectorAll('[data-stagger]');
    
    const staggerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const children = entry.target.children;
                const delay = parseInt(entry.target.dataset.stagger) || 100;
                
                Array.from(children).forEach((child, index) => {
                    setTimeout(() => {
                        child.classList.add('animated');
                    }, index * delay);
                });
                
                staggerObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });
    
    staggerContainers.forEach(container => staggerObserver.observe(container));
    
    
    // ==========================================
    // PARALLAX EFFECT
    // ==========================================
    
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    function updateParallax() {
        const scrolled = window.pageYOffset;
        
        parallaxElements.forEach(element => {
            const speed = parseFloat(element.dataset.parallax) || 0.5;
            const yPos = -(scrolled * speed);
            element.style.transform = `translate3d(0, ${yPos}px, 0)`;
        });
    }
    
    if (parallaxElements.length > 0) {
        window.addEventListener('scroll', updateParallax);
        updateParallax();
    }
    
    
    // ==========================================
    // TYPING EFFECT
    // ==========================================
    
    class TypeWriter {
        constructor(element, words, wait = 3000) {
            this.element = element;
            this.words = words;
            this.txt = '';
            this.wordIndex = 0;
            this.wait = parseInt(wait);
            this.isDeleting = false;
            this.type();
        }
        
        type() {
            const current = this.wordIndex % this.words.length;
            const fullTxt = this.words[current];
            
            if (this.isDeleting) {
                this.txt = fullTxt.substring(0, this.txt.length - 1);
            } else {
                this.txt = fullTxt.substring(0, this.txt.length + 1);
            }
            
            this.element.textContent = this.txt;
            
            let typeSpeed = 100;
            
            if (this.isDeleting) {
                typeSpeed /= 2;
            }
            
            if (!this.isDeleting && this.txt === fullTxt) {
                typeSpeed = this.wait;
                this.isDeleting = true;
            } else if (this.isDeleting && this.txt === '') {
                this.isDeleting = false;
                this.wordIndex++;
                typeSpeed = 500;
            }
            
            setTimeout(() => this.type(), typeSpeed);
        }
    }
    
    // Initialize typewriter
    const typeElements = document.querySelectorAll('[data-typewriter]');
    typeElements.forEach(element => {
        const words = JSON.parse(element.dataset.typewriter);
        new TypeWriter(element, words);
    });
    
    
    // ==========================================
    // REVEAL ANIMATION (Slide up with fade)
    // ==========================================
    
    function reveal() {
        const reveals = document.querySelectorAll('.reveal');
        
        reveals.forEach(element => {
            const windowHeight = window.innerHeight;
            const elementTop = element.getBoundingClientRect().top;
            const revealPoint = 150;
            
            if (elementTop < windowHeight - revealPoint) {
                element.classList.add('active');
            } else {
                element.classList.remove('active');
            }
        });
    }
    
    window.addEventListener('scroll', reveal);
    reveal(); // Initial check
    
    
    // ==========================================
    // IMAGE ZOOM ON HOVER
    // ==========================================
    
    const zoomImages = document.querySelectorAll('.zoom-on-hover');
    
    zoomImages.forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    
    // ==========================================
    // TILT EFFECT (3D Card Tilt)
    // ==========================================
    
    const tiltElements = document.querySelectorAll('[data-tilt]');
    
    tiltElements.forEach(element => {
        element.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
        });
    });
    
    
    // ==========================================
    // RIPPLE EFFECT ON BUTTONS
    // ==========================================
    
    const rippleButtons = document.querySelectorAll('.btn-ripple, [data-ripple]');
    
    rippleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    
    // ==========================================
    // SMOOTH NUMBER INCREMENT
    // ==========================================
    
    function smoothIncrement(element, start, end, duration) {
        let startTime = null;
        
        function animate(currentTime) {
            if (!startTime) startTime = currentTime;
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }
        
        requestAnimationFrame(animate);
    }
    
    
    // ==========================================
    // PULSE ANIMATION
    // ==========================================
    
    const pulseElements = document.querySelectorAll('.pulse, [data-pulse]');
    
    pulseElements.forEach(element => {
        setInterval(() => {
            element.classList.add('pulsing');
            setTimeout(() => element.classList.remove('pulsing'), 1000);
        }, 3000);
    });
    
    
    // ==========================================
    // LOADING ANIMATION
    // ==========================================
    
    window.addEventListener('load', function() {
        const loader = document.querySelector('.page-loader');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => loader.style.display = 'none', 500);
        }
        
        // Trigger entrance animations
        document.body.classList.add('loaded');
    });
    
    
    // ==========================================
    // ADD DEFAULT ANIMATION STYLES
    // ==========================================
    
    const style = document.createElement('style');
    style.textContent = `
        .animate-on-scroll,
        [data-aos] {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .animate-on-scroll.animated,
        [data-aos].animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.6s ease;
        }
        
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        .zoom-on-hover {
            transition: transform 0.3s ease;
        }
        
        [data-tilt] {
            transition: transform 0.3s ease;
        }
        
        .progress-bar {
            transition: width 1s ease;
        }
        
        .pulsing {
            animation: pulse-keyframe 1s ease;
        }
        
        @keyframes pulse-keyframe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    `;
    document.head.appendChild(style);
    
    
    console.log('✅ Animations.js loaded successfully');
});
