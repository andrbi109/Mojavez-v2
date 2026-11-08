<?php
/**
 * کنترلر تصویر مجوز (فاز ۶ نقشهٔ راه)
 * =====================================================================
 * قواعد کسب‌وکار:
 *  ۱) دریافت اول: خودکار و رایگان — با کلیک متقاضی، توکن ۷۲ ساعته صادر
 *     و download_count افزایش می‌یابد.
 *  ۲) دفعات بعد: متقاضی «درخواست دریافت مجدد» ثبت می‌کند → مدیر بررسی:
 *       - تأیید رایگان → توکن ۷۲ ساعته
 *       - تأیید مشروط به پرداخت → متقاضی رسید پرداخت را ثبت می‌کند
 *         (گردش فاز ۳) → پس از تأیید مدیر، توکن صادر می‌شود.
 *       - رد با دلیل
 *  ۳) لینک‌ها توکن تصادفی زمان‌دار دارند (نه آدرس ثابت) و سرو فایل فقط
 *     از مسیر /license/view/{token} با بررسی‌های کامل انجام می‌شود.
 */
class LicenseController extends Controller
{
    private LicenseFile $licenseFileModel;
    private LicenseAccessRequest $requestModel;
    private Applicant $applicantModel;
    private Document $documentModel;
    private StatusLog $statusLogModel;

    public function __construct()
    {
        $this->licenseFileModel = new LicenseFile();
        $this->requestModel = new LicenseAccessRequest();
        $this->applicantModel = new Applicant();
        $this->documentModel = new Document();
        $this->statusLogModel = new StatusLog();
        LicenseAccessRequest::expireStale(); // انقضای تنبل توکن‌ها
    }

    /* ==================== سمت متقاضی ==================== */

    /**
     * دریافت اول (خودکار) — توکن ۷۲ ساعته می‌سازد و به مشاهده هدایت می‌کند
     */
    public function firstAccess(): void
    {
        if (!$this->isPost()) $this->redirect('/tracking');
        $this->verifyCsrf();

        $applicant = $this->findApplicantOrFail();
        $back = '/tracking/status/' . rawurlencode($applicant['tracking_code']);

        $lf = $this->licenseFileModel->findByApplicantId((int)$applicant['id']);
        if (!$lf) {
            Session::set('license_message', 'تصویر مجوز هنوز توسط مدیر بارگذاری نشده است.');
            $this->redirect($back);
        }
        if ((int)$lf['download_count'] > 0) {
            Session::set('license_message', 'دریافت اول پیش‌تر انجام شده؛ برای دریافت مجدد، درخواست ثبت کنید.');
            $this->redirect($back);
        }
        // لینک معتبر قبلی (هنوز منقضی‌نشده)؟
        if ($valid = $this->requestModel->findValidToken((int)$applicant['id'])) {
            $this->redirect('/license/view/' . $valid['access_token']);
        }

        $reqId = $this->requestModel->create((int)$applicant['id'], true);
        $req = $this->requestModel->findById($reqId);
        $this->licenseFileModel->incrementDownload((int)$lf['id']);

        $this->statusLogModel->create([
            'applicant_id' => (int)$applicant['id'],
            'status_name' => 'دریافت تصویر مجوز',
            'description' => 'متقاضی برای اولین بار تصویر مجوز خود را دریافت کرد.',
        ]);

        $this->redirect('/license/view/' . $req['access_token']);
    }

