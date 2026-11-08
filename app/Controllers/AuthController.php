<?php
/**
 * کنترلر احراز هویت کاربران (AuthController)
 * ---------------------------------------------------
 * مدل فروشگاهی: کاربران آزادانه ثبت‌نام می‌کنند و وارد می‌شوند.
 *
 * مسیرها:
 *   GET  /signup            — فرم ثبت‌نام
 *   POST /signup            — ایجاد حساب
 *   GET  /login             — فرم ورود
 *   POST /login             — احراز هویت
 *   GET  /logout            — خروج
 *   GET  /forgot-password   — فرم فراموشی رمز (آینده)
 */
class AuthController extends Controller
{
    private UserAccount $userModel;

    public function __construct()
    {
        $this->userModel = new UserAccount();
    }

    /* ==================== ثبت‌نام ==================== */

    public function signupForm(): void
    {
        // اگر کاربر قبلاً وارد شده → هدایت به داشبورد
        if (Session::get('user_logged_in')) {
            $this->redirect("/panel");
        }
        $this->render('auth/signup', [
            'title' => 'ثبت‌نام در سامانه',
            'errors' => Session::get('signup_errors', []),
            'formData' => Session::get('signup_form', []),
        ]);
        Session::remove('signup_errors');
        Session::remove('signup_form');
    }

    public function signup(): void
    {
        if (!$this->isPost()) $this->redirect('/signup');
        $this->verifyCsrf();

        $data = [
            'mobile' => trim((string)$this->input('mobile', '')),
            'password' => (string)$this->input('password', ''),
            'password_confirm' => (string)$this->input('password_confirm', ''),
            'national_code' => trim((string)$this->input('national_code', '')),
            'first_name' => trim((string)$this->input('first_name', '')),
            'last_name' => trim((string)$this->input('last_name', '')),
        ];

        $errors = $this->validateSignup($data);

        if (!empty($errors)) {
            Session::set('signup_errors', $errors);
            Session::set('signup_form', $data);
            $this->redirect('/signup');
        }

        // بررسی یکتایی موبایل
        if ($this->userModel->findByMobile($data['mobile'])) {
            Session::set('signup_errors', ['mobile' => ['این شماره موبایل قبلاً ثبت شده است. اگر رمز خود را فراموش کرده‌اید، از بخش «فراموشی رمز» استفاده کنید.']]);
            Session::set('signup_form', $data);
            $this->redirect('/signup');
        }

        // بررسی یکتایی کد ملی (اگر Person با این کد ملی و موبایل متفاوت وجود دارد)
        $personModel = new Person();
        $existingPerson = $personModel->findByNationalCode($data['national_code']);
        if ($existingPerson && !empty($existingPerson['mobile']) && $existingPerson['mobile'] !== $data['mobile']) {
            // ممکن است کاربر موبایلش را عوض کرده باشد — اجازه می‌دهیم
        }
        if ($existingPerson) {
            $existingAccount = $this->userModel->findByPersonId((int)$existingPerson['id']);
            if ($existingAccount) {
                Session::set('signup_errors', ['national_code' => ['حساب کاربری با این کد ملی وجود دارد. لطفاً وارد شوید.']]);
                Session::set('signup_form', $data);
                $this->redirect('/signup');
            }
        }

        // ایجاد حساب
        try {
            $accountId = $this->userModel->create([
                'mobile' => $data['mobile'],
                'password' => $data['password'],
                'national_code' => $data['national_code'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]);

            // ورود خودکار پس از ثبت‌نام
            $this->setUserSession($accountId);
            Session::set('signup_success', 'حساب کاربری شما با موفقیت ایجاد شد. خوش آمدید!');
            $this->redirect("/panel");
        } catch (\Throwable $e) {
            Session::set('signup_errors', ['system' => ['خطا در ایجاد حساب. لطفاً دوباره تلاش کنید.']]);
            Session::set('signup_form', $data);
            $this->redirect('/signup');
        }
    }

    private function validateSignup(array $data): array
    {
        $errors = [];

        // موبایل
        if (empty($data['mobile'])) {
            $errors['mobile'] = ['شماره موبایل الزامی است.'];
        } elseif (!preg_match('/^09\d{9}$/', $data['mobile'])) {
            $errors['mobile'] = ['شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.'];
        }

        // کد ملی
        if (empty($data['national_code'])) {
            $errors['national_code'] = ['کد ملی الزامی است.'];
        } elseif (!preg_match('/^\d{10}$/', $data['national_code'])) {
            $errors['national_code'] = ['کد ملی باید ۱۰ رقم باشد.'];
        }

        // نام و نام خانوادگی
        if (empty($data['first_name'])) {
            $errors['first_name'] = ['نام الزامی است.'];
        }
        if (empty($data['last_name'])) {
            $errors['last_name'] = ['نام خانوادگی الزامی است.'];
        }

        // رمز عبور
        if (empty($data['password'])) {
            $errors['password'] = ['رمز عبور الزامی است.'];
        } elseif (mb_strlen($data['password']) < 6) {
            $errors['password'] = ['رمز عبور باید حداقل ۶ کاراکتر باشد.'];
        }
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = ['تکرار رمز عبور مطابقت ندارد.'];
        }

        return $errors;
    }

