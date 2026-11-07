<?php
/**
 * کنترلر پنل مدیریت
 */
class AdminController extends Controller
{
    private User $userModel;
    private Applicant $applicantModel;
    private JobDetail $jobDetailModel;
    private PropertyDetail $propertyDetailModel;
    private Document $documentModel;
    private StatusLog $statusLogModel;
    private PostalCodeChange $postalCodeChangeModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->applicantModel = new Applicant();
        $this->jobDetailModel = new JobDetail();
        $this->propertyDetailModel = new PropertyDetail();
        $this->documentModel = new Document();
        $this->statusLogModel = new StatusLog();
        $this->postalCodeChangeModel = new PostalCodeChange();
    }

    /**
     * بررسی احراز هویت مدیر
     */
    private function checkAuth(): void
    {
        if (!Session::get('admin_logged_in')) {
            $this->redirect('/admin/login');
        }
    }

    /**
     * صفحه ورود
     */
    public function loginForm(): void
    {
        // اگر قبلاً وارد شده، به داشبورد برو
        if (Session::get('admin_logged_in')) {
            $this->redirect('/admin/dashboard');
        }

        $this->render('admin/login', [
            'title' => 'ورود به پنل مدیریت',
            'error' => Session::get('login_error')
        ]);
        Session::remove('login_error');
    }

    /**
     * پردازش ورود — با محافظت CSRF و محدودسازی نرخ (ضد Brute-force)
     */
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/login');
        }

        // اعتبارسنجی توکن CSRF
        $this->verifyCsrf();

        // محدودسازی نرخ: حداکثر ۵ تلاش ناموفق در هر ۵ دقیقه (مبتنی بر سشن)
        if (Session::isLimited('login_attempts', 5, 300)) {
            $wait = (int)ceil(Session::retryAfter('login_attempts', 300) / 60);
            Session::set('login_error', "تعداد تلاش‌های ناموفق بیش از حد مجاز است. لطفاً حدود {$wait} دقیقه دیگر دوباره تلاش کنید.");
            $this->redirect('/admin/login');
        }

        $username = trim((string)$this->input('username', ''));
        $password = (string)$this->input('password', '');

        if ($username === '' || $password === '') {
            Session::set('login_error', 'نام کاربری و رمز عبور را وارد کنید.');
            $this->redirect('/admin/login');
        }

        $user = $this->userModel->findByUsername($username);
        // hash_compare روی ورودی نامعتبر هم فراخوانی می‌شود تا زمان پاسخ یکسان بماند (ضد Timing Attack)
        if (!$user || !password_verify($password, $user['password'])) {
            Session::hit('login_attempts');
            Session::set('login_error', 'نام کاربری یا رمز عبور اشتباه است.');
            $this->redirect('/admin/login');
        }

        // ورود موفق — بازتولید شناسهٔ نشست (ضد Session Fixation) و پاکسازی شمارنده
        Session::regenerate();
        Session::resetRate('login_attempts');
        Session::set('admin_id', (int)$user['id']);
        Session::set('admin_role', $user['role']);
        Session::set('admin_logged_in', true);
        Session::set('admin_username', $user['username']);
        Session::set('admin_display_name', $user['display_name'] ?: $user['username']);

        $this->redirect('/admin/dashboard');
    }

    /**
     * خروج — نابودی کامل نشست
     */
    public function logout(): void
    {
        Session::destroy();
        // شروع نشست تازه برای پیام خوش‌آمدگویی صفحهٔ ورود
        Session::start();
        Session::regenerate();
        $this->redirect('/admin/login');
    }

    /**
     * داشبورد مدیریت
     */
    public function dashboard(): void
    {
        $this->checkAuth();
        AutoCancelService::cancelExpired();
        AutoCancelService::updatePriorities();

        $db = Database::getInstance()->getConnection();

        // آمار کلی
        $total = $db->query("SELECT COUNT(*) FROM applicants")->fetchColumn();
        $expired = $db->query("SELECT COUNT(*) FROM applicants WHERE current_status = 'لغو'")->fetchColumn();
        $pending = $db->query("SELECT COUNT(*) FROM applicants WHERE current_status NOT IN ('صدور مجوز','لغو')")->fetchColumn();

        // آمار هر وضعیت (از مرجع مرکزی config/statuses.php)
        $statuses = array_keys((require CONFIG_PATH . '/statuses.php')['statuses']);
        $statuses = array_values(array_diff($statuses, ['مشاهده و چاپ'])); // رویداد خودکار کاربر، در آمار وضعیت‌ها نمی‌آید
        $statusCounts = [];
        foreach ($statuses as $st) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM applicants WHERE current_status = ?");
            $stmt->execute([$st]);
            $statusCounts[$st] = $stmt->fetchColumn();
        }

        // تعداد درخواست‌های تغییر کد پستی در انتظار بررسی
        $pendingPostal = $this->postalCodeChangeModel->countPending();

        // پرونده‌های اولویت‌دار (ثبت‌نام طولانی)
        $priorityCount = (int)$db->query("SELECT COUNT(*) FROM applicants WHERE priority = 1 AND current_status NOT IN ('صدور مجوز','لغو')")->fetchColumn();

        // آمار مالی (فاز ۴ نقشهٔ راه)
        $payStats = (new Payment())->stats();

        // در آستانهٔ لغو: بیش از ۱۰ روز از «ثبت درخواست صدور» گذشته ولی هنوز لغو نشده
        // (هشدار ۴ روزه به مدیر برای اقدام پیش از لغو خودکارِ روز چهاردهم)
        $warnDays = max(1, AUTO_CANCEL_DAYS - 4);
        $stmt = $db->prepare(
            "SELECT a.id, a.tracking_code, a.first_name, a.last_name, a.current_status,
                    sl.created_at AS submit_date
             FROM applicants a
             JOIN status_log sl ON sl.applicant_id = a.id AND sl.status_name = 'ثبت درخواست صدور'
             WHERE a.current_status IN ('ثبت درخواست صدور','بارگذاری مدارک به سازمان','تأیید اولیه')
               AND sl.created_at < DATE_SUB(NOW(), INTERVAL :warn DAY)
             ORDER BY sl.created_at ASC LIMIT 6"
        );
        $stmt->execute(['warn' => $warnDays]);
        $nearExpiry = $stmt->fetchAll();

        // آخرین رویدادهای ثبت‌شده در سامانه (برای دید سریع مدیر)
        $recentLogs = $db->query(
            "SELECT sl.*, a.first_name, a.last_name, a.tracking_code
             FROM status_log sl JOIN applicants a ON a.id = sl.applicant_id
             ORDER BY sl.created_at DESC LIMIT 8"
        )->fetchAll();

        $this->render('admin/dashboard', [
            'title' => 'داشبورد',
            'total' => (int)$total,
            'expired' => (int)$expired,
            'pending' => (int)$pending,
            'statusCounts' => $statusCounts,
            'pendingPostal' => $pendingPostal,
            'priorityCount' => $priorityCount,
            'payStats' => $payStats,
            'nearExpiry' => $nearExpiry,
            'recentLogs' => $recentLogs,
            'warnDays' => $warnDays,
            'currentPage' => 'dashboard'
        ], 'admin_layout');
    }


    public function applications(): void
    {
        $this->requireRole(['admin'], '/admin/my-tasks');

        // اجرای لغو خودکار و به‌روزرسانی اولویت‌ها (بدون cron job)
        AutoCancelService::cancelExpired();
        AutoCancelService::updatePriorities();

        // فیلترها — پاکسازی ورودی‌ها
        $trim = fn($k) => trim((string)($_GET[$k] ?? ''));
        $filters = [
            'tracking_code' => $trim('tracking_code'),
            'name' => $trim('name'),
            'national_code' => $trim('national_code'),
            'mobile' => $trim('mobile'),
            'province' => $trim('province'),
            'city' => $trim('city'),
            'village' => $trim('village'),
            'license_title' => $trim('license_title'),
            'status' => $trim('status'),
        ];

        // صفحه‌بندی — ۱۵ رکورد در هر صفحه
        $perPage = 15;
        $total = $this->applicantModel->countAll($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
        $offset = ($page - 1) * $perPage;

        $applicants = $this->applicantModel->getAll($filters, $perPage, $offset);

        $this->render('admin/applications', [
            'title' => 'همه درخواست‌ها',
            'applicants' => $applicants,
            'filters' => $filters,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'currentPage' => 'applications'
        ], 'admin_layout');
    }

    /**
     * مشاهده جزئیات یک درخواست
     */
    public function viewApplication(int|string $id): void
    {
        $this->checkAuth();

        $id = (int)$id;
        $applicant = $this->applicantModel->findById($id);
        if (!$applicant) {
            $this->redirect('/admin/dashboard');
        }

        $jobDetail = $this->jobDetailModel->findByApplicantId($id);
        $propertyDetail = $this->propertyDetailModel->findByApplicantId($id);
        $documents = $this->documentModel->findByApplicantId($id);
        $statusLogs = $this->statusLogModel->findByApplicantId($id);
        $postalChanges = $this->postalCodeChangeModel->findByApplicantId($id);

        // مقادیر فیلدهای سفارشی (فرم‌ساز پویا)
        $dynValues = (new ApplicantFieldValue())->findByApplicantId($id);

        // پرداخت‌های پرونده (فاز ۳/۴ نقشهٔ راه)
        $applicantPayments = (new Payment())->findByApplicantId($id);

        // تصویر مجوز (فاز ۶ نقشهٔ راه)
        $licenseFile = (new LicenseFile())->findByApplicantId($id);
        $licenseDoc = $licenseFile ? (new Document())->findById((int)$licenseFile['document_id']) : null;

        // نوع خدمت (فاز ۹)
        $serviceType = !empty($applicant['service_type_id'])
            ? (new ServiceType())->findById((int)$applicant['service_type_id']) : null;

        // شخص و سایر پرونده‌های او (فاز ۷ — چندمجوزی)
        $personModel = new Person();
        $person = !empty($applicant['person_id']) ? $personModel->findById((int)$applicant['person_id']) : null;
        $siblingApps = $person ? array_values(array_filter(
            $personModel->applications((int)$person['id']),
            fn($a) => (int)$a['id'] !== $id
        )) : [];

        $this->render('admin/view_application', [
            'title' => 'جزئیات درخواست',
            'applicant' => $applicant,
            'jobDetail' => $jobDetail,
            'propertyDetail' => $propertyDetail,
            'documents' => $documents,
            'statusLogs' => $statusLogs,
            'postalChanges' => $postalChanges,
            'dynValues' => $dynValues,
            'payments' => $applicantPayments,
            'paymentRequired' => (int)$applicant['payment_required'] === 1,
            'feeAmount' => FeeCalculatorService::calculateForApplicant((int)$applicant['id'])['amount'],
            'feeInfo' => FeeCalculatorService::calculateForApplicant((int)$applicant['id']),
            'licenseFile' => $licenseFile,
            'licenseDoc' => $licenseDoc,
            'person' => $person,
            'siblingApps' => $siblingApps,
            'serviceType' => $serviceType,
            'statuses' => array_keys((require CONFIG_PATH . '/statuses.php')['statuses']),
            'statusMeta' => (require CONFIG_PATH . '/statuses.php')['statuses'],
            'errors' => Session::get('errors', []),
        ], 'admin_layout');
        Session::remove('errors');
    }

    /**
     * تغییر وضعیت درخواست توسط مدیر
     */
    public function changeStatus(): void
    {
        $this->checkAuth();

        if (!$this->isPost()) {
            $this->redirect('/admin/dashboard');
        }

        // محافظت CSRF
        $this->verifyCsrf();

        $applicantId = (int)$this->input('applicant_id', 0);
        $newStatus = $this->input('new_status', '');

        $applicant = $this->applicantModel->findById($applicantId);
        if (!$applicant) {
            $this->redirect('/admin/dashboard');
        }

        $currentStatus = $applicant['current_status'];
        $role = Session::get('admin_role', 'admin');

        // بارگذاری workflow
        $workflow = require CONFIG_PATH . '/workflow.php';
        $transitions = $workflow['transitions'];

        // admin مجاز به همه تغییرات
        $allowed = ($role === 'admin');
        if (!$allowed && isset($transitions[$role])) {
            $rule = $transitions[$role];
            if (in_array($currentStatus, $rule['from']) && in_array($newStatus, $rule['to'])) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            $this->redirect('/admin/view/' . $applicantId);
        }

        // وضعیت‌های مجاز برای تغییر — از مرجع مرکزی
        $statusCfg = (require CONFIG_PATH . '/statuses.php')['statuses'];
        $allowedStatuses = array_keys(array_filter($statusCfg, fn($m) => $m['manual']));

        if (!in_array($newStatus, $allowedStatuses, true)) {
            $this->redirect('/admin/view/' . $applicantId);
        }

        // جلوگیری از بازگشت به وضعیت قبلی (مسیر یکطرفه)
        $currentStatus = $applicant['current_status'];
        $currentOrder = $statusCfg[$currentStatus]['order'] ?? 0;
        $newOrder = $statusCfg[$newStatus]['order'] ?? 0;

        // وضعیت‌های پایانیِ منفی (لغو/رد) همیشه مجازند؛ بازگشت به عقب ممنوع
        if (!in_array($newStatus, ['لغو', 'رد درخواست'], true) && $newOrder <= $currentOrder) {
            $this->redirect('/admin/view/' . $applicantId);
        }
        // پرونده‌های بسته‌شده (صادر/تحویل/رد/لغو) دیگر تغییر نمی‌کنند
        $terminal = (require CONFIG_PATH . '/statuses.php')['terminal'];
        if (in_array($currentStatus, $terminal, true)) {
            $this->redirect('/admin/view/' . $applicantId);
        }

        // به‌روزرسانی وضعیت
        $this->applicantModel->updateStatus($applicantId, $newStatus);

        // ثبت در تاریخچه — با نام کاربر اقدام‌کننده برای ممیزی
        $this->statusLogModel->create([
            'applicant_id' => $applicantId,
            'status_name' => $newStatus,
            'description' => 'تغییر وضعیت توسط ' . Session::get('admin_display_name', 'مدیر')
                . ' (از: ' . $currentStatus . ')'
        ]);

        $this->redirect('/admin/view/' . $applicantId);
    }

    /**
     * نمایش فرم ثبت‌نام دستی متقاضی — فقط مدیر کل
     */
    public function registerForm(): void
    {
        $this->requireRole(['admin']);
        $this->render('admin/register', [
            'title' => 'ثبت نام متقاضی جدید (بایگانی)',
            'errors' => Session::get('errors', []),
            'formData' => Session::get('formData', []),
            'licenseGroups' => (new LicenseTitle())->groupedActive(),
            'currentPage' => 'register'
        ], 'admin_layout');
        Session::remove('errors');
        Session::remove('formData');
    }

        /**
     * ذخیره ثبت‌نام دستی توسط مدیر
     */
    public function registerProcess(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) {
            $this->redirect('/admin/register');
        }
        $this->verifyCsrf();

        $data = $this->allInputs();

        // تاریخ تولد: تبدیل و اعتبارسنجی
        if (!empty($data['birth_year']) && !empty($data['birth_month']) && !empty($data['birth_day'])) {
            $jy = (int)$data['birth_year'];
            $jm = (int)$data['birth_month'];
            $jd = (int)$data['birth_day'];
            if (!is_valid_persian_date($jy, $jm, $jd)) {
                $data['birth_date'] = '';
            } else {
                $data['birth_date'] = persian_to_gregorian($jy, $jm, $jd);
            }
        } else {
            $data['birth_date'] = '';
        }

        // اگر «سایر» انتخاب شده
        if (str_starts_with((string)$data['license_title'], 'سایر') && !empty($data['custom_license_title'])) {
            $data['license_title'] = mb_substr(trim($data['custom_license_title']), 0, 100);
        }

        $validator = new Validator($data);

        // ---- اعتبارسنجی هویتی ----
        $validator->required('first_name', 'نام')
                ->persianAlpha('first_name', 'نام')
                ->maxLength('first_name', 50, 'نام')
                ->required('last_name', 'نام خانوادگی')
                ->persianAlpha('last_name', 'نام خانوادگی')
                ->maxLength('last_name', 50, 'نام خانوادگی')
                ->required('national_code', 'کد ملی')
                ->nationalCode('national_code', 'کد ملی')
                ->required('postal_code', 'کد پستی')
                ->postalCode('postal_code', 'کد پستی')
                ->required('mobile', 'شماره تلفن همراه')
                ->mobile('mobile', 'شماره تلفن همراه')
                ->required('father_name', 'نام پدر')
                ->persianAlpha('father_name', 'نام پدر')
                ->maxLength('father_name', 50, 'نام پدر')
                ->required('marital_status', 'وضعیت تأهل')
                ->enum('marital_status', ['مجرد','متأهل'], 'وضعیت تأهل')
                ->required('physical_condition', 'وضعیت جسمانی')
                ->required('has_court_case', 'پرونده قضایی')
                ->required('has_active_loan', 'وام فعال')
                ->required('has_overdue_installments', 'اقساط معوقه');

        // یکتایی موبایل در سطح شخص (چندمجوزی — فاز ۷ نقشهٔ راه)
        if ((new Person())->mobileBelongsToOtherPerson((string)($data['mobile'] ?? ''), (string)($data['national_code'] ?? ''))) {
            $validator->addError('mobile', 'این شمارهٔ موبایل به کد ملی دیگری تعلق دارد.');
        }

        // تاریخ تولد
        if (empty($data['birth_date'])) {
            $validator->addError('birth_date', 'تاریخ تولد الزامی و معتبر است.');
        }

        // وضعیت معلولیت
        if ($data['physical_condition'] === 'معلولیت') {
            $validator->required('disability_details', 'توضیحات معلولیت');
        }

        // تأهل
        if ($data['marital_status'] === 'متأهل') {
            $validator->required('children_count', 'تعداد فرزندان')
                    ->unsignedInt('children_count', 'تعداد فرزندان');
        } else {
            $data['children_count'] = 0;
        }

        if (!empty($data['income_decile'])) {
            $validator->unsignedInt('income_decile', 'دهک درآمدی');
        }

        // ---- اطلاعات شغلی ----
        $validator->required('license_title', 'عنوان مجوز')
                ->maxLength('license_title', 100, 'عنوان مجوز')
                ->required('experience_months', 'سابقه فعالیت')
                ->unsignedInt('experience_months', 'سابقه فعالیت')
                ->required('training_method', 'نحوه آموزش')
                ->enum('training_method', ['در خانه','استاد شاگردی','کارگاهی'], 'نحوه آموزش');

        // ---- اطلاعات ملک ----
        $validator->required('ownership_status', 'وضعیت مالکیت')
                ->enum('ownership_status', ['شخص','مستأجر','سرپرست'], 'وضعیت مالکیت')
                ->required('province', 'استان')
                ->required('county', 'شهرستان')
                ->required('city', 'شهر')
                ->required('electricity_bill_id', 'شناسه قبض برق')
                ->required('address_street', 'خیابان')
                ->required('address_plaque', 'پلاک');

        if (!empty($data['address_alley'])) {
            $validator->maxLength('address_alley', 100, 'کوچه');
        }

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/register');
        }

        // ---- ذخیره‌سازی ----
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            $trackingCode = Applicant::generateTrackingCode();

            $applicantId = $this->applicantModel->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'national_code' => $data['national_code'],
                'postal_code' => $data['postal_code'],
                'mobile' => $data['mobile'],
                'birth_date' => $data['birth_date'],
                'father_name' => $data['father_name'],
                'marital_status' => $data['marital_status'] ?? 'مجرد',
                'children_count' => $data['children_count'] ?? 0,
                'physical_condition' => $data['physical_condition'] ?? 'سالم',
                'disability_details' => $data['disability_details'] ?? null,
                'income_decile' => $data['income_decile'] ?? null,
                'has_court_case' => $data['has_court_case'] ?? 'خیر',
                'has_active_loan' => $data['has_active_loan'] ?? 'خیر',
                'has_overdue_installments' => $data['has_overdue_installments'] ?? 'خیر',
                'tracking_code' => $trackingCode
            ]);

            // یافتن/ساخت شخص و اتصال (چندمجوزی — فاز ۷)
            $personModel = new Person();
            $personId = $personModel->findOrCreate([
                'national_code' => $data['national_code'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'father_name' => $data['father_name'] ?? null,
                'birth_date' => $data['birth_date'] ?: null,
                'mobile' => $data['mobile'],
                'postal_code' => $data['postal_code'],
            ]);
            $personModel->linkApplication($applicantId, $personId);

            // شغلی
            $this->jobDetailModel->create([
                'applicant_id' => $applicantId,
                'license_title' => $data['license_title'],
                'experience_months' => $data['experience_months'],
                'training_method' => $data['training_method']
            ]);

            // ملک
            $this->propertyDetailModel->create([
                'applicant_id' => $applicantId,
                'ownership_status' => $data['ownership_status'],
                'province' => $data['province'],
                'county' => $data['county'],
                'city' => $data['city'],
                'district' => $data['district'] ?? null,
                'rural_district' => $data['rural_district'] ?? null,
                'village' => $data['village'] ?? null,
                'electricity_bill_id' => $data['electricity_bill_id'],
                'address_street' => $data['address_street'] ?? null,
                'address_alley' => $data['address_alley'] ?? null,
                'address_plaque' => $data['address_plaque'] ?? null
            ]);

            // مدارک
            $uploadDir = UPLOAD_DIR . '/' . date('Y/m');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $saveFile = function ($file, $docType) use ($applicantId, $uploadDir) {
                if (!$file || $file['error'] !== UPLOAD_ERR_OK) return;
                // اعتبارسنجی چندلایه‌ای هر فایل قبل از ذخیره
                $validation = validate_uploaded_file($file);
                if (!$validation['valid']) {
                    throw new \RuntimeException('فایل نامعتبر: ' . ($validation['error'] ?? 'خطا'));
                }
                $ext = $validation['ext'];
                $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
                $destPath = $uploadDir . '/' . $uniqueName;
                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    throw new \RuntimeException('خطا در ذخیره فایل ' . sanitize_upload_filename((string)$file['name']));
                }
                @chmod($destPath, 0640);
                $this->documentModel->create([
                    'applicant_id' => $applicantId,
                    'doc_type' => $docType,
                    'file_name' => $uniqueName,
                    'original_name' => sanitize_upload_filename((string)$file['name']),
                    'file_path' => str_replace(UPLOAD_DIR, '', $destPath)
                ]);
            };

            $saveFile($_FILES['national_card_front'] ?? null, 'national_card');
            $saveFile($_FILES['national_card_back'] ?? null, 'national_card');
            $saveFile($_FILES['birth_cert'] ?? null, 'birth_cert');
            $saveFile($_FILES['ownership_doc'] ?? null, 'ownership_doc');
            $saveFile($_FILES['vocational_cert'] ?? null, 'vocational_cert');
            $saveFile($_FILES['education_degree'] ?? null, 'education_degree');

            for ($i = 1; $i <= 3; $i++) {
                $saveFile($_FILES["optional_{$i}"] ?? null, 'optional');
            }

            if (!empty($_FILES['product_photos']['name']) && is_array($_FILES['product_photos']['name'])) {
                $ppNames = $_FILES['product_photos']['name'] ?? [];
                $ppTmpNames = $_FILES['product_photos']['tmp_name'] ?? [];
                $ppSizes = $_FILES['product_photos']['size'] ?? [];
                $ppErrors = $_FILES['product_photos']['error'] ?? [];
                foreach ($ppTmpNames as $index => $tmpName) {
                    if (($ppErrors[$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $ppNames[$index] ?? '',
                            'tmp_name' => $tmpName,
                            'size' => $ppSizes[$index] ?? 0,
                            'error' => $ppErrors[$index] ?? UPLOAD_ERR_NO_FILE,
                        ];
                        $saveFile($file, 'product_photos');
                    }
                }
            }

            $this->applicantModel->updateStatus($applicantId, 'تکمیل اطلاعات');
            $nowDate = date('Y-m-d');
            $stmt = $db->prepare("UPDATE applicants SET registration_started_at = :start_date, registration_completed_at = :end_date WHERE id = :id");
            $stmt->execute([
                'start_date' => $nowDate,
                'end_date' => $nowDate,
                'id' => $applicantId
            ]);

            $this->statusLogModel->create([
                'applicant_id' => $applicantId,
                'status_name' => 'تکمیل اطلاعات',
                'description' => 'ثبت نام توسط مدیر'
            ]);

            $db->commit();

            Session::remove('errors');
            Session::remove('formData');

            $this->redirect('/admin/view/' . $applicantId);

        } catch (\Exception $e) {
            $db->rollBack();
            Session::set('errors', ['system' => ['خطا در ثبت نام: ' . $e->getMessage()]]);
            Session::set('formData', $data);
            $this->redirect('/admin/register');
        }
    }

    /**
     * نمایش فرم ویرایش متقاضی — فقط مدیر کل
     */
    public function editApplication(int|string $id): void
    {
        $this->requireRole(['admin']);
        $id = (int)$id; // ورودی غیرعددی → ۰ → یافت نشد → هدایت
        $applicant = $this->applicantModel->findById($id);
        if (!$applicant) {
            $this->redirect('/admin/dashboard');
        }
        $jobDetail = $this->jobDetailModel->findByApplicantId($id);
        $propertyDetail = $this->propertyDetailModel->findByApplicantId($id);
        $documents = $this->documentModel->findByApplicantId($id);

        $this->render('admin/edit_application', [
            'title' => 'ویرایش متقاضی',
            'applicant' => $applicant,
            'jobDetail' => $jobDetail,
            'propertyDetail' => $propertyDetail,
            'documents' => $documents,
            'errors' => Session::get('errors', []),
            'formData' => Session::get('formData', []),
        ], 'admin_layout');
        Session::remove('errors');
        Session::remove('formData');
    }

    /**
     * پردازش ویرایش متقاضی — فقط مدیر کل
     */
    public function updateApplication(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) {
            $this->redirect('/admin/dashboard');
        }
        $this->verifyCsrf();

        $applicantId = (int)$this->input('applicant_id', 0);
        $data = $this->allInputs();
        $validator = new Validator($data);

        $validator->required('first_name', 'نام')
                ->persianAlpha('first_name', 'نام')
                ->maxLength('first_name', 50, 'نام')
                ->required('last_name', 'نام خانوادگی')
                ->persianAlpha('last_name', 'نام خانوادگی')
                ->maxLength('last_name', 50, 'نام خانوادگی')
                ->required('mobile', 'شماره تلفن همراه')
                ->mobile('mobile', 'شماره تلفن همراه')
                ->required('postal_code', 'کد پستی')
                ->postalCode('postal_code', 'کد پستی');

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/edit/' . $applicantId);
        }

        // آپدیت فیلدهای اصلی — اصلاح باگ: دسترسی مستقیم به کانکشن دیتابیس
        // (پیش‌تر به ویژگی خصوصی مدل‌ها دسترسی می‌شد که باعث خطای Fatal می‌گردید)
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "UPDATE applicants SET first_name=?, last_name=?, mobile=?, postal_code=?, updated_at=NOW() WHERE id=?"
        );
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['mobile'],
            $data['postal_code'],
            $applicantId
        ]);

        // در صورت ارسال فیلدهای آدرس، اطلاعات ملک نیز به‌روزرسانی می‌شود
        if (!empty($data['address_street']) || !empty($data['address_alley']) || !empty($data['address_plaque'])) {
            $propSql = "UPDATE property_details SET address_street=?, address_alley=?, address_plaque=? WHERE applicant_id=?";
            $db->prepare($propSql)->execute([
                $data['address_street'] ?? '',
                $data['address_alley'] ?? '',
                $data['address_plaque'] ?? '',
                $applicantId
            ]);
        }

        // همگام‌سازی رکورد شخص با داده‌های تازه (فاز ۷)
        (new Person())->syncFromApplicant($applicantId);

        // ثبت لاگ ممیزی — نام کاربرِ اقدام‌کننده نیز ذخیره می‌شود
        $this->statusLogModel->create([
            'applicant_id' => $applicantId,
            'status_name' => 'ویرایش اطلاعات توسط مدیر',
            'description' => 'اطلاعات هویتی یا آدرس ویرایش شد. (توسط: ' . Session::get('admin_username', 'نامشخص') . ')'
        ]);

        $this->redirect('/admin/view/' . $applicantId);
    }

    /**
     * افزودن مدرک جدید به پرونده — با اعتبارسنجی پسوند، حجم و نوع واقعی فایل
     */
    public function addDocument(): void
    {
        $this->checkAuth();
        if (!$this->isPost()) {
            $this->redirect('/admin/dashboard');
        }
        $this->verifyCsrf();

        $applicantId = (int)$this->input('applicant_id', 0);
        $docType = (string)$this->input('doc_type', 'optional');

        // اعتبارسنجی نوع مدرک در برابر فهرست مجاز
        $allowedDocTypes = ['national_card', 'birth_cert', 'ownership_doc', 'lease_agreement',
            'supervisor_doc', 'vocational_cert', 'education_degree', 'product_photos', 'optional'];
        if (!in_array($docType, $allowedDocTypes, true)) {
            $docType = 'optional';
        }

        $file = $_FILES['document_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::set('errors', ['document' => 'لطفاً یک فایل انتخاب کنید.']);
            $this->redirect('/admin/view/' . $applicantId);
        }

        // اعتبارسنجی چندلایه‌ای (نام + MIME واقعی + تطبیق)
        $validation = validate_uploaded_file($file);
        if (!$validation['valid']) {
            Session::set('errors', ['document' => $validation['error'] ?? 'فایل نامعتبر است.']);
            $this->redirect('/admin/view/' . $applicantId);
        }
        $ext = $validation['ext'];

        $uploadDir = UPLOAD_DIR . '/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $uploadDir . '/' . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            Session::set('errors', ['document' => 'خطا در ذخیره فایل.']);
            $this->redirect('/admin/view/' . $applicantId);
        }
        @chmod($destPath, 0640); // حداقل دسترسی ممکن روی فایل ذخیره‌شده

        $safeOriginalName = sanitize_upload_filename((string)$file['name']);
        $this->documentModel->create([
            'applicant_id' => $applicantId,
            'doc_type' => $docType,
            'file_name' => $uniqueName,
            'original_name' => $safeOriginalName,
            'file_path' => str_replace(UPLOAD_DIR, '', $destPath)
        ]);

        $this->statusLogModel->create([
            'applicant_id' => $applicantId,
            'status_name' => 'افزودن مدرک توسط مدیر',
            'description' => 'مدرک ' . htmlspecialchars($safeOriginalName, ENT_QUOTES, 'UTF-8')
                . ' اضافه شد. (توسط: ' . Session::get('admin_username', 'نامشخص') . ')'
        ]);

        $this->redirect('/admin/view/' . $applicantId);
    }

    /**
     * فعال/غیرفعال کردن «درخواست پرداخت هزینه» روی پرونده (فاز ۳ نقشهٔ راه)
     */
    public function requestPayment(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/dashboard');
        $this->verifyCsrf();

        $applicantId = (int)$this->input('applicant_id', 0);
        $enable = $this->input('enable', '1') === '1';
        $applicant = $this->applicantModel->findById($applicantId);
        if (!$applicant) $this->redirect('/admin/dashboard');

        $db = Database::getInstance()->getConnection();

        // محاسبهٔ گروه قیمتی و مبلغ (فقط هنگام فعال‌سازی)
        $feeInfo = null;
        if ($enable) {
            $feeInfo = FeeCalculatorService::calculateForApplicant($applicantId);
            $amount = $feeInfo['amount'];
            $groupId = $feeInfo['group']['id'] ?? null;
            $isRural = $feeInfo['is_rural'] ? 1 : 0;
            $fallback = $feeInfo['fallback_used'] ? 1 : 0;
            $flags = !empty($feeInfo['suspicion_flags']) ? implode(',', $feeInfo['suspicion_flags']) : null;
        } else {
            $amount = 0;
            $groupId = null;
            $isRural = 0;
            $fallback = 0;
            $flags = null;
        }

        $stmt = $db->prepare(
            "UPDATE applicants SET payment_required = :flag,
                    payment_requested_at = IF(:flag2 = 1, NOW(), NULL)
             WHERE id = :id"
        );
        $stmt->execute(['flag' => $enable ? 1 : 0, 'flag2' => $enable ? 1 : 0, 'id' => $applicantId]);

        // اگر فعال‌سازی شده، یک رکورد payments با مبلغ محاسبه‌شده بساز (وضعیت: در انتظار بررسی)
        if ($enable) {
            // جلوگیری از ایجاد رکورد تکراری
            $stmt = $db->prepare(
                "SELECT id FROM payments
                 WHERE applicant_id = :aid AND purpose = 'هزینهٔ ثبت‌نام'
                   AND status = 'در انتظار بررسی'
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute(['aid' => $applicantId]);
            $existingPaymentId = (int)$stmt->fetchColumn();

            if ($existingPaymentId === 0) {
                // ایجاد رکورد پرداخت با مبلغ محاسبه‌شده
                $stmt = $db->prepare(
                    "INSERT INTO payments
                        (applicant_id, payment_channel_id, purpose, amount, original_amount,
                         final_amount, fee_group_id, is_rural_exempt, fallback_used,
                         suspicion_flags, status, created_at)
                     VALUES
                        (:aid, NULL, 'هزینهٔ ثبت‌نام', :amount, :orig,
                         :final, :gid, :rural, :fallback,
                         :flags, 'در انتظار بررسی', NOW())"
                );
                $stmt->execute([
                    'aid' => $applicantId,
                    'amount' => $amount,
                    'orig' => $amount,
                    'final' => $amount,
                    'gid' => $groupId,
                    'rural' => $isRural,
                    'fallback' => $fallback,
                    'flags' => $flags,
                ]);
                $paymentId = (int)$db->lastInsertId();

                // ایجاد تیک‌های اولیه برای این پرداخت (همه تیک‌خورده)
                if ($groupId) {
                    (new PaymentServiceTick())->createForPayment($paymentId, $groupId);
                }

                // audit log
                (new PaymentAuditLog())->log(
                    $paymentId,
                    (int)Session::get('admin_id', 0),
                    'payment_requested',
                    null,
                    null,
                    (string)$amount,
                    $feeInfo['reason'] ?? 'درخواست پرداخت ایجاد شد'
                );
            }
        }

        $this->statusLogModel->create([
            'applicant_id' => $applicantId,
            'status_name' => $enable ? 'درخواست پرداخت هزینه' : 'لغو درخواست پرداخت',
            'description' => $enable
                ? 'پرداخت هزینهٔ ثبت‌نام (' . number_format($amount) . ' تومان) برای متقاضی فعال شد — توسط ' . Session::get('admin_display_name', 'مدیر')
                    . ($feeInfo && $feeInfo['group'] ? ' — ' . $feeInfo['group']['name'] : '')
                : 'درخواست پرداخت توسط مدیر غیرفعال شد.',
        ]);

        $this->redirect('/admin/view/' . $applicantId);
    }

    /**
     * به‌روزرسانی وضعیت مجوز و تاریخ اعتبار و پروندهٔ والد (فاز ۷)
     */
    public function updateLicenseState(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/dashboard');
        $this->verifyCsrf();

        $applicantId = (int)$this->input('applicant_id', 0);
        $applicant = $this->applicantModel->findById($applicantId);
        if (!$applicant) $this->redirect('/admin/dashboard');

        $state = (string)$this->input('license_state', 'جاری');
        if (!in_array($state, ['جاری', 'صادر شده', 'باطل شده', 'منقضی'], true)) {
            $state = 'جاری';
        }

        // تاریخ اعتبار شمسی → میلادی
        $validUntil = null;
        if (!empty($this->input('valid_year')) && !empty($this->input('valid_month')) && !empty($this->input('valid_day'))) {
            $jy = (int)$this->input('valid_year'); $jm = (int)$this->input('valid_month'); $jd = (int)$this->input('valid_day');
            if (is_valid_persian_date($jy, $jm, $jd)) {
                $validUntil = persian_to_gregorian($jy, $jm, $jd);
            }
        }

        // پروندهٔ والد (سلسله‌مراتب تمدید/ارتقا)
        $parentId = (int)$this->input('parent_application_id', 0);
        $db = Database::getInstance()->getConnection();
        if ($parentId > 0) {
            $check = $db->prepare("SELECT person_id FROM applicants WHERE id = :id");
            $check->execute(['id' => $parentId]);
            $parent = $check->fetch();
            // والد باید متعلق به همان شخص باشد (جلوگیری از اتصال اشتباه)
            if (!$parent || (int)$parent['person_id'] !== (int)($applicant['person_id'] ?? 0)) {
                $parentId = 0;
            }
        }
        $db->prepare("UPDATE applicants SET parent_application_id = :pid WHERE id = :id")
            ->execute(['pid' => $parentId > 0 ? $parentId : null, 'id' => $applicantId]);

        (new Person())->updateLicenseState($applicantId, $state, $validUntil);

        $this->statusLogModel->create([
            'applicant_id' => $applicantId,
            'status_name' => 'به‌روزرسانی وضعیت مجوز',
            'description' => 'وضعیت مجوز: ' . $state
                . ($validUntil ? ' — اعتبار تا ' . persianDate($validUntil) : '')
                . ' — توسط ' . Session::get('admin_display_name', 'مدیر'),
        ]);

        $this->redirect('/admin/view/' . $applicantId);
    }

    /* ============ گردش کار درخواست‌های تغییر کد پستی ============ */

    /**
     * فهرست درخواست‌های تغییر کد پستی (صفحهٔ بررسی مدیر)
     */
    public function postalRequests(): void
    {
        $this->checkAuth();

        $statusFilter = $_GET['status'] ?? 'در انتظار';
        if (!in_array($statusFilter, ['در انتظار', 'تأیید شده', 'رد شده', ''], true)) {
            $statusFilter = 'در انتظار';
        }

        $requests = $this->postalCodeChangeModel->getAllWithApplicant($statusFilter);

        $this->render('admin/postal_requests', [
            'title' => 'درخواست‌های تغییر کد پستی',
            'requests' => $requests,
            'statusFilter' => $statusFilter,
            'pendingCount' => $this->postalCodeChangeModel->countPending(),
            'message' => Session::get('postal_review_message'),
            'currentPage' => 'postal-requests'
        ], 'admin_layout');
        Session::remove('postal_review_message');
    }

    /**
     * تأیید درخواست تغییر کد پستی → اعمال کد جدید روی پرونده
     */
    public function approvePostalRequest(): void
    {
        $this->requireRole(['admin', 'expert']);
        if (!$this->isPost()) {
            $this->redirect('/admin/postal-requests');
        }
        $this->verifyCsrf();

        $requestId = (int)$this->input('request_id', 0);
        $request = $this->postalCodeChangeModel->findById($requestId);
        if (!$request || $request['status'] !== 'در انتظار') {
            Session::set('postal_review_message', 'درخواست مورد نظر یافت نشد یا پیش‌تر بررسی شده است.');
            $this->redirect('/admin/postal-requests');
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            // ۱) اعمال کد پستی جدید
            $this->applicantModel->updatePostalCode((int)$request['applicant_id'], $request['new_postal_code']);
            // ۲) ثبت نتیجهٔ بررسی
            $this->postalCodeChangeModel->review($requestId, 'تأیید شده', (int)Session::get('admin_id', 0));
            // ۳) ثبت رویداد در تاریخچهٔ پرونده
            $this->statusLogModel->create([
                'applicant_id' => (int)$request['applicant_id'],
                'status_name' => 'تأیید تغییر کد پستی',
                'description' => 'کد پستی از ' . $request['old_postal_code'] . ' به ' . $request['new_postal_code']
                    . ' تغییر یافت. (تأیید: ' . Session::get('admin_display_name', 'مدیر') . ')'
            ]);
            $db->commit();
            Session::set('postal_review_message', 'درخواست تأیید و کد پستی به‌روزرسانی شد. ✔');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::set('postal_review_message', 'خطا در ثبت تغییرات. لطفاً دوباره تلاش کنید.');
        }
        $this->redirect('/admin/postal-requests');
    }

    /**
     * رد درخواست تغییر کد پستی (بدون تغییر کد پستی پرونده)
     */
    public function rejectPostalRequest(): void
    {
        $this->requireRole(['admin', 'expert']);
        if (!$this->isPost()) {
            $this->redirect('/admin/postal-requests');
        }
        $this->verifyCsrf();

        $requestId = (int)$this->input('request_id', 0);
        $reason = trim((string)$this->input('reject_reason', ''));
        $request = $this->postalCodeChangeModel->findById($requestId);
        if (!$request || $request['status'] !== 'در انتظار') {
            Session::set('postal_review_message', 'درخواست مورد نظر یافت نشد یا پیش‌تر بررسی شده است.');
            $this->redirect('/admin/postal-requests');
        }

        $this->postalCodeChangeModel->review($requestId, 'رد شده', (int)Session::get('admin_id', 0), mb_substr($reason, 0, 250));
        $this->statusLogModel->create([
            'applicant_id' => (int)$request['applicant_id'],
            'status_name' => 'رد درخواست تغییر کد پستی',
            'description' => 'درخواست تغییر از ' . $request['old_postal_code'] . ' به ' . $request['new_postal_code']
                . ' رد شد.' . ($reason !== '' ? ' دلیل: ' . $reason : '')
        ]);

        Session::set('postal_review_message', 'درخواست رد شد.');
        $this->redirect('/admin/postal-requests');
    }

    public function myTasks(): void
    {
        $this->checkAuth();
        $role = Session::get('admin_role', 'admin');
        if ($role === 'admin') {
            $this->applications();
            return;
        }
        $workflow = require CONFIG_PATH . '/workflow.php';
        $allowedFrom = $workflow['transitions'][$role]['from'] ?? [];
        if (empty($allowedFrom)) {
            $applicants = [];
        } else {
            $in = implode("','", $allowedFrom);
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT * FROM applicants WHERE current_status IN ('$in') ORDER BY priority DESC, registration_completed_at DESC");
            $applicants = $stmt->fetchAll();
        }

        // فیلترهای پیش‌فرض کامل
        $filters = [
            'tracking_code' => '',
            'name' => '',
            'national_code' => '',
            'mobile' => '',
            'province' => '',
            'status' => '',
            'license_title' => '',
            'village' => '',
        ];

        $this->render('admin/applications', [
            'title' => 'وظایف من',
            'applicants' => $applicants,
            'filters' => $filters,
            'currentPage' => 'tasks'
        ], 'admin_layout');
    }
}