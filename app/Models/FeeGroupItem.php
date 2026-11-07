<?php
/**
 * مدل آیتم‌های هر گروه قیمتی (FeeGroupItem)
 * ---------------------------------------------------
 * هر گروه ۱۲ خدمت دارد. مدیر می‌تواند مبالغ را ویرایش کند.
 */
class FeeGroupItem
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByGroupId(int $groupId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM fee_group_items
             WHERE fee_group_id = :gid
             ORDER BY sort_order, id"
        );
        $stmt->execute(['gid' => $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * آیتم‌های همهٔ گروه‌ها به‌صورت گروه‌بندی‌شده.
     */
    public function groupedByFeeGroup(): array
    {
        $rows = $this->db->query(
            "SELECT i.*, g.code AS group_code, g.name AS group_name
             FROM fee_group_items i
             JOIN fee_groups g ON g.id = i.fee_group_id
             ORDER BY g.sort_order, i.sort_order"
        )->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['group_code']][] = $r;
        }
        return $grouped;
    }

    public function upsert(int $groupId, string $key, string $label, int $amount, int $sortOrder = 0, ?string $notes = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO fee_group_items (fee_group_id, item_key, item_label, amount, notes, sort_order)
             VALUES (:gid, :k, :label, :amount, :notes, :sort)
             ON DUPLICATE KEY UPDATE item_label = :label2, amount = :amount2, notes = :notes2, sort_order = :sort2"
        );
        $stmt->execute([
            'gid' => $groupId, 'k' => $key, 'label' => $label,
            'amount' => $amount, 'notes' => $notes, 'sort' => $sortOrder,
            'label2' => $label, 'amount2' => $amount, 'notes2' => $notes, 'sort2' => $sortOrder,
        ]);
    }

    public function deleteByGroupId(int $groupId): void
    {
        $stmt = $this->db->prepare("DELETE FROM fee_group_items WHERE fee_group_id = :gid");
        $stmt->execute(['gid' => $groupId]);
    }
}
