<?php
$title = 'إتمام الطلب - SwiftCart';
$mainClass = '';

ob_start();
?>

<div class="container py-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-credit-card me-2"></i>إتمام الطلب</h2>
    
    <div class="row g-4">
        <!-- Checkout Form -->
        <div class="col-lg-8">
            <!-- Progress Steps -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between position-relative">
                        <div class="text-center flex-fill">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;" id="step1-icon">1</div>
                            <div class="small mt-1">العنوان</div>
                        </div>
                        <div class="text-center flex-fill">
                            <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;" id="step2-icon">2</div>
                            <div class="small mt-1">الدفع</div>
                        </div>
                        <div class="text-center flex-fill">
                            <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;" id="step3-icon">3</div>
                            <div class="small mt-1">التأكيد</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 1: Shipping Address -->
            <div id="step1" class="checkout-step">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>عنوان التوصيل</h5>
                    </div>
                    <div class="card-body">
                        <form id="address-form">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">المدينة <span class="text-danger">*</span></label>
                                    <select class="form-select" id="city" required>
                                        <option value="">اختر المدينة</option>
                                        <option value="الرياض">الرياض</option>
                                        <option value="جدة">جدة</option>
                                        <option value="مكة">مكة المكرمة</option>
                                        <option value="المدينة">المدينة المنورة</option>
                                        <option value="الدمام">الدمام</option>
                                        <option value="الخبر">الخبر</option>
                                        <option value="الظهران">الظهران</option>
                                        <option value="القطيف">القطيف</option>
                                        <option value="الأحساء">الأحساء</option>
                                        <option value="الطائف">الطائف</option>
                                        <option value="تبوك">تبوك</option>
                                        <option value="بريدة">بريدة</option>
                                        <option value="خميس مشيط">خميس مشيط</option>
                                        <option value="أبها">أبها</option>
                                        <option value="نجران">نجران</option>
                                        <option value="جازان">جازان</option>
                                        <option value="ينبع">ينبع</option>
                                        <option value="حائل">حائل</option>
                                        <option value="الجبيل">الجبيل</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الحي <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="district" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">العنوان التفصيلي <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address" rows="2" required 
                                              placeholder="رقم المبنى، الشارع، معلم قريب..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الرمز البريدي</label>
                                    <input type="text" class="form-control" id="postal_code">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ملاحظات إضافية</label>
                                    <input type="text" class="form-control" id="notes" placeholder="تعليمات للتوصيل...">
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-between">
                                <a href="/cart" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-right me-1"></i>العودة للسلة
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    متابعة للدفع<i class="bi bi-arrow-left ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Payment -->
            <div id="step2" class="checkout-step" style="display: none;">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="bi bi-wallet2 me-2"></i>طريقة الدفع</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check card p-3 h-100">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="cod" value="cod" checked>
                                    <label class="form-check-label w-100" for="cod">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-cash-coin fs-3 text-success me-3"></i>
                                            <div>
                                                <strong>الدفع عند الاستلام</strong>
                                                <small class="d-block text-muted">ادفع نقداً عند التوصيل</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check card p-3 h-100">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="card" value="card">
                                    <label class="form-check-label w-100" for="card">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-credit-card fs-3 text-primary me-3"></i>
                                            <div>
                                                <strong>بطاقة ائتمان</strong>
                                                <small class="d-block text-muted">Visa, Mastercard, Mada</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check card p-3 h-100">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="stcpay" value="stcpay">
                                    <label class="form-check-label w-100" for="stcpay">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-phone fs-3 text-info me-3"></i>
                                            <div>
                                                <strong>STC Pay</strong>
                                                <small class="d-block text-muted">الدفع عبر محفظة STC</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check card p-3 h-100">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="bank" value="bank_transfer">
                                    <label class="form-check-label w-100" for="bank">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-bank fs-3 text-secondary me-3"></i>
                                            <div>
                                                <strong>تحويل بنكي</strong>
                                                <small class="d-block text-muted">تحويل مباشر للحساب</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Credit Card Form (Hidden by default) -->
                        <div id="card-form" class="mt-4" style="display: none;">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">رقم البطاقة</label>
                                    <input type="text" class="form-control" id="card_number" 
                                           placeholder="0000 0000 0000 0000" maxlength="19">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">تاريخ الانتهاء</label>
                                    <input type="text" class="form-control" id="card_expiry" 
                                           placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="card_cvv" 
                                           placeholder="000" maxlength="4">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">اسم حامل البطاقة</label>
                                    <input type="text" class="form-control" id="card_holder">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="goToStep(1)">
                                <i class="bi bi-arrow-right me-1"></i>السابق
                            </button>
                            <button type="button" class="btn btn-primary" onclick="goToStep(3)">
                                مراجعة الطلب<i class="bi bi-arrow-left ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Confirmation -->
            <div id="step3" class="checkout-step" style="display: none;">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>مراجعة وتأكيد الطلب</h5>
                    </div>
                    <div class="card-body">
                        <!-- Address Summary -->
                        <div class="mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><i class="bi bi-geo-alt me-1"></i>عنوان التوصيل</h6>
                                    <p class="mb-0" id="address-summary"></p>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="goToStep(1)">تعديل</button>
                            </div>
                        </div>
                        
                        <!-- Payment Summary -->
                        <div class="mb-4 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><i class="bi bi-wallet2 me-1"></i>طريقة الدفع</h6>
                                    <p class="mb-0" id="payment-summary"></p>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="goToStep(2)">تعديل</button>
                            </div>
                        </div>
                        
                        <!-- Products Summary -->
                        <div class="mb-4">
                            <h6 class="mb-3"><i class="bi bi-bag me-1"></i>المنتجات</h6>
                            <div id="products-summary"></div>
                        </div>
                        
                        <!-- Terms -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                أوافق على <a href="/terms" target="_blank">الشروط والأحكام</a> و
                                <a href="/privacy" target="_blank">سياسة الخصوصية</a>
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="goToStep(2)">
                                <i class="bi bi-arrow-right me-1"></i>السابق
                            </button>
                            <button type="button" class="btn btn-success btn-lg" onclick="placeOrder()" id="place-order-btn">
                                <i class="bi bi-check-circle me-2"></i>تأكيد الطلب
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">ملخص الطلب</h5>
                </div>
                <div class="card-body">
                    <div id="cart-items-summary">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                        </div>
                    </div>
                    
                    <hr>
                    
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
                    <div class="d-flex justify-content-between mb-2" id="discount-row" style="display: none;">
                        <span class="text-success">الخصم</span>
                        <span class="text-success" id="discount">-0.00 ر.س</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">الإجمالي</span>
                        <span class="fw-bold fs-5 text-primary" id="total">0.00 ر.س</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-body py-5">
                <div class="text-success mb-4">
                    <i class="bi bi-check-circle-fill display-1"></i>
                </div>
                <h3 class="mb-3">تم تأكيد طلبك بنجاح!</h3>
                <p class="text-muted mb-2">رقم الطلب: <strong id="order-number"></strong></p>
                <p class="text-muted mb-4">سيتم التواصل معك قريباً لتأكيد التوصيل</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="/orders" class="btn btn-primary">تتبع الطلب</a>
                    <a href="/products" class="btn btn-outline-secondary">متابعة التسوق</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
