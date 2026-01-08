<?php

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/core/Core.php';
require_once BASE_PATH . '/controllers/Controller.php';

Security::setSecurityHeaders();
Session::start();
Session::requireAdmin();

$userController = new UserController();
$courseController = new CourseController();

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateRequest()) {
    if ($page === 'courses') {
        switch ($action) {
            case 'create':
                $result = $courseController->create($_POST);
                Session::setFlash($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] : $result['error']);
                if ($result['success']) { header('Location: admin.php?page=courses'); exit; }
                break;
            case 'update':
                $result = $courseController->update($id, $_POST);
                Session::setFlash($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] : $result['error']);
                if ($result['success']) { header('Location: admin.php?page=courses'); exit; }
                break;
            case 'delete':
                $result = $courseController->delete($id);
                Session::setFlash($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] : $result['error']);
                header('Location: admin.php?page=courses'); exit;
            case 'toggle_publish':
                $result = $courseController->togglePublish($id);
                Session::setFlash($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] : $result['error']);
                header('Location: admin.php?page=courses'); exit;
        }
    } elseif ($page === 'users') {
        switch ($action) {
            case 'update':
                $result = $userController->update($id, $_POST);
                Session::setFlash($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] : $result['error']);
                if ($result['success']) { header('Location: admin.php?page=users'); exit; }
                break;
            case 'delete':
                $result = $userController->delete($id);
                Session::setFlash($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] : $result['error']);
                header('Location: admin.php?page=users'); exit;
            case 'toggle_status':
                $result = $userController->toggleStatus($id);
                Session::setFlash($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] : $result['error']);
                header('Location: admin.php?page=users'); exit;
            case 'change_role':
                $result = $userController->changeRole($id, $_POST['role'] ?? '');
                Session::setFlash($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] : $result['error']);
                header('Location: admin.php?page=users'); exit;
        }
    }
}

ob_start();

if ($page === 'dashboard'):
    $totalUsers = $userController->getCount();
    $courseStats = $courseController->getStats();
?>
<div class="page-header"><h1>🏠 لوحة التحكم</h1><p>مرحباً <?php echo htmlspecialchars(Session::getUserName()); ?></p></div>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-number"><?php echo $totalUsers; ?></div><div class="stat-label">👥 المستخدمين</div></div>
    <div class="stat-card"><div class="stat-number"><?php echo $courseStats['total']; ?></div><div class="stat-label">📚 إجمالي الكورسات</div></div>
    <div class="stat-card"><div class="stat-number"><?php echo $courseStats['published']; ?></div><div class="stat-label">✅ الكورسات المنشورة</div></div>
</div>
<div class="quick-links"><h2>إجراءات سريعة</h2>
    <div class="links-grid">
        <a href="admin.php?page=courses&action=add" class="quick-link-card"><span class="quick-link-icon">➕</span><span>إضافة كورس</span></a>
        <a href="admin.php?page=users" class="quick-link-card"><span class="quick-link-icon">👥</span><span>إدارة المستخدمين</span></a>
        <a href="admin.php?page=courses" class="quick-link-card"><span class="quick-link-icon">📋</span><span>إدارة الكورسات</span></a>
    </div>
</div>

<?php

elseif ($page === 'courses'):
    $courses = $courseController->getAllCourses();
    $course = ($action === 'edit' && $id > 0) ? $courseController->getCourse($id) : ['title'=>'','description'=>'','instructor'=>'','duration'=>0,'is_published'=>0];
?>
<div class="page-header"><h1>📚 إدارة الكورسات</h1><a href="admin.php?page=courses&action=add" class="btn btn-primary">➕ إضافة كورس</a></div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="card"><div class="card-header"><h2><?php echo $action === 'add' ? 'إضافة كورس' : 'تعديل الكورس'; ?></h2></div>
<div class="card-body">
    <form action="admin.php?page=courses" method="POST">
        <?php echo Session::csrfField(); ?>
        <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'create' : 'update'; ?>">
        <?php if ($action === 'edit'): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
        <div class="form-group"><label>عنوان الكورس *</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($course['title']); ?>" required></div>
        <div class="form-group"><label>وصف الكورس *</label><textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($course['description']); ?></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>اسم المدرب *</label><input type="text" name="instructor" class="form-control" value="<?php echo htmlspecialchars($course['instructor']); ?>" required></div>
            <div class="form-group"><label>المدة (ساعات)</label><input type="number" name="duration" class="form-control" value="<?php echo (int)$course['duration']; ?>" min="0"></div>
        </div>
        <div class="form-group"><label class="checkbox-label"><input type="checkbox" name="is_published" value="1" <?php echo $course['is_published'] ? 'checked' : ''; ?>> نشر الكورس</label></div>
        <div class="form-actions"><button type="submit" class="btn btn-primary">حفظ</button><a href="admin.php?page=courses" class="btn btn-secondary">إلغاء</a></div>
    </form>
