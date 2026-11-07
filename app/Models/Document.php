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
}