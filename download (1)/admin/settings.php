<?php
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$currentUser = getCurrentUser();

// معالجة الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $section = $_POST['section'] ?? 'general';
    
    if ($section === 'privacy') {
        // إعدادات الخصوصية والفلاج
        updateSetting('flag_token_enabled', isset($_POST['flag_token_enabled']) ? '1' : '0');
        updateSetting('hide_usernames_from_users', isset($_POST['hide_usernames_from_users']) ? '1' : '0');
        updateSetting('hide_usernames_scoreboard', isset($_POST['hide_usernames_scoreboard']) ? '1' : '0');
        updateSetting('hide_usernames_writeups', isset($_POST['hide_usernames_writeups']) ? '1' : '0');
        updateSetting('hide_usernames_solvers', isset($_POST['hide_usernames_solvers']) ? '1' : '0');
        flashMessage('success', __('privacy_settings_saved'));
    }
    elseif ($section === 'competition') {
        // إعدادات المسابقة
        updateSetting('competition_enabled', isset($_POST['competition_enabled']) ? '1' : '0');
        updateSetting('competition_start', sanitize($_POST['competition_start'] ?? ''));
        updateSetting('competition_end', sanitize($_POST['competition_end'] ?? ''));
        updateSetting('competition_name', sanitize($_POST['competition_name'] ?? 'AlwaniCTF'));
        updateSetting('competition_description', sanitize($_POST['competition_description'] ?? ''));
        flashMessage('success', __('competition_settings_saved'));
    }
    elseif ($section === 'scoreboard') {
        // تجميد لوحة النتائج
        $freeze_now = isset($_POST['freeze_now']);
        $unfreeze = isset($_POST['unfreeze']);
        
        if ($freeze_now) {
            updateSetting('scoreboard_frozen', '1');
            updateSetting('scoreboard_frozen_at', date('Y-m-d H:i:s'));
        } elseif ($unfreeze) {
            updateSetting('scoreboard_frozen', '0');
            updateSetting('scoreboard_frozen_at', '');
        }
        
        updateSetting('scoreboard_freeze_time', sanitize($_POST['scoreboard_freeze_time'] ?? ''));
        updateSetting('scoreboard_show_solves', isset($_POST['scoreboard_show_solves']) ? '1' : '0');
        updateSetting('scoreboard_show_teams', isset($_POST['scoreboard_show_teams']) ? '1' : '0');
        flashMessage('success', __('scoreboard_settings_saved'));
    }
    elseif ($section === 'general') {
        // إعدادات عامة
        updateSetting('registration_enabled', isset($_POST['registration_enabled']) ? '1' : '0');
        updateSetting('team_creation_enabled', isset($_POST['team_creation_enabled']) ? '1' : '0');
        updateSetting('writeups_enabled', isset($_POST['writeups_enabled']) ? '1' : '0');
        updateSetting('default_first_blood_bonus', sanitize($_POST['default_first_blood_bonus'] ?? '50'));
        updateSetting('max_team_members', intval($_POST['max_team_members'] ?? 5));
        updateSetting('require_email_verification', isset($_POST['require_email_verification']) ? '1' : '0');
        flashMessage('success', __('general_settings_saved'));
    }
    elseif ($section === 'challenges') {
        // إعدادات التحديات
        updateSetting('dynamic_scoring_enabled', isset($_POST['dynamic_scoring_enabled']) ? '1' : '0');
        updateSetting('dynamic_min_points', intval($_POST['dynamic_min_points'] ?? 100));
        updateSetting('dynamic_decay_factor', floatval($_POST['dynamic_decay_factor'] ?? 0.1));
        updateSetting('show_solve_count', isset($_POST['show_solve_count']) ? '1' : '0');
        updateSetting('show_first_blood', isset($_POST['show_first_blood']) ? '1' : '0');
        updateSetting('hint_penalty_enabled', isset($_POST['hint_penalty_enabled']) ? '1' : '0');
        updateSetting('hint_penalty_percent', intval($_POST['hint_penalty_percent'] ?? 10));
        flashMessage('success', __('challenge_settings_saved'));
    }
    elseif ($section === 'site') {
        // إعدادات الموقع
        updateSetting('site_name', sanitize($_POST['site_name'] ?? 'AlwaniCTF'));
        updateSetting('site_description', sanitize($_POST['site_description'] ?? ''));
        updateSetting('site_keywords', sanitize($_POST['site_keywords'] ?? ''));
        updateSetting('site_footer_text', sanitize($_POST['site_footer_text'] ?? ''));
        updateSetting('site_contact_email', sanitize($_POST['site_contact_email'] ?? ''));
        updateSetting('site_social_twitter', sanitize($_POST['site_social_twitter'] ?? ''));
        updateSetting('site_social_discord', sanitize($_POST['site_social_discord'] ?? ''));
        updateSetting('site_social_github', sanitize($_POST['site_social_github'] ?? ''));
        flashMessage('success', __('site_settings_saved'));
    }
    elseif ($section === 'notifications') {
        // إعدادات الإشعارات
        updateSetting('notify_new_user', isset($_POST['notify_new_user']) ? '1' : '0');
        updateSetting('notify_first_blood', isset($_POST['notify_first_blood']) ? '1' : '0');
        updateSetting('notify_new_challenge', isset($_POST['notify_new_challenge']) ? '1' : '0');
        updateSetting('email_notifications_enabled', isset($_POST['email_notifications_enabled']) ? '1' : '0');
        flashMessage('success', __('notification_settings_saved'));
    }
    elseif ($section === 'appearance') {
        // إعدادات المظهر
        updateSetting('default_theme', sanitize($_POST['default_theme'] ?? 'dark'));
        updateSetting('default_language', sanitize($_POST['default_language'] ?? 'ar'));
        updateSetting('show_hero_section', isset($_POST['show_hero_section']) ? '1' : '0');
        updateSetting('show_stats_section', isset($_POST['show_stats_section']) ? '1' : '0');
        updateSetting('primary_color', sanitize($_POST['primary_color'] ?? '#00ff88'));
        flashMessage('success', __('appearance_settings_saved'));
    }
    
    header('Location: settings.php');
    exit;
}

