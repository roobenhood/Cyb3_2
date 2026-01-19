<?php
require_once 'config.php';
requireLogin();

$lang = getCurrentLanguage();
$t = loadLanguage($lang);
$user = getCurrentUser();
$name_col = 'name_' . $lang;

if (!$user) {
    header('Location: logout.php');
    exit();
}

// جلب الفريق
$team = null;
if ($user['team_id']) {
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$user['team_id']]);
    $team = $stmt->fetch();
}

// جلب الحلول
$stmt = $pdo->prepare("
    SELECT s.*, 
           CASE WHEN ? = 'ar' THEN c.name_ar ELSE c.name_en END as challenge_name,
           c.points, 
           c.difficulty, 
           CASE WHEN ? = 'ar' THEN cat.name_ar ELSE cat.name_en END as category_name
    FROM solves s
    JOIN challenges c ON s.challenge_id = c.id
    JOIN categories cat ON c.category_id = cat.id
    WHERE s.user_id = ?
    ORDER BY s.solved_at DESC
    LIMIT 20
");
$stmt->execute([$lang, $lang, $user['id']]);
$solves = $stmt->fetchAll();

// إحصائيات عامة
$stmt = $pdo->prepare("SELECT COUNT(*) FROM solves WHERE user_id = ?");
$stmt->execute([$user['id']]);
$solve_count = $stmt->fetchColumn();

// الترتيب
$stmt = $pdo->prepare("SELECT COUNT(*) + 1 FROM users WHERE score > ? AND is_active = 1");
$stmt->execute([$user['score']]);
$rank = $stmt->fetchColumn();

// إجمالي التحديات
$stmt = $pdo->query("SELECT COUNT(*) FROM challenges WHERE is_active = 1");
$total_challenges = $stmt->fetchColumn();

// إحصائيات حسب الفئات
$stmt = $pdo->prepare("
    SELECT 
        cat.id,
        cat.$name_col as category_name,
        cat.color,
        cat.icon,
        COUNT(DISTINCT c.id) as total_in_category,
        COUNT(DISTINCT s.challenge_id) as solved_in_category,
        COALESCE(SUM(CASE WHEN s.id IS NOT NULL THEN c.points ELSE 0 END), 0) as points_earned
    FROM categories cat
    LEFT JOIN challenges c ON c.category_id = cat.id AND c.is_active = 1
    LEFT JOIN solves s ON s.challenge_id = c.id AND s.user_id = ?
    WHERE cat.is_active = 1
    GROUP BY cat.id, cat.$name_col, cat.color, cat.icon
    ORDER BY cat.sort_order, cat.id
");
$stmt->execute([$user['id']]);
$category_stats = $stmt->fetchAll();

// إحصائيات حسب الصعوبة
$stmt = $pdo->prepare("
    SELECT 
        c.difficulty,
        COUNT(DISTINCT c.id) as total,
        COUNT(DISTINCT s.challenge_id) as solved
    FROM challenges c
    LEFT JOIN solves s ON s.challenge_id = c.id AND s.user_id = ?
    WHERE c.is_active = 1
    GROUP BY c.difficulty
    ORDER BY FIELD(c.difficulty, 'easy', 'medium', 'hard', 'insane')
");
$stmt->execute([$user['id']]);
$difficulty_stats = $stmt->fetchAll();

// إحصائيات الأسبوع الماضي
$stmt = $pdo->prepare("
    SELECT COUNT(*) as weekly_solves, COALESCE(SUM(c.points), 0) as weekly_points
    FROM solves s
    JOIN challenges c ON s.challenge_id = c.id
    WHERE s.user_id = ? AND s.solved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmt->execute([$user['id']]);
$weekly_stats = $stmt->fetch();

// نشاط الحلول خلال الـ 30 يوم الماضية (للرسم البياني)
$stmt = $pdo->prepare("
    SELECT DATE(solved_at) as solve_date, COUNT(*) as count, SUM(c.points) as points
    FROM solves s
    JOIN challenges c ON s.challenge_id = c.id
    WHERE s.user_id = ? AND s.solved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(solved_at)
    ORDER BY solve_date
");
$stmt->execute([$user['id']]);
$activity_data = $stmt->fetchAll();

// First Blood count
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM solves s
    WHERE s.user_id = ? 
    AND s.id = (SELECT MIN(id) FROM solves WHERE challenge_id = s.challenge_id)
");
$stmt->execute([$user['id']]);
$first_blood_count = $stmt->fetchColumn();

$pageTitle = __('profile');
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">👤 <span><?php echo __('my_profile'); ?></span></h1>
    </div>
    
    <div class="grid grid-2" style="gap: 30px; margin-bottom: 30px;">
        <div class="card">
            <div class="profile-header" style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                <div class="profile-avatar">
                    <?php echo strtoupper(mb_substr($user['username'], 0, 1)); ?>
                </div>
                <div>
                    <h2 style="font-family: var(--font-display); font-size: 1.5rem; margin-bottom: 5px;">
                        <?php echo sanitize($user['username']); ?>
                    </h2>
                    <p style="color: var(--text-muted);"><?php echo sanitize($user['email']); ?></p>
                    <?php if ($team): ?>
                        <p style="color: var(--neon-purple); font-size: 0.9rem;">
                            👥 <?php echo sanitize($team['name']); ?>
                        </p>
                    <?php endif; ?>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">
                        <?php echo __('joined'); ?>: <?php echo date('Y/m/d', strtotime($user['created_at'])); ?>
                    </p>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="profile-stat-card">
                    <div class="profile-stat-value" style="color: var(--neon-green);">
                        <?php echo number_format($user['score']); ?>
                    </div>
                    <div class="profile-stat-label"><?php echo __('score'); ?></div>
                </div>
                <div class="profile-stat-card">
                    <div class="profile-stat-value" style="color: var(--neon-cyan);">
                        <?php echo $solve_count; ?>
                    </div>
                    <div class="profile-stat-label"><?php echo __('solves'); ?></div>
                </div>
                <div class="profile-stat-card">
                    <div class="profile-stat-value" style="color: var(--neon-purple);">
                        #<?php echo $rank; ?>
                    </div>
                    <div class="profile-stat-label"><?php echo __('rank'); ?></div>
                </div>
                <div class="profile-stat-card">
                    <div class="profile-stat-value" style="color: var(--neon-pink);">
                        <?php echo $first_blood_count; ?>
                    </div>
                    <div class="profile-stat-label"><?php echo __('first_bloods'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3 class="card-title">📊 <?php echo __('weekly_progress'); ?></h3>
            
            <div class="weekly-stats" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div class="stat-box" style="background: linear-gradient(135deg, rgba(0,255,136,0.1), rgba(0,212,255,0.1)); padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--neon-green);">
                        <?php echo $weekly_stats['weekly_solves'] ?? 0; ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;"><?php echo __('solves_this_week'); ?></div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, rgba(168,85,247,0.1), rgba(236,72,153,0.1)); padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--neon-purple);">
                        +<?php echo number_format($weekly_stats['weekly_points'] ?? 0); ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;"><?php echo __('points_this_week'); ?></div>
                </div>
            </div>
            
            <!-- Overall Progress Bar -->
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--text-muted);"><?php echo __('overall_progress'); ?></span>
                    <span style="color: var(--neon-green); font-weight: bold;">
                        <?php echo $solve_count; ?>/<?php echo $total_challenges; ?> 
                        (<?php echo $total_challenges > 0 ? round(($solve_count / $total_challenges) * 100) : 0; ?>%)
                    </span>
                </div>
                <div class="progress-bar-container" style="background: var(--bg-secondary); border-radius: 10px; height: 12px; overflow: hidden;">
                    <div class="progress-bar-fill" style="
                        width: <?php echo $total_challenges > 0 ? ($solve_count / $total_challenges) * 100 : 0; ?>%;
                        height: 100%;
                        background: linear-gradient(90deg, var(--neon-green), var(--neon-cyan));
                        border-radius: 10px;
                        transition: width 0.5s ease;
                    "></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card" style="margin-bottom: 30px;">
        
        <div class="category-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($category_stats as $cat): ?>
                <?php 
                $percentage = $cat['total_in_category'] > 0 ? round(($cat['solved_in_category'] / $cat['total_in_category']) * 100) : 0;
                ?>
                <div class="category-stat-card" style="
                    background: var(--bg-secondary);
                    border: 1px solid <?php echo $cat['color']; ?>30;
                    border-radius: 12px;
                    padding: 20px;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: <?php echo $cat['color']; ?>;"></div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 1.5rem;"><?php echo $cat['icon'] ?: '🏴'; ?></span>
                            <span style="font-weight: 600; color: <?php echo $cat['color']; ?>;"><?php echo $cat['category_name']; ?></span>
                        </div>
                        <span class="badge" style="background: <?php echo $cat['color']; ?>20; color: <?php echo $cat['color']; ?>; border-color: <?php echo $cat['color']; ?>50;">
                            <?php echo $cat['solved_in_category']; ?>/<?php echo $cat['total_in_category']; ?>
                        </span>
                    </div>
                    
                    <div class="progress-bar-container" style="background: var(--bg-primary); border-radius: 8px; height: 8px; overflow: hidden; margin-bottom: 10px;">
                        <div class="progress-bar-fill" style="
                            width: <?php echo $percentage; ?>%;
                            height: 100%;
                            background: <?php echo $cat['color']; ?>;
                            border-radius: 8px;
                            transition: width 0.5s ease;
                        "></div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; color: var(--text-muted); font-size: 0.85rem;">
                        <span><?php echo $percentage; ?>% <?php echo __('completed'); ?></span>
                        <span>+<?php echo number_format($cat['points_earned']); ?> <?php echo __('pts'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="grid grid-2" style="gap: 30px; margin-bottom: 30px;">
        <div class="card">
            <h3 class="card-title">⚔️ <?php echo __('progress_by_difficulty'); ?></h3>
                <?php foreach ($difficulty_stats as $diff): ?>
                    <?php 
                    $percentage = $diff['total'] > 0 ? round(($diff['solved'] / $diff['total']) * 100) : 0;
                    $diffColors = [
                        'easy' => '#00ff88',
                        'medium' => '#f97316',
                        'hard' => '#ec4899',
                        'insane' => '#a855f7'
                    ];
                    $color = $diffColors[$diff['difficulty']] ?? '#00ff88';
                    ?>
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span class="badge badge-<?php echo $diff['difficulty']; ?>">
                                <?php echo __($diff['difficulty']); ?>
                            </span>
                            <span style="color: var(--text-muted);">
                                <?php echo $diff['solved']; ?>/<?php echo $diff['total']; ?>
                            </span>
                        </div>
                        <div class="progress-bar-container" style="background: var(--bg-secondary); border-radius: 8px; height: 10px; overflow: hidden;">
                            <div class="progress-bar-fill" style="
                                width: <?php echo $percentage; ?>%;
                                height: 100%;
                                background: <?php echo $color; ?>;
                                border-radius: 8px;
                                transition: width 0.5s ease;
                            "></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <h3 class="card-title">🚩 <?php echo __('recent_solves'); ?></h3>
            <?php if (empty($solves)): ?>
                <div class="empty-state" style="padding: 30px; text-align: center;">
                    <div class="empty-icon">🔒</div>
                    <p><?php echo __('no_solves_yet'); ?></p>
                </div>
            <?php else: ?>
                <div class="activity-list" style="max-height: 350px; overflow-y: auto;">
                    <?php foreach ($solves as $solve): ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <div style="font-weight: 500; margin-bottom: 4px;">
                                    <?php echo sanitize($solve['challenge_name']); ?>
                                </div>
                                <div class="card-badges">
                                    <span class="badge" style="font-size: 0.7rem; padding: 2px 8px;">
                                        <?php echo $solve['category_name']; ?>
                                    </span>
                                    <span class="badge badge-<?php echo $solve['difficulty']; ?>" style="font-size: 0.7rem; padding: 2px 8px;">
                                        <?php echo __($solve['difficulty']); ?>
                                    </span>
                                </div>
                            </div>
                            <div style="text-align: left;">
                                <div class="activity-points">+<?php echo $solve['points']; ?></div>
                                <div style="color: var(--text-muted); font-size: 0.75rem;">
                                    <?php echo date('Y/m/d', strtotime($solve['solved_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Activity Chart (Last 30 Days) -->
    <?php if (!empty($activity_data)): ?>
    <div class="card">
        <h3 class="card-title">📈 <?php echo __('activity_last_30_days'); ?></h3>
        
        <div class="activity-chart" style="display: flex; align-items: flex-end; gap: 4px; height: 150px; padding: 20px 0;">
            <?php 
            $max_count = max(array_column($activity_data, 'count'));
            $dates = [];
            for ($i = 29; $i >= 0; $i--) {
                $dates[date('Y-m-d', strtotime("-$i days"))] = ['count' => 0, 'points' => 0];
            }
            foreach ($activity_data as $day) {
                $dates[$day['solve_date']] = $day;
            }
            ?>
            <?php foreach ($dates as $date => $data): ?>
                <?php 
                $height = $max_count > 0 && $data['count'] > 0 ? max(10, ($data['count'] / $max_count) * 100) : 5;
                $hasData = $data['count'] > 0;
                ?>
                <div 
                    class="chart-bar" 
                    style="
                        flex: 1;
                        height: <?php echo $height; ?>%;
                        background: <?php echo $hasData ? 'linear-gradient(180deg, var(--neon-green), var(--neon-cyan))' : 'var(--bg-secondary)'; ?>;
                        border-radius: 4px 4px 0 0;
                        min-width: 8px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    "
                    title="<?php echo date('M d', strtotime($date)); ?>: <?php echo $data['count']; ?> <?php echo __('solves'); ?> (+<?php echo $data['points']; ?> pts)"
                ></div>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; justify-content: space-between; color: var(--text-muted); font-size: 0.8rem; padding-top: 10px; border-top: 1px solid var(--border-color);">
            <span><?php echo date('M d', strtotime('-29 days')); ?></span>
            <span><?php echo __('today'); ?></span>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.chart-bar:hover {
    opacity: 0.8;
    transform: scaleY(1.05);
}

.activity-list::-webkit-scrollbar {
    width: 6px;
}

.activity-list::-webkit-scrollbar-track {
    background: var(--bg-secondary);
    border-radius: 3px;
}

.activity-list::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
}

.activity-list::-webkit-scrollbar-thumb:hover {
    background: var(--neon-green);
}

@media (max-width: 768px) {
    .category-stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    .weekly-stats {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
