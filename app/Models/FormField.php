<?php
/**
 * مدل فیلدهای فرم ثبت‌نام (فرم‌ساز پویا)
 * --------------------------------------------------------
 * دو دسته فیلد:
 *  - سیستمی (is_system=1): در کد کنترلرها پیاده‌سازی شده‌اند؛ حذف نمی‌شوند و
 *    فقط «اختیاری‌ها» قابل غیرفعال‌سازی از پنل مدیریت هستند.
 *  - سفارشی: توسط مدیر ساخته می‌شوند، اعتبارسنجی داده‌محور دارند و مقادیرشان
 *    در جدول applicant_field_values ذخیره می‌شود.
 */
class FormField
{
    private PDO $db;

    /** انواع مجاز فیلد */
    public const TYPES = [
        'text' => 'متن کوتاه',
        'number' => 'عدد',
        'textarea' => 'متن بلند',
        'select' => 'انتخاب از فهرست',
        'radio' => 'انتخاب تکی (دکمه‌های رادیویی)',
        'checkbox' => 'بله/خیر (تک‌کلیدی)',
        'date_jalali' => 'تاریخ شمسی',
        'file' => 'بارگذاری فایل (مدرک اختیاری)',
    ];

    /** فیلدهای سیستمی که مدیر می‌تواند فعال/غیرفعال کند (اختیاری‌های ذاتی) */
    public const TOGGLABLE_SYSTEM_KEYS = [
        'district', 'rural_district', 'village', 'income_decile', 'disability_details',
        'vocational_cert', 'education_degree', 'product_photos',
        'optional_1', 'optional_2', 'optional_3', 'national_card_back',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * همهٔ فیلدهای یک مرحله (برای رندر ویزارد) — فقط فعال‌ها
     * @deprecated از activeByStepId استفاده شود (فاز ۱ — مراحل پویا)
     */
    public function activeByStep(int $step): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM form_fields WHERE step = :step AND is_active = 1 ORDER BY sort_order, id"
        );
        $stmt->execute(['step' => $step]);
        return $stmt->fetchAll();
    }

    /**
     * فیلدهای فعال یک مرحله بر پایهٔ شناسهٔ wizard_steps (موتور ویزارد پویا)
     */
    public function activeByStepId(int $stepId, ?int $serviceTypeId = null): array
    {
        if ($serviceTypeId === null) {
            $stmt = $this->db->prepare(
                "SELECT * FROM form_fields
                 WHERE wizard_step_id = :sid AND is_active = 1 AND service_type_id IS NULL
                 ORDER BY sort_order, id"
            );
            $stmt->execute(['sid' => $stepId]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT * FROM form_fields
                 WHERE wizard_step_id = :sid AND is_active = 1
                   AND (service_type_id IS NULL OR service_type_id = :svc)
                 ORDER BY sort_order, id"
            );
            $stmt->execute(['sid' => $stepId, 'svc' => $serviceTypeId]);
        }
        return $stmt->fetchAll();
    }

    /**
     * شمار فیلدهای یک مرحله (برای محافظت از حذف مرحله)
     */
    public function countByStepId(int $stepId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM form_fields WHERE wizard_step_id = :sid");
        $stmt->execute(['sid' => $stepId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * همهٔ فیلدها (برای پنل مدیریت) — گروه‌بندی‌شده بر اساس مرحله
     */
    public function allGrouped(): array
    {
        $rows = $this->db->query(
            "SELECT f.*, w.title AS step_title, w.view_key, w.sort_order AS step_sort
             FROM form_fields f LEFT JOIN wizard_steps w ON w.id = f.wizard_step_id
             ORDER BY COALESCE(w.sort_order, 9999), f.sort_order, f.id"
        )->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $key = (int)($r['wizard_step_id'] ?? 0);
            $grouped[$key]['title'] = $r['step_title'] ?? ('مرحلهٔ_legacy_' . $r['step']);
            $grouped[$key]['view_key'] = $r['view_key'] ?? null;
            $grouped[$key]['fields'][] = $r;
        }
        return $grouped;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM form_fields WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM form_fields WHERE field_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        return $stmt->fetch() ?: null;
    }

    /**
     * آیا یک فیلد سیستمیِ مشخص فعال است؟ (برای رندر مشروط در ویزارد)
     */
    public function isSystemActive(string $key): bool
    {
        $f = $this->findByKey($key);
        return $f === null || (int)$f['is_active'] === 1; // نبود رکورد = فعال (پیش‌فرض سازگار)
    }

    /**
     * کلیدهای فعال همهٔ فیلدهای سیستمی (یک کوئری برای کل ویزارد)
     * @return array<string,bool>
     */
    public function systemActiveMap(): array
    {
        $rows = $this->db->query("SELECT field_key, is_active FROM form_fields WHERE is_system = 1")->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $map[$r['field_key']] = (int)$r['is_active'] === 1;
        }
        return $map;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO form_fields
                (step, wizard_step_id, field_key, label, field_type, options, is_required, is_system, is_active,
                 validation_json, placeholder, help_text, sort_order)
             VALUES
                (:step, :wizard_step_id, :field_key, :label, :field_type, :options, :is_required, 0, :is_active,
                 :validation_json, :placeholder, :help_text, :sort_order)"
        );
        $stmt->execute([
            'step' => (int)$data['step'],
            'wizard_step_id' => (int)($data['wizard_step_id'] ?? 0) ?: null,
            'field_key' => $data['field_key'],
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'options' => $data['options'] ?? null,
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'validation_json' => $data['validation_json'] ?? null,
            'placeholder' => $data['placeholder'] ?? null,
            'help_text' => $data['help_text'] ?? null,
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE form_fields SET
                step = :step, wizard_step_id = :wizard_step_id, label = :label, field_type = :field_type, options = :options,
                is_required = :is_required, is_active = :is_active,
                validation_json = :validation_json, placeholder = :placeholder,
                help_text = :help_text, sort_order = :sort_order
             WHERE id = :id"
        );
        $stmt->execute([
            'step' => (int)$data['step'],
            'wizard_step_id' => (int)($data['wizard_step_id'] ?? 0) ?: null,
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'options' => $data['options'] ?? null,
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'validation_json' => $data['validation_json'] ?? null,
            'placeholder' => $data['placeholder'] ?? null,
            'help_text' => $data['help_text'] ?? null,
            'sort_order' => (int)($data['sort_order'] ?? 100),
            'id' => $id,
        ]);
    }

    /**
     * فقط تغییر وضعیت فعال بودن (برای فیلدهای سیستمی اختیاری و سفارشی)
     */
    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare("UPDATE form_fields SET is_active = :a WHERE id = :id");
        $stmt->execute(['a' => $active ? 1 : 0, 'id' => $id]);
    }

    /**
     * حذف فیلد سفارشی (سیستمی‌ها قابل حذف نیستند)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM form_fields WHERE id = :id AND is_system = 0");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * جابه‌جایی ترتیب با همسایه (بالا/پایین)
     */
    public function move(int $id, string $direction): void
    {
        $field = $this->findById($id);
        if (!$field) return;

        $op = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'DESC' : 'ASC';
        $stmt = $this->db->prepare(
            "SELECT * FROM form_fields
             WHERE step = :step AND sort_order $op :sort
             ORDER BY sort_order $order LIMIT 1"
        );
        $stmt->execute(['step' => $field['step'], 'sort' => $field['sort_order']]);
        $neighbor = $stmt->fetch();
        if ($neighbor) {
            // جابه‌جایی ترتیب دو رکورد
            $this->db->prepare("UPDATE form_fields SET sort_order = :o WHERE id = :id")
                ->execute(['o' => $neighbor['sort_order'], 'id' => $id]);
            $this->db->prepare("UPDATE form_fields SET sort_order = :o WHERE id = :id")
                ->execute(['o' => $field['sort_order'], 'id' => $neighbor['id']]);
        }
    }

    /**
     * تجزیهٔ قوانین اعتبارسنجی ذخیره‌شده
     */
    public static function parseRules(?string $json): array
    {
        if (!$json) return [];
        $rules = json_decode($json, true);
        return is_array($rules) ? $rules : [];
    }

    /**
     * گزینه‌های select/radio — هر خط یک گزینه
     */
    public static function parseOptions(?string $text): array
    {
        if (!$text) return [];
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), fn($l) => $l !== ''));
    }

    /**
     * ساخت کلید فنی یکتا از برچسب فارسی (ترجیحاً مدیر کلید انگلیسی وارد کند)
     */
    public function generateUniqueKey(string $base): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base) ?: 'field';
        $key = 'dyn_' . strtolower(substr($base, 0, 40));
        $i = 1;
        while ($this->findByKey($key)) {
            $key = 'dyn_' . strtolower(substr($base, 0, 36)) . '_' . (++$i);
        }
        return $key;
    }
}
