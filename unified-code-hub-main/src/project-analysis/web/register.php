<?php
/**
 * Register Page - صفحة إنشاء حساب
 * ملف PHP متكامل مع HTML
 */

require_once __DIR__ . '/api/config/config.php';

session_start();

// إذا كان المستخدم مسجل دخوله، أعد توجيهه
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
];

// معالجة التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirmation'] ?? '';
    
    // التحقق من البيانات
    if (empty($formData['name'])) {
        $errors['name'] = 'الاسم مطلوب';
    } elseif (mb_strlen($formData['name']) < 2) {
        $errors['name'] = 'الاسم يجب أن يكون حرفين على الأقل';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'البريد الإلكتروني غير صالح';
    } else {
        // التحقق من عدم وجود البريد مسبقاً
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'البريد الإلكتروني مستخدم مسبقاً';
        }
    }
    
    if (empty($password)) {
        $errors['password'] = 'كلمة المرور مطلوبة';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }
    
    if ($password !== $passwordConfirm) {
        $errors['password_confirmation'] = 'كلمة المرور غير متطابقة';
    }
    
    // إذا لا توجد أخطاء، قم بالتسجيل
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $formData['name'],
                $formData['email'],
                $formData['phone'] ?: null,
                $hashedPassword
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // تسجيل الدخول تلقائياً
            $_SESSION['user_id'] = $userId;
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $formData['name'],
                'email' => $formData['email'],
                'role' => 'user',
                'avatar' => null,
            ];
            
            header('Location: index.php?registered=1');
            exit;
            
        } catch (Exception $e) {
            $errors['general'] = 'حدث خطأ أثناء إنشاء الحساب';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, #10b981 100%);
            padding: 2rem;
        }
        .auth-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .auth-subtitle {
            color: var(--text-secondary);
        }
        .auth-form .btn {
            width: 100%;
            margin-top: 1rem;
        }
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        .auth-footer a {
            color: var(--primary-color);
            font-weight: 600;
        }
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .form-error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .form-input.error {
            border-color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-header">
                <a href="index.php" class="auth-logo">
                    <span class="material-icons">store</span>
                    <span><?php echo APP_NAME; ?></span>
                </a>
                <h1 class="auth-title">إنشاء حساب جديد</h1>
                <p class="auth-subtitle">انضم إلينا اليوم</p>
            </div>

            <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
            <?php endif; ?>

            <form class="auth-form" method="POST">
                <div class="form-group">
                    <label for="name" class="form-label">الاسم الكامل</label>
                    <input type="text" id="name" name="name" 
                           class="form-input <?php echo isset($errors['name']) ? 'error' : ''; ?>" 
                           placeholder="أدخل اسمك الكامل" required
                           value="<?php echo htmlspecialchars($formData['name']); ?>">
                    <?php if (isset($errors['name'])): ?>
                    <p class="form-error"><?php echo htmlspecialchars($errors['name']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" 
                           class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                           placeholder="أدخل بريدك الإلكتروني" required
                           value="<?php echo htmlspecialchars($formData['email']); ?>">
                    <?php if (isset($errors['email'])): ?>
                    <p class="form-error"><?php echo htmlspecialchars($errors['email']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">رقم الهاتف (اختياري)</label>
                    <input type="tel" id="phone" name="phone" class="form-input" 
                           placeholder="05xxxxxxxx"
                           value="<?php echo htmlspecialchars($formData['phone']); ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <input type="password" id="password" name="password" 
                           class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                           placeholder="أدخل كلمة المرور" required>
                    <?php if (isset($errors['password'])): ?>
                    <p class="form-error"><?php echo htmlspecialchars($errors['password']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" 
                           class="form-input <?php echo isset($errors['password_confirmation']) ? 'error' : ''; ?>" 
                           placeholder="أعد إدخال كلمة المرور" required>
                    <?php if (isset($errors['password_confirmation'])): ?>
                    <p class="form-error"><?php echo htmlspecialchars($errors['password_confirmation']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span>أوافق على <a href="terms.php" target="_blank">الشروط والأحكام</a></span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">إنشاء حساب</button>
            </form>

            <div class="auth-footer">
                <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
            </div>
        </div>
    </div>
</body>
</html>
