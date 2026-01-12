/**
 * Authentication Service
 * خدمة المصادقة - المتجر الإلكتروني
 */

const Auth = {
    // Login user
    async login(email, password) {
        const response = await API.login(email, password);
        
        if (response.success) {
            localStorage.setItem(CONFIG.TOKEN_KEY, response.data.token);
            localStorage.setItem(CONFIG.USER_KEY, JSON.stringify(response.data.user));
            this.updateUI();
        }
        
        return response;
    },

    // Register user
    async register(name, email, password, password_confirmation) {
        const response = await API.register(name, email, password, password_confirmation);
        
        if (response.success) {
            localStorage.setItem(CONFIG.TOKEN_KEY, response.data.token);
            localStorage.setItem(CONFIG.USER_KEY, JSON.stringify(response.data.user));
            this.updateUI();
        }
        
        return response;
    },

    // Logout user
    logout() {
        localStorage.removeItem(CONFIG.TOKEN_KEY);
        localStorage.removeItem(CONFIG.USER_KEY);
        this.updateUI();
        window.location.href = 'index.html';
    },

    // Check if user is logged in
    isLoggedIn() {
        return !!localStorage.getItem(CONFIG.TOKEN_KEY);
    },

    // Get current user
    getUser() {
        const userData = localStorage.getItem(CONFIG.USER_KEY);
        return userData ? JSON.parse(userData) : null;
    },

    // Get token
    getToken() {
        return localStorage.getItem(CONFIG.TOKEN_KEY);
    },

    // Require authentication
    requireAuth() {
        if (!this.isLoggedIn()) {
            const currentPage = window.location.pathname.split('/').pop();
            window.location.href = `login.html?redirect=${currentPage}`;
            return false;
        }
        return true;
    },

    // Update UI based on auth state
    updateUI() {
        const authButtons = document.getElementById('authButtons');
        const userMenu = document.getElementById('userMenu');

        if (this.isLoggedIn()) {
            if (authButtons) authButtons.classList.add('hidden');
            if (userMenu) userMenu.classList.remove('hidden');
        } else {
            if (authButtons) authButtons.classList.remove('hidden');
            if (userMenu) userMenu.classList.add('hidden');
        }
    }
};

// Form Validator
class FormValidator {
    constructor(form) {
        this.form = form;
        this.errors = {};
    }

    required(fieldName, message = CONFIG.MESSAGES.REQUIRED_FIELD) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field && !field.value.trim()) {
            this.errors[fieldName] = message;
        }
        return this;
    }

    email(fieldName, message = CONFIG.MESSAGES.INVALID_EMAIL) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (field && field.value && !emailRegex.test(field.value)) {
            this.errors[fieldName] = message;
        }
        return this;
    }

    minLength(fieldName, length, message = null) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field && field.value && field.value.length < length) {
            this.errors[fieldName] = message || `يجب أن يكون ${length} أحرف على الأقل`;
        }
        return this;
    }

    maxLength(fieldName, length, message = null) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field && field.value && field.value.length > length) {
            this.errors[fieldName] = message || `يجب أن لا يتجاوز ${length} حرف`;
        }
        return this;
    }

    matches(fieldName, otherFieldName, message = CONFIG.MESSAGES.PASSWORD_MISMATCH) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        const otherField = this.form.querySelector(`[name="${otherFieldName}"]`);
        if (field && otherField && field.value !== otherField.value) {
            this.errors[fieldName] = message;
        }
        return this;
    }

    phone(fieldName, message = 'رقم الهاتف غير صحيح') {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        const phoneRegex = /^[\d\s\+\-\(\)]{8,20}$/;
        if (field && field.value && !phoneRegex.test(field.value)) {
            this.errors[fieldName] = message;
        }
        return this;
    }

    isValid() {
        return Object.keys(this.errors).length === 0;
    }

    showErrors() {
        // Clear previous errors
        this.form.querySelectorAll('.error-message').forEach(el => el.remove());
        this.form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        // Show new errors
        for (const [fieldName, message] of Object.entries(this.errors)) {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('error');
                
                const errorEl = document.createElement('span');
                errorEl.className = 'error-message';
                errorEl.textContent = message;
                
                field.parentNode.insertBefore(errorEl, field.nextSibling);
            }
        }
    }

    clearErrors() {
        this.errors = {};
        this.form.querySelectorAll('.error-message').forEach(el => el.remove());
        this.form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    }
}

// Initialize auth UI on page load
document.addEventListener('DOMContentLoaded', () => {
    Auth.updateUI();
});
