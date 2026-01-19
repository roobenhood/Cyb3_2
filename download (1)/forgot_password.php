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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_verified']);
}

$error = '';
$success = '';
$step = 'request';

if (isset($_SESSION['reset_user_id']) && isset($_SESSION['reset_verified'])) {
    $step = 'reset';
} elseif (isset($_SESSION['reset_user_id'])) {
    $step = 'verify';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die(__('invalid_csrf_token'));
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'request') {
        $email = sanitize($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = __('enter_email');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('invalid_email');
        } else {
            if (function_exists('checkRateLimit') && !checkRateLimit('password_reset_request', 3, 3600)) {
                $error = __('too_many_reset_attempts');
            } else {
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ? AND is_active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $otp = createOTP($user['id'], 'password_reset');
                    $emailSent = sendOTPEmail($user['email'], $otp, 'password_reset');
                    
                    $_SESSION['reset_user_id'] = $user['id'];
                    $_SESSION['reset_email'] = $user['email'];
                    
                    logActivity('password_reset_request', 'Password reset requested', $user['id']);
                    
                    if ($emailSent) {
                        $success = __('reset_code_sent');
                        $step = 'verify';
                    } else {
                        $error = __('reset_code_sent_failed');
                        error_log("Failed to send OTP to user ID: " . $user['id']);
                        unset($_SESSION['reset_user_id']);
                        unset($_SESSION['reset_email']);
                        $step = 'request'; 
                    }
                } else {
                    usleep(rand(500000, 1500000));
                    $success = __('reset_code_sent');
                }
            }
        }
    } elseif ($action === 'verify') {
        $code = sanitize($_POST['otp_code'] ?? '');
        
        if (!isset($_SESSION['reset_user_id'])) {
            $error = __('session_expired_start_over');
            $step = 'request';
        } elseif (empty($code)) {
            $error = __('enter_otp_code');
        } elseif (strlen($code) !== 6 || !ctype_digit($code)) {
            $error = __('invalid_otp_format');
        } else {
            if (function_exists('checkRateLimit') && !checkRateLimit('otp_verify_' . $_SESSION['reset_user_id'], 5, 600)) {
                $error = __('too_many_failed_attempts') . ' ' . __('try_again_later');
            } else {
                if (verifyOTP($_SESSION['reset_user_id'], $code, 'password_reset')) {
                    $_SESSION['reset_verified'] = true;
                    $step = 'reset';
                    $success = __('code_verified');
                } else {
                    $error = __('invalid_or_expired_otp');
                }
            }
        }
    } elseif ($action === 'reset') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_verified'])) {
            $error = __('session_expired');
            $step = 'request';
        } elseif (empty($password)) {
            $error = __('enter_password');
        } elseif (strlen($password) < 8) { 
            $error = __('password_too_short_8_chars');
        } elseif ($password !== $confirm_password) {
            $error = __('password_mismatch');
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashedPassword, $_SESSION['reset_user_id']])) {
                logActivity('password_reset_success', 'Password reset successfully', $_SESSION['reset_user_id']);
                
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_verified']);
                
                flashMessage('success', __('password_reset_success'));
                header('Location: login.php');
                exit();
            } else {
                $error = __('something_went_wrong');
            }
        }
    } elseif ($action === 'resend') {
        if (isset($_SESSION['reset_user_id']) && isset($_SESSION['reset_email'])) {
            if (function_exists('checkRateLimit') && !checkRateLimit('otp_resend', 3, 300)) {
                $error = __('too_many_resend_attempts');
            } else {
                $otp = createOTP($_SESSION['reset_user_id'], 'password_reset');
                $emailSent = sendOTPEmail($_SESSION['reset_email'], $otp, 'password_reset');
                
                if ($emailSent) {
                    $success = __('otp_resent');
                } else {
                    $error = __('otp_resend_failed'); 
                }
            }
        } else {
            $error = __('session_expired');
            $step = 'request';
        }
    } elseif ($action === 'back') {
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_verified']);
        $step = 'request';
    }
}

