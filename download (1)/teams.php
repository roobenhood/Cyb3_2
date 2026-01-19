<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

$lang = getCurrentLanguage();
$user = getCurrentUser();

// معالجة إنشاء فريق
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        flashMessage('error', __('login_to_solve'));
        header('Location: login.php');
        exit();
    }
    
    if ($_POST['action'] === 'create_team') {
        // التحقق من تفعيل إنشاء الفرق
        if (getSetting('team_creation_enabled', '1') !== '1') {
            flashMessage('error', 'إنشاء الفرق مغلق حالياً');
            header('Location: teams.php');
            exit();
        }
        
        $team_name = sanitize($_POST['team_name'] ?? '');
        $team_description = sanitize($_POST['team_description'] ?? '');
        
        if (empty($team_name)) {
            flashMessage('error', 'الرجاء إدخال اسم الفريق');
        } elseif ($user['team_id']) {
            flashMessage('error', __('already_in_team'));
        } else {
            // التحقق من عدم وجود الفريق
            $stmt = $pdo->prepare("SELECT id FROM teams WHERE name = ?");
            $stmt->execute([$team_name]);
            if ($stmt->fetch()) {
                flashMessage('error', 'اسم الفريق مستخدم مسبقاً');
            } else {
                // توليد رمز دعوة
                $invite_code = strtoupper(bin2hex(random_bytes(4)));
                $max_members = intval(getSetting('max_team_members', '5'));
                
                // إنشاء الفريق
                $stmt = $pdo->prepare("INSERT INTO teams (name, description, captain_id, invite_code, max_members) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$team_name, $team_description, $user['id'], $invite_code, $max_members]);
                $team_id = $pdo->lastInsertId();
                
                // تحديث المستخدم
                $stmt = $pdo->prepare("UPDATE users SET team_id = ? WHERE id = ?");
                $stmt->execute([$team_id, $user['id']]);
                
                logActivity('create_team', "Created team: $team_name");
                flashMessage('success', __('team_created') . ' - ' . __('invite_code') . ': ' . $invite_code);
            }
        }
        header('Location: teams.php');
        exit();
    }
    
    if ($_POST['action'] === 'join_team') {
        $invite_code = sanitize($_POST['invite_code'] ?? '');
        
        if ($user['team_id']) {
            flashMessage('error', __('already_in_team'));
        } elseif (empty($invite_code)) {
            flashMessage('error', __('enter_invite_code'));
        } else {
            // البحث عن الفريق برمز الدعوة
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE invite_code = ? AND is_active = 1");
            $stmt->execute([$invite_code]);
            $team = $stmt->fetch();
            
            if (!$team) {
                flashMessage('error', __('invalid_invite_code'));
            } else {
                // التحقق من عدد الأعضاء
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE team_id = ?");
                $stmt->execute([$team['id']]);
                $member_count = $stmt->fetchColumn();
                
                if ($member_count >= $team['max_members']) {
                    flashMessage('error', __('team_full'));
                } else {
                    // الانضمام للفريق
                    $stmt = $pdo->prepare("UPDATE users SET team_id = ? WHERE id = ?");
                    $stmt->execute([$team['id'], $user['id']]);
                    
                    logActivity('join_team', "Joined team: " . $team['name']);
                    flashMessage('success', __('team_joined'));
                }
            }
        }
        header('Location: teams.php');
        exit();
    }
    
    if ($_POST['action'] === 'leave_team' && $user['team_id']) {
        $team_id = $user['team_id'];
        
        // التحقق إذا كان القائد
        $stmt = $pdo->prepare("SELECT captain_id FROM teams WHERE id = ?");
        $stmt->execute([$team_id]);
        $team = $stmt->fetch();
        
        // مغادرة الفريق
        $stmt = $pdo->prepare("UPDATE users SET team_id = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // إذا كان القائد، نقل القيادة أو حذف الفريق
        if ($team && $team['captain_id'] == $user['id']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE team_id = ? LIMIT 1");
            $stmt->execute([$team_id]);
            $new_captain = $stmt->fetch();
            
            if ($new_captain) {
                $stmt = $pdo->prepare("UPDATE teams SET captain_id = ? WHERE id = ?");
                $stmt->execute([$new_captain['id'], $team_id]);
            } else {
                // حذف الفريق إذا لم يبق أعضاء
                $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
                $stmt->execute([$team_id]);
            }
        }
        
        logActivity('leave_team', "Left team ID: $team_id");
        flashMessage('success', __('team_left'));
        header('Location: teams.php');
        exit();
    }
}

// جلب الفرق
$stmt = $pdo->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM users WHERE team_id = t.id) as member_count,
           (SELECT username FROM users WHERE id = t.captain_id) as captain_name,
           (SELECT COUNT(*) FROM solves s JOIN users u ON s.user_id = u.id WHERE u.team_id = t.id) as solve_count
    FROM teams t
    WHERE t.is_active = 1
    ORDER BY t.score DESC
