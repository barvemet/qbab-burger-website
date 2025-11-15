/* ========================================
   Q-BAB BURGER - ANIMATION CONTROLLER
   Handles scroll animations, parallax, and interactions
   ======================================== */

(function() {
    'use strict';

    // ========================================
    // SCROLL REVEAL ANIMATION
    // ========================================
    function initScrollReveal() {
        const revealElements = document.querySelectorAll('.scroll-reveal');

        if (revealElements.length === 0) return;

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        revealElements.forEach(el => {
            revealObserver.observe(el);
        });
    }

    // ========================================
    // PARALLAX EFFECT
    // ========================================
    function initParallax() {
        const parallaxElements = document.querySelectorAll('.parallax');

        if (parallaxElements.length === 0) return;

        let ticking = false;

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;

                    parallaxElements.forEach(el => {
                        const speed = el.dataset.speed || 0.5;
                        const yPos = -(scrolled * speed);
                        el.style.transform = `translateY(${yPos}px)`;
                    });

                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    // ========================================
    // BUTTON RIPPLE EFFECT
    // ========================================
    function initRippleButtons() {
        const rippleButtons = document.querySelectorAll('.btn-ripple');

        rippleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    width: 20px;
                    height: 20px;
                    background: rgba(255, 255, 255, 0.6);
                    border-radius: 50%;
                    left: ${x}px;
                    top: ${y}px;
                    transform: translate(-50%, -50%) scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                `;

                this.appendChild(ripple);

                setTimeout(() => ripple.remove(), 600);
            });
        });
    }

    // ========================================
    // SMOOTH MOUSE PARALLAX FOR CARDS
    // ========================================
    function initMouseParallax() {
        const cards = document.querySelectorAll('.parallax-card');

        cards.forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;

                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
            });
        });
    }

    // ========================================
    // ANIMATED COUNTER
    // ========================================
    function animateCounter(element, target, duration = 2000) {
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = Math.floor(target);
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    }

    function initCounters() {
        const counters = document.querySelectorAll('.counter');

        if (counters.length === 0) return;

        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.target);
                    animateCounter(entry.target, target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => {
            counterObserver.observe(counter);
        });
    }

    // ========================================
    // STAGGER ANIMATION FOR LISTS
    // ========================================
    function initStaggerAnimation() {
        const staggerContainers = document.querySelectorAll('.stagger-container');

        staggerContainers.forEach(container => {
            const items = container.querySelectorAll('.stagger-item');

            items.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
                item.classList.add('animate-fadeInUp');
            });
        });
    }

    // ========================================
    // FLOATING ELEMENTS
    // ========================================
    function initFloatingElements() {
        const floatingElements = document.querySelectorAll('.float-element');

        floatingElements.forEach((el, index) => {
            el.style.animationDelay = `${index * 0.2}s`;
            el.style.animationDuration = `${3 + (index * 0.5)}s`;
        });
    }

    // ========================================
    // CURSOR GLOW EFFECT (Optional - Performance heavy)
    // ========================================
    function initCursorGlow() {
        if (window.innerWidth < 768) return; // Disable on mobile

        const glow = document.createElement('div');
        glow.className = 'cursor-glow';
        glow.style.cssText = `
            position: fixed;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,107,53,0.1) 0%, transparent 70%);
            pointer-events: none;
            z-index: 9999;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s ease;
            opacity: 0;
        `;
        document.body.appendChild(glow);

        document.addEventListener('mousemove', (e) => {
            glow.style.left = e.clientX + 'px';
            glow.style.top = e.clientY + 'px';
            glow.style.opacity = '1';
        });

        document.addEventListener('mouseleave', () => {
            glow.style.opacity = '0';
        });
    }

    // ========================================
    // PAGE TRANSITION
    // ========================================
    function initPageTransition() {
        // Fade in on load
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.5s ease';

        window.addEventListener('load', () => {
            document.body.style.opacity = '1';
        });

        // Fade out on navigation (optional)
        const links = document.querySelectorAll('a:not([target="_blank"])');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.hostname === window.location.hostname) {
                    const href = this.href;
                    e.preventDefault();

                    document.body.style.opacity = '0';
                    setTimeout(() => {
                        window.location.href = href;
                    }, 300);
                }
            });
        });
    }

    // ========================================
    // IMAGE LAZY LOAD WITH FADE
    // ========================================
    function initLazyImages() {
        const lazyImages = document.querySelectorAll('img[data-src]');

        if (lazyImages.length === 0) return;

        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.style.opacity = '0';
                    img.style.transition = 'opacity 0.5s ease';

                    img.onload = () => {
                        img.style.opacity = '1';
                        img.classList.add('loaded');
                    };

                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    }

    // ========================================
    // INITIALIZE ALL ANIMATIONS
    // ========================================
    function init() {
        // Check if animations are enabled (respect user preferences)
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (prefersReducedMotion) {
            return;
        }

        initScrollReveal();
        initParallax();
        initRippleButtons();
        initMouseParallax();
        initCounters();
        initStaggerAnimation();
        initFloatingElements();
        initLazyImages();

        // Optional: Enable cursor glow (can be performance heavy)
        // initCursorGlow();

        // Optional: Enable page transitions
        // initPageTransition();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for external use
    window.QBabAnimations = {
        animateCounter,
        initScrollReveal,
        initParallax
    };

})();
