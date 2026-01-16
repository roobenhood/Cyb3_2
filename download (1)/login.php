<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die(__('invalid_csrf_token'));
    }

    $identifier = sanitize($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($identifier) || empty($password)) {
        $error = __('fill_all_fields');
    } else {
        if (function_exists('checkLoginAttempts') && checkLoginAttempts($identifier)) {
            $remaining = function_exists('getRemainingLockoutTime') ? getRemainingLockoutTime($identifier) : 0;
            $error = __('too_many_attempts') . ' (' . ceil($remaining / 60) . ' ' . __('minutes') . ')';
        } else {
            $user = findUserByIdentifier($identifier);
            
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                if (function_exists('clearLoginAttempts')) {
                    clearLoginAttempts($identifier);
                }

                $emailVerificationRequired = getSetting('email_verification_required', '1') === '1';
                
                if ($emailVerificationRequired && !$user['email_verified']) {
                    $_SESSION['pending_verification_user_id'] = $user['id'];
                    $_SESSION['pending_verification_email'] = $user['email'];
                    
                    $otp = createOTP($user['id'], 'email_verify');
                    sendOTPEmail($user['email'], $otp, 'email_verify');
                    
                    flashMessage('warning', __('email_not_verified'));
                    header('Location: verify_email.php');
                    exit();
                }
                
                $newDeviceOtpEnabled = getSetting('new_device_otp_enabled', '1') === '1';
                
                if ($newDeviceOtpEnabled && !isTrustedDevice($user['id'])) {
                    $otp = createOTP($user['id'], 'login_verify');
                    $emailSent = sendOTPEmail($user['email'], $otp, 'login_verify');
                    
                    $_SESSION['pending_login_user_id'] = $user['id'];
                    $_SESSION['pending_login_email'] = $user['email'];
                    $_SESSION['pending_login_username'] = $user['username'];
                    $_SESSION['pending_login_redirect'] = $_SESSION['redirect_url'] ?? 'index.php';
                    unset($_SESSION['redirect_url']);
                    
                    if (!$emailSent) {
                        flashMessage('info', __('otp_email_failed')); 
                        error_log("OTP Login failed for user ID: " . $user['id']);
                    } else {
                        flashMessage('info', __('new_device_otp_sent'));
                    }
                    
                    header('Location: verify_otp.php');
                    exit();
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['is_admin'] = in_array($user['role'], ['admin', 'super_admin']) ? 1 : 0;
                
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                addTrustedDevice($user['id']);
                logActivity('login', 'User logged in');
                
                flashMessage('success', __('login_success') . '، ' . $user['username'] . '!');
                
                if (in_array($user['role'], ['admin', 'super_admin'])) {
                    header('Location: admin/index.php');
                } else {
                    $redirect = $_SESSION['redirect_url'] ?? 'index.php';
                    unset($_SESSION['redirect_url']);
                    header('Location: ' . $redirect);
                }
                exit();

            } else {
                $error = __('invalid_credentials');
                
                if (function_exists('incrementLoginAttempts')) {
                    incrementLoginAttempts($identifier);
                }
                
                if (!$user) {
                    usleep(rand(100000, 300000));
                }

                logActivity('failed_login', 'Failed login attempt for: ' . $identifier);
            }
        }
    }
}

$pageTitle = __('login');
include 'includes/header_minimal.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">🛡️</div>
            <h1 class="auth-title"><?php echo __('login_title'); ?></h1>
            <p class="auth-subtitle"><?php echo __('hero_subtitle'); ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label class="form-label" for="identifier"><?php echo __('username_or_email'); ?></label>
                <input 
                    type="text" 
                    id="identifier" 
                    name="identifier" 
                    class="form-input" 
                    placeholder="<?php echo __('username_or_email_placeholder'); ?>"
                    value="<?php echo isset($_POST['identifier']) ? sanitize($_POST['identifier']) : ''; ?>"
                    required
                    autocomplete="username"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password"><?php echo __('password'); ?></label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <div class="security-note">
                <span class="note-icon">🔒</span>
                <span><?php echo __('login_security_note'); ?></span>
            </div>
            
            <button type="submit" class="btn btn-neon btn-block">
                <?php echo __('login'); ?>
            </button>
        </form>
        
        <div class="auth-footer">
            <a href="forgot_password.php" class="forgot-link"><?php echo __('forgot_password'); ?></a>
            <span class="footer-divider">|</span>
            <?php echo __('no_account'); ?> <a href="register.php"><?php echo __('register'); ?></a>
        </div>
        
        <div class="terminal-box">
            <div class="terminal-line">
                <span class="terminal-prompt">$</span> ssh hacker@alwanictf.com
            </div>
            <div class="terminal-line">
                <span class="terminal-output">Awaiting authentication...</span>
            </div>
        </div>
    </div>
</div>

<style>
.security-note {
    background: rgba(0, 229, 255, 0.1);
    border: 1px solid rgba(0, 229, 255, 0.3);
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    color: var(--neon-cyan);
}

.note-icon {
    font-size: 1.1rem;
}
</style>

<?php include 'includes/footer_minimal.php'; ?>