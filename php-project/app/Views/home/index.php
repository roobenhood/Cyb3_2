<?php
$title = 'SwiftCart - متجرك الإلكتروني';

ob_start();
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">تسوق بذكاء مع SwiftCart</h1>
                <p class="lead mb-4">اكتشف آلاف المنتجات بأفضل الأسعار مع توصيل سريع لباب منزلك</p>
                <div class="d-flex gap-3">
                    <a href="/products" class="btn btn-light btn-lg">
                        <i class="bi bi-bag me-2"></i>تسوق الآن
                    </a>
                    <a href="/categories" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-grid me-2"></i>التصنيفات
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="/assets/images/hero-shopping.svg" alt="تسوق" class="img-fluid" style="max-height: 400px;">
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="text-center">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-truck fs-3 text-primary"></i>
                    </div>
                    <h6 class="fw-bold">توصيل سريع</h6>
                    <p class="text-muted small mb-0">توصيل خلال 24 ساعة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-shield-check fs-3 text-success"></i>
                    </div>
                    <h6 class="fw-bold">دفع آمن</h6>
                    <p class="text-muted small mb-0">طرق دفع متعددة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-arrow-repeat fs-3 text-warning"></i>
                    </div>
                    <h6 class="fw-bold">إرجاع سهل</h6>
                    <p class="text-muted small mb-0">14 يوم للإرجاع</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi bi-headset fs-3 text-info"></i>
                    </div>
                    <h6 class="fw-bold">دعم 24/7</h6>
                    <p class="text-muted small mb-0">نحن هنا لمساعدتك</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">تصفح التصنيفات</h3>
            <a href="/categories" class="btn btn-outline-primary">عرض الكل</a>
        </div>
        <div class="row g-4" id="categories-grid">
            <!-- يتم تحميلها بـ JavaScript -->
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">منتجات مميزة</h3>
            <a href="/products?featured=1" class="btn btn-outline-primary">عرض الكل</a>
        </div>
        <div class="row g-4" id="featured-products">
            <!-- يتم تحميلها بـ JavaScript -->
        </div>
    </div>
</section>

<!-- New Arrivals -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">وصل حديثاً</h3>
            <a href="/products?sort=newest" class="btn btn-outline-primary">عرض الكل</a>
        </div>
        <div class="row g-4" id="new-products">
            <!-- يتم تحميلها بـ JavaScript -->
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <h4 class="fw-bold mb-3">اشترك في النشرة البريدية</h4>
                <p class="mb-4">احصل على آخر العروض والخصومات مباشرة في بريدك</p>
                <form class="d-flex gap-2" id="newsletter-form">
                    <input type="email" class="form-control" placeholder="بريدك الإلكتروني" required>
                    <button type="submit" class="btn btn-light">اشترك</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadFeaturedProducts();
    loadNewProducts();
});

async function loadCategories() {
    try {
        const response = await fetch('/api/categories.php?action=list');
        const data = await response.json();
        if (data.success) {
            renderCategories(data.data.slice(0, 6));
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

async function loadFeaturedProducts() {
    try {
        const response = await fetch('/api/products.php?action=list&featured=1&limit=4');
        const data = await response.json();
        if (data.success) {
            renderProducts('featured-products', data.data);
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

async function loadNewProducts() {
    try {
        const response = await fetch('/api/products.php?action=list&sort=newest&limit=4');
        const data = await response.json();
        if (data.success) {
            renderProducts('new-products', data.data);
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

function renderCategories(categories) {
    const container = document.getElementById('categories-grid');
    container.innerHTML = categories.map(cat => `
        <div class="col-md-4 col-lg-2">
            <a href="/products?category=${cat.id}" class="card text-decoration-none h-100 border-0 shadow-sm hover-lift">
                <div class="card-body text-center py-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                        <i class="bi ${cat.icon || 'bi-grid'} fs-3 text-primary"></i>
                    </div>
                    <h6 class="card-title mb-1">${cat.name}</h6>
                    <small class="text-muted">${cat.product_count || 0} منتج</small>
                </div>
            </a>
        </div>
    `).join('');
}

function renderProducts(containerId, products) {
    const container = document.getElementById(containerId);
    container.innerHTML = products.map(product => `
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <img src="${product.image || '/assets/images/placeholder.jpg'}" class="card-img-top" 
                     alt="${product.name}" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h6 class="card-title mb-1">${product.name}</h6>
                    <p class="text-muted small mb-2">${product.category_name || ''}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            ${product.sale_price ? `
                                <span class="text-primary fw-bold">${product.sale_price} ر.س</span>
                                <small class="text-muted text-decoration-line-through me-1">${product.price}</small>
                            ` : `
                                <span class="text-primary fw-bold">${product.price} ر.س</span>
                            `}
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="addToCart(${product.id})">
                            <i class="bi bi-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

async function addToCart(productId) {
    try {
        const response = await fetch('/api/cart.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });
        const data = await response.json();
        if (data.success) {
            alert('تمت الإضافة للسلة');
            updateCartCount();
        }
    } catch (error) {
        alert('يرجى تسجيل الدخول أولاً');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
