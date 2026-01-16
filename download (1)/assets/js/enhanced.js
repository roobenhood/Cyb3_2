/**
 * AlwaniCTF - Enhanced JavaScript
 * تفاعلات محسنة وأنيميشن متقدمة
 */

// ============================================
// Toast Notification System
// ============================================

class ToastManager {
    constructor() {
        this.container = null;
        this.init();
    }
    
    init() {
        if (!document.querySelector('.toast-container')) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.querySelector('.toast-container');
        }
    }
    
    show(options) {
        const { type = 'info', title, message, duration = 5000 } = options;
        
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type]}</span>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        this.container.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
        
        return toast;
    }
    
    success(message, title = '') {
        return this.show({ type: 'success', title, message });
    }
    
    error(message, title = '') {
        return this.show({ type: 'error', title, message });
    }
    
    warning(message, title = '') {
        return this.show({ type: 'warning', title, message });
    }
    
    info(message, title = '') {
        return this.show({ type: 'info', title, message });
    }
}

const toast = new ToastManager();

// ============================================
// Scroll Reveal Animations
// ============================================

class ScrollReveal {
    constructor() {
        this.elements = document.querySelectorAll('[data-reveal]');
        this.init();
    }
    
    init() {
        if (this.elements.length === 0) return;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('revealed');
                    }, index * 100);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        this.elements.forEach(el => observer.observe(el));
    }
}

// ============================================
// Smooth Counter Animation
// ============================================

class CounterAnimation {
    constructor(element, target, duration = 2000) {
        this.element = element;
        this.target = target;
        this.duration = duration;
        this.start = 0;
        this.startTime = null;
    }
    
    animate(currentTime) {
        if (!this.startTime) this.startTime = currentTime;
        
        const progress = Math.min((currentTime - this.startTime) / this.duration, 1);
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        
        const current = Math.floor(easeOutQuart * this.target);
        this.element.textContent = current.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame((time) => this.animate(time));
        }
    }
    
    start() {
        requestAnimationFrame((time) => this.animate(time));
    }
}

function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.dataset.counter);
                const counter = new CounterAnimation(entry.target, target);
                counter.start();
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
}

// ============================================
// Navbar Scroll Effect
// ============================================

function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
}

// ============================================
// Smooth Page Transitions
// ============================================

function initPageTransitions() {
    document.body.classList.add('page-loaded');
    
    document.querySelectorAll('a:not([target="_blank"])').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                e.preventDefault();
                document.body.classList.add('page-exit');
                
                setTimeout(() => {
                    window.location.href = href;
                }, 300);
            }
        });
    });
}

// ============================================
// Enhanced Form Validation
// ============================================

function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required], textarea[required]');
        
        inputs.forEach(input => {
            // Add floating label effect
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.parentElement.classList.remove('focused');
                }
                validateInput(input);
            });
            
            input.addEventListener('input', () => {
                if (input.classList.contains('invalid')) {
                    validateInput(input);
                }
            });
        });
        
        form.addEventListener('submit', (e) => {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateInput(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                toast.error('يرجى تصحيح الأخطاء في النموذج');
            }
        });
    });
}

function validateInput(input) {
    const value = input.value.trim();
    let isValid = true;
    let message = '';
    
    // Required check
    if (input.hasAttribute('required') && !value) {
        isValid = false;
        message = 'هذا الحقل مطلوب';
    }
    
    // Email check
    if (input.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'بريد إلكتروني غير صالح';
        }
    }
    
    // Min length check
    if (input.minLength > 0 && value.length < input.minLength) {
        isValid = false;
        message = `الحد الأدنى ${input.minLength} أحرف`;
    }
    
    // Update UI
    const parent = input.parentElement;
    const errorEl = parent.querySelector('.input-error') || document.createElement('span');
    
    if (!isValid) {
        input.classList.add('invalid');
        input.classList.remove('valid');
        errorEl.className = 'input-error';
        errorEl.textContent = message;
        if (!parent.querySelector('.input-error')) {
            parent.appendChild(errorEl);
        }
    } else {
        input.classList.remove('invalid');
        input.classList.add('valid');
        if (parent.querySelector('.input-error')) {
            parent.querySelector('.input-error').remove();
        }
    }
    
    return isValid;
}

// ============================================
// Particle Background Effect
// ============================================

function initParticles() {
    const container = document.querySelector('.hero-particles');
    if (!container) return;
    
    const particleCount = 50;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 8 + 's';
        particle.style.animationDuration = (8 + Math.random() * 4) + 's';
        container.appendChild(particle);
    }
}

