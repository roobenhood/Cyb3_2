<?php
$title = ($product['name'] ?? 'تفاصيل المنتج') . ' - SwiftCart';
$mainClass = '';

ob_start();
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">الرئيسية</a></li>
            <li class="breadcrumb-item"><a href="/products">المنتجات</a></li>
            <li class="breadcrumb-item active" id="product-category"></li>
        </ol>
    </nav>
    
    <!-- Product Details -->
    <div class="row g-5" id="product-container">
        <div class="col-12 text-center py-5" id="loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <section class="mt-5 pt-5 border-top" id="reviews-section" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0"><i class="bi bi-chat-quote me-2"></i>التقييمات والمراجعات</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                <i class="bi bi-plus-lg me-1"></i>أضف تقييمك
            </button>
        </div>
        
        <!-- Reviews Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-0 bg-light text-center py-4">
                    <div class="display-3 fw-bold text-primary" id="avg-rating">0</div>
                    <div id="rating-stars" class="mb-2"></div>
                    <p class="text-muted mb-0">من <span id="total-reviews">0</span> تقييم</p>
                </div>
            </div>
            <div class="col-md-8">
                <div id="rating-bars"></div>
            </div>
        </div>
        
        <!-- Reviews List -->
        <div id="reviews-list"></div>
    </section>
    
    <!-- Related Products -->
    <section class="mt-5 pt-5 border-top" id="related-section" style="display: none;">
        <h4 class="fw-bold mb-4"><i class="bi bi-grid me-2"></i>منتجات مشابهة</h4>
        <div class="row g-4" id="related-products"></div>
    </section>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">أضف تقييمك</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="review-form">
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <label class="form-label fw-bold">التقييم</label>
                        <div class="rating-input fs-3" id="rating-input">
                            <i class="bi bi-star" data-rating="1"></i>
                            <i class="bi bi-star" data-rating="2"></i>
                            <i class="bi bi-star" data-rating="3"></i>
                            <i class="bi bi-star" data-rating="4"></i>
                            <i class="bi bi-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" id="rating-value" value="5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">المراجعة</label>
                        <textarea class="form-control" id="review-comment" rows="4" 
                                  placeholder="شاركنا رأيك في المنتج..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال التقييم</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.rating-input i {
    cursor: pointer;
    color: #ffc107;
    transition: transform 0.2s;
}
.rating-input i:hover {
    transform: scale(1.2);
}
.rating-input i.bi-star-fill {
    color: #ffc107;
}
.quantity-input {
    width: 120px;
}
.quantity-input input {
    text-align: center;
}
.product-gallery img {
    cursor: pointer;
    transition: opacity 0.2s;
}
.product-gallery img:hover {
    opacity: 0.8;
}
.product-gallery img.active {
    border: 2px solid var(--bs-primary);
}
</style>

<script>
let productId = null;
let productData = null;

document.addEventListener('DOMContentLoaded', function() {
    // Get product ID from URL
    const pathParts = window.location.pathname.split('/');
    productId = pathParts[pathParts.length - 1];
    
    loadProduct();
    setupRatingInput();
    setupReviewForm();
});

