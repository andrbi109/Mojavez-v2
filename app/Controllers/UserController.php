<?php
/**
 * کنترلر مدیریت کاربران پنل (فقط نقش admin)
 * ---------------------------------------------------
 * نقش‌ها:
 *  - admin              → مدیر کل؛ دسترسی کامل
 *  - expert             → کارشناس؛ بررسی پرونده‌ها و ارتقای وضعیت تا «تأیید اولیه»
 *  - document_uploader  → متصدی بارگذاری مدارک به سازمان
 */
class UserController extends Controller
{
    private User $userModel;

    /** نقش‌های مجاز سامانه */
    private const ROLES = ['admin', 'expert', 'document_uploader'];

    public function __construct()
    {
        $this->userModel = new User();
    }

    private function checkAdmin(): void
    {
        $this->requireRole(['admin']);
    }

    public function index(): void
    {
        $this->checkAdmin();
        $users = $this->userModel->getAll();
        $this->render('admin/users_list', [
            'title' => 'مدیریت کاربران',
            'users' => $users,
            'message' => Session::get('user_message'),
            'currentPage' => 'users'
        ], 'admin_layout');
        Session::remove('user_message');
    }

    public function createForm(): void
    {
        $this->checkAdmin();
        $this->render('admin/user_form', [
            'title' => 'ایجاد کاربر جدید',
            'formData' => Session::get('formData', []),
            'errors' => Session::get('errors', []),
            'editMode' => false,
            'currentPage' => 'users'
        ], 'admin_layout');
        Session::remove('errors');
        Session::remove('formData');
    }

    public function store(): void
    {
        $this->checkAdmin();
        if (!$this->isPost()) $this->redirect('/admin/users/create');
        $this->verifyCsrf();

        $data = $this->allInputs();
        $validator = new Validator($data);
        $validator->required('username', 'نام کاربری')
                  ->minLength('username', 3, 'نام کاربری')
                  ->maxLength('username', 50, 'نام کاربری')
                  ->unique('username', 'users', 'username', 'نام کاربری')
                  ->required('password', 'رمز عبور')
                  ->minLength('password', 8, 'رمز عبور')
                  ->required('role', 'نقش')
                  ->enum('role', self::ROLES, 'نقش');

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', array_diff_key($data, ['password' => ''])); // رمز به سشن برنگردد
            $this->redirect('/admin/users/create');
        }

        $this->userModel->create([
            'username' => trim((string)$data['username']),
            'password' => $data['password'],
            'display_name' => trim((string)($data['display_name'] ?? '')),
            'role' => $data['role']
        ]);

        Session::set('user_message', 'کاربر «' . htmlspecialchars($data['username']) . '» با موفقیت ایجاد شد.');
        $this->redirect('/admin/users');
    }

    public function editForm(int|string $id): void
    {
        $this->checkAdmin();
        $id = (int)$id;
        $user = $this->userModel->findById($id);
        if (!$user) $this->redirect('/admin/users');
        $this->render('admin/user_form', [
            'title' => 'ویرایش کاربر',
            'formData' => Session::get('formData', []),
            'errors' => Session::get('errors', []),
            'editMode' => true,
            'user' => $user,
            'currentPage' => 'users'
        ], 'admin_layout');
        Session::remove('errors');
        Session::remove('formData');
    }

    public function update(int|string $id): void
    {
        $this->checkAdmin();
        if (!$this->isPost()) $this->redirect('/admin/users');
        $this->verifyCsrf();

        $id = (int)$id;
        $user = $this->userModel->findById($id);
        if (!$user) $this->redirect('/admin/users');

        $data = $this->allInputs();
        $validator = new Validator($data);
        $validator->required('username', 'نام کاربری')
                  ->minLength('username', 3, 'نام کاربری')
                  ->unique('username', 'users', 'username', 'نام کاربری', $id)
                  ->required('role', 'نقش')
                  ->enum('role', self::ROLES, 'نقش');
        if (!empty($data['password'])) {
            $validator->minLength('password', 8, 'رمز عبور');
        }

        // محافظت: آخرین کاربر admin نباید نقشش گرفته شود
        if ($user['role'] === 'admin' && $data['role'] !== 'admin') {
            $adminCount = $this->userModel->countByRole('admin');
            if ($adminCount <= 1) {
                $validator->addError('role', 'این کاربر تنها مدیر سامانه است؛ نقش او قابل تغییر نیست.');
            }
        }

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', array_diff_key($data, ['password' => '']));
            $this->redirect('/admin/users/edit/' . $id);
        }

        $this->userModel->update($id, [
            'username' => trim((string)$data['username']),
            'password' => $data['password'] ?? '',
            'display_name' => trim((string)($data['display_name'] ?? '')),
            'role' => $data['role']
        ]);

        Session::set('user_message', 'اطلاعات کاربر به‌روزرسانی شد.');
        $this->redirect('/admin/users');
    }

    /**
     * حذف کاربر — با POST و توکن CSRF (حذف با GET خطرناک است)
     */
    public function delete(): void
    {
        $this->checkAdmin();
        if (!$this->isPost()) $this->redirect('/admin/users');
        $this->verifyCsrf();

        $id = (int)$this->input('user_id', 0);
        $user = $this->userModel->findById($id);
        if (!$user) $this->redirect('/admin/users');

        // قوانین محافظتی
        if ($id === (int)Session::get('admin_id', 0)) {
            Session::set('user_message', 'نمی‌توانید حساب کاربری خود را حذف کنید.');
            $this->redirect('/admin/users');
        }
        if ($id === 1) {
            Session::set('user_message', 'حساب مدیر اصلی سامانه قابل حذف نیست.');
            $this->redirect('/admin/users');
        }
        if ($user['role'] === 'admin' && $this->userModel->countByRole('admin') <= 1) {
            Session::set('user_message', 'آخرین مدیر سامانه قابل حذف نیست.');
            $this->redirect('/admin/users');
        }

        $this->userModel->delete($id);
        Session::set('user_message', 'کاربر «' . htmlspecialchars($user['username']) . '» حذف شد.');
        $this->redirect('/admin/users');
    }
}
