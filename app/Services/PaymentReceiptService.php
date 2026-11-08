<?php
/**
 * سرویس متمرکز مدیریت رسید پرداخت (PaymentReceiptService)
 * =================================================================
 * این سرویس منطق مشترک ۲ جریان ثبت رسید پرداخت را متمرکز می‌کند:
 *   ۱) TrackingController::submitPayment — هزینهٔ ثبت‌نام
 *   ۲) LicenseController::submitReissuePayment — هزینهٔ دریافت مجدد تصویر مجوز
 *
 * مسائل برطرف‌شده:
 *   - D-D-1: ۸۰ خط منطق تکراری در ۲ کنترلر → اکنون متمرکز در submitReceipt()
 *   - D-D-2: ۳ شکل مختلف INSERT در payments → اکنون یک شکل واحد
 *   - D-D-3: PaymentController::review اکنون ممیزی می‌نویسد (این سرویس متد review را فراهم می‌کند)
 *
 * این سرویس به ApplicantRegistrationService::saveUploadedFile وابسته است
 * برای ذخیرهٔ فیزیکی فایل رسید.
 */
class PaymentReceiptService
{
    private Payment $paymentModel;
    private Document $documentModel;
    private StatusLog $statusLogModel;
    private PaymentAuditLog $auditLogModel;
    private ApplicantRegistrationService $registrationService;

    public function __construct()
    {
        $this->paymentModel         = new Payment();
        $this->documentModel        = new Document();
        $this->statusLogModel       = new StatusLog();
        $this->auditLogModel        = new PaymentAuditLog();
        $this->registrationService  = new ApplicantRegistrationService();
    }

    /**
     * اعتبارسنجی فیلدهای مشترک رسید پرداخت.
     *
     * @param array $data ورودی‌های فرم (شامل payment_channel_id, amount, paid_year/month/day, reference_code)
     * @param array $file $_FILES['receipt_file']
     * @param int|null $minAmount حداقل مبلغ اجباری (برای reissue)
     * @return array{valid: bool, errors: array, paid_at: ?string, channel: ?array, amount: int, file_validation: ?array}
     */
    public function validateReceipt(array $data, array $file, ?int $minAmount = null): array
    {
        $validator = new Validator($data);
        $validator->required('payment_channel_id', 'کانال پرداخت')
            ->unsignedInt('amount', 'مبلغ واریزی');

        $channelId = (int)($data['payment_channel_id'] ?? 0);
        $channel = null;
        if ($channelId > 0) {
            $channelModel = new PaymentChannel();
            $channel = $channelModel->findById($channelId);
            if (!$channel || !(int)$channel['is_active']) {
                $validator->addError('payment_channel_id', 'کانال پرداخت معتبر نیست.');
                $channel = null;
            }
        }

        $amount = (int)($data['amount'] ?? 0);
        if ($amount <= 0 || $amount > 1000000000) {
            $validator->addError('amount', 'مبلغ را به تومان و به‌درستی وارد کنید.');
        } elseif ($minAmount !== null && $amount < $minAmount) {
            $validator->addError('amount', 'مبلغ واریزی نباید کمتر از مبلغ تعیین‌شده (' . number_format($minAmount) . ' تومان) باشد.');
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
        $fileValidation = null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $validator->addError('receipt_file', 'بارگذاری تصویر رسید الزامی است.');
        } else {
            $fileValidation = validate_uploaded_file($file);
            if (!$fileValidation['valid']) {
                $validator->addError('receipt_file', $fileValidation['error'] ?? 'فایل رسید نامعتبر است.');
                $fileValidation = null;
            }
        }

        return [
            'valid'           => !$validator->hasErrors(),
            'errors'          => $validator->getErrors(),
            'first_error'     => $validator->firstError(),
            'paid_at'         => $paidAt,
            'channel'         => $channel,
            'amount'          => $amount,
            'channel_id'      => $channelId,
            'reference_code'  => trim((string)($data['reference_code'] ?? '')) ?: null,
            'file_validation' => $fileValidation,
        ];
    }

