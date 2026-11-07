<?php
/**
 * کنترلر مدیریت فرم ثبت‌نام (فرم‌ساز پویا) — فقط مدیر کل
 * ----------------------------------------------------------------
 * مدیر می‌تواند:
 *  - فیلد سفارشی جدید به هر مرحله (۱ تا ۴) اضافه کند
 *  - فیلدهای سفارشی را ویرایش/حذف کند
 *  - فیلدهای سیستمی «اختیاری» را فعال/غیرفعال کند (حذف سیستمی ممکن نیست)
 *  - ترتیب نمایش فیلدها را با دکمهٔ بالا/پایین تغییر دهد
 */
class FormFieldController extends Controller
{
    private FormField $fieldModel;

    public function __construct()
    {
        $this->fieldModel = new FormField();
    }

    /**
     * فهرست فیلدهای فرم به تفکیک مرحله
     */
    public function index(): void
    {
        $this->requireRole(['admin']);

        $this->render('admin/form_fields', [
            'title' => 'مدیریت فرم ثبت‌نام',
            'grouped' => $this->fieldModel->allGrouped(),
            'wizardSteps' => (new WizardStep())->getAll(),
            'message' => Session::get('field_message'),
            'currentPage' => 'form-fields',
        ], 'admin_layout');
        Session::remove('field_message');
    }

    /**
     * فرم ایجاد/ویرایش فیلد
     */
    public function editForm(int|string $id = 0): void
    {
        $this->requireRole(['admin']);
        $id = (int)$id;
        $field = $id > 0 ? $this->fieldModel->findById($id) : null;

        if ($id > 0 && !$field) {
            $this->redirect('/admin/form-fields');
        }
        // فیلد سیستمی فقط در حد «فعال/غیرفعال» و برچسب راهنما قابل تغییر است
        $this->render('admin/form_field_edit', [
            'title' => $field ? 'ویرایش فیلد فرم' : 'افزودن فیلد جدید به فرم',
            'field' => Session::get('formData') ?: $field,
            'errors' => Session::get('errors', []),
            'isEdit' => (bool)$field,
            'isSystem' => $field && (int)$field['is_system'] === 1,
            'wizardSteps' => (new WizardStep())->getAll(),
            'currentPage' => 'form-fields',
        ], 'admin_layout');
        Session::remove('errors');
        Session::remove('formData');
    }

    /**
     * ذخیرهٔ فیلد (ایجاد یا ویرایش)
     */
    public function save(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/form-fields');
        $this->verifyCsrf();

        $data = $this->allInputs();
        $id = (int)($data['id'] ?? 0);
        $existing = $id > 0 ? $this->fieldModel->findById($id) : null;

        $validator = new Validator($data);
        $validator->required('label', 'برچسب فیلد')
            ->maxLength('label', 150, 'برچسب')
            ->required('wizard_step_id', 'مرحله')
            ->required('field_type', 'نوع فیلد')
            ->enum('field_type', array_keys(FormField::TYPES), 'نوع فیلد');

        // مرحله باید وجود داشته باشد
        $stepModel = new WizardStep();
        $targetStep = $stepModel->findById((int)($data['wizard_step_id'] ?? 0));
        if (!$targetStep) {
            $validator->addError('wizard_step_id', 'مرحلهٔ انتخاب‌شده معتبر نیست.');
        }

        $type = $data['field_type'] ?? '';
        if (in_array($type, ['select', 'radio'], true) && empty(trim($data['options'] ?? ''))) {
            $validator->addError('options', 'برای نوع «فهرست» یا «رادیویی» وارد کردن گزینه‌ها (هر خط یکی) الزامی است.');
        }
        // فیلد فایلی فقط در مرحلهٔ «بارگذاری مدارک» مجاز است (فرم‌های سایر
        // مراحل multipart نیستند و ذخیرهٔ فایل بین مراحل پشتیبانی نمی‌شود)
        if ($type === 'file' && ($targetStep['view_key'] ?? '') !== 'step4') {
            $validator->addError('wizard_step_id', 'فیلد از نوع «بارگذاری فایل» فقط در مرحلهٔ «بارگذاری مدارک» قابل افزودن است.');
        }

        // قوانین اعتبارسنجی عددی
        $rules = [];
        if (!empty($data['maxlength'])) {
            $rules['maxlength'] = max(1, (int)$data['maxlength']);
        }
        if ($type === 'number') {
            if (isset($data['min_value']) && $data['min_value'] !== '') $rules['min'] = (float)$data['min_value'];
            if (isset($data['max_value']) && $data['max_value'] !== '') $rules['max'] = (float)$data['max_value'];
        }
        if (!empty($data['pattern'])) {
            // بررسی معتبر بودن الگوی regex
            if (@preg_match('/' . str_replace('/', '\/', $data['pattern']) . '/u', '') === false) {
                $validator->addError('pattern', 'الگوی اعتبارسنجی (regex) معتبر نیست.');
            } else {
                $rules['pattern'] = $data['pattern'];
            }
        }

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect($id > 0 ? '/admin/form-fields/edit/' . $id : '/admin/form-fields/create');
        }

