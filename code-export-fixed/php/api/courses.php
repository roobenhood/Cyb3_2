<?php
/**
 * Courses API Endpoints
 * نقاط نهاية API للدورات
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
require_once __DIR__ . '/../utils/FileUpload.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Enrollment.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getCourses();
        break;
    case 'featured':
        getFeaturedCourses();
        break;
    case 'get':
        getCourse();
        break;
    case 'create':
        createCourse();
        break;
    case 'update':
        updateCourse();
        break;
    case 'delete':
        deleteCourse();
        break;
    case 'lessons':
        getCourseLessons();
        break;
    case 'reviews':
        getCourseReviews();
        break;
    case 'enroll':
        enrollCourse();
        break;
    case 'my-courses':
        getMyCourses();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

function getCourses() {
    $filters = [
        'page' => (int)($_GET['page'] ?? 1),
        'per_page' => (int)($_GET['per_page'] ?? DEFAULT_PAGE_SIZE),
        'category_id' => $_GET['category_id'] ?? null,
        'level' => $_GET['level'] ?? null,
        'search' => $_GET['search'] ?? null,
        'sort' => $_GET['sort'] ?? null,
        'min_price' => $_GET['min_price'] ?? null,
        'max_price' => $_GET['max_price'] ?? null
    ];

    $course = new Course();
    $result = $course->getAll($filters);

    Response::paginated($result['data'], $result['total'], $result['page'], $result['per_page']);
}

function getFeaturedCourses() {
    $limit = (int)($_GET['limit'] ?? 6);
    $course = new Course();
    $courses = $course->getFeatured($limit);

    Response::success($courses);
}

function getCourse() {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $course = new Course();
    $courseData = $course->findById($id);

    if (!$courseData) {
        Response::notFound('الدورة غير موجودة');
    }

    // Get lessons
    $courseData['lessons'] = $course->getLessons($id);

    // Check if user is enrolled
    $userId = Auth::getCurrentUserId();
    if ($userId) {
        $enrollment = new Enrollment();
        $courseData['is_enrolled'] = $enrollment->isEnrolled($userId, $id);
        if ($courseData['is_enrolled']) {
            $courseData['progress'] = $enrollment->getProgress($userId, $id);
        }
    } else {
        $courseData['is_enrolled'] = false;
    }

    Response::success($courseData);
}

function createCourse() {
    $userId = Auth::requireAuth();

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'title' => 'required|min:3|max:255',
        'description' => 'required|min:10',
        'category_id' => 'required|integer',
        'price' => 'required|numeric',
        'level' => 'required|in:beginner,intermediate,advanced'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $course = new Course();
    $course->title = Validator::sanitize($data['title']);
    $course->description = Validator::sanitize($data['description'], 'html');
    $course->instructor_id = $userId;
    $course->category_id = (int)$data['category_id'];
    $course->price = (float)$data['price'];
    $course->discount_price = isset($data['discount_price']) ? (float)$data['discount_price'] : null;
    $course->level = $data['level'];
    $course->duration = $data['duration'] ?? 0;
    $course->is_featured = $data['is_featured'] ?? 0;
    $course->is_published = $data['is_published'] ?? 0;
    $course->thumbnail = $data['thumbnail'] ?? null;

    if ($course->create()) {
        Response::success(['id' => $course->id], 'تم إنشاء الدورة بنجاح', 201);
    } else {
        Response::serverError('فشل إنشاء الدورة');
    }
}

function updateCourse() {
    $userId = Auth::requireAuth();
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $course = new Course();
    $existingCourse = $course->findById($id);

    if (!$existingCourse) {
        Response::notFound('الدورة غير موجودة');
    }

    // Check ownership
    if ($existingCourse['instructor_id'] != $userId) {
        Response::forbidden('لا يمكنك تعديل هذه الدورة');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'title' => 'min:3|max:255',
        'description' => 'min:10',
        'price' => 'numeric',
        'level' => 'in:beginner,intermediate,advanced'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $updateData = [];
    if (isset($data['title'])) $updateData['title'] = Validator::sanitize($data['title']);
    if (isset($data['description'])) $updateData['description'] = Validator::sanitize($data['description'], 'html');
    if (isset($data['category_id'])) $updateData['category_id'] = (int)$data['category_id'];
    if (isset($data['price'])) $updateData['price'] = (float)$data['price'];
    if (isset($data['discount_price'])) $updateData['discount_price'] = (float)$data['discount_price'];
    if (isset($data['level'])) $updateData['level'] = $data['level'];
    if (isset($data['duration'])) $updateData['duration'] = (int)$data['duration'];
    if (isset($data['is_featured'])) $updateData['is_featured'] = (int)$data['is_featured'];
    if (isset($data['is_published'])) $updateData['is_published'] = (int)$data['is_published'];
    if (isset($data['thumbnail'])) $updateData['thumbnail'] = $data['thumbnail'];

    if ($course->update($id, $updateData)) {
        Response::success(null, 'تم تحديث الدورة بنجاح');
    } else {
        Response::serverError('فشل تحديث الدورة');
    }
}

function deleteCourse() {
    $userId = Auth::requireAuth();
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $course = new Course();
    $existingCourse = $course->findById($id);

    if (!$existingCourse) {
        Response::notFound('الدورة غير موجودة');
    }

    if ($existingCourse['instructor_id'] != $userId) {
        Response::forbidden('لا يمكنك حذف هذه الدورة');
    }

    if ($course->delete($id)) {
        Response::success(null, 'تم حذف الدورة بنجاح');
    } else {
        Response::serverError('فشل حذف الدورة');
    }
}

function getCourseLessons() {
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $course = new Course();
    $lessons = $course->getLessons($id);

    // Check if user is enrolled to show full content
    $userId = Auth::getCurrentUserId();
    $enrollment = new Enrollment();
    $isEnrolled = $userId ? $enrollment->isEnrolled($userId, $id) : false;

    // Hide video URLs for non-enrolled users (except free lessons)
    if (!$isEnrolled) {
        foreach ($lessons as &$lesson) {
            if (!$lesson['is_free']) {
                $lesson['video_url'] = null;
            }
        }
    }

    Response::success($lessons);
}

function getCourseReviews() {
    $id = (int)($_GET['id'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    if (!$id) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $course = new Course();
    $reviews = $course->getReviews($id, $page, $perPage);

    Response::success($reviews);
}

function enrollCourse() {
    $userId = Auth::requireAuth();
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $enrollment = new Enrollment();

    if ($enrollment->isEnrolled($userId, $id)) {
        Response::error('أنت مسجل بالفعل في هذه الدورة', [], 400);
    }

    if ($enrollment->create($userId, $id)) {
        Response::success(null, 'تم التسجيل في الدورة بنجاح', 201);
    } else {
        Response::serverError('فشل التسجيل في الدورة');
    }
}

function getMyCourses() {
    $userId = Auth::requireAuth();

    $enrollment = new Enrollment();
    $courses = $enrollment->getByUser($userId);

    Response::success($courses);
}
