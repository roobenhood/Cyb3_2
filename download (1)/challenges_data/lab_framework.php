<?php
/**
 * Lab Framework - إطار عمل اللابات
 * يوفر الوظائف المشتركة لجميع اللابات
 */

// تحميل الإعدادات - البحث التصاعدي
$configLoaded = false;
$dir = __DIR__;
for ($i = 0; $i < 5; $i++) {
    $configPath = $dir . '/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
        break;
    }
    $dir = dirname($dir);
}

class LabFramework {
    private $pdo;
    private $labType;      // xss, sqli, file_upload, path_traversal
    private $labId;        // معرف اللاب في DB (إذا وجد)
    private $userId;
    private $totalSteps;
    private $currentStep;
    private $labConfig;
    
    public function __construct($labType, $totalSteps = 5) {
        global $pdo;
        $this->pdo = $pdo;
        $this->labType = $labType;
        $this->totalSteps = $totalSteps;
        $this->userId = $_SESSION['user_id'] ?? null;
        
        // تحميل تقدم المستخدم من الجلسة
        $sessionKey = $labType . '_current_step';
        $this->currentStep = $_SESSION[$sessionKey] ?? 1;
    }
    
    /**
     * الحصول على معلومات التقدم
     */
    public function getProgress() {
        return [
            'current_step' => $this->currentStep,
            'total_steps' => $this->totalSteps,
            'percentage' => round(($this->currentStep / $this->totalSteps) * 100),
            'completed' => $this->currentStep >= $this->totalSteps
        ];
    }
    
    /**
     * الانتقال للخطوة التالية
     */
    public function nextStep() {
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $_SESSION[$this->labType . '_current_step'] = $this->currentStep;
        }
        return $this->currentStep;
    }
    
    /**
     * تعيين خطوة محددة
     */
    public function setStep($step) {
        $step = max(1, min($step, $this->totalSteps));
        $this->currentStep = $step;
        $_SESSION[$this->labType . '_current_step'] = $this->currentStep;
        return $this->currentStep;
    }
    
    /**
     * إعادة تعيين التقدم
     */
    public function resetProgress() {
        $this->currentStep = 1;
        $_SESSION[$this->labType . '_current_step'] = 1;
        unset($_SESSION[$this->labType . '_completed']);
    }
    
    /**
     * التحقق من حل تحدي معين (باستخدام pattern)
     */
    public function checkSolution($input, $patterns, $requiredPatterns = 1) {
        $matchCount = 0;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $matchCount++;
            }
        }
        return $matchCount >= $requiredPatterns;
    }
    
    /**
     * تسجيل إكمال اللاب في قاعدة البيانات
     */
    public function completeLab($flag, $points = 100) {
        if (!$this->userId) {
            return [
                'success' => false,
                'message' => 'يجب تسجيل الدخول',
                'logged_in' => false
            ];
        }
        
        // تسجيل الإكمال في الجلسة
        $_SESSION[$this->labType . '_completed'] = true;
        
        try {
            // البحث عن التحدي في قاعدة البيانات
            $stmt = $this->pdo->prepare("
                SELECT id, points, flag FROM challenges 
                WHERE folder_name = ? AND is_active = 1
            ");
            $stmt->execute([$this->labType]);
            $challenge = $stmt->fetch();
            
            if (!$challenge) {
                return [
                    'success' => true,
                    'message' => 'أكملت اللاب! (غير مسجل في DB)',
                    'points_earned' => 0,
                    'registered' => false
                ];
            }
            
            // التحقق من عدم الحل مسبقاً
            $stmt = $this->pdo->prepare("
                SELECT id FROM solves WHERE user_id = ? AND challenge_id = ?
            ");
            $stmt->execute([$this->userId, $challenge['id']]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => true,
                    'message' => 'محلول مسبقاً!',
                    'points_earned' => 0,
                    'already_solved' => true
                ];
            }
            
            // التحقق من First Blood
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM solves WHERE challenge_id = ?");
            $stmt->execute([$challenge['id']]);
            $isFirstBlood = ($stmt->fetchColumn() == 0);
            
            $pointsEarned = $challenge['points'] ?: $points;
            
            // تسجيل الحل
            $stmt = $this->pdo->prepare("
                INSERT INTO solves (user_id, challenge_id, points_earned, is_first_blood) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$this->userId, $challenge['id'], $pointsEarned, $isFirstBlood ? 1 : 0]);
            
            // تحديث نقاط المستخدم
            $stmt = $this->pdo->prepare("UPDATE users SET score = score + ? WHERE id = ?");
            $stmt->execute([$pointsEarned, $this->userId]);
            
            // تحديث عدد الحلول
            $stmt = $this->pdo->prepare("UPDATE challenges SET solves_count = solves_count + 1 WHERE id = ?");
            $stmt->execute([$challenge['id']]);
            
            // تسجيل النشاط
            if (function_exists('logActivity')) {
                logActivity('lab_completed', "Completed {$this->labType} lab");
            }
            
            return [
                'success' => true,
                'message' => 'تم تسجيل الحل!',
                'points_earned' => $pointsEarned,
                'is_first_blood' => $isFirstBlood,
                'registered' => true
            ];
            
        } catch (PDOException $e) {
            error_log('Lab completion error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطأ في التسجيل',
                'error' => true
            ];
        }
    }
    
    /**
     * التحقق مما إذا كان المستخدم أكمل اللاب
     */
    public function isCompleted() {
        return isset($_SESSION[$this->labType . '_completed']) && $_SESSION[$this->labType . '_completed'] === true;
    }
    
    /**
     * الحصول على التلميح للخطوة الحالية
     */
    public function getHint($stepHints) {
        return $stepHints[$this->currentStep] ?? 'لا يوجد تلميح متاح';
    }
    
    /**
     * تسجيل محاولة فاشلة
     */
    public function logAttempt($input) {
        $sessionKey = $this->labType . '_attempts';
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }
        $_SESSION[$sessionKey][] = [
            'input' => substr($input, 0, 200), // حد أقصى
            'time' => time(),
            'step' => $this->currentStep
        ];
    }
    
    /**
     * الحصول على عدد المحاولات
     */
    public function getAttemptCount() {
        $sessionKey = $this->labType . '_attempts';
        return count($_SESSION[$sessionKey] ?? []);
    }
    
    /**
     * عرض شريط التقدم HTML
     */
    public function renderProgressBar($labName = 'Lab') {
        $progress = $this->getProgress();
        $html = <<<HTML
        <div class="lab-progress-container">
            <div class="lab-progress-header">
                <span class="lab-name">{$labName}</span>
                <span class="lab-step">الخطوة {$progress['current_step']} من {$progress['total_steps']}</span>
            </div>
            <div class="lab-progress-bar">
                <div class="lab-progress-fill" style="width: {$progress['percentage']}%"></div>
            </div>
        </div>
        HTML;
        return $html;
    }
}

