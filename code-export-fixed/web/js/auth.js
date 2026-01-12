/**
 * Authentication Service
 * خدمة المصادقة
 */

class AuthService {
    constructor() {
        this.user = null;
        this.init();
    }

    // Initialize auth state
    init() {
        const userData = localStorage.getItem(CONFIG.USER_KEY);
        if (userData) {
            try {
                this.user = JSON.parse(userData);
            } catch (e) {
                this.logout();
            }
        }
        this.updateUI();
    }

    // Check if user is logged in
    isLoggedIn() {
        return !!this.user && !!localStorage.getItem(CONFIG.TOKEN_KEY);
    }

    // Get current user
    getUser() {
        return this.user;
    }

    // Login
    async login(email, password) {
        const response = await API.login(email, password);
        
        if (response.success) {
            this.user = response.data.user;
            localStorage.setItem(CONFIG.TOKEN_KEY, response.data.token);
            localStorage.setItem(CONFIG.USER_KEY, JSON.stringify(this.user));
            this.updateUI();
        }
        
        return response;
    }

    // Register
    async register(name, email, password, confirmPassword) {
        const response = await API.register(name, email, password, confirmPassword);
        
        if (response.success) {
            this.user = response.data.user;
            localStorage.setItem(CONFIG.TOKEN_KEY, response.data.token);
            localStorage.setItem(CONFIG.USER_KEY, JSON.stringify(this.user));
            this.updateUI();
        }
        
        return response;
    }

    // Logout
    logout() {
        this.user = null;
        localStorage.removeItem(CONFIG.TOKEN_KEY);
        localStorage.removeItem(CONFIG.USER_KEY);
        this.updateUI();
        
        // Redirect to home if on protected page
        const protectedPages = ['profile.html', 'my-courses.html', 'cart.html'];
        const currentPage = window.location.pathname.split('/').pop();
        if (protectedPages.includes(currentPage)) {
            window.location.href = 'index.html';
        }
    }

    // Update UI based on auth state
    updateUI() {
        const authButtons = document.getElementById('authButtons');
        const userMenu = document.getElementById('userMenu');

        if (authButtons && userMenu) {
            if (this.isLoggedIn()) {
                authButtons.classList.add('hidden');
                userMenu.classList.remove('hidden');
            } else {
                authButtons.classList.remove('hidden');
                userMenu.classList.add('hidden');
            }
        }
    }

    // Require auth for protected actions
    requireAuth() {
        if (!this.isLoggedIn()) {
            window.location.href = 'login.html?redirect=' + encodeURIComponent(window.location.href);
            return false;
        }
        return true;
    }
}

// Form Validation Helper
class FormValidator {
    constructor(form) {
        this.form = form;
        this.errors = {};
    }

    // Validate required field
    required(fieldName, message = CONFIG.MESSAGES.REQUIRED_FIELD) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        const value = field ? field.value.trim() : '';
        
        if (!value) {
            this.errors[fieldName] = message;
            return false;
        }
        return true;
    }

    // Validate email
    email(fieldName, message = CONFIG.MESSAGES.INVALID_EMAIL) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        const value = field ? field.value.trim() : '';
        const emailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
        
        if (value && !emailRegex.test(value)) {
            this.errors[fieldName] = message;
            return false;
        }
        return true;
    }

    // Validate minimum length
    minLength(fieldName, min, message) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        const value = field ? field.value : '';
        
        if (value && value.length < min) {
            this.errors[fieldName] = message || `يجب أن يكون على الأقل ${min} أحرف`;
            return false;
        }
        return true;
    }

    // Validate matching fields
    matches(fieldName, matchFieldName, message = CONFIG.MESSAGES.PASSWORD_MISMATCH) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        const matchField = this.form.querySelector(`[name="${matchFieldName}"]`);
        
        if (field && matchField && field.value !== matchField.value) {
            this.errors[fieldName] = message;
            return false;
        }
        return true;
    }

    // Check if valid
    isValid() {
        return Object.keys(this.errors).length === 0;
    }

    // Get errors
    getErrors() {
        return this.errors;
    }

    // Show errors in form
    showErrors() {
        // Clear previous errors
        this.form.querySelectorAll('.form-error').forEach(el => el.remove());
        this.form.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));

        // Show new errors
        for (const [fieldName, message] of Object.entries(this.errors)) {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('error');
                const errorEl = document.createElement('div');
                errorEl.className = 'form-error';
                errorEl.textContent = message;
                field.parentNode.appendChild(errorEl);
            }
        }
    }

    // Clear errors
    clearErrors() {
        this.errors = {};
        this.form.querySelectorAll('.form-error').forEach(el => el.remove());
        this.form.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
    }
}

// Create global Auth instance
const Auth = new AuthService();
