// AlwaniCTF - Main JavaScript

// Toggle Mobile Menu
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const btn = document.querySelector('.mobile-menu-btn');
    if (menu) {
        menu.classList.toggle('active');
        document.body.classList.toggle('menu-open');
        
        // تحديث أيقونة الزر
        if (btn) {
            btn.textContent = menu.classList.contains('active') ? '✕' : '☰';
        }
    }
}

// إغلاق القائمة عند النقر على رابط
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuLinks = document.querySelectorAll('.mobile-menu a');
    mobileMenuLinks.forEach(link => {
        link.addEventListener('click', function() {
            const menu = document.getElementById('mobileMenu');
            const btn = document.querySelector('.mobile-menu-btn');
            if (menu && menu.classList.contains('active')) {
                menu.classList.remove('active');
                document.body.classList.remove('menu-open');
                if (btn) btn.textContent = '☰';
            }
        });
    });
});

// Theme Toggle
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    document.cookie = 'theme=' + newTheme + '; path=/; max-age=' + (365 * 24 * 60 * 60);
    
    // Update theme icon
    updateThemeIcon(newTheme);
    
    // Add animation class
    document.body.classList.add('theme-transition');
    setTimeout(() => {
        document.body.classList.remove('theme-transition');
    }, 500);
}

function updateThemeIcon(theme) {
    const icons = document.querySelectorAll('.theme-icon');
    icons.forEach(icon => {
        icon.textContent = theme === 'dark' ? '🌙' : '☀️';
    });
}

