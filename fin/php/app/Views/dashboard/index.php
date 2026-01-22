<?php
$title = 'لوحة التحكم - SwiftCart';
$mainClass = '';

ob_start();
?>

<div class="d-flex">
    <!-- Sidebar -->
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-grow-1">
        <?php include __DIR__ . '/partials/header.php'; ?>
        
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">لوحة التحكم</h4>
                <span class="text-muted"><?= date('l, d F Y') ?></span>
            </div>
            
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1 opacity-75">إجمالي المبيعات</h6>
                                    <h3 class="fw-bold mb-0" id="total-sales">0</h3>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-currency-dollar fs-4"></i>
                                </div>
                            </div>
                            <small class="opacity-75"><i class="bi bi-arrow-up"></i> 12% من الشهر الماضي</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1 opacity-75">الطلبات الجديدة</h6>
                                    <h3 class="fw-bold mb-0" id="new-orders">0</h3>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-bag fs-4"></i>
                                </div>
                            </div>
                            <small class="opacity-75">بانتظار المعالجة</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1 opacity-75">المنتجات</h6>
                                    <h3 class="fw-bold mb-0" id="total-products">0</h3>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-box fs-4"></i>
                                </div>
                            </div>
                            <small class="opacity-75">منتج نشط</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1 opacity-75">المستخدمين</h6>
                                    <h3 class="fw-bold mb-0" id="total-users">0</h3>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="bi bi-people fs-4"></i>
                                </div>
                            </div>
                            <small class="opacity-75">مستخدم مسجل</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Recent Orders -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">آخر الطلبات</h6>
                            <a href="/dashboard/orders" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>العميل</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent-orders">
                                        <tr>
                                            <td colspan="5" class="text-center py-4">جاري التحميل...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Products -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="fw-bold mb-0">المنتجات الأكثر مبيعاً</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" id="top-products">
                                <li class="list-group-item text-center py-4">جاري التحميل...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
});

async function loadDashboardStats() {
    // Load stats from API
    // This would fetch from your admin API endpoints
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
