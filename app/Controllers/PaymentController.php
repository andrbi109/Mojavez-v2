<?php
/**
 * کنترلر مالی: پرداخت‌ها و کانال‌های پرداخت (فاز ۴ نقشهٔ راه)
 * ----------------------------------------------------------------
 * بخش «درآمد» پنل مدیریت: فهرست پرداخت‌ها با فیلتر، تأیید/رد رسید،
 * آمار مالی (کل/ماه شمسی)، درآمد بر اساس کانال، خروجی CSV و مدیریت کانال‌ها.
 */
class PaymentController extends Controller
{
    private Payment $paymentModel;
    private PaymentChannel $channelModel;
    private StatusLog $statusLogModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->channelModel = new PaymentChannel();
        $this->statusLogModel = new StatusLog();
    }

    /**
     * صفحهٔ درآمد — فهرست پرداخت‌ها + آمار
     */
    public function index(): void
    {
        $this->requireRole(['admin']);

        $filters = [
            'status' => $_GET['status'] ?? '',
            'purpose' => $_GET['purpose'] ?? '',
            'channel' => $_GET['channel'] ?? '',
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        // فیلتر وضعیت/هدف از فهرست مجاز
        if (!in_array($filters['status'], ['', 'در انتظار بررسی', 'تأیید شده', 'رد شده'], true)) $filters['status'] = '';
        if (!in_array($filters['purpose'], ['', 'هزینهٔ ثبت‌نام', 'هزینهٔ دریافت مجدد تصویر مجوز'], true)) $filters['purpose'] = '';

        $perPage = 20;
        $total = $this->paymentModel->countAllWithFilters($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));

        $this->render('admin/payments', [
            'title' => 'درآمد و پرداخت‌ها',
            'payments' => $this->paymentModel->getAllWithFilters($filters, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'stats' => $this->paymentModel->stats(),
            'byChannel' => $this->paymentModel->sumByChannel(),
            'channels' => $this->channelModel->getAll(),
            'message' => Session::get('payment_message'),
            'currentPage' => 'payments',
        ], 'admin_layout');
        Session::remove('payment_message');
    }

    /**
     * تأیید یا رد رسید پرداخت
     */
    public function review(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/payments');
        $this->verifyCsrf();

        $id = (int)$this->input('payment_id', 0);
        $action = $this->input('action', '');
        $reason = trim((string)$this->input('reject_reason', ''));

        $payment = $this->paymentModel->findById($id);
        if (!$payment || $payment['status'] !== 'در انتظار بررسی' || !in_array($action, ['approve', 'reject'], true)) {
            Session::set('payment_message', 'پرداخت یافت نشد یا پیش‌تر بررسی شده است.');
            $this->redirect('/admin/payments');
        }

        // استفاده از سرویس متمرکز — شامل PaymentAuditLog (رفع D-D-3) و status_log
        $paymentService = new PaymentReceiptService();
        $result = $paymentService->reviewPayment(
            $id,
            $action,
            (int)Session::get('admin_id', 0),
            Session::get('admin_display_name', 'مدیر'),
            $reason !== '' ? $reason : null
        );

        if (!$result['success']) {
            Session::set('payment_message', $result['error']);
            $this->redirect('/admin/payments');
        }

        // اگر پرداخت «هزینهٔ دریافت مجدد تصویر مجوز» تأیید شد، توکن دسترسی صادر می‌شود (فاز ۶)
        if ($action === 'approve' && $payment['purpose'] === 'هزینهٔ دریافت مجدد تصویر مجوز'
            && !empty($payment['linked_request_id']) && class_exists('LicenseAccessRequest')) {
            (new LicenseAccessRequest())->grantAfterPayment((int)$payment['linked_request_id']);
        }

        Session::set('payment_message', $action === 'approve'
            ? 'پرداخت #' . $id . ' تأیید شد. ✔'
            : 'پرداخت #' . $id . ' رد شد.');
        $this->redirect('/admin/payments');
    }

    /**
     * خروجی CSV پرداخت‌ها (با حفظ فیلترها)
     */
    public function csv(): void
    {
        $this->requireRole(['admin']);
        $filters = [
            'status' => $_GET['status'] ?? '',
            'purpose' => $_GET['purpose'] ?? '',
            'channel' => $_GET['channel'] ?? '',
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        $rows = $this->paymentModel->getAllWithFilters($filters);

        $headers = ['شناسه', 'کد رهگیری', 'متقاضی', 'کد ملی', 'هدف پرداخت', 'مبلغ (تومان)',
            'کانال', 'کد پیگیری', 'تاریخ واریز', 'وضعیت', 'بررسی‌کننده', 'تاریخ بررسی'];
        $out = [];
        foreach ($rows as $p) {
            $out[] = [
                (string)$p['id'],
                $p['tracking_code'],
                $p['first_name'] . ' ' . $p['last_name'],
                $p['national_code'],
                $p['purpose'],
                (string)$p['amount'],
                $p['channel_title'] ?? '—',
                $p['reference_code'] ?? '',
                persianDate((string)($p['paid_at'] ?? '')),
                $p['status'],
                $p['reviewer_name'] ?? '',
                persianDate((string)($p['reviewed_at'] ?? '')),
            ];
        }
        CsvExporter::export('گزارش_پرداخت‌ها_' . jdate('Y-m-d'), $headers, $out);
    }

    /* ==================== مدیریت کانال‌های پرداخت ==================== */

    /**
     * ذخیرهٔ کانال (ایجاد/ویرایش)
     */
    public function saveChannel(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/payments');
        $this->verifyCsrf();

        $data = $this->allInputs();
        $id = (int)($data['id'] ?? 0);

        $validator = new Validator($data);
        $validator->required('title', 'عنوان کانال')
            ->maxLength('title', 100, 'عنوان کانال')
            ->required('channel_type', 'نوع کانال')
            ->enum('channel_type', PaymentChannel::TYPES, 'نوع کانال')
            ->required('account_info', 'اطلاعات حساب/کارت')
            ->maxLength('account_info', 500, 'اطلاعات حساب');

        if ($validator->hasErrors()) {
            Session::set('payment_message', 'خطا در ذخیرهٔ کانال: ' . $validator->firstError());
            $this->redirect('/admin/payments');
        }

        $payload = [
            'title' => trim((string)$data['title']),
            'channel_type' => $data['channel_type'],
            'account_info' => trim((string)$data['account_info']),
            'is_active' => !empty($data['is_active']),
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ];
        if ($id > 0) {
            $this->channelModel->update($id, $payload);
            Session::set('payment_message', 'کانال «' . $payload['title'] . '» ویرایش شد.');
        } else {
            $this->channelModel->create($payload);
            Session::set('payment_message', 'کانال «' . $payload['title'] . '» افزوده شد.');
        }
        $this->redirect('/admin/payments');
    }

    /**
     * حذف کانال (در صورت استفاده → غیرفعال)
     */
    public function deleteChannel(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/payments');
        $this->verifyCsrf();

        $id = (int)$this->input('id', 0);
        $deleted = $this->channelModel->delete($id);
        Session::set('payment_message', $deleted
            ? 'کانال حذف شد.'
            : 'کانال به‌دلیل وجود تراکنش حذف نشد؛ غیرفعال گردید.');
        $this->redirect('/admin/payments');
    }
}
