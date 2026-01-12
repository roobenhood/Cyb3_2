/**
 * API Service
 * خدمة الاتصال بـ API - المتجر الإلكتروني
 */

class ApiService {
    constructor() {
        this.baseUrl = CONFIG.API_URL;
    }

    // Get auth token from storage
    getToken() {
        return localStorage.getItem(CONFIG.TOKEN_KEY);
    }

    // Build headers with optional auth
    buildHeaders(includeAuth = false) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        if (includeAuth) {
            const token = this.getToken();
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
        }

        return headers;
    }

    // Generic request method
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}/${endpoint}`;

        try {
            const response = await fetch(url, {
                ...options,
                headers: this.buildHeaders(options.auth)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new ApiError(data.message || 'Request failed', data.errors || {}, response.status);
            }

            return data;
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            throw new ApiError(CONFIG.MESSAGES.ERROR_GENERAL, {}, 500);
        }
    }

    // GET request
    async get(endpoint, auth = false) {
        return this.request(endpoint, { method: 'GET', auth });
    }

    // POST request
    async post(endpoint, data, auth = false) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
            auth
        });
    }

    // PUT request
    async put(endpoint, data, auth = false) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
            auth
        });
    }

    // DELETE request
    async delete(endpoint, auth = false) {
        return this.request(endpoint, { method: 'DELETE', auth });
    }

    // ========== Auth APIs ==========
    async login(email, password) {
        return this.post('auth.php?action=login', { email, password });
    }

    async register(name, email, password, password_confirmation) {
        return this.post('auth.php?action=register', { name, email, password, password_confirmation });
    }

    async getProfile() {
        return this.get('auth.php?action=profile', true);
    }

    async updateProfile(data) {
        return this.put('auth.php?action=update-profile', data, true);
    }

    // ========== Products APIs ==========
    async getProducts(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`products.php?action=list&${params}`);
    }

    async getProduct(id) {
        return this.get(`products.php?action=get&id=${id}`);
    }

    async getFeaturedProducts(limit = 8) {
        return this.get(`products.php?action=featured&limit=${limit}`);
    }

    async getNewArrivals(limit = 8) {
        return this.get(`products.php?action=new&limit=${limit}`);
    }

    async searchProducts(query) {
        return this.get(`products.php?action=search&q=${encodeURIComponent(query)}`);
    }

    async getProductReviews(productId) {
        return this.get(`products.php?action=reviews&id=${productId}`);
    }

    async addReview(productId, rating, comment) {
        return this.post('products.php?action=add-review', { product_id: productId, rating, comment }, true);
    }

    // ========== Categories APIs ==========
    async getCategories() {
        return this.get('categories.php?action=list');
    }

    async getCategory(id) {
        return this.get(`categories.php?action=get&id=${id}`);
    }

    // ========== Cart APIs ==========
    async getCart() {
        return this.get('cart.php?action=list', true);
    }

    async addToCart(productId, quantity = 1) {
        return this.post('cart.php?action=add', { product_id: productId, quantity }, true);
    }

    async updateCartItem(productId, quantity) {
        return this.put('cart.php?action=update', { product_id: productId, quantity }, true);
    }

    async removeFromCart(productId) {
        return this.delete(`cart.php?action=remove&product_id=${productId}`, true);
    }

    async clearCart() {
        return this.delete('cart.php?action=clear', true);
    }

    // ========== Favorites APIs ==========
    async getFavorites() {
        return this.get('favorites.php?action=list', true);
    }

    async addToFavorites(productId) {
        return this.post('favorites.php?action=add', { product_id: productId }, true);
    }

    async removeFromFavorites(productId) {
        return this.delete(`favorites.php?action=remove&product_id=${productId}`, true);
    }

    // ========== Orders APIs ==========
    async getOrders() {
        return this.get('orders.php?action=list', true);
    }

    async getOrder(id) {
        return this.get(`orders.php?action=get&id=${id}`, true);
    }

    async createOrder(data) {
        return this.post('orders.php?action=create', data, true);
    }

    async cancelOrder(id) {
        return this.put(`orders.php?action=cancel&id=${id}`, {}, true);
    }

    // ========== Addresses APIs ==========
    async getAddresses() {
        return this.get('addresses.php?action=list', true);
    }

    async addAddress(data) {
        return this.post('addresses.php?action=add', data, true);
    }

    async updateAddress(id, data) {
        return this.put(`addresses.php?action=update&id=${id}`, data, true);
    }

    async deleteAddress(id) {
        return this.delete(`addresses.php?action=delete&id=${id}`, true);
    }

    async setDefaultAddress(id) {
        return this.put(`addresses.php?action=set-default&id=${id}`, {}, true);
    }
}

// Custom Error class
class ApiError extends Error {
    constructor(message, errors = {}, status = 500) {
        super(message);
        this.errors = errors;
        this.status = status;
    }
}

// Create global API instance
const API = new ApiService();
