/**
 * API Service
 * خدمة الاتصال بـ API
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

    // ==========================================
    // Auth Endpoints
    // ==========================================

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

    async changePassword(currentPassword, newPassword, confirmPassword) {
        return this.put('auth.php?action=change-password', {
            current_password: currentPassword,
            new_password: newPassword,
            new_password_confirmation: confirmPassword
        }, true);
    }

    // ==========================================
    // Courses Endpoints
    // ==========================================

    async getCourses(params = {}) {
        const query = new URLSearchParams(params).toString();
        return this.get(`courses.php?action=list&${query}`);
    }

    async getFeaturedCourses(limit = 6) {
        return this.get(`courses.php?action=featured&limit=${limit}`);
    }

    async getCourse(id) {
        return this.get(`courses.php?action=get&id=${id}`, true);
    }

    async enrollCourse(courseId) {
        return this.post(`courses.php?action=enroll&id=${courseId}`, {}, true);
    }

    async getMyCourses() {
        return this.get('courses.php?action=my-courses', true);
    }

    // ==========================================
    // Categories Endpoints
    // ==========================================

    async getCategories() {
        return this.get('categories.php?action=list');
    }

    // ==========================================
    // Cart Endpoints
    // ==========================================

    async getCart() {
        return this.get('cart.php?action=list', true);
    }

    async addToCart(courseId) {
        return this.post('cart.php?action=add', { course_id: courseId }, true);
    }

    async removeFromCart(courseId) {
        return this.delete(`cart.php?action=remove&course_id=${courseId}`, true);
    }

    async clearCart() {
        return this.delete('cart.php?action=clear', true);
    }

    async checkout() {
        return this.post('cart.php?action=checkout', {}, true);
    }

    // ==========================================
    // Reviews Endpoints
    // ==========================================

    async getReviews(courseId, page = 1) {
        return this.get(`reviews.php?action=list&course_id=${courseId}&page=${page}`);
    }

    async createReview(courseId, rating, comment) {
        return this.post('reviews.php?action=create', { course_id: courseId, rating, comment }, true);
    }

    async getReviewStats(courseId) {
        return this.get(`reviews.php?action=stats&course_id=${courseId}`);
    }

    // ==========================================
    // Lessons Endpoints
    // ==========================================

    async getLessons(courseId) {
        return this.get(`lessons.php?action=list&course_id=${courseId}`, true);
    }

    async getLesson(id) {
        return this.get(`lessons.php?action=get&id=${id}`, true);
    }

    async markLessonComplete(lessonId) {
        return this.post('lessons.php?action=complete', { lesson_id: lessonId }, true);
    }
}

// Custom API Error class
class ApiError extends Error {
    constructor(message, errors = {}, status = 400) {
        super(message);
        this.name = 'ApiError';
        this.errors = errors;
        this.status = status;
    }
}

// Create global API instance
const API = new ApiService();
