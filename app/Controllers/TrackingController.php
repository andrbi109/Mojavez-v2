<?php
/**
 * کنترلر پیگیری درخواست با کد رهگیری
 * ----------------------------------------------------
 * نکات امنیتی:
 *  - همهٔ فرم‌های POST با توکن CSRF محافظت می‌شوند.
 *  - جستجوی کد رهگیری محدودسازی نرخ دارد (ضد حدس زدن کد).
 *  - کاربر امکان ویرایش اطلاعات را ندارد؛ فقط «درخواست تغییر کد پستی»
 *    و «مشاهدهٔ نسخهٔ چاپی» مجاز است.
 */
class TrackingController extends Controller
{
    private Applicant $applicantModel;
    private StatusLog $statusLogModel;
    private PostalCodeChange $postalCodeChangeModel;
    private JobDetail $jobDetailModel;
    private PropertyDetail $propertyDetailModel;
    private Document $documentModel;
    private Payment $paymentModel;
    private PaymentChannel $channelModel;

    public function __construct()
    {
        $this->applicantModel = new Applicant();
        $this->statusLogModel = new StatusLog();
        $this->postalCodeChangeModel = new PostalCodeChange();
        $this->jobDetailModel = new JobDetail();
        $this->propertyDetailModel = new PropertyDetail();
        $this->documentModel = new Document();
        $this->paymentModel = new Payment();
        $this->channelModel = new PaymentChannel();
    }

    /**
     * صفحه جستجوی کد رهگیری
     */
    public function search(): void
    {
        // اجرای لغو خودکار قبل از نمایش (بدون cron job)
        AutoCancelService::cancelExpired();
        AutoCancelService::updatePriorities();

        $this->render('tracking/search', [
            'title' => 'پیگیری درخواست',
            'error' => Session::get('tracking_error')
        ]);
        Session::remove('tracking_error');
    }

    /**
     * پردازش جستجو و نمایش وضعیت
     */
    public function find(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/tracking');
        }
        $this->verifyCsrf();

        // محدودسازی نرخ: حداکثر ۱۰ جستجو در دقیقه برای هر نشست
        if (Session::isLimited('tracking_search', 10, 60)) {
            Session::set('tracking_error', 'تعداد جستجوهای شما بیش از حد مجاز است. لطفاً یک دقیقه صبر کنید.');
            $this->redirect('/tracking');
        }

        $code = strtoupper(trim((string)$this->input('tracking_code', '')));
        if ($code === '') {
            Session::set('tracking_error', 'کد رهگیری را وارد کنید.');
            $this->redirect('/tracking');
        }

        Session::hit('tracking_search');

        $applicant = $this->applicantModel->findByTrackingCode($code);
        if (!$applicant) {
            Session::set('tracking_error', 'کد رهگیری یافت نشد. لطفاً کد را بررسی کنید.');
            $this->redirect('/tracking');
        }

