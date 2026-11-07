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
            'license_title' => $data['license_title'],
            'experience_months' => $data['experience_months'],
            'training_method' => $data['training_method']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByApplicantId(int $applicantId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM job_details WHERE applicant_id = :id LIMIT 1");
        $stmt->execute(['id' => $applicantId]);
        return $stmt->fetch() ?: null;
    }
}