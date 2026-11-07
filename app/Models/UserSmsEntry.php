<?php
/**
 * مدل پیامک‌های ثبت‌شده توسط متقاضیان (فاز ۵ نقشهٔ راه)
 * ---------------------------------------------------------
 * چرخه: متقاضی پیامک دریافتی از سازمان را ثبت می‌کند (در انتظار) →
 *       مدیر تأیید می‌کند → وضعیت پرونده به «وضعیت مقصد» الگو ارتقا می‌یابد
 *       (با رعایت مسیر یکطرفه) و نتیجه در تایم‌لاین ثبت می‌شود.
 */
class UserSmsEntry
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO user_sms_entries (applicant_id, sms_template_id, sms_code, sms_text, received_at)
             VALUES (:applicant_id, :sms_template_id, :sms_code, :sms_text, :received_at)"
        );
        $stmt->execute([
            'applicant_id' => (int)$data['applicant_id'],
            'sms_template_id' => (int)$data['sms_template_id'],
            'sms_code' => $data['sms_code'] ?? null,
            'sms_text' => $data['sms_text'] ?? null,
            'received_at' => $data['received_at'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, t.title AS template_title, t.target_status, t.requires_code
             FROM user_sms_entries e JOIN sms_templates t ON t.id = e.sms_template_id
             WHERE e.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getAllWithApplicant(string $statusFilter = '', int $limit = 100): array
    {
        $sql = "SELECT e.*, t.title AS template_title, t.target_status,
                       a.tracking_code, a.first_name, a.last_name, a.current_status,
                       u.username AS reviewer_name
                FROM user_sms_entries e
                JOIN sms_templates t ON t.id = e.sms_template_id
                JOIN applicants a ON a.id = e.applicant_id
                LEFT JOIN users u ON u.id = e.reviewed_by";
        $params = [];
        if ($statusFilter !== '') {
            $sql .= " WHERE e.status = :status";
            $params['status'] = $statusFilter;
        }
        $sql .= " ORDER BY (e.status = 'در انتظار') DESC, e.id DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByApplicantId(int $applicantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, t.title AS template_title, t.target_status
             FROM user_sms_entries e JOIN sms_templates t ON t.id = e.sms_template_id
             WHERE e.applicant_id = :aid ORDER BY e.id DESC"
        );
        $stmt->execute(['aid' => $applicantId]);
        return $stmt->fetchAll();
    }

    public function findPendingByApplicant(int $applicantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_sms_entries WHERE applicant_id = :aid AND status = 'در انتظار'
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['aid' => $applicantId]);
        return $stmt->fetch() ?: null;
    }

    public function countPending(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM user_sms_entries WHERE status = 'در انتظار'")->fetchColumn();
    }

    /**
     * ثبت نتیجهٔ بررسی
     * @param bool $applied آیا وضعیت پرونده نیز اعمال شد؟
     */
    public function review(int $id, string $status, int $reviewerId, ?string $rejectReason = null, bool $applied = false): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_sms_entries
             SET status = :status, reviewed_by = :reviewer, reviewed_at = NOW(),
                 reject_reason = :reason, applied_status = :applied
             WHERE id = :id AND status = 'در انتظار'"
        );
        $stmt->execute([
            'status' => $status,
            'reviewer' => $reviewerId,
            'reason' => $rejectReason,
            'applied' => $applied ? 1 : 0,
            'id' => $id,
        ]);
    }

    /**
     * شمارش ثبت‌های اخیر یک متقاضی (برای محدودسازی نرخ)
     */
    public function countRecentByApplicant(int $applicantId, int $hours = 24): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM user_sms_entries
             WHERE applicant_id = :aid AND created_at > DATE_SUB(NOW(), INTERVAL :h HOUR)"
        );
        $stmt->execute(['aid' => $applicantId, 'h' => $hours]);
        return (int)$stmt->fetchColumn();
    }
}
