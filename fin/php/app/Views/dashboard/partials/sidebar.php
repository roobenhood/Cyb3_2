<?php use App\Helpers\Session; ?>

<div class="bg-dark text-white" style="width: 250px; min-height: 100vh;">
    <div class="p-3 border-bottom border-secondary">
        <a href="/dashboard" class="text-white text-decoration-none">
            <h5 class="fw-bold mb-0"><i class="bi bi-cart3 me-2"></i>SwiftCart</h5>
        </a>
    </div>
    
    <nav class="p-3">
        <ul class="nav flex-column">
            <li class="nav-item mb-1">
                <a href="/dashboard" class="nav-link text-white <?= $currentPage === 'dashboard' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>لوحة التحكم
                </a>
            </li>
            
            <li class="nav-item mb-1">
                <a href="/dashboard/orders" class="nav-link text-white <?= $currentPage === 'orders' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-bag me-2"></i>الطلبات
                    <span class="badge bg-danger ms-auto" id="pending-orders-badge">0</span>
                </a>
            </li>
            
            <li class="nav-item mb-1">
                <a href="/dashboard/products" class="nav-link text-white <?= $currentPage === 'products' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-box me-2"></i>المنتجات
                </a>
            </li>
            
            <li class="nav-item mb-1">
                <a href="/dashboard/categories" class="nav-link text-white <?= $currentPage === 'categories' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-tags me-2"></i>التصنيفات
                </a>
            </li>
            
            <li class="nav-item mb-1">
                <a href="/dashboard/users" class="nav-link text-white <?= $currentPage === 'users' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-people me-2"></i>المستخدمين
                </a>
            </li>
            
            <li class="nav-item mb-1">
                <a href="/dashboard/reviews" class="nav-link text-white <?= $currentPage === 'reviews' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-star me-2"></i>التقييمات
                </a>
            </li>
            
            <li class="nav-item mb-1">
                <a href="/dashboard/coupons" class="nav-link text-white <?= $currentPage === 'coupons' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-ticket-perforated me-2"></i>الكوبونات
                </a>
            </li>
            
            <hr class="my-3 border-secondary">
            
            <li class="nav-item mb-1">
                <a href="/dashboard/settings" class="nav-link text-white <?= $currentPage === 'settings' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-gear me-2"></i>الإعدادات
                </a>
            </li>
            
            <li class="nav-item mb-1">
                <a href="/dashboard/reports" class="nav-link text-white <?= $currentPage === 'reports' ? 'active bg-primary rounded' : '' ?>">
                    <i class="bi bi-graph-up me-2"></i>التقارير
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="p-3 mt-auto border-top border-secondary">
        <div class="d-flex align-items-center">
            <div class="bg-primary rounded-circle p-2 me-2">
                <i class="bi bi-person text-white"></i>
            </div>
            <div class="flex-grow-1">
                <small class="d-block text-white"><?= htmlspecialchars(Session::user()['name'] ?? 'Admin') ?></small>
                <small class="text-muted">مدير</small>
            </div>
        </div>
    </div>
</div>
