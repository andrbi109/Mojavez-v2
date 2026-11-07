<?php
/**
 * مدل گروه‌های قیمتی نرخ‌نامه (FeeGroup)
 * ---------------------------------------------------
 * ۴ گروه: الف (روستایی/دهک ۱-۲)، ب (۳-۴)، ج (۵-۶)، د (۷-۱۰)
 * هر گروه دارای: نام، آیکون، محدودهٔ دهک، مبلغ کل، پیام حمایتی
 */
class FeeGroup
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        return $this->db->query(
            "SELECT * FROM fee_groups ORDER BY sort_order, id"
        )->fetchAll();
    }

    public function active(): array
    {
        return $this->db->query(
            "SELECT * FROM fee_groups WHERE is_active = 1 ORDER BY sort_order, id"
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM fee_groups WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM fee_groups WHERE code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    /**
     * یافتن گروه متناسب با یک دهک درآمدی.
     */
    public function findByDecile(int $decile): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM fee_groups
             WHERE is_active = 1 AND :decile BETWEEN decile_min AND decile_max
             ORDER BY sort_order LIMIT 1"
        );
        $stmt->execute(['decile' => $decile]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO fee_groups
                (code, name, icon, decile_min, decile_max, is_rural_eligible,
                 total_amount, message_title, message_body, is_active, sort_order)
             VALUES
                (:code, :name, :icon, :dmin, :dmax, :rural,
                 :amount, :mtitle, :mbody, :active, :sort)"
        );
        $stmt->execute([
            'code' => $data['code'],
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'dmin' => (int)$data['decile_min'],
            'dmax' => (int)$data['decile_max'],
            'rural' => (int)($data['is_rural_eligible'] ?? 0),
            'amount' => (int)$data['total_amount'],
            'mtitle' => $data['message_title'] ?? null,
            'mbody' => $data['message_body'] ?? null,
            'active' => (int)($data['is_active'] ?? 1),
            'sort' => (int)($data['sort_order'] ?? 50),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE fee_groups SET
                name = :name, icon = :icon, decile_min = :dmin, decile_max = :dmax,
                is_rural_eligible = :rural, total_amount = :amount,
                message_title = :mtitle, message_body = :mbody,
                is_active = :active, sort_order = :sort
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'dmin' => (int)$data['decile_min'],
            'dmax' => (int)$data['decile_max'],
            'rural' => (int)($data['is_rural_eligible'] ?? 0),
            'amount' => (int)$data['total_amount'],
            'mtitle' => $data['message_title'] ?? null,
            'mbody' => $data['message_body'] ?? null,
            'active' => (int)($data['is_active'] ?? 1),
            'sort' => (int)($data['sort_order'] ?? 50),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM fee_groups WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