// ============================================
// Magnetic Hover Effect
// ============================================

function initMagneticHover() {
    const elements = document.querySelectorAll('.magnetic-hover');
    
    elements.forEach(el => {
        el.addEventListener('mousemove', (e) => {
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            el.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`;
        });
        
        el.addEventListener('mouseleave', () => {
            el.style.transform = 'translate(0, 0)';
        });
    });
}

// ============================================
// Card 3D Tilt Effect
// ============================================

function initCardTilt() {
    const cards = document.querySelectorAll('.card-3d');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
        });
    });
}

// ============================================
// Typing Effect Enhanced
// ============================================

class TypeWriter {
    constructor(element, texts, options = {}) {
        this.element = element;
        this.texts = texts;
        this.speed = options.speed || 80;
        this.deleteSpeed = options.deleteSpeed || 50;
        this.pauseTime = options.pauseTime || 2000;
        this.loop = options.loop !== false;
        this.currentText = 0;
        this.isDeleting = false;
        this.charIndex = 0;
    }
    
    type() {
        const current = this.texts[this.currentText];
        
        if (this.isDeleting) {
            this.element.textContent = current.substring(0, this.charIndex - 1);
            this.charIndex--;
        } else {
            this.element.textContent = current.substring(0, this.charIndex + 1);
            this.charIndex++;
        }
        
        // Add cursor
        if (!this.element.querySelector('.cursor')) {
            const cursor = document.createElement('span');
            cursor.className = 'cursor';
            cursor.textContent = '|';
            cursor.style.animation = 'blink 1s infinite';
            this.element.appendChild(cursor);
        }
        
        let typeSpeed = this.isDeleting ? this.deleteSpeed : this.speed;
        
        if (!this.isDeleting && this.charIndex === current.length) {
            typeSpeed = this.pauseTime;
            this.isDeleting = true;
        } else if (this.isDeleting && this.charIndex === 0) {
            this.isDeleting = false;
            this.currentText = (this.currentText + 1) % this.texts.length;
            typeSpeed = 500;
        }
        
        setTimeout(() => this.type(), typeSpeed);
    }
    
    start() {
        this.type();
    }
}

// ============================================
// Progress Bar Animation
// ============================================

function animateProgressBars() {
    const progressBars = document.querySelectorAll('[data-progress]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.dataset.progress);
                entry.target.style.setProperty('--progress', target + '%');
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    progressBars.forEach(bar => observer.observe(bar));
}

// ============================================
// Smooth Scroll
// ============================================

function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ============================================
// Loading State Manager
// ============================================

const LoadingManager = {
    show(element, message = 'جاري التحميل...') {
        const loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.innerHTML = `
            <div class="loading-spinner"></div>
            <span class="loading-text">${message}</span>
        `;
        element.style.position = 'relative';
        element.appendChild(loader);
    },
    
    hide(element) {
        const loader = element.querySelector('.loading-overlay');
        if (loader) {
            loader.classList.add('fade-out');
            setTimeout(() => loader.remove(), 300);
        }
    }
};

// ============================================
// Keyboard Navigation
// ============================================

function initKeyboardNav() {
    document.addEventListener('keydown', (e) => {
        // ESC to close modals
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal-overlay.active');
            if (modal) {
                if (typeof closeModal === 'function' && modal.id) {
                    closeModal(modal.id);
                } else {
                    modal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            }
        }
    });
}

// ============================================
// Initialize All
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all features
    new ScrollReveal();
    animateCounters();
    initNavbarScroll();
    initFormValidation();
    initParticles();
    initMagneticHover();
    initCardTilt();
    animateProgressBars();
    initSmoothScroll();
    initKeyboardNav();
    
    // Typing effect on hero
    const heroSubtitle = document.querySelector('.hero-subtitle');
    if (heroSubtitle) {
        const texts = ['Capture The Flag', 'Hack The Planet', 'Pwn Everything', 'Level Up Skills'];
        const typewriter = new TypeWriter(heroSubtitle, texts, {
            speed: 100,
            deleteSpeed: 60,
            pauseTime: 2500
        });
        typewriter.start();
    }
    
    // Page load animation
    document.body.classList.add('loaded');
    
    console.log('%c🛡️ AlwaniCTF', 'color: #00ff88; font-size: 24px; font-weight: bold;');
    console.log('%cWelcome, Hacker!', 'color: #00d4ff; font-size: 14px;');
});

// Export for global use
window.toast = toast;
window.LoadingManager = LoadingManager;
