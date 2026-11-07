<?php
/**
 * مدل کانال‌های پرداخت (فاز ۳ نقشهٔ راه)
 */
class PaymentChannel
{
    private PDO $db;

    public const TYPES = ['کارت به کارت', 'حساب بانکی', 'پرداخت اینترنتی', 'سایر'];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** کانال‌های فعال برای نمایش به متقاضی */
    public function getActive(): array
    {
        return $this->db->query(
            "SELECT * FROM payment_channels WHERE is_active = 1 ORDER BY sort_order, id"
        )->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM payment_channels ORDER BY sort_order, id")->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_channels WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO payment_channels (title, channel_type, account_info, is_active, sort_order)
             VALUES (:title, :channel_type, :account_info, :is_active, :sort_order)"
        );
        $stmt->execute([
            'title' => $data['title'],
            'channel_type' => $data['channel_type'],
            'account_info' => $data['account_info'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE payment_channels SET title = :title, channel_type = :channel_type,
                    account_info = :account_info, is_active = :is_active, sort_order = :sort_order
             WHERE id = :id"
        );
        $stmt->execute([
            'title' => $data['title'],
            'channel_type' => $data['channel_type'],
            'account_info' => $data['account_info'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        // اگر پرداختی به این کانال وصل است، حذف نمی‌شود (FK SET NULL اجازه می‌دهد ولی برای شفافیت مالی نگه می‌داریم)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM payments WHERE payment_channel_id = :id");
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $this->db->prepare("UPDATE payment_channels SET is_active = 0 WHERE id = :id")->execute(['id' => $id]);
            return false;
        }
        $this->db->prepare("DELETE FROM payment_channels WHERE id = :id")->execute(['id' => $id]);
        return true;
    }
}