async function loadProduct() {
    try {
        const response = await fetch(`/api/products.php?action=get&id=${productId}`);
        const data = await response.json();
        
        document.getElementById('loading').style.display = 'none';
        
        if (data.success) {
            productData = data.data;
            renderProduct(productData);
            loadReviews();
            
            if (productData.related && productData.related.length > 0) {
                renderRelatedProducts(productData.related);
            }
        } else {
            showError('المنتج غير موجود');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('حدث خطأ في تحميل المنتج');
    }
}

function renderProduct(product) {
    const container = document.getElementById('product-container');
    document.getElementById('product-category').textContent = product.category_name || 'منتج';
    
    container.innerHTML = `
        <!-- Gallery -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <img src="${product.image || '/assets/images/placeholder.jpg'}" 
                     class="card-img-top" id="main-image" alt="${product.name}"
                     style="height: 450px; object-fit: contain; background: #f8f9fa;">
            </div>
            ${product.gallery && product.gallery.length > 0 ? `
                <div class="row g-2 mt-3 product-gallery">
                    <div class="col-3">
                        <img src="${product.image}" class="img-fluid rounded active" 
                             onclick="changeImage(this, '${product.image}')">
                    </div>
                    ${product.gallery.map(img => `
                        <div class="col-3">
                            <img src="${img}" class="img-fluid rounded" 
                                 onclick="changeImage(this, '${img}')">
                        </div>
                    `).join('')}
                </div>
            ` : ''}
        </div>
        
        <!-- Details -->
        <div class="col-lg-6">
            <div class="mb-3">
                ${product.sale_price ? `
                    <span class="badge bg-danger mb-2">خصم ${Math.round((1 - product.sale_price / product.price) * 100)}%</span>
                ` : ''}
                <h1 class="h2 fw-bold">${product.name}</h1>
                <p class="text-muted">${product.category_name || ''}</p>
            </div>
            
            <!-- Rating -->
            <div class="d-flex align-items-center mb-3">
                ${renderRating(product.rating || 0)}
                <span class="text-muted ms-2">(${product.reviews_count || 0} تقييم)</span>
                <a href="#reviews-section" class="ms-3 text-primary">اقرأ التقييمات</a>
            </div>
            
            <!-- Price -->
            <div class="mb-4">
                ${product.sale_price ? `
                    <span class="h2 text-primary fw-bold">${formatPrice(product.sale_price)}</span>
                    <span class="h5 text-muted text-decoration-line-through ms-2">${formatPrice(product.price)}</span>
                    <span class="badge bg-success ms-2">وفر ${formatPrice(product.price - product.sale_price)}</span>
                ` : `
                    <span class="h2 text-primary fw-bold">${formatPrice(product.price)}</span>
                `}
            </div>
            
            <!-- Stock Status -->
            <div class="mb-4">
                ${product.stock > 0 ? `
                    <span class="text-success"><i class="bi bi-check-circle me-1"></i>متوفر (${product.stock} قطعة)</span>
                ` : `
                    <span class="text-danger"><i class="bi bi-x-circle me-1"></i>غير متوفر</span>
                `}
            </div>
            
            <!-- Quantity & Add to Cart -->
            ${product.stock > 0 ? `
                <div class="row g-3 mb-4">
                    <div class="col-auto">
                        <div class="input-group quantity-input">
                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(-1)">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" class="form-control" id="quantity" value="1" min="1" max="${product.stock}">
                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col">
                        <button class="btn btn-primary btn-lg w-100" onclick="addToCart()">
                            <i class="bi bi-cart-plus me-2"></i>أضف للسلة
                        </button>
                    </div>
                </div>
                <div class="d-flex gap-3 mb-4">
                    <button class="btn btn-outline-danger" onclick="addToWishlist()">
                        <i class="bi bi-heart me-1"></i>أضف للمفضلة
                    </button>
                    <button class="btn btn-outline-secondary" onclick="shareProduct()">
                        <i class="bi bi-share me-1"></i>مشاركة
                    </button>
                </div>
            ` : `
                <button class="btn btn-secondary btn-lg w-100 mb-4" disabled>
                    <i class="bi bi-x-circle me-2"></i>غير متوفر حالياً
                </button>
            `}
            
            <!-- Description -->
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h5 class="card-title mb-3">وصف المنتج</h5>
                    <p class="card-text">${product.description || 'لا يوجد وصف'}</p>
                </div>
            </div>
            
            <!-- Features -->
            <div class="row g-3 mt-3">
                <div class="col-6">
                    <div class="d-flex align-items-center text-muted">
                        <i class="bi bi-truck fs-4 me-2 text-primary"></i>
                        <small>توصيل سريع</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center text-muted">
                        <i class="bi bi-shield-check fs-4 me-2 text-success"></i>
                        <small>ضمان الجودة</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center text-muted">
                        <i class="bi bi-arrow-repeat fs-4 me-2 text-warning"></i>
                        <small>إرجاع مجاني</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center text-muted">
                        <i class="bi bi-credit-card fs-4 me-2 text-info"></i>
                        <small>دفع آمن</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('reviews-section').style.display = 'block';
}

function renderRating(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="bi bi-star-fill text-warning"></i>';
        } else if (i - 0.5 <= rating) {
            stars += '<i class="bi bi-star-half text-warning"></i>';
        } else {
            stars += '<i class="bi bi-star text-warning"></i>';
        }
    }
    return stars;
}

function formatPrice(price) {
    return parseFloat(price).toFixed(2) + ' ر.س';
}

function changeImage(thumb, src) {
    document.getElementById('main-image').src = src;
    document.querySelectorAll('.product-gallery img').forEach(img => img.classList.remove('active'));
    thumb.classList.add('active');
}

function updateQuantity(delta) {
    const input = document.getElementById('quantity');
    let value = parseInt(input.value) + delta;
    value = Math.max(1, Math.min(value, productData.stock));
    input.value = value;
}

async function addToCart() {
    const quantity = parseInt(document.getElementById('quantity').value);
    
    try {
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId, quantity: quantity })
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('تمت إضافة المنتج للسلة بنجاح', 'success');
            updateCartCount();
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        showToast('يرجى تسجيل الدخول أولاً', 'warning');
    }
}

async function addToWishlist() {
    showToast('تمت إضافة المنتج للمفضلة', 'success');
}

function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: productData.name,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        showToast('تم نسخ الرابط', 'success');
    }
}

async function loadReviews() {
    try {
        const response = await fetch(`/api/reviews.php?action=list&product_id=${productId}`);
        const data = await response.json();
        
        if (data.success) {
            renderReviews(data.data);
        }
        
        // Load stats
        const statsResponse = await fetch(`/api/reviews.php?action=stats&product_id=${productId}`);
        const statsData = await statsResponse.json();
        
        if (statsData.success) {
            renderReviewStats(statsData.data);
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
    }
}

function renderReviewStats(stats) {
    document.getElementById('avg-rating').textContent = parseFloat(stats.average || 0).toFixed(1);
    document.getElementById('total-reviews').textContent = stats.total || 0;
    document.getElementById('rating-stars').innerHTML = renderRating(stats.average || 0);
    
    // Rating bars
    const barsHtml = [5, 4, 3, 2, 1].map(star => {
        const count = stats.distribution?.[star] || 0;
        const percent = stats.total > 0 ? (count / stats.total * 100) : 0;
        return `
            <div class="d-flex align-items-center mb-2">
                <span class="me-2" style="width: 20px;">${star}</span>
                <i class="bi bi-star-fill text-warning me-2"></i>
                <div class="progress flex-grow-1" style="height: 8px;">
                    <div class="progress-bar bg-warning" style="width: ${percent}%"></div>
                </div>
                <span class="ms-2 text-muted" style="width: 40px;">${count}</span>
            </div>
        `;
    }).join('');
    
    document.getElementById('rating-bars').innerHTML = barsHtml;
}

function renderReviews(reviews) {
    const container = document.getElementById('reviews-list');
    
    if (reviews.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-square-text fs-1"></i>
                <p class="mt-2">لا توجد تقييمات بعد. كن أول من يقيم هذا المنتج!</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = reviews.map(review => `
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                             style="width: 40px; height: 40px;">
                            ${review.user_name?.charAt(0) || 'م'}
                        </div>
                        <div>
                            <strong>${review.user_name || 'مستخدم'}</strong>
                            <div>${renderRating(review.rating)}</div>
                        </div>
                    </div>
                    <small class="text-muted">${formatDate(review.created_at)}</small>
                </div>
                <p class="mb-0">${review.comment || ''}</p>
            </div>
        </div>
    `).join('');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('ar-SA');
}

function renderRelatedProducts(products) {
    document.getElementById('related-section').style.display = 'block';
    const container = document.getElementById('related-products');
    
    container.innerHTML = products.map(product => `
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <a href="/products/${product.id}" class="text-decoration-none">
                    <img src="${product.image || '/assets/images/placeholder.jpg'}" 
                         class="card-img-top" alt="${product.name}"
                         style="height: 180px; object-fit: cover;">
                </a>
                <div class="card-body">
                    <a href="/products/${product.id}" class="text-decoration-none text-dark">
                        <h6 class="card-title text-truncate">${product.name}</h6>
                    </a>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-primary fw-bold">${formatPrice(product.sale_price || product.price)}</span>
                        <button class="btn btn-sm btn-outline-primary" onclick="addToCartQuick(${product.id})">
                            <i class="bi bi-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function setupRatingInput() {
    const stars = document.querySelectorAll('#rating-input i');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            document.getElementById('rating-value').value = rating;
            stars.forEach((s, i) => {
                s.className = i < rating ? 'bi bi-star-fill' : 'bi bi-star';
            });
        });
        star.addEventListener('mouseenter', function() {
            const rating = this.dataset.rating;
            stars.forEach((s, i) => {
                s.className = i < rating ? 'bi bi-star-fill' : 'bi bi-star';
            });
        });
    });
    
    document.getElementById('rating-input').addEventListener('mouseleave', function() {
        const rating = document.getElementById('rating-value').value;
        stars.forEach((s, i) => {
            s.className = i < rating ? 'bi bi-star-fill' : 'bi bi-star';
        });
    });
}