    /**
     * ثبت درخواست دریافت مجدد
     */
    public function requestAccess(): void
    {
        if (!$this->isPost()) $this->redirect('/tracking');
        $this->verifyCsrf();

        $applicant = $this->findApplicantOrFail();
        $back = '/tracking/status/' . rawurlencode($applicant['tracking_code']);

        // محدودسازی نرخ: ۲ درخواست در هفته
        if (Session::isLimited('license_request', 2, 604800)) {
            Session::set('license_message', 'تعداد درخواست‌های دریافت مجدد در این هفته بیش از حد مجاز است.');
            $this->redirect($back);
        }

        $latest = $this->requestModel->findLatestByApplicant((int)$applicant['id']);
        if ($latest && in_array($latest['status'], ['در انتظار', 'در انتظار پرداخت'], true)) {
            Session::set('license_message', 'یک درخواست دریافت مجدد در جریان بررسی/پرداخت دارید.');
            $this->redirect($back);
        }
        // لینک معتبر باز وجود دارد؟
        if ($this->requestModel->findValidToken((int)$applicant['id'])) {
            Session::set('license_message', 'هم‌اکنون لینک دسترسی معتبر دارید؛ از همان استفاده کنید.');
            $this->redirect($back);
        }

        Session::hit('license_request');
        $this->requestModel->create((int)$applicant['id'], false);

        $this->statusLogModel->create([
            'applicant_id' => (int)$applicant['id'],
            'status_name' => 'درخواست دریافت مجدد تصویر مجوز',
            'description' => 'درخواست در انتظار بررسی مدیر ثبت شد.',
        ]);

        Session::set('license_message', 'درخواست دریافت مجدد ثبت شد؛ پس از بررسی مدیر نتیجه در همین صفحه اعلام می‌شود.');
        $this->redirect($back);
    }

    /**
     * ثبت رسید پرداخت برای درخواست «در انتظار پرداخت» دریافت مجدد مجوز
     */
    public function submitReissuePayment(): void
    {
        if (!$this->isPost()) $this->redirect('/tracking');
        $this->verifyCsrf();

        $applicant = $this->findApplicantOrFail();
        $back = '/tracking/status/' . rawurlencode($applicant['tracking_code']);

        $latest = $this->requestModel->findLatestByApplicant((int)$applicant['id']);
        if (!$latest || $latest['status'] !== 'در انتظار پرداخت') {
            Session::set('license_message', 'درخواستی در وضعیت «در انتظار پرداخت» ندارید.');
            $this->redirect($back);
        }
        // رسید در انتظار/تأیید قبلی برای همین درخواست؟
        $payModel = new Payment();
        $exist = $payModel->findLatestByApplicant((int)$applicant['id'], 'هزینهٔ دریافت مجدد تصویر مجوز');
        if ($exist && in_array($exist['status'], ['در انتظار بررسی', 'تأیید شده'], true)
            && (int)$exist['linked_request_id'] === (int)$latest['id']) {
            Session::set('license_message', 'رسید پرداخت این درخواست پیش‌تر ثبت شده و در حال بررسی است.');
            $this->redirect($back);
        }

        // اعتبارسنجی با سرویس متمرکز — حداقل مبلغ = مبلغ درخواست
        $data = $this->allInputs();
        $file = $_FILES['receipt_file'] ?? null;
        $paymentService = new PaymentReceiptService();
        $validated = $paymentService->validateReceipt($data, $file ?: [], (int)$latest['amount']);

        if (!$validated['valid']) {
            Session::set('license_message', $validated['first_error']);
            $this->redirect($back);
        }

        // ثبت رسید با سرویس متمرکز
        $result = $paymentService->submitReceipt(
            (int)$applicant['id'],
            $file,
            $validated,
            [
                'purpose' => 'هزینهٔ دریافت مجدد تصویر مجوز',
                'linked_request_id' => (int)$latest['id'],
            ]
        );

        if (!$result['success']) {
            Session::set('license_message', $result['error']);
            $this->redirect($back);
        }

        // اتصال پرداخت به درخواست
        $this->requestModel->attachPayment((int)$latest['id'], $result['payment_id']);

        Session::set('license_message', 'رسید پرداخت ثبت شد؛ پس از تأیید مدیر، لینک دریافت تصویر مجوز فعال می‌شود.');
        $this->redirect($back);
    }

    /* ==================== مشاهده با توکن ==================== */