// جلب الإعدادات الحالية
$settings = [
    // Privacy & Flag Settings
    'flag_token_enabled' => getSetting('flag_token_enabled', '1'),
    'hide_usernames_from_users' => getSetting('hide_usernames_from_users', '0'),
    'hide_usernames_scoreboard' => getSetting('hide_usernames_scoreboard', '0'),
    'hide_usernames_writeups' => getSetting('hide_usernames_writeups', '0'),
    'hide_usernames_solvers' => getSetting('hide_usernames_solvers', '0'),
    
    // Competition
    'competition_enabled' => getSetting('competition_enabled', '0'),
    'competition_start' => getSetting('competition_start', ''),
    'competition_end' => getSetting('competition_end', ''),
    'competition_name' => getSetting('competition_name', 'AlwaniCTF'),
    'competition_description' => getSetting('competition_description', ''),
    
    // Scoreboard
    'scoreboard_frozen' => getSetting('scoreboard_frozen', '0'),
    'scoreboard_frozen_at' => getSetting('scoreboard_frozen_at', ''),
    'scoreboard_freeze_time' => getSetting('scoreboard_freeze_time', ''),
    'scoreboard_show_solves' => getSetting('scoreboard_show_solves', '1'),
    'scoreboard_show_teams' => getSetting('scoreboard_show_teams', '1'),
    
    // General
    'registration_enabled' => getSetting('registration_enabled', '1'),
    'team_creation_enabled' => getSetting('team_creation_enabled', '1'),
    'writeups_enabled' => getSetting('writeups_enabled', '1'),
    'default_first_blood_bonus' => getSetting('default_first_blood_bonus', '50'),
    'max_team_members' => getSetting('max_team_members', '5'),
    'require_email_verification' => getSetting('require_email_verification', '0'),
    
    // Challenges
    'dynamic_scoring_enabled' => getSetting('dynamic_scoring_enabled', '0'),
    'dynamic_min_points' => getSetting('dynamic_min_points', '100'),
    'dynamic_decay_factor' => getSetting('dynamic_decay_factor', '0.1'),
    'show_solve_count' => getSetting('show_solve_count', '1'),
    'show_first_blood' => getSetting('show_first_blood', '1'),
    'hint_penalty_enabled' => getSetting('hint_penalty_enabled', '0'),
    'hint_penalty_percent' => getSetting('hint_penalty_percent', '10'),
    
    // Site
    'site_name' => getSetting('site_name', 'AlwaniCTF'),
    'site_description' => getSetting('site_description', ''),
    'site_keywords' => getSetting('site_keywords', ''),
    'site_footer_text' => getSetting('site_footer_text', ''),
    'site_contact_email' => getSetting('site_contact_email', ''),
    'site_social_twitter' => getSetting('site_social_twitter', ''),
    'site_social_discord' => getSetting('site_social_discord', ''),
    'site_social_github' => getSetting('site_social_github', ''),
    
    // Notifications
    'notify_new_user' => getSetting('notify_new_user', '0'),
    'notify_first_blood' => getSetting('notify_first_blood', '1'),
    'notify_new_challenge' => getSetting('notify_new_challenge', '1'),
    'email_notifications_enabled' => getSetting('email_notifications_enabled', '0'),
    
    // Appearance
    'default_theme' => getSetting('default_theme', 'dark'),
    'default_language' => getSetting('default_language', 'ar'),
    'show_hero_section' => getSetting('show_hero_section', '1'),
    'show_stats_section' => getSetting('show_stats_section', '1'),
    'primary_color' => getSetting('primary_color', '#00ff88'),
];

