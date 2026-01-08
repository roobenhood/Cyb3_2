<?php
class Session
{
    private static bool $started = false;
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }

        session_name(SESSION_NAME);
        session_start();
        self::$started = true;

        self::validateSession();
    }

    private static function validateSession(): void
    {

        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > SESSION_LIFETIME) {
                self::destroy();
                return;
            }
        }
        $_SESSION['last_activity'] = time();

        $fingerprint = self::generateFingerprint();
        if (isset($_SESSION['fingerprint'])) {
            if ($_SESSION['fingerprint'] !== $fingerprint) {
                self::destroy();
                return;
            }
        } else {
            $_SESSION['fingerprint'] = $fingerprint;
        }

        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } elseif (time() - $_SESSION['created_at'] > SESSION_REGENERATE_TIME) {
            self::regenerate();
        }
    }

    private static function generateFingerprint(): string
    {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        return hash('sha256', implode('|', $data));
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['created_at'] = time();
        }
    }
    public static function login(array $user): void
    {
        self::start();
        self::regenerate();

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = self::getClientIP();

        self::regenerateCSRFToken();
    }

    public static function logout(): void
    {
        self::destroy();
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$started = false;
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function isAdmin(): bool
    {
        self::start();
        return self::isLoggedIn() &&
               isset($_SESSION['user_role']) &&
               $_SESSION['user_role'] === ROLE_ADMIN;
    }

    public static function getUserId(): ?int
    {
        self::start();
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function getUserName(): ?string
    {
        self::start();
        return $_SESSION['user_name'] ?? null;
    }

    public static function getUserRole(): ?string
    {
        self::start();
        return $_SESSION['user_role'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            self::setFlash('error', 'يجب تسجيل الدخول أولاً');
            header('Location: auth.php?action=login');
            exit;
        }
    }
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            self::setFlash('error', 'ليس لديك صلاحية الوصول لهذه الصفحة');
            header('Location: index.php');
            exit;
        }
    }

    public static function getClientIP(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
    public static function regenerateCSRFToken(): string
    {
        self::start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        return $_SESSION['csrf_token'];
    }

    public static function getCSRFToken(): string
    {
        self::start();
        if (!isset($_SESSION['csrf_token'])) {
            return self::regenerateCSRFToken();
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken(?string $token): bool
    {
        self::start();

        if (empty($token) || !isset($_SESSION['csrf_token'])) {
            return false;
        }

        if (isset($_SESSION['csrf_token_time'])) {
            if (time() - $_SESSION['csrf_token_time'] > 3600) {
                self::regenerateCSRFToken();
                return false;
            }
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::getCSRFToken() . '">';
    }
    public static function setFlash(string $type, string $message): void
    {
        self::start();
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function getFlash(): ?array
    {
        self::start();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    public static function hasFlash(): bool
    {
        self::start();
        return isset($_SESSION['flash']);
    }
}

class Security
{
    public static function sanitize(string $input): string
    {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $input;
    }
    public static function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $key = self::sanitize((string)$key);
            if (is_string($value)) {
                $sanitized[$key] = self::sanitize($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isStrongPassword(string $password): bool
    {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        return true;
    }

    public static function getPasswordError(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'كلمة المرور يجب أن تحتوي على حرف كبير واحد على الأقل';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'كلمة المرور يجب أن تحتوي على حرف صغير واحد على الأقل';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل';
        }
        return null;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function isPostRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function validateRequest(): bool
    {
        if (!self::isPostRequest()) {
            return true;
        }
        $token = $_POST['csrf_token'] ?? null;
        return Session::validateCSRFToken($token);
    }

    public static function preventCaching(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public static function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

   
    public static function checkRateLimit(string $key, int $maxAttempts = 10, int $windowSeconds = 60): bool
    {
        Session::start();

        $rateLimitKey = 'rate_limit_' . $key;
        $now = time();

        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = ['count' => 1, 'start' => $now];
            return true;
        }

        $data = $_SESSION[$rateLimitKey];

        if ($now - $data['start'] > $windowSeconds) {
            $_SESSION[$rateLimitKey] = ['count' => 1, 'start' => $now];
            return true;
        }

        $_SESSION[$rateLimitKey]['count']++;

        return $data['count'] < $maxAttempts;
    }
}


class Validation
{
    private array $errors = [];
    private array $data = [];

    public function setData(array $data): self
    {
        $this->data = $data;
        $this->errors = [];
        return $this;
    }

    public function required(string $field, string $label): self
    {
        $value = $this->getValue($field);
        if (empty($value) && $value !== '0') {
            $this->errors[$field] = "حقل {$label} مطلوب";
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->getValue($field);
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "البريد الإلكتروني غير صحيح";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        $value = $this->getValue($field);
        if (!empty($value) && mb_strlen($value) < $min) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون على الأقل {$min} أحرف";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label): self
    {
        $value = $this->getValue($field);
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$field] = "حقل {$label} يجب أن لا يتجاوز {$max} حرف";
        }
        return $this;
    }

    public function match(string $field1, string $field2, string $label): self
    {
        $value1 = $this->getValue($field1);
        $value2 = $this->getValue($field2);
        if ($value1 !== $value2) {
            $this->errors[$field2] = "{$label} غير متطابقة";
        }
        return $this;
    }

    public function positiveInteger(string $field, string $label): self
    {
        $value = $this->getValue($field);
        if (!empty($value) && (!is_numeric($value) || (int)$value < 0)) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون رقماً موجباً";
        }
        return $this;
    }

    public function strongPassword(string $field, string $label): self
    {
        $value = $this->getValue($field);
        if (!empty($value)) {
            $error = Security::getPasswordError($value);
            if ($error) {
                $this->errors[$field] = $error;
            }
        }
        return $this;
    }

    public function inList(string $field, array $list, string $label): self
    {
        $value = $this->getValue($field);
        if (!empty($value) && !in_array($value, $list)) {
            $this->errors[$field] = "قيمة حقل {$label} غير صحيحة";
        }
        return $this;
    }

    private function getValue(string $field)
    {
        return $this->data[$field] ?? null;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
}


class View
{
    private static string $viewsPath = '';
    private static array $sharedData = [];

    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/') . '/';
    }

    public static function share(string $key, $value): void
    {
        self::$sharedData[$key] = $value;
    }

    public static function render(string $template, array $data = []): void
    {
        $data = array_merge(self::$sharedData, $data);
        extract($data);

        $templatePath = self::$viewsPath . str_replace('.', '/', $template) . '.php';

        if (!file_exists($templatePath)) {
            throw new Exception("القالب غير موجود: {$template}");
        }

        include $templatePath;
    }

    public static function flashMessage(): void
    {
        $flash = Session::getFlash();
        if ($flash) {
            $type = $flash['type'] === 'success' ? 'success' : 'error';
            echo '<div class="alert alert-' . $type . '">';
            echo htmlspecialchars($flash['message']);
            echo '</div>';
        }
    }

    public static function formatDate(string $date): string
    {
        return date('Y/m/d', strtotime($date));
    }

    public static function truncate(string $text, int $length = 100): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '...';
    }
}


View::setViewsPath(BASE_PATH . '/templates');
