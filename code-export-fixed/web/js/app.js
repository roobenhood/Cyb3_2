/**
 * Main Application
 * التطبيق الرئيسي
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize app
    App.init();
});

const App = {
    // Initialize
    init() {
        this.setupEventListeners();
        this.loadPageContent();
    },

    // Setup global event listeners
    setupEventListeners() {
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');
        
        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                Auth.logout();
                this.showToast(CONFIG.MESSAGES.LOGOUT_SUCCESS, 'success');
            });
        }

        // Cart button
        const cartBtn = document.getElementById('cartBtn');
        if (cartBtn) {
            cartBtn.addEventListener('click', () => {
                if (Auth.requireAuth()) {
                    window.location.href = 'cart.html';
                }
            });
        }

        // Search form
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const query = document.getElementById('searchInput').value.trim();
                if (query) {
                    window.location.href = `courses.html?search=${encodeURIComponent(query)}`;
                }
            });
        }
    },

    // Load page-specific content
    async loadPageContent() {
        const page = window.location.pathname.split('/').pop() || 'index.html';

        switch (page) {
            case 'index.html':
            case '':
                await this.loadHomePage();
                break;
            case 'courses.html':
                await this.loadCoursesPage();
                break;
            case 'course.html':
                await this.loadCoursePage();
                break;
            case 'login.html':
                this.setupLoginPage();
                break;
            case 'register.html':
                this.setupRegisterPage();
                break;
        }

        // Update cart count
        this.updateCartCount();
    },

    // Load home page content
    async loadHomePage() {
        try {
            // Load categories
            const categoriesResponse = await API.getCategories();
            if (categoriesResponse.success) {
                this.renderCategories(categoriesResponse.data);
            }

            // Load featured courses
            const coursesResponse = await API.getFeaturedCourses(6);
            if (coursesResponse.success) {
                this.renderCourses(coursesResponse.data, 'featuredCourses');
            }
        } catch (error) {
            console.error('Error loading home page:', error);
        }
    },

    // Load courses page
    async loadCoursesPage() {
        const params = new URLSearchParams(window.location.search);
        const filters = {
            page: params.get('page') || 1,
            category_id: params.get('category') || '',
            level: params.get('level') || '',
            search: params.get('search') || '',
            sort: params.get('sort') || ''
        };

        try {
            const response = await API.getCourses(filters);
            if (response.success) {
                this.renderCourses(response.data, 'coursesGrid');
                this.renderPagination(response.pagination);
            }
        } catch (error) {
            console.error('Error loading courses:', error);
        }
    },

    // Load single course page
    async loadCoursePage() {
        const params = new URLSearchParams(window.location.search);
        const courseId = params.get('id');

        if (!courseId) {
            window.location.href = 'courses.html';
            return;
        }

        try {
            const response = await API.getCourse(courseId);
            if (response.success) {
                this.renderCourseDetails(response.data);
            }
        } catch (error) {
            console.error('Error loading course:', error);
        }
    },

    // Setup login page
    setupLoginPage() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const validator = new FormValidator(form);
            validator.required('email');
            validator.email('email');
            validator.required('password');

            if (!validator.isValid()) {
                validator.showErrors();
                return;
            }

            const email = form.querySelector('[name="email"]').value;
            const password = form.querySelector('[name="password"]').value;

            try {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> جاري التحميل...';

                await Auth.login(email, password);
                this.showToast(CONFIG.MESSAGES.LOGIN_SUCCESS, 'success');

                // Redirect
                const redirect = new URLSearchParams(window.location.search).get('redirect');
                window.location.href = redirect || 'index.html';
            } catch (error) {
                this.showToast(error.message || CONFIG.MESSAGES.LOGIN_ERROR, 'error');
                form.querySelector('button[type="submit"]').disabled = false;
                form.querySelector('button[type="submit"]').innerHTML = 'تسجيل الدخول';
            }
        });
    },

    // Setup register page
    setupRegisterPage() {
        const form = document.getElementById('registerForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const validator = new FormValidator(form);
            validator.required('name');
            validator.minLength('name', 2);
            validator.required('email');
            validator.email('email');
            validator.required('password');
            validator.minLength('password', 6, CONFIG.MESSAGES.PASSWORD_MIN);
            validator.required('password_confirmation');
            validator.matches('password_confirmation', 'password');

            if (!validator.isValid()) {
                validator.showErrors();
                return;
            }

            const name = form.querySelector('[name="name"]').value;
            const email = form.querySelector('[name="email"]').value;
            const password = form.querySelector('[name="password"]').value;
            const confirmPassword = form.querySelector('[name="password_confirmation"]').value;

            try {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> جاري التحميل...';

                await Auth.register(name, email, password, confirmPassword);
                this.showToast(CONFIG.MESSAGES.REGISTER_SUCCESS, 'success');
                window.location.href = 'index.html';
            } catch (error) {
                if (error.errors) {
                    const validator = new FormValidator(form);
                    validator.errors = error.errors;
                    validator.showErrors();
                }
                this.showToast(error.message || CONFIG.MESSAGES.ERROR_GENERAL, 'error');
                form.querySelector('button[type="submit"]').disabled = false;
                form.querySelector('button[type="submit"]').innerHTML = 'إنشاء حساب';
            }
        });
    },

    // Render categories
    renderCategories(categories) {
        const container = document.getElementById('categoriesGrid');
        if (!container) return;

        container.innerHTML = categories.map(cat => `
            <a href="courses.html?category=${cat.id}" class="category-card">
                <div class="category-icon">
                    <span class="material-icons">${cat.icon || 'category'}</span>
                </div>
                <h3 class="category-name">${this.escapeHtml(cat.name)}</h3>
                <p class="category-count">${cat.courses_count || 0} دورة</p>
            </a>
        `).join('');
    },

    // Render courses
    renderCourses(courses, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (courses.length === 0) {
            container.innerHTML = '<p class="no-results">لا توجد دورات متاحة</p>';
            return;
        }

        container.innerHTML = courses.map(course => `
            <a href="course.html?id=${course.id}" class="course-card">
                <div class="course-thumbnail">
                    <img src="${course.thumbnail || CONFIG.DEFAULT_THUMBNAIL}" alt="${this.escapeHtml(course.title)}">
                    ${course.discount_price ? '<span class="course-badge">خصم</span>' : ''}
                </div>
                <div class="course-content">
                    <span class="course-category">${this.escapeHtml(course.category_name || '')}</span>
                    <h3 class="course-title">${this.escapeHtml(course.title)}</h3>
                    <p class="course-instructor">
                        <span class="material-icons">person</span>
                        ${this.escapeHtml(course.instructor_name || '')}
                    </p>
                    <div class="course-meta">
                        <div class="course-rating">
                            <span class="material-icons">star</span>
                            <span>${(course.avg_rating || 0).toFixed(1)}</span>
                        </div>
                        <div class="course-price">
                            ${course.discount_price ? `<span class="original-price">${CONFIG.CURRENCY}${course.price}</span>` : ''}
                            ${CONFIG.CURRENCY}${course.discount_price || course.price}
                        </div>
                    </div>
                </div>
            </a>
        `).join('');
    },

    // Render course details
    renderCourseDetails(course) {
        // Implementation for course detail page
        const container = document.getElementById('courseDetails');
        if (!container) return;

        // This would be a full implementation in a real app
        container.innerHTML = `
            <div class="course-header">
                <h1>${this.escapeHtml(course.title)}</h1>
                <p>${this.escapeHtml(course.description)}</p>
            </div>
        `;
    },

    // Render pagination
    renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!container || !pagination) return;

        const { current_page, total_pages } = pagination;
        let html = '';

        for (let i = 1; i <= total_pages; i++) {
            html += `<a href="?page=${i}" class="pagination-link ${i === current_page ? 'active' : ''}">${i}</a>`;
        }

        container.innerHTML = html;
    },

    // Update cart count
    async updateCartCount() {
        const badge = document.getElementById('cartCount');
        if (!badge) return;

        if (Auth.isLoggedIn()) {
            try {
                const response = await API.getCart();
                badge.textContent = response.data.count || 0;
            } catch (error) {
                badge.textContent = 0;
            }
        } else {
            badge.textContent = 0;
        }
    },

    // Show toast notification
    showToast(message, type = 'info') {
        // Remove existing toasts
        document.querySelectorAll('.toast').forEach(el => el.remove());

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="material-icons">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}</span>
            <span>${message}</span>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.add('show'), 100);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    // Escape HTML to prevent XSS
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Add toast styles dynamically
const toastStyles = document.createElement('style');
toastStyles.textContent = `
    .toast {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        z-index: 9999;
        transition: transform 0.3s ease;
    }
    .toast.show {
        transform: translateX(-50%) translateY(0);
    }
    .toast-success { border-left: 4px solid #22c55e; }
    .toast-error { border-left: 4px solid #ef4444; }
    .toast-info { border-left: 4px solid #3b82f6; }
    .toast-success .material-icons { color: #22c55e; }
    .toast-error .material-icons { color: #ef4444; }
    .toast-info .material-icons { color: #3b82f6; }
`;
document.head.appendChild(toastStyles);