/**
 * CSS مشترك للابات
 */
function getLabStyles() {
    return <<<CSS
    <style>
        :root {
            --lab-primary: #00ff88;
            --lab-secondary: #00d9ff;
            --lab-warning: #feca57;
            --lab-danger: #ff6b6b;
            --lab-dark: #0a0a0a;
            --lab-darker: #050505;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, var(--lab-dark) 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
        }
        
        .lab-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        /* Progress Bar */
        .lab-progress-container {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .lab-progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .lab-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--lab-primary);
        }
        
        .lab-step {
            color: #888;
        }
        
        .lab-progress-bar {
            height: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .lab-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--lab-primary), var(--lab-secondary));
            border-radius: 5px;
            transition: width 0.5s ease;
        }
        
        /* Scenario Box */
        .lab-scenario {
            background: linear-gradient(135deg, rgba(0,255,136,0.05), rgba(0,217,255,0.05));
            border: 1px solid rgba(0,255,136,0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .lab-scenario h2 {
            color: var(--lab-primary);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .lab-scenario p {
            color: #bbb;
            line-height: 1.8;
        }
        
        /* Objective Box */
        .lab-objective {
            background: rgba(254,202,87,0.1);
            border: 1px solid rgba(254,202,87,0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .lab-objective h3 {
            color: var(--lab-warning);
            margin-bottom: 10px;
        }
        
        .lab-objective ul {
            margin-right: 25px;
            color: #ddd;
        }
        
        .lab-objective li {
            margin-bottom: 8px;
        }
        
        /* Hint Box */
        .lab-hint {
            background: rgba(0,217,255,0.1);
            border: 1px solid rgba(0,217,255,0.3);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            cursor: pointer;
        }
        
        .lab-hint summary {
            color: var(--lab-secondary);
            font-weight: bold;
            cursor: pointer;
        }
        
        .lab-hint p {
            margin-top: 15px;
            color: #bbb;
            padding: 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
        }
        
        .lab-hint code {
            background: #000;
            padding: 2px 8px;
            border-radius: 4px;
            color: var(--lab-primary);
        }
        
        /* Vulnerable App Frame */
        .vulnerable-app {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            margin-bottom: 25px;
        }
        
        .app-header {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: #fff;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .app-url-bar {
            flex: 1;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            direction: ltr;
        }
        
        .app-content {
            padding: 30px;
            color: #333;
        }
        
        /* Forms */
        .lab-form .form-group {
            margin-bottom: 20px;
        }
        
        .lab-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        
        .lab-form input,
        .lab-form textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .lab-form input:focus,
        .lab-form textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .lab-form button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .lab-form button:hover {
            transform: translateY(-2px);
        }
        
        /* Success Box */
        .lab-success {
            background: linear-gradient(135deg, rgba(0,255,136,0.1), rgba(0,200,100,0.05));
            border: 2px solid var(--lab-primary);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin: 25px 0;
            animation: successPulse 2s infinite;
        }
        
        @keyframes successPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0,255,136,0.4); }
            50% { box-shadow: 0 0 30px 10px rgba(0,255,136,0.2); }
        }
        
        .lab-success h2 {
            color: var(--lab-primary);
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .lab-flag {
            font-family: 'Courier New', monospace;
            font-size: 1.3rem;
            background: #000;
            color: var(--lab-primary);
            padding: 15px 30px;
            border-radius: 10px;
            display: inline-block;
            margin: 20px 0;
            letter-spacing: 1px;
        }
        
        .lab-points {
            font-size: 1.5rem;
            color: var(--lab-secondary);
            margin: 15px 0;
        }
        
        .first-blood-badge {
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: #000;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
CSS;
}
