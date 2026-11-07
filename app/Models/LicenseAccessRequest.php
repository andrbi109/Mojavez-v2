<?php
/**
 * مدل درخواست‌های دسترسی به تصویر مجوز
 * ------------------------------------------------------
 * چرخهٔ عمر:
 *  - دریافت اول: خودکار (is_first_access=1) → بلافاصله «تأیید شده» + توکن ۷۲ ساعته
 *  - دفعات بعد: «در انتظار» → تأیید مدیر (رایگان → توکن؛ یا با مبلغ → «در انتظار پرداخت»)
 *    → پس از تأیید رسید پرداخت، توکن صادر می‌شود.
 */
class LicenseAccessRequest
{
    private PDO $db;

    /** مدت اعتبار لینک (ساعت) */
    public const TOKEN_HOURS = 72;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM license_access_requests WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM license_access_requests WHERE access_token = :t LIMIT 1");
        $stmt->execute(['t' => $token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * آخرین درخواست یک متقاضی (برای جلوگیری از درخواست موازی)
     */
    public function findLatestByApplicant(int $applicantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM license_access_requests WHERE applicant_id = :aid ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['aid' => $applicantId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * لینک معتبر جاری (تأییدشده و منقضی‌نشده) — برای نمایش در صفحهٔ پیگیری
     */
    public function findValidToken(int $applicantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM license_access_requests
             WHERE applicant_id = :aid AND status = 'تأیید شده'
               AND access_token IS NOT NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['aid' => $applicantId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * ایجاد درخواست (دریافت اول = خودکار تأیید)
     */
    public function create(int $applicantId, bool $isFirst, bool $requiresPayment = false, int $amount = 0): int
    {
        $token = null;
        $status = 'در انتظار';
        $expires = null;
        if ($isFirst) {
            // دریافت اول: فوری و خودکار
            $token = bin2hex(random_bytes(32));
            $status = 'تأیید شده';
            $expires = date('Y-m-d H:i:s', time() + self::TOKEN_HOURS * 3600);
        }
        $stmt = $this->db->prepare(
            "INSERT INTO license_access_requests
                (applicant_id, is_first_access, access_token, status, requires_payment, amount, expires_at)
             VALUES (:aid, :first, :token, :status, :reqpay, :amount, :expires)"
        );
        $stmt->execute([
            'aid' => $applicantId,
            'first' => $isFirst ? 1 : 0,
            'token' => $token,
            'status' => $status,
            'reqpay' => $requiresPayment ? 1 : 0,
            'amount' => $amount,
            'expires' => $expires,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * تصمیم مدیر روی درخواست در انتظار
     * @param string $decision approve_free | approve_paid | reject
     */
    public function review(int $id, string $decision, int $reviewerId, int $amount = 0, ?string $reason = null): void
    {
        if ($decision === 'approve_free') {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + self::TOKEN_HOURS * 3600);
            $stmt = $this->db->prepare(
                "UPDATE license_access_requests
                 SET status = 'تأیید شده', access_token = :token, expires_at = :expires,
                     requires_payment = 0, reviewed_by = :rev, reviewed_at = NOW()
                 WHERE id = :id AND status = 'در انتظار'"
            );
            $stmt->execute(['token' => $token, 'expires' => $expires, 'rev' => $reviewerId, 'id' => $id]);
        } elseif ($decision === 'approve_paid') {
            $stmt = $this->db->prepare(
                "UPDATE license_access_requests
                 SET status = 'در انتظار پرداخت', requires_payment = 1, amount = :amount,
                     reviewed_by = :rev, reviewed_at = NOW()
                 WHERE id = :id AND status = 'در انتظار'"
            );
            $stmt->execute(['amount' => $amount, 'rev' => $reviewerId, 'id' => $id]);
        } else { // reject
            $stmt = $this->db->prepare(
                "UPDATE license_access_requests
                 SET status = 'رد شده', reject_reason = :reason, reviewed_by = :rev, reviewed_at = NOW()
                 WHERE id = :id AND status = 'در انتظار'"
            );
            $stmt->execute(['reason' => $reason, 'rev' => $reviewerId, 'id' => $id]);
        }
    }

    /**
     * صدور توکن پس از تأیید رسید پرداخت (از PaymentController صدا زده می‌شود)
     */
    public function grantAfterPayment(int $requestId): void
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + self::TOKEN_HOURS * 3600);
        $stmt = $this->db->prepare(
            "UPDATE license_access_requests
             SET status = 'تأیید شده', access_token = :token, expires_at = :expires
             WHERE id = :id AND status = 'در انتظار پرداخت'"
        );
        $stmt->execute(['token' => $token, 'expires' => $expires, 'id' => $requestId]);
    }

    /**
     * اتصال پرداخت به درخواست (هنگام ثبت رسید توسط متقاضی)
     */
    public function attachPayment(int $requestId, int $paymentId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE license_access_requests SET payment_id = :pid WHERE id = :id"
        );
        $stmt->execute(['pid' => $paymentId, 'id' => $requestId]);
        $this->db->prepare("UPDATE payments SET linked_request_id = :rid WHERE id = :pid")
            ->execute(['rid' => $requestId, 'pid' => $paymentId]);
    }

    /**
     * فهرست کامل برای پنل مدیر (در انتظارها اول)
     */
    public function getAllWithApplicant(string $statusFilter = ''): array
    {
        $sql = "SELECT lar.*, a.tracking_code, a.first_name, a.last_name,
                       u.username AS reviewer_name
                FROM license_access_requests lar
                JOIN applicants a ON a.id = lar.applicant_id
                LEFT JOIN users u ON u.id = lar.reviewed_by";
        $params = [];
        if ($statusFilter !== '') {
            $sql .= " WHERE lar.status = :status";
            $params['status'] = $statusFilter;
        }
        $sql .= " ORDER BY (lar.status IN ('در انتظار','در انتظار پرداخت')) DESC, lar.id DESC LIMIT 200";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countPending(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM license_access_requests WHERE status IN ('در انتظار','در انتظار پرداخت')"
        )->fetchColumn();
    }

    /**
     * انقضای تنبل (lazy): توکن‌های گذشته‌اعتبار علامت‌گذاری می‌شوند
     */
    public static function expireStale(): void
    {
        Database::getInstance()->getConnection()->exec(
            "UPDATE license_access_requests SET status = 'منقضی'
             WHERE status = 'تأیید شده' AND expires_at IS NOT NULL AND expires_at < NOW()"
        );
    }
}
