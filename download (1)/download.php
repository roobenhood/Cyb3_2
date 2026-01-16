<?php
/**
 * تحميل ملفات التحديات
 * Challenge File Download
 */
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

// التحقق من المعاملات
$challenge_folder = sanitize($_GET['challenge'] ?? '');
$file_name = sanitize($_GET['file'] ?? '');

if (empty($challenge_folder) || empty($file_name)) {
    http_response_code(400);
    die(__('error'));
}

// منع اختراق المسار (Path Traversal)
if (strpos($challenge_folder, '..') !== false || strpos($file_name, '..') !== false) {
    http_response_code(403);
    die(__('access_denied'));
}

// بناء المسار الكامل
$file_path = CHALLENGES_PATH . $challenge_folder . '/files/' . $file_name;

// التحقق من وجود الملف
if (!file_exists($file_path) || !is_file($file_path)) {
    http_response_code(404);
    die('File not found');
}

// التحقق من أن الملف داخل مجلد التحديات (أمان إضافي)
$real_path = realpath($file_path);
$challenges_real_path = realpath(CHALLENGES_PATH);

if ($real_path === false || strpos($real_path, $challenges_real_path) !== 0) {
    http_response_code(403);
    die(__('access_denied'));
}

// الحصول على معلومات الملف
$file_size = filesize($file_path);
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// تحديد نوع MIME
$mime_types = [
    'txt' => 'text/plain',
    'pdf' => 'application/pdf',
    'zip' => 'application/zip',
    'tar' => 'application/x-tar',
    'gz' => 'application/gzip',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'py' => 'text/x-python',
    'c' => 'text/x-c',
    'cpp' => 'text/x-c++',
    'elf' => 'application/x-elf',
    'exe' => 'application/x-msdownload',
];

$mime_type = $mime_types[$file_extension] ?? 'application/octet-stream';

// تسجيل التحميل
if (isLoggedIn()) {
    logActivity('download_file', "Downloaded: $challenge_folder/$file_name");
}

// إرسال الملف
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// قراءة الملف وإرساله
readfile($file_path);
exit();
