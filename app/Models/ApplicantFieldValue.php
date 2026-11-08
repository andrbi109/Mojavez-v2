<?php
/**
 * مدل مقادیر فیلدهای پویای هر متقاضی
 * -------------------------------------------
 * مقادیر فیلدهای سفارشی فرم‌ساز (غیرسیستمی) در این جدول نگهداری می‌شوند
 * و در صفحهٔ جزئیات مدیر، نسخهٔ چاپی و خروجی CSV نمایش داده می‌شوند.
 */
class ApplicantFieldValue
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * ذخیرهٔ دسته‌ای مقادیر یک متقاضی
     * @param array<int, string> $valuesByFieldId نگاشت شناسهٔ فیلد → مقدار
     */
    public function saveMany(int $applicantId, array $valuesByFieldId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO applicant_field_values (applicant_id, field_id, field_value)
             VALUES (:aid, :fid, :val)
             ON DUPLICATE KEY UPDATE field_value = :val2"
        );
        foreach ($valuesByFieldId as $fieldId => $value) {
            $stmt->execute([
                'aid' => $applicantId,
                'fid' => (int)$fieldId,
                'val' => $value === null ? null : (string)$value,
                'val2' => $value === null ? null : (string)$value,
            ]);
        }
    }

    /**
     * مقادیر یک متقاضی همراه با تعریف فیلد (برای نمایش)
     */
    public function findByApplicantId(int $applicantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT v.field_value, f.label, f.field_key, f.field_type, f.step
             FROM applicant_field_values v
             JOIN form_fields f ON f.id = v.field_id
             WHERE v.applicant_id = :aid
             ORDER BY f.step, f.sort_order, f.id"
        );
        $stmt->execute(['aid' => $applicantId]);
        return $stmt->fetchAll();
    }

    /**
     * مقادیر ذخیره‌شدهٔ یک متقاضی — نگاشت field_id → field_value.
     * برای پیش‌پر کردن فرم ویرایش فیلدهای پویا.
     *
     * @return array<int, string> field_id → value
     */
    public function findKeyedByFieldId(int $applicantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT field_id, field_value FROM applicant_field_values WHERE applicant_id = :aid"
        );
        $stmt->execute(['aid' => $applicantId]);
        $map = [];
        while ($row = $stmt->fetch()) {
            $map[(int)$row['field_id']] = (string)$row['field_value'];
        }
        return $map;
    }

    /**
     * همهٔ فیلدهای سفارشی فعال (برای ستون‌های پویای گزارش CSV)
     */
    public function activeCustomFields(): array
    {
        return $this->db->query(
            "SELECT * FROM form_fields WHERE is_system = 0 AND is_active = 1 ORDER BY step, sort_order, id"
        )->fetchAll();
    }
}