");
$teams = $stmt->fetchAll();

// تحديث معلومات المستخدم بعد العمليات
if (isLoggedIn()) {
    $user = getCurrentUser();
}

$pageTitle = __('teams');
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">👥 <span><?php echo __('teams'); ?></span></h1>
            <p class="page-description"><?php echo __('teams_description'); ?></p>
        </div>
        
        <?php if (isLoggedIn() && !$user['team_id']): ?>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-neon" onclick="openModal('createTeamModal')">
                    ➕ <?php echo __('create_team'); ?>
                </button>
                <button class="btn btn-outline" onclick="openModal('joinTeamModal')">
                    🔑 <?php echo __('join_team'); ?>
                </button>
            </div>
        <?php elseif (isLoggedIn() && $user['team_id']): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="leave_team">
                <button type="submit" class="btn btn-outline" onclick="return confirm('<?php echo __('confirm_delete'); ?>')">
                    🚪 <?php echo __('leave_team'); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="filters-section">
        <input 
            type="text" 
            id="searchInput" 
            class="form-input search-input" 
            placeholder="🔍 <?php echo __('search_teams'); ?>"
            onkeyup="searchTeams()"
        >
    </div>
    
    <div class="teams-grid">
        <?php foreach ($teams as $team): ?>
            <div class="team-card" data-name="<?php echo strtolower($team['name']); ?>">
                <div class="card-header">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="team-avatar">
                            <?php echo strtoupper(mb_substr($team['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="team-name"><?php echo sanitize($team['name']); ?></h3>
                            <div style="color: var(--text-muted); font-size: 0.8rem;">
                                👑 <?php echo sanitize($team['captain_name'] ?? 'Unknown'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="card-description">
                    <?php echo $team['description'] ? sanitize(mb_substr($team['description'], 0, 100)) . '...' : __('none'); ?>
                </p>
                
                <div class="team-stats">
                    <div class="team-stat">
                        <div class="team-stat-value"><?php echo number_format($team['score']); ?></div>
                        <div class="team-stat-label"><?php echo __('score'); ?></div>
                    </div>
                    <div class="team-stat">
                        <div class="team-stat-value" style="color: var(--neon-cyan);"><?php echo $team['member_count']; ?>/<?php echo $team['max_members']; ?></div>
                        <div class="team-stat-label"><?php echo __('members'); ?></div>
                    </div>
                    <div class="team-stat">
                        <div class="team-stat-value" style="color: var(--neon-purple);"><?php echo $team['solve_count']; ?></div>
                        <div class="team-stat-label"><?php echo __('solves'); ?></div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <?php if ($team['is_open'] && $team['member_count'] < $team['max_members']): ?>
                        <span class="badge badge-easy"><?php echo __('active'); ?></span>
                    <?php else: ?>
                        <span class="badge badge-hard"><?php echo __('inactive'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($teams)): ?>
        <div class="empty-state">
            <div class="empty-icon">👥</div>
            <h3><?php echo __('no_teams_yet'); ?></h3>
            <p><?php echo __('check_back_later'); ?></p>
        </div>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="createTeamModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo __('create_team'); ?></h3>
            <button class="modal-close" onclick="closeModal('createTeamModal')">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create_team">
            
            <div class="form-group">
                <label class="form-label"><?php echo __('team_name'); ?></label>
                <input type="text" name="team_name" class="form-input" placeholder="CyberNinjas" required maxlength="100">
            </div>
            
            <div class="form-group">
                <label class="form-label"><?php echo __('team_description'); ?></label>
                <textarea name="team_description" class="form-input" rows="3" placeholder="<?php echo __('description'); ?>..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-cyber btn-block">
                <?php echo __('create_team'); ?>
            </button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="joinTeamModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo __('join_team'); ?></h3>
            <button class="modal-close" onclick="closeModal('joinTeamModal')">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="join_team">
            
            <div class="form-group">
                <label class="form-label"><?php echo __('invite_code'); ?></label>
                <input type="text" name="invite_code" class="form-input" placeholder="XXXXXXXX" required maxlength="20" style="text-transform: uppercase;">
            </div>
            
            <button type="submit" class="btn btn-neon btn-block">
                <?php echo __('join_team'); ?>
            </button>
        </form>
    </div>
</div>

<script>
function searchTeams() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.team-card');
    
    cards.forEach(card => {
        const name = card.dataset.name;
        card.style.display = name.includes(query) ? '' : 'none';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
