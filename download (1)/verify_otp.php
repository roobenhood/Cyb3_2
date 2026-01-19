<?php
require_once 'config.php';

// التحقق من وجود جلسة التحقق
if (!isset($_SESSION['pending_login_user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['pending_login_user_id'];
$email = $_SESSION['pending_login_email'] ?? '';
$username = $_SESSION['pending_login_username'] ?? '';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'resend') {
        // إعادة إرسال الرمز
        $otp = createOTP($userId, 'login_verify');
        $emailSent = sendOTPEmail($email, $otp, 'login_verify');
        
        if ($emailSent) {
            $success = __('otp_resent');
        } else {
            $success = __('otp_resend_failed') . ' ' . __('otp_code') . ': ' . $otp;
        }
    } else {
        // التحقق من الرمز
        $code = sanitize($_POST['otp_code'] ?? '');
        $trustDevice = isset($_POST['trust_device']) ? true : false;
        
        if (empty($code)) {
            $error = __('enter_otp_code');
        } elseif (strlen($code) !== 6 || !ctype_digit($code)) {
            $error = __('invalid_otp_format');
        } else {
            if (verifyOTP($userId, $code, 'login_verify')) {
                // الحصول على معلومات المستخدم
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // تسجيل الدخول
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['is_admin'] = in_array($user['role'], ['admin', 'super_admin']) ? 1 : 0;
                    
                    // إضافة الجهاز كموثوق إذا طلب المستخدم
                    if ($trustDevice) {
                        addTrustedDevice($user['id']);
                    }
                    
                    // تحديث آخر دخول
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // حذف جلسة التحقق
                    unset($_SESSION['pending_login_user_id']);
                    unset($_SESSION['pending_login_email']);
                    unset($_SESSION['pending_login_username']);
                    unset($_SESSION['pending_login_redirect']);
                    
                    // تسجيل النشاط
                    logActivity('login_verified', 'Login from new device verified');
                    
                    flashMessage('success', __('login_success') . '، ' . $user['username'] . '!');
                    
                    // إعادة التوجيه حسب الدور
                    if (in_array($user['role'], ['admin', 'super_admin'])) {
                        header('Location: admin/index.php');
                    } else {
                        $redirect = $_SESSION['pending_login_redirect'] ?? 'index.php';
                        header('Location: ' . $redirect);
                    }
                    exit();
                }
            } else {
                $error = __('invalid_or_expired_otp');
            }
        }
    }
}

$pageTitle = __('verify_login');
include 'includes/header_minimal.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon" style="background: linear-gradient(135deg, var(--neon-yellow), var(--neon-orange));">🔐</div>
            <h1 class="auth-title"><?php echo __('verify_login'); ?></h1>
            <p class="auth-subtitle"><?php echo __('new_device_detected'); ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="device-warning">
            <span class="warning-icon">⚠️</span>
            <div class="warning-text">
                <strong><?php echo __('new_device_warning'); ?></strong>
                <p><?php echo __('new_device_description'); ?></p>
            </div>
        </div>
        
        <div class="email-info">
            <span class="email-label"><?php echo __('verification_sent_to'); ?></span>
            <span class="email-value"><?php echo sanitize($email); ?></span>
        </div>
        
        <form method="POST" action="" id="verify-form">
            <div class="form-group">
                <label class="form-label"><?php echo __('enter_verification_code'); ?></label>
                <div class="otp-input-container">
                    <input type="text" name="otp_code" class="otp-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" required>
                </div>
            </div>
            
            <div class="form-group trust-device-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="trust_device" value="1" checked>
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text"><?php echo __('trust_this_device'); ?></span>
                </label>
                <small class="checkbox-hint"><?php echo __('trust_device_hint'); ?></small>
            </div>
            
            <button type="submit" class="btn btn-neon btn-block">
                <?php echo __('verify_and_login'); ?>
            </button>
        </form>
        
        <div class="resend-section">
            <p><?php echo __('didnt_receive_code'); ?></p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="btn btn-outline btn-block">
                    <?php echo __('resend_code'); ?>
                </button>
            </form>
        </div>
        
        <div class="auth-footer">
            <a href="login.php"><?php echo __('back_to_login'); ?></a>
        </div>
        
        <div class="terminal-box">
            <div class="terminal-line">
                <span class="terminal-prompt">$</span> ./verify_device.sh
            </div>
            <div class="terminal-line">
                <span class="terminal-output">Verifying new device... █</span>
            </div>
        </div>
    </div>
</div>

<style>
.device-warning {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.warning-icon {
    font-size: 1.5rem;
}

.warning-text strong {
    display: block;
    color: var(--neon-yellow);
    margin-bottom: 5px;
}

.warning-text p {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0;
}

.email-info {
    background: rgba(0, 255, 136, 0.1);
    border: 1px solid rgba(0, 255, 136, 0.3);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 25px;
    text-align: center;
}

.email-label {
    display: block;
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.email-value {
    display: block;
    color: var(--neon-green);
    font-size: 1.1rem;
    font-weight: 600;
    direction: ltr;
}

.otp-input-container {
    display: flex;
    justify-content: center;
}

.otp-input {
    width: 100%;
    max-width: 250px;
    text-align: center;
    font-size: 2rem;
    font-family: 'Courier New', monospace;
    letter-spacing: 10px;
    padding: 15px;
    background: var(--bg-tertiary);
    border: 2px solid var(--border-color);
    border-radius: 10px;
    color: var(--neon-green);
    transition: all 0.3s ease;
}

.otp-input:focus {
    outline: none;
    border-color: var(--neon-green);
    box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
}

.otp-input::placeholder {
    color: var(--text-secondary);
    opacity: 0.3;
}

.trust-device-group {
    background: var(--bg-tertiary);
    border-radius: 10px;
    padding: 15px;
    margin: 20px 0;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 22px;
    height: 22px;
    border: 2px solid var(--neon-green);
    border-radius: 5px;
    position: relative;
    transition: all 0.3s ease;
}

.checkbox-label input:checked + .checkbox-custom {
    background: var(--neon-green);
}

.checkbox-label input:checked + .checkbox-custom::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--bg-primary);
    font-size: 14px;
    font-weight: bold;
}

.checkbox-text {
    color: var(--text-primary);
    font-weight: 500;
}

.checkbox-hint {
    display: block;
    margin-top: 8px;
    margin-left: 32px;
    color: var(--text-secondary);
    font-size: 0.85rem;
}

.resend-section {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
    text-align: center;
}

.resend-section p {
    color: var(--text-secondary);
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--neon-cyan);
    color: var(--neon-cyan);
}

.btn-outline:hover {
    background: rgba(0, 229, 255, 0.1);
}
</style>

<script>
// تنسيق إدخال OTP
document.querySelector('.otp-input').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
});
</script>

<?php include 'includes/footer_minimal.php'; ?>
