<?php

class AuthController
{
    private User $userModel;
    private Validation $validation;

    public function __construct()
    {
        $this->userModel = new User();
        $this->validation = new Validation();
    }

    public function login(array $data): array
    {
        if (!Security::checkRateLimit('login', 5, 60)) {
            return [
                'success' => false,
                'error' => 'محاولات كثيرة. انتظر قليلاً ثم حاول مرة أخرى'
            ];
        }

        $this->validation->setData($data)
            ->required('email', 'البريد الإلكتروني')
            ->email('email', 'البريد الإلكتروني')
            ->required('password', 'كلمة المرور');

        if ($this->validation->fails()) {
            return [
                'success' => false,
                'error' => $this->validation->getFirstError()
            ];
        }

        $result = $this->userModel->authenticate($data['email'], $data['password']);

        if (!$result['success']) {
            return $result;
        }

        Session::login($result['user']);

        return [
            'success' => true,
            'user' => $result['user'],
            'redirect' => $result['user']['role'] === ROLE_ADMIN ? 'admin.php' : 'index.php'
        ];
    }

    public function register(array $data): array
    {
        if (!Security::checkRateLimit('register', 3, 300)) {
            return [
                'success' => false,
                'error' => 'محاولات كثيرة. انتظر قليلاً ثم حاول مرة أخرى'
            ];
        }

        $this->validation->setData($data)
            ->required('name', 'الاسم')
            ->minLength('name', 3, 'الاسم')
            ->maxLength('name', 100, 'الاسم')
            ->required('email', 'البريد الإلكتروني')
            ->email('email', 'البريد الإلكتروني')
            ->required('password', 'كلمة المرور')
            ->strongPassword('password', 'كلمة المرور')
            ->required('password_confirm', 'تأكيد كلمة المرور')
            ->match('password', 'password_confirm', 'كلمتا المرور');

        if ($this->validation->fails()) {
            return [
                'success' => false,
                'error' => $this->validation->getFirstError()
            ];
        }

        if ($this->userModel->emailExists($data['email'])) {
            return [
                'success' => false,
                'error' => 'البريد الإلكتروني مستخدم بالفعل'
            ];
        }

        $userId = $this->userModel->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => ROLE_USER
        ]);

        if (!$userId) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء إنشاء الحساب'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح'
        ];
    }

    public function logout(): void
    {
        Session::logout();
    }
}

class CourseController
{
    private Course $courseModel;
    private Validation $validation;

    public function __construct()
    {
        $this->courseModel = new Course();
        $this->validation = new Validation();
    }

    public function getPublishedCourses(): array
    {
        return $this->courseModel->getAll(true);
    }

    public function getAllCourses(): array
    {
        return $this->courseModel->getAll(false);
    }

    public function getCourse(int $id): ?array
    {
        return $this->courseModel->findById($id);
    }

    public function create(array $data): array
    {
        $this->validation->setData($data)
            ->required('title', 'عنوان الكورس')
            ->minLength('title', 3, 'عنوان الكورس')
            ->maxLength('title', 200, 'عنوان الكورس')
            ->required('description', 'وصف الكورس')
            ->required('instructor', 'اسم المدرب')
            ->positiveInteger('duration', 'المدة');

        if ($this->validation->fails()) {
            return [
                'success' => false,
                'error' => $this->validation->getFirstError()
            ];
        }

        $createdBy = Session::getUserId();
        if (!$createdBy) {
            return [
                'success' => false,
                'error' => 'يجب تسجيل الدخول أولاً'
            ];
        }

        $courseId = $this->courseModel->create($data, $createdBy);

        if (!$courseId) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء إنشاء الكورس'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم إنشاء الكورس بنجاح',
            'course_id' => $courseId
        ];
    }

    public function update(int $id, array $data): array
    {
        $course = $this->courseModel->findById($id);
        if (!$course) {
            return [
                'success' => false,
                'error' => 'الكورس غير موجود'
            ];
        }

        $this->validation->setData($data)
            ->required('title', 'عنوان الكورس')
            ->minLength('title', 3, 'عنوان الكورس')
            ->maxLength('title', 200, 'عنوان الكورس')
            ->required('description', 'وصف الكورس')
            ->required('instructor', 'اسم المدرب')
            ->positiveInteger('duration', 'المدة');

        if ($this->validation->fails()) {
            return [
                'success' => false,
                'error' => $this->validation->getFirstError()
            ];
        }

        $result = $this->courseModel->update($id, [
            'title' => $data['title'],
            'description' => $data['description'],
            'instructor' => $data['instructor'],
            'duration' => (int)$data['duration'],
            'is_published' => isset($data['is_published']) && $data['is_published']
        ]);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء تحديث الكورس'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم تحديث الكورس بنجاح'
        ];
    }

    public function delete(int $id): array
    {
        $course = $this->courseModel->findById($id);
        if (!$course) {
            return [
                'success' => false,
                'error' => 'الكورس غير موجود'
            ];
        }

        $result = $this->courseModel->delete($id);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء حذف الكورس'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم حذف الكورس بنجاح'
        ];
    }

    public function togglePublish(int $id): array
    {
        $result = $this->courseModel->togglePublish($id);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء تغيير حالة الكورس'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم تغيير حالة الكورس بنجاح'
        ];
    }

    public function enroll(int $userId, int $courseId): array
    {
        $course = $this->courseModel->findById($courseId);
        if (!$course) {
            return [
                'success' => false,
                'error' => 'الكورس غير موجود'
            ];
        }

        if ($course['is_published'] != COURSE_PUBLISHED) {
            return [
                'success' => false,
                'error' => 'الكورس غير متاح حالياً'
            ];
        }

        if ($this->courseModel->isEnrolled($userId, $courseId)) {
            return [
                'success' => false,
                'error' => 'أنت مسجل بالفعل في هذا الكورس'
            ];
        }

        $result = $this->courseModel->enroll($userId, $courseId);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء التسجيل'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم التسجيل في الكورس بنجاح'
        ];
    }

    public function unenroll(int $userId, int $courseId): array
    {
        if (!$this->courseModel->isEnrolled($userId, $courseId)) {
            return [
                'success' => false,
                'error' => 'أنت غير مسجل في هذا الكورس'
            ];
        }

        $result = $this->courseModel->unenroll($userId, $courseId);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء إلغاء التسجيل'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم إلغاء التسجيل من الكورس بنجاح'
        ];
    }

    public function getUserCourses(int $userId): array
    {
        return $this->courseModel->getUserCourses($userId);
    }

    public function isEnrolled(int $userId, int $courseId): bool
    {
        return $this->courseModel->isEnrolled($userId, $courseId);
    }

    public function getEnrollmentCount(int $courseId): int
    {
        return $this->courseModel->getEnrollmentCount($courseId);
    }

    public function getStats(): array
    {
        return [
            'total' => $this->courseModel->count(false),
            'published' => $this->courseModel->count(true)
        ];
    }
}

