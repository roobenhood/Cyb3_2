<?php
/**
 * إدارة التحديات - لوحة التحكم
 * Challenge Management - Admin Panel
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$isRTL = ($lang === 'ar');
$t = loadLanguage($lang);

// معالجة تبديل اللغة
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: challenges.php');
    exit;
}

// تأكد أن أعمدة مكافآت التحدي موجودة
function ensureChallengeBonusColumns($pdo) {
    $columns = [
        'bonus_enabled' => "TINYINT(1) DEFAULT 0",
        'bonus_count' => "INT DEFAULT 0",
        'bonus_points' => "TEXT DEFAULT NULL",
    ];

    foreach ($columns as $col => $def) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM challenges LIKE ?");
            $stmt->execute([$col]);
            if ($stmt->rowCount() === 0) {
                $pdo->exec("ALTER TABLE challenges ADD COLUMN {$col} {$def}");
            }
        } catch (PDOException $e) {
            error_log('ensureChallengeBonusColumns: ' . $e->getMessage());
        }
    }
}

ensureChallengeBonusColumns($pdo);

// معالجة الإضافة/التعديل/الحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name_en = sanitize($_POST['name_en'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');
        $description_en = sanitize($_POST['description_en'] ?? '');
        $description_ar = sanitize($_POST['description_ar'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $points = intval($_POST['points'] ?? 0);
        $flag = $_POST['flag'] ?? '';
        $difficulty = $_POST['difficulty'] ?? 'easy';
        $hint_en = sanitize($_POST['hint_en'] ?? '');
        $hint_ar = sanitize($_POST['hint_ar'] ?? '');
        $hint_cost = intval($_POST['hint_cost'] ?? 0);
        $folder_name = sanitize($_POST['folder_name'] ?? '');
        $max_attempts = intval($_POST['max_attempts'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // إعدادات المكافآت
        $bonus_enabled = isset($_POST['bonus_enabled']) ? 1 : 0;
        $bonus_count = $bonus_enabled ? intval($_POST['bonus_count'] ?? 0) : 0;
        $bonus_points = [];
        if ($bonus_enabled && $bonus_count > 0) {
            for ($i = 1; $i <= $bonus_count; $i++) {
                $bp = intval($_POST['bonus_points_' . $i] ?? 0);
                if ($bp > 0) {
                    $bonus_points[$i] = $bp;
                }
            }
        }
        $bonus_points_json = $bonus_enabled ? json_encode($bonus_points) : null;

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO challenges (name_en, name_ar, description_en, description_ar, category_id, points, flag, difficulty, hint_en, hint_ar, hint_cost, folder_name, max_attempts, is_active, bonus_enabled, bonus_count, bonus_points)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name_en, $name_ar, $description_en, $description_ar, $category_id, $points, $flag, $difficulty, $hint_en, $hint_ar, $hint_cost, $folder_name, $max_attempts, $is_active, $bonus_enabled, $bonus_count, $bonus_points_json]);
                flashMessage('success', __('challenge_added'));
            } else {
                $stmt = $pdo->prepare("
                    UPDATE challenges SET name_en=?, name_ar=?, description_en=?, description_ar=?, category_id=?, points=?, flag=?, difficulty=?, hint_en=?, hint_ar=?, hint_cost=?, folder_name=?, max_attempts=?, is_active=?, bonus_enabled=?, bonus_count=?, bonus_points=?
                    WHERE id=?
                ");
                $stmt->execute([$name_en, $name_ar, $description_en, $description_ar, $category_id, $points, $flag, $difficulty, $hint_en, $hint_ar, $hint_cost, $folder_name, $max_attempts, $is_active, $bonus_enabled, $bonus_count, $bonus_points_json, $id]);
                flashMessage('success', __('challenge_updated'));
            }
        } catch (PDOException $e) {
            error_log('admin/challenges.php save failed: ' . $e->getMessage());
            flashMessage('error', __('error_occurred'));
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM challenges WHERE id = ?");
            $stmt->execute([$id]);
            flashMessage('success', __('challenge_deleted'));
        } catch (PDOException $e) {
            error_log('admin/challenges.php delete failed: ' . $e->getMessage());
            flashMessage('error', __('error_occurred'));
        }
    }
    
    header('Location: challenges.php');
    exit();
}

// جلب الفئات
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name_" . $lang);
$categories = $stmt->fetchAll();

// جلب المجلدات المتاحة في challenges_data بشكل متفرع (recursive)
$challengeFolders = [];
$challengesDataPath = __DIR__ . '/../challenges_data/';

function scanLabFolders($basePath, $relativePath = '') {
    $folders = [];
    $fullPath = $basePath . $relativePath;
    
    if (!is_dir($fullPath)) return $folders;
    
    $items = scandir($fullPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || strpos($item, '.') === 0) continue;
        
        $itemPath = $relativePath ? $relativePath . '/' . $item : $item;
        $itemFullPath = $basePath . $itemPath;
        
        if (is_dir($itemFullPath)) {
            // تحقق من وجود index.php
            $hasIndex = file_exists($itemFullPath . '/index.php');
            
            if ($hasIndex) {
                // هذا مجلد لاب صالح
                $folders[] = [
                    'path' => $itemPath,
                    'name' => $item,
                    'full_path' => $itemFullPath,
                    'depth' => substr_count($itemPath, '/')
                ];
            }
            
            // استمر في البحث في المجلدات الفرعية
            $subFolders = scanLabFolders($basePath, $itemPath);
            $folders = array_merge($folders, $subFolders);
        }
    }
    
    return $folders;
}

$challengeFolders = scanLabFolders($challengesDataPath);

// جلب التحديات
$name_col = "name_" . $lang;
$cat_name_col = "cat.name_" . $lang;
$stmt = $pdo->query("
    SELECT c.*, {$cat_name_col} as category_name,
           (SELECT COUNT(*) FROM solves WHERE challenge_id = c.id) as solve_count
    FROM challenges c
    JOIN categories cat ON c.category_id = cat.id
    ORDER BY c.created_at DESC
");
$challenges = $stmt->fetchAll();

$pageTitle = __('manage_challenges');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                <a href="challenges.php" class="nav-item active">🚩 <?php echo __('challenges'); ?></a>
                <a href="categories.php" class="nav-item">📁 <?php echo __('manage_categories'); ?></a>
                <a href="users.php" class="nav-item">👥 <?php echo __('users'); ?></a>
                <a href="teams.php" class="nav-item">🏴 <?php echo __('teams'); ?></a>
                <a href="notifications.php" class="nav-item">🔔 <?php echo __('notifications'); ?></a>
                <a href="settings.php" class="nav-item">⚙️ <?php echo __('settings'); ?></a>
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
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>🚩 <?php echo $pageTitle; ?></h1>
                <button class="btn btn-neon" onclick="openAddModal()">➕ <?php echo __('add_challenge'); ?></button>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?php echo __('title'); ?></th>
                                <th><?php echo __('category'); ?></th>
                                <th><?php echo __('difficulty'); ?></th>
                                <th><?php echo __('points'); ?></th>
                                <th><?php echo __('solves'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($challenges as $challenge): ?>
                                <?php $challenge_name = $lang === 'ar' ? $challenge['name_ar'] : $challenge['name_en']; ?>
                                <tr>
                                    <td><strong><?php echo sanitize($challenge_name); ?></strong></td>
                                    <td><span class="badge"><?php echo $challenge['category_name']; ?></span></td>
                                    <td>
                                        <span class="badge badge-<?php echo $challenge['difficulty']; ?>">
                                            <?php echo __($challenge['difficulty']); ?>
                                        </span>
                                    </td>
                                    <td class="text-success"><?php echo $challenge['points']; ?></td>
                                    <td><?php echo $challenge['solve_count']; ?></td>
                                    <td>
                                        <?php if ($challenge['is_active']): ?>
                                            <span class="status-active">✓ <?php echo __('active'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive">✗ <?php echo __('inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <button class="btn btn-sm btn-outline" onclick='editChallenge(<?php echo json_encode($challenge, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><?php echo __('edit'); ?></button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo __('confirm_delete'); ?>')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $challenge['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><?php echo __('delete'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($challenges)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted"><?php echo __('no_challenges'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><?php echo __('add_challenge'); ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            
            <form method="POST" id="challengeForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="challengeId" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('name_en'); ?></label>
                        <input type="text" name="name_en" id="name_en" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('name_ar'); ?></label>
                        <input type="text" name="name_ar" id="name_ar" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('description_en'); ?></label>
                    <textarea name="description_en" id="description_en" class="form-input" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('description_ar'); ?></label>
                    <textarea name="description_ar" id="description_ar" class="form-input" rows="3"></textarea>
                </div>
                
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('category'); ?></label>
                        <select name="category_id" id="category_id" class="form-input" required>
                            <?php foreach ($categories as $cat): ?>
                                <?php $cat_name = $lang === 'ar' ? $cat['name_ar'] : $cat['name_en']; ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo __('difficulty'); ?></label>
                        <select name="difficulty" id="difficulty" class="form-input">
                            <option value="easy"><?php echo __('easy'); ?></option>
                            <option value="medium"><?php echo __('medium'); ?></option>
                            <option value="hard"><?php echo __('hard'); ?></option>
                            <option value="insane"><?php echo __('insane'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?php echo __('points'); ?></label>
                        <input type="number" name="points" id="points" class="form-input" min="1" value="100" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('flag'); ?></label>
                        <input type="text" name="flag" id="flag" class="form-input" placeholder="CTF{flag_here}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('challenge_folder'); ?></label>
                        <select name="folder_name" id="folder_name" class="form-input" required>
                            <option value="">-- <?php echo __('select'); ?> --</option>
                            <?php 
                            $lastCategory = '';
                            foreach ($challengeFolders as $folder): 
                                $parts = explode('/', $folder['path']);
                                $category = $parts[0] ?? '';
                                $indent = str_repeat('│  ', $folder['depth']);
                                $displayName = $indent . '├─ ' . $folder['name'];
                                
                                // إضافة فاصل للفئة الجديدة
                                if ($category !== $lastCategory && !empty($category)):
                                    $lastCategory = $category;
                            ?>
                                <option disabled style="background: #333; color: var(--neon-primary);">━━ <?php echo strtoupper($category); ?> ━━</option>
                            <?php endif; ?>
                                <option value="<?php echo htmlspecialchars($folder['path']); ?>" title="<?php echo $folder['path']; ?>">
                                    <?php echo htmlspecialchars($displayName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #888; display: block; margin-top: 5px;">
                            📁 يتم الكشف تلقائياً عن المجلدات التي تحتوي على index.php
                        </small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('hint_en'); ?></label>
                        <input type="text" name="hint_en" id="hint_en" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('hint_ar'); ?></label>
                        <input type="text" name="hint_ar" id="hint_ar" class="form-input">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('hint_cost'); ?></label>
                        <input type="number" name="hint_cost" id="hint_cost" class="form-input" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('max_attempts'); ?> (0 = <?php echo __('unlimited'); ?>)</label>
                        <input type="number" name="max_attempts" id="max_attempts" class="form-input" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="is_active" checked>
                        <span><?php echo __('active_visible'); ?></span>
                    </label>
                </div>
                
                <!-- إعدادات المكافآت للتحدي -->
                <div class="bonus-settings-box">
                    <h4>🎯 <?php echo __('early_solver_bonus'); ?></h4>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="bonus_enabled" id="bonus_enabled" onchange="toggleBonusSettings()">
                            <span><?php echo __('enable_bonus'); ?></span>
                        </label>
                    </div>
                    
                    <div id="bonusSettingsContainer" style="display: none;">
                        <div class="form-group">
                            <label class="form-label"><?php echo __('bonus_ranks'); ?></label>
                            <input type="number" name="bonus_count" id="bonus_count" class="form-input" min="1" max="10" value="1" onchange="updateBonusRows()">
                        </div>
                        
                        <div id="bonusPointsContainer"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-neon btn-block"><?php echo __('save'); ?></button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function toggleBonusSettings() {
            const enabled = document.getElementById('bonus_enabled').checked;
            document.getElementById('bonusSettingsContainer').style.display = enabled ? 'block' : 'none';
            if (enabled) {
                updateBonusRows();
            }
        }
        
        function updateBonusRows() {
            const count = parseInt(document.getElementById('bonus_count').value) || 1;
            const container = document.getElementById('bonusPointsContainer');
            container.innerHTML = '';
            
            const labels = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
            const labelText = <?php echo json_encode(__('rank')); ?>;
            const pointsText = <?php echo json_encode(__('bonus_points')); ?>;
            
            for (let i = 1; i <= count; i++) {
                const div = document.createElement('div');
                div.className = 'form-group';
                div.innerHTML = `
                    <label class="form-label">${labels[i-1]} ${labelText} ${i}</label>
                    <input type="number" name="bonus_points_${i}" id="bonus_points_${i}" class="form-input" min="0" placeholder="${pointsText}">
                `;
                container.appendChild(div);
            }
        }
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = <?php echo json_encode(__('add_challenge')); ?>;
            document.getElementById('formAction').value = 'add';
            document.getElementById('challengeForm').reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('bonus_enabled').checked = false;
            document.getElementById('bonusSettingsContainer').style.display = 'none';
            document.getElementById('bonusPointsContainer').innerHTML = '';
            openModal('addModal');
        }
        
        function editChallenge(challenge) {
            document.getElementById('modalTitle').textContent = <?php echo json_encode(__('edit_challenge')); ?>;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('challengeId').value = challenge.id;
            document.getElementById('name_en').value = challenge.name_en || '';
            document.getElementById('name_ar').value = challenge.name_ar || '';
            document.getElementById('description_en').value = challenge.description_en || '';
            document.getElementById('description_ar').value = challenge.description_ar || '';
            document.getElementById('category_id').value = challenge.category_id;
            document.getElementById('difficulty').value = challenge.difficulty;
            document.getElementById('points').value = challenge.points;
            document.getElementById('flag').value = challenge.flag;
            document.getElementById('folder_name').value = challenge.folder_name || '';
            document.getElementById('hint_en').value = challenge.hint_en || '';
            document.getElementById('hint_ar').value = challenge.hint_ar || '';
            document.getElementById('hint_cost').value = challenge.hint_cost || 0;
            document.getElementById('max_attempts').value = challenge.max_attempts || 0;
            document.getElementById('is_active').checked = challenge.is_active == 1;
            
            // إعدادات المكافآت
            document.getElementById('bonus_enabled').checked = challenge.bonus_enabled == 1;
            document.getElementById('bonus_count').value = challenge.bonus_count || 1;
            toggleBonusSettings();
            
            // تحميل قيم النقاط
            if (challenge.bonus_points) {
                const bonusPoints = typeof challenge.bonus_points === 'string' ? JSON.parse(challenge.bonus_points) : challenge.bonus_points;
                setTimeout(() => {
                    for (let i = 1; i <= (challenge.bonus_count || 0); i++) {
                        const input = document.getElementById('bonus_points_' + i);
                        if (input && bonusPoints[i]) {
                            input.value = bonusPoints[i];
                        }
                    }
                }, 100);
            }
            
            openModal('addModal');
        }
    </script>
</body>
</html>
