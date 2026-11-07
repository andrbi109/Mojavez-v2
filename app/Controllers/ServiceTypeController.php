<?php
/**
 * کنترلر مدیریت انواع خدمات (فاز ۹ نقشهٔ راه) — فقط مدیر کل
 */
class ServiceTypeController extends Controller
{
    private ServiceType $model;

    public function __construct()
    {
        $this->model = new ServiceType();
    }

    public function index(): void
    {
        $this->requireRole(['admin']);

        $services = $this->model->getAll();
        $db = Database::getInstance()->getConnection();
        $counts = [];
        foreach ($services as $sv) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM applicants WHERE service_type_id = :id");
            $stmt->execute(['id' => (int)$sv['id']]);
            $counts[(int)$sv['id']] = (int)$stmt->fetchColumn();
        }

        $editId = (int)($_GET['edit'] ?? 0);
        $this->render('admin/service_types', [
            'title' => 'مدیریت انواع خدمات',
            'services' => $services,
            'counts' => $counts,
            'editing' => $editId > 0 ? $this->model->findById($editId) : null,
            'message' => Session::get('service_message'),
            'errors' => Session::get('errors', []),
            'formData' => Session::get('formData'),
            'currentPage' => 'service-types',
        ], 'admin_layout');
        Session::remove('service_message');
        Session::remove('errors');
        Session::remove('formData');
    }

    public function save(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/service-types');
        $this->verifyCsrf();

        $data = $this->allInputs();
        $id = (int)($data['id'] ?? 0);

        $validator = new Validator($data);
        $validator->required('title', 'عنوان سرویس')
            ->maxLength('title', 120, 'عنوان سرویس')
            ->maxLength('authority', 150, 'مرجع صدور')
            ->unique('title', 'service_types', 'title', 'این سرویس', $id > 0 ? $id : null);

        if ($validator->hasErrors()) {
            Session::set('errors', $validator->getErrors());
            Session::set('formData', $data);
            $this->redirect('/admin/service-types');
        }

        $payload = [
            'title' => trim((string)$data['title']),
            'authority' => trim((string)($data['authority'] ?? '')) ?: null,
            'description' => trim((string)($data['description'] ?? '')) ?: null,
            'is_active' => $id > 0 ? !empty($data['is_active']) : true,
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ];
        if ($id > 0) {
            $this->model->update($id, $payload);
            Session::set('service_message', 'سرویس «' . $payload['title'] . '» ویرایش شد.');
        } else {
            $this->model->create($payload);
            Session::set('service_message', 'سرویس «' . $payload['title'] . '» افزوده شد.');
        }
        $this->redirect('/admin/service-types');
    }

    public function delete(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/service-types');
        $this->verifyCsrf();

        $id = (int)$this->input('id', 0);
        $deleted = $this->model->delete($id);
        Session::set('service_message', $deleted
            ? 'سرویس حذف شد.'
            : 'سرویس به‌دلیل وجود پرونده/فیلد/مرحله حذف نشد؛ غیرفعال گردید.');
        $this->redirect('/admin/service-types');
    }
}
