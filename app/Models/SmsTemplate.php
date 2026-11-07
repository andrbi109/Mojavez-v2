<?php
/**
 * مدل الگوهای پیامک (فاز ۵ نقشهٔ راه)
 * متن/وضعیت مقصد الگوها تماماً از پنل مدیریت قابل تغییر است.
 */
class SmsTemplate
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getActive(): array
    {
        return $this->db->query(
            "SELECT * FROM sms_templates WHERE is_active = 1 ORDER BY sort_order, id"
        )->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM sms_templates ORDER BY sort_order, id")->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sms_templates WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sms_templates (title, sample_text, target_status, requires_code, is_active, sort_order)
             VALUES (:title, :sample_text, :target_status, :requires_code, :is_active, :sort_order)"
        );
        $stmt->execute([
            'title' => $data['title'],
            'sample_text' => $data['sample_text'] ?? null,
            'target_status' => $data['target_status'],
            'requires_code' => !empty($data['requires_code']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE sms_templates SET title = :title, sample_text = :sample_text,
                    target_status = :target_status, requires_code = :requires_code,
                    is_active = :is_active, sort_order = :sort_order
             WHERE id = :id"
        );
        $stmt->execute([
            'title' => $data['title'],
            'sample_text' => $data['sample_text'] ?? null,
            'target_status' => $data['target_status'],
            'requires_code' => !empty($data['requires_code']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        // اگر ورودی ثبت‌شده دارد → غیرفعال
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_sms_entries WHERE sms_template_id = :id");
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $this->db->prepare("UPDATE sms_templates SET is_active = 0 WHERE id = :id")->execute(['id' => $id]);
            return false;
        }
        $this->db->prepare("DELETE FROM sms_templates WHERE id = :id")->execute(['id' => $id]);
        return true;
    }
}
