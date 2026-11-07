<?php
/**
 * کنترلر تنظیمات سامانه — فقط مدیر کل
 * ------------------------------------------------
 * هویت سامانه، آیدی شبکه‌های اجتماعی، قالب پیام اطلاع‌رسانی متقاضی
 * و محتوای صفحهٔ «دربارهٔ ما» از اینجا مدیریت می‌شوند.
 */
class SettingController extends Controller
{
    private Setting $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    /**
     * فرم تنظیمات
     */
    public function index(): void
    {
        $this->requireRole(['admin']);

        $this->render('admin/settings', [
            'title' => 'تنظیمات سامانه',
            'settings' => $this->settingModel->all(),
            'message' => Session::get('settings_message'),
            'errors' => Session::get('errors', []),
            'formData' => Session::get('formData'),
            'currentPage' => 'settings',
        ], 'admin_layout');
        Session::remove('settings_message');
        Session::remove('errors');
        Session::remove('formData');
    }

    /**
     * ذخیرهٔ تنظیمات (فقط کلیدهای فهرست سفید)
     */
    public function save(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/settings');
        $this->verifyCsrf();

        $data = $this->allInputs();
        $validator = new Validator($data);
        $validator->required('site_name', 'نام سامانه')
            ->maxLength('site_name', 100, 'نام سامانه')
            ->required('owner_name', 'نام مالک سامانه')
            ->maxLength('owner_name', 100, 'نام مالک')
            ->maxLength('success_message_template', 2000, 'قالب پیام');

        // آیدی شبکه‌های اجتماعی: فقط حروف/عدد/آندرلاین
        foreach (['owner_username', 'social_eitaa', 'social_rubika', 'social_bale'] as $key) {
            $val = trim((string)($data[$key] ?? ''));
            if ($val !== '' && !preg_match('/^[A-Za-z0-9_]{3,40}$/', $val)) {
                $validator->addError($key, 'آیدی فقط می‌تواند شامل حروف انگلیسی، عدد و _ باشد (۳ تا ۴۰ کاراکتر).');
            }
        }

        // قالب پیام باید دست‌کم یکی از جای‌نماها را داشته باشد
        $tpl = (string)($data['success_message_template'] ?? '');
        if ($tpl !== '' && !str_contains($tpl, '{code}') && !str_contains($tpl, '{کد رهگیری}')) {
            $validator->addError('success_message_template', 'قالب پیام باید شامل جای‌نمای {code} (کد رهگیری) باشد.');
        }

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/settings');
        }

        // مبالغ باید عددی نامنفی باشند
        foreach (['registration_fee_amount', 'license_reissue_fee_amount'] as $feeKey) {
            $fv = trim((string)($data[$feeKey] ?? ''));
            if ($fv !== '' && (!ctype_digit($fv) || (int)$fv > 1000000000)) {
                $validator->addError($feeKey, 'مبلغ باید عددی بر حسب تومان باشد (حداکثر ۱٬۰۰۰٬۰۰۰٬۰۰۰).');
            }
        }
        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/settings');
        }

        $allowed = Setting::editableKeys();
        $toSave = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $toSave[$key] = trim((string)$data[$key]);
            }
        }
        $this->settingModel->setMany($toSave);

        Session::set('settings_message', 'تنظیمات با موفقیت ذخیره شد. ✔');
        $this->redirect('/admin/settings');
    }

    /**
     * بارگذاری تصویر پروفایل مدیر/مالک سامانه
     * -------------------------------------------------
     * تصویر در public/assets/img/owner.{ext} ذخیره می‌شود (فایل عمومی برندینگ،
     * بدون اطلاعات حساس) و مسیر آن در تنظیم «owner_image» ثبت می‌گردد.
     * در صورت نبود تصویر، از آیکون پیش‌فرض استفاده می‌شود.
     */
    public function uploadImage(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/settings');
        $this->verifyCsrf();

        // حذف تصویر فعلی
        if (!empty($_POST['remove_image'])) {
            $this->deleteOwnerImage();
            $this->settingModel->set('owner_image', '');
            Session::set('settings_message', 'تصویر مدیر حذف شد؛ آیکون پیش‌فرض نمایش داده می‌شود.');
            $this->redirect('/admin/settings');
        }

        $file = $_FILES['owner_image'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::set('settings_message', 'فایلی برای بارگذاری انتخاب نشد.');
            $this->redirect('/admin/settings');
        }

        // اعتبارسنجی: فقط تصویر واقعی و حداکثر ۲ مگابایت
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            Session::set('settings_message', 'حجم تصویر بیش از ۲ مگابایت است.');
            $this->redirect('/admin/settings');
        }
        $realMime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$realMime])) {
            Session::set('settings_message', 'فقط تصویر JPG یا PNG معتبر پذیرفته می‌شود.');
            $this->redirect('/admin/settings');
        }

        $dir = ROOT_PATH . '/public/assets/img';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->deleteOwnerImage(); // پاکسازی نسخهٔ قبلی (هر پسوند)

        $ext = $allowed[$realMime];
        $dest = $dir . '/owner.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::set('settings_message', 'خطا در ذخیرهٔ تصویر.');
            $this->redirect('/admin/settings');
        }
        @chmod($dest, 0644); // فایل عمومی است (برندینگ)

        // مسیر نسبی + شمارهٔ نسخه برای شکستن کش مرورگر
        $this->settingModel->set('owner_image', 'assets/img/owner.' . $ext . '?v=' . time());
        Session::set('settings_message', 'تصویر مدیر با موفقیت بارگذاری شد. ✔');
        $this->redirect('/admin/settings');
    }

    /**
     * حذف فایل‌های تصویر قدیمی مدیر
     */
    private function deleteOwnerImage(): void
    {
        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            $old = ROOT_PATH . '/public/assets/img/owner.' . $ext;
            if (is_file($old)) {
                @unlink($old);
            }
        }
    }
}
