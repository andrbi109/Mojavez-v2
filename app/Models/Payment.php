<?php
/**
 * مدل پرداخت‌های متقاضیان (فاز ۳ و ۴ نقشهٔ راه)
 * -------------------------------------------------
 * چرخه: متقاضی رسید را ثبت می‌کند (در انتظار بررسی) →
 *       مدیر تأیید/رد می‌کند → تأییدشده‌ها در بخش درآمد گزارش می‌شوند.
 */
class Payment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO payments
                (applicant_id, payment_channel_id, purpose, amount, reference_code,
                 paid_at, receipt_document_id, linked_request_id)
             VALUES
                (:applicant_id, :payment_channel_id, :purpose, :amount, :reference_code,
                 :paid_at, :receipt_document_id, :linked_request_id)"
        );
        $stmt->execute([
            'applicant_id' => (int)$data['applicant_id'],
            'payment_channel_id' => $data['payment_channel_id'] ?? null,
            'purpose' => $data['purpose'] ?? 'هزینهٔ ثبت‌نام',
            'amount' => (int)($data['amount'] ?? 0),
            'reference_code' => $data['reference_code'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'receipt_document_id' => $data['receipt_document_id'] ?? null,
            'linked_request_id' => $data['linked_request_id'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * همهٔ پرداخت‌های یک متقاضی (برای صفحهٔ جزئیات پرونده)
     */
    public function findByApplicantId(int $applicantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.title AS channel_title
             FROM payments p LEFT JOIN payment_channels c ON c.id = p.payment_channel_id
             WHERE p.applicant_id = :aid ORDER BY p.id DESC"
        );
        $stmt->execute(['aid' => $applicantId]);
        return $stmt->fetchAll();
    }

    /**
     * آخرین پرداخت یک متقاضی با هدف مشخص
     */
    public function findLatestByApplicant(int $applicantId, string $purpose = 'هزینهٔ ثبت‌نام'): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payments WHERE applicant_id = :aid AND purpose = :purpose
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['aid' => $applicantId, 'purpose' => $purpose]);
        return $stmt->fetch() ?: null;
    }

    /**
     * آیا پرداخت تأییدشده برای این هدف وجود دارد؟
     */
    public function hasApproved(int $applicantId, string $purpose = 'هزینهٔ ثبت‌نام'): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM payments
             WHERE applicant_id = :aid AND purpose = :purpose AND status = 'تأیید شده'"
        );
        $stmt->execute(['aid' => $applicantId, 'purpose' => $purpose]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * فهرست کامل با فیلتر و اطلاعات متقاضی/کانال/بررسی‌کننده + صفحه‌بندی
     */
    public function getAllWithFilters(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$sql, $params] = $this->buildFilterSql($filters);
        $sql .= " ORDER BY (p.status = 'در انتظار بررسی') DESC, p.created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAllWithFilters(array $filters = []): int
    {
        [$sql, $params] = $this->buildFilterSql($filters);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ({$sql}) t");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function buildFilterSql(array $filters): array
    {
        $sql = "SELECT p.*, a.tracking_code, a.first_name, a.last_name, a.national_code,
                       c.title AS channel_title, u.username AS reviewer_name
                FROM payments p
                JOIN applicants a ON a.id = p.applicant_id
                LEFT JOIN payment_channels c ON c.id = p.payment_channel_id
                LEFT JOIN users u ON u.id = p.reviewed_by
                WHERE 1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['purpose'])) {
            $sql .= " AND p.purpose = :purpose";
            $params['purpose'] = $filters['purpose'];
        }
        if (!empty($filters['channel'])) {
            $sql .= " AND p.payment_channel_id = :channel";
            $params['channel'] = (int)$filters['channel'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (a.tracking_code LIKE :q OR a.national_code LIKE :q2 OR a.first_name LIKE :q3 OR a.last_name LIKE :q4 OR p.reference_code LIKE :q5)";
            $like = '%' . $filters['q'] . '%';
            $params['q'] = $like; $params['q2'] = $like; $params['q3'] = $like; $params['q4'] = $like; $params['q5'] = $like;
        }
        return [$sql, $params];
    }

    /**
     * آمار مالی — جمع تأییدشده‌ها (کل / ماه شمسی جاری) + شمار در انتظار
     * @return array{approved_total:int, approved_month:int, pending_count:int, pending_sum:int, rejected_count:int}
     */
    public function stats(): array
    {
        $approvedTotal = (int)$this->db->query(
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'تأیید شده'"
        )->fetchColumn();

        // بازهٔ ماه شمسی جاری → میلادی
        $jy = (int)jdate('Y'); $jm = (int)jdate('n'); $days = (int)jdate('t');
        [$gy1, $gm1, $gd1] = jalali_to_gregorian($jy, $jm, 1);
        [$gy2, $gm2, $gd2] = jalali_to_gregorian($jy, $jm, $days);
        $from = sprintf('%04d-%02d-%02d', $gy1, $gm1, $gd1);
        $to = sprintf('%04d-%02d-%02d', $gy2, $gm2, $gd2);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM payments
             WHERE status = 'تأیید شده' AND paid_at BETWEEN :from AND :to"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        $approvedMonth = (int)$stmt->fetchColumn();

        $pendingCount = (int)$this->db->query("SELECT COUNT(*) FROM payments WHERE status = 'در انتظار بررسی'")->fetchColumn();
        $pendingSum = (int)$this->db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'در انتظار بررسی'")->fetchColumn();
        $rejectedCount = (int)$this->db->query("SELECT COUNT(*) FROM payments WHERE status = 'رد شده'")->fetchColumn();

        return compact('approvedTotal', 'approvedMonth', 'pendingCount', 'pendingSum', 'rejectedCount');
    }

    /**
     * درآمد تأییدشده بر اساس کانال
     */
    public function sumByChannel(): array
    {
        return $this->db->query(
            "SELECT COALESCE(c.title, '—') AS channel, COUNT(*) AS cnt, COALESCE(SUM(p.amount),0) AS total
             FROM payments p LEFT JOIN payment_channels c ON c.id = p.payment_channel_id
             WHERE p.status = 'تأیید شده'
             GROUP BY c.title ORDER BY total DESC"
        )->fetchAll();
    }

    /**
     * بررسی توسط مدیر
     */
    public function review(int $id, string $status, int $reviewerId, ?string $rejectReason = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE payments SET status = :status, reviewed_by = :reviewer,
                    reviewed_at = NOW(), reject_reason = :reason
             WHERE id = :id AND status = 'در انتظار بررسی'"
        );
        $stmt->execute([
            'status' => $status,
            'reviewer' => $reviewerId,
            'reason' => $rejectReason,
            'id' => $id,
        ]);
    }
}
