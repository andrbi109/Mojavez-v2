<?php
/**
 * مدل تیک خدمات انجام‌شده (PaymentServiceTick)
 * ---------------------------------------------------
 * برای هر پرداخت، ۱۲ ردیف تیک ایجاد می‌شود (پیش‌فرض همگی انجام‌شده).
 * مدیر می‌تواند خدمات انجام‌نشده را تیک بردارد → مبلغ نهایی کسر می‌شود.
 */
class PaymentServiceTick
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByPaymentId(int $paymentId): array
    {
        // کوئری با ORDER BY id به‌عنوان fallback امن — sort_order ممکن است در دیتابیس‌های قدیمی موجود نباشد
        // اما در CREATE TABLE فعلی موجود است، پس مشکلی نیست
        $stmt = $this->db->prepare(
            "SELECT * FROM payment_service_ticks
             WHERE payment_id = :pid
             ORDER BY sort_order, id"
        );
        try {
            $stmt->execute(['pid' => $paymentId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            // fallback: اگر ستون sort_order موجود نبود (دیتابیس قدیمی)
            $stmt = $this->db->prepare(
                "SELECT * FROM payment_service_ticks
                 WHERE payment_id = :pid
                 ORDER BY id"
            );
            $stmt->execute(['pid' => $paymentId]);
            return $stmt->fetchAll();
        }
    }

    /**
     * ایجاد تیک‌های اولیه برای یک پرداخت جدید — همه تیک‌خورده.
     *
     * @param int $paymentId
     * @param int $groupId گروه قیمتی محاسبه‌شده
     * @return int تعداد تیک‌های ایجادشده
     */
    public function createForPayment(int $paymentId, int $groupId): int
    {
        $items = (new FeeGroupItem())->findByGroupId($groupId);
        if (!$items) return 0;

        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        $stmt = $this->db->prepare(
            "INSERT INTO payment_service_ticks
                (payment_id, item_key, item_label, original_amount, is_done, ticked_by, ticked_at, untick_reason, sort_order)
             VALUES
                (:pid, :k, :label, :amt, 1, :uid, NOW(), NULL, :sort)"
        );
        $count = 0;
        foreach ($items as $item) {
            $stmt->execute([
                'pid' => $paymentId,
                'k' => $item['item_key'],
                'label' => $item['item_label'],
                'amt' => (int)$item['amount'],
                'uid' => $adminId,
                'sort' => (int)($item['sort_order'] ?? 0),
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * تغییر وضعیت یک تیک (done/undone).
     */
    public function setDone(int $tickId, bool $done, ?string $reason = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE payment_service_ticks
             SET is_done = :done, untick_reason = :reason, ticked_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            'done' => $done ? 1 : 0,
            'reason' => $reason,
            'id' => $tickId,
        ]);
    }

    /**
     * محاسبهٔ جمع مبالغ خدمات انجام‌شده برای یک پرداخت.
     */
    public function sumDone(int $paymentId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(original_amount), 0)
             FROM payment_service_ticks
             WHERE payment_id = :pid AND is_done = 1"
        );
        $stmt->execute(['pid' => $paymentId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * تعداد تیک‌های برداشته‌شده.
     */
    public function countUnticked(int $paymentId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM payment_service_ticks
             WHERE payment_id = :pid AND is_done = 0"
        );
        $stmt->execute(['pid' => $paymentId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * مجموع مبلغ همهٔ خدمات (برای مقایسه با original_amount).
     */
    public function sumAll(int $paymentId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(original_amount), 0)
             FROM payment_service_ticks
             WHERE payment_id = :pid"
        );
        $stmt->execute(['pid' => $paymentId]);
        return (int)$stmt->fetchColumn();
    }
}
