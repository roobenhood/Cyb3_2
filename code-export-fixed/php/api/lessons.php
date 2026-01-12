<?php
/**
 * Lessons API Endpoints
 * نقاط نهاية API للدروس
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../models/Lesson.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Enrollment.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getLessons();
        break;
    case 'get':
        getLesson();
        break;
    case 'create':
        createLesson();
        break;
    case 'update':
        updateLesson();
        break;
    case 'delete':
        deleteLesson();
        break;
    case 'reorder':
        reorderLessons();
        break;
    case 'complete':
        markComplete();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

function getLessons() {
    $courseId = (int)($_GET['course_id'] ?? 0);

    if (!$courseId) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $lesson = new Lesson();
    $lessons = $lesson->getByCourse($courseId);

    // Check if user is enrolled
    $userId = Auth::getCurrentUserId();
    $enrollment = new Enrollment();
    $isEnrolled = $userId ? $enrollment->isEnrolled($userId, $courseId) : false;

    // Hide video URLs for non-enrolled users (except free lessons)
    if (!$isEnrolled) {
        foreach ($lessons as &$l) {
            if (!$l['is_free']) {
                $l['video_url'] = null;
            }
        }
    }

    Response::success($lessons);
}

function getLesson() {
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الدرس مطلوب', [], 400);
    }

    $lesson = new Lesson();
    $lessonData = $lesson->findById($id);

    if (!$lessonData) {
        Response::notFound('الدرس غير موجود');
    }

    // Check access
    if (!$lessonData['is_free']) {
        $userId = Auth::getCurrentUserId();
        if (!$userId) {
            Response::unauthorized('يجب تسجيل الدخول لمشاهدة هذا الدرس');
        }

        $enrollment = new Enrollment();
        if (!$enrollment->isEnrolled($userId, $lessonData['course_id'])) {
            Response::forbidden('يجب التسجيل في الدورة لمشاهدة هذا الدرس');
        }
    }

    Response::success($lessonData);
}

function createLesson() {
    $userId = Auth::requireAuth();

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'course_id' => 'required|integer',
        'title' => 'required|min:3|max:255',
        'video_url' => 'url',
        'duration' => 'integer'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $courseId = (int)$data['course_id'];

    // Check course ownership
    $course = new Course();
    $courseData = $course->findById($courseId);

    if (!$courseData) {
        Response::notFound('الدورة غير موجودة');
    }

    if ($courseData['instructor_id'] != $userId) {
        Response::forbidden('لا يمكنك إضافة دروس لهذه الدورة');
    }

    $lesson = new Lesson();
    $orderNum = $lesson->getNextOrder($courseId);

    $lessonData = [
        'course_id' => $courseId,
        'title' => Validator::sanitize($data['title']),
        'description' => Validator::sanitize($data['description'] ?? '', 'html'),
        'video_url' => Validator::sanitize($data['video_url'] ?? '', 'url'),
        'duration' => (int)($data['duration'] ?? 0),
        'order_num' => $orderNum,
        'is_free' => (int)($data['is_free'] ?? 0)
    ];

    $id = $lesson->create($lessonData);

    if ($id) {
        Response::success(['id' => $id], 'تم إضافة الدرس بنجاح', 201);
    } else {
        Response::serverError('فشل إضافة الدرس');
    }
}

function updateLesson() {
    $userId = Auth::requireAuth();
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الدرس مطلوب', [], 400);
    }

    $lesson = new Lesson();
    $lessonData = $lesson->findById($id);

    if (!$lessonData) {
        Response::notFound('الدرس غير موجود');
    }

    // Check course ownership
    $course = new Course();
    $courseData = $course->findById($lessonData['course_id']);

    if ($courseData['instructor_id'] != $userId) {
        Response::forbidden('لا يمكنك تعديل هذا الدرس');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'title' => 'min:3|max:255',
        'video_url' => 'url',
        'duration' => 'integer'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $updateData = [];
    if (isset($data['title'])) $updateData['title'] = Validator::sanitize($data['title']);
    if (isset($data['description'])) $updateData['description'] = Validator::sanitize($data['description'], 'html');
    if (isset($data['video_url'])) $updateData['video_url'] = Validator::sanitize($data['video_url'], 'url');
    if (isset($data['duration'])) $updateData['duration'] = (int)$data['duration'];
    if (isset($data['is_free'])) $updateData['is_free'] = (int)$data['is_free'];

    if ($lesson->update($id, $updateData)) {
        Response::success(null, 'تم تحديث الدرس بنجاح');
    } else {
        Response::serverError('فشل تحديث الدرس');
    }
}

function deleteLesson() {
    $userId = Auth::requireAuth();
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الدرس مطلوب', [], 400);
    }

    $lesson = new Lesson();
    $lessonData = $lesson->findById($id);

    if (!$lessonData) {
        Response::notFound('الدرس غير موجود');
    }

    // Check course ownership
    $course = new Course();
    $courseData = $course->findById($lessonData['course_id']);

    if ($courseData['instructor_id'] != $userId) {
        Response::forbidden('لا يمكنك حذف هذا الدرس');
    }

    if ($lesson->delete($id)) {
        Response::success(null, 'تم حذف الدرس بنجاح');
    } else {
        Response::serverError('فشل حذف الدرس');
    }
}

function reorderLessons() {
    $userId = Auth::requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $courseId = (int)($data['course_id'] ?? 0);
    $orderedIds = $data['lesson_ids'] ?? [];

    if (!$courseId || empty($orderedIds)) {
        Response::error('البيانات غير صحيحة', [], 400);
    }

    // Check course ownership
    $course = new Course();
    $courseData = $course->findById($courseId);

    if (!$courseData || $courseData['instructor_id'] != $userId) {
        Response::forbidden('لا يمكنك تعديل ترتيب الدروس');
    }

    $lesson = new Lesson();

    if ($lesson->reorder($courseId, $orderedIds)) {
        Response::success(null, 'تم إعادة ترتيب الدروس بنجاح');
    } else {
        Response::serverError('فشل إعادة ترتيب الدروس');
    }
}

function markComplete() {
    $userId = Auth::requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $lessonId = (int)($data['lesson_id'] ?? 0);

    if (!$lessonId) {
        Response::error('معرف الدرس مطلوب', [], 400);
    }

    $lesson = new Lesson();
    $lessonData = $lesson->findById($lessonId);

    if (!$lessonData) {
        Response::notFound('الدرس غير موجود');
    }

    $courseId = $lessonData['course_id'];

    // Check enrollment
    $enrollment = new Enrollment();
    if (!$enrollment->isEnrolled($userId, $courseId)) {
        Response::forbidden('أنت غير مسجل في هذه الدورة');
    }

    if ($enrollment->markLessonComplete($userId, $courseId, $lessonId)) {
        $progress = $enrollment->getProgress($userId, $courseId);
        Response::success([
            'progress' => $progress['progress'],
            'is_completed' => (bool)$progress['is_completed']
        ], 'تم تسجيل إكمال الدرس');
    } else {
        Response::serverError('فشل تسجيل إكمال الدرس');
    }
}
