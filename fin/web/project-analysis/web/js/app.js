/**
 * Main Application (Simplified)
 * التطبيق الرئيسي - مبسط
 * 
 * معظم المنطق الآن يتم معالجته في PHP
 * هذا الملف للوظائف البسيطة فقط
 */

document.addEventListener('DOMContentLoaded', function() {
    App.init();
});

const App = {
    init() {
        this.setupMobileMenu();
        this.setupUserDropdown();
        this.setupToast();
        this.setupAddToCart();
    },

    // Mobile menu toggle
    setupMobileMenu() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }
    },

    // User dropdown menu
    setupUserDropdown() {
        const userAvatar = document.querySelector('.user-avatar');
        if (userAvatar) {
            userAvatar.addEventListener('click', (e) => {
                e.stopPropagation();
                userAvatar.parentElement.classList.toggle('active');
            });

            document.addEventListener('click', () => {
                document.querySelectorAll('.user-menu').forEach(menu => {
                    menu.classList.remove('active');
                });
            });
        }
    },

    // Toast notifications
    setupToast() {
        // Check for toast messages from PHP
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success')) {
            this.showToast(urlParams.get('success'), 'success');
        }
        if (urlParams.has('error')) {
            this.showToast(urlParams.get('error'), 'error');
        }
        if (urlParams.has('registered')) {
            this.showToast('تم إنشاء حسابك بنجاح!', 'success');
        }
        if (urlParams.has('cart_added')) {
            this.showToast('تمت إضافة المنتج إلى السلة', 'success');
        }
    },

    // Add to cart functionality
    setupAddToCart() {
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const productId = btn.dataset.productId;
                const quantity = btn.dataset.quantity || 1;
                
                try {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner"></span>';
                    
                    const response = await fetch(`/api/cart.php?action=add`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: quantity
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showToast(CONFIG.MESSAGES.CART_ADD_SUCCESS, 'success');
                        this.updateCartCount(data.cart_count);
                    } else {
                        if (data.message === 'unauthorized') {
                            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                        } else {
                            this.showToast(data.message || CONFIG.MESSAGES.ERROR_GENERAL, 'error');
                        }
                    }
                } catch (error) {
                    this.showToast(CONFIG.MESSAGES.ERROR_GENERAL, 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-icons">add_shopping_cart</span> أضف للسلة';
                }
            });
        });
    },

    // Update cart count badge
    updateCartCount(count) {
        const badge = document.querySelector('.nav-actions .badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.add('pulse');
            setTimeout(() => badge.classList.remove('pulse'), 300);
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
            <span>${this.escapeHtml(message)}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <span class="material-icons">close</span>
            </button>
        `;

        document.body.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => toast.classList.add('show'));

        // Remove after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    // Escape HTML to prevent XSS
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Add toast and animation styles
const styles = document.createElement('style');
styles.textContent = `
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
        max-width: 90%;
    }
    .toast.show {
        transform: translateX(-50%) translateY(0);
    }
    .toast-success { border-right: 4px solid #22c55e; }
    .toast-error { border-right: 4px solid #ef4444; }
    .toast-info { border-right: 4px solid #3b82f6; }
    .toast-success .material-icons:first-child { color: #22c55e; }
    .toast-error .material-icons:first-child { color: #ef4444; }
    .toast-info .material-icons:first-child { color: #3b82f6; }
    .toast-close {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        margin-right: -0.5rem;
        color: #9ca3af;
    }
    .toast-close:hover { color: #6b7280; }
    
    .spinner {
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .badge.pulse {
        animation: pulse 0.3s ease;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
`;
document.head.appendChild(styles);