class UserController
{
    private User $userModel;
    private Validation $validation;

    public function __construct()
    {
        $this->userModel = new User();
        $this->validation = new Validation();
    }

    public function getAllUsers(): array
    {
        return $this->userModel->getAll();
    }

    public function getUser(int $id): ?array
    {
        return $this->userModel->findById($id);
    }

    public function update(int $id, array $data): array
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'المستخدم غير موجود'
            ];
        }

        $this->validation->setData($data)
            ->required('name', 'الاسم')
            ->minLength('name', 3, 'الاسم')
            ->maxLength('name', 100, 'الاسم')
            ->required('email', 'البريد الإلكتروني')
            ->email('email', 'البريد الإلكتروني');

        if ($this->validation->fails()) {
            return [
                'success' => false,
                'error' => $this->validation->getFirstError()
            ];
        }

        if ($this->userModel->emailExists($data['email'], $id)) {
            return [
                'success' => false,
                'error' => 'البريد الإلكتروني مستخدم بالفعل'
            ];
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email']
        ];

        if (!empty($data['password'])) {
            $this->validation->setData($data)
                ->strongPassword('password', 'كلمة المرور');

            if ($this->validation->fails()) {
                return [
                    'success' => false,
                    'error' => $this->validation->getFirstError()
                ];
            }
            $updateData['password'] = $data['password'];
        }

        $result = $this->userModel->update($id, $updateData);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء تحديث المستخدم'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح'
        ];
    }

    public function delete(int $id): array
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'المستخدم غير موجود'
            ];
        }

        if ($id === 1) {
            return [
                'success' => false,
                'error' => 'لا يمكن حذف المدير الرئيسي'
            ];
        }

        $result = $this->userModel->delete($id);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء حذف المستخدم'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح'
        ];
    }

    public function toggleStatus(int $id): array
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'المستخدم غير موجود'
            ];
        }

        if ($id === 1) {
            return [
                'success' => false,
                'error' => 'لا يمكن تعطيل المدير الرئيسي'
            ];
        }

        $result = $this->userModel->toggleStatus($id);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء تغيير حالة المستخدم'
            ];
        }

        $newStatus = $user['is_active'] == USER_ACTIVE ? 'تعطيل' : 'تفعيل';
        return [
            'success' => true,
            'message' => "تم {$newStatus} المستخدم بنجاح"
        ];
    }

    public function changeRole(int $id, string $role): array
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'المستخدم غير موجود'
            ];
        }

        if ($id === 1) {
            return [
                'success' => false,
                'error' => 'لا يمكن تغيير دور المدير الرئيسي'
            ];
        }

        if (!in_array($role, [ROLE_ADMIN, ROLE_USER])) {
            return [
                'success' => false,
                'error' => 'الدور غير صحيح'
            ];
        }

        $result = $this->userModel->changeRole($id, $role);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء تغيير الدور'
            ];
        }

        return [
            'success' => true,
            'message' => 'تم تغيير دور المستخدم بنجاح'
        ];
    }

    public function getCount(): int
    {
        return $this->userModel->count();
    }
}