    /**
     * سرو تصویر مجوز با توکن زمان‌دار — عمومی ولی غیرقابل حدس
     */
    public function viewByToken(string $token): void
    {
        // Rate limiting مبتنی بر IP برای جلوگیری از brute-force توکن
        if (rate_limit_ip('license_view', 20, 60)) {
            http_response_code(429);
            header('Retry-After: 60');
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
                . '<title>درخواست بیش از حد</title></head><body style="font-family:Tahoma;text-align:center;padding:3rem">'
                . '<h2>⏱ تعداد درخواست‌های شما بیش از حد مجاز است.</h2>'
                . '<p>لطفاً یک دقیقه دیگر تلاش کنید.</p></body></html>';
            return;
        }

        LicenseAccessRequest::expireStale();
        $req = $this->requestModel->findByToken($token);

        $deny = function (string $msg) {
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
                . '<meta name="viewport" content="width=device-width,initial-scale=1"><title>لینک نامعتبر</title></head>'
                . '<body style="font-family:Tahoma;text-align:center;padding:3rem">'
                . '<h3>⚠️ ' . htmlspecialchars($msg) . '</h3>'
                . '<p>برای دریافت لینک جدید به صفحهٔ پیگیری پرونده مراجعه کنید.</p>'
                . '</body></html>';
            exit;
        };

        if (!$req) $deny('لینک دسترسی معتبر نیست.');
        if ($req['status'] !== 'تأیید شده') $deny('لینک دسترسی فعال نیست.');
        if ($req['expires_at'] && strtotime($req['expires_at']) < time()) $deny('لینک دسترسی منقضی شده است.');

        $lf = $this->licenseFileModel->findByApplicantId((int)$req['applicant_id']);
        if (!$lf) $deny('تصویر مجوزی برای این پرونده ثبت نشده است.');

        $doc = $this->documentModel->findById((int)$lf['document_id']);
        if (!$doc) $deny('فایل مجوز یافت نشد.');

        $clean = str_replace(['..', "\0"], '', (string)$doc['file_path']);
        $filePath = realpath(UPLOAD_DIR . $clean);
        $base = realpath(UPLOAD_DIR);
        if (!$filePath || !$base || !str_starts_with($filePath, $base) || !is_file($filePath)) {
            $deny('فایل مجوز روی سرور موجود نیست.');
        }

        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($filePath);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
            $deny('نوع فایل قابل نمایش نیست.');
        }

