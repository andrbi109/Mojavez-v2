<?php
/**
 * کنترلر پنل کاربری (UserPanelController)
 * ---------------------------------------------------
 * پنل شخصی کاربر با سایدبار: پروفایل، درخواست‌ها، پرداخت‌ها،
 * مجوزها، اعلان‌ها، پیگیری.
 *
 * مسیرها:
 *   GET  /panel              — نمای کلی
 *   GET  /panel/profile      — پروفایل + بارگذاری تصویر
 *   POST /panel/profile      — به‌روزرسانی پروفایل
 *   POST /panel/avatar       — بارگذاری تصویر پروفایل
 *   GET  /panel/requests     — درخواست‌های من
 *   GET  /panel/payments     — پرداخت‌های من
 *   GET  /panel/licenses     — مجوزهای من
 *   GET  /panel/notifications — اعلان‌ها
 *   POST /panel/notifications/read — علامت‌گذاری همه به‌عنوان خوانده‌شده
 *   GET  /panel/tracking     — پیگیری با کد رهگیری
 */
class UserPanelController extends Controller
{
    private UserAccount $userModel;
    private UserNotification $notifModel;

    public function __construct()
    {
        $this->userModel = new UserAccount();
        $this->notifModel = new UserNotification();
    }

    private function getUser(): array
    {
        if (!Session::get('user_logged_in')) {
            Session::set('redirect_after_login', $_SERVER['REQUEST_URI'] ?? '/panel');
            Session::set('login_error', 'برای دسترسی به پنل کاربری، ابتدا وارد شوید.');
            $this->redirect('/login');
        }
        $userId = (int)Session::get('user_id', 0);
        $user = $this->userModel->findById($userId);
        if (!$user) {
            Session::destroy();
            $this->redirect('/login');
        }
        return $user;
    }

    private function prepareLayout(array &$data, string $active): void
    {
        $user = $this->getUser();
        $data['user'] = $user;
        $data['activeMenu'] = $active;
        $data['unreadCount'] = 0;
        try { $data['unreadCount'] = $this->notifModel->countUnread((int)$user['id']); } catch (\Throwable $e) {}
        $data['statusMeta'] = (require CONFIG_PATH . '/statuses.php')['statuses'];
    }

    /* ==================== نمای کلی ==================== */
    public function index(): void
    {
        $user = $this->getUser();
        $applications = $this->userModel->getApplications((int)$user['id']);
        $payments = $this->userModel->getPayments((int)$user['id']);
        $notifications = $this->notifModel->findByUser((int)$user['id'], 5);

        $totalApps = count($applications);
        $pendingApps = count(array_filter($applications, fn($a) => !in_array($a['current_status'] ?? '', ['صدور مجوز','تحویل مجوز','لغو'], true)));
        $issuedApps = count(array_filter($applications, fn($a) => in_array($a['current_status'] ?? '', ['صدور مجوز','تحویل مجوز'], true)));

        $latestApp = !empty($applications) ? $applications[0] : null;
        $timeline = [];
        if ($latestApp) {
            try { $timeline = (new StatusLog())->findByApplicantId((int)$latestApp['id']); } catch (\Throwable $e) {}
        }

        $data = [
            'title' => 'پنل کاربری',
            'applications' => $applications,
            'payments' => $payments,
            'notifications' => $notifications,
            'latestApp' => $latestApp,
            'timeline' => $timeline,
            'stats' => [
                'total_apps' => $totalApps,
                'pending_apps' => $pendingApps,
                'issued_apps' => $issuedApps,
            ],
        ];
        $this->prepareLayout($data, 'overview');
        $this->render('user/overview', $data, 'user_layout');
    }

    /* ==================== پروفایل ==================== */
    public function profile(): void
    {
        $user = $this->getUser();
        $personModel = new Person();
        $person = $personModel->findById((int)$user['person_id']);

        // اطلاعات ملک از آخرین پرونده
        $applications = $this->userModel->getApplications((int)$user['id']);
        $property = null;
        if (!empty($applications)) {
            try { $property = (new PropertyDetail())->findByApplicantId((int)$applications[0]['id']); } catch (\Throwable $e) {}
        }

        $data = [
            'title' => 'پروفایل من',
            'person' => $person,
            'property' => $property,
            'success' => Session::get('profile_success'),
            'error' => Session::get('profile_error'),
        ];
        Session::remove('profile_success');
        Session::remove('profile_error');
        $this->prepareLayout($data, 'profile');
        $this->render('user/profile', $data, 'user_layout');
    }

