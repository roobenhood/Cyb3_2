<?php use App\Helpers\Session; ?>

<header class="bg-white border-bottom px-4 py-3">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-dark d-lg-none me-2" id="toggle-sidebar">
                <i class="bi bi-list fs-4"></i>
            </button>
            <form class="d-none d-md-flex">
                <div class="input-group" style="width: 300px;">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-0" placeholder="بحث...">
                </div>
            </form>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn btn-link text-dark position-relative" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notification-count">
                        3
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow" style="width: 300px;">
                    <h6 class="dropdown-header">الإشعارات</h6>
                    <div id="notifications-list">
                        <a href="#" class="dropdown-item py-2">
                            <div class="d-flex">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                    <i class="bi bi-bag text-primary"></i>
                                </div>
                                <div>
                                    <small class="d-block">طلب جديد #1234</small>
                                    <small class="text-muted">منذ 5 دقائق</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="/dashboard/notifications" class="dropdown-item text-center text-primary">عرض الكل</a>
                </div>
            </div>
            
            <!-- User Menu -->
            <div class="dropdown">
                <button class="btn btn-link text-dark d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <div class="bg-primary rounded-circle p-2">
                        <i class="bi bi-person text-white"></i>
                    </div>
                    <span class="d-none d-md-inline"><?= htmlspecialchars(Session::user()['name'] ?? 'Admin') ?></span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i>الملف الشخصي</a></li>
                    <li><a class="dropdown-item" href="/dashboard/settings"><i class="bi bi-gear me-2"></i>الإعدادات</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="/logout" method="POST">
                            <?= \App\Middleware\CsrfMiddleware::field() ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>تسجيل الخروج
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
