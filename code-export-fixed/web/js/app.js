/**
 * Main Application
 * التطبيق الرئيسي - المتجر الإلكتروني
 */

document.addEventListener('DOMContentLoaded', function() {
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

        // Search toggle
        const searchToggle = document.getElementById('searchToggle');
        const searchOverlay = document.getElementById('searchOverlay');
        const searchClose = document.getElementById('searchClose');

        if (searchToggle && searchOverlay) {
            searchToggle.addEventListener('click', () => {
                searchOverlay.classList.add('active');
                document.getElementById('searchInput').focus();
            });
        }

        if (searchClose && searchOverlay) {
            searchClose.addEventListener('click', () => {
                searchOverlay.classList.remove('active');
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

        // Favorites button
        const favoritesBtn = document.getElementById('favoritesBtn');
        if (favoritesBtn) {
            favoritesBtn.addEventListener('click', () => {
                if (Auth.requireAuth()) {
                    window.location.href = 'favorites.html';
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
                    window.location.href = `products.html?search=${encodeURIComponent(query)}`;
                }
            });
        }

        // Newsletter form
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.showToast('تم الاشتراك في النشرة البريدية بنجاح', 'success');
                newsletterForm.reset();
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
            case 'products.html':
                await this.loadProductsPage();
                break;
            case 'product.html':
                await this.loadProductPage();
                break;
            case 'cart.html':
                await this.loadCartPage();
                break;
            case 'favorites.html':
                await this.loadFavoritesPage();
                break;
            case 'checkout.html':
                await this.loadCheckoutPage();
                break;
            case 'orders.html':
                await this.loadOrdersPage();
                break;
            case 'login.html':
                this.setupLoginPage();
                break;
            case 'register.html':
                this.setupRegisterPage();
                break;
        }

        // Update cart and favorites count
        this.updateCartCount();
        this.updateFavoritesCount();
    },

    // Load home page content
    async loadHomePage() {
        try {
            // Load categories
            const categoriesResponse = await API.getCategories();
            if (categoriesResponse.success) {
                this.renderCategories(categoriesResponse.data);
            }

            // Load featured products
            const featuredResponse = await API.getFeaturedProducts(8);
            if (featuredResponse.success) {
                this.renderProducts(featuredResponse.data, 'featuredProducts');
            }

            // Load new arrivals
            const newResponse = await API.getNewArrivals(8);
            if (newResponse.success) {
                this.renderProducts(newResponse.data, 'newArrivals');
            }
        } catch (error) {
            console.error('Error loading home page:', error);
        }
    },

    // Load products page
    async loadProductsPage() {
        const params = new URLSearchParams(window.location.search);
        const filters = {
            page: params.get('page') || 1,
            category_id: params.get('category') || '',
            search: params.get('search') || '',
            sort: params.get('sort') || '',
            featured: params.get('featured') || '',
            min_price: params.get('min_price') || '',
            max_price: params.get('max_price') || ''
        };

        try {
            const response = await API.getProducts(filters);
            if (response.success) {
                this.renderProducts(response.data, 'productsGrid');
                this.renderPagination(response.pagination);
            }
        } catch (error) {
            console.error('Error loading products:', error);
        }
    },

    // Load single product page
    async loadProductPage() {
        const params = new URLSearchParams(window.location.search);
        const productId = params.get('id');

        if (!productId) {
            window.location.href = 'products.html';
            return;
        }

        try {
            const response = await API.getProduct(productId);
            if (response.success) {
                this.renderProductDetails(response.data);
            }
        } catch (error) {
            console.error('Error loading product:', error);
        }
    }

    // Load cart page
    async loadCartPage() {
        try {
            const response = await API.getCart();
            if (response.success) {
                this.renderCart(response.data);
            }
        } catch (error) {
            console.error('Error loading cart:', error);
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
            <a href="products.html?category=${cat.id}" class="category-card">
                <div class="category-icon">
                    <span class="material-icons">${cat.icon || 'category'}</span>
                </div>
                <h3 class="category-name">${this.escapeHtml(cat.name)}</h3>
                <p class="category-count">${cat.product_count || 0} منتج</p>
            </a>
        `).join('');
    },

    // Render products
    renderProducts(products, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (products.length === 0) {
            container.innerHTML = '<p class="no-results">لا توجد منتجات متاحة</p>';
            return;
        }

        container.innerHTML = products.map(product => `
            <div class="product-card">
                <a href="product.html?id=${product.id}" class="product-image">
                    <img src="${product.image_url || CONFIG.DEFAULT_PRODUCT_IMAGE}" alt="${this.escapeHtml(product.name)}">
                    ${product.discount_price ? '<span class="product-badge sale">خصم</span>' : ''}
                    ${product.stock === 0 ? '<span class="product-badge out">نفذت الكمية</span>' : ''}
                </a>
                <div class="product-content">
                    <span class="product-category">${this.escapeHtml(product.category_name || '')}</span>
                    <h3 class="product-title">
                        <a href="product.html?id=${product.id}">${this.escapeHtml(product.name)}</a>
                    </h3>
                    <div class="product-rating">
                        ${this.renderStars(product.rating || 0)}
                        <span>(${product.review_count || 0})</span>
                    </div>
                    <div class="product-price">
                        ${product.discount_price ? `<span class="original-price">${CONFIG.CURRENCY}${product.price}</span>` : ''}
                        <span class="current-price">${CONFIG.CURRENCY}${product.discount_price || product.price}</span>
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-primary btn-sm add-to-cart" data-id="${product.id}" ${product.stock === 0 ? 'disabled' : ''}>
                            <span class="material-icons">shopping_cart</span>
                            أضف للسلة
                        </button>
                        <button class="btn-icon add-to-favorites" data-id="${product.id}">
                            <span class="material-icons">favorite_border</span>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Add event listeners for add to cart buttons
        container.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = btn.dataset.id;
                this.addToCart(productId);
            });
        });

        // Add event listeners for add to favorites buttons
        container.querySelectorAll('.add-to-favorites').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = btn.dataset.id;
                this.addToFavorites(productId);
            });
        });
    },

    // Render stars
    renderStars(rating) {
        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

        let html = '';
        for (let i = 0; i < fullStars; i++) {
            html += '<span class="material-icons star">star</span>';
        }
        if (halfStar) {
            html += '<span class="material-icons star">star_half</span>';
        }
        for (let i = 0; i < emptyStars; i++) {
            html += '<span class="material-icons star empty">star_border</span>';
        }
        return html;
    },

    // Render product details
    renderProductDetails(product) {
        const container = document.getElementById('productDetails');
        if (!container) return;

        container.innerHTML = `
            <div class="product-gallery">
                <div class="main-image">
                    <img src="${product.image_url || CONFIG.DEFAULT_PRODUCT_IMAGE}" alt="${this.escapeHtml(product.name)}" id="mainImage">
                </div>
                ${product.images && product.images.length > 0 ? `
                    <div class="thumbnail-images">
                        ${product.images.map((img, i) => `
                            <img src="${img}" alt="صورة ${i + 1}" class="thumbnail" data-image="${img}">
                        `).join('')}
                    </div>
                ` : ''}
            </div>
            <div class="product-info">
                <span class="product-category">${this.escapeHtml(product.category_name || '')}</span>
                <h1 class="product-title">${this.escapeHtml(product.name)}</h1>
                <div class="product-rating">
                    ${this.renderStars(product.rating || 0)}
                    <span>(${product.review_count || 0} تقييم)</span>
                </div>
                <div class="product-price">
                    ${product.discount_price ? `
                        <span class="original-price">${CONFIG.CURRENCY}${product.price}</span>
                        <span class="discount-badge">-${Math.round((1 - product.discount_price / product.price) * 100)}%</span>
                    ` : ''}
                    <span class="current-price">${CONFIG.CURRENCY}${product.discount_price || product.price}</span>
                </div>
                <p class="product-description">${this.escapeHtml(product.description)}</p>
                <div class="product-stock ${product.stock > 0 ? 'in-stock' : 'out-of-stock'}">
                    <span class="material-icons">${product.stock > 0 ? 'check_circle' : 'cancel'}</span>
                    ${product.stock > 0 ? `متوفر (${product.stock} قطعة)` : 'غير متوفر'}
                </div>
                <div class="product-quantity">
                    <label>الكمية:</label>
                    <div class="quantity-selector">
                        <button class="qty-btn minus">-</button>
                        <input type="number" value="1" min="1" max="${product.stock}" id="quantity">
                        <button class="qty-btn plus">+</button>
                    </div>
                </div>
                <div class="product-actions">
                    <button class="btn btn-primary btn-lg" id="addToCartBtn" ${product.stock === 0 ? 'disabled' : ''}>
                        <span class="material-icons">shopping_cart</span>
                        أضف للسلة
                    </button>
                    <button class="btn btn-outline btn-lg" id="addToFavoritesBtn">
                        <span class="material-icons">favorite_border</span>
                    </button>
                </div>
            </div>
        `;

        // Quantity controls
        const quantityInput = document.getElementById('quantity');
        container.querySelector('.qty-btn.minus').addEventListener('click', () => {
            if (quantityInput.value > 1) quantityInput.value--;
        });
        container.querySelector('.qty-btn.plus').addEventListener('click', () => {
            if (quantityInput.value < product.stock) quantityInput.value++;
        });

        // Add to cart
        document.getElementById('addToCartBtn').addEventListener('click', () => {
            this.addToCart(product.id, parseInt(quantityInput.value));
        });

        // Add to favorites
        document.getElementById('addToFavoritesBtn').addEventListener('click', () => {
            this.addToFavorites(product.id);
        });
    },

    // Render cart
    renderCart(cart) {
        const container = document.getElementById('cartItems');
        const summaryContainer = document.getElementById('cartSummary');
        if (!container) return;

        if (!cart.items || cart.items.length === 0) {
            container.innerHTML = `
                <div class="empty-cart">
                    <span class="material-icons">shopping_cart</span>
                    <h3>السلة فارغة</h3>
                    <p>لم تضف أي منتجات إلى السلة بعد</p>
                    <a href="products.html" class="btn btn-primary">تسوق الآن</a>
                </div>
            `;
            if (summaryContainer) summaryContainer.classList.add('hidden');
            return;
        }

        container.innerHTML = cart.items.map(item => `
            <div class="cart-item" data-id="${item.product_id}">
                <div class="cart-item-image">
                    <img src="${item.product.image_url || CONFIG.DEFAULT_PRODUCT_IMAGE}" alt="${this.escapeHtml(item.product.name)}">
                </div>
                <div class="cart-item-details">
                    <h4>${this.escapeHtml(item.product.name)}</h4>
                    <span class="cart-item-price">${CONFIG.CURRENCY}${item.product.discount_price || item.product.price}</span>
                </div>
                <div class="cart-item-quantity">
                    <button class="qty-btn minus" data-id="${item.product_id}">-</button>
                    <input type="number" value="${item.quantity}" min="1" max="${item.product.stock}" data-id="${item.product_id}">
                    <button class="qty-btn plus" data-id="${item.product_id}">+</button>
                </div>
                <div class="cart-item-total">
                    ${CONFIG.CURRENCY}${((item.product.discount_price || item.product.price) * item.quantity).toFixed(2)}
                </div>
                <button class="btn-icon remove-item" data-id="${item.product_id}">
                    <span class="material-icons">delete</span>
                </button>
            </div>
        `).join('');

        // Cart summary
        if (summaryContainer) {
            const subtotal = cart.subtotal || 0;
            const shipping = subtotal >= CONFIG.FREE_SHIPPING_THRESHOLD ? 0 : CONFIG.SHIPPING_COST;
            const tax = subtotal * CONFIG.TAX_RATE;
            const total = subtotal + shipping + tax;

            summaryContainer.innerHTML = `
                <h3>ملخص الطلب</h3>
                <div class="summary-row">
                    <span>المجموع الفرعي</span>
                    <span>${CONFIG.CURRENCY}${subtotal.toFixed(2)}</span>
                </div>
                <div class="summary-row">
                    <span>الشحن</span>
                    <span>${shipping === 0 ? 'مجاني' : CONFIG.CURRENCY + shipping.toFixed(2)}</span>
                </div>
                <div class="summary-row">
                    <span>الضريبة (15%)</span>
                    <span>${CONFIG.CURRENCY}${tax.toFixed(2)}</span>
                </div>
                <hr>
                <div class="summary-row total">
                    <span>الإجمالي</span>
                    <span>${CONFIG.CURRENCY}${total.toFixed(2)}</span>
                </div>
                <a href="checkout.html" class="btn btn-primary btn-block">إتمام الطلب</a>
            `;
            summaryContainer.classList.remove('hidden');
        }

        // Event listeners
        container.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', () => this.removeFromCart(btn.dataset.id));
        });
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

    // Add to cart
    async addToCart(productId, quantity = 1) {
        if (!Auth.requireAuth()) return;

        try {
            await API.addToCart(productId, quantity);
            this.showToast(CONFIG.MESSAGES.CART_ADD_SUCCESS, 'success');
            this.updateCartCount();
        } catch (error) {
            this.showToast(error.message || CONFIG.MESSAGES.ERROR_GENERAL, 'error');
        }
    },

    // Remove from cart
    async removeFromCart(productId) {
        try {
            await API.removeFromCart(productId);
            this.showToast(CONFIG.MESSAGES.CART_REMOVE_SUCCESS, 'success');
            this.updateCartCount();
            this.loadCartPage();
        } catch (error) {
            this.showToast(error.message || CONFIG.MESSAGES.ERROR_GENERAL, 'error');
        }
    },

    // Add to favorites
    async addToFavorites(productId) {
        if (!Auth.requireAuth()) return;

        try {
            await API.addToFavorites(productId);
            this.showToast(CONFIG.MESSAGES.FAVORITE_ADD_SUCCESS, 'success');
            this.updateFavoritesCount();
        } catch (error) {
            this.showToast(error.message || CONFIG.MESSAGES.ERROR_GENERAL, 'error');
        }
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

    // Update favorites count
    async updateFavoritesCount() {
        const badge = document.getElementById('favoritesCount');
        if (!badge) return;

        if (Auth.isLoggedIn()) {
            try {
                const response = await API.getFavorites();
                badge.textContent = response.data.length || 0;
            } catch (error) {
                badge.textContent = 0;
            }
        } else {
            badge.textContent = 0;
        }
    },

    // Show toast notification
    showToast(message, type = 'info') {
        document.querySelectorAll('.toast').forEach(el => el.remove());

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="material-icons">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}</span>
            <span>${message}</span>
        `;

        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    // Escape HTML
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Toast styles
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
    .toast.show { transform: translateX(-50%) translateY(0); }
    .toast-success { border-left: 4px solid #22c55e; }
    .toast-error { border-left: 4px solid #ef4444; }
    .toast-info { border-left: 4px solid #3b82f6; }
    .toast-success .material-icons { color: #22c55e; }
    .toast-error .material-icons { color: #ef4444; }
    .toast-info .material-icons { color: #3b82f6; }
`;
document.head.appendChild(toastStyles);
