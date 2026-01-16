<?php
require_once 'config.php';

// التحقق من وجود جلسة التحقق
if (!isset($_SESSION['pending_verification_user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['pending_verification_user_id'];
$email = $_SESSION['pending_verification_email'] ?? '';

// إرسال رمز OTP تلقائياً عند أول زيارة (إذا لم يتم الإرسال مسبقاً)
if (!isset($_SESSION['otp_sent_for_verification'])) {
    $otp = createOTP($userId, 'email_verify');
    $emailSent = sendOTPEmail($email, $otp, 'email_verify');
    $_SESSION['otp_sent_for_verification'] = true;
    
    if (!$emailSent) {
        // عرض الرمز للمطور في حالة فشل الإرسال
        $_SESSION['debug_otp'] = $otp;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'resend') {
        // إعادة إرسال الرمز
        $otp = createOTP($userId, 'email_verify');
        $emailSent = sendOTPEmail($email, $otp, 'email_verify');
        
        if ($emailSent) {
            $success = __('otp_resent');
        } else {
            $success = __('otp_resend_failed') . ' ' . __('otp_code') . ': ' . $otp;
        }
    } else {
        // التحقق من الرمز
        $code = sanitize($_POST['otp_code'] ?? '');
        
        if (empty($code)) {
            $error = __('enter_otp_code');
        } elseif (strlen($code) !== 6 || !ctype_digit($code)) {
            $error = __('invalid_otp_format');
        } else {
            if (verifyOTP($userId, $code, 'email_verify')) {
                // تحديث البريد كمؤكد
                markEmailAsVerified($userId);
                
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
                    
                    // إضافة الجهاز كموثوق
                    addTrustedDevice($user['id']);
                    
                    // حذف جلسة التحقق
                    unset($_SESSION['pending_verification_user_id']);
                    unset($_SESSION['pending_verification_email']);
                    unset($_SESSION['otp_sent_for_verification']);
                    unset($_SESSION['debug_otp']);
                    
                    // تسجيل النشاط
                    logActivity('email_verified', 'Email verified successfully');
                    
                    flashMessage('success', __('email_verified_success'));
                    header('Location: index.php');
                    exit();
                }
            } else {
                $error = __('invalid_or_expired_otp');
            }
        }
    }
}

$pageTitle = __('verify_email');
include 'includes/header_minimal.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon" style="background: linear-gradient(135deg, var(--neon-green), var(--neon-cyan));">📧</div>
            <h1 class="auth-title"><?php echo __('verify_email'); ?></h1>
            <p class="auth-subtitle"><?php echo __('verify_email_description'); ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['debug_otp'])): ?>
            <div class="alert alert-warning">
                ⚠️ <?php echo __('otp_email_failed'); ?> <?php echo __('otp_code'); ?>: <strong><?php echo $_SESSION['debug_otp']; ?></strong>
            </div>
        <?php endif; ?>
        
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
            
            <button type="submit" class="btn btn-neon btn-block">
                <?php echo __('verify'); ?>
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
        
        <div class="terminal-box">
            <div class="terminal-line">
                <span class="terminal-prompt">$</span> ./verify_identity.sh
            </div>
            <div class="terminal-line">
                <span class="terminal-output">Awaiting verification code... █</span>
            </div>
        </div>
    </div>
</div>

<style>
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
