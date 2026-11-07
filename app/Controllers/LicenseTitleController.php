<?php
/**
 * کنترلر مدیریت رشته‌های مجوز — فقط مدیر کل
 * -------------------------------------------------------
 * افزودن / ویرایش / حذف / غیرفعال‌سازی رشته‌ها و دسته‌های آن‌ها.
 * رشته‌های در حال استفاده در پرونده‌ها حذف نمی‌شوند (فقط غیرفعال).
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
     */
    public function index(): void
    {
        $this->requireRole(['admin']);

        $editId = (int)($_GET['edit'] ?? 0);
        $editing = $editId > 0 ? $this->model->findById($editId) : null;

        // گروه‌بندی برای نمایش
        $all = $this->model->getAll();
        $grouped = [];
        foreach ($all as $row) {
            $grouped[$row['category']][] = $row;
        }

        $this->render('admin/license_titles', [
            'title' => 'مدیریت رشته‌های مجوز',
            'grouped' => $grouped,
            'categories' => $this->model->getCategories(),
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
            ->maxLength('title', 120, 'عنوان رشته')
            ->unique('title', 'license_titles', 'title', 'این رشته', $id > 0 ? $id : null);

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/license-titles');
        }

        $payload = [
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
            Session::set('license_message', 'رشتهٔ «' . $payload['title'] . '» به گروه «' . $payload['category'] . '» افزوده شد.');
        }
        $this->redirect('/admin/license-titles');
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
        $this->redirect('/admin/license-titles');
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
                'category' => $row['category'],
                'title' => $row['title'],
                'is_active' => !(int)$row['is_active'],
                'sort_order' => (int)$row['sort_order'],
            ]);
            Session::set('license_message', (int)$row['is_active']
                ? 'رشتهٔ «' . $row['title'] . '» از فرم ثبت‌نام حذف (غیرفعال) شد.'
                : 'رشتهٔ «' . $row['title'] . '» فعال شد.');
        }
        $this->redirect('/admin/license-titles');
    }
}
