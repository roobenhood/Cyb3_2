<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

$lang = getCurrentLanguage();
$view = isset($_GET['view']) && $_GET['view'] === 'teams' ? 'teams' : 'players';
$currentUser = getCurrentUser();

// التحقق من إعدادات إخفاء الأسماء
$hideUsernames = getSetting('hide_usernames_from_users', '0') === '1' && getSetting('hide_usernames_scoreboard', '0') === '1';
$isUserAdmin = $currentUser && in_array($currentUser['role'], ['admin', 'super_admin']);

// التحقق من تجميد لوحة النتائج
$scoreboard_frozen = getSetting('scoreboard_frozen', '0') === '1';
$frozen_at = getSetting('scoreboard_frozen_at', '');
$freeze_time = getSetting('scoreboard_freeze_time', '');

// إذا كانت لوحة النتائج مجمدة، استخدم وقت التجميد كحد أقصى
$freeze_condition = "";
if ($scoreboard_frozen && $frozen_at) {
    $freeze_condition = " AND s.solved_at <= '$frozen_at'";
}

// جلب ترتيب اللاعبين
if ($scoreboard_frozen && $frozen_at) {
    // حساب النقاط حتى وقت التجميد فقط
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.avatar, u.created_at, t.name as team_name,
               COALESCE(SUM(CASE WHEN s.solved_at <= ? THEN s.points_earned ELSE 0 END), 0) as frozen_score,
               (SELECT COUNT(*) FROM solves WHERE user_id = u.id AND solved_at <= ?) as solve_count
        FROM users u
        LEFT JOIN teams t ON u.team_id = t.id
        LEFT JOIN solves s ON u.id = s.user_id
        WHERE u.is_active = 1
        GROUP BY u.id
        ORDER BY frozen_score DESC, u.created_at ASC
        LIMIT 100
    ");
    $stmt->execute([$frozen_at, $frozen_at]);
    $players = $stmt->fetchAll();
    // إعادة تسمية frozen_score إلى score
    foreach ($players as &$player) {
        $player['score'] = $player['frozen_score'];
    }
} else {
    $stmt = $pdo->query("
        SELECT u.*, t.name as team_name, 
               (SELECT COUNT(*) FROM solves WHERE user_id = u.id) as solve_count
        FROM users u
        LEFT JOIN teams t ON u.team_id = t.id
        WHERE u.is_active = 1
        ORDER BY u.score DESC, u.created_at ASC
        LIMIT 100
    ");
    $players = $stmt->fetchAll();
}

// جلب ترتيب الفرق
if ($scoreboard_frozen && $frozen_at) {
    $stmt = $pdo->prepare("
        SELECT t.id, t.name, t.max_members, t.created_at,
               (SELECT COUNT(*) FROM users WHERE team_id = t.id) as member_count,
               COALESCE(SUM(CASE WHEN s.solved_at <= ? THEN s.points_earned ELSE 0 END), 0) as frozen_score,
               (SELECT COUNT(*) FROM solves s2 JOIN users u2 ON s2.user_id = u2.id WHERE u2.team_id = t.id AND s2.solved_at <= ?) as solve_count
        FROM teams t
        LEFT JOIN users u ON u.team_id = t.id
        LEFT JOIN solves s ON s.user_id = u.id
        WHERE t.is_active = 1
        GROUP BY t.id
        ORDER BY frozen_score DESC, t.created_at ASC
        LIMIT 50
    ");
    $stmt->execute([$frozen_at, $frozen_at]);
    $teams = $stmt->fetchAll();
    foreach ($teams as &$team) {
        $team['score'] = $team['frozen_score'];
    }
} else {
    $stmt = $pdo->query("
        SELECT t.*, 
               (SELECT COUNT(*) FROM users WHERE team_id = t.id) as member_count,
               (SELECT COUNT(*) FROM solves s JOIN users u ON s.user_id = u.id WHERE u.team_id = t.id) as solve_count
        FROM teams t
        WHERE t.is_active = 1
        ORDER BY t.score DESC, t.created_at ASC
        LIMIT 50
    ");
    $teams = $stmt->fetchAll();
}

// إعدادات المسابقة
$competition_enabled = getSetting('competition_enabled', '0') === '1';
$competition_start = getSetting('competition_start', '');
$competition_end = getSetting('competition_end', '');

$pageTitle = __('scoreboard');
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header page-header--center-desc">
        <h1 class="page-title">🏆 <span><?php echo __('scoreboard_title'); ?></span></h1>
        <p class="page-description"><?php echo __('teams_description'); ?></p>
    </div>
    
    <?php if ($scoreboard_frozen): ?>
        <div class="alert alert-warning">
            ❄️ <?php echo __('scoreboard_frozen_notice'); ?>
            <?php if ($frozen_at): ?>
                <br><small><?php echo __('frozen_at'); ?>: <?php echo date('Y-m-d H:i', strtotime($frozen_at)); ?></small>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($competition_enabled): ?>
        <div class="competition-timer card" id="competitionTimer">
            <div class="timer-content">
                <?php if ($competition_start && strtotime($competition_start) > time()): ?>
                    <h3>⏳ <?php echo __('competition_starts_in'); ?></h3>
                    <div class="countdown" data-target="<?php echo $competition_start; ?>">
                        <div class="countdown-item"><span id="days">00</span><label><?php echo __('days'); ?></label></div>
                        <div class="countdown-item"><span id="hours">00</span><label><?php echo __('hours'); ?></label></div>
                        <div class="countdown-item"><span id="minutes">00</span><label><?php echo __('minutes'); ?></label></div>
                        <div class="countdown-item"><span id="seconds">00</span><label><?php echo __('seconds'); ?></label></div>
                    </div>
                <?php elseif ($competition_end && strtotime($competition_end) > time()): ?>
                    <h3>⏱️ <?php echo __('competition_ends_in'); ?></h3>
                    <div class="countdown" data-target="<?php echo $competition_end; ?>">
                        <div class="countdown-item"><span id="days">00</span><label><?php echo __('days'); ?></label></div>
                        <div class="countdown-item"><span id="hours">00</span><label><?php echo __('hours'); ?></label></div>
                        <div class="countdown-item"><span id="minutes">00</span><label><?php echo __('minutes'); ?></label></div>
                        <div class="countdown-item"><span id="seconds">00</span><label><?php echo __('seconds'); ?></label></div>
                    </div>
                <?php elseif ($competition_end && strtotime($competition_end) <= time()): ?>
                    <h3>🏁 <?php echo __('competition_ended'); ?></h3>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="scoreboard-tabs">
        <a href="?view=players" class="btn <?php echo $view === 'players' ? 'btn-neon' : 'btn-outline'; ?>">
            👤 <?php echo __('players'); ?>
        </a>
        <a href="?view=teams" class="btn <?php echo $view === 'teams' ? 'btn-neon' : 'btn-outline'; ?>">
            👥 <?php echo __('teams'); ?>
        </a>
    </div>
    
    <?php if ($view === 'players'): ?>
        <div class="card">
            <div class="scoreboard-header">
                <div><?php echo __('rank'); ?></div>
                <div><?php echo __('player'); ?></div>
                <div><?php echo __('team'); ?></div>
                <div><?php echo __('solves'); ?></div>
                <div><?php echo __('score'); ?></div>
            </div>
            
            <?php $rank = 1; foreach ($players as $player): ?>
                <div class="scoreboard-row <?php echo $rank <= 3 ? 'rank-' . $rank : ''; ?>">
                    <div>
                        <?php if ($rank === 1): ?>
                            <span class="rank-badge gold">🥇</span>
                        <?php elseif ($rank === 2): ?>
                            <span class="rank-badge silver">🥈</span>
                        <?php elseif ($rank === 3): ?>
                            <span class="rank-badge bronze">🥉</span>
                        <?php else: ?>
                            <span class="rank-badge">#<?php echo $rank; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="player-info">
                        <div class="player-avatar">
                            <?php echo strtoupper(mb_substr($player['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <?php 
                            // تحديد الاسم المعروض
                            $displayName = $player['username'];
                            $isCurrentUser = $currentUser && $currentUser['id'] == $player['id'];
                            
                            if ($hideUsernames && !$isUserAdmin && !$isCurrentUser) {
                                $displayName = __('hidden_player') . ' #' . $rank;
                            }
                            ?>
                            <div class="player-name"><?php echo sanitize($displayName); ?></div>
                        </div>
                    </div>
                    <div class="player-team">
                        <?php echo $player['team_name'] ? sanitize($player['team_name']) : '-'; ?>
                    </div>
                    <div style="color: var(--neon-cyan);">
                        <?php echo $player['solve_count']; ?>
                    </div>
                    <div class="score-value">
                        <?php echo number_format($player['score']); ?>
                    </div>
                </div>
            <?php $rank++; endforeach; ?>
            
            <?php if (empty($players)): ?>
                <div class="empty-state">
                    <p><?php echo __('no_players_yet'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="scoreboard-header">
                <div><?php echo __('rank'); ?></div>
                <div><?php echo __('team'); ?></div>
                <div><?php echo __('members'); ?></div>
                <div><?php echo __('solves'); ?></div>
                <div><?php echo __('score'); ?></div>
            </div>
            
            <?php $rank = 1; foreach ($teams as $team): ?>
                <div class="scoreboard-row <?php echo $rank <= 3 ? 'rank-' . $rank : ''; ?>">
                    <div>
                        <?php if ($rank === 1): ?>
                            <span class="rank-badge gold">🥇</span>
                        <?php elseif ($rank === 2): ?>
                            <span class="rank-badge silver">🥈</span>
                        <?php elseif ($rank === 3): ?>
                            <span class="rank-badge bronze">🥉</span>
                        <?php else: ?>
                            <span class="rank-badge">#<?php echo $rank; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="player-info">
                        <div class="player-avatar" style="background: linear-gradient(135deg, var(--neon-purple), var(--neon-pink)); border-radius: 8px;">
                            <?php echo strtoupper(mb_substr($team['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="player-name"><?php echo sanitize($team['name']); ?></div>
                        </div>
                    </div>
                    <div style="color: var(--text-muted);">
                        <?php echo $team['member_count']; ?>/<?php echo $team['max_members']; ?>
                    </div>
                    <div style="color: var(--neon-cyan);">
                        <?php echo $team['solve_count']; ?>
                    </div>
                    <div class="score-value">
                        <?php echo number_format($team['score']); ?>
                    </div>
                </div>
            <?php $rank++; endforeach; ?>
            
            <?php if (empty($teams)): ?>
                <div class="empty-state">
                    <p><?php echo __('no_teams_ranked'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($competition_enabled && ($competition_start || $competition_end)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countdown = document.querySelector('.countdown');
    if (!countdown) return;
    
    const targetDate = new Date(countdown.dataset.target).getTime();
    
    const timer = setInterval(function() {
        const now = new Date().getTime();
        const distance = targetDate - now;
        
        if (distance < 0) {
            clearInterval(timer);
            location.reload();
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        document.getElementById('days').textContent = String(days).padStart(2, '0');
        document.getElementById('hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
    }, 1000);
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>