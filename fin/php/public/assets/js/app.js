/**
 * SwiftCart - Main JavaScript
 */

// API Base URL
const API_BASE = '/api';

// Toast Notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// API Helper
async function apiRequest(endpoint, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };
    
    const token = localStorage.getItem('auth_token');
    if (token) {
        defaultOptions.headers['Authorization'] = `Bearer ${token}`;
    }
    
    const response = await fetch(`${API_BASE}/${endpoint}`, {
        ...defaultOptions,
        ...options
    });
    
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || 'حدث خطأ');
    }
    
    return data;
}

// Cart Functions
async function updateCartCount() {
    try {
        const data = await apiRequest('cart.php?action=list');
        const count = data.data?.items?.length || 0;
        const badge = document.getElementById('cart-count');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

async function addToCart(productId, quantity = 1, variantId = null) {
    try {
        const data = await apiRequest('cart.php?action=add', {
            method: 'POST',
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
                variant_id: variantId
            })
        });
        showToast(data.message || 'تمت الإضافة للسلة');
        updateCartCount();
        return data;
    } catch (error) {
        showToast(error.message, 'danger');
        throw error;
    }
}

async function removeFromCart(itemId) {
    try {
        const data = await apiRequest(`cart.php?action=remove&item_id=${itemId}`, {
            method: 'DELETE'
        });
        showToast(data.message || 'تمت الإزالة');
        updateCartCount();
        return data;
    } catch (error) {
        showToast(error.message, 'danger');
        throw error;
    }
}

// Form Validation
function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let valid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return valid;
}

// Format Price
function formatPrice(price, currency = 'ر.س') {
    return `${parseFloat(price).toFixed(2)} ${currency}`;
}

// Format Date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('ar-SA', options);
}

// Loading State
function setLoading(element, loading = true) {
    if (loading) {
        element.disabled = true;
        element.dataset.originalText = element.innerHTML;
        element.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري التحميل...';
    } else {
        element.disabled = false;
        element.innerHTML = element.dataset.originalText;
    }
}

// Confirm Dialog
async function confirmAction(message = 'هل أنت متأكد؟') {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="modal fade" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center py-4">
                            <i class="bi bi-question-circle text-warning display-4 mb-3"></i>
                            <p class="mb-0">${message}</p>
                        </div>
                        <div class="modal-footer justify-content-center border-0 pt-0">
                            <button type="button" class="btn btn-secondary" data-action="cancel">إلغاء</button>
                            <button type="button" class="btn btn-primary" data-action="confirm">تأكيد</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const modalEl = modal.querySelector('.modal');
        const bsModal = new bootstrap.Modal(modalEl);
        
        modal.querySelector('[data-action="confirm"]').onclick = () => {
            bsModal.hide();
            resolve(true);
        };
        
        modal.querySelector('[data-action="cancel"]').onclick = () => {
            bsModal.hide();
            resolve(false);
        };
        
        modalEl.addEventListener('hidden.bs.modal', () => modal.remove());
        bsModal.show();
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count on page load
    if (document.getElementById('cart-count')) {
        updateCartCount();
    }
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showToast('يرجى ملء جميع الحقول المطلوبة', 'warning');
            }
        });
    });
    
    // Remove invalid class on input
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
});
