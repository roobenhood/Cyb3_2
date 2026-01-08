<?php

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/core/Core.php';
require_once BASE_PATH . '/controllers/Controller.php';

Security::setSecurityHeaders();
Session::start();

$courseController = new CourseController();
$courses = $courseController->getPublishedCourses();
$enrolledCourseIds = [];
$isLoggedIn = Session::isLoggedIn();

if ($isLoggedIn) {
    $userId = Session::getUserId();
    foreach ($courses as $course) {
        if ($courseController->isEnrolled($userId, $course['id'])) {
            $enrolledCourseIds[$course['id']] = true;
        }
    }
}


ob_start();
?>
<div class="page-header">
    <h1>📚 الكورسات المتاحة</h1>
    <p>اختر من بين مجموعة متنوعة من الكورسات التعليمية</p>
</div>

<?php if (empty($courses)): ?>
    <div class="empty-state">
        <p>لا توجد كورسات متاحة حالياً</p>
    </div>
<?php else: ?>
    <div class="courses-grid">
        <?php foreach ($courses as $course): ?>
            <div class="course-card">
                <div class="course-header">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <span class="course-instructor">👨‍🏫 <?php echo htmlspecialchars($course['instructor']); ?></span>
                </div>

                <div class="course-body">
                    <p class="course-description">
                        <?php echo htmlspecialchars(mb_substr($course['description'], 0, 150)); ?>
                        <?php echo mb_strlen($course['description']) > 150 ? '...' : ''; ?>
                    </p>

                    <div class="course-meta">
                        <span>⏱️ <?php echo (int)$course['duration']; ?> ساعة</span>
                    </div>
                </div>

                <div class="course-footer">
                    <?php if ($isLoggedIn): ?>
                        <?php if (isset($enrolledCourseIds[$course['id']])): ?>
                            <span class="badge badge-success">✅ مسجل</span>
                            <form action="courses.php?action=unenroll" method="POST" class="inline-form">
                                <?php echo Session::csrfField(); ?>
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">إلغاء التسجيل</button>
                            </form>
                        <?php else: ?>
                            <form action="courses.php?action=enroll" method="POST" class="inline-form">
                                <?php echo Session::csrfField(); ?>
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" class="btn btn-primary">التسجيل في الكورس</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="auth.php?action=login" class="btn btn-primary">سجل دخول للتسجيل</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();

$pageTitle = SITE_NAME;
$currentPage = 'home';
include BASE_PATH . '/templates/layout.php';
