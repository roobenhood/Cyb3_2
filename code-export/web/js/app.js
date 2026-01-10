/**
 * Main Application JavaScript
 */

// API Base URL - غير هذا إلى رابط السيرفر الخاص بك
const API_BASE_URL = 'https://your-domain.com/api';

// =====================================================
// Utility Functions
// =====================================================

// Fetch wrapper with error handling
async function apiRequest(endpoint, options = {}) {
    try {
        const token = localStorage.getItem('token');
        
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const response = await fetch(`${API_BASE_URL}${endpoint}`, {
            ...options,
            headers
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'حدث خطأ');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Format price
function formatPrice(price) {
    return `$${parseFloat(price).toFixed(2)}`;
}

// =====================================================
// Authentication
// =====================================================

// Check if user is logged in
function isLoggedIn() {
    return !!localStorage.getItem('token');
}

// Get current user
function getCurrentUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
}

// Login
async function login(email, password) {
    const data = await apiRequest('/login.php', {
        method: 'POST',
        body: JSON.stringify({ email, password })
    });
    
    if (data.success) {
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        return data;
    }
    
    throw new Error(data.message);
}

// Register
async function register(name, email, password) {
    const data = await apiRequest('/register.php', {
        method: 'POST',
        body: JSON.stringify({ name, email, password })
    });
    
    if (data.success) {
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        return data;
    }
    
    throw new Error(data.message);
}

// Logout
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'index.html';
}

// =====================================================
// Courses
// =====================================================

// Load featured courses
async function loadFeaturedCourses() {
    const container = document.getElementById('featuredCourses');
    if (!container) return;
    
    try {
        const data = await apiRequest('/courses.php?featured=1&limit=4');
        
        if (data.success && data.courses.length > 0) {
            container.innerHTML = data.courses.map(course => `
                <div class="course-card">
                    <div class="course-image">
                        <i class="fas fa-play-circle"></i>
                        ${course.is_featured ? '<span class="course-badge">مميز</span>' : ''}
                    </div>
                    <div class="course-content">
                        <h3><a href="course.html?id=${course.id}">${course.title}</a></h3>
                        <div class="course-instructor">
                            <img src="images/default-avatar.png" alt="${course.instructor_name}">
                            <span>${course.instructor_name || 'المدرب'}</span>
                        </div>
                        <div class="course-meta">
                            <div class="course-rating">
                                <i class="fas fa-star"></i>
                                <span>${course.avg_rating || '4.5'}</span>
                            </div>
                            <div class="course-price">${formatPrice(course.price)}</div>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            // عرض كورسات تجريبية
            container.innerHTML = getDemoCourses();
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        container.innerHTML = getDemoCourses();
    }
}

// Demo courses for testing
function getDemoCourses() {
    const demoCourses = [
        { id: 1, title: 'كورس Flutter الشامل', instructor: 'أحمد محمد', price: 49.99, rating: 4.8 },
        { id: 2, title: 'تطوير واجهات ويب بـ React', instructor: 'سارة أحمد', price: 39.99, rating: 4.7 },
        { id: 3, title: 'تصميم UI/UX احترافي', instructor: 'محمد علي', price: 29.99, rating: 4.9 },
        { id: 4, title: 'التسويق الرقمي', instructor: 'فاطمة حسن', price: 44.99, rating: 4.6 }
    ];
    
    return demoCourses.map(course => `
        <div class="course-card">
            <div class="course-image">
                <i class="fas fa-play-circle"></i>
                <span class="course-badge">مميز</span>
            </div>
            <div class="course-content">
                <h3><a href="course.html?id=${course.id}">${course.title}</a></h3>
                <div class="course-instructor">
                    <img src="images/default-avatar.png" alt="${course.instructor}">
                    <span>${course.instructor}</span>
                </div>
                <div class="course-meta">
                    <div class="course-rating">
                        <i class="fas fa-star"></i>
                        <span>${course.rating}</span>
                    </div>
                    <div class="course-price">${formatPrice(course.price)}</div>
                </div>
            </div>
        </div>
    `).join('');
}

// =====================================================
// Cart
// =====================================================

// Get cart from localStorage
function getCart() {
    const cart = localStorage.getItem('cart');
    return cart ? JSON.parse(cart) : [];
}

// Save cart to localStorage
function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

// Add to cart
function addToCart(course) {
    const cart = getCart();
    
    if (!cart.find(item => item.id === course.id)) {
        cart.push(course);
        saveCart(cart);
        showNotification('تمت الإضافة إلى السلة');
    } else {
        showNotification('الكورس موجود بالفعل في السلة', 'warning');
    }
}

// Remove from cart
function removeFromCart(courseId) {
    let cart = getCart();
    cart = cart.filter(item => item.id !== courseId);
    saveCart(cart);
    showNotification('تم الحذف من السلة');
}

// Update cart count in header
function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const cart = getCart();
        cartCount.textContent = cart.length;
    }
}

// =====================================================
// UI Handlers
// =====================================================

// Mobile menu toggle
function setupMobileMenu() {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    const navActions = document.querySelector('.nav-actions');
    
    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('show');
            navActions.classList.toggle('show');
        });
    }
}

// Update nav for logged in user
function updateNavForAuth() {
    const navActions = document.querySelector('.nav-actions');
    if (!navActions) return;
    
    if (isLoggedIn()) {
        const user = getCurrentUser();
        navActions.innerHTML = `
            <a href="cart.html" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count">0</span>
            </a>
            <div class="user-dropdown">
                <button class="user-btn">
                    <img src="images/default-avatar.png" alt="${user?.name}">
                    <span>${user?.name || 'المستخدم'}</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="dashboard.html"><i class="fas fa-home"></i> لوحة التحكم</a>
                    <a href="my-courses.html"><i class="fas fa-book"></i> كورساتي</a>
                    <a href="profile.html"><i class="fas fa-user"></i> الملف الشخصي</a>
                    <hr>
                    <a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                </div>
            </div>
        `;
    }
    
    updateCartCount();
}

// Handle login form
function setupLoginForm() {
    const form = document.getElementById('loginForm');
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = form.querySelector('[name="email"]').value;
        const password = form.querySelector('[name="password"]').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري تسجيل الدخول...';
        
        try {
            await login(email, password);
            showNotification('تم تسجيل الدخول بنجاح');
            window.location.href = 'index.html';
        } catch (error) {
            showNotification(error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'تسجيل الدخول';
        }
    });
}

// Handle register form
function setupRegisterForm() {
    const form = document.getElementById('registerForm');
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const name = form.querySelector('[name="name"]').value;
        const email = form.querySelector('[name="email"]').value;
        const password = form.querySelector('[name="password"]').value;
        const confirmPassword = form.querySelector('[name="confirmPassword"]').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (password !== confirmPassword) {
            showNotification('كلمة المرور غير متطابقة', 'error');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري إنشاء الحساب...';
        
        try {
            await register(name, email, password);
            showNotification('تم إنشاء الحساب بنجاح');
            window.location.href = 'index.html';
        } catch (error) {
            showNotification(error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'إنشاء حساب';
        }
    });
}

// Search functionality
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `courses.html?search=${encodeURIComponent(query)}`;
            }
        }
    });
}

// =====================================================
// Initialize
// =====================================================

document.addEventListener('DOMContentLoaded', () => {
    // Setup UI handlers
    setupMobileMenu();
    updateNavForAuth();
    setupSearch();
    
    // Setup forms
    setupLoginForm();
    setupRegisterForm();
    
    // Load data
    loadFeaturedCourses();
});

// Add notification styles dynamically
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(-100px);
        background: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        transition: transform 0.3s ease;
    }
    
    .notification.show {
        transform: translateX(-50%) translateY(0);
    }
    
    .notification-success i { color: #28a745; }
    .notification-error i { color: #dc3545; }
    .notification-warning i { color: #ffc107; }
`;
document.head.appendChild(notificationStyles);