    public function updateProfile(): void
    {
        if (!$this->isPost()) $this->redirect('/panel/profile');
        $this->verifyCsrf();
        $user = $this->getUser();

        // به‌روزرسانی Person
        $personModel = new Person();
        $db = Database::getInstance()->getConnection();

        $email = trim((string)$this->input('email', ''));
        $stmt = $db->prepare("UPDATE user_accounts SET email = :email, updated_at = NOW() WHERE id = :uid");
        $stmt->execute(['email' => $email ?: null, 'uid' => (int)$user['id']]);

        Session::set('profile_success', 'اطلاعات پروفایل به‌روزرسانی شد.');
        $this->redirect('/panel/profile');
    }

    public function uploadAvatar(): void
    {
        if (!$this->isPost()) $this->redirect('/panel/profile');
        $this->verifyCsrf();
        $user = $this->getUser();

        $file = $_FILES['avatar'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::set('profile_error', 'لطفاً یک فایل تصویر انتخاب کنید.');
            $this->redirect('/panel/profile');
        }

        $validation = validate_uploaded_file($file);
        if (!$validation['valid']) {
            Session::set('profile_error', $validation['error'] ?? 'فایل نامعتبر است.');
            $this->redirect('/panel/profile');
        }

        // فقط تصویر مجاز است (jpg, png)
        $ext = $validation['ext'];
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            Session::set('profile_error', 'تصویر پروفایل باید jpg یا png باشد.');
            $this->redirect('/panel/profile');
        }

        $uploadDir = UPLOAD_DIR . '/avatars';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $uploadDir . '/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            Session::set('profile_error', 'خطا در ذخیرهٔ تصویر.');
            $this->redirect('/panel/profile');
        }
        @chmod($destPath, 0640);

        // ذخیره در documents
        $docId = (new Document())->create([
            'applicant_id' => 0,
            'doc_type' => 'avatar',
            'file_name' => $uniqueName,
            'original_name' => sanitize_upload_filename((string)$file['name']),
            'file_path' => '/avatars/' . $uniqueName,
        ]);

        // به‌روزرسانی avatar_document_id
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE user_accounts SET avatar_document_id = :did, updated_at = NOW() WHERE id = :uid");
        $stmt->execute(['did' => $docId, 'uid' => (int)$user['id']]);

        Session::set('profile_success', 'تصویر پروفایل به‌روزرسانی شد.');
        $this->redirect('/panel/profile');
    }

    /* ==================== درخواست‌های من ==================== */
    public function requests(): void
    {
        $user = $this->getUser();
        $applications = $this->userModel->getApplications((int)$user['id']);

        $data = [
            'title' => 'درخواست‌های من',
            'applications' => $applications,
        ];
        $this->prepareLayout($data, 'requests');
        $this->render('user/requests', $data, 'user_layout');
    }

    /* ==================== پرداخت‌های من ==================== */
    public function payments(): void
    {
        $user = $this->getUser();
        $payments = $this->userModel->getPayments((int)$user['id']);

        $data = [
            'title' => 'پرداخت‌های من',
            'payments' => $payments,
        ];
        $this->prepareLayout($data, 'payments');
        $this->render('user/payments', $data, 'user_layout');
    }

    /* ==================== مجوزهای من ==================== */
    public function licenses(): void
    {
        $user = $this->getUser();
        $applications = $this->userModel->getApplications((int)$user['id']);
        $licenses = array_filter($applications, fn($a) => in_array($a['current_status'] ?? '', ['صدور مجوز','تحویل مجوز'], true));

        $data = [
            'title' => 'مجوزهای من',
            'licenses' => array_values($licenses),
        ];
        $this->prepareLayout($data, 'licenses');
        $this->render('user/licenses', $data, 'user_layout');
    }

    /* ==================== اعلان‌ها ==================== */
    public function notifications(): void
    {
        $user = $this->getUser();
        $notifications = $this->notifModel->findByUser((int)$user['id'], 100);

        $data = [
            'title' => 'اعلان‌ها',
            'notifications' => $notifications,
        ];
        $this->prepareLayout($data, 'notifications');
        $this->render('user/notifications', $data, 'user_layout');
    }

    public function markAllRead(): void
    {
        if (!$this->isPost()) $this->redirect('/panel/notifications');
        $this->verifyCsrf();
        $user = $this->getUser();
        $this->notifModel->markAllRead((int)$user['id']);
        $this->redirect('/panel/notifications');
    }

    /* ==================== پیگیری ==================== */
    public function tracking(): void
    {
        $user = $this->getUser();
        $data = [
            'title' => 'پیگیری درخواست',
        ];
        $this->prepareLayout($data, 'tracking');
        $this->render('user/tracking', $data, 'user_layout');
    }
}
