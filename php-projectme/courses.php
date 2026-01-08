<?php

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/core/Core.php';
require_once BASE_PATH . '/controllers/Controller.php';

Security::setSecurityHeaders();
Session::start();
Session::requireLogin();

$courseController = new CourseController();
$action = $_GET['action'] ?? 'list';


if ($action === 'enroll' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateRequest()) {
        Session::setFlash('error', 'طلب غير صالح. حاول مرة أخرى');
        header('Location: index.php');
        exit;
    }

    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if (!$courseId || $courseId <= 0) {
        Session::setFlash('error', 'كورس غير صالح');
        header('Location: index.php');
        exit;
    }

    $result = $courseController->enroll(Session::getUserId(), $courseId);
    Session::setFlash(
        $result['success'] ? 'success' : 'error',
        $result['success'] ? $result['message'] : $result['error']
    );

    header('Location: index.php');
    exit;
}

if ($action === 'unenroll' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateRequest()) {
        Session::setFlash('error', 'طلب غير صالح. حاول مرة أخرى');
        header('Location: index.php');
        exit;
    }

    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if (!$courseId || $courseId <= 0) {
        Session::setFlash('error', 'كورس غير صالح');
        header('Location: index.php');
        exit;
    }

    $result = $courseController->unenroll(Session::getUserId(), $courseId);
    Session::setFlash(
        $result['success'] ? 'success' : 'error',
        $result['success'] ? $result['message'] : $result['error']
    );

  
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    $isAllowed = strpos($referer, 'index.php') !== false || strpos($referer, 'courses.php') !== false;
    header('Location: ' . ($isAllowed ? $referer : 'index.php'));
    exit;
}

$courses = $courseController->getUserCourses(Session::getUserId());

ob_start();
?>
<div class="page-header">
    <h1>📖 كورساتي</h1>
    <p>الكورسات المسجل فيها</p>
</div>

<?php if (empty($courses)): ?>
    <div class="empty-state">
        <p>لم تسجل في أي كورس بعد</p>
        <a href="index.php" class="btn btn-primary">تصفح الكورسات</a>
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
                        <span>📅 مسجل منذ: <?php echo date('Y/m/d', strtotime($course['enrolled_at'])); ?></span>
                    </div>
                </div>

                <div class="course-footer">
                    <span class="badge badge-success">✅ مسجل</span>
                    <form action="courses.php?action=unenroll" method="POST" class="inline-form">
                        <?php echo Session::csrfField(); ?>
                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">إلغاء التسجيل</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = 'كورساتي';
$currentPage = 'my-courses';
include BASE_PATH . '/templates/layout.php';
