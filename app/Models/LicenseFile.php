<?php
/**
 * مدل تصویر مجوز صادرشده (فاز ۶ نقشهٔ راه)
 */
class LicenseFile
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByApplicantId(int $applicantId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM license_files WHERE applicant_id = :aid LIMIT 1");
        $stmt->execute(['aid' => $applicantId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * ثبت/جایگزینی تصویر مجوز یک پرونده
     */
    public function upsert(int $applicantId, int $documentId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO license_files (applicant_id, document_id)
             VALUES (:aid, :did)
             ON DUPLICATE KEY UPDATE document_id = :did2"
        );
        $stmt->execute(['aid' => $applicantId, 'did' => $documentId, 'did2' => $documentId]);
    }

    public function incrementDownload(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE license_files SET download_count = download_count + 1,
                    first_accessed_at = COALESCE(first_accessed_at, NOW())
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }
}
