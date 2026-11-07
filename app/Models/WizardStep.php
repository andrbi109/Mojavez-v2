<?php
/**
 * مدل مراحل ویزارد ثبت‌نام (فاز ۱ نقشهٔ راه)
 * -------------------------------------------------------
 * چهار مرحلهٔ سیستمی (view_key = step1..step4) + مراحل سفارشی مدیر.
 * مرحلهٔ «بارگذاری مدارک» با sort_order=1000 همیشه آخرین است.
 */
class WizardStep
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * مراحل فعال به ترتیب — مرحلهٔ مدارک همیشه آخر
     */
    public function orderedActive(?int $serviceTypeId = null): array
    {
        if ($serviceTypeId === null) {
            $rows = $this->db->query(
                "SELECT * FROM wizard_steps WHERE is_active = 1 AND service_type_id IS NULL ORDER BY sort_order, id"
            )->fetchAll();
        } else {
            $stmt = $this->db->prepare(
                "SELECT * FROM wizard_steps
                 WHERE is_active = 1 AND (service_type_id IS NULL OR service_type_id = :svc)
                 ORDER BY sort_order, id"
            );
            $stmt->execute(['svc' => $serviceTypeId]);
            $rows = $stmt->fetchAll();
        }
        // تضمین: step4 (بارگذاری مدارک) آخر باشد
        $docs = null;
        $rest = [];
        foreach ($rows as $r) {
            if ($r['view_key'] === 'step4') {
                $docs = $r;
            } else {
                $rest[] = $r;
            }
        }
        if ($docs) {
            $rest[] = $docs;
        }
        return $rest;
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM wizard_steps ORDER BY sort_order, id")->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM wizard_steps WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByViewKey(string $key): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM wizard_steps WHERE view_key = :k AND is_active = 1 LIMIT 1");
        $stmt->execute(['k' => $key]);
        return $stmt->fetch() ?: null;
    }

    /**
     * جایگاه (۱-مبنا) یک مرحله در میان مراحل فعال
     */
    public function positionOf(int $stepId): ?int
    {
        foreach ($this->orderedActive() as $i => $st) {
            if ((int)$st['id'] === $stepId) {
                return $i + 1;
            }
        }
        return null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO wizard_steps (title, description, icon, is_system, is_active, sort_order)
             VALUES (:title, :description, :icon, 0, :is_active, :sort_order)"
        );
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 50),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE wizard_steps SET title = :title, description = :description,
                    icon = :icon, sort_order = :sort_order
             WHERE id = :id"
        );
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => (int)($data['sort_order'] ?? 50),
            'id' => $id,
        ]);
    }

    public function setActive(int $id, bool $active): bool
    {
        // مراحل سیستمیِ زیرساختی غیرفعال نمی‌شوند (مدارک همیشه لازم است؛
        // غیرفعال‌سازی سایر سیستمی‌ها مجاز است تا مدیر گردش دلخواه بچیند)
        $step = $this->findById($id);
        if (!$step) return false;
        if ($step['view_key'] === 'step4' && !$active) {
            return false; // مرحلهٔ بارگذاری مدارک همیشه فعال می‌ماند
        }
        $this->db->prepare("UPDATE wizard_steps SET is_active = :a WHERE id = :id")
            ->execute(['a' => $active ? 1 : 0, 'id' => $id]);
        return true;
    }

    /**
     * حذف مرحلهٔ سفارشی — فقط وقتی هیچ فیلدی نداشته باشد
     */
    public function delete(int $id): bool|string
    {
        $step = $this->findById($id);
        if (!$step || (int)$step['is_system'] === 1) {
            return false;
        }
        $cnt = $this->db->prepare("SELECT COUNT(*) FROM form_fields WHERE wizard_step_id = :id");
        $cnt->execute(['id' => $id]);
        if ((int)$cnt->fetchColumn() > 0) {
            return 'has_fields'; // ابتدا فیلدهایش را حذف کنید
        }
        $this->db->prepare("DELETE FROM wizard_steps WHERE id = :id")->execute(['id' => $id]);
        return true;
    }

    public function move(int $id, string $direction): void
    {
        $step = $this->findById($id);
        if (!$step) return;
        // مرحلهٔ مدارک جابه‌جا نمی‌شود
        if ($step['view_key'] === 'step4') return;

        $op = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'DESC' : 'ASC';
        $stmt = $this->db->prepare(
            "SELECT * FROM wizard_steps
             WHERE sort_order $op :sort AND view_key IS DISTINCT FROM 'step4'
             ORDER BY sort_order $order LIMIT 1"
        );
        // IS DISTINCT FROM در MariaDB پشتیبانی نمی‌شود — از NOT <=> استفاده می‌کنیم
        $stmt = $this->db->prepare(
            "SELECT * FROM wizard_steps
             WHERE sort_order $op :sort AND NOT (view_key <=> 'step4')
             ORDER BY sort_order $order LIMIT 1"
        );
        $stmt->execute(['sort' => $step['sort_order']]);
        $neighbor = $stmt->fetch();
        if ($neighbor) {
            $this->db->prepare("UPDATE wizard_steps SET sort_order = :o WHERE id = :id")
                ->execute(['o' => $neighbor['sort_order'], 'id' => $id]);
            $this->db->prepare("UPDATE wizard_steps SET sort_order = :o WHERE id = :id")
                ->execute(['o' => $step['sort_order'], 'id' => $neighbor['id']]);
        }
    }
}