        // یافتن موفق — بازگرداندن آدرس مستقیم وضعیت
        $this->redirect('/tracking/status/' . rawurlencode($applicant['tracking_code']));
    }

    /**
     * نمایش وضعیت و تاریخچه
     */
    public function status(string $trackingCode): void
    {
        $applicant = $this->applicantModel->findByTrackingCode(strtoupper(trim($trackingCode)));
        if (!$applicant) {
            Session::set('tracking_error', 'کد رهگیری نامعتبر است.');
            $this->redirect('/tracking');
        }

        // اجرای لغو خودکار و سپس بارگذاری مجدد وضعیت (تا لغوهای جدید فوراً دیده شوند)
        AutoCancelService::cancelExpired();
        AutoCancelService::updatePriorities();
        $applicant = $this->applicantModel->findByTrackingCode($applicant['tracking_code']) ?? $applicant;

        $statusLogs = $this->statusLogModel->findByApplicantId((int)$applicant['id']);
        $postalChanges = $this->postalCodeChangeModel->findByApplicantId((int)$applicant['id']);
        $pendingPostal = $this->postalCodeChangeModel->findPendingByApplicantId((int)$applicant['id']);

        // اطلاعات پرداخت هزینهٔ ثبت‌نام (فاز ۳)
        $latestPayment = $this->paymentModel->findLatestByApplicant((int)$applicant['id'], 'هزینهٔ ثبت‌نام');

        // چرخهٔ بازبینی پرونده (فاز ۲)
        $revModel = new ApplicantRevision();
        $pendingRevision = $revModel->findPendingByApplicant((int)$applicant['id']);

        // پیامک‌های ثبت‌شده توسط متقاضی (فاز ۵)
        $smsModel = new UserSmsEntry();
        $smsTemplates = (new SmsTemplate())->getActive();
        $smsEntries = $smsModel->findByApplicantId((int)$applicant['id']);
        $pendingSms = $smsModel->findPendingByApplicant((int)$applicant['id']);

        // تصویر مجوز (فاز ۶) — فقط وقتی وضعیت پرونده به صدور/تحویل رساده باشد
        LicenseAccessRequest::expireStale();
        $licenseFile = (new LicenseFile())->findByApplicantId((int)$applicant['id']);
        $licenseStatuses = ['صدور مجوز', 'تحویل مجوز'];
        $licenseVisible = $licenseFile && in_array($applicant['current_status'], $licenseStatuses, true);
        $licenseReq = $licenseVisible ? (new LicenseAccessRequest())->findLatestByApplicant((int)$applicant['id']) : null;
        $licenseValidToken = $licenseVisible ? (new LicenseAccessRequest())->findValidToken((int)$applicant['id']) : null;
        $reissuePayment = $licenseVisible
            ? $this->paymentModel->findLatestByApplicant((int)$applicant['id'], 'هزینهٔ دریافت مجدد تصویر مجوز')
            : null;

        $this->render('tracking/status', [
            'title' => 'وضعیت درخواست',
            'applicant' => $applicant,
            'statusLogs' => $statusLogs,
            'postalChanges' => $postalChanges,
            'pendingPostal' => $pendingPostal,
            'paymentRequired' => (int)$applicant['payment_required'] === 1,
            'feeAmount' => FeeCalculatorService::calculateForApplicant((int)$applicant['id'])['amount'],
            'paymentChannels' => $this->channelModel->getActive(),
            'latestPayment' => $latestPayment,
            'changePostalError' => Session::get('change_postal_error'),
            'changePostalSuccess' => Session::get('change_postal_success'),
            'paymentError' => Session::get('payment_error'),
            'paymentSuccess' => Session::get('payment_success'),
            'licenseFile' => $licenseVisible ? $licenseFile : null,
            'licenseReq' => $licenseReq,
            'licenseValidToken' => $licenseValidToken,
            'reissuePayment' => $reissuePayment,
            'reissueFee' => (int)setting('license_reissue_fee_amount', '0'),
            'licenseMessage' => Session::get('license_message'),
            'smsTemplates' => $smsTemplates,
            'smsEntries' => $smsEntries,
            'pendingSms' => $pendingSms,
            'smsError' => Session::get('sms_error'),
            'smsSuccess' => Session::get('sms_success'),
            'pendingRevision' => $pendingRevision,
            'revisionError' => Session::get('revision_error'),
            'revisionSuccess' => Session::get('revision_success'),
        ]);

        Session::remove('change_postal_error');
        Session::remove('change_postal_success');
        Session::remove('payment_error');
        Session::remove('payment_success');
        Session::remove('license_message');
        Session::remove('sms_error');
        Session::remove('sms_success');
        Session::remove('revision_error');
        Session::remove('revision_success');
    }

    /**
     * درخواست تغییر کد پستی
     * طبق نیازمندی: کد پستی بلافاصله تغییر نمی‌کند؛ فقط درخواست + تاریخچه ثبت
     * می‌شود و پس از تأیید مدیر در پنل، اعمال می‌گردد.
     */
    public function changePostalCode(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/tracking');
        }
        $this->verifyCsrf();

        $trackingCode = strtoupper(trim((string)$this->input('tracking_code', '')));
        $newPostalCode = trim((string)$this->input('new_postal_code', ''));

        $applicant = $this->applicantModel->findByTrackingCode($trackingCode);
        if (!$applicant) {
            Session::set('tracking_error', 'کد رهگیری نامعتبر است.');
            $this->redirect('/tracking');
        }

        // محدودسازی نرخ: حداکثر ۳ درخواست در ساعت
        if (Session::isLimited('postal_change', 3, 3600)) {
            Session::set('change_postal_error', 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً بعداً تلاش کنید.');
            $this->redirect('/tracking/status/' . rawurlencode($trackingCode));
        }

        // اعتبارسنجی کد پستی جدید
        $validator = new Validator(['new_postal_code' => $newPostalCode]);
        $validator->required('new_postal_code', 'کد پستی جدید')
                  ->postalCode('new_postal_code', 'کد پستی جدید');

        if ($newPostalCode === $applicant['postal_code']) {
            $validator->addError('new_postal_code', 'کد پستی جدید با کد پستی فعلی یکسان است.');
        }

        if ($validator->hasErrors()) {
            Session::set('change_postal_error', $validator->firstError());
            $this->redirect('/tracking/status/' . rawurlencode($trackingCode));
        }

        // اگر درخواست در انتظارِ بررسی وجود دارد، درخواست جدید ثبت نشود
        if ($this->postalCodeChangeModel->findPendingByApplicantId((int)$applicant['id'])) {
            Session::set('change_postal_error', 'یک درخواست تغییر کد پستی در انتظار بررسی دارید. پس از بررسی آن، درخواست جدید ثبت کنید.');
            $this->redirect('/tracking/status/' . rawurlencode($trackingCode));
        }

        Session::hit('postal_change');

        // ثبت درخواست تغییر
        $this->postalCodeChangeModel->create([
            'applicant_id' => $applicant['id'],
            'old_postal_code' => $applicant['postal_code'],
            'new_postal_code' => $newPostalCode
        ]);

        // ثبت رویداد در تاریخچهٔ پرونده
        $this->statusLogModel->create([
            'applicant_id' => $applicant['id'],
            'status_name' => 'درخواست تغییر کد پستی',
            'description' => "از {$applicant['postal_code']} به {$newPostalCode} (در انتظار بررسی مدیر)"
        ]);

        Session::set('change_postal_success', 'درخواست تغییر کد پستی ثبت شد و پس از بررسی مدیر اعمال خواهد شد.');
        $this->redirect('/tracking/status/' . rawurlencode($trackingCode));
    }

    /**
     * ثبت رسید پرداخت هزینه توسط متقاضی (فاز ۳ نقشهٔ راه)
     * شرایط: مدیر «درخواست پرداخت» را روی پرونده فعال کرده باشد و
     * پرداخت در انتظار/تأییدشدهٔ قبلی برای همین هدف وجود نداشته باشد.
     */
    public function submitPayment(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/tracking');
        }
        $this->verifyCsrf();

        $trackingCode = strtoupper(trim((string)$this->input('tracking_code', '')));
        $applicant = $this->applicantModel->findByTrackingCode($trackingCode);
        if (!$applicant) {
            Session::set('tracking_error', 'کد رهگیری نامعتبر است.');
            $this->redirect('/tracking');
        }
        $back = '/tracking/status/' . rawurlencode($trackingCode);

        // محدودسازی نرخ: ۳ ثبت رسید در روز
        if (Session::isLimited('payment_submit', 3, 86400)) {
            Session::set('payment_error', 'تعداد دفعات ثبت رسید امروز بیش از حد مجاز است.');
            $this->redirect($back);
        }

        // شرایط پذیرش
        if ((int)$applicant['payment_required'] !== 1) {
            Session::set('payment_error', 'پرداخت هزینه برای این پرونده فعال نشده است.');
            $this->redirect($back);
        }
        $latest = $this->paymentModel->findLatestByApplicant((int)$applicant['id'], 'هزینهٔ ثبت‌نام');
        if ($latest && in_array($latest['status'], ['در انتظار بررسی', 'تأیید شده'], true)) {
            Session::set('payment_error', 'یک رسید قبلی در وضعیت «' . $latest['status'] . '» دارید؛ ثبت رسید جدید ممکن نیست.');
            $this->redirect($back);
        }

        // اعتبارسنجی فیلدها
        $data = $this->allInputs();
        $validator = new Validator($data);
        $validator->required('payment_channel_id', 'کانال پرداخت')
            ->required('amount', 'مبلغ واریزی')
            ->unsignedInt('amount', 'مبلغ واریزی');

        $channelId = (int)($data['payment_channel_id'] ?? 0);
        $channel = $channelId > 0 ? $this->channelModel->findById($channelId) : null;
        if (!$channel || !(int)$channel['is_active']) {
            $validator->addError('payment_channel_id', 'کانال پرداخت معتبر نیست.');
        }
        $amount = (int)($data['amount'] ?? 0);
        if ($amount <= 0 || $amount > 1000000000) {
            $validator->addError('amount', 'مبلغ را به تومان و به‌درستی وارد کنید.');
        }

        // تاریخ واریز شمسی → میلادی
        $paidAt = null;
        if (!empty($data['paid_year']) && !empty($data['paid_month']) && !empty($data['paid_day'])) {
            $jy = (int)$data['paid_year']; $jm = (int)$data['paid_month']; $jd = (int)$data['paid_day'];
            if (is_valid_persian_date($jy, $jm, $jd)) {
                $paidAt = persian_to_gregorian($jy, $jm, $jd);
            } else {
                $validator->addError('paid_at', 'تاریخ واریز معتبر نیست.');
            }
        } else {
            $validator->addError('paid_at', 'تاریخ واریز الزامی است.');
        }

        // فایل رسید
        $file = $_FILES['receipt_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $validator->addError('receipt_file', 'بارگذاری تصویر رسید پرداخت الزامی است.');
        } else {
            // اعتبارسنجی چندلایه‌ای (نام + MIME واقعی + تطبیق)
            $validation = validate_uploaded_file($file);
            if (!$validation['valid']) {
                $validator->addError('receipt_file', $validation['error'] ?? 'فایل رسید نامعتبر است.');
            }
        }

        if ($validator->hasErrors()) {
            Session::set('payment_error', $validator->firstError());
            $this->redirect($back);
        }

        Session::hit('payment_submit');

        // ذخیرهٔ تصویر رسید (با پسوند تأیید شده از اعتبارسنجی)
        $validation = validate_uploaded_file($file);
        $ext = $validation['ext'];
        $uploadDir = UPLOAD_DIR . '/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        // ext از validation بالا گرفته شده است (پسوند تأیید شده)
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $uniqueName)) {
            Session::set('payment_error', 'خطا در ذخیرهٔ فایل رسید.');
            $this->redirect($back);
        }
        @chmod($uploadDir . '/' . $uniqueName, 0640);

        $docId = $this->documentModel->create([
            'applicant_id' => (int)$applicant['id'],
            'doc_type' => 'payment_receipt',
            'file_name' => $uniqueName,
            'original_name' => basename((string)$file['name']),
            'file_path' => '/' . date('Y/m') . '/' . $uniqueName,
        ]);

        $this->paymentModel->create([
            'applicant_id' => (int)$applicant['id'],
            'payment_channel_id' => $channelId,
            'purpose' => 'هزینهٔ ثبت‌نام',
            'amount' => $amount,
            'reference_code' => trim((string)($data['reference_code'] ?? '')) ?: null,
            'paid_at' => $paidAt,
            'receipt_document_id' => $docId,
        ]);

        $this->statusLogModel->create([
            'applicant_id' => (int)$applicant['id'],
            'status_name' => 'ثبت رسید پرداخت',
            'description' => ($channel['title'] ?? '') . ' — مبلغ ' . number_format($amount) . ' تومان'
                . ' — در انتظار بررسی مدیر',
        ]);

        Session::set('payment_success', 'رسید پرداخت ثبت شد و پس از بررسی مدیر، نتیجه در همین صفحه و تایم‌لاین پرونده نمایش داده می‌شود.');
        $this->redirect($back);
    }

    /**
     * ثبت پیامک دریافتی از سازمان توسط متقاضی (فاز ۵ نقشهٔ راه)
     * پس از تأیید مدیر، وضعیت پرونده به وضعیت مقصد الگو ارتقا می‌یابد.
     */
    public function submitSms(): void
    {
        if (!$this->isPost()) $this->redirect('/tracking');
        $this->verifyCsrf();

        $trackingCode = strtoupper(trim((string)$this->input('tracking_code', '')));
        $applicant = $this->applicantModel->findByTrackingCode($trackingCode);
        if (!$applicant) {
            Session::set('tracking_error', 'کد رهگیری نامعتبر است.');
            $this->redirect('/tracking');
        }
        $back = '/tracking/status/' . rawurlencode($trackingCode);

        // محدودسازی نرخ: ۳ ثبت در روز (مبتنی بر دیتابیس — نه فقط سشن)
        $smsModel = new UserSmsEntry();
        if ($smsModel->countRecentByApplicant((int)$applicant['id'], 24) >= 3) {
            Session::set('sms_error', 'تعداد پیامک‌های ثبت‌شدهٔ امروز بیش از حد مجاز (۳) است.');
            $this->redirect($back);
        }
        if ($smsModel->findPendingByApplicant((int)$applicant['id'])) {
            Session::set('sms_error', 'یک پیامک در انتظار بررسی دارید؛ پس از بررسی آن، پیامک جدید ثبت کنید.');
            $this->redirect($back);
        }

        $data = $this->allInputs();
        $templateId = (int)($data['sms_template_id'] ?? 0);
        $template = $templateId > 0 ? (new SmsTemplate())->findById($templateId) : null;
        if (!$template || !(int)$template['is_active']) {
            Session::set('sms_error', 'نوع پیامک را انتخاب کنید.');
            $this->redirect($back);
        }

        $validator = new Validator($data);
        $code = trim((string)($data['sms_code'] ?? ''));
        $text = trim((string)($data['sms_text'] ?? ''));
        if ((int)$template['requires_code'] === 1) {
            $validator->required('sms_code', 'کد/شمارهٔ پیامک');
            if ($code !== '' && mb_strlen($code) > 50) {
                $validator->addError('sms_code', 'کد پیامک بیش از حد طولانی است.');
            }
        }
        if ($text !== '' && mb_strlen($text) > 1000) {
            $validator->addError('sms_text', 'متن پیامک بیش از حد طولانی است.');
        }
        if ($code === '' && $text === '') {
            $validator->addError('sms_text', 'حداقل کد یا متن پیامک را وارد کنید.');
        }

        // تاریخ دریافت (شمسی → میلادی)
        $receivedAt = null;
        if (!empty($data['sms_year']) && !empty($data['sms_month']) && !empty($data['sms_day'])) {
            $jy = (int)$data['sms_year']; $jm = (int)$data['sms_month']; $jd = (int)$data['sms_day'];
            if (is_valid_persian_date($jy, $jm, $jd)) {
                $receivedAt = persian_to_gregorian($jy, $jm, $jd);
            } else {
                $validator->addError('sms_received', 'تاریخ دریافت پیامک معتبر نیست.');
            }
        } else {
            $validator->addError('sms_received', 'تاریخ دریافت پیامک الزامی است.');
        }

        if ($validator->hasErrors()) {
            Session::set('sms_error', $validator->firstError());
            $this->redirect($back);
        }

        $smsModel->create([
            'applicant_id' => (int)$applicant['id'],
            'sms_template_id' => $templateId,
            'sms_code' => $code !== '' ? mb_substr($code, 0, 50) : null,
            'sms_text' => $text !== '' ? mb_substr($text, 0, 1000) : null,
            'received_at' => $receivedAt,
        ]);

        $this->statusLogModel->create([
            'applicant_id' => (int)$applicant['id'],
            'status_name' => 'ثبت پیامک توسط متقاضی',
            'description' => 'پیامک نوع «' . $template['title'] . '»' . ($code !== '' ? ' با کد ' . $code : '') . ' — در انتظار بررسی مدیر',
        ]);

        Session::set('sms_success', 'پیامک ثبت شد؛ پس از بررسی و تأیید مدیر، وضعیت پرونده به‌روزرسانی می‌شود.');
        $this->redirect($back);
    }

    /**
     * نمایش نسخه چاپی پرونده
     * طبق نیازمندی، هر بار مشاهده یک رویداد «مشاهده و چاپ پرونده» ثبت می‌شود.
     */
    public function printView(string $trackingCode): void
    {
        $applicant = $this->applicantModel->findByTrackingCode(strtoupper(trim($trackingCode)));
        if (!$applicant) {
            $this->redirect('/tracking');
        }

        // ثبت رویداد مشاهده و چاپ
        $this->statusLogModel->create([
            'applicant_id' => $applicant['id'],
            'status_name' => 'مشاهده و چاپ پرونده',
            'description' => 'کاربر نسخه چاپی پرونده را مشاهده کرد.'
        ]);

        $this->render('print', [
            'title' => 'پرونده قابل چاپ - ' . $applicant['tracking_code'],
            'applicant' => $applicant,
            'jobDetail' => $this->jobDetailModel->findByApplicantId((int)$applicant['id']),
            'propertyDetail' => $this->propertyDetailModel->findByApplicantId((int)$applicant['id']),
            'documents' => $this->documentModel->findByApplicantId((int)$applicant['id']),
            'statusLogs' => $this->statusLogModel->findByApplicantId((int)$applicant['id']),
            'postalChanges' => $this->postalCodeChangeModel->findByApplicantId((int)$applicant['id']),
            'dynValues' => (new ApplicantFieldValue())->findByApplicantId((int)$applicant['id']),
        ], 'layout_print'); // قالب مخصوص چاپ
    }
}
