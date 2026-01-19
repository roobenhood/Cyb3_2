<?php
/**
 * إدارة الفئات - لوحة التحكم
 * Category Management - Admin Panel
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$isRTL = ($lang === 'ar');

// معالجة تبديل اللغة
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: categories.php');
    exit;
}

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name_en = sanitize($_POST['name_en'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');
        $description_en = sanitize($_POST['description_en'] ?? '');
        $description_ar = sanitize($_POST['description_ar'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'fa-flag');
        $color = sanitize($_POST['color'] ?? '#00ff88');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name_en) && empty($name_ar)) {
            flashMessage('error', 'يجب إدخال اسم الفئة');
        } else {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO categories (name_en, name_ar, description_en, description_ar, icon, color, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name_en, $name_ar, $description_en, $description_ar, $icon, $color, $sort_order, $is_active]);
                logActivity('add_category', "Added category: $name_en");
                flashMessage('success', __('category_added'));
            } else {
                $stmt = $pdo->prepare("UPDATE categories SET name_en=?, name_ar=?, description_en=?, description_ar=?, icon=?, color=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$name_en, $name_ar, $description_en, $description_ar, $icon, $color, $sort_order, $is_active, $id]);
                logActivity('edit_category', "Edited category ID: $id");
                flashMessage('success', __('category_updated'));
            }
        }
        header('Location: categories.php');
        exit;
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // التحقق من وجود تحديات في هذه الفئة
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM challenges WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            flashMessage('error', __('category_has_challenges'));
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            logActivity('delete_category', "Deleted category ID: $id");
            flashMessage('success', __('category_deleted'));
        }
        header('Location: categories.php');
        exit;
    }
}

// جلب الفئات
$stmt = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM challenges WHERE category_id = c.id) as challenge_count
    FROM categories c
    ORDER BY c.sort_order, c.id
");
$categories = $stmt->fetchAll();

$pageTitle = __('manage_categories');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
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
                <a href="challenges.php" class="nav-item">🚩 <?php echo __('challenges'); ?></a>
                <a href="categories.php" class="nav-item active">📁 <?php echo __('manage_categories'); ?></a>
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
                <h1>📁 <?php echo __('manage_categories'); ?></h1>
                <button class="btn btn-neon" onclick="openModal('addModal')">
                    ➕ <?php echo __('add_category'); ?>
                </button>
            </div>
            
            <!-- Categories Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?php echo __('name'); ?></th>
                                <th><?php echo __('description'); ?></th>
                                <th><?php echo __('challenges'); ?></th>
                                <th><?php echo __('sort_order'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: <?php echo $cat['color']; ?>;"></span>
                                            <strong><?php echo $lang === 'ar' ? $cat['name_ar'] : $cat['name_en']; ?></strong>
                                        </div>
                                    </td>
                                    <td class="text-muted">
                                        <?php echo mb_substr($lang === 'ar' ? $cat['description_ar'] : $cat['description_en'], 0, 50); ?>...
                                    </td>
                                    <td><?php echo $cat['challenge_count']; ?></td>
                                    <td><?php echo $cat['sort_order']; ?></td>
                                    <td>
                                        <?php if ($cat['is_active']): ?>
                                            <span class="status-active">✓ <?php echo __('active'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive">✗ <?php echo __('inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <button class="btn btn-sm btn-outline" onclick='editCategory(<?php echo json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            <?php echo __('edit'); ?>
                                        </button>
                                        <?php if ($cat['challenge_count'] == 0): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo __('confirm_delete'); ?>')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"><?php echo __('delete'); ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <?php echo __('none'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"><?php echo __('add_category'); ?></h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            
            <form method="POST" id="categoryForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="categoryId" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('name_ar'); ?></label>
                        <input type="text" name="name_ar" id="name_ar" class="form-input" dir="rtl">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('name_en'); ?></label>
                        <input type="text" name="name_en" id="name_en" class="form-input" dir="ltr">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('description_ar'); ?></label>
                    <textarea name="description_ar" id="description_ar" class="form-input" rows="2" dir="rtl"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('description_en'); ?></label>
                    <textarea name="description_en" id="description_en" class="form-input" rows="2" dir="ltr"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('category_icon'); ?></label>
                        <input type="text" name="icon" id="icon" class="form-input" value="fa-flag" dir="ltr">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('category_color'); ?></label>
                        <input type="color" name="color" id="color" class="form-input" value="#00ff88" style="height: 44px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo __('sort_order'); ?></label>
                        <input type="number" name="sort_order" id="sort_order" class="form-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label checkbox-label" style="margin-top: 30px;">
                            <input type="checkbox" name="is_active" id="is_active" checked>
                            <span><?php echo __('active'); ?></span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-neon btn-block"><?php echo __('save'); ?></button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
    function editCategory(cat) {
        document.getElementById('modalTitle').textContent = '<?php echo __('edit_category'); ?>';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('categoryId').value = cat.id;
        document.getElementById('name_ar').value = cat.name_ar || '';
        document.getElementById('name_en').value = cat.name_en || '';
        document.getElementById('description_ar').value = cat.description_ar || '';
        document.getElementById('description_en').value = cat.description_en || '';
        document.getElementById('icon').value = cat.icon || 'fa-flag';
        document.getElementById('color').value = cat.color || '#00ff88';
        document.getElementById('sort_order').value = cat.sort_order || 0;
        document.getElementById('is_active').checked = cat.is_active == 1;
        openModal('addModal');
    }
    
    function resetForm() {
        document.getElementById('modalTitle').textContent = '<?php echo __('add_category'); ?>';
        document.getElementById('formAction').value = 'add';
        document.getElementById('categoryForm').reset();
        document.getElementById('is_active').checked = true;
    }
    
    // Reset form when opening for add
    document.querySelector('[onclick="openModal(\'addModal\')"]').addEventListener('click', resetForm);
    </script>
</body>
</html>