// Language Menu Toggle
function toggleLangMenu() {
    const menu = document.getElementById('langMenu');
    if (menu) {
        menu.classList.toggle('active');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    // Close language menu
    const langDropdown = document.querySelector('.lang-dropdown');
    const langMenu = document.getElementById('langMenu');
    
    if (langDropdown && langMenu && !langDropdown.contains(e.target)) {
        langMenu.classList.remove('active');
    }
    
    // Close modals when clicking overlay
    if (e.target.classList.contains('modal-overlay')) {
        const modalId = e.target.id;
        if (modalId) {
            closeModal(modalId);
        } else {
            e.target.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }
});

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Reset any previous scroll inside modal
    modal.scrollTop = 0;
    const content = modal.querySelector('.modal-content, .modal');
    if (content) content.scrollTop = 0;

    // IMPORTANT: body has page-transition transform classes which can break position:fixed
    // Lock scroll in an iOS-safe way and neutralize transforms while modal is open.
    const scrollY = window.scrollY || window.pageYOffset || 0;
    document.body.dataset.scrollY = String(scrollY);
    document.body.classList.add('modal-open');

    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollY}px`;
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.width = '100%';

    modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }

    const scrollY = parseInt(document.body.dataset.scrollY || '0', 10) || 0;
    document.body.classList.remove('modal-open');

    document.body.style.overflow = 'auto';
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.body.style.width = '';
    delete document.body.dataset.scrollY;

    window.scrollTo(0, scrollY);
}

// Submit Flag
function submitFlag(challengeId) {
    const flagInput = document.getElementById('flag-input-' + challengeId);
    if (!flagInput) return;
    
    const flag = flagInput.value.trim();
    
    if (!flag) {
        showAlert('error', 'الرجاء إدخال الفلاج');
        return;
    }
    
    // AJAX request to submit flag
    fetch('submit_flag.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'challenge_id=' + challengeId + '&flag=' + encodeURIComponent(flag)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'مبروك! حصلت على ' + data.points + ' نقطة');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('error', data.message || 'الفلاج غير صحيح');
        }
    })
    .catch(error => {
        showAlert('error', 'حدث خطأ، الرجاء المحاولة مرة أخرى');
    });
}

// Show Alert
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type + ' animate-slide-down';
    alert.textContent = message;
    
    // Insert after navbar
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(alert, mainContent.firstChild);
    }
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alert.classList.add('animate-fade-out');
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// متغيرات الفلترة الحالية
var currentFilters = {
    category: 'all',
    difficulty: 'all',
    search: ''
};

// تطبيق جميع الفلاتر معاً
function applyFilters() {
    const cards = document.querySelectorAll('.challenge-card');
    let visibleIndex = 0;
    
    cards.forEach((card) => {
        const matchCategory = currentFilters.category === 'all' || card.dataset.category === currentFilters.category;
        const matchDifficulty = currentFilters.difficulty === 'all' || card.dataset.difficulty === currentFilters.difficulty;
        
        // البحث في العنوان
        let matchSearch = true;
        if (currentFilters.search) {
            const title = card.querySelector('.card-title');
            matchSearch = title && title.textContent.toLowerCase().includes(currentFilters.search);
        }
        
        if (matchCategory && matchDifficulty && matchSearch) {
            card.style.display = 'block';
            card.style.animationDelay = (visibleIndex * 0.03) + 's';
            card.classList.add('animate-scale');
            visibleIndex++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // عرض رسالة إذا لم يكن هناك نتائج
    updateEmptyState(visibleIndex === 0);
}

// عرض/إخفاء رسالة "لا توجد نتائج"
function updateEmptyState(isEmpty) {
    let emptyMsg = document.getElementById('filter-empty-state');
    
    if (isEmpty) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('div');
            emptyMsg.id = 'filter-empty-state';
            emptyMsg.className = 'empty-state';
            emptyMsg.innerHTML = '<div class="empty-icon">🔍</div><h3>لا توجد تحديات مطابقة</h3><p>جرب تغيير معايير البحث أو الفلترة</p>';
            const grid = document.querySelector('.grid.grid-3');
            if (grid) grid.parentNode.insertBefore(emptyMsg, grid.nextSibling);
        }
        emptyMsg.style.display = 'block';
    } else {
        if (emptyMsg) emptyMsg.style.display = 'none';
    }
}

// Filter Challenges by Category
function filterChallenges(category) {
    currentFilters.category = category;
    
    const buttons = document.querySelectorAll('.filter-btn.category-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    applyFilters();
}

// Filter by Difficulty
function filterByDifficulty(difficulty) {
    currentFilters.difficulty = difficulty;
    
    const buttons = document.querySelectorAll('.filter-btn.difficulty-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    applyFilters();
}

// Search Challenges
function searchChallenges() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    currentFilters.search = searchInput.value.toLowerCase().trim();
    applyFilters();
}

// Typing Effect
function typeWriter(element, text, speed = 100) {
    if (!element) return;
    
    let i = 0;
    element.innerHTML = '';
    
    function type() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    type();
}

// Initialize Animations (Simplified - less aggressive)
function initAnimations() {
    // Add fade animation to elements with data-animate attribute
    const animatedElements = document.querySelectorAll('[data-animate]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const animationType = entry.target.dataset.animate;
                entry.target.classList.add('animate-' + animationType);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    animatedElements.forEach(el => observer.observe(el));
    
    // Note: Removed card-3d effect as it was too aggressive
}

// Initialize Page
document.addEventListener('DOMContentLoaded', function() {
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    
    // Activate page enter animation
    document.body.classList.remove('page-enter');
    document.body.classList.add('page-enter-active');
    
    // Initialize animations
    initAnimations();
    
    // Typing effect on hero
    const heroSubtitle = document.querySelector('.hero-subtitle');
    if (heroSubtitle) {
        const texts = ['Capture The Flag', 'Hack The Planet', 'Pwn Everything', 'Level Up Skills'];
        let textIndex = 0;
        
        function cycleText() {
            typeWriter(heroSubtitle, texts[textIndex], 80);
            textIndex = (textIndex + 1) % texts.length;
        }
        
        cycleText();
        setInterval(cycleText, 4000);
    }
    
    // Auto-hide alerts with animation
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('animate-fade-out');
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Note: Removed ripple effect from all buttons as it was too aggressive
    // Only apply ripple to specific buttons that need it
});

// Confirm Delete
function confirmDelete(message) {
    return confirm(message || 'هل أنت متأكد من الحذف؟');
}

// Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('success', 'تم النسخ!');
    }).catch(() => {
        showAlert('error', 'فشل النسخ');
    });
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const target = document.querySelector(targetId);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
