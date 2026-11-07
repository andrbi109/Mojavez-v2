<?php
/**
 * مدل چرخه‌های بازبینی پرونده (فاز ۲ نقشهٔ راه)
 * ----------------------------------------------------------
 * متقاضی فرم کامل اطلاعات (مراحل ۱ تا ۳) را به‌همراه مدارک جدید پیشنهاد می‌دهد؛
 * مدیر آن را در کنار مقادیر فعلی می‌بیند و تأیید/رد می‌کند.
 * تا زمان تأیید، دادهٔ رسمی پرونده تغییر نمی‌کند.
 */
class ApplicantRevision
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $applicantId, array $payload, ?string $note = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO applicant_revisions (applicant_id, payload, note)
             VALUES (:aid, :payload, :note)"
        );
        $stmt->execute([
            'aid' => $applicantId,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'note' => $note !== null ? mb_substr($note, 0, 500) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, a.tracking_code, a.first_name, a.last_name, a.current_status
             FROM applicant_revisions r JOIN applicants a ON a.id = r.applicant_id
             WHERE r.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['payload_data'] = json_decode($row['payload'], true) ?: [];
        }
        return $row ?: null;
    }

    public function findPendingByApplicant(int $applicantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM applicant_revisions
             WHERE applicant_id = :aid AND status = 'در انتظار' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['aid' => $applicantId]);
        return $stmt->fetch() ?: null;
    }

    public function countByApplicantSince(int $applicantId, int $days): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM applicant_revisions
             WHERE applicant_id = :aid AND created_at > DATE_SUB(NOW(), INTERVAL :d DAY)"
        );
        $stmt->execute(['aid' => $applicantId, 'd' => $days]);
        return (int)$stmt->fetchColumn();
    }

    public function getAllWithApplicant(string $statusFilter = ''): array
    {
        $sql = "SELECT r.*, a.tracking_code, a.first_name, a.last_name, a.current_status,
                       u.username AS reviewer_name
                FROM applicant_revisions r
                JOIN applicants a ON a.id = r.applicant_id
                LEFT JOIN users u ON u.id = r.reviewed_by";
        $params = [];
        if ($statusFilter !== '') {
            $sql .= " WHERE r.status = :status";
            $params['status'] = $statusFilter;
        }
        $sql .= " ORDER BY (r.status = 'در انتظار') DESC, r.id DESC LIMIT 100";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countPending(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM applicant_revisions WHERE status = 'در انتظار'")->fetchColumn();
    }

    public function review(int $id, string $status, int $reviewerId, ?string $reason = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE applicant_revisions
             SET status = :status, reviewed_by = :rev, reviewed_at = NOW(), reject_reason = :reason
             WHERE id = :id AND status = 'در انتظار'"
        );
        $stmt->execute(['status' => $status, 'rev' => $reviewerId, 'reason' => $reason, 'id' => $id]);
    }

    /**
     * شمارش بازبینی‌های در انتظار (برای نشان منو)
     */
    public static function pendingBadge(): int
    {
        try {
            return (new self())->countPending();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