$pageTitle = __('settings');
$isRTL = ($lang === 'ar');
$currentTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>" data-theme="<?php echo $currentTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
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
                <a href="settings.php" class="nav-item active">⚙️ <?php echo __('settings'); ?></a>
                <a href="security_settings.php" class="nav-item">🔐 <?php echo __('security_settings'); ?></a>
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
                <h1>⚙️ <?php echo __('settings'); ?></h1>
            </div>
            
            <div class="settings-tabs" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px;">
                <button type="button" class="btn btn-neon tab-btn active" data-tab="site">🌐 <?php echo __('site_settings'); ?></button>
                <button type="button" class="btn btn-outline tab-btn" data-tab="privacy">🔒 <?php echo __('privacy_settings'); ?></button>
                <button type="button" class="btn btn-outline tab-btn" data-tab="competition">⏱️ <?php echo __('competition_settings'); ?></button>
                <button type="button" class="btn btn-outline tab-btn" data-tab="scoreboard">📊 <?php echo __('scoreboard_settings'); ?></button>
                <button type="button" class="btn btn-outline tab-btn" data-tab="general">⚙️ <?php echo __('general_settings'); ?></button>
                <button type="button" class="btn btn-outline tab-btn" data-tab="challenges">🚩 <?php echo __('challenge_settings'); ?></button>
                <button type="button" class="btn btn-outline tab-btn" data-tab="notifications">🔔 <?php echo __('notification_settings'); ?></button>
                <button type="button" class="btn btn-outline tab-btn" data-tab="appearance">🎨 <?php echo __('appearance_settings'); ?></button>
            </div>
            
            <div class="settings-panel active" id="panel-site">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="section" value="site">
                    
                    <div class="card settings-card">
                        <div class="card-header">
                            <h3>🌐 <?php echo __('site_settings'); ?></h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('site_name'); ?></label>
                            <input type="text" name="site_name" class="form-input" value="<?php echo sanitize($settings['site_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('site_description'); ?></label>
                            <textarea name="site_description" class="form-input form-textarea" rows="3" placeholder="<?php echo __('site_description_hint'); ?>"><?php echo sanitize($settings['site_description']); ?></textarea>
                            <small class="form-hint"><?php echo __('seo_description_hint'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('site_keywords'); ?></label>
                            <input type="text" name="site_keywords" class="form-input" value="<?php echo sanitize($settings['site_keywords']); ?>" placeholder="CTF, Cybersecurity, Hacking">
                            <small class="form-hint"><?php echo __('seo_keywords_hint'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('contact_email'); ?></label>
                            <input type="email" name="site_contact_email" class="form-input" value="<?php echo sanitize($settings['site_contact_email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('footer_text'); ?></label>
                            <input type="text" name="site_footer_text" class="form-input" value="<?php echo sanitize($settings['site_footer_text']); ?>">
                        </div>
                        
                        <h4 style="margin: 20px 0 15px; color: var(--neon-cyan);">📱 <?php echo __('social_links'); ?></h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Twitter/X</label>
                                <input type="url" name="site_social_twitter" class="form-input" value="<?php echo sanitize($settings['site_social_twitter']); ?>" placeholder="https://twitter.com/yourpage">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Discord</label>
                                <input type="url" name="site_social_discord" class="form-input" value="<?php echo sanitize($settings['site_social_discord']); ?>" placeholder="https://discord.gg/invite">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">GitHub</label>
                            <input type="url" name="site_social_github" class="form-input" value="<?php echo sanitize($settings['site_social_github']); ?>" placeholder="https://github.com/yourorg">
                        </div>
                        
                        <button type="submit" class="btn btn-neon btn-block"><?php echo __('save_settings'); ?></button>
                    </div>
                </form>
            </div>
            
            <!-- Privacy & Flag Settings -->
            <div class="settings-panel" id="panel-privacy" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="section" value="privacy">
                    
                    <div class="card settings-card">
                        <div class="card-header">
                            <h3>🔒 <?php echo __('privacy_settings'); ?></h3>
                        </div>
                        
                        <!-- إعدادات الفلاج -->
                        <h4 style="margin: 20px 0 15px; color: var(--neon-cyan);">🚩 <?php echo __('flag_settings'); ?></h4>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="flag_token_enabled" <?php echo $settings['flag_token_enabled'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('enable_flag_token'); ?>
                            </label>
                            <small class="form-hint"><?php echo __('flag_token_hint'); ?></small>
                        </div>
                        
                        <!-- إعدادات إخفاء الأسماء -->
                        <h4 style="margin: 20px 0 15px; color: var(--neon-purple);">👁️ <?php echo __('username_visibility'); ?></h4>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="hide_usernames_from_users" <?php echo $settings['hide_usernames_from_users'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('hide_usernames_from_users'); ?>
                            </label>
                            <small class="form-hint"><?php echo __('hide_usernames_hint'); ?></small>
                        </div>
                        
                        <div class="form-group" style="padding-<?php echo $isRTL ? 'right' : 'left'; ?>: 30px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="hide_usernames_scoreboard" <?php echo $settings['hide_usernames_scoreboard'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('hide_in_scoreboard'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group" style="padding-<?php echo $isRTL ? 'right' : 'left'; ?>: 30px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="hide_usernames_writeups" <?php echo $settings['hide_usernames_writeups'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('hide_in_writeups'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group" style="padding-<?php echo $isRTL ? 'right' : 'left'; ?>: 30px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="hide_usernames_solvers" <?php echo $settings['hide_usernames_solvers'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('hide_in_solvers'); ?>
                            </label>
                        </div>
                        
                        <div class="alert alert-info" style="margin-top: 15px;">
                            💡 <?php echo __('admin_can_see_all'); ?>
                        </div>
                        
                        <button type="submit" class="btn btn-neon btn-block"><?php echo __('save_settings'); ?></button>
                    </div>
                </form>
            </div>
            
            <!-- Competition Settings -->
            <div class="settings-panel" id="panel-competition" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="section" value="competition">
                    
                    <div class="card settings-card">
                        <div class="card-header">
                            <h3>⏱️ <?php echo __('competition_settings'); ?></h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="competition_enabled" <?php echo $settings['competition_enabled'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('enable_competition_mode'); ?>
                            </label>
                            <small class="form-hint"><?php echo __('competition_mode_hint'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('competition_name'); ?></label>
                            <input type="text" name="competition_name" class="form-input" value="<?php echo sanitize($settings['competition_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('competition_description'); ?></label>
                            <textarea name="competition_description" class="form-input form-textarea" rows="3"><?php echo sanitize($settings['competition_description']); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php echo __('competition_start'); ?></label>
                                <input type="datetime-local" name="competition_start" class="form-input" value="<?php echo $settings['competition_start'] ? date('Y-m-d\TH:i', strtotime($settings['competition_start'])) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo __('competition_end'); ?></label>
                                <input type="datetime-local" name="competition_end" class="form-input" value="<?php echo $settings['competition_end'] ? date('Y-m-d\TH:i', strtotime($settings['competition_end'])) : ''; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-neon btn-block"><?php echo __('save_settings'); ?></button>
                    </div>
                </form>
            </div>
            
            <!-- Scoreboard Settings -->
            <div class="settings-panel" id="panel-scoreboard" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="section" value="scoreboard">
                    
                    <div class="card settings-card">
                        <div class="card-header">
                            <h3>❄️ <?php echo __('freeze_scoreboard'); ?></h3>
                        </div>
                        
                        <?php if ($settings['scoreboard_frozen'] === '1'): ?>
                            <div class="alert alert-warning">
                                ❄️ <?php echo __('scoreboard_is_frozen'); ?>
                                <?php if ($settings['scoreboard_frozen_at']): ?>
                                    (<?php echo $settings['scoreboard_frozen_at']; ?>)
                                <?php endif; ?>
                            </div>
                            <button type="submit" name="unfreeze" class="btn btn-success" style="margin-bottom: 20px;">
                                🔓 <?php echo __('unfreeze_scoreboard'); ?>
                            </button>
                        <?php else: ?>
                            <button type="submit" name="freeze_now" class="btn btn-warning" style="margin-bottom: 20px;">
                                ❄️ <?php echo __('freeze_now'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('auto_freeze_time'); ?></label>
                            <input type="datetime-local" name="scoreboard_freeze_time" class="form-input" value="<?php echo $settings['scoreboard_freeze_time'] ? date('Y-m-d\TH:i', strtotime($settings['scoreboard_freeze_time'])) : ''; ?>">
                            <small class="form-hint"><?php echo __('auto_freeze_hint'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="scoreboard_show_solves" <?php echo $settings['scoreboard_show_solves'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('show_solve_count_scoreboard'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="scoreboard_show_teams" <?php echo $settings['scoreboard_show_teams'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('show_teams_scoreboard'); ?>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-neon btn-block"><?php echo __('save_settings'); ?></button>
                    </div>
                </form>
            </div>
            
            <!-- General Settings -->
            <div class="settings-panel" id="panel-general" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="section" value="general">
                    
                    <div class="card settings-card">
                        <div class="card-header">
                            <h3>⚙️ <?php echo __('general_settings'); ?></h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="registration_enabled" <?php echo $settings['registration_enabled'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('enable_registration'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="require_email_verification" <?php echo $settings['require_email_verification'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('require_email_verification'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="team_creation_enabled" <?php echo $settings['team_creation_enabled'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('enable_team_creation'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="writeups_enabled" <?php echo $settings['writeups_enabled'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('enable_writeups'); ?>
                            </label>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php echo __('max_team_members'); ?></label>
                                <input type="number" name="max_team_members" class="form-input" value="<?php echo $settings['max_team_members']; ?>" min="2" max="20">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo __('default_first_blood_bonus'); ?></label>
                                <input type="number" name="default_first_blood_bonus" class="form-input" value="<?php echo $settings['default_first_blood_bonus']; ?>" min="0">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-neon btn-block"><?php echo __('save_settings'); ?></button>
                    </div>
                </form>
            </div>
            
            <!-- Challenge Settings -->
            <div class="settings-panel" id="panel-challenges" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="section" value="challenges">
                    
                    <div class="card settings-card">
                        <div class="card-header">
                            <h3>🚩 <?php echo __('challenge_settings'); ?></h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="dynamic_scoring_enabled" <?php echo $settings['dynamic_scoring_enabled'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('enable_dynamic_scoring'); ?>
                            </label>
                            <small class="form-hint"><?php echo __('dynamic_scoring_hint'); ?></small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php echo __('dynamic_min_points'); ?></label>
                                <input type="number" name="dynamic_min_points" class="form-input" value="<?php echo $settings['dynamic_min_points']; ?>" min="10">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo __('dynamic_decay_factor'); ?></label>
                                <input type="number" name="dynamic_decay_factor" class="form-input" value="<?php echo $settings['dynamic_decay_factor']; ?>" min="0" max="1" step="0.01">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_solve_count" <?php echo $settings['show_solve_count'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('show_solve_count'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_first_blood" <?php echo $settings['show_first_blood'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('show_first_blood'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="hint_penalty_enabled" <?php echo $settings['hint_penalty_enabled'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('enable_hint_penalty'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('hint_penalty_percent'); ?></label>
                            <input type="number" name="hint_penalty_percent" class="form-input" value="<?php echo $settings['hint_penalty_percent']; ?>" min="0" max="100">
                        </div>
                        
                        <button type="submit" class="btn btn-neon btn-block"><?php echo __('save_settings'); ?></button>
                    </div>
                </form>
            </div>
            
            <!-- Notification Settings -->
            <div class="settings-panel" id="panel-notifications" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="section" value="notifications">
                    
                    <div class="card settings-card">
                        <div class="card-header">
                            <h3>🔔 <?php echo __('notification_settings'); ?></h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="notify_new_user" <?php echo $settings['notify_new_user'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('notify_new_user'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="notify_first_blood" <?php echo $settings['notify_first_blood'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('notify_first_blood'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="notify_new_challenge" <?php echo $settings['notify_new_challenge'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('notify_new_challenge'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="email_notifications_enabled" <?php echo $settings['email_notifications_enabled'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('enable_email_notifications'); ?>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-neon btn-block"><?php echo __('save_settings'); ?></button>
                    </div>
                </form>
            </div>
            
            <!-- Appearance Settings -->
            <div class="settings-panel" id="panel-appearance" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="section" value="appearance">
                    
                    <div class="card settings-card">
                        <div class="card-header">
                            <h3>🎨 <?php echo __('appearance_settings'); ?></h3>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php echo __('default_theme'); ?></label>
                                <select name="default_theme" class="form-input">
                                    <option value="dark" <?php echo $settings['default_theme'] === 'dark' ? 'selected' : ''; ?>>🌙 <?php echo __('dark_theme'); ?></option>
                                    <option value="light" <?php echo $settings['default_theme'] === 'light' ? 'selected' : ''; ?>>☀️ <?php echo __('light_theme'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo __('default_language'); ?></label>
                                <select name="default_language" class="form-input">
                                    <option value="ar" <?php echo $settings['default_language'] === 'ar' ? 'selected' : ''; ?>>🇸🇦 العربية</option>
                                    <option value="en" <?php echo $settings['default_language'] === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('primary_color'); ?></label>
                            <input type="color" name="primary_color" class="form-input" value="<?php echo $settings['primary_color']; ?>" style="height: 50px; cursor: pointer;">
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_hero_section" <?php echo $settings['show_hero_section'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('show_hero_section'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_stats_section" <?php echo $settings['show_stats_section'] === '1' ? 'checked' : ''; ?>>
                                <?php echo __('show_stats_section'); ?>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-neon btn-block"><?php echo __('save_settings'); ?></button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tab = this.dataset.tab;
                
                // Update button states
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('btn-neon', 'active');
                    b.classList.add('btn-outline');
                });
                this.classList.remove('btn-outline');
                this.classList.add('btn-neon', 'active');
                
                // Show corresponding panel
                document.querySelectorAll('.settings-panel').forEach(p => {
                    p.style.display = 'none';
                });
                document.getElementById('panel-' + tab).style.display = 'block';
            });
        });
    </script>
</body>
</html>
