<?php
/**
 * صفحة 404 - الصفحة غير موجودة
 * إعادة توجيه للصفحة الرئيسية
 */
require_once 'config.php';

$lang = getCurrentLanguage();
$redirectDelay = 5; // ثواني قبل الإعادة التلقائية

$pageTitle = __('page_not_found') ?? 'الصفحة غير موجودة';
include 'includes/header.php';
?>

<div class="container" style="text-align: center; padding: 80px 20px;">
    <div style="font-size: 6rem; margin-bottom: 20px; opacity: 0.8;">🔍</div>
    <h1 style="color: var(--neon-orange); font-size: 3rem; margin-bottom: 15px;">404</h1>
    <h2 style="color: var(--text-color); margin-bottom: 20px;"><?php echo __('page_not_found') ?? 'الصفحة غير موجودة'; ?></h2>
    <p style="color: var(--text-muted); margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">
        <?php echo __('page_not_found_desc') ?? 'عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.'; ?>
    </p>
    
    <p style="color: var(--text-muted); margin-bottom: 20px;">
        <?php echo __('redirect_in') ?? 'سيتم إعادة توجيهك خلال'; ?> <span id="countdown"><?php echo $redirectDelay; ?></span> <?php echo __('seconds') ?? 'ثواني'; ?>...
    </p>
    
    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-neon">
            🏠 <?php echo __('home') ?? 'الرئيسية'; ?>
        </a>
        <a href="<?php echo SITE_URL; ?>/challenges.php" class="btn btn-outline">
            🚩 <?php echo __('challenges') ?? 'التحديات'; ?>
        </a>
        <a href="javascript:history.back()" class="btn btn-outline">
            ← <?php echo __('go_back') ?? 'رجوع'; ?>
        </a>
    </div>
</div>

<script>
// العد التنازلي والتوجيه التلقائي
(function() {
    var seconds = <?php echo $redirectDelay; ?>;
    var countdown = document.getElementById('countdown');
    
    var timer = setInterval(function() {
        seconds--;
        if (countdown) countdown.textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(timer);
            window.location.href = '<?php echo SITE_URL; ?>/index.php';
        }
    }, 1000);
})();
</script>

<?php include 'includes/footer.php'; ?>