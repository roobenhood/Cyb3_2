<?php
/**
 * ملف التنسيقات المشتركة لجميع اللابات
 * مع دعم الفلاج الديناميكي
 */

// تحميل lab_helper إذا لم يكن محملاً
if (!function_exists('findAndLoadConfig')) {
    require_once __DIR__ . '/lab_helper.php';
}

// التأكد من تحميل الإعدادات
if (!defined('SITE_URL')) {
    findAndLoadConfig();
}

/**
 * الحصول على أنماط CSS للابات
 * تستخدم في اللابات الجديدة
 */
function getLabStyles() {
    return <<<CSS
<style>
    :root {
        --lab-primary: #00ff88;
        --lab-secondary: #00d9ff;
        --lab-warning: #feca57;
        --lab-danger: #ff6b6b;
        --lab-dark: #0a0a0a;
    }
    
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
        font-family: 'Cairo', 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, var(--lab-dark) 0%, #1a1a2e 50%, #16213e 100%);
        min-height: 100vh;
        color: #fff;
        direction: rtl;
    }
    
    .lab-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .lab-header-bar {
        background: rgba(255,255,255,0.05);
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .lab-header-bar h1 { font-size: 1.2rem; color: var(--lab-primary); }
    .lab-header-bar a { color: #888; text-decoration: none; }
    .lab-header-bar a:hover { color: #fff; }
    
    .scenario-box, .hint-box, .code-box, .success-box, .lesson-box {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .scenario-box { border-color: rgba(0,255,136,0.3); }
    .scenario-box h3 { color: var(--lab-primary); margin-bottom: 10px; }
    
    .hint-box { border-color: rgba(0,217,255,0.3); background: rgba(0,217,255,0.05); }
    
    .code-box { 
        background: #000; 
        font-family: 'Courier New', monospace;
        word-break: break-all;
    }
    .code-box code { color: var(--lab-primary); }
    
    .success-box { 
        border-color: var(--lab-primary); 
        text-align: center;
        animation: glow 2s infinite;
    }
    @keyframes glow {
        0%, 100% { box-shadow: 0 0 5px rgba(0,255,136,0.3); }
        50% { box-shadow: 0 0 20px rgba(0,255,136,0.5); }
    }
    
    .lesson-box { border-color: rgba(254,202,87,0.3); }
    .lesson-box h3 { color: var(--lab-warning); margin-bottom: 10px; }
    .lesson-box ul { margin-right: 20px; color: #bbb; }
    .lesson-box li { margin-bottom: 5px; }
    
    .progress-bar {
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
        height: 8px;
        margin: 20px 0;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--lab-primary), var(--lab-secondary));
        transition: width 0.5s;
    }
    
    .btn {
        display: inline-block;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s;
        margin: 5px;
    }
    .btn-primary { background: var(--lab-primary); color: #000; }
    .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
    .btn-success { background: var(--lab-primary); color: #000; font-weight: bold; }
    .btn-warning { background: var(--lab-warning); color: #000; }
    .btn-outline { background: transparent; border: 1px solid var(--lab-primary); color: var(--lab-primary); }
    .btn:hover { transform: translateY(-2px); opacity: 0.9; }
    
    .alert { padding: 15px; border-radius: 8px; margin-bottom: 15px; }
    .alert-success { background: rgba(0,255,136,0.1); border: 1px solid var(--lab-primary); color: var(--lab-primary); }
    .alert-info { background: rgba(0,217,255,0.1); border: 1px solid var(--lab-secondary); color: var(--lab-secondary); }
    .alert-warning { background: rgba(254,202,87,0.1); border: 1px solid var(--lab-warning); color: var(--lab-warning); }
    
    .nav-buttons { margin-top: 30px; text-align: center; }
    
    .upload-box, .upload-form {
        background: rgba(255,255,255,0.03);
        padding: 25px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .upload-box h3 { color: var(--lab-primary); margin-bottom: 15px; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; color: #aaa; }
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 8px;
        background: rgba(0,0,0,0.3);
        color: #fff;
        font-size: 1rem;
    }
    .form-group input:focus, .form-group textarea:focus {
        outline: none;
        border-color: var(--lab-primary);
    }
    .form-group small { color: #666; font-size: 0.85rem; }
</style>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
CSS;
}

/**
 * رندر JavaScript لتحويل الروابط - يستخدم في الملفات التي تستخدم HTML يدوي
 */
function renderLabScripts() {
    $siteUrl = defined('SITE_URL') ? SITE_URL : '';
    $challengeId = $_SESSION['current_challenge_id'] ?? 0;
    $currentPage = $_GET['page'] ?? 'index';
?>
<script>
(function() {
    const siteUrl = <?php echo json_encode($siteUrl); ?>;
    const challengeId = <?php echo json_encode($challengeId); ?>;
    const currentPage = <?php echo json_encode($currentPage); ?>;
    
    if (!challengeId) return;
    
    function buildLabUrl(pageName, extraParams) {
        let url = siteUrl + '/challenge_play.php?id=' + challengeId + '&page=' + encodeURIComponent(pageName);
        if (extraParams) url += '&' + extraParams;
        return url;
    }
    
    function extractPageName(href) {
        let pageName = href.replace(/^\.\//, '').replace(/^\.\.\/+/g, '');
        let queryString = '';
        if (pageName.includes('?')) {
            const parts = pageName.split('?');
            pageName = parts[0];
            queryString = parts[1];
        }
        if (pageName.endsWith('.php')) pageName = pageName.slice(0, -4);
        return { pageName, queryString };
    }
    
    function shouldSkipLink(href) {
        if (!href) return true;
        if (href.startsWith('http://') || href.startsWith('https://') || href.startsWith('//')) return true;
        if (href.startsWith('#') || href.startsWith('javascript:')) return true;
        if (href.includes('challenge_play.php') || href.includes('challenges.php')) return true;
        if (href.includes('challenge_view.php') || href.includes('login.php') || href.includes('logout.php')) return true;
        return false;
    }
    
    document.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute('href');
        if (shouldSkipLink(href)) return;
        const { pageName, queryString } = extractPageName(href);
        if (!pageName) return;
        link.setAttribute('href', buildLabUrl(pageName, queryString));
    });
    
    document.querySelectorAll('form').forEach(form => {
        const action = form.getAttribute('action');
        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (action && shouldSkipLink(action)) return;
        
        let targetPage = currentPage;
        let extraQueryString = '';
        if (action && action !== '' && action !== '#') {
            const { pageName, queryString } = extractPageName(action);
            if (pageName) { targetPage = pageName; extraQueryString = queryString; }
        }
        
        if (method === 'get') {
            form.setAttribute('action', siteUrl + '/challenge_play.php');
            if (!form.querySelector('input[name="id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden'; idInput.name = 'id'; idInput.value = challengeId;
                form.prepend(idInput);
            }
            if (!form.querySelector('input[name="page"]')) {
                const pageInput = document.createElement('input');
                pageInput.type = 'hidden'; pageInput.name = 'page'; pageInput.value = targetPage;
                form.prepend(pageInput);
            }
        } else {
            form.setAttribute('action', buildLabUrl(targetPage, extraQueryString));
        }
    });
    
    document.querySelectorAll('.flag-display').forEach(el => {
        el.addEventListener('click', function() {
            navigator.clipboard.writeText(this.textContent.trim());
            const original = this.textContent;
            this.style.background = 'var(--lab-primary, var(--primary))';
            this.style.color = '#000';
            this.textContent = '✓ تم النسخ';
            setTimeout(() => {
                this.style.background = '#000';
                this.style.color = 'var(--lab-primary, var(--primary))';
                this.textContent = original;
            }, 1000);
        });
    });
})();
</script>
<?php
}

/**
 * رندر هيدر اللاب (يعمل بدون معاملات أو مع معاملات)
 * استخدم: echo renderLabHeader() أو renderLabHeader() فقط
 */
function renderLabHeader($title = null, $folderName = null) {
    // إذا تم تمرير معاملات
    if ($title !== null) {
        $siteUrl = defined('SITE_URL') ? SITE_URL : '../../../..';
        $challengesUrl = $siteUrl . '/challenges.php';
        $html = <<<HTML
<div class="lab-header-bar">
    <h1>🔐 {$title}</h1>
    <a href="{$challengesUrl}">← العودة للتحديات</a>
</div>
HTML;
        echo $html;
        return '';
    }
    
    // بدون معاملات - استخدم الطريقة القديمة
    renderLabHeaderLegacy();
    return '';
}

/**
 * رندر شريط التقدم
 * استخدم: echo renderProgressBar() أو renderProgressBar() فقط
 */
function renderProgressBar($current, $total) {
    $percent = ($current / $total) * 100;
    $html = <<<HTML
<div class="progress-bar">
    <div class="progress-fill" style="width: {$percent}%"></div>
</div>
<p style="text-align: center; color: #888; margin-bottom: 20px;">الخطوة {$current} من {$total}</p>
HTML;
    echo $html;
    return '';
}

/**
 * رندر هيدر اللاب القديم (للتوافق)
 */
function renderLabHeaderLegacy() {
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $GLOBALS['lab_title'] ?? 'Security Lab'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00ff88;
            --secondary: #00d9ff;
            --warning: #feca57;
            --danger: #ff6b6b;
            --dark: #0a0a0a;
            --darker: #050505;
            --card-bg: rgba(255,255,255,0.03);
            --border: rgba(255,255,255,0.1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Cairo', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
            line-height: 1.7;
        }
        
        .lab-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .lab-header {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .lab-header h1 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .lab-header p { color: #888; }
        
        .lab-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 10px;
        }
        
        .badge-beginner { background: #27ae60; color: #fff; }
        .badge-intermediate { background: #f39c12; color: #000; }
        .badge-advanced { background: #e74c3c; color: #fff; }
        
        .lab-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .lab-card h2 { color: var(--primary); margin-bottom: 15px; font-size: 1.3rem; }
        .lab-card h3 { color: var(--secondary); margin-bottom: 10px; font-size: 1.1rem; }
        .lab-card p { color: #bbb; margin-bottom: 10px; }
        
        .scenario-box {
            background: rgba(0,255,136,0.05);
            border-right: 4px solid var(--primary);
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 12px 12px 0;
        }
        
        .scenario-box h4 { color: var(--primary); margin-bottom: 10px; }
        
        .objective-box {
            background: rgba(254,202,87,0.08);
            border: 1px solid rgba(254,202,87,0.3);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .objective-box h4 { color: var(--warning); margin-bottom: 10px; }
        
        .vuln-app {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            margin: 25px 0;
        }
        
        .app-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            padding: 12px 20px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .app-url {
            flex: 1;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            direction: ltr;
            font-family: monospace;
        }
        
        .app-body {
            padding: 25px;
            color: #333;
        }
        
        .app-body h3 { color: #333; margin-bottom: 15px; }
        .app-body p { color: #666; }
        
        .app-form input, .app-form textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 12px;
            font-family: inherit;
        }
        
        .app-form input:focus, .app-form textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .app-form button {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .app-form button:hover { transform: translateY(-2px); }
        
        .lab-form {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .lab-form input, .lab-form textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            margin-bottom: 15px;
            font-family: 'Courier New', monospace;
        }
        
        .lab-form textarea { min-height: 100px; resize: vertical; }
        .lab-form input:focus, .lab-form textarea:focus { outline: none; border-color: var(--primary); }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .btn-primary { background: var(--primary); color: #000; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,255,136,0.3); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid var(--border); }
        .btn-secondary:hover { background: rgba(255,255,255,0.15); }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 15px 0;
        }
        
        .alert-success { background: rgba(0,255,136,0.1); border: 1px solid var(--primary); color: var(--primary); }
        .alert-error { background: rgba(255,107,107,0.1); border: 1px solid var(--danger); color: var(--danger); }
        .alert-info { background: rgba(0,217,255,0.1); border: 1px solid var(--secondary); color: var(--secondary); }
        
        .success-box {
            background: linear-gradient(135deg, rgba(0,255,136,0.1), rgba(0,200,100,0.05));
            border: 2px solid var(--primary);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin: 30px 0;
        }
        
        .success-box h2 { color: var(--primary); font-size: 2rem; margin-bottom: 15px; }
        
        .flag-display {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            background: #000;
            color: var(--primary);
            padding: 15px 30px;
            border-radius: 10px;
            display: inline-block;
            margin: 20px 0;
            letter-spacing: 1px;
            user-select: all;
            cursor: pointer;
            word-break: break-all;
        }
        
        .flag-warning {
            background: rgba(255,107,107,0.1);
            border: 1px solid var(--danger);
            border-radius: 8px;
            padding: 10px 15px;
            margin-top: 15px;
            font-size: 0.85rem;
            color: var(--danger);
        }
        
        code {
            background: rgba(0,0,0,0.5);
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: var(--primary);
        }
        
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        
        .text-center { text-align: center; }
        .text-muted { color: #888; }
        .mt-20 { margin-top: 20px; }
        .mb-20 { margin-bottom: 20px; }
        
        @media (max-width: 768px) {
            .lab-container { padding: 15px; }
            .lab-header h1 { font-size: 1.4rem; }
            .nav-buttons { flex-direction: column; gap: 10px; }
            .nav-buttons .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="lab-container">
<?php
}

function renderLabFooter($backUrl = null) {
    $back = $backUrl ?: (defined('SITE_URL') ? SITE_URL . '/challenges.php' : '../../../challenges.php');
    $siteUrl = defined('SITE_URL') ? SITE_URL : '';
    $challengeId = $_SESSION['current_challenge_id'] ?? 0;
    $currentPage = $_GET['page'] ?? 'index';
?>
    </div>
    <script>
        // تحويل الروابط والنماذج تلقائياً للعمل مع challenge_play.php
        (function() {
            const siteUrl = <?php echo json_encode($siteUrl); ?>;
            const challengeId = <?php echo json_encode($challengeId); ?>;
            const currentPage = <?php echo json_encode($currentPage); ?>;
            
            if (!challengeId) return;
            
            // دالة مساعدة لبناء رابط challenge_play.php
            function buildLabUrl(pageName, extraParams = '') {
                let url = siteUrl + '/challenge_play.php?id=' + challengeId + '&page=' + encodeURIComponent(pageName);
                if (extraParams) {
                    url += '&' + extraParams;
                }
                return url;
            }
            
            // دالة لاستخراج اسم الصفحة من الرابط
            function extractPageName(href) {
                let pageName = href;
                let queryString = '';
                
                // إزالة أي مسارات نسبية
                pageName = pageName.replace(/^\.\//, '').replace(/^\.\.\/+/g, '');
                
                // فصل المعاملات
                if (pageName.includes('?')) {
                    const parts = pageName.split('?');
                    pageName = parts[0];
                    queryString = parts[1];
                }
                
                // إزالة .php
                if (pageName.endsWith('.php')) {
                    pageName = pageName.slice(0, -4);
                }
                
                return { pageName, queryString };
            }
            
            // دالة للتحقق إذا كان الرابط يجب تجاهله
            function shouldSkipLink(href) {
                if (!href) return true;
                if (href.startsWith('http://') || href.startsWith('https://') || href.startsWith('//')) return true;
                if (href.startsWith('#') || href.startsWith('javascript:')) return true;

                // لا تعدّل روابط/نماذج نظام الخطوات (step=...) حتى لا يتم كسر التنقل أو فقدان البيانات بعد POST
                if (href.includes('step=')) return true;

                if (href.includes('challenge_play.php')) return true;
                if (href.includes('challenges.php')) return true;
                if (href.includes('challenge_view.php')) return true;
                if (href.includes('login.php')) return true;
                if (href.includes('logout.php')) return true;
                return false;
            }
            
            // تحويل جميع الروابط النسبية
            document.querySelectorAll('a[href]').forEach(link => {
                const href = link.getAttribute('href');
                
                if (shouldSkipLink(href)) return;
                
                const { pageName, queryString } = extractPageName(href);
                if (!pageName) return;
                
                const newHref = buildLabUrl(pageName, queryString);
                link.setAttribute('href', newHref);
            });
            
            // تحويل جميع النماذج
            document.querySelectorAll('form').forEach(form => {
                const action = form.getAttribute('action');
                const method = (form.getAttribute('method') || 'get').toLowerCase();
                
                // إذا كان النموذج يرسل لصفحة خارجية، تجاهله
                if (action && shouldSkipLink(action)) return;
                
                // استخراج اسم الصفحة
                let targetPage = currentPage; // افتراضياً الصفحة الحالية
                let extraQueryString = '';
                
                if (action && action !== '' && action !== '#') {
                    const { pageName, queryString } = extractPageName(action);
                    if (pageName) {
                        targetPage = pageName;
                        extraQueryString = queryString;
                    }
                }
                
                if (method === 'get') {
                    // للـ GET، نحتاج لإضافة الحقول المخفية
                    const newAction = siteUrl + '/challenge_play.php';
                    form.setAttribute('action', newAction);
                    
                    // إضافة حقول مخفية لـ id و page
                    if (!form.querySelector('input[name="id"]')) {
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id';
                        idInput.value = challengeId;
                        form.prepend(idInput);
                    }
                    
                    if (!form.querySelector('input[name="page"]')) {
                        const pageInput = document.createElement('input');
                        pageInput.type = 'hidden';
                        pageInput.name = 'page';
                        pageInput.value = targetPage;
                        form.prepend(pageInput);
                    }
                } else {
                    // للـ POST، نستخدم الرابط مباشرة
                    const newAction = buildLabUrl(targetPage, extraQueryString);
                    form.setAttribute('action', newAction);
                }
            });
        })();
        
        // نسخ الفلاج
        document.querySelectorAll('.flag-display').forEach(el => {
            el.addEventListener('click', function() {
                navigator.clipboard.writeText(this.textContent.trim());
                const original = this.textContent;
                this.style.background = 'var(--primary)';
                this.style.color = '#000';
                this.textContent = '✓ تم النسخ';
                setTimeout(() => {
                    this.style.background = '#000';
                    this.style.color = 'var(--primary)';
                    this.textContent = original;
                }, 1000);
            });
        });
    </script>
</body>
</html>
<?php
}

function renderProgress($currentStep, $totalSteps, $completedSteps = []) {
    $percentage = ($currentStep / $totalSteps) * 100;
?>
    <div class="lab-progress" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 15px; padding: 20px; margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.9rem;">
            <span>التقدم</span>
            <span>الخطوة <?php echo $currentStep; ?> من <?php echo $totalSteps; ?></span>
        </div>
        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; width: <?php echo $percentage; ?>%; background: linear-gradient(90deg, var(--primary), var(--secondary)); transition: width 0.5s;"></div>
        </div>
    </div>
<?php
}

function renderVulnApp($url, $title, $content) {
?>
    <div class="vuln-app">
        <div class="app-bar">
            <span>🔒</span>
            <div class="app-url"><?php echo htmlspecialchars($url); ?></div>
        </div>
        <div class="app-body">
            <h3><?php echo htmlspecialchars($title); ?></h3>
            <?php echo $content; ?>
        </div>
    </div>
<?php
}

/**
 * عرض صندوق النجاح مع الفلاج الديناميكي
 * @param string $folderName اسم مجلد اللاب
 */
function renderSuccessBox($folderName, $message = 'أحسنت! أكملت هذا التحدي') {
    // جلب الفلاج الديناميكي
    $dynamicFlag = getDynamicFlag($folderName);
    
    if (!$dynamicFlag) {
        // fallback إذا لم يتم العثور على التحدي في قاعدة البيانات
        $dynamicFlag = 'التحدي غير مسجل - تواصل مع الأدمن';
    }
    
    // تسجيل إكمال اللاب
    markLabCompleted($folderName);
?>
    <div class="success-box">
        <h2>🎉 تم الحل</h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        <div class="flag-display"><?php echo htmlspecialchars($dynamicFlag); ?></div>
        <p class="text-muted">اضغط على الفلاج لنسخه، ثم اضغط "العودة للتحدي" لإرسال الفلاج</p>
        <?php if (isTokenEnabled()): ?>
        <div class="flag-warning">
            ⚠️ هذا الفلاج خاص بك فقط ولا يمكن استخدامه من حساب آخر
        </div>
        <?php endif; ?>
    </div>
    <div class="nav-buttons" style="margin-top: 20px;">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">🚩 العودة للتحدي لإرسال الفلاج</a>
    </div>
<?php
}

/**
 * عرض صندوق النجاح بفلاج ثابت (للتوافق مع الكود القديم)
 * @deprecated استخدم renderSuccessBox($folderName) بدلاً منها
 */
function renderSuccessBoxLegacy($flag, $message = 'أحسنت! أكملت هذا التحدي') {
?>
    <div class="success-box">
        <h2>🎉 تم الحل</h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        <div class="flag-display"><?php echo htmlspecialchars($flag); ?></div>
        <p class="text-muted">اضغط على الفلاج لنسخه</p>
    </div>
<?php
}
?>
