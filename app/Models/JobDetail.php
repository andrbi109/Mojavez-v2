<?php
class JobDetail
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO job_details (applicant_id, license_title, experience_months, training_method)
            VALUES (:applicant_id, :license_title, :experience_months, :training_method)"
        );

        $stmt->execute([
            'applicant_id' => $data['applicant_id'],
            'license_title' => $data['license_title'] ?? '',
            'experience_months' => $data['experience_months'] ?? 0,
            'training_method' => $data['training_method'] ?? 'کارگاهی'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByApplicantId(int $applicantId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM job_details WHERE applicant_id = :id LIMIT 1");
        $stmt->execute(['id' => $applicantId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * به‌روزرسانی اطلاعات شغلی یک متقاضی.
     * اگر رکوردی موجود نبود، آن را می‌سازد (upsert).
     */
    public function updateByApplicantId(int $applicantId, array $data): void
    {
        $existing = $this->findByApplicantId($applicantId);
        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE job_details
                 SET license_title = :lt, experience_months = :em, training_method = :tm
                 WHERE applicant_id = :aid"
            );
            $stmt->execute([
                'lt' => $data['license_title'] ?? '',
                'em' => (int)($data['experience_months'] ?? 0),
                'tm' => $data['training_method'] ?? 'کارگاهی',
                'aid' => $applicantId,
            ]);
        } else {
            $this->create([
                'applicant_id' => $applicantId,
                'license_title' => $data['license_title'] ?? '',
                'experience_months' => $data['experience_months'] ?? 0,
                'training_method' => $data['training_method'] ?? 'کارگاهی',
            ]);
        }
    }
}