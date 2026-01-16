<?php
require_once 'config.php';

// إذا كان مسجل دخول، انتقل للرئيسية
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// التحقق من تفعيل التسجيل
if (getSetting('registration_enabled', '1') !== '1') {
    flashMessage('error', 'التسجيل مغلق حالياً');
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // التحقق من صحة البيانات
    if (empty($username) || empty($email) || empty($password)) {
        $error = __('fill_all_fields');
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = __('username_length_error');
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = __('username_format_error');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('invalid_email');
    } elseif (strlen($password) < 6) {
        $error = __('weak_password');
    } elseif ($password !== $confirm_password) {
        $error = __('password_mismatch');
    } else {
        // التحقق من عدم وجود المستخدم مسبقاً - اسم المستخدم
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = __('username_exists');
        } else {
            // التحقق من عدم وجود البريد مسبقاً
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = __('email_exists');
            } else {
                // إنشاء الحساب
                $hashed_password = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
                $default_lang = getSetting('default_language', 'ar');
                $emailVerificationRequired = getSetting('email_verification_required', '1') === '1';
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, language, role, email_verified) VALUES (?, ?, ?, ?, 'user', ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password, $default_lang, $emailVerificationRequired ? 0 : 1])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // تسجيل النشاط
                    logActivity('register', 'New user registered', $user_id);
                    
                    if ($emailVerificationRequired) {
                        // إنشاء وإرسال رمز التحقق
                        $otp = createOTP($user_id, 'email_verify');
                        $emailSent = sendOTPEmail($email, $otp, 'email_verify');
                        
                        // حفظ معرف المستخدم للتحقق
                        $_SESSION['pending_verification_user_id'] = $user_id;
                        $_SESSION['pending_verification_email'] = $email;
                        
                        if ($emailSent) {
                            flashMessage('success', __('verification_email_sent'));
                        } else {
                            flashMessage('info', __('verification_email_failed') . ' ' . __('otp_code') . ': ' . $otp);
                        }
                        
                        header('Location: verify_email.php');
                        exit();
                    } else {
                        // تسجيل الدخول تلقائياً إذا لم يكن التحقق مطلوباً
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = 'user';
                        $_SESSION['logged_in'] = true;
                        $_SESSION['is_admin'] = 0;
                        
                        // إضافة الجهاز كموثوق
                        addTrustedDevice($user_id);
                        
                        flashMessage('success', __('register_success') . '! ' . $username);
                        header('Location: index.php');
                        exit();
                    }
                } else {
                    $error = __('something_went_wrong');
                }
            }
        }
    }
}

$pageTitle = __('register');
include 'includes/header_minimal.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon" style="background: linear-gradient(135deg, var(--neon-purple), var(--neon-pink));">🛡️</div>
            <h1 class="auth-title"><?php echo __('register_title'); ?></h1>
            <p class="auth-subtitle"><?php echo __('hero_description'); ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username"><?php echo __('username'); ?></label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    placeholder="h4ck3r_name"
                    value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>"
                    required
                    pattern="[a-zA-Z0-9_]+"
                    minlength="3"
                    maxlength="50"
                >
                <small class="form-hint"><?php echo __('username_hint'); ?></small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email"><?php echo __('email'); ?></label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="hacker@alwanictf.com"
                    value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>"
                    required
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
                    minlength="6"
                    required
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
                >
            </div>
            
            <div class="verification-note">
                <span class="note-icon">📧</span>
                <span><?php echo __('email_verification_note'); ?></span>
            </div>
            
            <button type="submit" class="btn btn-cyber btn-block">
                <?php echo __('register'); ?>
            </button>
        </form>
        
        <div class="auth-footer">
            <?php echo __('have_account'); ?> <a href="login.php"><?php echo __('login'); ?></a>
        </div>
        
        <div class="terminal-box">
            <div class="terminal-line">
                <span class="terminal-prompt">$</span> ./create_hacker.sh
            </div>
            <div class="terminal-line">
                <span class="terminal-output">Initializing new hacker profile... █</span>
            </div>
        </div>
    </div>
</div>

<style>
.verification-note {
    background: rgba(0, 255, 136, 0.1);
    border: 1px solid rgba(0, 255, 136, 0.3);
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--neon-green);
}

.note-icon {
    font-size: 1.2rem;
}

.form-hint {
    display: block;
    margin-top: 5px;
    font-size: 0.8rem;
    color: var(--text-secondary);
}
</style>

<?php include 'includes/footer_minimal.php'; ?>