        if ($existing && (int)$existing['is_system'] === 1) {
            // فیلد سیستمی: فقط برچسب راهنما، ترتیب و فعال بودن قابل تغییر است
            $this->fieldModel->update($id, [
                'step' => $existing['step'],
                'wizard_step_id' => (int)$existing['wizard_step_id'],
                'label' => $existing['label'],
                'field_type' => $existing['field_type'],
                'options' => $existing['options'],
                'is_required' => (int)$existing['is_required'],
                'is_active' => in_array($existing['field_key'], FormField::TOGGLABLE_SYSTEM_KEYS, true)
                    ? !empty($data['is_active']) : 1,
                'validation_json' => $existing['validation_json'],
                'placeholder' => $existing['placeholder'],
                'help_text' => trim((string)($data['help_text'] ?? '')),
                'sort_order' => (int)$existing['sort_order'],
            ]);
            Session::set('field_message', 'فیلد سیستمی به‌روزرسانی شد.');
            $this->redirect('/admin/form-fields');
        }

        $payload = [
            'step' => (int)($existing['step'] ?? $data['step'] ?? 0),
            'wizard_step_id' => (int)$data['wizard_step_id'],
            'label' => trim((string)$data['label']),
            'field_type' => $type,
            'options' => in_array($type, ['select', 'radio'], true) ? trim((string)$data['options']) : null,
            'is_required' => !empty($data['is_required']),
            'is_active' => $id > 0 ? !empty($data['is_active']) : true,
            'validation_json' => $rules ? json_encode($rules, JSON_UNESCAPED_UNICODE) : null,
            'placeholder' => trim((string)($data['placeholder'] ?? '')) ?: null,
            'help_text' => trim((string)($data['help_text'] ?? '')) ?: null,
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ];

        if ($id > 0 && $existing) {
            $this->fieldModel->update($id, $payload);
            Session::set('field_message', 'فیلد «' . $payload['label'] . '» ویرایش شد.');
        } else {
            // ساخت کلید یکتا — از کلید پیشنهادی مدیر یا برچسب
            $suggested = trim((string)($data['field_key'] ?? ''));
            $payload['field_key'] = $this->fieldModel->generateUniqueKey($suggested !== '' ? $suggested : 'f' . time());
            $this->fieldModel->create($payload);
            Session::set('field_message', 'فیلد «' . $payload['label'] . '» به مرحلهٔ ' . $payload['step'] . ' اضافه شد.');
        }
        $this->redirect('/admin/form-fields');
    }

    /**
     * فعال/غیرفعال کردن فیلد
     */
    public function toggle(int|string $id): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/form-fields');
        $this->verifyCsrf();

        $id = (int)$id;
        $field = $this->fieldModel->findById($id);
        if (!$field) $this->redirect('/admin/form-fields');

        // فیلدهای سیستمیِ اجباری هرگز غیرفعال نمی‌شوند
        if ((int)$field['is_system'] === 1
            && !in_array($field['field_key'], FormField::TOGGLABLE_SYSTEM_KEYS, true)) {
            Session::set('field_message', 'این فیلد سیستمی و اجباری است و قابل غیرفعال‌سازی نیست.');
            $this->redirect('/admin/form-fields');
        }

        $this->fieldModel->setActive($id, !(bool)$field['is_active']);
        Session::set('field_message', $field['is_active']
            ? 'فیلد «' . $field['label'] . '» از فرم حذف (غیرفعال) شد.'
            : 'فیلد «' . $field['label'] . '» دوباره به فرم اضافه (فعال) شد.');
        $this->redirect('/admin/form-fields');
    }

    /**
     * حذف فیلد سفارشی
     */
    public function delete(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/form-fields');
        $this->verifyCsrf();

        $id = (int)$this->input('id', 0);
        $field = $this->fieldModel->findById($id);
        if ($field && (int)$field['is_system'] === 1) {
            Session::set('field_message', 'فیلدهای سیستمی قابل حذف نیستند؛ در صورت نیاز غیرفعالشان کنید.');
            $this->redirect('/admin/form-fields');
        }
        if ($this->fieldModel->delete($id)) {
            Session::set('field_message', 'فیلد حذف شد (مقادیر مرتبط نیز پاک شدند).');
        }
        $this->redirect('/admin/form-fields');
    }

    /**
     * جابه‌جایی ترتیب فیلد
     */
    public function move(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/form-fields');
        $this->verifyCsrf();

        $id = (int)$this->input('id', 0);
        $dir = $this->input('direction') === 'up' ? 'up' : 'down';
        $this->fieldModel->move($id, $dir);
        $this->redirect('/admin/form-fields');
    }
}
