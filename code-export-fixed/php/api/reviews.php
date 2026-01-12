<?php
/**
 * Reviews API Endpoints
 * نقاط نهاية API للتقييمات
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
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Enrollment.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getReviews();
        break;
    case 'create':
        createReview();
        break;
    case 'update':
        updateReview();
        break;
    case 'delete':
        deleteReview();
        break;
    case 'stats':
        getReviewStats();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

function getReviews() {
    $courseId = (int)($_GET['course_id'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    if (!$courseId) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $review = new Review();
    $result = $review->getByCourse($courseId, $page, $perPage);

    Response::paginated($result['data'], $result['total'], $page, $perPage);
}

function createReview() {
    $userId = Auth::requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'course_id' => 'required|integer',
        'rating' => 'required|integer',
        'comment' => 'max:1000'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $courseId = (int)$data['course_id'];
    $rating = (int)$data['rating'];

    // Validate rating range
    if ($rating < 1 || $rating > 5) {
        Response::error('التقييم يجب أن يكون بين 1 و 5', [], 400);
    }

    // Check if user is enrolled
    $enrollment = new Enrollment();
    if (!$enrollment->isEnrolled($userId, $courseId)) {
        Response::error('يجب أن تكون مسجلاً في الدورة لتقييمها', [], 403);
    }

    $review = new Review();

    // Check if already reviewed
    if ($review->hasReviewed($userId, $courseId)) {
        Response::error('لقد قمت بتقييم هذه الدورة مسبقاً', [], 400);
    }

    $comment = isset($data['comment']) ? Validator::sanitize($data['comment'], 'html') : null;

    $id = $review->create($userId, $courseId, $rating, $comment);

    if ($id) {
        Response::success(['id' => $id], 'تم إضافة التقييم بنجاح', 201);
    } else {
        Response::serverError('فشل إضافة التقييم');
    }
}

function updateReview() {
    $userId = Auth::requireAuth();
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف التقييم مطلوب', [], 400);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $isValid = $validator->validate([
        'rating' => 'integer',
        'comment' => 'max:1000'
    ]);

    if (!$isValid) {
        Response::validationError($validator->getErrors());
    }

    $rating = (int)($data['rating'] ?? 0);

    if ($rating && ($rating < 1 || $rating > 5)) {
        Response::error('التقييم يجب أن يكون بين 1 و 5', [], 400);
    }

    $comment = isset($data['comment']) ? Validator::sanitize($data['comment'], 'html') : null;

    $review = new Review();

    if ($review->update($id, $userId, $rating, $comment)) {
        Response::success(null, 'تم تحديث التقييم بنجاح');
    } else {
        Response::serverError('فشل تحديث التقييم');
    }
}

function deleteReview() {
    $userId = Auth::requireAuth();
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف التقييم مطلوب', [], 400);
    }

    $review = new Review();

    if ($review->delete($id, $userId)) {
        Response::success(null, 'تم حذف التقييم بنجاح');
    } else {
        Response::serverError('فشل حذف التقييم');
    }
}

function getReviewStats() {
    $courseId = (int)($_GET['course_id'] ?? 0);

    if (!$courseId) {
        Response::error('معرف الدورة مطلوب', [], 400);
    }

    $review = new Review();
    $average = $review->getAverageRating($courseId);
    $distribution = $review->getRatingDistribution($courseId);

    Response::success([
        'average_rating' => round((float)$average['avg_rating'], 1),
        'total_reviews' => (int)$average['total_reviews'],
        'distribution' => $distribution
    ]);
}
