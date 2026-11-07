<?php
/**
 * مدل درخواست‌های تغییر کد پستی
 * ---------------------------------------------------
 * گردش کار:
 *   ۱. کاربر در صفحه پیگیری درخواست تغییر ثبت می‌کند (status = در انتظار)
 *   ۲. مدیر در پنل «درخواست‌های تغییر کد پستی» آن را تأیید یا رد می‌کند
 *   ۳. در صورت تأیید، کد پستی جدید در جدول applicants اعمال می‌شود
 */
class PostalCodeChange
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO postal_code_changes (applicant_id, old_postal_code, new_postal_code)
             VALUES (:applicant_id, :old_postal_code, :new_postal_code)"
        );
        $stmt->execute([
            'applicant_id' => $data['applicant_id'],
            'old_postal_code' => $data['old_postal_code'],
            'new_postal_code' => $data['new_postal_code']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findByApplicantId(int $applicantId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM postal_code_changes WHERE applicant_id = :id ORDER BY requested_at DESC");
        $stmt->execute(['id' => $applicantId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM postal_code_changes WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * آخرین درخواست «در انتظار» یک متقاضی (برای جلوگیری از درخواست موازی)
     */
    public function findPendingByApplicantId(int $applicantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM postal_code_changes
             WHERE applicant_id = :id AND status = 'در انتظار'
             ORDER BY requested_at DESC LIMIT 1"
        );
        $stmt->execute(['id' => $applicantId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * فهرست تمام درخواست‌ها همراه با اطلاعات متقاضی (برای پنل مدیر)
     * @param string $onlyStatus فیلتر وضعیت؛ رشتهٔ خالی یعنی همه
     */
    public function getAllWithApplicant(string $onlyStatus = ''): array
    {
        $sql = "SELECT pc.*, a.first_name, a.last_name, a.tracking_code, a.current_status,
                       u.username AS reviewer_name
                FROM postal_code_changes pc
                JOIN applicants a ON a.id = pc.applicant_id
                LEFT JOIN users u ON u.id = pc.reviewed_by";
        $params = [];
        if ($onlyStatus !== '') {
            $sql .= " WHERE pc.status = :status";
            $params['status'] = $onlyStatus;
        }
        $sql .= " ORDER BY (pc.status = 'در انتظار') DESC, pc.requested_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * تعداد درخواست‌های در انتظار (برای نشان روی منوی پنل)
     */
    public function countPending(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM postal_code_changes WHERE status = 'در انتظار'")->fetchColumn();
    }

    /**
     * ثبت نتیجهٔ بررسی توسط مدیر
     * @param string $status یکی از: تأیید شده | رد شده
     */
    public function review(int $id, string $status, int $reviewerId, ?string $rejectReason = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE postal_code_changes
             SET status = :status, reviewed_by = :reviewer, reviewed_at = NOW(), reject_reason = :reason
             WHERE id = :id AND status = 'در انتظار'"
        );
        $stmt->execute([
            'status' => $status,
            'reviewer' => $reviewerId,
            'reason' => $rejectReason,
            'id' => $id,
        ]);
    }
}
