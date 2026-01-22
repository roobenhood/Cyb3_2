<?php
$title = 'سلة التسوق - SwiftCart';
$mainClass = '';

ob_start();
?>

<div class="container py-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-cart3 me-2"></i>سلة التسوق</h2>
    
    <div class="row g-4">
        <!-- Cart Items -->
        <div class="col-lg-8">
            <div id="cart-items">
                <div class="text-center py-5" id="loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;" id="order-summary">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">ملخص الطلب</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">المجموع الفرعي</span>
                        <span id="subtotal">0.00 ر.س</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">الشحن</span>
                        <span id="shipping">0.00 ر.س</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">الضريبة (15%)</span>
                        <span id="tax">0.00 ر.س</span>
                    </div>
                    
                    <!-- Coupon -->
                    <div class="mb-3 mt-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="coupon-input" placeholder="كود الخصم">
                            <button class="btn btn-outline-primary" onclick="applyCoupon()">تطبيق</button>
                        </div>
                        <div id="coupon-message" class="small mt-1"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2" id="discount-row" style="display: none !important;">
                        <span class="text-success">الخصم</span>
                        <span class="text-success" id="discount">-0.00 ر.س</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold fs-5">الإجمالي</span>
                        <span class="fw-bold fs-5 text-primary" id="total">0.00 ر.س</span>
                    </div>
                    
                    <button class="btn btn-primary btn-lg w-100" onclick="proceedToCheckout()" id="checkout-btn" disabled>
                        <i class="bi bi-credit-card me-2"></i>إتمام الطلب
                    </button>
                    
                    <a href="/products" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-arrow-right me-2"></i>متابعة التسوق
                    </a>
                </div>
                
                <!-- Trust Badges -->
                <div class="card-footer bg-light border-0">
                    <div class="row g-2 text-center small text-muted">
                        <div class="col-4">
                            <i class="bi bi-shield-check text-success"></i>
                            <div>دفع آمن</div>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-truck text-primary"></i>
                            <div>توصيل سريع</div>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-arrow-repeat text-warning"></i>
                            <div>إرجاع مجاني</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quantity-input {
    width: 110px;
}
.quantity-input input {
    text-align: center;
    -moz-appearance: textfield;
}
.quantity-input input::-webkit-outer-spin-button,
.quantity-input input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>

<script>
let cartData = null;
let appliedCoupon = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});

async function loadCart() {
    try {
        const response = await fetch('/api/cart.php?action=list');
        const data = await response.json();
        
        document.getElementById('loading').style.display = 'none';
        
        if (data.success) {
            cartData = data.data;
            renderCart(cartData);
            calculateTotals();
        } else {
            showEmptyCart();
        }
    } catch (error) {
        console.error('Error:', error);
        showLoginRequired();
    }
}

