<?php
/**
 * مدل انواع خدمات (فاز ۹ نقشهٔ راه)
 * صنایع دستی، مشاغل خانگی (مستقل/پشتیبان)، گردشگری، هنری — قابل مدیریت از پنل
 */
class ServiceType
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getActive(): array
    {
        return $this->db->query(
            "SELECT * FROM service_types WHERE is_active = 1 ORDER BY sort_order, id"
        )->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM service_types ORDER BY sort_order, id")->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM service_types WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO service_types (title, authority, description, is_active, sort_order)
             VALUES (:title, :authority, :description, :is_active, :sort_order)"
        );
        $stmt->execute([
            'title' => $data['title'],
            'authority' => $data['authority'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE service_types SET title = :title, authority = :authority,
                    description = :description, is_active = :is_active, sort_order = :sort_order
             WHERE id = :id"
        );
        $stmt->execute([
            'title' => $data['title'],
            'authority' => $data['authority'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
            'id' => $id,
        ]);
    }

    /**
     * حذف سرویس — اگر پرونده/فیلد/مرحله به آن وصل باشد فقط غیرفعال می‌شود
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT
                (SELECT COUNT(*) FROM applicants WHERE service_type_id = :a) +
                (SELECT COUNT(*) FROM form_fields WHERE service_type_id = :b) +
                (SELECT COUNT(*) FROM wizard_steps WHERE service_type_id = :c) AS total"
        );
        $stmt->execute(['a' => $id, 'b' => $id, 'c' => $id]);
        if ((int)($stmt->fetch()['total'] ?? 0) > 0) {
            $this->db->prepare("UPDATE service_types SET is_active = 0 WHERE id = :id")->execute(['id' => $id]);
            return false;
        }
        $this->db->prepare("DELETE FROM service_types WHERE id = :id")->execute(['id' => $id]);
        return true;
    }
}
