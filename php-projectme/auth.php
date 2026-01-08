<?php

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/core/Core.php';
require_once BASE_PATH . '/controllers/Controller.php';

Security::setSecurityHeaders();
Security::preventCaching();
Session::start();

$action = $_GET['action'] ?? 'login';
$error = '';
$name = '';
$email = '';


if ($action === 'logout') {
    $authController = new AuthController();
    $authController->logout();
    Session::start();
    Session::setFlash('success', 'تم تسجيل الخروج بنجاح');
    header('Location: index.php');
    exit;
}


if (Session::isLoggedIn() && in_array($action, ['login', 'register'])) {
    header('Location: index.php');
    exit;
}


if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateRequest()) {
        $error = 'طلب غير صالح. حاول مرة أخرى';
    } else {
        $authController = new AuthController();
        $result = $authController->login([
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? ''
        ]);

        if ($result['success']) {
            Session::setFlash('success', 'تم تسجيل الدخول بنجاح');
            header('Location: ' . $result['redirect']);
            exit;
        } else {
            $error = $result['error'];
            $email = $_POST['email'] ?? '';
        }
    }
}

if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateRequest()) {
        $error = 'طلب غير صالح. حاول مرة أخرى';
    } else {
        $authController = new AuthController();
        $result = $authController->register([
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ]);

        if ($result['success']) {
            Session::setFlash('success', 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول');
            header('Location: auth.php?action=login');
            exit;
        } else {
            $error = $result['error'];
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
        }
    }
}


ob_start();

if ($action === 'login'):
?>
<div class="auth-container">
    <div class="auth-card">
        <h1>🔐 تسجيل الدخول</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="auth.php?action=login" method="POST" class="auth-form">
            <?php echo Session::csrfField(); ?>

            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                    placeholder="أدخل بريدك الإلكتروني"
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    required
                    placeholder="أدخل كلمة المرور"
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">دخول</button>
        </form>

        <div class="auth-footer">
            <p>ليس لديك حساب؟ <a href="auth.php?action=register">سجل الآن</a></p>
        </div>
    </div>
</div>

<?php elseif ($action === 'register'): ?>
<div class="auth-container">
    <div class="auth-card">
        <h1>📝 إنشاء حساب جديد</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="auth.php?action=register" method="POST" class="auth-form">
            <?php echo Session::csrfField(); ?>

            <div class="form-group">
                <label for="name">الاسم الكامل</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    value="<?php echo htmlspecialchars($name); ?>"
                    required
                    placeholder="أدخل اسمك الكامل"
                    autocomplete="name"
                >
            </div>

            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                    placeholder="أدخل بريدك الإلكتروني"
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    required
                    placeholder="8 أحرف على الأقل مع حرف كبير وصغير ورقم"
                    autocomplete="new-password"
                >
                <small class="form-hint">يجب أن تحتوي على 8 أحرف، حرف كبير، حرف صغير، ورقم</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">تأكيد كلمة المرور</label>
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    class="form-control"
                    required
                    placeholder="أعد إدخال كلمة المرور"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">إنشاء الحساب</button>
        </form>

        <div class="auth-footer">
            <p>لديك حساب بالفعل؟ <a href="auth.php?action=login">سجل دخول</a></p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = $action === 'login' ? 'تسجيل الدخول' : 'إنشاء حساب';
$currentPage = $action;
include BASE_PATH . '/templates/layout.php';
