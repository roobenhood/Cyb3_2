<?php
/**
 * Addresses API Endpoints
 * نقاط نهاية API للعناوين - المتجر الإلكتروني
 */

header('Content-Type: application/json; charset=utf-8');
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
require_once __DIR__ . '/../models/Address.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getAddresses();
        break;
    case 'get':
        getAddress();
        break;
    case 'add':
        addAddress();
        break;
    case 'update':
        updateAddress();
        break;
    case 'delete':
        deleteAddress();
        break;
    case 'set-default':
        setDefaultAddress();
        break;
    default:
        Response::error('إجراء غير صالح', [], 400);
}

/**
 * Get user addresses
 */
function getAddresses() {
    $user = Auth::requireAuth();
    if (!$user) return;

    try {
        $addressModel = new Address();
        $addresses = $addressModel->getByUserId($user['id']);
        
        Response::success('تم جلب العناوين', $addresses);
    } catch (Exception $e) {
        Response::error('فشل في جلب العناوين', [], 500);
    }
}

/**
 * Get single address
 */
function getAddress() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        Response::error('معرف العنوان مطلوب', [], 400);
        return;
    }

    try {
        $addressModel = new Address();
        $address = $addressModel->getById($id);

        if (!$address || $address['user_id'] !== $user['id']) {
            Response::error('العنوان غير موجود', [], 404);
            return;
        }

        Response::success('تم جلب العنوان', $address);
    } catch (Exception $e) {
        Response::error('فشل في جلب العنوان', [], 500);
    }
}

/**
 * Add new address
 */
function addAddress() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator($data);
    $validator->required('name', 'الاسم مطلوب');
    $validator->required('phone', 'رقم الهاتف مطلوب');
    $validator->required('country', 'الدولة مطلوبة');
    $validator->required('city', 'المدينة مطلوبة');
    $validator->required('address_line1', 'العنوان مطلوب');

    if (!$validator->isValid()) {
        Response::error('بيانات غير صالحة', $validator->getErrors(), 422);
        return;
    }

    try {
        $addressModel = new Address();
        
        $addressData = [
            'user_id' => $user['id'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'city' => $data['city'],
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'is_default' => $data['is_default'] ?? false
        ];

        $addressId = $addressModel->create($addressData);

        // If this is the first address or marked as default, set it as default
        if ($addressData['is_default']) {
            $addressModel->setDefault($user['id'], $addressId);
        }

        $address = $addressModel->getById($addressId);

        Response::success('تم إضافة العنوان بنجاح', $address, [], 201);
    } catch (Exception $e) {
        Response::error('فشل في إضافة العنوان', [], 500);
    }
}

/**
 * Update address
 */
function updateAddress() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        Response::error('معرف العنوان مطلوب', [], 400);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    try {
        $addressModel = new Address();
        $address = $addressModel->getById($id);

        if (!$address || $address['user_id'] !== $user['id']) {
            Response::error('العنوان غير موجود', [], 404);
            return;
        }

        $addressModel->update($id, $data);

        if (isset($data['is_default']) && $data['is_default']) {
            $addressModel->setDefault($user['id'], $id);
        }

        $address = $addressModel->getById($id);

        Response::success('تم تحديث العنوان بنجاح', $address);
    } catch (Exception $e) {
        Response::error('فشل في تحديث العنوان', [], 500);
    }
}

/**
 * Delete address
 */
function deleteAddress() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        Response::error('معرف العنوان مطلوب', [], 400);
        return;
    }

    try {
        $addressModel = new Address();
        $address = $addressModel->getById($id);

        if (!$address || $address['user_id'] !== $user['id']) {
            Response::error('العنوان غير موجود', [], 404);
            return;
        }

        $addressModel->delete($id);

        Response::success('تم حذف العنوان بنجاح');
    } catch (Exception $e) {
        Response::error('فشل في حذف العنوان', [], 500);
    }
}

/**
 * Set address as default
 */
function setDefaultAddress() {
    $user = Auth::requireAuth();
    if (!$user) return;

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        Response::error('معرف العنوان مطلوب', [], 400);
        return;
    }

    try {
        $addressModel = new Address();
        $address = $addressModel->getById($id);

        if (!$address || $address['user_id'] !== $user['id']) {
            Response::error('العنوان غير موجود', [], 404);
            return;
        }

        $addressModel->setDefault($user['id'], $id);
        $address = $addressModel->getById($id);

        Response::success('تم تعيين العنوان الافتراضي', $address);
    } catch (Exception $e) {
        Response::error('فشل في تعيين العنوان الافتراضي', [], 500);
    }
}
