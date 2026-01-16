<?php
/**
 * إعدادات الأمان المتقدمة
 * Advanced Security Settings for Admin
 * Only for Super Admin
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$currentUser = getCurrentUser();

// التحقق من صلاحية السوبر أدمن
$isSuperAdmin = ($currentUser['role'] === 'super_admin' || $currentUser['id'] == 1);

// معالجة الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (!$isSuperAdmin) {
        flashMessage('error', __('super_admin_required'));
        header('Location: security_settings.php');
        exit;
    }
    
    $section = $_POST['section'] ?? '';
    
    if ($section === 'otp') {
        // إعدادات OTP
        updateSetting('otp_method', sanitize($_POST['otp_method'] ?? 'smtp'));
        updateSetting('email_verification_required', isset($_POST['email_verification_required']) ? '1' : '0');
        updateSetting('new_device_otp_enabled', isset($_POST['new_device_otp_enabled']) ? '1' : '0');
        updateSetting('force_logout_unverified', isset($_POST['force_logout_unverified']) ? '1' : '0');
        updateSetting('otp_expiry_minutes', intval($_POST['otp_expiry_minutes'] ?? 10));
        updateSetting('otp_length', intval($_POST['otp_length'] ?? 6));
        
        flashMessage('success', __('otp_settings_saved'));
    }
    elseif ($section === 'smtp') {
        // إعدادات SMTP
        updateSetting('smtp_host', sanitize($_POST['smtp_host'] ?? ''));
        updateSetting('smtp_port', intval($_POST['smtp_port'] ?? 587));
        updateSetting('smtp_username', sanitize($_POST['smtp_username'] ?? ''));
        if (!empty($_POST['smtp_password'])) {
            updateSetting('smtp_password', $_POST['smtp_password']);
        }
        updateSetting('smtp_from_email', sanitize($_POST['smtp_from_email'] ?? ''));
        updateSetting('smtp_from_name', sanitize($_POST['smtp_from_name'] ?? 'AlwaniCTF'));
        updateSetting('smtp_encryption', sanitize($_POST['smtp_encryption'] ?? 'tls'));
        
        flashMessage('success', __('smtp_settings_saved'));
    }
    elseif ($section === 'firebase') {
        // إعدادات Firebase
        updateSetting('firebase_enabled', isset($_POST['firebase_enabled']) ? '1' : '0');
        updateSetting('firebase_api_key', sanitize($_POST['firebase_api_key'] ?? ''));
        updateSetting('firebase_auth_domain', sanitize($_POST['firebase_auth_domain'] ?? ''));
        updateSetting('firebase_project_id', sanitize($_POST['firebase_project_id'] ?? ''));
        
        flashMessage('success', __('firebase_settings_saved'));
    }
    elseif ($section === 'security') {
        // إعدادات الأمان العامة
        updateSetting('max_login_attempts', intval($_POST['max_login_attempts'] ?? 5));
        updateSetting('login_lockout_time', intval($_POST['login_lockout_time'] ?? 900));
        updateSetting('session_lifetime', intval($_POST['session_lifetime'] ?? 7200));
        updateSetting('password_min_length', intval($_POST['password_min_length'] ?? 6));
        updateSetting('require_strong_password', isset($_POST['require_strong_password']) ? '1' : '0');
        updateSetting('enable_2fa', isset($_POST['enable_2fa']) ? '1' : '0');
        updateSetting('log_all_activities', isset($_POST['log_all_activities']) ? '1' : '0');
        
        flashMessage('success', __('security_settings_saved'));
    }
    elseif ($section === 'rate_limit') {
        // إعدادات Rate Limiting
        updateSetting('rate_limit_enabled', isset($_POST['rate_limit_enabled']) ? '1' : '0');
        updateSetting('rate_limit_requests', intval($_POST['rate_limit_requests'] ?? 60));
        updateSetting('rate_limit_period', intval($_POST['rate_limit_period'] ?? 60));
        
        flashMessage('success', __('rate_limit_settings_saved'));
    }
    
    header('Location: security_settings.php');
    exit;
}

// جلب الإعدادات الحالية
$settings = [
    // OTP Settings
    'otp_method' => getSetting('otp_method', 'smtp'),
    'email_verification_required' => getSetting('email_verification_required', '0'),
    'new_device_otp_enabled' => getSetting('new_device_otp_enabled', '0'),
    'force_logout_unverified' => getSetting('force_logout_unverified', '0'),
    'otp_expiry_minutes' => getSetting('otp_expiry_minutes', '10'),
    'otp_length' => getSetting('otp_length', '6'),
    
    // SMTP Settings
    'smtp_host' => getSetting('smtp_host', ''),
    'smtp_port' => getSetting('smtp_port', '587'),
    'smtp_username' => getSetting('smtp_username', ''),
    'smtp_from_email' => getSetting('smtp_from_email', ''),
    'smtp_from_name' => getSetting('smtp_from_name', 'AlwaniCTF'),
    'smtp_encryption' => getSetting('smtp_encryption', 'tls'),
    
    // Firebase Settings
    'firebase_enabled' => getSetting('firebase_enabled', '0'),
    'firebase_api_key' => getSetting('firebase_api_key', ''),
    'firebase_auth_domain' => getSetting('firebase_auth_domain', ''),
    'firebase_project_id' => getSetting('firebase_project_id', ''),
    
    // Security Settings
    'max_login_attempts' => getSetting('max_login_attempts', '5'),
    'login_lockout_time' => getSetting('login_lockout_time', '900'),
    'session_lifetime' => getSetting('session_lifetime', '7200'),
    'password_min_length' => getSetting('password_min_length', '6'),
    'require_strong_password' => getSetting('require_strong_password', '0'),
    'enable_2fa' => getSetting('enable_2fa', '0'),
    'log_all_activities' => getSetting('log_all_activities', '1'),
    
    // Rate Limiting
    'rate_limit_enabled' => getSetting('rate_limit_enabled', '1'),
    'rate_limit_requests' => getSetting('rate_limit_requests', '60'),
    'rate_limit_period' => getSetting('rate_limit_period', '60'),
];

$pageTitle = __('security_settings');
$isRTL = ($lang === 'ar');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="logo">
                    <span class="logo-icon">🛡️</span>
                    <span class="logo-text">AlwaniCTF</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">📊 <?php echo __('dashboard'); ?></a>
                <a href="challenges.php" class="nav-item">🚩 <?php echo __('challenges'); ?></a>
                <a href="categories.php" class="nav-item">📁 <?php echo __('manage_categories'); ?></a>
                <a href="users.php" class="nav-item">👥 <?php echo __('users'); ?></a>
                <a href="teams.php" class="nav-item">🏴 <?php echo __('teams'); ?></a>
                <a href="notifications.php" class="nav-item">🔔 <?php echo __('notifications'); ?></a>
                <a href="settings.php" class="nav-item">⚙️ <?php echo __('settings'); ?></a>
                <a href="security_settings.php" class="nav-item active">🔐 <?php echo __('security_settings'); ?></a>
                <a href="roles.php" class="nav-item">👑 <?php echo __('roles_management'); ?></a>
                <hr class="nav-divider">
                <a href="../index.php" class="nav-item">🏠 <?php echo __('home'); ?></a>
                <a href="../logout.php" class="nav-item logout">🚪 <?php echo __('logout'); ?></a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="lang-switch">
                    <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
                    <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
                </div>
            </div>
        </aside>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>🔐 <?php echo __('security_settings'); ?></h1>
                <?php if (!$isSuperAdmin): ?>
                    <div class="alert alert-warning">
                        ⚠️ <?php echo __('view_only_mode'); ?> - <?php echo __('super_admin_required'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="settings-grid">
                <!-- إعدادات OTP -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h3>📱 <?php echo __('otp_settings'); ?></h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="section" value="otp">
                        
                        <div class="form-group">
                            <label><?php echo __('otp_method'); ?></label>
                            <select name="otp_method" class="form-select" <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <option value="smtp" <?php echo $settings['otp_method'] === 'smtp' ? 'selected' : ''; ?>>
                                    📧 SMTP (<?php echo __('email'); ?>)
                                </option>
                                <option value="firebase" <?php echo $settings['otp_method'] === 'firebase' ? 'selected' : ''; ?>>
                                    🔥 Firebase
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="email_verification_required" 
                                       <?php echo $settings['email_verification_required'] === '1' ? 'checked' : ''; ?>
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <?php echo __('require_email_verification'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="new_device_otp_enabled" 
                                       <?php echo $settings['new_device_otp_enabled'] === '1' ? 'checked' : ''; ?>
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <?php echo __('enable_new_device_otp'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label warning-label">
                                <input type="checkbox" name="force_logout_unverified" 
                                       <?php echo $settings['force_logout_unverified'] === '1' ? 'checked' : ''; ?>
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <?php echo __('force_logout_unverified'); ?>
                            </label>
                            <small class="form-hint warning-hint">⚠️ <?php echo __('force_logout_hint'); ?></small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo __('otp_expiry_minutes'); ?></label>
                                <input type="number" name="otp_expiry_minutes" 
                                       value="<?php echo $settings['otp_expiry_minutes']; ?>" 
                                       min="1" max="60"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label><?php echo __('otp_length'); ?></label>
                                <input type="number" name="otp_length" 
                                       value="<?php echo $settings['otp_length']; ?>" 
                                       min="4" max="8"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        
                        <?php if ($isSuperAdmin): ?>
                        <button type="submit" class="btn btn-neon"><?php echo __('save'); ?></button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- إعدادات SMTP -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h3>📧 <?php echo __('smtp_settings'); ?></h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="section" value="smtp">
                        
                        <div class="form-group">
                            <label><?php echo __('smtp_host'); ?></label>
                            <input type="text" name="smtp_host" 
                                   value="<?php echo sanitize($settings['smtp_host']); ?>"
                                   placeholder="smtp.gmail.com"
                                   <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo __('smtp_port'); ?></label>
                                <input type="number" name="smtp_port" 
                                       value="<?php echo $settings['smtp_port']; ?>"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label><?php echo __('smtp_encryption'); ?></label>
                                <select name="smtp_encryption" class="form-select" <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                    <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo $settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo __('smtp_username'); ?></label>
                            <input type="text" name="smtp_username" 
                                   value="<?php echo sanitize($settings['smtp_username']); ?>"
                                   <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo __('smtp_password'); ?></label>
                            <input type="password" name="smtp_password" 
                                   placeholder="<?php echo __('leave_empty_to_keep'); ?>"
                                   <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo __('smtp_from_email'); ?></label>
                                <input type="email" name="smtp_from_email" 
                                       value="<?php echo sanitize($settings['smtp_from_email']); ?>"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label><?php echo __('smtp_from_name'); ?></label>
                                <input type="text" name="smtp_from_name" 
                                       value="<?php echo sanitize($settings['smtp_from_name']); ?>"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        
                        <?php if ($isSuperAdmin): ?>
                        <button type="submit" class="btn btn-neon"><?php echo __('save'); ?></button>
                        <button type="button" class="btn btn-outline" onclick="testSMTP()">
                            🧪 <?php echo __('test_smtp'); ?>
                        </button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- إعدادات Firebase -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h3>🔥 <?php echo __('firebase_settings'); ?></h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="section" value="firebase">
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="firebase_enabled" 
                                       <?php echo $settings['firebase_enabled'] === '1' ? 'checked' : ''; ?>
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <?php echo __('enable_firebase'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo __('firebase_api_key'); ?></label>
                            <input type="text" name="firebase_api_key" 
                                   value="<?php echo sanitize($settings['firebase_api_key']); ?>"
                                   placeholder="AIzaSy..."
                                   <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo __('firebase_auth_domain'); ?></label>
                            <input type="text" name="firebase_auth_domain" 
                                   value="<?php echo sanitize($settings['firebase_auth_domain']); ?>"
                                   placeholder="your-app.firebaseapp.com"
                                   <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo __('firebase_project_id'); ?></label>
                            <input type="text" name="firebase_project_id" 
                                   value="<?php echo sanitize($settings['firebase_project_id']); ?>"
                                   placeholder="your-project-id"
                                   <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                        </div>
                        
                        <div class="info-box">
                            <span class="info-icon">ℹ️</span>
                            <span><?php echo __('firebase_setup_hint'); ?></span>
                        </div>
                        
                        <?php if ($isSuperAdmin): ?>
                        <button type="submit" class="btn btn-neon"><?php echo __('save'); ?></button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- إعدادات الأمان العامة -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h3>🛡️ <?php echo __('general_security'); ?></h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="section" value="security">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo __('max_login_attempts'); ?></label>
                                <input type="number" name="max_login_attempts" 
                                       value="<?php echo $settings['max_login_attempts']; ?>" 
                                       min="1" max="20"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label><?php echo __('lockout_time_seconds'); ?></label>
                                <input type="number" name="login_lockout_time" 
                                       value="<?php echo $settings['login_lockout_time']; ?>" 
                                       min="60" max="86400"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo __('session_lifetime_seconds'); ?></label>
                                <input type="number" name="session_lifetime" 
                                       value="<?php echo $settings['session_lifetime']; ?>" 
                                       min="300" max="86400"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label><?php echo __('password_min_length'); ?></label>
                                <input type="number" name="password_min_length" 
                                       value="<?php echo $settings['password_min_length']; ?>" 
                                       min="4" max="20"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="require_strong_password" 
                                       <?php echo $settings['require_strong_password'] === '1' ? 'checked' : ''; ?>
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <?php echo __('require_strong_password'); ?>
                            </label>
                            <small class="form-hint"><?php echo __('strong_password_hint'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="enable_2fa" 
                                       <?php echo $settings['enable_2fa'] === '1' ? 'checked' : ''; ?>
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <?php echo __('enable_2fa'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="log_all_activities" 
                                       <?php echo $settings['log_all_activities'] === '1' ? 'checked' : ''; ?>
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <?php echo __('log_all_activities'); ?>
                            </label>
                        </div>
                        
                        <?php if ($isSuperAdmin): ?>
                        <button type="submit" class="btn btn-neon"><?php echo __('save'); ?></button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Rate Limiting -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h3>⚡ <?php echo __('rate_limiting'); ?></h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="section" value="rate_limit">
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="rate_limit_enabled" 
                                       <?php echo $settings['rate_limit_enabled'] === '1' ? 'checked' : ''; ?>
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                                <?php echo __('enable_rate_limiting'); ?>
                            </label>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo __('max_requests'); ?></label>
                                <input type="number" name="rate_limit_requests" 
                                       value="<?php echo $settings['rate_limit_requests']; ?>" 
                                       min="10" max="1000"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label><?php echo __('time_period_seconds'); ?></label>
                                <input type="number" name="rate_limit_period" 
                                       value="<?php echo $settings['rate_limit_period']; ?>" 
                                       min="10" max="3600"
                                       <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        
                        <?php if ($isSuperAdmin): ?>
                        <button type="submit" class="btn btn-neon"><?php echo __('save'); ?></button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
    function testSMTP() {
        alert('<?php echo __("test_smtp_message"); ?>');
    }
    </script>
    
    <style>
    .warning-label {
        color: var(--neon-orange) !important;
    }
    .warning-hint {
        color: var(--neon-orange) !important;
        display: block;
        margin-top: 5px;
        font-size: 0.85rem;
    }
    .form-hint {
        display: block;
        margin-top: 5px;
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
    .info-box {
        background: rgba(0, 229, 255, 0.1);
        border: 1px solid rgba(0, 229, 255, 0.3);
        border-radius: 8px;
        padding: 12px 15px;
        margin: 15px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
        color: var(--neon-cyan);
    }
    .info-icon {
        font-size: 1.1rem;
    }
    </style>
</body>
</html>
