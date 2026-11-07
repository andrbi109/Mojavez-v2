<?php
/**
 * کنترلر مدیریت مراحل ویزارد (فاز ۱ نقشهٔ راه) — فقط مدیر کل
 * مدیر می‌تواند مرحلهٔ سفارشی بسازد (مثلاً «سوابق بیمه»)، ترتیب دهد،
 * غیرفعال یا حذف کند. مرحلهٔ «بارگذاری مدارک» همیشه فعال و آخر است.
 */
class WizardStepController extends Controller
{
    private WizardStep $stepModel;
    private FormField $fieldModel;

    /** آیکون‌های قابل انتخاب */
    public const ICONS = ['id-card', 'vase', 'home', 'upload', 'file-text', 'book', 'award', 'money',
        'phone', 'map-pin', 'shield', 'task', 'form', 'grid', 'folder', 'chart', 'info', 'user', 'users'];

    public function __construct()
    {
        $this->stepModel = new WizardStep();
        $this->fieldModel = new FormField();
    }

    public function index(): void
    {
        $this->requireRole(['admin']);

        $steps = $this->stepModel->getAll();
        $counts = [];
        foreach ($steps as $st) {
            $counts[(int)$st['id']] = $this->fieldModel->countByStepId((int)$st['id']);
        }

        $editId = (int)($_GET['edit'] ?? 0);
        $this->render('admin/wizard_steps', [
            'title' => 'مدیریت مراحل ثبت‌نام',
            'steps' => $steps,
            'counts' => $counts,
            'icons' => self::ICONS,
            'editing' => $editId > 0 ? $this->stepModel->findById($editId) : null,
            'message' => Session::get('step_message'),
            'errors' => Session::get('errors', []),
            'formData' => Session::get('formData'),
            'currentPage' => 'wizard-steps',
        ], 'admin_layout');
        Session::remove('step_message');
        Session::remove('errors');
        Session::remove('formData');
    }

    public function save(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/wizard-steps');
        $this->verifyCsrf();

        $data = $this->allInputs();
        $id = (int)($data['id'] ?? 0);

        $validator = new Validator($data);
        $validator->required('title', 'عنوان مرحله')->maxLength('title', 100, 'عنوان مرحله');
        if (!empty($data['icon']) && !in_array($data['icon'], self::ICONS, true)) {
            $validator->addError('icon', 'آیکون انتخاب‌شده معتبر نیست.');
        }

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/wizard-steps');
        }

        $payload = [
            'title' => trim((string)$data['title']),
            'description' => trim((string)($data['description'] ?? '')) ?: null,
            'icon' => $data['icon'] ?: null,
            'sort_order' => (int)($data['sort_order'] ?? 50),
        ];
        // مرحلهٔ مدارک همیشه sort=1000 می‌ماند
        if ($id > 0) {
            $existing = $this->stepModel->findById($id);
            if ($existing && $existing['view_key'] === 'step4') {
                $payload['sort_order'] = 1000;
            }
            $this->stepModel->update($id, $payload);
            Session::set('step_message', 'مرحلهٔ «' . $payload['title'] . '» ویرایش شد.');
        } else {
            $this->stepModel->create($payload + ['is_active' => true]);
            Session::set('step_message', 'مرحلهٔ «' . $payload['title'] . '» ساخته شد — اکنون فیلدهایش را در «مدیریت فرم ثبت‌نام» بیفزایید.');
        }
        $this->redirect('/admin/wizard-steps');
    }

    public function toggle(int|string $id): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/wizard-steps');
        $this->verifyCsrf();

        $id = (int)$id;
        $step = $this->stepModel->findById($id);
        if (!$step) $this->redirect('/admin/wizard-steps');

        $newState = !(int)$step['is_active'];
        if (!$this->stepModel->setActive($id, $newState)) {
            Session::set('step_message', 'مرحلهٔ «بارگذاری مدارک» همیشه فعال است.');
            $this->redirect('/admin/wizard-steps');
        }
        Session::set('step_message', $newState
            ? 'مرحلهٔ «' . $step['title'] . '» فعال شد.'
            : 'مرحلهٔ «' . $step['title'] . '» از ویزارد حذف (غیرفعال) شد.');
        $this->redirect('/admin/wizard-steps');
    }

    public function delete(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/wizard-steps');
        $this->verifyCsrf();

        $id = (int)$this->input('id', 0);
        $result = $this->stepModel->delete($id);
        if ($result === 'has_fields') {
            Session::set('step_message', 'این مرحله هنوز فیلد دارد؛ ابتدا فیلدهایش را از «مدیریت فرم ثبت‌نام» حذف کنید.');
        } elseif ($result === true) {
            Session::set('step_message', 'مرحله حذف شد.');
        } else {
            Session::set('step_message', 'مراحل سیستمی قابل حذف نیستند؛ در صورت نیاز غیرفعالشان کنید.');
        }
        $this->redirect('/admin/wizard-steps');
    }

    public function move(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/wizard-steps');
        $this->verifyCsrf();

        $this->stepModel->move((int)$this->input('id', 0), $this->input('direction') === 'up' ? 'up' : 'down');
        $this->redirect('/admin/wizard-steps');
    }
}