$pageTitle = __('forgot_password');
include 'includes/header_minimal.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon" style="background: linear-gradient(135deg, var(--neon-orange), var(--neon-red));">🔐</div>
            <h1 class="auth-title"><?php echo __('forgot_password'); ?></h1>
            <p class="auth-subtitle">
                <?php 
                if ($step === 'request') {
                    echo __('forgot_password_description');
                } elseif ($step === 'verify') {
                    echo __('verify_reset_code_description');
                } else {
                    echo __('enter_new_password_description');
                }
                ?>
            </p>
        </div>
        
        <div class="password-reset-steps">
            <div class="step <?php echo $step === 'request' ? 'active' : ($step !== 'request' ? 'completed' : ''); ?>">
                <span class="step-number">1</span>
                <span class="step-label"><?php echo __('email'); ?></span>
            </div>
            <div class="step-line <?php echo $step !== 'request' ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step === 'verify' ? 'active' : ($step === 'reset' ? 'completed' : ''); ?>">
                <span class="step-number">2</span>
                <span class="step-label"><?php echo __('verify'); ?></span>
            </div>
            <div class="step-line <?php echo $step === 'reset' ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step === 'reset' ? 'active' : ''; ?>">
                <span class="step-number">3</span>
                <span class="step-label"><?php echo __('reset'); ?></span>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'request'): ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="request">
            
            <div class="form-group">
                <label class="form-label" for="email"><?php echo __('email'); ?></label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="hacker@alwanictf.com"
                    required
                    autocomplete="email"
                >
            </div>
            
            <button type="submit" class="btn btn-neon btn-block">
                <?php echo __('send_reset_code'); ?>
            </button>
        </form>
        
        <?php elseif ($step === 'verify'): ?>
        <div class="email-info">
            <span class="email-label"><?php echo __('reset_code_sent_to'); ?></span>
            <span class="email-value"><?php echo sanitize($_SESSION['reset_email'] ?? ''); ?></span>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="verify">
            
            <div class="form-group">
                <label class="form-label"><?php echo __('enter_verification_code'); ?></label>
                <div class="otp-input-container">
                    <input type="text" name="otp_code" class="otp-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-neon btn-block">
                <?php echo __('verify_code'); ?>
            </button>
        </form>
        
        <div class="resend-section">
            <p><?php echo __('didnt_receive_code'); ?></p>
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="btn btn-outline btn-sm">
                    <?php echo __('resend_code'); ?>
                </button>
            </form>
            
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="back">
                <button type="submit" class="btn btn-ghost btn-sm">
                    <?php echo __('change_email'); ?>
                </button>
            </form>
        </div>
        
        <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="reset">
            
            <div class="form-group">
                <label class="form-label" for="password"><?php echo __('new_password'); ?></label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="••••••••"
                    minlength="8" 
                    required
                    autocomplete="new-password"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="confirm_password"><?php echo __('confirm_password'); ?></label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-input" 
                    placeholder="••••••••"
                    required
                    autocomplete="new-password"
                >
            </div>
            
            <div class="password-requirements">
                <span class="req-icon">🔒</span>
                <span><?php echo __('password_requirements'); ?> (Min 8 chars)</span>
            </div>
            
            <button type="submit" class="btn btn-neon btn-block">
                <?php echo __('reset_password'); ?>
            </button>
        </form>
        <?php endif; ?>
        
        <div class="auth-footer">
            <a href="login.php">← <?php echo __('back_to_login'); ?></a>
        </div>
        
        <div class="terminal-box">
            <div class="terminal-line">
                <span class="terminal-prompt">$</span> ./recover_access.sh
            </div>
            <div class="terminal-line">
                <span class="terminal-output">Initiating recovery protocol... █</span>
            </div>
        </div>
    </div>
</div>

<style>
.password-reset-steps { display: flex; align-items: center; justify-content: center; margin-bottom: 30px; padding: 20px 0; }
.step { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.step-number { width: 35px; height: 35px; border-radius: 50%; background: var(--bg-tertiary); border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary); transition: all 0.3s ease; }
.step.active .step-number { background: var(--neon-green); border-color: var(--neon-green); color: var(--bg-primary); box-shadow: 0 0 15px rgba(0, 255, 136, 0.5); }
.step.completed .step-number { background: rgba(0, 255, 136, 0.2); border-color: var(--neon-green); color: var(--neon-green); }
.step.completed .step-number::after { content: '✓'; }
.step-label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
.step.active .step-label { color: var(--neon-green); }
.step-line { width: 50px; height: 2px; background: var(--border-color); margin: 0 10px; margin-bottom: 25px; transition: all 0.3s ease; }
.step-line.completed { background: var(--neon-green); }
.email-info { background: rgba(0, 255, 136, 0.1); border: 1px solid rgba(0, 255, 136, 0.3); border-radius: 10px; padding: 15px; margin-bottom: 25px; text-align: center; }
.email-label { display: block; color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 5px; }
.email-value { display: block; color: var(--neon-green); font-size: 1.1rem; font-weight: 600; direction: ltr; }
.otp-input-container { display: flex; justify-content: center; }
.otp-input { width: 100%; max-width: 250px; text-align: center; font-size: 2rem; font-family: 'Courier New', monospace; letter-spacing: 10px; padding: 15px; background: var(--bg-tertiary); border: 2px solid var(--border-color); border-radius: 10px; color: var(--neon-green); transition: all 0.3s ease; }
.otp-input:focus { outline: none; border-color: var(--neon-green); box-shadow: 0 0 20px rgba(0, 255, 136, 0.3); }
.resend-section { margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center; }
.resend-section p { color: var(--text-secondary); margin-bottom: 15px; font-size: 0.9rem; }
.btn-ghost { background: transparent; border: none; color: var(--text-secondary); cursor: pointer; padding: 8px 15px; }
.btn-ghost:hover { color: var(--neon-cyan); }
.password-requirements { background: rgba(255, 200, 0, 0.1); border: 1px solid rgba(255, 200, 0, 0.3); border-radius: 8px; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: #ffc800; }
.req-icon { font-size: 1.1rem; }
</style>

<script>
document.querySelector('.otp-input')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
});
</script>

<?php include 'includes/footer_minimal.php'; ?>