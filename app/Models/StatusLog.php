<?php
class StatusLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO status_log (applicant_id, status_name, description)
             VALUES (:applicant_id, :status_name, :description)"
        );
        $stmt->execute([
            'applicant_id' => $data['applicant_id'],
            'status_name' => $data['status_name'],
            'description' => $data['description'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findByApplicantId(int $applicantId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM status_log WHERE applicant_id = :id ORDER BY created_at ASC");
        $stmt->execute(['id' => $applicantId]);
        return $stmt->fetchAll();
    }

    /**
     * دریافت تاریخ ثبت یک وضعیت خاص
     */
    public function getStatusDate(int $applicantId, string $statusName): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT created_at FROM status_log WHERE applicant_id = :id AND status_name = :status ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute(['id' => $applicantId, 'status' => $statusName]);
        $row = $stmt->fetch();
        return $row ? $row['created_at'] : null;
    }
}