<?php
class Document
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO documents (applicant_id, doc_type, file_name, original_name, file_path)
            VALUES (:applicant_id, :doc_type, :file_name, :original_name, :file_path)"
        );

        $stmt->execute([
            'applicant_id' => $data['applicant_id'],
            'doc_type' => $data['doc_type'],
            'file_name' => $data['file_name'],
            'original_name' => $data['original_name'],
            'file_path' => $data['file_path']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByApplicantId(int $applicantId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE applicant_id = :id ORDER BY uploaded_at ASC");
        $stmt->execute(['id' => $applicantId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * حذف یک مدرک (با حذف فیزیکی فایل از دیسک).
     * @return bool آیا حذف شد؟
     */
    public function delete(int $id): bool
    {
        $doc = $this->findById($id);
        if (!$doc) return false;

        // حذف رکورد دیتابیس
        $stmt = $this->db->prepare("DELETE FROM documents WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $deleted = $stmt->rowCount() > 0;

        // حذف فایل فیزیکی از دیسک (با FileStorage برای امنیت مسیر)
        if ($deleted && !empty($doc['file_path'])) {
            $storage = new FileStorage();
            $storage->deleteFile($doc['file_path']);
        }
        return $deleted;
    }
}