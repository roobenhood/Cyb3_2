<?php use App\Helpers\Session; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-cart3 me-2"></i>SwiftCart
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/"><i class="bi bi-house me-1"></i>الرئيسية</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/products"><i class="bi bi-grid me-1"></i>المنتجات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/categories"><i class="bi bi-tags me-1"></i>التصنيفات</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (Session::isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/cart">
                            <i class="bi bi-cart me-1"></i>السلة
                            <span class="badge bg-danger" id="cart-count">0</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars(Session::user()['name'] ?? 'المستخدم') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i>الملف الشخصي</a></li>
                            <li><a class="dropdown-item" href="/orders"><i class="bi bi-bag me-2"></i>طلباتي</a></li>
                            <?php if ((Session::user()['role'] ?? '') === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/dashboard"><i class="bi bi-speedometer2 me-2"></i>لوحة التحكم</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="/logout" method="POST" class="d-inline">
                                    <?= \App\Middleware\CsrfMiddleware::field() ?>
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>تسجيل الخروج
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login"><i class="bi bi-box-arrow-in-left me-1"></i>تسجيل الدخول</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/register"><i class="bi bi-person-plus me-1"></i>إنشاء حساب</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
