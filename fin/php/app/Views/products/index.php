<?php
$title = 'المنتجات - SwiftCart';
$mainClass = '';

ob_start();
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>تصفية المنتجات</h5>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">البحث</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search-input" placeholder="ابحث عن منتج...">
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Categories -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">التصنيف</label>
                        <select class="form-select" id="category-filter">
                            <option value="">جميع التصنيفات</option>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">نطاق السعر</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" class="form-control" id="min-price" placeholder="من">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" id="max-price" placeholder="إلى">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sort -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">الترتيب</label>
                        <select class="form-select" id="sort-filter">
                            <option value="newest">الأحدث</option>
                            <option value="price_asc">السعر: من الأقل للأعلى</option>
                            <option value="price_desc">السعر: من الأعلى للأقل</option>
                            <option value="name">الاسم</option>
                            <option value="popular">الأكثر مبيعاً</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-primary w-100" onclick="applyFilters()">
                        <i class="bi bi-check2 me-2"></i>تطبيق الفلترة
                    </button>
                    <button class="btn btn-outline-secondary w-100 mt-2" onclick="resetFilters()">
                        <i class="bi bi-x-circle me-2"></i>إعادة تعيين
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">المنتجات</h4>
                    <p class="text-muted mb-0" id="results-count">جاري التحميل...</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" onclick="setView('grid')" id="grid-view-btn">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="setView('list')" id="list-view-btn">
                        <i class="bi bi-list-ul"></i>
                    </button>
                </div>
            </div>
            
            <!-- Products -->
            <div class="row g-4" id="products-grid">
                <!-- Loading -->
                <div class="col-12 text-center py-5" id="loading-indicator">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <nav class="mt-5" id="pagination-container">
                <ul class="pagination justify-content-center" id="pagination">
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let viewMode = 'grid';

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadProducts();
    
    // URL params
    const params = new URLSearchParams(window.location.search);
    if (params.get('category')) document.getElementById('category-filter').value = params.get('category');
    if (params.get('search')) document.getElementById('search-input').value = params.get('search');
    if (params.get('sort')) document.getElementById('sort-filter').value = params.get('sort');
});