let cartData = null;
let orderData = {};

document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    setupForms();
});

async function loadCart() {
    try {
        const response = await fetch('/api/cart.php?action=list');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            cartData = data.data;
            renderCartSummary();
            calculateTotals();
        } else {
            window.location.href = '/cart';
        }
    } catch (error) {
        window.location.href = '/cart';
    }
}

function renderCartSummary() {
    const container = document.getElementById('cart-items-summary');
    container.innerHTML = cartData.map(item => `
        <div class="d-flex mb-3">
            <img src="${item.image || '/assets/images/placeholder.jpg'}" 
                 class="rounded me-2" style="width: 50px; height: 50px; object-fit: cover;">
            <div class="flex-grow-1">
                <small class="d-block text-truncate" style="max-width: 150px;">${item.name}</small>
                <small class="text-muted">×${item.quantity}</small>
            </div>
            <small class="fw-bold">${formatPrice((item.sale_price || item.price) * item.quantity)}</small>
        </div>
    `).join('');
}

async function calculateTotals() {
    const params = new URLSearchParams(window.location.search);
    const coupon = params.get('coupon');
    
    try {
        const response = await fetch(`/api/cart.php?action=totals${coupon ? '&coupon=' + coupon : ''}`);
        const data = await response.json();
        
        if (data.success) {
            updateSummary(data.data);
        }
    } catch (error) {
        let subtotal = 0;
        cartData.forEach(item => {
            subtotal += (item.sale_price || item.price) * item.quantity;
        });
        const shipping = subtotal >= 500 ? 0 : 25;
        const tax = subtotal * 0.15;
        updateSummary({ subtotal, shipping, tax, total: subtotal + shipping + tax });
    }
}