function setupReviewForm() {
    document.getElementById('review-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const rating = document.getElementById('rating-value').value;
        const comment = document.getElementById('review-comment').value;
        
        try {
            const response = await fetch('/api/reviews.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'create',
                    product_id: productId, 
                    rating: parseInt(rating), 
                    comment: comment 
                })
            });
            const data = await response.json();
            
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
                showToast('تم إرسال تقييمك بنجاح', 'success');
                loadReviews();
                document.getElementById('review-form').reset();
            } else {
                showToast(data.message || 'حدث خطأ', 'error');
            }
        } catch (error) {
            showToast('يرجى تسجيل الدخول أولاً', 'warning');
        }
    });
}

function showError(message) {
    document.getElementById('product-container').innerHTML = `
        <div class="col-12 text-center py-5">
            <i class="bi bi-exclamation-circle text-danger display-1"></i>
            <h4 class="mt-3">${message}</h4>
            <a href="/products" class="btn btn-primary mt-3">العودة للمنتجات</a>
        </div>
    `;
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

async function addToCartQuick(id) {
    try {
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: id, quantity: 1 })
        });
        const data = await response.json();
        if (data.success) {
            showToast('تمت الإضافة للسلة', 'success');
            updateCartCount();
        }
    } catch (error) {
        showToast('يرجى تسجيل الدخول', 'warning');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
