<?php
/**
 * کنترلر پیامک‌ها (فاز ۵ نقشهٔ راه)
 * ===========================================================
 * - مدیریت الگوهای پیامک (متن، وضعیت مقصد، الزام کد) — فقط admin
 * - بررسی پیامک‌های ثبت‌شده توسط متقاضیان؛ پس از تأیید، وضعیت پرونده
 *   به وضعیت مقصد الگو ارتقا می‌یابد (با رعایت مسیر یکطرفه).
 */
class SmsController extends Controller
{
    private SmsTemplate $templateModel;
    private UserSmsEntry $entryModel;
    private Applicant $applicantModel;
    private StatusLog $statusLogModel;

    public function __construct()
    {
        $this->templateModel = new SmsTemplate();
        $this->entryModel = new UserSmsEntry();
        $this->applicantModel = new Applicant();
        $this->statusLogModel = new StatusLog();
    }

    /**
     * صفحهٔ یکپارچه: الگوها + ورودی‌های ثبت‌شده
     */
    public function index(): void
    {
        $this->requireRole(['admin']);

        $statusFilter = $_GET['status'] ?? '';
        if (!in_array($statusFilter, ['', 'در انتظار', 'تأیید شده', 'رد شده'], true)) {
            $statusFilter = '';
        }
        $editId = (int)($_GET['edit'] ?? 0);

        $this->render('admin/sms', [
            'title' => 'مدیریت پیامک‌ها',
            'templates' => $this->templateModel->getAll(),
            'entries' => $this->entryModel->getAllWithApplicant($statusFilter),
            'statusFilter' => $statusFilter,
            'editing' => $editId > 0 ? $this->templateModel->findById($editId) : null,
            'statusMeta' => (require CONFIG_PATH . '/statuses.php')['statuses'],
            'formData' => Session::get('formData'),
            'errors' => Session::get('errors', []),
            'message' => Session::get('sms_message'),
            'currentPage' => 'sms',
        ], 'admin_layout');
        Session::remove('formData');
        Session::remove('errors');
        Session::remove('sms_message');
    }

    /**
     * ذخیرهٔ الگو (ایجاد/ویرایش)
     */
    public function saveTemplate(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/sms');
        $this->verifyCsrf();

        $data = $this->allInputs();
        $id = (int)($data['id'] ?? 0);
        $statusCfg = (require CONFIG_PATH . '/statuses.php')['statuses'];

        $validator = new Validator($data);
        $validator->required('title', 'عنوان الگو')
            ->maxLength('title', 120, 'عنوان الگو')
            ->required('target_status', 'وضعیت مقصد')
            ->enum('target_status', array_keys($statusCfg), 'وضعیت مقصد');

        // «مشاهده و چاپ» رویداد خودکار کاربر است — نباید مقصد پیامک باشد
        if (($data['target_status'] ?? '') === 'مشاهده و چاپ') {
            $validator->addError('target_status', '«مشاهده و چاپ» رویداد خودکار است و قابل انتساب به پیامک نیست.');
        }

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/sms');
        }

        $payload = [
            'title' => trim((string)$data['title']),
            'sample_text' => trim((string)($data['sample_text'] ?? '')) ?: null,
            'target_status' => $data['target_status'],
            'requires_code' => !empty($data['requires_code']),
            'is_active' => $id > 0 ? !empty($data['is_active']) : true,
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ];
        if ($id > 0) {
            $this->templateModel->update($id, $payload);
            Session::set('sms_message', 'الگوی «' . $payload['title'] . '» ویرایش شد.');
        } else {
            $this->templateModel->create($payload);
            Session::set('sms_message', 'الگوی «' . $payload['title'] . '» افزوده شد.');
        }
        $this->redirect('/admin/sms');
    }

    /**
     * حذف الگو (در صورت استفاده → غیرفعال)
     */
    public function deleteTemplate(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/sms');
        $this->verifyCsrf();

        $id = (int)$this->input('id', 0);
        $deleted = $this->templateModel->delete($id);
        Session::set('sms_message', $deleted ? 'الگو حذف شد.' : 'الگو به‌دلیل وجود ورودی، غیرفعال شد.');
        $this->redirect('/admin/sms');
    }

    /**
     * بررسی پیامک ثبت‌شده: تأیید (→ ارتقای وضعیت) یا رد
     */
    public function reviewEntry(): void
    {
        $this->requireRole(['admin', 'expert']);
        if (!$this->isPost()) $this->redirect('/admin/sms');
        $this->verifyCsrf();

        $id = (int)$this->input('entry_id', 0);
        $action = (string)$this->input('action', '');
        $reason = trim((string)$this->input('reject_reason', ''));

        $entry = $this->entryModel->findById($id);
        if (!$entry || $entry['status'] !== 'در انتظار' || !in_array($action, ['approve', 'reject'], true)) {
            Session::set('sms_message', 'ورودی یافت نشد یا پیش‌تر بررسی شده است.');
            $this->redirect('/admin/sms');
        }

        if ($action === 'reject') {
            $this->entryModel->review($id, 'رد شده', (int)Session::get('admin_id', 0), $reason !== '' ? $reason : null);
            $this->statusLogModel->create([
                'applicant_id' => (int)$entry['applicant_id'],
                'status_name' => 'رد پیامک ثبت‌شده',
                'description' => 'پیامک «' . $entry['template_title'] . '» رد شد'
                    . ($reason !== '' ? ' — دلیل: ' . $reason : ''),
            ]);
            Session::set('sms_message', 'ورودی رد شد.');
            $this->redirect('/admin/sms');
        }

        // تأیید — ارتقای وضعیت با رعایت workflow.php و مسیر یکطرفه
        $applicant = $this->applicantModel->findById((int)$entry['applicant_id']);
        $target = $entry['target_status'];
        $role = Session::get('admin_role', 'admin');
        $applied = false;
        $applyReason = '';

        if ($applicant) {
            // اعتبارسنجی مرکزی: workflow.php + مسیر یکطرفه + terminal
            $validation = $this->validateStatusTransition(
                $applicant['current_status'],
                $target,
                $role
            );
            if ($validation['allowed']) {
                $this->applicantModel->updateStatus((int)$applicant['id'], $target);
                $applied = true;
            } else {
                $applyReason = $validation['reason'];
            }
        }

        $this->entryModel->review($id, 'تأیید شده', (int)Session::get('admin_id', 0), null, $applied);
        $this->statusLogModel->create([
            'applicant_id' => (int)$entry['applicant_id'],
            'status_name' => $applied ? 'ارتقای وضعیت بر اساس پیامک' : 'تأیید پیامک ثبت‌شده',
            'description' => 'پیامک «' . $entry['template_title'] . '»'
                . (!empty($entry['sms_code']) ? ' با کد ' . $entry['sms_code'] : '')
                . ($applied
                    ? ' → وضعیت پرونده به «' . $target . '» ارتقا یافت.'
                    : ' تأیید شد (وضعیت پرونده تغییر نکرد: ' . ($applyReason !== '' ? $applyReason : 'علت نامشخص') . ').')
                . ' — توسط ' . Session::get('admin_display_name', 'مدیر'),
        ]);

        Session::set('sms_message', $applied
            ? 'پیامک تأیید و وضعیت پرونده به «' . $target . '» ارتقا یافت. ✔'
            : 'پیامک تأیید شد (وضعیت پرونده تغییر نکرد: ' . ($applyReason !== '' ? $applyReason : 'علت نامشخص') . ').');
        $this->redirect('/admin/sms');
    }
}