function updateSummary(totals) {
    document.getElementById('subtotal').textContent = formatPrice(totals.subtotal);
    document.getElementById('shipping').textContent = totals.shipping > 0 ? formatPrice(totals.shipping) : 'مجاني';
    document.getElementById('tax').textContent = formatPrice(totals.tax);
    document.getElementById('total').textContent = formatPrice(totals.total);
    
    if (totals.discount && totals.discount > 0) {
        document.getElementById('discount-row').style.display = 'flex';
        document.getElementById('discount').textContent = '-' + formatPrice(totals.discount);
    }
    
    orderData.totals = totals;
}

function formatPrice(price) {
    return parseFloat(price).toFixed(2) + ' ر.س';
}

function setupForms() {
    // Address form
    document.getElementById('address-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        orderData.shipping = {
            full_name: document.getElementById('full_name').value,
            phone: document.getElementById('phone').value,
            city: document.getElementById('city').value,
            district: document.getElementById('district').value,
            address: document.getElementById('address').value,
            postal_code: document.getElementById('postal_code').value,
            notes: document.getElementById('notes').value
        };
        
        goToStep(2);
    });
    
    // Payment method change
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('card-form').style.display = this.value === 'card' ? 'block' : 'none';
        });
    });
}

function goToStep(step) {
    // Validate current step
    if (step > currentStep) {
        if (currentStep === 1 && !validateAddressForm()) return;
        if (currentStep === 2) collectPaymentData();
    }
    
    // Hide all steps
    document.querySelectorAll('.checkout-step').forEach(el => el.style.display = 'none');
    
    // Show target step
    document.getElementById('step' + step).style.display = 'block';
    
    // Update icons
    for (let i = 1; i <= 3; i++) {
        const icon = document.getElementById('step' + i + '-icon');
        if (i < step) {
            icon.className = 'rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center';
            icon.innerHTML = '<i class="bi bi-check"></i>';
        } else if (i === step) {
            icon.className = 'rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center';
            icon.textContent = i;
        } else {
            icon.className = 'rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center';
            icon.textContent = i;
        }
        icon.style.width = '40px';
        icon.style.height = '40px';
    }
    
    currentStep = step;
    
    // Update summaries for step 3
    if (step === 3) {
        updateConfirmationSummaries();
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateAddressForm() {
    const form = document.getElementById('address-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    return true;
}

function collectPaymentData() {
    const method = document.querySelector('input[name="payment_method"]:checked').value;
    orderData.payment = {
        method: method
    };
    
    if (method === 'card') {
        orderData.payment.card = {
            number: document.getElementById('card_number').value,
            expiry: document.getElementById('card_expiry').value,
            cvv: document.getElementById('card_cvv').value,
            holder: document.getElementById('card_holder').value
        };
    }
}

function updateConfirmationSummaries() {
    // Address summary
    const addr = orderData.shipping;
    document.getElementById('address-summary').innerHTML = `
        <strong>${addr.full_name}</strong> - ${addr.phone}<br>
        ${addr.address}, ${addr.district}, ${addr.city}
        ${addr.postal_code ? '<br>الرمز البريدي: ' + addr.postal_code : ''}
    `;
    
    // Payment summary
    const paymentMethods = {
        'cod': 'الدفع عند الاستلام',
        'card': 'بطاقة ائتمان',
        'stcpay': 'STC Pay',
        'bank_transfer': 'تحويل بنكي'
    };
    document.getElementById('payment-summary').textContent = paymentMethods[orderData.payment?.method] || 'الدفع عند الاستلام';
    
    // Products summary
    document.getElementById('products-summary').innerHTML = cartData.map(item => `
        <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
            <img src="${item.image || '/assets/images/placeholder.jpg'}" 
                 class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
            <div class="flex-grow-1">
                <small>${item.name}</small>
                <small class="text-muted d-block">×${item.quantity}</small>
            </div>
            <small class="fw-bold">${formatPrice((item.sale_price || item.price) * item.quantity)}</small>
        </div>
    `).join('');
}

async function placeOrder() {
    if (!document.getElementById('terms').checked) {
        showToast('يرجى الموافقة على الشروط والأحكام', 'warning');
        return;
    }
    
    const btn = document.getElementById('place-order-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري المعالجة...';
    
    try {
        const params = new URLSearchParams(window.location.search);
        
        const response = await fetch('/api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create',
                shipping_address: orderData.shipping,
                payment_method: orderData.payment?.method || 'cod',
                coupon_code: params.get('coupon') || null,
                notes: orderData.shipping.notes
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('order-number').textContent = data.data?.order_number || data.data?.id || 'N/A';
            new bootstrap.Modal(document.getElementById('successModal')).show();
        } else {
            showToast(data.message || 'حدث خطأ في إنشاء الطلب', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>تأكيد الطلب';
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>تأكيد الطلب';
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'primary'} border-0 position-fixed bottom-0 end-0 m-3`;
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