    /* ==================== ورود ==================== */

    public function loginForm(): void
    {
        if (Session::get('user_logged_in')) {
            $this->redirect("/panel");
        }
        $this->render('auth/login', [
            'title' => 'ورود به سامانه',
            'error' => Session::get('login_error'),
            'success' => Session::get('signup_success'),
        ]);
        Session::remove('login_error');
        Session::remove('signup_success');
    }

    public function login(): void
    {
        if (!$this->isPost()) $this->redirect('/login');
        $this->verifyCsrf();

        // رفع H-2: rate limit مبتنی بر IP برای جلوگیری از brute-force
        if (rate_limit_ip('user_login', 5, 300)) {
            Session::set('login_error', 'تعداد تلاش‌های ناموفق بیش از حد مجاز است. ۵ دقیقه صبر کنید.');
            $this->redirect('/login');
        }

        $mobile = trim((string)$this->input('mobile', ''));
        $password = (string)$this->input('password', '');

        if (empty($mobile) || empty($password)) {
            Session::set('login_error', 'موبایل و رمز عبور الزامی است.');
            $this->redirect('/login');
        }

        $user = $this->userModel->findByMobile($mobile);
        if (!$user) {
            Session::set('login_error', 'حساب کاربری با این موبایل یافت نشد. آیا ثبت‌نام کرده‌اید؟');
            $this->redirect('/login');
        }

        if ((int)$user['is_active'] !== 1) {
            Session::set('login_error', 'حساب شما غیرفعال است. با پشتیبانی تماس بگیرید.');
            $this->redirect('/login');
        }

        if ($this->userModel->isLocked((int)$user['id'])) {
            Session::set('login_error', 'حساب شما به دلیل ۵ تلاش ناموفق برای ۵ دقیقه قفل شده است. بعداً تلاش کنید.');
            $this->redirect('/login');
        }

        if (!$this->userModel->verifyPassword((int)$user['id'], $password)) {
            $this->userModel->recordFailedLogin((int)$user['id']);
            Session::set('login_error', 'رمز عبور اشتباه است.');
            $this->redirect('/login');
        }

        // ورود موفق
        $this->userModel->updateLastLogin((int)$user['id']);
        $this->setUserSession((int)$user['id']);
        // رفع M-1: مصرف redirect_after_login
        $next = Session::get('redirect_after_login', '/panel');
        Session::remove('redirect_after_login');
        $this->redirect($next);
    }

    /* ==================== خروج ==================== */

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/');
    }

    /* ==================== کمکی ==================== */

    private function setUserSession(int $accountId): void
    {
        $user = $this->userModel->findById($accountId);
        if (!$user) return;

        Session::regenerate();
        Session::set('user_logged_in', true);
        Session::set('user_id', (int)$user['id']);
        Session::set('user_mobile', $user['mobile']);
        Session::set('user_name', trim($user['first_name'] . ' ' . $user['last_name']));
        Session::set('user_national_code', $user['national_code']);
    }
}
