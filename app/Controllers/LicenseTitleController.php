<?php
/**
 * کنترلر مدیریت رشته‌های مجوز — فقط مدیر کل
 * -------------------------------------------------------
 * افزودن / ویرایش / حذف / غیرفعال‌سازی رشته‌ها و دسته‌های آن‌ها.
 * رشته‌های در حال استفاده در پرونده‌ها حذف نمی‌شوند (فقط غیرفعال).
 *
 * هر رشته به یک سرویس خاص وصل می‌شود (service_type_id) تا هر سرویس
 * فهرست اختصاصی خود را داشته باشد.
 */
class LicenseTitleController extends Controller
{
    private LicenseTitle $model;

    public function __construct()
    {
        $this->model = new LicenseTitle();
    }

    /**
     * فهرست رشته‌ها + فرم افزودن/ویرایش درون‌خطی
     * پارامتر GET service=<id> برای فیلتر بر اساس سرویس
     */
    public function index(): void
    {
        $this->requireRole(['admin']);

        $editId = (int)($_GET['edit'] ?? 0);
        $editing = $editId > 0 ? $this->model->findById($editId) : null;

        // فیلتر سرویس: از GET ?service=N، وگرنه از رکورد ویرایش، وگرنه همهٔ سرویس‌ها
        $filterServiceId = (int)($_GET['service'] ?? 0);
        if ($filterServiceId <= 0 && $editing) {
            $filterServiceId = (int)($editing['service_type_id'] ?? 0);
        }
        $activeFilter = $filterServiceId > 0 ? $filterServiceId : null;

        $all = $this->model->getAll($activeFilter);

        // گروه‌بندی برای نمایش — ابتدا بر اساس سرویس، سپس بر اساس دسته
        $grouped = [];
        foreach ($all as $row) {
            $serviceKey = $row['service_title'] ?? 'بدون سرویس';
            $grouped[$serviceKey][$row['category']][] = $row;
        }

        $this->render('admin/license_titles', [
            'title' => 'مدیریت رشته‌های مجوز',
            'grouped' => $grouped,
            'categories' => $this->model->getCategories($activeFilter),
            'services' => (new ServiceType())->getAll(),
            'filterServiceId' => $filterServiceId,
            'editing' => $editing,
            'message' => Session::get('license_message'),
            'errors' => Session::get('errors', []),
            'formData' => Session::get('formData'),
            'currentPage' => 'license-titles',
        ], 'admin_layout');
        Session::remove('license_message');
        Session::remove('errors');
        Session::remove('formData');
    }

    /**
     * ذخیرهٔ رشتهٔ جدید یا ویرایش رشتهٔ موجود
     */
    public function save(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/license-titles');
        $this->verifyCsrf();

        $data = $this->allInputs();
        $id = (int)($data['id'] ?? 0);

        $validator = new Validator($data);
        $validator->required('category', 'دسته/گروه رشته')
            ->maxLength('category', 80, 'دسته')
            ->required('title', 'عنوان رشته')
            ->maxLength('title', 120, 'عنوان رشته');

        // بررسی یکتایی عنوان در دامنهٔ سرویس انتخاب‌شده (به‌جای یکتایی سراسری)
        $serviceTypeId = !empty($data['service_type_id']) ? (int)$data['service_type_id'] : null;
        if (!$this->model->isTitleUniqueInService(
            trim((string)$data['title']),
            $serviceTypeId,
            $id > 0 ? $id : null
        )) {
            $validator->addError('title', 'این رشته برای سرویس انتخاب‌شده قبلاً ثبت شده است.');
        }

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/license-titles' . ($serviceTypeId ? '?service=' . $serviceTypeId : ''));
        }

        $payload = [
            'service_type_id' => $serviceTypeId,
            'category' => trim((string)$data['category']),
            'title' => trim((string)$data['title']),
            'is_active' => !empty($data['is_active']),
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ];

        if ($id > 0) {
            $this->model->update($id, $payload);
            Session::set('license_message', 'رشتهٔ «' . $payload['title'] . '» ویرایش شد.');
        } else {
            $this->model->create($payload);
            Session::set('license_message', 'رشتهٔ «' . $payload['title'] . '» به سرویس انتخاب‌شده افزوده شد.');
        }
        $this->redirect('/admin/license-titles' . ($serviceTypeId ? '?service=' . $serviceTypeId : ''));
    }

    /**
     * حذف رشته (در صورت استفاده در پرونده‌ها → غیرفعال می‌شود)
     */
    public function delete(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/license-titles');
        $this->verifyCsrf();

        $id = (int)$this->input('id', 0);
        $row = $this->model->findById($id);
        if (!$row) $this->redirect('/admin/license-titles');

        $deleted = $this->model->delete($id);
        Session::set('license_message', $deleted
            ? 'رشتهٔ «' . $row['title'] . '» حذف شد.'
            : 'رشتهٔ «' . $row['title'] . '» در پرونده‌ها استفاده شده است؛ به‌جای حذف، غیرفعال شد.');
        $redirect = '/admin/license-titles' . (!empty($row['service_type_id']) ? '?service=' . (int)$row['service_type_id'] : '');
        $this->redirect($redirect);
    }

    /**
     * فعال/غیرفعال کردن سریع
     */
    public function toggle(int|string $id): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/license-titles');
        $this->verifyCsrf();

        $id = (int)$id;
        $row = $this->model->findById($id);
        if ($row) {
            $this->model->update($id, [
                'service_type_id' => $row['service_type_id'] ?? null,
                'category' => $row['category'],
                'title' => $row['title'],
                'is_active' => !(int)$row['is_active'],
                'sort_order' => (int)$row['sort_order'],
            ]);
            Session::set('license_message', (int)$row['is_active']
                ? 'رشتهٔ «' . $row['title'] . '» از فرم ثبت‌نام حذف (غیرفعال) شد.'
                : 'رشتهٔ «' . $row['title'] . '» فعال شد.');
        }
        $redirect = '/admin/license-titles' . (!empty($row['service_type_id']) ? '?service=' . (int)$row['service_type_id'] : '');
        $this->redirect($redirect);
    }

    /**
     * API JSON — بازگرداندن رشته‌های فعال یک سرویس برای فرم‌های پویا
     * GET /admin/license-titles/by-service/{id}
     *
     * این متد برای زمانی است که کاربر در فرم ثبت‌نام یا ویرایش، نوع
     * خدمت را تغییر می‌دهد و باید فهرست عناوین به‌روز شود.
     */
    public function byService(int|string $serviceId): void
    {
        $this->requireRole(['admin']);
        $serviceId = (int)$serviceId;
        $grouped = $this->model->groupedActiveByService($serviceId > 0 ? $serviceId : null);
        $this->json([
            'success' => true,
            'service_id' => $serviceId,
            'groups' => $grouped,
        ]);
    }
}