        $applicant = $this->applicantModel->findById((int)$req['applicant_id']);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="license-' . ($applicant['tracking_code'] ?? $req['applicant_id']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=300');
        readfile($filePath);
        exit;
    }

    /* ==================== سمت مدیر ==================== */

    /**
     * بارگذاری تصویر مجوز روی پرونده (در صفحهٔ جزئیات)
     */
    public function upload(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/dashboard');
        $this->verifyCsrf();

        $applicantId = (int)$this->input('applicant_id', 0);
        $applicant = $this->applicantModel->findById($applicantId);
        if (!$applicant) $this->redirect('/admin/dashboard');

        $file = $_FILES['license_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::set('errors', ['license' => ['فایل تصویر مجوز را انتخاب کنید.']]);
            $this->redirect('/admin/view/' . $applicantId);
        }
        // اعتبارسنجی چندلایه‌ای (نام + MIME واقعی + تطبیق)
        $licenseValidation = validate_uploaded_file($file);
        if (!$licenseValidation['valid']) {
            Session::set('errors', ['license' => [$licenseValidation['error'] ?? 'فایل مجوز نامعتبر است.']]);
            $this->redirect('/admin/view/' . $applicantId);
        }
        $ext = $licenseValidation['ext'];

        $uploadDir = UPLOAD_DIR . '/' . date('Y/m');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $uniqueName)) {
            Session::set('errors', ['license' => ['خطا در ذخیرهٔ فایل.']]);
            $this->redirect('/admin/view/' . $applicantId);
        }
        @chmod($uploadDir . '/' . $uniqueName, 0640);

        $docId = $this->documentModel->create([
            'applicant_id' => $applicantId,
            'doc_type' => 'license_image',
            'file_name' => $uniqueName,
            'original_name' => sanitize_upload_filename((string)$file['name']),
            'file_path' => '/' . date('Y/m') . '/' . $uniqueName,
        ]);
        $this->licenseFileModel->upsert($applicantId, $docId);

        $this->statusLogModel->create([
            'applicant_id' => $applicantId,
            'status_name' => 'بارگذاری تصویر مجوز',
            'description' => 'تصویر مجوز توسط ' . Session::get('admin_display_name', 'مدیر') . ' بارگذاری شد.',
        ]);

        $this->redirect('/admin/view/' . $applicantId);
    }

    /**
     * صفحهٔ بررسی درخواست‌های دسترسی به تصویر مجوز
     */
    public function requests(): void
    {
        $this->requireRole(['admin']);
        $statusFilter = $_GET['status'] ?? '';
        if (!in_array($statusFilter, ['', 'در انتظار', 'تأیید شده', 'رد شده', 'در انتظار پرداخت', 'منقضی'], true)) {
            $statusFilter = '';
        }

        $this->render('admin/license_requests', [
            'title' => 'درخواست‌های تصویر مجوز',
            'requests' => $this->requestModel->getAllWithApplicant($statusFilter),
            'statusFilter' => $statusFilter,
            'reissueFee' => (int)setting('license_reissue_fee_amount', '0'),
            'message' => Session::get('license_admin_message'),
            'currentPage' => 'license-requests',
        ], 'admin_layout');
        Session::remove('license_admin_message');
    }

    /**
     * تصمیم مدیر روی درخواست دریافت مجدد
     */
    public function reviewRequest(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/license-requests');
        $this->verifyCsrf();

        $id = (int)$this->input('request_id', 0);
        $decision = (string)$this->input('decision', '');
        $amount = max(0, (int)$this->input('amount', 0));
        $reason = trim((string)$this->input('reject_reason', ''));

        $req = $this->requestModel->findById($id);
        if (!$req || $req['status'] !== 'در انتظار'
            || !in_array($decision, ['approve_free', 'approve_paid', 'reject'], true)) {
            Session::set('license_admin_message', 'درخواست یافت نشد یا قابل بررسی نیست.');
            $this->redirect('/admin/license-requests');
        }

        $this->requestModel->review($id, $decision, (int)Session::get('admin_id', 0), $amount, $reason !== '' ? $reason : null);

        $labels = [
            'approve_free' => 'درخواست دریافت مجدد تصویر مجوز تأیید شد (رایگان — لینک ۷۲ ساعته صادر شد)',
            'approve_paid' => 'درخواست دریافت مجدد تأیید مشروط به پرداخت شد — مبلغ ' . number_format($amount) . ' تومان',
            'reject' => 'درخواست دریافت مجدد تصویر مجوز رد شد' . ($reason !== '' ? ' — دلیل: ' . $reason : ''),
        ];
        $this->statusLogModel->create([
            'applicant_id' => (int)$req['applicant_id'],
            'status_name' => 'بررسی درخواست تصویر مجوز',
            'description' => $labels[$decision] . ' — توسط ' . Session::get('admin_display_name', 'مدیر'),
        ]);

        Session::set('license_admin_message', 'تصمیم ثبت شد.');
        $this->redirect('/admin/license-requests');
    }

    /* ==================== داخلی ==================== */

    private function findApplicantOrFail(): array
    {
        $code = strtoupper(trim((string)$this->input('tracking_code', '')));
        $applicant = $this->applicantModel->findByTrackingCode($code);
        if (!$applicant) {
            Session::set('tracking_error', 'کد رهگیری نامعتبر است.');
            $this->redirect('/tracking');
        }
        return $applicant;
    }
}
