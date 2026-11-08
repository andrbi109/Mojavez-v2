<?php
/**
 * مدل متقاضیان
 */
class Applicant
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * ایجاد متقاضی جدید
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO applicants 
                (first_name, last_name, national_code, postal_code, mobile, 
                birth_date, father_name, marital_status, children_count, 
                physical_condition, disability_details, income_decile, 
                has_court_case, has_active_loan, has_overdue_installments, 
                tracking_code, service_type_id, applicant_type, user_account_id,
                current_status, registration_started_at)
            VALUES 
                (:first_name, :last_name, :national_code, :postal_code, :mobile,
                :birth_date, :father_name, :marital_status, :children_count,
                :physical_condition, :disability_details, :income_decile,
                :has_court_case, :has_active_loan, :has_overdue_installments,
                :tracking_code, :service_type_id, :applicant_type, :user_account_id,
                'پیش‌نویس', NOW())"
        );

        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'national_code' => $data['national_code'],
            'postal_code' => $data['postal_code'] ?? '',
            'mobile' => $data['mobile'],
            'birth_date' => $data['birth_date'] ?? null,
            'father_name' => $data['father_name'] ?? null,
            'marital_status' => $data['marital_status'] ?? 'مجرد',
            'children_count' => $data['children_count'] ?? 0,
            'physical_condition' => $data['physical_condition'] ?? 'سالم',
            'disability_details' => $data['disability_details'] ?? null,
            'income_decile' => $data['income_decile'] ?? null,
            'has_court_case' => $data['has_court_case'] ?? 'خیر',
            'has_active_loan' => $data['has_active_loan'] ?? 'خیر',
            'has_overdue_installments' => $data['has_overdue_installments'] ?? 'خیر',
            'tracking_code' => $data['tracking_code'],
            'service_type_id' => $data['service_type_id'] ?? null,
            'applicant_type' => $data['applicant_type'] ?? 'حقیقی',
            'user_account_id' => $data['user_account_id'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * به‌روزرسانی وضعیت و تکمیل ثبت‌نام
     */
    public function completeRegistration(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE applicants SET current_status = 'تکمیل اطلاعات', registration_completed_at = NOW(), updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * به‌روزرسانی کامل رکورد متقاضی (برای ویرایش توسط مدیر).
     * national_code و tracking_code عمداً به‌روزرسانی نمی‌شوند (لنگر هویت).
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE applicants SET
                first_name = :first_name,
                last_name = :last_name,
                postal_code = :postal_code,
                mobile = :mobile,
                birth_date = :birth_date,
                father_name = :father_name,
                marital_status = :marital_status,
                children_count = :children_count,
                physical_condition = :physical_condition,
                disability_details = :disability_details,
                income_decile = :income_decile,
                has_court_case = :has_court_case,
                has_active_loan = :has_active_loan,
                has_overdue_installments = :has_overdue_installments,
                service_type_id = :service_type_id,
                updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'postal_code' => $data['postal_code'] ?? '',
            'mobile' => $data['mobile'] ?? '',
            'birth_date' => $data['birth_date'] ?: null,
            'father_name' => $data['father_name'] ?? null,
            'marital_status' => $data['marital_status'] ?? 'مجرد',
            'children_count' => (int)($data['children_count'] ?? 0),
            'physical_condition' => $data['physical_condition'] ?? 'سالم',
            'disability_details' => $data['disability_details'] ?? null,
            'income_decile' => !empty($data['income_decile']) ? (int)$data['income_decile'] : null,
            'has_court_case' => $data['has_court_case'] ?? 'خیر',
            'has_active_loan' => $data['has_active_loan'] ?? 'خیر',
            'has_overdue_installments' => $data['has_overdue_installments'] ?? 'خیر',
            'service_type_id' => !empty($data['service_type_id']) ? (int)$data['service_type_id'] : null,
            'id' => $id,
        ]);
    }

    /**
     * یافتن با کد رهگیری
     */
    public function findByTrackingCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM applicants WHERE tracking_code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    /**
     * یافتن با شناسه
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM applicants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * به‌روزرسانی وضعیت
     */
    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE applicants SET current_status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * دریافت لیست متقاضیان با فیلتر، مرتب‌سازی و صفحه‌بندی
     * @param int $limit صفر یعنی بدون محدودیت (برای گزارش‌گیری)
     */
    public function getAll(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$sql, $params] = $this->buildFilterQuery($filters);

        $sql .= " ORDER BY a.priority DESC, a.registration_completed_at DESC, a.id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * شمارش کل نتایج یک فیلتر (برای صفحه‌بندی)
     */
    public function countAll(array $filters = []): int
    {
        [$sql] = $this->buildFilterQuery($filters);
        $countSql = "SELECT COUNT(*) FROM ({$sql}) AS t";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($this->lastFilterParams);
        return (int)$stmt->fetchColumn();
    }

    /** پارامترهای آخرین فیلتر ساخته‌شده (برای countAll) */
    private array $lastFilterParams = [];

    /**
     * ساخت کوئری مشترک فیلترها (دریافت همهٔ ستون‌های لازم با JOIN)
     */
    private function buildFilterQuery(array $filters): array
    {
        $sql = "SELECT a.*, j.license_title, j.experience_months, j.training_method,
                       p.province, p.county, p.city, p.village, p.ownership_status
                FROM applicants a
                LEFT JOIN job_details j ON a.id = j.applicant_id
                LEFT JOIN property_details p ON a.id = p.applicant_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['license_title'])) {
            $sql .= " AND j.license_title LIKE :license_title";
            $params['license_title'] = '%' . $filters['license_title'] . '%';
        }
        if (!empty($filters['village'])) {
            $sql .= " AND p.village LIKE :village";
            $params['village'] = '%' . $filters['village'] . '%';
        }
        if (!empty($filters['city'])) {
            $sql .= " AND p.city LIKE :city";
            $params['city'] = '%' . $filters['city'] . '%';
        }
        if (!empty($filters['tracking_code'])) {
            $sql .= " AND a.tracking_code LIKE :tracking_code";
            $params['tracking_code'] = '%' . $filters['tracking_code'] . '%';
        }
        if (!empty($filters['name'])) {
            $sql .= " AND (a.first_name LIKE :name OR a.last_name LIKE :name2)";
            $params['name'] = '%' . $filters['name'] . '%';
            $params['name2'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['national_code'])) {
            $sql .= " AND a.national_code LIKE :national_code";
            $params['national_code'] = '%' . $filters['national_code'] . '%';
        }
        if (!empty($filters['mobile'])) {
            $sql .= " AND a.mobile LIKE :mobile";
            $params['mobile'] = '%' . $filters['mobile'] . '%';
        }
        if (!empty($filters['province'])) {
            $sql .= " AND p.province LIKE :province";
            $params['province'] = '%' . $filters['province'] . '%';
        }
        if (!empty($filters['status'])) {
            $sql .= " AND a.current_status = :status";
            $params['status'] = $filters['status'];
        }

        $this->lastFilterParams = $params;
        return [$sql, $params];
    }

    /**
     * به‌روزرسانی پرچم اولویت
     */
    public function updatePriority(int $id, int $priority): void
    {
        $stmt = $this->db->prepare("UPDATE applicants SET priority = :priority WHERE id = :id");
        $stmt->execute(['priority' => $priority, 'id' => $id]);
    }

    /**
     * به‌روزرسانی کد پستی
     */
    public function updatePostalCode(int $id, string $postalCode): void
    {
        $stmt = $this->db->prepare("UPDATE applicants SET postal_code = :postal_code, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['postal_code' => $postalCode, 'id' => $id]);
    }

    /**
     * یافتن درخواست‌های قابل لغو (بیش از ۱۴ روز از ثبت درخواست صدور)
     */
    public function findExpiredApplications(int $days): array
    {
        // وضعیت‌های مشمول لغو خودکار از مرجع مرکزی خوانده می‌شوند
        // (همهٔ پارامترها نام‌دار — اختلاط نام‌دار/موقعیتی در PDO خطا می‌دهد)
        $preIssue = (require CONFIG_PATH . '/statuses.php')['pre_issue'];
        $named = [];
        foreach (array_values($preIssue) as $i => $st) {
            $named['st' . $i] = $st;
        }
        $in = implode(',', array_map(fn($k) => ':' . $k, array_keys($named)));
        $sql = "SELECT a.* FROM applicants a
                INNER JOIN status_log sl ON a.id = sl.applicant_id
                WHERE sl.status_name = 'ثبت درخواست صدور'
                AND a.current_status IN ($in)
                AND sl.created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->db->prepare($sql);
        $named['days'] = $days;
        $stmt->execute($named);
        return $stmt->fetchAll();
    }

    /**
     * تولید کد رهگیری یکتا
     */
    public static function generateTrackingCode(): string
    {
        $prefix = 'HG';
        $unique = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $code = $prefix . '-' . $unique;

        // بررسی یکتایی
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM applicants WHERE tracking_code = :code");
        $stmt->execute(['code' => $code]);

        if ($stmt->fetchColumn() > 0) {
            return self::generateTrackingCode(); // تلاش مجدد
        }

        return $code;
    }
}