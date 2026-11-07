<?php
/**
 * مدل لاگ ممیزی پرداخت‌ها (PaymentAuditLog)
 * ---------------------------------------------------
 * تمام تغییرات مدیر روی پرداخت‌ها (تیک‌ها، تخفیف، تأیید/رد) ثبت می‌شود.
 */
class PaymentAuditLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log(int $paymentId, int $userId, string $action, ?string $field = null, $oldValue = null, $newValue = null, ?string $note = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO payment_audit_log
                (payment_id, user_id, action, field_changed, old_value, new_value, note)
             VALUES
                (:pid, :uid, :action, :field, :old, :new, :note)"
        );
        $stmt->execute([
            'pid' => $paymentId,
            'uid' => $userId,
            'action' => $action,
            'field' => $field,
            'old' => $oldValue !== null ? (string)$oldValue : null,
            'new' => $newValue !== null ? (string)$newValue : null,
            'note' => $note,
        ]);
    }

    public function findByPaymentId(int $paymentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.username, u.display_name
             FROM payment_audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.payment_id = :pid
             ORDER BY a.created_at DESC"
        );
        $stmt->execute(['pid' => $paymentId]);
        return $stmt->fetchAll();
    }
}