function renderCart(items) {
    const container = document.getElementById('cart-items');
    
    if (!items || items.length === 0) {
        showEmptyCart();
        return;
    }
    
    container.innerHTML = items.map(item => `
        <div class="card border-0 shadow-sm mb-3 cart-item" data-id="${item.id}">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <img src="${item.image || '/assets/images/placeholder.jpg'}" 
                             alt="${item.name}" class="rounded"
                             style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <div class="col">
                        <h6 class="mb-1">
                            <a href="/products/${item.product_id}" class="text-decoration-none text-dark">
                                ${item.name}
                            </a>
                        </h6>
                        <p class="text-muted small mb-2">${item.category_name || ''}</p>
                        <div class="d-flex align-items-center">
                            ${item.sale_price ? `
                                <span class="text-primary fw-bold">${formatPrice(item.sale_price)}</span>
                                <small class="text-muted text-decoration-line-through ms-2">${formatPrice(item.price)}</small>
                            ` : `
                                <span class="text-primary fw-bold">${formatPrice(item.price)}</span>
                            `}
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="input-group input-group-sm quantity-input">
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="updateQuantity(${item.id}, ${item.quantity - 1})">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" class="form-control" value="${item.quantity}" min="1" 
                                   onchange="updateQuantity(${item.id}, this.value)">
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="updateQuantity(${item.id}, ${item.quantity + 1})">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-auto text-end">
                        <div class="fw-bold text-primary mb-2">
                            ${formatPrice((item.sale_price || item.price) * item.quantity)}
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${item.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    // Add clear cart button
    container.innerHTML += `
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted">${items.length} منتج في السلة</span>
            <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                <i class="bi bi-trash me-1"></i>تفريغ السلة
            </button>
        </div>
    `;
    
    document.getElementById('checkout-btn').disabled = false;
}

function showEmptyCart() {
    document.getElementById('cart-items').innerHTML = `
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-cart-x text-muted display-1"></i>
                <h4 class="mt-3">سلتك فارغة</h4>
                <p class="text-muted">لم تقم بإضافة أي منتجات بعد</p>
                <a href="/products" class="btn btn-primary mt-2">
                    <i class="bi bi-bag me-2"></i>تصفح المنتجات
                </a>
            </div>
        </div>
    `;
    document.getElementById('checkout-btn').disabled = true;
}

function showLoginRequired() {
    document.getElementById('cart-items').innerHTML = `
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-lock text-muted display-1"></i>
                <h4 class="mt-3">يرجى تسجيل الدخول</h4>
                <p class="text-muted">سجل دخولك لعرض سلة التسوق الخاصة بك</p>
                <a href="/login" class="btn btn-primary mt-2">
                    <i class="bi bi-box-arrow-in-left me-2"></i>تسجيل الدخول
                </a>
            </div>
        </div>
    `;
}

function formatPrice(price) {
    return parseFloat(price).toFixed(2) + ' ر.س';
}

async function updateQuantity(itemId, quantity) {
    if (quantity < 1) {
        removeItem(itemId);
        return;
    }
    
    try {
        const response = await fetch('/api/cart.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', item_id: itemId, quantity: parseInt(quantity) })
        });
        const data = await response.json();
        
        if (data.success) {
            loadCart();
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في التحديث', 'error');
    }
}

async function removeItem(itemId) {
    if (!confirm('هل تريد حذف هذا المنتج من السلة؟')) return;
    
    try {
        const response = await fetch(`/api/cart.php?action=remove&item_id=${itemId}`, {
            method: 'DELETE'
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('تم حذف المنتج', 'success');
            loadCart();
            updateCartCount();
        }
    } catch (error) {
        showToast('حدث خطأ في الحذف', 'error');
    }
}

async function clearCart() {
    if (!confirm('هل تريد تفريغ السلة بالكامل؟')) return;
    
    try {
        const response = await fetch('/api/cart.php?action=clear', {
            method: 'DELETE'
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('تم تفريغ السلة', 'success');
            loadCart();
            updateCartCount();
        }
    } catch (error) {
        showToast('حدث خطأ', 'error');
    }
}

async function calculateTotals() {
    if (!cartData || cartData.length === 0) {
        updateSummary({ subtotal: 0, shipping: 0, tax: 0, total: 0 });
        return;
    }
    
    try {
        const coupon = document.getElementById('coupon-input').value;
        const response = await fetch(`/api/cart.php?action=totals${coupon ? '&coupon=' + coupon : ''}`);
        const data = await response.json();
        
        if (data.success) {
            updateSummary(data.data);
        }
    } catch (error) {
        // Calculate locally
        let subtotal = 0;
        cartData.forEach(item => {
            subtotal += (item.sale_price || item.price) * item.quantity;
        });
        
        const shipping = subtotal >= 500 ? 0 : 25;
        const tax = subtotal * 0.15;
        const total = subtotal + shipping + tax;
        
        updateSummary({ subtotal, shipping, tax, total });
    }
}

function updateSummary(totals) {
    document.getElementById('subtotal').textContent = formatPrice(totals.subtotal);
    document.getElementById('shipping').textContent = totals.shipping > 0 ? formatPrice(totals.shipping) : 'مجاني';
    document.getElementById('tax').textContent = formatPrice(totals.tax);
    document.getElementById('total').textContent = formatPrice(totals.total);
    
    if (totals.discount && totals.discount > 0) {
        document.getElementById('discount-row').style.display = 'flex !important';
        document.getElementById('discount').textContent = '-' + formatPrice(totals.discount);
    }
}

async function applyCoupon() {
    const code = document.getElementById('coupon-input').value.trim();
    const messageEl = document.getElementById('coupon-message');
    
    if (!code) {
        messageEl.innerHTML = '<span class="text-danger">أدخل كود الخصم</span>';
        return;
    }
    
    messageEl.innerHTML = '<span class="text-muted">جاري التحقق...</span>';
    
    try {
        const response = await fetch(`/api/cart.php?action=totals&coupon=${code}`);
        const data = await response.json();
        
        if (data.success && data.data.discount > 0) {
            appliedCoupon = code;
            messageEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>تم تطبيق الخصم</span>';
            updateSummary(data.data);
            document.getElementById('discount-row').style.display = 'flex';
        } else {
            messageEl.innerHTML = '<span class="text-danger">كود غير صالح</span>';
        }
    } catch (error) {
        messageEl.innerHTML = '<span class="text-danger">حدث خطأ</span>';
    }
}

function proceedToCheckout() {
    window.location.href = '/checkout' + (appliedCoupon ? '?coupon=' + appliedCoupon : '');
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0 position-fixed bottom-0 end-0 m-3`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