async function loadCategories() {
    try {
        const response = await fetch('/api/categories.php?action=list');
        const data = await response.json();
        if (data.success) {
            const select = document.getElementById('category-filter');
            data.data.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = `${cat.name} (${cat.product_count || 0})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadProducts(page = 1) {
    currentPage = page;
    document.getElementById('loading-indicator').style.display = 'block';
    
    const params = new URLSearchParams({
        action: 'list',
        page: page,
        per_page: 12,
        search: document.getElementById('search-input').value,
        category_id: document.getElementById('category-filter').value,
        min_price: document.getElementById('min-price').value,
        max_price: document.getElementById('max-price').value,
        sort: document.getElementById('sort-filter').value
    });
    
    try {
        const response = await fetch(`/api/products.php?${params}`);
        const data = await response.json();
        
        document.getElementById('loading-indicator').style.display = 'none';
        
        if (data.success) {
            renderProducts(data.data);
            document.getElementById('results-count').textContent = `${data.pagination.total} منتج`;
            totalPages = data.pagination.last_page;
            renderPagination();
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('products-grid').innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-exclamation-circle text-danger fs-1"></i>
                <p class="mt-3">حدث خطأ في تحميل المنتجات</p>
            </div>
        `;
    }
}

function renderProducts(products) {
    const container = document.getElementById('products-grid');
    
    if (products.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox text-muted fs-1"></i>
                <p class="mt-3 text-muted">لا توجد منتجات</p>
            </div>
        `;
        return;
    }
    
    if (viewMode === 'grid') {
        container.innerHTML = products.map(product => `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-lift product-card">
                    <a href="/products/${product.id}" class="text-decoration-none">
                        <div class="position-relative overflow-hidden">
                            <img src="${product.image || '/assets/images/placeholder.jpg'}" 
                                 class="card-img-top" alt="${product.name}" 
                                 style="height: 220px; object-fit: cover;">
                            ${product.sale_price ? `
                                <span class="position-absolute top-0 start-0 bg-danger text-white px-2 py-1 m-2 rounded-pill small">
                                    خصم ${Math.round((1 - product.sale_price / product.price) * 100)}%
                                </span>
                            ` : ''}
                            ${product.stock <= 0 ? `
                                <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center">
                                    <span class="badge bg-danger fs-6">نفذت الكمية</span>
                                </div>
                            ` : ''}
                        </div>
                    </a>
                    <div class="card-body">
                        <a href="/products/${product.id}" class="text-decoration-none text-dark">
                            <h6 class="card-title mb-1 text-truncate">${product.name}</h6>
                        </a>
                        <p class="text-muted small mb-2">${product.category_name || ''}</p>
                        <div class="d-flex align-items-center mb-2">
                            ${renderRating(product.rating || 0)}
                            <span class="text-muted small ms-2">(${product.reviews_count || 0})</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                ${product.sale_price ? `
                                    <span class="text-primary fw-bold">${formatPrice(product.sale_price)}</span>
                                    <small class="text-muted text-decoration-line-through me-1">${formatPrice(product.price)}</small>
                                ` : `
                                    <span class="text-primary fw-bold">${formatPrice(product.price)}</span>
                                `}
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="addToCart(${product.id})" 
                                    ${product.stock <= 0 ? 'disabled' : ''}>
                                <i class="bi bi-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    } else {
        container.innerHTML = products.map(product => `
            <div class="col-12">
                <div class="card border-0 shadow-sm hover-lift">
                    <div class="row g-0">
                        <div class="col-md-3">
                            <a href="/products/${product.id}">
                                <img src="${product.image || '/assets/images/placeholder.jpg'}" 
                                     class="img-fluid rounded-start h-100" alt="${product.name}"
                                     style="object-fit: cover; min-height: 150px;">
                            </a>
                        </div>
                        <div class="col-md-9">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="/products/${product.id}" class="text-decoration-none text-dark">
                                            <h5 class="card-title mb-1">${product.name}</h5>
                                        </a>
                                        <p class="text-muted small mb-2">${product.category_name || ''}</p>
                                        <div class="d-flex align-items-center mb-2">
                                            ${renderRating(product.rating || 0)}
                                            <span class="text-muted small ms-2">(${product.reviews_count || 0} تقييم)</span>
                                        </div>
                                        <p class="card-text text-muted">${(product.description || '').substring(0, 150)}...</p>
                                    </div>
                                    <div class="text-end">
                                        ${product.sale_price ? `
                                            <div class="text-primary fw-bold fs-5">${formatPrice(product.sale_price)}</div>
                                            <small class="text-muted text-decoration-line-through">${formatPrice(product.price)}</small>
                                        ` : `
                                            <div class="text-primary fw-bold fs-5">${formatPrice(product.price)}</div>
                                        `}
                                        <button class="btn btn-primary mt-2" onclick="addToCart(${product.id})"
                                                ${product.stock <= 0 ? 'disabled' : ''}>
                                            <i class="bi bi-cart-plus me-1"></i>أضف للسلة
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }
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

function renderPagination() {
    const container = document.getElementById('pagination');
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadProducts(${currentPage - 1}); return false;">السابق</a>
    </li>`;
    
    // Pages
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadProducts(${i}); return false;">${i}</a>
            </li>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    // Next
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadProducts(${currentPage + 1}); return false;">التالي</a>
    </li>`;
    
    container.innerHTML = html;
}

function applyFilters() {
    loadProducts(1);
}

function resetFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    document.getElementById('sort-filter').value = 'newest';
    loadProducts(1);
}

function setView(mode) {
    viewMode = mode;
    document.getElementById('grid-view-btn').classList.toggle('active', mode === 'grid');
    document.getElementById('list-view-btn').classList.toggle('active', mode === 'list');
    loadProducts(currentPage);
}

async function addToCart(productId) {
    try {
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId, quantity: 1 })
        });
        const data = await response.json();
        if (data.success) {
            showToast('تمت إضافة المنتج للسلة', 'success');
            updateCartCount();
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        showToast('يرجى تسجيل الدخول أولاً', 'warning');
    }
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
