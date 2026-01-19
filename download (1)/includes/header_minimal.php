<!DOCTYPE html>
<?php 
$currentLang = getCurrentLanguage();
$isRTL = ($currentLang === 'ar');
$currentTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>" data-theme="<?php echo $currentTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AlwaniCTF - Capture The Flag Platform | منصة مسابقات الأمن السيبراني">
    <meta name="keywords" content="CTF, Capture The Flag, Cybersecurity, Hacking, Security Challenges">
    <meta name="author" content="AlwaniCTF">
    <meta name="theme-color" content="#00ff88">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Preload fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Base URL for relative paths -->
    <?php
    $baseHref = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($baseHref === '' || $baseHref === '.' || $baseHref === '/') {
        $baseHref = '';
    }
    $baseHref = $baseHref . '/';
    ?>
    <base href="<?php echo $baseHref; ?>">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/main.css?v=<?php echo time(); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🇾🇪</text></svg>">
    
    <script>
        // تحميل الوضع المحفوظ مباشرة لمنع الوميض
        (function() {
            var theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="page-enter auth-page">
    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; max-width: 90%;">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <main class="main-content" style="padding-top: 0;">