</div></div>
<?php else: ?>
<div class="table-responsive"><table class="table"><thead><tr><th>#</th><th>العنوان</th><th>المدرب</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody>
<?php foreach ($courses as $c): ?>
<tr><td><?php echo $c['id']; ?></td><td><?php echo htmlspecialchars($c['title']); ?></td><td><?php echo htmlspecialchars($c['instructor']); ?></td>
<td><span class="badge badge-<?php echo $c['is_published'] ? 'success' : 'warning'; ?>"><?php echo $c['is_published'] ? 'منشور' : 'مسودة'; ?></span></td>
<td class="actions-cell">
    <a href="admin.php?page=courses&action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-secondary">✏️</a>
    <form action="admin.php?page=courses" method="POST" class="inline-form"><?php echo Session::csrfField(); ?><input type="hidden" name="action" value="toggle_publish"><input type="hidden" name="id" value="<?php echo $c['id']; ?>"><button type="submit" class="btn btn-sm btn-info"><?php echo $c['is_published'] ? '🔒' : '📢'; ?></button></form>
    <form action="admin.php?page=courses" method="POST" class="inline-form" onsubmit="return confirm('حذف؟')"><?php echo Session::csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $c['id']; ?>"><button type="submit" class="btn btn-sm btn-danger">🗑️</button></form>
</td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>

<?php

elseif ($page === 'users'):
    $users = $userController->getAllUsers();
    $user = ($action === 'edit' && $id > 0) ? $userController->getUser($id) : null;
?>
<div class="page-header"><h1>👥 إدارة المستخدمين</h1></div>

<?php if ($action === 'edit' && $user): ?>
<div class="card"><div class="card-header"><h2>تعديل المستخدم</h2></div>
<div class="card-body">
    <form action="admin.php?page=users" method="POST">
        <?php echo Session::csrfField(); ?>
        <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-group"><label>الاسم *</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
        <div class="form-group"><label>البريد *</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
        <div class="form-group"><label>كلمة مرور جديدة</label><input type="password" name="password" class="form-control"><small class="form-hint">اتركها فارغة للإبقاء على الحالية</small></div>
        <div class="form-actions"><button type="submit" class="btn btn-primary">حفظ</button><a href="admin.php?page=users" class="btn btn-secondary">إلغاء</a></div>
    </form>
</div></div>
<?php else: ?>
<div class="table-responsive"><table class="table"><thead><tr><th>#</th><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody>
<?php foreach ($users as $u): ?>
<tr><td><?php echo $u['id']; ?></td><td><?php echo htmlspecialchars($u['name']); ?></td><td><?php echo htmlspecialchars($u['email']); ?></td>
<td><span class="badge badge-<?php echo $u['role'] === ROLE_ADMIN ? 'primary' : 'secondary'; ?>"><?php echo $u['role'] === ROLE_ADMIN ? 'أدمن' : 'مستخدم'; ?></span></td>
<td><span class="badge badge-<?php echo $u['is_active'] ? 'success' : 'danger'; ?>"><?php echo $u['is_active'] ? 'نشط' : 'معطل'; ?></span></td>
<td class="actions-cell">
    <a href="admin.php?page=users&action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-secondary">✏️</a>
    <?php if ($u['id'] !== 1): ?>
    <form action="admin.php?page=users" method="POST" class="inline-form"><?php echo Session::csrfField(); ?><input type="hidden" name="action" value="change_role"><input type="hidden" name="id" value="<?php echo $u['id']; ?>"><input type="hidden" name="role" value="<?php echo $u['role'] === ROLE_ADMIN ? ROLE_USER : ROLE_ADMIN; ?>"><button type="submit" class="btn btn-sm btn-info"><?php echo $u['role'] === ROLE_ADMIN ? '👤' : '👨‍💼'; ?></button></form>
    <form action="admin.php?page=users" method="POST" class="inline-form"><?php echo Session::csrfField(); ?><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?php echo $u['id']; ?>"><button type="submit" class="btn btn-sm <?php echo $u['is_active'] ? 'btn-warning' : 'btn-success'; ?>"><?php echo $u['is_active'] ? '🔒' : '🔓'; ?></button></form>
    <form action="admin.php?page=users" method="POST" class="inline-form" onsubmit="return confirm('حذف؟')"><?php echo Session::csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $u['id']; ?>"><button type="submit" class="btn btn-sm btn-danger">🗑️</button></form>
    <?php endif; ?>
</td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = 'لوحة التحكم - ' . SITE_NAME;
$currentPage = $page;
$isAdmin = true;
include BASE_PATH . '/templates/layout.php';