    /**
     * ثبت یک رسید پرداخت — متمرکز برای هر دو جریان.
     *
     * این متد:
     *   ۱) فایل رسید را ذخیره می‌کند (با الگوی یکسان)
     *   ۲) رکورد documents را با doc_type='payment_receipt' ایجاد می‌کند
     *   ۳) رکورد payments را به‌روزرسانی (اگر موجود) یا ایجاد می‌کند
     *   ۴) رکورد status_log را ثبت می‌کند
     *
     * @param int    $applicantId
     * @param array  $file        $_FILES['receipt_file']
     * @param array  $validated   خروجی validateReceipt() (شامل channel_id, amount, paid_at, reference_code, file_validation)
     * @param array  $options     گزینه‌ها:
     *   - purpose: string — 'هزینهٔ ثبت‌نام' | 'هزینهٔ دریافت مجدد تصویر مجوز'
     *   - linked_request_id: ?int — برای reissue
     *   - existing_payment_id: ?int — اگر رکورد payments موجود است (پیش‌تر مدیر ایجاد کرده)
     * @return array{success: bool, payment_id: ?int, error: ?string}
     */
    public function submitReceipt(int $applicantId, array $file, array $validated, array $options = []): array
    {
        $purpose = $options['purpose'] ?? 'هزینهٔ ثبت‌نام';
        $linkedRequestId = $options['linked_request_id'] ?? null;
        $existingPaymentId = $options['existing_payment_id'] ?? null;

        // ذخیرهٔ فایل رسید با همان الگوی واحد
        $fileResult = $this->registrationService->saveUploadedFile($file, $applicantId, 'payment_receipt');
        if (!$fileResult['success']) {
            return ['success' => false, 'payment_id' => null, 'error' => $fileResult['error'] ?? 'خطا در ذخیرهٔ فایل رسید'];
        }
        $docId = $fileResult['document_id'];

        $channelId = $validated['channel_id'];
        $amount = $validated['amount'];
        $paidAt = $validated['paid_at'];
        $referenceCode = $validated['reference_code'];

        // به‌روزرسانی رکورد موجود یا ایجاد رکورد جدید
        if ($existingPaymentId) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "UPDATE payments
                 SET payment_channel_id = :chid,
                     amount = :amt,
                     reference_code = :ref,
                     paid_at = :paid,
                     receipt_document_id = :docid,
                     status = 'در انتظار بررسی'
                 WHERE id = :pid"
            );
            $stmt->execute([
                'chid' => $channelId,
                'amt'  => $amount,
                'ref'  => $referenceCode,
                'paid' => $paidAt,
                'docid' => $docId,
                'pid'  => $existingPaymentId,
            ]);
            $paymentId = $existingPaymentId;
        } else {
            $paymentId = $this->paymentModel->create([
                'applicant_id'        => $applicantId,
                'payment_channel_id'  => $channelId,
                'purpose'             => $purpose,
                'amount'              => $amount,
                'reference_code'      => $referenceCode,
                'paid_at'             => $paidAt,
                'receipt_document_id' => $docId,
                'linked_request_id'   => $linkedRequestId,
            ]);
        }

        // ثبت در status_log
        $channelTitle = $validated['channel']['title'] ?? '';
        $this->statusLogModel->create([
            'applicant_id' => $applicantId,
            'status_name'  => 'ثبت رسید پرداخت',
            'description'  => $purpose
                . ' — ' . ($channelTitle !== '' ? $channelTitle . ' — ' : '')
                . 'مبلغ ' . number_format($amount) . ' تومان'
                . ' — در انتظار بررسی مدیر',
        ]);

        return ['success' => true, 'payment_id' => $paymentId, 'error' => null];
    }

    /**
     * بررسی و تأیید/رد یک پرداخت توسط مدیر.
     *
     * رفع D-D-3: اکنون هم status_log و هم PaymentAuditLog ثبت می‌شوند.
     *
     * @param int    $paymentId
     * @param string $decision     'approve' | 'reject'
     * @param int    $reviewerId   شناسهٔ مدیر
     * @param string $reviewerName  نام مدیر (برای لاگ)
     * @param string|null $reason  دلیل (برای رد)
     * @return array{success: bool, error: ?string}
     */
    public function reviewPayment(int $paymentId, string $decision, int $reviewerId, string $reviewerName, ?string $reason = null): array
    {
        $payment = $this->paymentModel->findById($paymentId);
        if (!$payment) {
            return ['success' => false, 'error' => 'پرداخت یافت نشد.'];
        }
        if ($payment['status'] === 'تأیید شده' || $payment['status'] === 'رد شده') {
            return ['success' => false, 'error' => 'این پرداخت قبلاً بررسی شده است.'];
        }

        $status = $decision === 'approve' ? 'تأیید شده' : 'رد شده';
        $this->paymentModel->review($paymentId, $status, $reviewerId, $reason);

        // ممیزی کامل (رفع D-D-3)
        $this->auditLogModel->log(
            $paymentId,
            $reviewerId,
            'review',
            'status',
            $payment['status'],
            $status,
            $reason ?? null
        );

        // status_log
        $this->statusLogModel->create([
            'applicant_id' => (int)$payment['applicant_id'],
            'status_name'  => $decision === 'approve' ? 'تأیید رسید پرداخت' : 'رد رسید پرداخت',
            'description'  => 'مبلغ ' . number_format((int)$payment['amount']) . ' تومان'
                . ' — توسط ' . $reviewerName
                . ($reason ? ' — دلیل: ' . $reason : ''),
        ]);

        // ارسال اعلان به کاربر دربارهٔ نتیجهٔ بررسی
        $applicantModel = new Applicant();
        $applicant = $applicantModel->findById((int)$payment['applicant_id']);
        if ($applicant && !empty($applicant['user_account_id'])) {
            try {
                $notifModel = new UserNotification();
                if ($decision === 'approve') {
                    $notifModel->create(
                        (int)$applicant['user_account_id'],
                        'payment_approved',
                        'پرداخت شما تأیید شد ✅',
                        'مبلغ ' . number_format((int)$payment['amount']) . ' تومان — کد رهگیری: ' . ($applicant['tracking_code'] ?? ''),
                        (int)$applicant['id']
                    );
                } else {
                    $notifModel->create(
                        (int)$applicant['user_account_id'],
                        'payment_rejected',
                        'پرداخت شما رد شد ❌',
                        'مبلغ ' . number_format((int)$payment['amount']) . ' تومان'
                            . ' — کد رهگیری: ' . ($applicant['tracking_code'] ?? '')
                            . ($reason ? ' — دلیل: ' . $reason : ''),
                        (int)$applicant['id']
                    );
                }
            } catch (\Throwable $e) {}
        }

        return ['success' => true, 'error' => null];
    }
}
